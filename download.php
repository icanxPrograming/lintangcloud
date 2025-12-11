<?php
require_once __DIR__ . '/minio.php';

$objectKey = $_GET['file'] ?? '';
if (!$objectKey) {
  die("File tidak ditemukan");
}

$minio = new MinioClient();

try {
  $fileContent = $minio->downloadObject($objectKey);

  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="' . basename($objectKey) . '"');
  header('Content-Length: ' . strlen($fileContent));

  echo $fileContent;
  exit;
} catch (Exception $e) {
  die("Gagal download file: " . $e->getMessage());
}
