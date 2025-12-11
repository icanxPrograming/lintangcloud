<?php
require_once 'config/Session.php';
require_once __DIR__ . '/controllers/BackupController.php';

header('Content-Type: application/json');

// Ambil action & parameter
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$id_backup = isset($_POST['id_backup']) ? (int) $_POST['id_backup'] : (isset($_GET['id_backup']) ? (int) $_GET['id_backup'] : null);
$backup_name = $_POST['backup_name'] ?? $_GET['backup_name'] ?? null;
$days = isset($_POST['days']) ? (int) $_POST['days'] : (isset($_GET['days']) ? (int) $_GET['days'] : 30);

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$controller = new BackupController();

try {
    switch ($action) {
        case 'create':
            // Buat backup (termasuk backup lokal)
            $response = $controller->create();
            break;

        case 'restore':
            if ($id_backup) {
                $response = $controller->restore($id_backup);
            } else {
                $response = ['success' => false, 'message' => 'Backup ID required'];
            }
            break;

        case 'delete':
            if ($id_backup) {
                $response = $controller->delete($id_backup);
            } else {
                $response = ['success' => false, 'message' => 'Backup ID required'];
            }
            break;

        // ========== FUNGSI ADMIN UNTUK BACKUP LOKAL ==========
        case 'download_local':
            // Hanya untuk admin
            if (($_SESSION['role'] ?? '') !== 'admin') {
                $response = ['success' => false, 'message' => 'Akses ditolak'];
                break;
            }

            if ($backup_name) {
                $result = $controller->downloadLocalBackup($backup_name);

                if ($result['success'] && isset($result['zip_file'])) {
                    // Kirim file zip sebagai download
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . $backup_name . '.zip"');
                    header('Content-Length: ' . filesize($result['zip_file']));
                    readfile($result['zip_file']);

                    // Hapus file zip sementara setelah dikirim
                    unlink($result['zip_file']);
                    exit;
                } else {
                    $response = $result;
                }
            } else {
                $response = ['success' => false, 'message' => 'Backup name required'];
            }
            break;

        case 'cleanup_local':
            // Hanya untuk admin
            if (($_SESSION['role'] ?? '') !== 'admin') {
                $response = ['success' => false, 'message' => 'Akses ditolak'];
                break;
            }

            $response = $controller->cleanupLocalBackups($days);
            break;

        case 'list_local':
            // Hanya untuk admin
            if (($_SESSION['role'] ?? '') !== 'admin') {
                $response = ['success' => false, 'message' => 'Akses ditolak'];
                break;
            }

            $response = $controller->listLocalBackups();
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
            break;
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Terjadi error: ' . $e->getMessage()
    ];
}

// Kirim response JSON
echo json_encode($response);
