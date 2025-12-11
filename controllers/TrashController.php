<?php

use Models\TrashModel;
use Models\FileModel;
use Models\ActivityModel;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../minio.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class TrashController
{
  private $trashModel;
  private $fileModel;
  private $activityModel;
  private $minio;


  public function __construct()
  {
    $this->trashModel = new TrashModel();
    $this->fileModel = new FileModel();
    $this->activityModel = new ActivityModel();
    $this->minio = new MinioClient();
  }

  /**
   * Ambil daftar file di trash milik user
   */
  public function listTrash(int $userId): array
  {
    return $this->trashModel->getTrashByUser($userId);
  }

  /**
   * Restore file/folder dari ttrash ke tfile - FIXED VERSION
   */
  public function restoreFile(int $userId, int $id_trash): bool
  {
    // Mulai transaction menggunakan FileModel (karena kita akan insert ke tfile)
    $this->fileModel->beginTransaction();

    try {
      // Ambil data file di ttrash
      $file = $this->trashModel->getTrashById($id_trash);
      if (!$file || $file['id_user'] != $userId) {
        throw new Exception('File tidak ditemukan atau akses ditolak');
      }

      error_log("🔄 RESTORE PROCESS: {$file['nama_file']} (Type: {$file['jenis_file']}, Original Parent: {$file['original_parent_id']})");

      // Restore file/folder utama
      $newFileId = $this->restoreSingleFile($file);

      if (!$newFileId) {
        throw new Exception('Gagal restore file utama');
      }

      // Jika ini folder, restore semua isinya juga
      if ($file['jenis_file'] === 'folder') {
        error_log("📁 Restoring folder contents for: {$file['nama_file']}");
        $this->restoreFolderContents($userId, $file['original_id_file'], $newFileId);
      }

      // Hapus dari ttrash
      $deleteSuccess = $this->trashModel->deleteFile($id_trash);
      if (!$deleteSuccess) {
        throw new Exception('Gagal menghapus dari trash');
      }

      $this->fileModel->commit();

      // Update storage
      $this->fileModel->updateUserStorage($userId);

      // Catat aktivitas restore
      $this->activityModel->addActivity([
        'id_user' => $userId,
        'aktivitas' => "Restore file dari Trash: {$file['nama_file']}"
      ]);

      error_log("✅ SUCCESS restore file: {$file['nama_file']} -> New ID: {$newFileId}");
      return true;
    } catch (Exception $e) {
      $this->fileModel->rollBack();
      error_log('❌ ERROR restoring file: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Restore single file dari trash (helper method) - FIXED VERSION
   */
  private function restoreSingleFile(array $file): int
  {
    error_log("🔄 Restoring single file: {$file['nama_file']} (Original Parent: {$file['original_parent_id']})");

    // Data untuk insert ke tfile - GUNAKAN original_parent_id untuk menjaga struktur
    $fileData = [
      'id_user' => $file['id_user'],
      'parent_id' => $file['original_parent_id'] ?? 0, // Kembalikan ke parent asli
      'nama_file' => $file['nama_file'],
      'minio_object_key' => $file['minio_object_key'],
      'size' => $file['size'] ?? 0,
      'jenis_file' => $file['jenis_file']
    ];

    // Insert ke tfile menggunakan FileModel
    $id_file = $this->fileModel->addFile($fileData);

    if (!$id_file) {
      throw new Exception("Gagal insert ke tfile: {$file['nama_file']}");
    }

    error_log("✅ Restored to tfile with ID: {$id_file}, Parent: {$fileData['parent_id']}");
    return $id_file;
  }

  /**
   * Restore semua konten folder (recursive) - FIXED VERSION
   */
  private function restoreFolderContents(int $userId, int $original_folder_id, int $new_folder_id): void
  {
    error_log("📁 Restoring contents for folder: {$original_folder_id} -> {$new_folder_id}");

    // Cari semua file yang parent_id-nya = original_folder_id di trash
    $childFiles = $this->trashModel->getFilesInTrashFolder($userId, $original_folder_id);

    error_log("📁 Found " . count($childFiles) . " items in folder");

    foreach ($childFiles as $childFile) {
      error_log("📄 Restoring child: {$childFile['nama_file']} (ID: {$childFile['id_trash']})");

      // Update parent_id child menjadi new_folder_id
      $childFile['original_parent_id'] = $new_folder_id;

      // Restore child file
      $new_child_id = $this->restoreSingleFile($childFile);

      // Jika child adalah folder, restore kontennya juga (recursive)
      if ($childFile['jenis_file'] === 'folder') {
        $this->restoreFolderContents($userId, $childFile['original_id_file'], $new_child_id);
      }

      // Hapus child dari trash
      $this->trashModel->deleteFile($childFile['id_trash']);
    }
  }

  /**
   * Hapus file secara permanen dari trash
   */
  public function deletePermanently(int $userId, int $id_trash): array
  {
    $this->trashModel->beginTransaction();

    try {
      $file = $this->trashModel->getTrashById($id_trash);
      if (!$file || $file['id_user'] != $userId) {
        throw new Exception('File tidak ditemukan atau akses ditolak');
      }

      $result = ['success' => true, 'message' => ''];

      // Jika folder → hapus semua isinya dulu
      if ($file['jenis_file'] === 'folder') {
        $result = $this->deleteFolderContentsPermanently($userId, $file['original_id_file']);
        if (!$result['success']) {
          throw new Exception($result['message']);
        }
      }

      // Jika file biasa → hapus dari MinIO
      if ($file['jenis_file'] === 'file' && !empty($file['minio_object_key'])) {
        $minioRes = $this->minio->delete($file['minio_object_key']);
        if (!$minioRes['success']) {
          throw new Exception("Gagal hapus file di MinIO: " . $minioRes['message']);
        }
        $result['message'] .= $minioRes['message'];
      }

      // Hapus record di trash
      $deleted = $this->trashModel->deleteFile($id_trash);
      if (!$deleted) {
        throw new Exception("Gagal hapus record di database");
      }

      $this->fileModel->updateUserStorage($userId);
      $this->trashModel->commit();

      // Catat aktivitas
      $this->activityModel->addActivity([
        'id_user' => $userId,
        'aktivitas' => "Hapus permanen file/folder: {$file['nama_file']}"
      ]);

      return [
        'success' => true,
        'message' => $result['message'] ?: "File/folder '{$file['nama_file']}' berhasil dihapus permanen"
      ];
    } catch (Exception $e) {
      $this->trashModel->rollBack();
      return [
        'success' => false,
        'message' => $e->getMessage()
      ];
    }
  }

  /**
   * Hapus semua konten folder secara permanen (recursive)
   */
  private function deleteFolderContentsPermanently(int $userId, int $folder_id): array
  {
    $childFiles = $this->trashModel->getFilesInTrashFolder($userId, $folder_id);
    $result = ['success' => true, 'message' => ''];

    foreach ($childFiles as $childFile) {
      // Folder → recursive
      if ($childFile['jenis_file'] === 'folder') {
        $childResult = $this->deleteFolderContentsPermanently($userId, $childFile['original_id_file']);
        if (!$childResult['success']) {
          return $childResult;
        }
      }

      // File → hapus dari MinIO
      if ($childFile['jenis_file'] === 'file' && !empty($childFile['minio_object_key'])) {
        $minioRes = $this->minio->delete($childFile['minio_object_key']);
        if (!$minioRes['success']) {
          return [
            'success' => false,
            'message' => "Gagal hapus file '{$childFile['nama_file']}' di MinIO: " . $minioRes['message']
          ];
        }
      }

      // Hapus record dari trash
      $this->trashModel->deleteFile($childFile['id_trash']);
    }

    return $result;
  }
}
