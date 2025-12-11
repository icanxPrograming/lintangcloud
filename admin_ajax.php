<?php
// admin_ajax.php
require_once __DIR__ . '/config/Koneksi.php';
require_once __DIR__ . '/config/Session.php';

use DB\Koneksi;

// Cek login dan status aktif user
Session::checkActiveLogin();

// Hanya admin yang boleh akses
if (!Session::isAdmin()) {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Akses ditolak!']);
  exit;
}

// Function untuk handle AJAX request
function handleAdminAjaxRequest()
{
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Proses tambah user
    if (isset($_POST['add_user'])) {
      $full_name = trim($_POST['full_name'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $password = trim($_POST['password'] ?? '');
      $storage_gb = (int)($_POST['storage_gb'] ?? 2);

      if (empty($full_name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Nama, Email, dan Password wajib diisi!'];
      }

      try {
        // Cek apakah email sudah ada
        $stmt = Koneksi::getConnection()->prepare("SELECT id_user FROM tuser WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
          return ['success' => false, 'message' => 'Email sudah terdaftar!'];
        }

        // Konversi GB ke bytes
        $storage_limit = $storage_gb * 1024 * 1024 * 1024;

        // Cek available storage di tstorage
        $stmt = Koneksi::getConnection()->prepare("SELECT available_storage FROM tstorage WHERE id_storage = 1");
        $stmt->execute();
        $storage_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$storage_info || $storage_info['available_storage'] < $storage_limit) {
          $available_gb = $storage_info ? number_format($storage_info['available_storage'] / (1024 ** 3), 2) : 0;
          return ['success' => false, 'message' => "Storage tidak mencukupi! Available: " . $available_gb . " GB, Dibutuhkan: " . $storage_gb . " GB"];
        }

        // Mulai transaction
        $db = Koneksi::getConnection();
        $db->beginTransaction();

        // Insert user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO tuser (email, password, full_name, role, storage_limit, created_at, status) VALUES (?, ?, ?, 'user', ?, NOW(), 'aktif')");
        $stmt->execute([$email, $hashed_password, $full_name, $storage_limit]);

        // Update tstorage - tambah allocated_storage
        $stmt = $db->prepare("UPDATE tstorage SET allocated_storage = allocated_storage + ? WHERE id_storage = 1");
        $stmt->execute([$storage_limit]);

        $db->commit();
        return ['success' => true, 'message' => 'User berhasil ditambahkan dengan paket ' . $storage_gb . ' GB!'];
      } catch (PDOException $e) {
        if (isset($db)) $db->rollBack();
        return ['success' => false, 'message' => 'Error saat menambah user: ' . $e->getMessage()];
      }
    }

    // Proses reset password
    if (isset($_POST['reset_password'])) {
      $id_user = (int)($_POST['id_user'] ?? 0);
      $new_password = trim($_POST['new_password'] ?? '');

      if ($id_user <= 0 || empty($new_password)) {
        return ['success' => false, 'message' => 'ID User dan Password baru wajib diisi!'];
      }

      try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = Koneksi::getConnection()->prepare("UPDATE tuser SET password = ? WHERE id_user = ? AND role = 'user'");
        $stmt->execute([$hashed_password, $id_user]);

        if ($stmt->rowCount() > 0) {
          return ['success' => true, 'message' => 'Password berhasil direset!'];
        } else {
          return ['success' => false, 'message' => 'User tidak ditemukan atau bukan role user!'];
        }
      } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error saat reset password: ' . $e->getMessage()];
      }
    }

    // Proses edit user
    if (isset($_POST['edit_user'])) {
      $id_user = (int)$_POST['id_user'] ?? 0;
      $full_name = trim($_POST['full_name'] ?? '');
      $new_storage_gb = (int)$_POST['storage_gb'] ?? 2;

      if ($id_user <= 0 || empty($full_name)) {
        return ['success' => false, 'message' => 'ID User dan Nama wajib diisi!'];
      }

      try {
        // Konversi GB ke bytes
        $new_storage_limit = $new_storage_gb * 1024 * 1024 * 1024;

        // Dapatkan storage_limit lama
        $stmt = Koneksi::getConnection()->prepare("SELECT storage_limit FROM tuser WHERE id_user = ? AND role = 'user'");
        $stmt->execute([$id_user]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
          return ['success' => false, 'message' => 'User tidak ditemukan atau bukan role user!'];
        }

        $old_storage_limit = $user['storage_limit'];
        $storage_difference = $new_storage_limit - $old_storage_limit;

        // Jika storage bertambah, cek available storage
        if ($storage_difference > 0) {
          $stmt = Koneksi::getConnection()->prepare("SELECT available_storage FROM tstorage WHERE id_storage = 1");
          $stmt->execute();
          $storage_info = $stmt->fetch(PDO::FETCH_ASSOC);

          if (!$storage_info || $storage_info['available_storage'] < $storage_difference) {
            $available_gb = $storage_info ? number_format($storage_info['available_storage'] / (1024 ** 3), 2) : 0;
            $needed_gb = number_format($storage_difference / (1024 ** 3), 2);
            return ['success' => false, 'message' => 'Storage tidak mencukupi untuk upgrade! Available: ' . $available_gb . ' GB, Dibutuhkan: ' . $needed_gb . ' GB'];
          }
        }

        // Mulai transaction
        $db = Koneksi::getConnection();
        $db->beginTransaction();

        // Update user
        $stmt = $db->prepare("UPDATE tuser SET full_name = ?, storage_limit = ? WHERE id_user = ? AND role = 'user'");
        $stmt->execute([$full_name, $new_storage_limit, $id_user]);

        if ($stmt->rowCount() > 0) {
          // Update tstorage - sesuaikan allocated_storage
          $stmt = $db->prepare("UPDATE tstorage SET allocated_storage = allocated_storage + ? WHERE id_storage = 1");
          $stmt->execute([$storage_difference]);

          $db->commit();

          $change_type = $storage_difference > 0 ? "ditambah" : ($storage_difference < 0 ? "dikurangi" : "tidak berubah");
          $change_gb = number_format(abs($storage_difference) / (1024 ** 3), 2);
          return ['success' => true, 'message' => 'User berhasil diperbarui dengan paket ' . $new_storage_gb . ' GB! Storage ' . $change_type . ' ' . $change_gb . ' GB.'];
        } else {
          $db->rollBack();
          return ['success' => false, 'message' => 'User tidak ditemukan atau bukan role user!'];
        }
      } catch (PDOException $e) {
        if (isset($db)) $db->rollBack();
        return ['success' => false, 'message' => 'Error saat edit user: ' . $e->getMessage()];
      }
    }

    // Proses hapus user
    if (isset($_POST['delete_user'])) {
      $id_user = (int)$_POST['id_user'] ?? 0;

      if ($id_user <= 0) {
        return ['success' => false, 'message' => 'ID User wajib diisi!'];
      }

      try {
        // Dapatkan storage_limit user yang akan dihapus
        $stmt = Koneksi::getConnection()->prepare("SELECT storage_limit FROM tuser WHERE id_user = ? AND role = 'user'");
        $stmt->execute([$id_user]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
          return ['success' => false, 'message' => 'User tidak ditemukan atau bukan role user!'];
        }

        $storage_limit = $user['storage_limit'];

        // Mulai transaction
        $db = Koneksi::getConnection();
        $db->beginTransaction();

        // Hapus user
        $stmt = $db->prepare("DELETE FROM tuser WHERE id_user = ? AND role = 'user'");
        $stmt->execute([$id_user]);

        if ($stmt->rowCount() > 0) {
          // Update tstorage - kurangi allocated_storage
          $stmt = $db->prepare("UPDATE tstorage SET allocated_storage = GREATEST(0, allocated_storage - ?) WHERE id_storage = 1");
          $stmt->execute([$storage_limit]);

          $db->commit();
          return ['success' => true, 'message' => 'User berhasil dihapus! Storage ' . number_format($storage_limit / (1024 ** 3), 2) . ' GB dikembalikan ke sistem.'];
        } else {
          $db->rollBack();
          return ['success' => false, 'message' => 'User tidak ditemukan atau bukan role user!'];
        }
      } catch (PDOException $e) {
        if (isset($db)) $db->rollBack();
        return ['success' => false, 'message' => 'Error saat menghapus user: ' . $e->getMessage()];
      }
    }

    // Proses nonaktifkan
    if (isset($_POST['deactivate_user'])) {
      $id_user = (int)$_POST['id_user'] ?? 0;

      if ($id_user <= 0) {
        return ['success' => false, 'message' => 'ID User wajib diisi!'];
      }

      try {
        $stmt = Koneksi::getConnection()->prepare("UPDATE tuser SET status = 'nonaktif' WHERE id_user = ? AND role = 'user'");
        $stmt->execute([$id_user]);

        if ($stmt->rowCount() > 0) {
          return ['success' => true, 'message' => 'User berhasil dinonaktifkan!'];
        } else {
          return ['success' => false, 'message' => 'User tidak ditemukan atau bukan role user!'];
        }
      } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error saat nonaktifkan user: ' . $e->getMessage()];
      }
    }

    // Proses aktifkan kembali
    if (isset($_POST['activate_user'])) {
      $id_user = (int)$_POST['id_user'] ?? 0;

      if ($id_user <= 0) {
        return ['success' => false, 'message' => 'ID User wajib diisi!'];
      }

      try {
        $stmt = Koneksi::getConnection()->prepare("UPDATE tuser SET status = 'aktif' WHERE id_user = ? AND role = 'user'");
        $stmt->execute([$id_user]);

        if ($stmt->rowCount() > 0) {
          return ['success' => true, 'message' => 'User berhasil diaktifkan kembali!'];
        } else {
          return ['success' => false, 'message' => 'User tidak ditemukan atau bukan role user!'];
        }
      } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error saat mengaktifkan user: ' . $e->getMessage()];
      }
    }
  }
  return null;
}

// Handle AJAX request
$ajaxResponse = handleAdminAjaxRequest();
if ($ajaxResponse) {
  header('Content-Type: application/json');
  echo json_encode($ajaxResponse);
  exit;
} else {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Tidak ada aksi yang valid']);
  exit;
}
