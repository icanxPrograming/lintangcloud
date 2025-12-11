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

  public function __construct()
  {
    $this->fileModel = new FileModel();
    $this->activityModel = new ActivityModel();
    $this->minio = new MinioClient();
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

  public function uploadFile(int $id_user, string $localPath, string $fileName, ?int $parentFileId = 0): ?array
  {
    // 1. Validasi file ada
    if (!file_exists($localPath)) {
      error_log("❌ File tidak ditemukan: $localPath");
      return null;
    }

    $fileSize = filesize($localPath);
    $fileType = $this->getFileType($fileName);

    // 2. Tentukan object key MinIO
    $objectKey = "user{$id_user}/uploads/" . uniqid() . "-" . $fileName;

    try {
      // 3. Upload ke MinIO
      $this->minio->upload($objectKey, $localPath);
    } catch (Exception $e) {
      error_log("❌ MinIO Upload Error: " . $e->getMessage());
      return null;
    }

    // 4. Simpan metadata ke DB
    $data = [
      'id_user'          => $id_user,
      'parent_id'        => $parentFileId,
      'nama_file'        => $fileName,
      'minio_object_key' => $objectKey,
      'size'             => $fileSize,
      'jenis_file'       => $fileType
    ];

    $id_file = $this->fileModel->addFile($data);

    // 5. Catat aktivitas & update storage
    if ($id_file) {
      $this->activityModel->addActivity([
        'id_user'   => $id_user,
        'aktivitas' => "Upload file: $fileName"
      ]);

      $this->fileModel->updateUserStorage($id_user);

      return $this->fileModel->getFileById($id_file);
    }

    return null;
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
