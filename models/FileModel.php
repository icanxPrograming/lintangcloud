<?php

namespace Models;

use Core\Model;
use PDO;
use Exception;

require_once __DIR__ . '/../core/Model.php';

class FileModel extends Model
{
  // ========== TAMBAHKAN KONFIGURASI ==========
  private $allowedExtensions = [
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

  private $blockedExtensions = [
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
  // ===========================================

  /**
   * Validasi file sebelum insert ke database
   */
  private function validateFileData(array $data): array
  {
    $errors = [];

    // Validasi untuk file (bukan folder)
    if ($data['jenis_file'] !== 'folder') {
      $filename = $data['nama_file'];
      $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

      // Cek ekstensi berbahaya
      if (in_array($extension, $this->blockedExtensions)) {
        $errors[] = "Ekstensi .{$extension} tidak diizinkan karena alasan keamanan.";
      }

      // Cek ekstensi di allowlist
      if (!in_array($extension, $this->allowedExtensions)) {
        $errors[] = "Ekstensi .{$extension} tidak diizinkan.";
      }

      // Cek ukuran file (max 50MB)
      $maxSize = 50 * 1024 * 1024;
      if (isset($data['size']) && $data['size'] > $maxSize) {
        $errors[] = "Ukuran file melebihi batas maksimal.";
      }
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors
    ];
  }

  /**
   * Get allowed extensions (untuk keperluan client-side jika diperlukan)
   */
  public function getAllowedExtensions(): array
  {
    return $this->allowedExtensions;
  }

  /**
   * Get blocked extensions (untuk keperluan client-side jika diperlukan)
   */
  public function getBlockedExtensions(): array
  {
    return $this->blockedExtensions;
  }

  /**
   * Mulai transaction
   */
  public function beginTransaction(): bool
  {
    return $this->db->beginTransaction();
  }

  /**
   * Commit transaction
   */
  public function commit(): bool
  {
    return $this->db->commit();
  }

  /**
   * Rollback transaction
   */
  public function rollBack(): bool
  {
    return $this->db->rollBack();
  }

  /**
   * Ambil semua file user (untuk kompatibilitas)
   */
  public function getFilesByUser(int $id_user): array
  {
    $stmt = $this->db->prepare("
        SELECT * FROM tfile 
        WHERE id_user = :id_user 
        ORDER BY jenis_file DESC, nama_file ASC
    ");
    $stmt->execute(['id_user' => $id_user]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Ambil file user berdasarkan parent_id
   */
  public function getFilesByUserAndParent(int $id_user, int $parent_id = 0): array
  {
    $stmt = $this->db->prepare("
        SELECT id_file, parent_id, nama_file, minio_object_key, size, jenis_file, uploaded_at
        FROM tfile 
        WHERE id_user = :id_user 
        AND parent_id = :parent_id 
        ORDER BY jenis_file DESC, nama_file ASC
    ");
    $stmt->execute([
      'id_user' => $id_user,
      'parent_id' => $parent_id
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }


  public function updateObjectKey(int $id_file, string $newKey): bool
  {
    $stmt = $this->db->prepare("
        UPDATE tfile 
        SET minio_object_key = :new_key
        WHERE id_file = :id_file
    ");

    return $stmt->execute([
      'new_key' => $newKey,
      'id_file' => $id_file
    ]);
  }


  /**
   * Ambil semua file dalam folder (FIXED - lebih simple)
   */
  public function getFilesInFolderRecursive(int $id_user, int $folder_id): array
  {
    $stmt = $this->db->prepare("
        SELECT id_file, id_user, parent_id, nama_file, minio_object_key, size, jenis_file, uploaded_at
        FROM tfile
        WHERE id_user = :id_user
        AND (id_file = :folder_id OR parent_id = :folder_id)
        ORDER BY jenis_file DESC, nama_file ASC
    ");
    $stmt->execute([
      'id_user' => $id_user,
      'folder_id' => $folder_id
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Tambah file baru (atau folder)
  public function addFile(array $data)
  {
    error_log("📁 Data untuk insert file: " . print_r($data, true));

    $requiredFields = ['id_user', 'nama_file', 'jenis_file'];
    foreach ($requiredFields as $field) {
      if (!isset($data[$field])) {
        error_log("❌ Field $field tidak ada dalam data");
        return false;
      }
    }

    // ========== VALIDASI FILE DATA ==========
    if ($data['jenis_file'] !== 'folder') {
      $validation = $this->validateFileData($data);
      if (!$validation['valid']) {
        error_log("❌ File validation failed: " . implode(', ', $validation['errors']));
        return false;
      }
    }
    // =======================================

    // Set default values untuk field yang optional
    $data['parent_id'] = $data['parent_id'] ?? 0;
    $data['minio_object_key'] = $data['minio_object_key'] ?? null;
    $data['size'] = $data['size'] ?? 0;

    $stmt = $this->db->prepare("
      INSERT INTO tfile (id_user, parent_id, nama_file, minio_object_key, size, jenis_file, uploaded_at)
      VALUES (:id_user, :parent_id, :nama_file, :minio_object_key, :size, :jenis_file, NOW())
    ");

    try {
      $result = $stmt->execute($data);

      if ($result) {
        $id_file = (int) $this->db->lastInsertId();
        error_log("✅ File berhasil disimpan dengan ID: " . $id_file);
        return $id_file;
      } else {
        $errorInfo = $stmt->errorInfo();
        error_log('❌ Insert gagal: ' . print_r($errorInfo, true));
        return false;
      }
    } catch (Exception $e) {
      error_log('❌ Insert exception: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Pindahkan file/folder ke trash - SIMPLIFIED VERSION
   */
  public function moveToTrash(array $file): bool
  {
    if (!isset($file['id_user'], $file['id_file'], $file['nama_file'], $file['jenis_file'])) {
      error_log("❌ Data file tidak lengkap: " . print_r($file, true));
      return false;
    }

    error_log("🗑️ START moveToTrash: {$file['nama_file']} (ID: {$file['id_file']}, Type: {$file['jenis_file']})");

    // Mulai transaction
    $this->db->beginTransaction();

    try {
      // Jika ini folder, pindahkan dulu semua file di dalamnya
      if ($file['jenis_file'] === 'folder') {
        error_log("📁 Ini folder, mencari konten...");
        $childFiles = $this->getFilesInFolderRecursive($file['id_user'], $file['id_file']);
        error_log("📁 Ditemukan " . count($childFiles) . " file dalam folder");

        // Pindahkan semua child files (kecuali folder utama)
        foreach ($childFiles as $childFile) {
          if ($childFile['id_file'] != $file['id_file']) { // Skip folder utama
            error_log("📄 Pindahkan child: {$childFile['nama_file']} (ID: {$childFile['id_file']})");
            $this->moveSingleFileToTrash($childFile);
          }
        }
      }

      // Pindahkan file/folder utama
      error_log("🎯 Pindahkan file utama: {$file['nama_file']}");
      $this->moveSingleFileToTrash($file);

      $this->db->commit();
      error_log("✅ SUCCESS moveToTrash completed");
      return true;
    } catch (Exception $e) {
      $this->db->rollBack();
      error_log('❌ ERROR moveToTrash: ' . $e->getMessage());
      error_log('❌ Stack trace: ' . $e->getTraceAsString());
      return false;
    }
  }

  /**
   * Pindahkan single file ke trash (helper method) - SIMPLIFIED VERSION
   */
  private function moveSingleFileToTrash(array $file): bool
  {
    error_log("🔄 moveSingleFileToTrash: {$file['nama_file']} (ID: {$file['id_file']}, Parent: {$file['parent_id']})");

    $stmt = $this->db->prepare("
      INSERT INTO ttrash 
      (id_user, parent_id, original_id_file, nama_file, minio_object_key, size, jenis_file, deleted_at, original_parent_id)
      VALUES (:id_user, :parent_id, :original_id_file, :nama_file, :minio_object_key, :size, :jenis_file, :deleted_at, :original_parent_id)
  ");

    $result = $stmt->execute([
      'id_user' => $file['id_user'],
      'parent_id' => $file['parent_id'] ?? 0, // PERTAHANKAN PARENT_ID ASLI
      'original_id_file' => $file['id_file'],
      'nama_file' => $file['nama_file'],
      'minio_object_key' => $file['minio_object_key'] ?? null,
      'size' => $file['size'] ?? 0,
      'jenis_file' => $file['jenis_file'],
      'deleted_at' => date('Y-m-d H:i:s'),
      'original_parent_id' => $file['parent_id'] ?? 0
    ]);

    if (!$result) {
      $errorInfo = $stmt->errorInfo();
      error_log('❌ Gagal insert ttrash: ' . $errorInfo[2]);
      throw new Exception('Gagal memindahkan file ke trash: ' . $errorInfo[2]);
    }

    // Hapus dari tfile
    $del = $this->db->prepare("DELETE FROM tfile WHERE id_file = :id_file");
    $delOk = $del->execute(['id_file' => $file['id_file']]);

    if (!$delOk) {
      $errorInfo = $del->errorInfo();
      error_log('❌ Gagal delete dari tfile: ' . $errorInfo[2]);
      throw new Exception('Gagal menghapus file dari tfile: ' . $errorInfo[2]);
    }

    error_log("✅ Berhasil memindahkan {$file['nama_file']} ke trash");
    return true;
  }

  // Method lainnya tetap sama...
  public function getTotalStorageUsed(int $id_user): int
  {
    $stmt = $this->db->prepare("SELECT SUM(size) as total FROM tfile WHERE id_user = :id_user AND jenis_file != 'folder'");
    $stmt->execute(['id_user' => $id_user]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int) ($row['total'] ?? 0);
  }

  public function updateUserStorage(int $id_user): int
  {
    $totalUsed = $this->getTotalStorageIncludingTrash($id_user);
    $stmt = $this->db->prepare("UPDATE tuser SET storage_used = :used WHERE id_user = :id_user");
    $stmt->execute(['used' => $totalUsed, 'id_user' => $id_user]);
    return $totalUsed;
  }

  public function getUserStorage(int $id_user): array
  {
    $stmt = $this->db->prepare("SELECT storage_used, storage_limit FROM tuser WHERE id_user = :id_user");
    $stmt->execute(['id_user' => $id_user]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: ['storage_used' => 0, 'storage_limit' => 2147483648];
  }

  public function getTotalStorageIncludingTrash(int $id_user): int
  {
    $stmt1 = $this->db->prepare("SELECT SUM(size) as total FROM tfile WHERE id_user = :id_user AND jenis_file != 'folder'");
    $stmt1->execute(['id_user' => $id_user]);
    $fileTotal = (int) ($stmt1->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt2 = $this->db->prepare("SELECT SUM(size) as total FROM ttrash WHERE id_user = :id_user");
    $stmt2->execute(['id_user' => $id_user]);
    $trashTotal = (int) ($stmt2->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    return $fileTotal + $trashTotal;
  }

  public function getStorageLimit(int $id_user): int
  {
    $stmt = $this->db->prepare("SELECT storage_limit FROM tuser WHERE id_user = :id_user");
    $stmt->execute(['id_user' => $id_user]);
    return (int) $stmt->fetchColumn();
  }

  public function getFileById(int $id_file): ?array
  {
    $stmt = $this->db->prepare("
        SELECT id_file, id_user, parent_id, nama_file, minio_object_key, size, jenis_file
        FROM tfile
        WHERE id_file = :id_file
    ");
    $stmt->execute(['id_file' => $id_file]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    return $file ?: null;
  }

  public function renameFile(int $id_file, string $nama_file): bool
  {
    $stmt = $this->db->prepare("UPDATE tfile SET nama_file = :nama_file WHERE id_file = :id_file");
    return $stmt->execute(['nama_file' => $nama_file, 'id_file' => $id_file]);
  }

  // Tambahkan method ini di FileModel.php
  public function getStorageBreakdownWithTrash(int $id_user): array
  {
    // Query untuk file aktif
    $activeQuery = "
        SELECT
            SUM(size) AS total,
            SUM(CASE 
                WHEN LOWER(SUBSTRING_INDEX(nama_file, '.', -1)) IN ('jpg','jpeg','png','gif','bmp','webp','svg')
                THEN size ELSE 0 END) AS photo,
            SUM(CASE 
                WHEN LOWER(SUBSTRING_INDEX(nama_file, '.', -1)) IN ('mp4','mkv','mov','avi','webm','flv')
                THEN size ELSE 0 END) AS video,
            SUM(CASE 
                WHEN LOWER(SUBSTRING_INDEX(nama_file, '.', -1)) IN ('pdf','doc','docx','xls','xlsx','ppt','pptx','txt')
                THEN size ELSE 0 END) AS doc,
            SUM(CASE 
                WHEN LOWER(SUBSTRING_INDEX(nama_file, '.', -1)) NOT IN (
                    'jpg','jpeg','png','gif','bmp','webp','svg',
                    'mp4','mkv','mov','avi','webm','flv',
                    'pdf','doc','docx','xls','xlsx','ppt','pptx','txt'
                )
                THEN size ELSE 0 END) AS other
        FROM tfile
        WHERE id_user = ? AND jenis_file != 'folder'
    ";

    $stmt = $this->db->prepare($activeQuery);
    $stmt->execute([$id_user]);
    $activeBreakdown = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
      'total' => 0,
      'photo' => 0,
      'video' => 0,
      'doc' => 0,
      'other' => 0
    ];

    // Query untuk file di trash
    $trashQuery = "
        SELECT SUM(size) as trash_total 
        FROM ttrash 
        WHERE id_user = ?
    ";
    $stmt = $this->db->prepare($trashQuery);
    $stmt->execute([$id_user]);
    $trashResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $trashTotal = (float) ($trashResult['trash_total'] ?? 0);

    // Total storage digunakan (aktif + trash)
    $totalUsed = (float) $activeBreakdown['total'] + $trashTotal;

    if ($totalUsed <= 0) {
      return [
        'photo' => 0,
        'video' => 0,
        'doc' => 0,
        'other' => 0,
        'trash' => 0
      ];
    }

    // Hitung persentase masing-masing
    return [
      'photo' => round(($activeBreakdown['photo'] / $totalUsed) * 100, 2),
      'video' => round(($activeBreakdown['video'] / $totalUsed) * 100, 2),
      'doc' => round(($activeBreakdown['doc'] / $totalUsed) * 100, 2),
      'other' => round(($activeBreakdown['other'] / $totalUsed) * 100, 2),
      'trash' => round(($trashTotal / $totalUsed) * 100, 2)
    ];
  }

  public function getFolderByName(int $id_user, string $folderName, int $parent_id = 0): ?array
  {
    $stmt = $this->db->prepare("
        SELECT * FROM tfile 
        WHERE id_user = :id_user 
        AND nama_file = :folder_name 
        AND jenis_file = 'folder' 
        AND parent_id = :parent_id
    ");
    $stmt->execute([
      'id_user' => $id_user,
      'folder_name' => $folderName,
      'parent_id' => $parent_id
    ]);
    $folder = $stmt->fetch(PDO::FETCH_ASSOC);
    return $folder ?: null;
  }
}
