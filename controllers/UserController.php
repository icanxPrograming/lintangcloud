<?php

use Models\UserModel;

require_once __DIR__ . '/../vendor/autoload.php';

class UserController
{
  public $userModel;

  public function __construct()
  {
    $this->userModel = new UserModel();
  }

  public function getUserData(int $id_user): array
  {
    // Karena UserModel tidak punya method getUserById, kita buat query langsung
    $stmt = $this->userModel->getDb()->prepare("
            SELECT id_user, email, full_name, role, storage_used, storage_limit, created_at 
            FROM tuser 
            WHERE id_user = :id_user
        ");
    $stmt->execute(['id_user' => $id_user]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
  }

  public function updateUserData(int $id_user, array $data): bool
  {
    $allowedFields = ['full_name', 'email'];
    $updateData = [];

    foreach ($allowedFields as $field) {
      if (isset($data[$field])) {
        $updateData[$field] = $data[$field];
      }
    }

    if (empty($updateData)) {
      return false;
    }

    $setClause = implode(', ', array_map(fn($field) => "$field = :$field", array_keys($updateData)));
    $updateData['id_user'] = $id_user;

    $stmt = $this->userModel->getDb()->prepare("UPDATE tuser SET $setClause WHERE id_user = :id_user");
    return $stmt->execute($updateData);
  }

  public function changePassword(int $id_user, string $currentPassword, string $newPassword): bool
  {
    // Verifikasi password lama
    $stmt = $this->userModel->getDb()->prepare("SELECT password FROM tuser WHERE id_user = :id_user");
    $stmt->execute(['id_user' => $id_user]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($currentPassword, $user['password'])) {
      return false;
    }

    // Update password baru menggunakan method yang ada di UserModel
    return $this->userModel->updatePassword($id_user, $newPassword);
  }

  public function getStorageInfo(int $id_user): array
  {
    return $this->userModel->getStorageInfo($id_user);
  }
}
