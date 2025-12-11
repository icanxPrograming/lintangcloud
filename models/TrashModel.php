<?php

namespace Models;

use Core\Model;
use PDO;

require_once __DIR__ . '/../core/Model.php';

class TrashModel extends Model
{
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
   * Ambil semua file di trash per user - HANYA TAMPILKAN ROOT LEVEL
   */
  public function getTrashByUser(int $id_user): array
  {
    // HANYA TAMPILKAN ITEMS YANG parent_id = 0 (ROOT LEVEL)
    $stmt = $this->db->prepare("
      SELECT t.* 
      FROM ttrash t
      LEFT JOIN ttrash parent ON t.parent_id = parent.original_id_file AND parent.id_user = :id_user
      WHERE t.id_user = :id_user 
      AND (t.parent_id = 0 OR parent.id_trash IS NULL)
      ORDER BY 
        CASE WHEN t.jenis_file = 'folder' THEN 0 ELSE 1 END,
        t.deleted_at DESC
    ");
    $stmt->execute(['id_user' => $id_user]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Ambil file trash berdasarkan id_trash
   */
  public function getTrashById(int $id_trash): ?array
  {
    $stmt = $this->db->prepare("
      SELECT id_trash, id_user, parent_id, original_id_file, original_parent_id, 
             nama_file, minio_object_key, size, jenis_file, deleted_at
      FROM ttrash
      WHERE id_trash = :id_trash
    ");
    $stmt->execute(['id_trash' => $id_trash]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    return $file ?: null;
  }

  /**
   * Ambil semua file dalam folder di trash (untuk restore)
   */
  public function getFilesInTrashFolder(int $id_user, int $folder_id): array
  {
    $stmt = $this->db->prepare("
      SELECT * FROM ttrash 
      WHERE id_user = :id_user AND parent_id = :folder_id
      ORDER BY jenis_file DESC, nama_file ASC
    ");
    $stmt->execute([
      'id_user' => $id_user,
      'folder_id' => $folder_id
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Restore file dari trash (hapus record di ttrash) - SIMPLE VERSION
   */
  public function restoreFile(int $id_trash): bool
  {
    $stmt = $this->db->prepare("DELETE FROM ttrash WHERE id_trash = :id_trash");
    return $stmt->execute(['id_trash' => $id_trash]);
  }

  /**
   * Hapus file secara permanen dari trash - ALIAS untuk konsistensi
   */
  public function deleteFile(int $id_trash): bool
  {
    return $this->restoreFile($id_trash); // Sama dengan restoreFile, hapus dari trash
  }

  /**
   * Hapus semua file dalam folder di trash secara permanen
   */
  public function deleteFolderContentsPermanently(int $id_user, int $original_id_file): bool
  {
    $childFiles = $this->getFilesInTrashFolder($id_user, $original_id_file);

    foreach ($childFiles as $childFile) {
      $this->deleteFile($childFile['id_trash']);
    }

    return true;
  }

  /**
   * Cek apakah file ada di trash berdasarkan original_id_file
   */
  public function isFileInTrash(int $original_id_file): bool
  {
    $stmt = $this->db->prepare("SELECT 1 FROM ttrash WHERE original_id_file = :original_id_file LIMIT 1");
    $stmt->execute(['original_id_file' => $original_id_file]);
    return (bool)$stmt->fetch();
  }
}
