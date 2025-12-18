<?php
require_once 'config/Session.php';
require_once __DIR__ . '/controllers/BackupController.php';

// Start output buffering
ob_start();

Session::checkLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$id_backup = isset($_POST['id_backup']) ? (int) $_POST['id_backup'] : (isset($_GET['id_backup']) ? (int) $_GET['id_backup'] : null);
$backup_name = $_POST['backup_name'] ?? $_GET['backup_name'] ?? null;
$days = isset($_POST['days']) ? (int) $_POST['days'] : (isset($_GET['days']) ? (int) $_GET['days'] : 30);

$id_user = $_SESSION['id_user'] ?? null;
if (!$id_user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$controller = new BackupController();
$response = [];

try {
    switch ($action) {
        case 'create':
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

        // ========== DOWNLOAD LOCAL - SPECIAL HANDLING ==========
        case 'download_local':
            // Hanya untuk admin
            if (($_SESSION['role'] ?? '') !== 'admin') {
                $response = ['success' => false, 'message' => 'Akses ditolak'];
                break;
            }

            if (!$backup_name) {
                $response = ['success' => false, 'message' => 'Backup name required'];
                break;
            }

            // Clear buffer untuk file download
            ob_end_clean();

            $result = $controller->downloadLocalBackup($backup_name);

            if ($result['success'] && isset($result['zip_file'])) {
                $zipFile = $result['zip_file'];

                if (!file_exists($zipFile)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'File ZIP tidak ditemukan']);
                    exit;
                }

                // Send file
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $backup_name . '.zip"');
                header('Content-Length: ' . filesize($zipFile));
                readfile($zipFile);

                // Cleanup
                if (file_exists($zipFile)) {
                    unlink($zipFile);
                }

                exit;
            } else {
                $response = $result;
            }
            break;

        case 'cleanup_local':
            if (($_SESSION['role'] ?? '') !== 'admin') {
                $response = ['success' => false, 'message' => 'Akses ditolak'];
                break;
            }
            $response = $controller->cleanupLocalBackups($days);
            break;

        case 'list_local':
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

// Hanya untuk non-download actions
ob_end_clean();
header('Content-Type: application/json');
echo json_encode($response);
