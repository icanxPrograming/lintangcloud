<?php

namespace Models;

use Core\Model;
use PDO;
// models/ShareModel.php
require_once __DIR__ . '/../core/Model.php';

class ShareModel extends Model
{
  // Bagikan file
  public function shareFile(array $data)
  {
    $stmt = $this->db->prepare("
            INSERT INTO tshare (id_file, share_token, shared_by, is_public) 
            VALUES (:id_file, :share_token, :shared_by, :is_public)
        ");
    return $stmt->execute($data);
  }

  // Ambil file yang dibagikan ke user tertentu
  public function getSharedFiles(int $id_user)
  {
    $stmt = $this->db->prepare("
            SELECT f.*, s.shared_at, s.is_public 
            FROM tfile f 
            JOIN tshare s ON f.id_file = s.id_file 
            WHERE s.shared_by = :id_user OR s.is_public = 1
        ");
    $stmt->execute(['id_user' => $id_user]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
