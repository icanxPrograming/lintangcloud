<?php

namespace Models;

use Core\Model;
use PDO;

require_once __DIR__ . '/../core/Model.php';

class ActivityModel extends Model
{
  // Tambah aktivitas baru (sinkron dengan pemanggilan di FileController)
  public function addActivity(array $data)
  {
    $stmt = $this->db->prepare("
      INSERT INTO tactivity (id_user, aktivitas, waktu)
      VALUES (:id_user, :aktivitas, NOW())
    ");
    return $stmt->execute([
      'id_user' => $data['id_user'],
      'aktivitas' => $data['aktivitas']
    ]);
  }

  // Ambil riwayat aktivitas user (urut terbaru)
  public function getActivityByUser(int $id_user): array
  {
    $stmt = $this->db->prepare("
      SELECT * FROM tactivity 
      WHERE id_user = :id_user 
      ORDER BY waktu DESC
    ");
    $stmt->execute(['id_user' => $id_user]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function logActivity(array $data)
  {
    return $this->addActivity($data);
  }
}
