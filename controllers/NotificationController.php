<?php

use Models\NotificationModel;
use Models\ActivityModel;

require_once __DIR__ . '/../vendor/autoload.php';
class NotificationController
{
  private $notifModel;
  private $activityModel;

  public function __construct()
  {
    $this->notifModel = new NotificationModel();
    $this->activityModel = new ActivityModel();
  }

  // Ambil semua notifikasi user
  public function getUserNotifications(int $id_user): array
  {
    return $this->notifModel->getNotificationsByUser($id_user);
  }

  // Tandai notifikasi sudah dibaca + catat aktivitas
  public function markNotificationAsRead(int $id_user, int $id_notif): bool
  {
    $success = $this->notifModel->markAsRead($id_notif);
    if ($success) {
      $this->activityModel->logActivity([
        'id_user' => $id_user,
        'aktivitas' => "Membaca notifikasi ID: $id_notif"
      ]);
    }
    return $success;
  }

  // Kirim notifikasi baru + catat aktivitas
  public function sendNotification(int $id_user, string $judul, string $pesan): bool
  {
    $success = $this->notifModel->addNotification([
      'id_user' => $id_user,
      'judul'   => $judul,
      'pesan'   => $pesan,
      'status'  => 'unread'
    ]);

    if ($success) {
      $this->activityModel->logActivity([
        'id_user' => $id_user,
        'aktivitas' => "Menerima notifikasi baru: $judul"
      ]);
    }

    return $success;
  }
}
