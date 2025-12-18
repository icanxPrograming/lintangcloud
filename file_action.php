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
    // ====================== UPLOAD FILE (SINGLE & WITH PARENT) ======================
    case 'upload_file':
    case 'upload_file_with_parent':
      if (!isset($_FILES['file'])) {
        throw new Exception('File tidak ditemukan');
      }

      $file = $_FILES['file'];

      // Tentukan parent_id
      $parent_id = ($action === 'upload_file_with_parent')
        ? (int)($_POST['parent_id'] ?? 0)
        : 0;

      error_log("📤 {$action} - File: {$file['name']}, Parent ID: {$parent_id}, Size: {$file['size']} bytes");

      // ========== VALIDASI DASAR SEBELUM KE CONTROLLER ==========

      // 1. Cek error upload PHP
      if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = self::getUploadErrorMessage($file['error']);
        throw new Exception("Upload gagal: {$errorMsg}");
      }

      // 2. Cek file kosong
      if ($file['size'] === 0) {
        throw new Exception("File kosong atau rusak.");
      }

      // 3. Cek ukuran file maksimal (50MB)
      $maxSize = 50 * 1024 * 1024; // 50MB
      if ($file['size'] > $maxSize) {
        $maxSizeMB = $maxSize / 1024 / 1024;
        throw new Exception("File terlalu besar! Maksimal {$maxSizeMB} MB.");
      }

      // 4. Validasi ekstensi file (BLOCKLIST approach)
      $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      $dangerousExtensions = [
        'php',
        'phtml',
        'php3',
        'php4',
        'php5',
        'php7',
        'phps',
        'php8',
        'exe',
        'bat',
        'cmd',
        'sh',
        'bash',
        'ps1',
        'jsp',
        'asp',
        'aspx',
        'pl',
        'py',
        'cgi',
        'htaccess',
        'htpasswd',
        'dll',
        'sys',
        'vbs',
        'scr',
        'msi'
      ];

      if (in_array($extension, $dangerousExtensions)) {
        error_log("❌ BLOCKED EXTENSION ATTEMPT: User {$id_user} mencoba upload .{$extension} - {$file['name']}");
        throw new Exception("File dengan ekstensi .{$extension} tidak diizinkan karena alasan keamanan!");
      }

      // 5. Validasi ekstensi (ALLOWLIST approach - opsional)
      $allowedExtensions = [
        'pdf',
        'doc',
        'docx',
        'txt',
        'rtf',
        'odt',
        'xls',
        'xlsx',
        'csv',
        'ods',
        'ppt',
        'pptx',
        'odp',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'bmp',
        'svg',
        'webp',
        'ico',
        'mp3',
        'mp4',
        'wav',
        'avi',
        'mov',
        'mkv',
        'flv',
        'webm',
        'zip',
        'rar',
        '7z',
        'tar',
        'gz',
        'json',
        'xml',
        'html',
        'css',
        'js'
      ];

      if (!in_array($extension, $allowedExtensions)) {
        $allowedList = implode(', ', $allowedExtensions);
        throw new Exception("Ekstensi .{$extension} tidak diizinkan. Ekstensi yang diizinkan: {$allowedList}");
      }

      // 6. Validasi MIME type sederhana (optional)
      $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/json',
        'application/xml',
        'text/html',
        'text/css',
        'audio/mpeg',
        'video/mp4',
        'audio/wav',
        'application/javascript',
        'text/javascript'
      ];

      // Cek MIME type hanya jika file_upload tersedia
      if (function_exists('mime_content_type') && file_exists($file['tmp_name'])) {
        $detectedMime = mime_content_type($file['tmp_name']);
        if (!in_array($detectedMime, $allowedMimeTypes)) {
          throw new Exception("Tipe file tidak didukung. File terdeteksi sebagai: {$detectedMime}");
        }
      }
      // ========== END OF VALIDASI ==========

      // Upload file melalui controller (validasi lebih lanjut di controller)
      $uploaded = $fileController->uploadFile($id_user, $file['tmp_name'], $file['name'], $parent_id);

      if ($uploaded) {
        error_log("✅ UPLOAD_SUCCESS - File: {$file['name']}, ID: {$uploaded['id_file']}, Parent: {$uploaded['parent_id']}");

        $storage = $fileController->getUserStorage($id_user);

        echo json_encode([
          'success' => true,
          'file' => $uploaded,
          'storage' => $storage,
          'message' => 'File berhasil diupload'
        ]);
      } else {
        // Jika controller return null, cek apakah karena validasi internal
        throw new Exception('Gagal mengunggah file. Format file mungkin tidak didukung atau terjadi kesalahan internal.');
      }
      break;
    // ====================== END UPLOAD FILE ======================

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

    case 'list_files':
      $parent_id = (int)($_POST['parent_id'] ?? 0);
      $files = $fileController->listFiles($id_user, $parent_id);
      echo json_encode($files);
      break;

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

    case 'check_folder_exists':
      $folderName = trim($_POST['folderName'] ?? '');
      $parent_id = (int)($_POST['parent_id'] ?? 0);

      if (empty($folderName)) {
        echo json_encode(['exists' => false, 'folder' => null]);
        break;
      }

      $existingFolder = $fileController->getFolderByName($id_user, $folderName, $parent_id);

      echo json_encode([
        'exists' => (bool)$existingFolder,
        'folder' => $existingFolder ?: null
      ]);
      break;

    case 'get_storage':
      $storage = $fileController->getUserStorage($id_user);
      echo json_encode($storage);
      break;

    case 'get_storage_breakdown_with_trash':
      $breakdown = $fileController->getStorageBreakdownWithTrash($id_user);
      echo json_encode($breakdown);
      break;

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

/**
 * Helper function untuk pesan error upload PHP
 */
function getUploadErrorMessage(int $errorCode): string
{
  switch ($errorCode) {
    case UPLOAD_ERR_INI_SIZE:
      return "Ukuran file melebihi batas upload_max_filesize di php.ini";
    case UPLOAD_ERR_FORM_SIZE:
      return "Ukuran file melebihi batas MAX_FILE_SIZE yang ditentukan di form";
    case UPLOAD_ERR_PARTIAL:
      return "File hanya terupload sebagian";
    case UPLOAD_ERR_NO_FILE:
      return "Tidak ada file yang dipilih";
    case UPLOAD_ERR_NO_TMP_DIR:
      return "Folder temporary tidak ditemukan";
    case UPLOAD_ERR_CANT_WRITE:
      return "Gagal menulis file ke disk";
    case UPLOAD_ERR_EXTENSION:
      return "Ekstensi file tidak diizinkan oleh ekstensi PHP";
    default:
      return "Terjadi kesalahan tidak diketahui saat upload file (Code: {$errorCode})";
  }
}
