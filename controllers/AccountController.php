<?php

use Models\UserModel;

require_once __DIR__ . '/../config/Session.php';
require_once __DIR__ . '/../vendor/autoload.php';
class AuthController
{
  private $userModel;

  public function __construct()
  {
    $this->userModel = new UserModel();
  }

  // Register user baru
  public function register(array $data)
  {
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    $data['role'] = 'user';
    return $this->userModel->createUser($data);
  }

  // Ubah password user
  public function changePassword(int $id_user, string $newPassword)
  {
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    return $this->userModel->updatePassword($id_user, $hashed);
  }

  // Ambil semua user (hanya untuk admin)
  public function listUsers()
  {
    return $this->userModel->getAllUsers();
  }

  // Logout
  public function logout()
  {
    Session::destroy();
  }
}
