<?php

use Models\AdminModel;

require_once __DIR__ . '/../vendor/autoload.php';
class AdminController
{
  private $adminModel;

  public function __construct()
  {
    $this->adminModel = new AdminModel();
  }

  // Ambil semua user
  public function listUsers()
  {
    return $this->adminModel->getAllUsers();
  }

  // Update user info & storage
  public function updateUser($id_user, $data)
  {
    return $this->adminModel->updateUser($id_user, $data);
  }

  // Hapus user
  public function deleteUser($id_user)
  {
    return $this->adminModel->deleteUser($id_user);
  }

  // Monitor storage user
  public function getUserStorage($id_user)
  {
    return $this->adminModel->getUserStorageUsage($id_user);
  }

  public function getUserActivities($id_user)
  {
    return $this->adminModel->getUserActivities($id_user);
  }
}
