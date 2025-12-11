<?php
require_once 'config/Session.php';
require_once 'controllers/UserController.php';

header('Content-Type: application/json');
Session::checkLogin();

$action = $_POST['action'] ?? '';
$userController = new UserController();
$id_user = Session::get('id_user');

try {
  // Validasi user exists
  $userData = $userController->getUserData($id_user);
  if (!$userData) {
    throw new Exception('User tidak ditemukan');
  }

  switch ($action) {
    case 'update_profile':
      $full_name = trim($_POST['full_name'] ?? '');
      $email = trim($_POST['email'] ?? '');

      // Validasi
      if ($full_name === '')
        throw new Exception('Nama lengkap tidak boleh kosong');
      if ($email === '')
        throw new Exception('Email tidak boleh kosong');
      if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        throw new Exception('Format email tidak valid');

      // Cek email sudah dipakai user lain
      $db = $userController->userModel->getDb();
      $stmt = $db->prepare("SELECT id_user FROM tuser WHERE email = :email AND id_user != :id_user");
      $stmt->execute(['email' => $email, 'id_user' => $id_user]);

      if ($stmt->fetch()) {
        throw new Exception('Email sudah digunakan oleh user lain');
      }

      // Update data
      $success = $userController->updateUserData($id_user, [
        'full_name' => $full_name,
        'email' => $email
      ]);

      if (!$success)
        throw new Exception('Gagal memperbarui profil');

      echo json_encode([
        'success' => true,
        'message' => 'Profil berhasil diperbarui'
      ]);
      break;


    case 'change_password':
      $current_password = $_POST['current_password'] ?? '';
      $new_password = $_POST['new_password'] ?? '';
      $confirm_password = $_POST['confirm_password'] ?? '';

      if ($current_password === '')
        throw new Exception('Password lama harus diisi');
      if ($new_password === '')
        throw new Exception('Password baru harus diisi');
      if ($confirm_password === '')
        throw new Exception('Konfirmasi password harus diisi');

      if ($new_password !== $confirm_password) {
        throw new Exception('Konfirmasi password tidak sesuai');
      }

      if (strlen($new_password) < 6) {
        throw new Exception('Password baru minimal 6 karakter');
      }

      if (!$userController->changePassword($id_user, $current_password, $new_password)) {
        throw new Exception('Password lama tidak sesuai');
      }

      echo json_encode([
        'success' => true,
        'message' => 'Password berhasil diubah'
      ]);
      break;


    default:
      throw new Exception('Action tidak dikenali: ' . $action);
  }
} catch (Exception $e) {
  error_log('Account action error: ' . $e->getMessage());
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
}
