<?php

namespace Models;

use Core\Model;
use PDO;

require_once __DIR__ . '/../core/Model.php';

class AdminModel extends Model
{
  // Ambil semua user
  public function getAllUsers()
  {
    $stmt = $this->db->query("SELECT * FROM tuser ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Ambil user berdasarkan ID
  public function getUserById(int $id_user)
  {
    $stmt = $this->db->prepare("SELECT * FROM tuser WHERE id_user = :id_user");
    $stmt->execute(['id_user' => $id_user]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Update data user (misal full_name, email, role)
  public function updateUser(int $id_user, array $data)
  {
    $stmt = $this->db->prepare("
            UPDATE tuser 
            SET full_name = :full_name, email = :email, role = :role, storage_limit = :storage_limit
            WHERE id_user = :id_user
        ");
    $data['id_user'] = $id_user;
    return $stmt->execute($data);
  }

  // Hapus user beserta semua file (ON DELETE CASCADE sudah di DB)
  public function deleteUser(int $id_user)
  {
    $stmt = $this->db->prepare("DELETE FROM tuser WHERE id_user = :id_user");
    return $stmt->execute(['id_user' => $id_user]);
  }

  // Ambil total storage yang digunakan oleh user
  public function getUserStorageUsage(int $id_user)
  {
    $stmt = $this->db->prepare("SELECT SUM(size) as total_used FROM tfile WHERE id_user = :id_user");
    $stmt->execute(['id_user' => $id_user]);
    return (int) $stmt->fetchColumn();
  }

  // Set ulang storage user (misal admin ingin reset penggunaan atau limit)
  public function updateUserStorage(int $id_user, int $storage_used, int $storage_limit)
  {
    $stmt = $this->db->prepare("
            UPDATE tuser 
            SET storage_used = :storage_used, storage_limit = :storage_limit 
            WHERE id_user = :id_user
        ");
    return $stmt->execute([
      'id_user' => $id_user,
      'storage_used' => $storage_used,
      'storage_limit' => $storage_limit
    ]);
  }

  // Ambil aktivitas terakhir user (misal monitoring)
  public function getUserActivities(int $id_user, int $limit = 50)
  {
    $stmt = $this->db->prepare("
            SELECT * FROM tactivity 
            WHERE id_user = :id_user 
            ORDER BY waktu DESC 
            LIMIT :limit
        ");
    $stmt->bindValue(':id_user', $id_user, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Ambil semua file dari user tertentu (misal untuk monitoring storage)
  public function getFilesByUser(int $id_user)
  {
    $stmt = $this->db->prepare("SELECT * FROM tfile WHERE id_user = :id_user ORDER BY uploaded_at DESC");
    $stmt->execute(['id_user' => $id_user]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
