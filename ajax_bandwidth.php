<?php
// Pastikan tidak ada output sebelum JSON
ob_start();

// Aktifkan error logging (tapi jangan tampilkan ke browser)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Load Session & Koneksi
require_once __DIR__ . '/config/Session.php';  // Session sudah handle sendiri
require_once __DIR__ . '/config/Koneksi.php';

use DB\Koneksi;

// Atur header JSON
header('Content-Type: application/json');

// Cek session user
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Ambil raw JSON dari JS
$input = file_get_contents('php://input');
error_log("Bandwidth AJAX Input: " . $input);

// Jika input kosong
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Decode JSON
$data = json_decode($input, true);

// Jika JSON rusak
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON: ' . json_last_error_msg(),
        'input_raw' => $input
    ]);
    exit;
}

// Validasi user ID
if (empty($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID tidak ditemukan']);
    exit;
}

$user_id = (int) $data['user_id'];
$upload_speed = isset($data['upload_speed']) ? (int) $data['upload_speed'] : 0;
$download_speed = isset($data['download_speed']) ? (int) $data['download_speed'] : 0;
$daily_upload = isset($data['daily_upload']) ? (int) $data['daily_upload'] : 0;
$daily_download = isset($data['daily_download']) ? (int) $data['daily_download'] : 0;

try {
    $db = Koneksi::getConnection();

    $sql = "UPDATE tuser SET
                upload_speed_limit   = :upload_speed,
                download_speed_limit = :download_speed,
                daily_upload_limit   = :daily_upload,
                daily_download_limit = :daily_download
            WHERE id_user = :id_user";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(':upload_speed', $upload_speed, PDO::PARAM_INT);
    $stmt->bindValue(':download_speed', $download_speed, PDO::PARAM_INT);
    $stmt->bindValue(':daily_upload', $daily_upload, PDO::PARAM_INT);
    $stmt->bindValue(':daily_download', $daily_download, PDO::PARAM_INT);
    $stmt->bindValue(':id_user', $user_id, PDO::PARAM_INT);

    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Batas bandwidth berhasil diperbarui.',
        'affected_rows' => $stmt->rowCount()
    ]);
} catch (PDOException $e) {

    error_log("DB ERROR (bandwidth): " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Database Error',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {

    error_log("GENERAL ERROR (bandwidth): " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'System Error'
    ]);
}

exit;
