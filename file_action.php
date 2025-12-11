<?php
require_once 'config/Session.php';
require_once 'controllers/FileController.php';
require_once 'controllers/TrashController.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header JSON pertama kali
header('Content-Type: application/json');

try {
  Session::checkLogin();

  $action = $_POST['action'] ?? '';
  $fileController = new FileController();
  $trashController = new TrashController();
  $id_user = Session::get('id_user');

  // Validasi action
  if (empty($action)) {
    throw new Exception('Action tidak boleh kosong');
  }

  switch ($action) {
    case 'upload_file':
      if (!isset($_FILES['file'])) {
        throw new Exception('File tidak ditemukan');
      }

      $file = $_FILES['file'];
      $uploaded = $fileController->uploadFile($id_user, $file['tmp_name'], $file['name'], 0);

      if ($uploaded) {
        $storage = $fileController->getUserStorage($id_user);
        echo json_encode([
          'success' => true,
          'file' => $uploaded,
          'storage' => $storage
        ]);
      } else {
        throw new Exception('Gagal mengunggah file ke MinIO');
      }
      break;


    case 'create_folder':
      $folderName = trim($_POST['folderName'] ?? 'Folder Tanpa Nama');
      $parent_id = (int)($_POST['parent_id'] ?? 0);

      if (empty($folderName)) {
        throw new Exception('Nama folder tidak boleh kosong');
      }

      $folder = $fileController->createFolder($id_user, $folderName, $parent_id);
      echo json_encode([
        'success' => (bool)$folder,
        'folder' => $folder
      ]);
      break;

    // Tambahkan di switch case file_action.php
    case 'list_files':
      $parent_id = (int)($_POST['parent_id'] ?? 0);
      $files = $fileController->listFiles($id_user, $parent_id);
      echo json_encode($files);
      break;

    // Tambahkan case ini di switch statement file_action.php
    case 'list_trash':
      $trashFiles = $trashController->listTrash($id_user);
      echo json_encode($trashFiles);
      break;

    case 'rename_file':
      $id_file = (int)($_POST['id_file'] ?? 0);
      $newName = trim($_POST['newName'] ?? '');

      if ($id_file === 0) {
        throw new Exception('ID file tidak valid');
      }
      if (empty($newName)) {
        throw new Exception('Nama baru tidak boleh kosong');
      }

      $result = $fileController->renameFile($id_file, $newName);
      echo json_encode(['success' => $result]);
      break;

    case 'move_to_trash':
      $id_file = (int)($_POST['id_file'] ?? 0);

      if ($id_file === 0) {
        throw new Exception('ID file tidak valid');
      }

      $success = $fileController->moveToTrash($id_file);
      echo json_encode([
        'success' => $success,
        'message' => $success ? 'File berhasil dipindahkan ke sampah' : 'Gagal memindahkan file ke sampah'
      ]);
      break;

    // file_action.php - tambahkan case ini SEBELUM default case

    case 'check_folder_exists':
      $folderName = trim($_POST['folderName'] ?? '');
      $parent_id = (int)($_POST['parent_id'] ?? 0);

      if (empty($folderName)) {
        echo json_encode(['exists' => false, 'folder' => null]);
        break;
      }

      // Method untuk cek folder exist (perlu ditambahkan di FileController)
      $existingFolder = $fileController->getFolderByName($id_user, $folderName, $parent_id);

      echo json_encode([
        'exists' => (bool)$existingFolder,
        'folder' => $existingFolder ?: null
      ]);
      break;

    case 'upload_file_with_parent':
      if (!isset($_FILES['file'])) {
        throw new Exception('File tidak ditemukan');
      }

      $file = $_FILES['file'];
      $parent_id = (int)($_POST['parent_id'] ?? 0);

      error_log("📤 UPLOAD_FILE_WITH_PARENT - Parent ID: $parent_id, File: " . $file['name']);

      // Upload langsung ke MinIO
      $uploaded = $fileController->uploadFile($id_user, $file['tmp_name'], $file['name'], $parent_id);

      if ($uploaded) {
        error_log("✅ FILE_UPLOAD_SUCCESS - Parent ID: " . $uploaded['parent_id']);
        $storage = $fileController->getUserStorage($id_user);
        echo json_encode([
          'success' => true,
          'file' => $uploaded,
          'storage' => $storage
        ]);
      } else {
        throw new Exception('Gagal mengunggah ke MinIO');
      }
      break;


    case 'get_storage':
      $storage = $fileController->getUserStorage($id_user);
      echo json_encode($storage);
      break;

    // Tambahkan case ini di switch statement file_action.php
    case 'get_storage_breakdown_with_trash':
      $breakdown = $fileController->getStorageBreakdownWithTrash($id_user);
      echo json_encode($breakdown);
      break;

    // file_action.php - tambahkan case ini

    case 'get_folder_by_name':
      $folderName = trim($_POST['folderName'] ?? '');
      $parent_id = (int)($_POST['parent_id'] ?? 0);

      if (empty($folderName)) {
        echo json_encode([
          'success' => false,
          'folder' => null,
          'message' => 'Nama folder tidak boleh kosong'
        ]);
        break;
      }

      try {
        $existingFolder = $fileController->getFolderByName($id_user, $folderName, $parent_id);
        echo json_encode([
          'success' => true,
          'exists' => (bool)$existingFolder,
          'folder' => $existingFolder
        ]);
      } catch (Exception $e) {
        echo json_encode([
          'success' => false,
          'exists' => false,
          'folder' => null,
          'message' => $e->getMessage()
        ]);
      }
      break;

    case 'restore_file':
      $id_trash = (int)($_POST['id_trash'] ?? 0);
      error_log("🔄 RESTORE_FILE - Trash ID: $id_trash, User ID: $id_user");

      $success = $trashController->restoreFile($id_user, $id_trash);
      error_log("🔄 RESTORE_FILE - Result: " . ($success ? 'SUCCESS' : 'FAILED'));

      echo json_encode([
        'success' => $success,
        'message' => $success ? 'File berhasil dipulihkan' : 'Gagal memulihkan file'
      ]);
      break;

    case 'delete_permanent':
      $id_trash = (int)($_POST['id_trash'] ?? 0);

      // Hapus permanen
      $result = $trashController->deletePermanently($id_user, $id_trash);

      // Tambahkan info storage
      $result['storage'] = $fileController->getUserStorage($id_user);

      echo json_encode($result);
      break;


    default:
      throw new Exception('Action tidak dikenal: ' . $action);
  }
} catch (Exception $e) {
  // Pastikan response selalu JSON meski ada error
  error_log('Error in file_action.php: ' . $e->getMessage());
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
  exit;
}
