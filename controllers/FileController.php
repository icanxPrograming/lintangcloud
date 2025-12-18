<?php

use Models\FileModel;
use Models\ActivityModel;

require_once __DIR__ . '/../minio.php';
require_once __DIR__ . '/../vendor/autoload.php';

class FileController
{
  private $fileModel;
  private $activityModel;
  private $minio;

  // ========== TAMBAHKAN KONFIGURASI EKSTENSI ==========
  private $allowedExtensions = [
    // Dokumen
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

    // Gambar
    'jpg',
    'jpeg',
    'png',
    'gif',
    'bmp',
    'svg',
    'webp',
    'ico',

    // Media
    'mp3',
    'mp4',
    'wav',
    'avi',
    'mov',
    'mkv',
    'flv',
    'webm',

    // Archive
    'zip',
    'rar',
    '7z',
    'tar',
    'gz',

    // Lainnya
    'json',
    'xml',
    'html',
    'css',
    'js'
  ];

  private $blockedExtensions = [
    // Ekstensi berbahaya
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

  private $maxFileSize = 50 * 1024 * 1024; // 50MB
  // ====================================================

  public function __construct()
  {
    $this->fileModel = new FileModel();
    $this->activityModel = new ActivityModel();
    $this->minio = new MinioClient();
  }

  /**
   * Validasi file sebelum upload
   */
  private function validateUploadedFile(array $file): array
  {
    $errors = [];
    $filename = $file['name'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // Cek error upload PHP
    if ($file['error'] !== UPLOAD_ERR_OK) {
      $errors[] = $this->getUploadErrorMessage($file['error']);
    }

    // Cek ekstensi berbahaya
    if (in_array($extension, $this->blockedExtensions)) {
      $errors[] = "Ekstensi file .{$extension} tidak diizinkan karena alasan keamanan.";
    }

    // Cek ekstensi di allowlist
    if (!in_array($extension, $this->allowedExtensions)) {
      $allowedList = implode(', ', $this->allowedExtensions);
      $errors[] = "Ekstensi .{$extension} tidak diizinkan. Ekstensi yang diizinkan: {$allowedList}";
    }

    // Cek ukuran file
    if ($file['size'] > $this->maxFileSize) {
      $maxSizeMB = $this->maxFileSize / 1024 / 1024;
      $errors[] = "Ukuran file terlalu besar. Maksimal: {$maxSizeMB} MB";
    }

    // Cek file kosong
    if ($file['size'] === 0) {
      $errors[] = "File kosong atau rusak.";
    }

    // Validasi MIME type (opsional tapi recommended)
    if (!empty($file['tmp_name']) && file_exists($file['tmp_name'])) {
      $mimeValidation = $this->validateMimeType($file['tmp_name'], $extension);
      if (!$mimeValidation['valid']) {
        $errors[] = $mimeValidation['message'];
      }
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors,
      'extension' => $extension
    ];
  }

  /**
   * Validasi MIME type untuk mencegah spoofing
   */
  private function validateMimeType(string $tmpFilePath, string $expectedExtension): array
  {
    if (!file_exists($tmpFilePath)) {
      return ['valid' => false, 'message' => 'File tidak ditemukan'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = finfo_file($finfo, $tmpFilePath);
    finfo_close($finfo);

    // Mapping ekstensi ke MIME types yang valid
    $validMimeTypes = [
      // Images
      'jpg' => ['image/jpeg', 'image/jpg'],
      'jpeg' => ['image/jpeg', 'image/jpg'],
      'png' => ['image/png'],
      'gif' => ['image/gif'],
      'bmp' => ['image/bmp'],
      'svg' => ['image/svg+xml'],
      'webp' => ['image/webp'],
      'ico' => ['image/x-icon'],

      // Documents
      'pdf' => ['application/pdf'],
      'doc' => ['application/msword'],
      'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
      'txt' => ['text/plain'],
      'rtf' => ['application/rtf', 'text/rtf'],

      // Spreadsheets
      'xls' => ['application/vnd.ms-excel'],
      'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
      'csv' => ['text/csv', 'text/plain'],
      'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],

      // Presentations
      'ppt' => ['application/vnd.ms-powerpoint'],
      'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
      'odp' => ['application/vnd.oasis.opendocument.presentation'],

      // Media
      'mp3' => ['audio/mpeg'],
      'mp4' => ['video/mp4'],
      'wav' => ['audio/wav'],
      'avi' => ['video/x-msvideo'],
      'mov' => ['video/quicktime'],

      // Archive
      'zip' => ['application/zip', 'application/x-zip-compressed'],
      'rar' => ['application/x-rar-compressed'],
      '7z' => ['application/x-7z-compressed'],
      'tar' => ['application/x-tar'],
      'gz' => ['application/gzip'],

      // Others
      'json' => ['application/json'],
      'xml' => ['application/xml', 'text/xml'],
      'html' => ['text/html'],
      'css' => ['text/css'],
      'js' => ['application/javascript', 'text/javascript']
    ];

    // Jika ekstensi tidak ada di mapping, skip validasi MIME
    if (!isset($validMimeTypes[$expectedExtension])) {
      return ['valid' => true, 'message' => ''];
    }

    // Cek apakah MIME type yang terdeteksi sesuai dengan ekstensi
    if (!in_array($detectedMime, $validMimeTypes[$expectedExtension])) {
      return [
        'valid' => false,
        'message' => "Tipe file tidak valid. Ekstensi .{$expectedExtension} seharusnya memiliki MIME type: " .
          implode(' atau ', $validMimeTypes[$expectedExtension]) .
          ", tetapi terdeteksi: {$detectedMime}"
      ];
    }

    return ['valid' => true, 'message' => ''];
  }

  private function getUploadErrorMessage(int $errorCode): string
  {
    switch ($errorCode) {
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        return "Ukuran file terlalu besar.";
      case UPLOAD_ERR_PARTIAL:
        return "File hanya terupload sebagian.";
      case UPLOAD_ERR_NO_FILE:
        return "Tidak ada file yang dipilih.";
      case UPLOAD_ERR_NO_TMP_DIR:
        return "Folder temporary tidak ditemukan.";
      case UPLOAD_ERR_CANT_WRITE:
        return "Gagal menulis file ke disk.";
      case UPLOAD_ERR_EXTENSION:
        return "Ekstensi file tidak diizinkan oleh server.";
      default:
        return "Terjadi kesalahan saat upload file (Code: {$errorCode}).";
    }
  }

  /**
   * Dapatkan folder berdasarkan nama
   */
  public function getFolderByName(int $id_user, string $folderName, int $parent_id = 0): ?array
  {
    return $this->fileModel->getFolderByName($id_user, $folderName, $parent_id);
  }

  /**
   * Cek apakah folder dengan nama tertentu sudah ada
   */
  public function checkFolderExists(int $id_user, string $folderName, int $parent_id = 0): bool
  {
    return $this->fileModel->getFolderByName($id_user, $folderName, $parent_id) !== null;
  }

  /**
   * Upload file dengan validasi
   */
  public function uploadFile(int $id_user, string $localPath, string $fileName, ?int $parentFileId = 0): ?array
  {
    error_log("📤 START uploadFile: {$fileName}, Parent: {$parentFileId}");

    // 1. Validasi file ada
    if (!file_exists($localPath)) {
      error_log("❌ File tidak ditemukan: {$localPath}");
      return null;
    }

    $fileSize = filesize($localPath);

    // 2. Validasi ekstensi (tambahan keamanan)
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($extension, $this->blockedExtensions)) {
      error_log("❌ BLOCKED_EXTENSION: {$extension} untuk file {$fileName}");
      return null;
    }

    if (!in_array($extension, $this->allowedExtensions)) {
      error_log("❌ EXTENSION_NOT_ALLOWED: {$extension} untuk file {$fileName}");
      return null;
    }

    // 3. Validasi ukuran
    if ($fileSize > $this->maxFileSize) {
      error_log("❌ FILE_TOO_LARGE: {$fileName} ({$fileSize} bytes)");
      return null;
    }

    // 4. Tentukan object key MinIO dengan nama yang aman
    $safeFileName = $this->generateSafeFileName($fileName);
    $objectKey = "user{$id_user}/uploads/" . uniqid() . "_" . $safeFileName;

    try {
      // 5. Upload ke MinIO
      error_log("📤 Uploading to MinIO: {$objectKey}");
      $this->minio->upload($objectKey, $localPath);
    } catch (Exception $e) {
      error_log("❌ MinIO Upload Error: " . $e->getMessage());
      return null;
    }

    // 6. Tentukan jenis file untuk database
    $fileType = $this->getFileType($fileName);

    // 7. Simpan metadata ke DB
    $data = [
      'id_user'          => $id_user,
      'parent_id'        => $parentFileId,
      'nama_file'        => $fileName, // Simpan nama asli
      'minio_object_key' => $objectKey,
      'size'             => $fileSize,
      'jenis_file'       => $fileType
    ];

    $id_file = $this->fileModel->addFile($data);

    // 8. Catat aktivitas & update storage
    if ($id_file) {
      error_log("✅ File uploaded successfully: {$fileName} (ID: {$id_file})");

      $this->activityModel->addActivity([
        'id_user'   => $id_user,
        'aktivitas' => "Upload file: {$fileName}"
      ]);

      $this->fileModel->updateUserStorage($id_user);

      return $this->fileModel->getFileById($id_file);
    }

    error_log("❌ Failed to save file to database: {$fileName}");
    return null;
  }

  /**
   * Generate safe filename untuk MinIO
   */
  private function generateSafeFileName(string $originalName): string
  {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $filename = pathinfo($originalName, PATHINFO_FILENAME);

    // Hapus karakter berbahaya
    $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);

    return $safeName . '.' . $extension;
  }


  // Tambahkan method getFileType yang hilang
  private function getFileType(string $fileName): string
  {
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $videoTypes = ['mp4', 'mkv', 'mov', 'avi', 'webm'];
    $docTypes = ['pdf', 'doc', 'docx', 'xlsx', 'pptx', 'txt'];

    if (in_array($extension, $imageTypes)) {
      return 'image';
    } elseif (in_array($extension, $videoTypes)) {
      return 'video';
    } elseif (in_array($extension, $docTypes)) {
      return 'document';
    } else {
      return 'other';
    }
  }

  public function getFolderById(int $id_file): ?array
  {
    return $this->fileModel->getFileById($id_file);
  }

  /**
   * Buat folder baru (untuk internal use)
   */
  public function createFolderInternal(int $id_user, string $folderName, int $parent_id = 0): ?array
  {
    return $this->createFolder($id_user, $folderName, $parent_id);
  }

  public function createFolder(int $id_user, string $folderName, ?int $parentFileId = 0): ?array
  {
    error_log("📁 Memulai createFolder: $folderName, parent: $parentFileId, user: $id_user");

    // Validasi input
    $folderName = trim($folderName);
    if (empty($folderName)) {
      error_log("❌ Nama folder kosong");
      return null;
    }

    $data = [
      'id_user' => $id_user,
      'parent_id' => $parentFileId,
      'nama_file' => $folderName,
      'minio_object_key' => null,
      'size' => 0,
      'jenis_file' => 'folder'
    ];

    error_log("📁 Data untuk create folder: " . print_r($data, true));

    $id_file = $this->fileModel->addFile($data);

    if ($id_file) {
      error_log("✅ Folder berhasil dibuat dengan ID: $id_file");

      $this->activityModel->addActivity([
        'id_user' => $id_user,
        'aktivitas' => "Buat folder: $folderName"
      ]);

      // Return the complete folder data
      $folderData = $this->fileModel->getFileById($id_file);

      if ($folderData) {
        error_log("✅ Data folder berhasil diambil: " . print_r($folderData, true));
        return $folderData;
      } else {
        // Fallback jika tidak bisa fetch data
        error_log("⚠️ Tidak bisa fetch data folder, menggunakan fallback");
        return [
          'id_file' => $id_file,
          'nama_file' => $folderName,
          'jenis_file' => 'folder',
          'parent_id' => $parentFileId,
          'size' => 0,
          'uploaded_at' => date('Y-m-d H:i:s')
        ];
      }
    } else {
      error_log("❌ Gagal membuat folder di database");
      return null;
    }
  }

  public function renameFile(int $id_file, string $newName): bool
  {
    $file = $this->fileModel->getFileById($id_file);
    if (!$file) return false;

    // Hanya rename file biasa, folder tidak punya objectKey
    if ($file['jenis_file'] !== 'folder') {

      // Key lama
      $oldKey = $file['minio_object_key'];

      // Buat key baru
      $ext = pathinfo($file['nama_file'], PATHINFO_EXTENSION);
      $newKey = dirname($oldKey) . "/" . uniqid() . "-" . $newName;

      // Rename di MinIO
      $this->minio->rename($oldKey, $newKey);

      // Update DB key
      $this->fileModel->updateObjectKey($id_file, $newKey);
    }

    // Rename metadata database
    $result = $this->fileModel->renameFile($id_file, $newName);

    if ($result) {
      $this->activityModel->addActivity([
        'id_user' => $file['id_user'],
        'aktivitas' => "Rename: {$file['nama_file']} → $newName"
      ]);
    }

    return $result;
  }


  /**
   * Pindahkan file/folder ke trash - FIXED VERSION
   */
  public function moveToTrash(int $id_file): bool
  {
    error_log("🎯 FileController::moveToTrash called for ID: $id_file");

    $file = $this->fileModel->getFileById($id_file);
    if (!$file) {
      error_log("❌ File tidak ditemukan dengan ID: $id_file");
      return false;
    }

    error_log("📄 File data: " . print_r($file, true));

    $result = $this->fileModel->moveToTrash($file);

    if ($result) {
      $this->activityModel->addActivity([
        'id_user' => $file['id_user'],
        'aktivitas' => "Pindahkan ke sampah: {$file['nama_file']}"
      ]);
      // JANGAN update storage di sini
      error_log("✅ FileController::moveToTrash SUCCESS");
    } else {
      error_log("❌ FileController::moveToTrash FAILED");
    }

    return $result;
  }

  public function getUserStorage(int $id_user): array
  {
    // Ambil data storage dari model (harus mengembalikan storage_used & storage_limit)
    $storage = $this->fileModel->getUserStorage($id_user);

    $usedBytes = (int)($storage['storage_used'] ?? 0);
    $limitBytes = (int)($storage['storage_limit'] ?? (2 * 1024 ** 3)); // default 2GB jika tidak ada

    // 🔹 Konversi bertingkat (Bytes → KB → MB → GB)
    $usedDisplay = $this->formatBytes($usedBytes);
    $limitDisplay = $this->formatBytes($limitBytes);

    return [
      'used' => $usedDisplay,
      'limit' => $limitDisplay,
    ];
  }

  /**
   * Format ukuran bytes ke satuan yang sesuai (KB, MB, GB, dll)
   */
  private function formatBytes(int $bytes): string
  {
    if ($bytes < 1024) {
      return $bytes . ' B';
    } elseif ($bytes < 1024 ** 2) {
      return round($bytes / 1024, 2) . ' KB';
    } elseif ($bytes < 1024 ** 3) {
      return round($bytes / (1024 ** 2), 2) . ' MB';
    } else {
      return round($bytes / (1024 ** 3), 2) . ' GB';
    }
  }

  // Tambahkan method ini di FileController.php
  /**
   * Get storage breakdown including trash
   */
  public function getStorageBreakdownWithTrash(int $id_user): array
  {
    return $this->fileModel->getStorageBreakdownWithTrash($id_user);
  }


  public function listFiles(int $id_user, ?int $parentFileId = null): array
  {
    // Jika parentFileId null, tampilkan root files (parent_id = 0)
    $parent_id = $parentFileId ?? 0;
    return $this->fileModel->getFilesByUserAndParent($id_user, $parent_id);
  }
}
