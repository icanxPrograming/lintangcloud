<?php
require_once 'config/Session.php';
require_once __DIR__ . '/controllers/BackupController.php';

// Helper functions
function sendJson($data)
{
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function downloadFile($filePath, $filename)
{
    ob_end_clean();

    if (!file_exists($filePath)) {
        sendJson(['success' => false, 'message' => 'File tidak ditemukan']);
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);

    // Cleanup
    if (file_exists($filePath) && strpos($filePath, sys_get_temp_dir()) === 0) {
        unlink($filePath);
    }

    exit;
}

// Main execution
ob_start();
Session::checkLogin();

$id_user = $_SESSION['id_user'] ?? null;
if (!$id_user) {
    sendJson(['success' => false, 'message' => 'Unauthorized']);
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$controller = new BackupController();

try {
    switch ($action) {
        case 'create':
            sendJson($controller->create());

        case 'restore':
            $id_backup = (int) ($_POST['id_backup'] ?? $_GET['id_backup'] ?? 0);
            if (!$id_backup) {
                sendJson(['success' => false, 'message' => 'Backup ID required']);
            }
            sendJson($controller->restore($id_backup));

        case 'delete':
            $id_backup = (int) ($_POST['id_backup'] ?? $_GET['id_backup'] ?? 0);
            if (!$id_backup) {
                sendJson(['success' => false, 'message' => 'Backup ID required']);
            }
            sendJson($controller->delete($id_backup));

        case 'download_local':
            // Hanya untuk admin
            if (($_SESSION['role'] ?? '') !== 'admin') {
                sendJson(['success' => false, 'message' => 'Akses ditolak']);
            }

            $backup_name = $_POST['backup_name'] ?? $_GET['backup_name'] ?? '';
            if (!$backup_name) {
                sendJson(['success' => false, 'message' => 'Backup name required']);
            }

            $result = $controller->downloadLocalBackup($backup_name);

            if ($result['success'] && isset($result['zip_file'])) {
                downloadFile($result['zip_file'], $backup_name . '.zip');
            }
            sendJson($result);

        case 'cleanup_local':
            if (($_SESSION['role'] ?? '') !== 'admin') {
                sendJson(['success' => false, 'message' => 'Akses ditolak']);
            }
            $days = (int) ($_POST['days'] ?? $_GET['days'] ?? 30);
            sendJson($controller->cleanupLocalBackups($days));

        case 'list_local':
            if (($_SESSION['role'] ?? '') !== 'admin') {
                sendJson(['success' => false, 'message' => 'Akses ditolak']);
            }
            sendJson($controller->listLocalBackups());

        default:
            sendJson(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    sendJson([
        'success' => false,
        'message' => 'Terjadi error: ' . $e->getMessage()
    ]);
}
