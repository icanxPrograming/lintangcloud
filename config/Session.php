<?php
session_start();

use Db\Koneksi;

class Session
{
  public static function set($key, $value)
  {
    $_SESSION[$key] = $value;
  }

  public static function get($key)
  {
    return $_SESSION[$key] ?? null;
  }

  public static function destroy()
  {
    session_unset();
    session_destroy();
  }

  public static function checkLogin()
  {
    if (!isset($_SESSION['id_user'])) {
      header("Location: ../views/auth/login.php");
      exit;
    }
  }

  public static function isAdmin()
  {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
  }

  // Tambahan: Cek apakah user aktif
  public static function isActive()
  {
    return (isset($_SESSION['status']) && $_SESSION['status'] === 'aktif');
  }

  // Tambahan: Cek login dan status aktif
  public static function checkActiveLogin()
  {
    // Cek apakah user sudah login
    if (!isset($_SESSION['id_user'])) {
      header("Location: ../views/auth/login.php");
      exit;
    }

    // Untuk admin, tidak perlu cek status aktif
    if (self::isAdmin()) {
      return true;
    }

    // Untuk user biasa, cek status aktif
    if (!self::isActive()) {
      // Logout user yang tidak aktif
      self::destroy();
      header("Location: ../views/auth/login.php?error=account_inactive");
      exit;
    }
  }

  // Tambahan: Update session data dari database
  public static function refreshUserData()
  {
    if (isset($_SESSION['id_user'])) {
      try {
        $stmt = Koneksi::getConnection()->prepare("SELECT full_name, email, role, status, storage_limit, storage_used FROM tuser WHERE id_user = ?");
        $stmt->execute([$_SESSION['id_user']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
          // Update session dengan data terbaru
          $_SESSION['full_name'] = $user['full_name'];
          $_SESSION['email'] = $user['email'];
          $_SESSION['role'] = $user['role'];
          $_SESSION['status'] = $user['status'];
          $_SESSION['storage_limit'] = $user['storage_limit'];
          $_SESSION['storage_used'] = $user['storage_used'];

          return true;
        }
      } catch (PDOException $e) {
        error_log("Error refreshing user data: " . $e->getMessage());
      }
    }
    return false;
  }
}
