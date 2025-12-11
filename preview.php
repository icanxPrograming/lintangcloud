<?php
header("Content-Type: application/json");

require_once __DIR__ . '/config/Session.php';
require_once __DIR__ . '/config/Koneksi.php';
require_once __DIR__ . '/minio.php'; // pastikan path benar

// Cek session
if (!isset($_SESSION['id_user'])) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

// Cek parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
  echo json_encode(['status' => 'error', 'message' => 'ID diperlukan']);
  exit;
}

use Db\Koneksi;

$fileId = (int) $_GET['id'];
$userId = $_SESSION['id_user'];

try {
  $conn = Koneksi::getConnection();

  // Ambil data file
  $stmt = $conn->prepare("
        SELECT nama_file, minio_object_key, jenis_file, size 
        FROM tfile 
        WHERE id_file = ? AND id_user = ?
    ");
  $stmt->execute([$fileId, $userId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row || empty($row['minio_object_key'])) {
    echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan']);
    exit;
  }

  $minioKey = $row['minio_object_key'];

  // Gunakan MinioClient untuk membuat presigned URL
  $minio = new MinioClient();
  $fileUrl = $minio->getPresignedUrl($minioKey, '+10 minutes'); // URL berlaku 10 menit

  if (!$fileUrl) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat preview URL']);
    exit;
  }

  // Response ke JS
  echo json_encode([
    'status' => 'success',
    'url' => $fileUrl,
    'fileName' => $row['nama_file'],
    'fileType' => $row['jenis_file'],
    'fileSize' => $row['size']
  ]);
} catch (Exception $e) {
  echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
}
