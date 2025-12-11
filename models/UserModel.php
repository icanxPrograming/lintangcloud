<?php

namespace Models;

use Core\Model;
use PDO;

require_once __DIR__ . '/../core/Model.php';

class UserModel extends Model
{
  // Ambil user berdasarkan email
  public function getUserByEmail(string $email)
  {
    $stmt = $this->db->prepare("SELECT * FROM tuser WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getStorageInfo($id_user)
  {
    $stmt = $this->db->prepare("SELECT storage_used, storage_limit FROM tuser WHERE id_user = ?");
    $stmt->execute([$id_user]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Tambah user baru (otomatis buat folder MinIO namespace)
  public function createUser(array $data)
  {
    if (isset($data['password'])) {
      $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    $stmt = $this->db->prepare("
      INSERT INTO tuser (email, password, full_name, role, storage_used, storage_limit, minio_user_folder)
      VALUES (:email, :password, :full_name, :role, :storage_used, :storage_limit, :minio_user_folder)
    ");

    return $stmt->execute([
      'email' => $data['email'],
      'password' => $data['password'],
      'full_name' => $data['full_name'],
      'role' => $data['role'] ?? 'user',
      'storage_used' => $data['storage_used'] ?? 0,
      'storage_limit' => $data['storage_limit'] ?? 2147483648,
      'minio_user_folder' => $data['minio_user_folder'] ?? null
    ]);
  }

  // Update password user
  public function updatePassword(int $id_user, string $password)
  {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $this->db->prepare("UPDATE tuser SET password = :password WHERE id_user = :id_user");
    return $stmt->execute(['password' => $hashedPassword, 'id_user' => $id_user]);
  }

  // Update penggunaan storage
  public function updateStorageUsage(int $id_user, int $usedBytes)
  {
    $stmt = $this->db->prepare("UPDATE tuser SET storage_used = :used WHERE id_user = :id");
    return $stmt->execute(['used' => $usedBytes, 'id' => $id_user]);
  }

  // Ambil semua user (untuk admin)
  public function getAllUsers()
  {
    $stmt = $this->db->query("SELECT id_user, email, full_name, role, storage_used, storage_limit, created_at FROM tuser");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Method untuk mendapatkan koneksi database
  public function getDb()
  {
    return $this->db;
  }
}
