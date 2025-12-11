<?php

namespace Models;

use Core\Model;
use PDO;
// models/NotificationModel.php
require_once __DIR__ . '/../core/Model.php';

class NotificationModel extends Model
{
  // Ambil notifikasi user
  public function getNotificationsByUser(int $id_user)
  {
    $stmt = $this->db->prepare("SELECT * FROM tnotifikasi WHERE id_user = :id_user ORDER BY dikirim_pada DESC");
    $stmt->execute(['id_user' => $id_user]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Tambah notifikasi
  public function addNotification(array $data)
  {
    $stmt = $this->db->prepare("
            INSERT INTO tnotifikasi (id_user, judul, pesan, status) 
            VALUES (:id_user, :judul, :pesan, :status)
        ");
    return $stmt->execute($data);
  }

  // Tandai sudah dibaca
  public function markAsRead(int $id_notif)
  {
    $stmt = $this->db->prepare("UPDATE tnotifikasi SET status = 'read' WHERE id_notif = :id_notif");
    return $stmt->execute(['id_notif' => $id_notif]);
  }
}
