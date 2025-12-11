<?php

use Models\UserModel;
use Models\ActivityModel;

require_once __DIR__ . '/../config/Session.php';
require_once __DIR__ . '/../vendor/autoload.php';

class AuthController
{
  private $userModel;
  private $activityModel;

  public function __construct()
  {
    $this->userModel = new UserModel();
    $this->activityModel = new ActivityModel();
  }

  public function login($email, $password)
  {
    $user = $this->userModel->getUserByEmail($email);

    if ($user && password_verify($password, $user['password'])) {
      // Tambahkan pengecekan status akun (kecuali admin)
      if ($user['status'] === 'nonaktif' && $user['role'] !== 'admin') {
        Session::set('login_error', 'Akun Anda telah dinonaktifkan. Info selengkapnya hubungi admin.');
        return false;
      }

      // Simpan session user
      Session::set('id_user', $user['id_user']);
      Session::set('full_name', $user['full_name']);
      Session::set('role', $user['role']);
      Session::set('status', $user['status']); // Jangan lupa simpan status di session

      // Ambil data storage user (dari database)
      $storageData = $this->userModel->getStorageInfo($user['id_user']);
      if ($storageData) {
        Session::set('storage_used', $storageData['storage_used']);
        Session::set('storage_limit', $storageData['storage_limit']);
      }

      // Simpan aktivitas login
      $this->activityModel->addActivity([
        'id_user' => $user['id_user'],
        'aktivitas' => 'Login ke sistem LintangCloud'
      ]);

      return true;
    }

    return false;
  }

  public function logout()
  {
    $id_user = Session::get('id_user');
    if ($id_user) {
      // Simpan aktivitas logout (disesuaikan)
      $this->activityModel->addActivity([
        'id_user' => $id_user,
        'aktivitas' => 'Logout dari sistem LintangCloud'
      ]);
    }

    Session::destroy();
  }
}
