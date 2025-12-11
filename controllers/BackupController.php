<?php

use Models\BackupModel;

require_once __DIR__ . '/../minio.php';
require_once __DIR__ . '/../vendor/autoload.php';
class BackupController
{
    private $backupModel;
    private $minio;
    private $localBackupPath;

    public function __construct()
    {
        $this->backupModel = new BackupModel();
        $this->minio = new MinioClient();

        // Tentukan path backup lokal yang aman
        $this->localBackupPath = $this->getSecureLocalBackupPath();

        // Pastikan folder backup lokal ada
        if (!file_exists($this->localBackupPath)) {
            mkdir($this->localBackupPath, 0700, true);
            $this->createSecureDirectory($this->localBackupPath);
        }
    }

    // Dapatkan path backup lokal yang aman
    private function getSecureLocalBackupPath()
    {
        // Prioritaskan lokasi yang aman berdasarkan OS
        $home = getenv('HOME') ?: getenv('USERPROFILE');

        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: AppData/Local/YourApp/backups/
            $appData = getenv('LOCALAPPDATA') ?: ($home . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Local');
            $path = $appData . DIRECTORY_SEPARATOR . 'FileManagerBackup' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            // macOS: ~/Library/Application Support/YourApp/backups/
            $path = $home . DIRECTORY_SEPARATOR . 'Library' . DIRECTORY_SEPARATOR .
                'Application Support' . DIRECTORY_SEPARATOR .
                'FileManagerBackup' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
        } else {
            // Linux/Unix: ~/.config/your-app/backups/ atau /var/backups/
            $path = $home . DIRECTORY_SEPARATOR . '.filemanager_backup' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
        }

        return $path;
    }

    // Buat direktori dengan pengamanan
    private function createSecureDirectory($path)
    {
        // Tambahkan file index.php/index.html untuk mencegah directory listing
        $indexFile = $path . DIRECTORY_SEPARATOR . 'index.php';
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, '<?php header("HTTP/1.0 403 Forbidden"); echo "Access Forbidden"; ?>');
        }
    }

    // CREATE BACKUP (dengan backup lokal)
    public function create()
    {
        if (!isset($_SESSION['id_user'])) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $id_user = $_SESSION['id_user'];
        $backup_name = 'backup_' . date('Ymd_His');
        $folderKey = "backup/{$backup_name}/";

        try {
            $totalSize = 0;
            $fileCount = 0;

            // Buat folder lokal untuk backup ini
            $backupId = uniqid('backup_', true);
            $localFolderPath = $this->localBackupPath . $backupId . DIRECTORY_SEPARATOR;

            if (!file_exists($localFolderPath)) {
                mkdir($localFolderPath, 0700, true);
            }

            // Simpan metadata backup
            $metadata = [
                'backup_name' => $backup_name,
                'minio_path' => $folderKey,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $id_user,
                'created_by_name' => $_SESSION['full_name'] ?? 'Unknown'
            ];

            // Ambil semua root folder user yang punya uploads
            $users = $this->backupModel->getAllUserIds();

            foreach ($users as $user) {
                $userId = $user['id_user'];

                // Dapat semua file di folder user
                $files = $this->backupModel->getFilesByParent($userId, 0); // parent_id = 0 → root
                foreach ($files as $file) {
                    $this->copyFileRecursive($file, $folderKey . "user{$userId}/uploads/", $localFolderPath, $userId);
                    $totalSize += (int) $file['size'];
                    $fileCount++;
                }
            }

            // Simpan metadata ke file lokal
            $metadataFile = $localFolderPath . 'metadata.json';
            file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));

            // Simpan summary backup
            $summaryFile = $localFolderPath . 'summary.txt';
            $summary = "BACKUP SUMMARY\n";
            $summary .= "==============\n";
            $summary .= "Backup Name: {$backup_name}\n";
            $summary .= "Created: " . date('Y-m-d H:i:s') . "\n";
            $summary .= "Created By: User ID {$id_user}\n";
            $summary .= "Total Users: " . count($users) . "\n";
            $summary .= "Total Files: {$fileCount}\n";
            $summary .= "Total Size: " . $this->formatBytes($totalSize) . "\n";
            $summary .= "MinIO Path: {$folderKey}\n";
            $summary .= "Local Path: " . realpath($localFolderPath) . "\n";
            file_put_contents($summaryFile, $summary);

            // Simpan mapping lokal (tanpa menyimpan di database)
            $this->saveLocalBackupMapping($backup_name, $localFolderPath);

            // ✅ PERBAIKAN DI SINI: Sesuaikan dengan struktur tbackup
            // backup_name: 'backup_20241217_120500'
            // file_name: sama dengan backup_name (karena kita backup semua file dalam satu backup)
            // file_path: 'backup/backup_20241217_120500/' (path di MinIO)
            // file_size: $totalSize
            // created_by: $id_user
            $this->backupModel->createBackup(
                $id_user,
                $backup_name,    // backup_name
                $backup_name,    // file_name (sama dengan backup_name)
                $folderKey,      // file_path (MinIO path)
                $totalSize       // file_size
            );

            return [
                'success' => true,
                'message' => 'Backup berhasil dibuat',
                'backup_name' => $backup_name,
                'total_size' => $this->formatBytes($totalSize),
                'file_count' => $fileCount
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Backup gagal: ' . $e->getMessage()];
        }
    }

    // Rekursif copy file dan subfolder ke folder backup global + simpan ke lokal
    private function copyFileRecursive($file, $currentMinIOPath, $localBasePath, $userId)
    {
        $name = $file['nama_file'] ?? 'unknown';
        $isFolder = empty($file['minio_object_key']);
        $targetMinIOPath = rtrim($currentMinIOPath, '/') . '/' . $name . ($isFolder ? '/' : '');

        // Path lokal
        $localFilePath = $localBasePath . 'user_' . $userId . DIRECTORY_SEPARATOR .
            str_replace('/', DIRECTORY_SEPARATOR, substr($targetMinIOPath, strlen($currentMinIOPath)));

        if ($isFolder) {
            // Buat folder di MinIO
            $this->minio->makeFolder($targetMinIOPath);

            // Buat folder di lokal
            if (!file_exists(dirname($localFilePath))) {
                mkdir(dirname($localFilePath), 0700, true);
            }
        } else {
            // Copy file ke MinIO
            $this->minio->copy($file['minio_object_key'], $targetMinIOPath);

            // Download dan simpan file ke lokal
            try {
                $fileContent = $this->minio->getObject($file['minio_object_key']);

                // Buat folder lokal jika belum ada
                if (!file_exists(dirname($localFilePath))) {
                    mkdir(dirname($localFilePath), 0700, true);
                }

                // Simpan file ke lokal
                file_put_contents($localFilePath, $fileContent);

                // Simpan metadata file
                $fileMetadata = [
                    'id_file' => $file['id_file'],
                    'file_name' => $name,
                    'minio_key' => $file['minio_object_key'],
                    'size' => $file['size'] ?? 0,
                    'local_path' => $localFilePath,
                    'saved_at' => date('Y-m-d H:i:s')
                ];

                $metadataDir = $localBasePath . 'metadata' . DIRECTORY_SEPARATOR;
                if (!file_exists($metadataDir)) {
                    mkdir($metadataDir, 0700, true);
                }

                $metadataFile = $metadataDir . 'file_' . $file['id_file'] . '.json';
                file_put_contents($metadataFile, json_encode($fileMetadata, JSON_PRETTY_PRINT));
            } catch (Exception $e) {
                // Log error tapi lanjutkan proses
                error_log("Gagal menyimpan file lokal: " . $e->getMessage());
            }
        }

        // Rekursif ke anak
        $children = $this->backupModel->getFilesByParent($file['id_user'], $file['id_file']);
        foreach ($children as $child) {
            $this->copyFileRecursive($child, $targetMinIOPath, $localBasePath, $userId);
        }
    }

    // Format bytes untuk display
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    // RESTORE BACKUP (tetap hanya dari MinIO, tidak sentuh lokal)
    public function restore(int $id_backup)
    {
        if (!isset($_SESSION['id_user'])) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $id_user = $_SESSION['id_user'];
        $backup = $this->backupModel->getBackupById($id_backup, $id_user);

        if (!$backup) {
            return ['success' => false, 'message' => 'Backup tidak ditemukan'];
        }

        try {
            $objects = $this->minio->listObjects(rtrim($backup['file_path'], '/'));
            $restoredCount = 0;

            foreach ($objects as $obj) {
                // Skip folder/directory
                if (substr($obj, -1) === '/') {
                    continue;
                }

                $filename = basename($obj);
                $restoreKey = "user{$id_user}/uploads/{$filename}";
                $this->minio->copy($obj, $restoreKey);
                $restoredCount++;
            }

            return [
                'success' => true,
                'message' => "Berhasil merestore {$restoredCount} file",
                'restored_count' => $restoredCount
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Restore gagal: ' . $e->getMessage()];
        }
    }

    // DELETE BACKUP (hanya hapus dari database dan MinIO, backup lokal tetap aman)
    public function delete(int $id_backup)
    {
        if (!isset($_SESSION['id_user'])) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $id_user = $_SESSION['id_user'];
        $backup = $this->backupModel->getBackupById($id_backup, $id_user);

        if (!$backup) {
            return ['success' => false, 'message' => 'Backup tidak ditemukan'];
        }

        try {
            // Hapus folder MinIO
            $this->minio->deleteFolder($backup['file_path']);

            // Hapus record DB
            $this->backupModel->deleteBackup($id_backup, $id_user);

            // Backup lokal TIDAK dihapus! Tetap aman sebagai arsip

            return [
                'success' => true,
                'message' => 'Backup berhasil dihapus dari database dan MinIO',
                'note' => 'Backup lokal tetap disimpan di lokasi aman'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Hapus backup gagal: ' . $e->getMessage()];
        }
    }

    // Simpan mapping backup lokal (file terpisah yang aman)
    private function saveLocalBackupMapping($backupName, $localPath)
    {
        $mappingFile = $this->localBackupPath . '.backup_mappings.json';

        $mappings = [];
        if (file_exists($mappingFile)) {
            $existing = file_get_contents($mappingFile);
            $mappings = json_decode($existing, true) ?: [];
        }

        $mappings[$backupName] = [
            'local_path' => $localPath,
            'created' => date('Y-m-d H:i:s'),
            'encrypted_path' => base64_encode($localPath)
        ];

        file_put_contents($mappingFile, json_encode($mappings, JSON_PRETTY_PRINT));

        // Set permission yang ketat
        chmod($mappingFile, 0600);
    }

    // Hapus direktori rekursif
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    // ========== FUNGSI TAMBAHAN UNTUK ADMIN ==========

    // Download backup lokal (hanya untuk admin)
    public function downloadLocalBackup(string $backupName)
    {
        $mappingFile = $this->localBackupPath . '.backup_mappings.json';

        if (!file_exists($mappingFile)) {
            return ['success' => false, 'message' => 'Mapping file tidak ditemukan'];
        }

        $mappings = json_decode(file_get_contents($mappingFile), true);

        if (!isset($mappings[$backupName]['encrypted_path'])) {
            return ['success' => false, 'message' => 'Backup lokal tidak ditemukan'];
        }

        $localPath = base64_decode($mappings[$backupName]['encrypted_path']);

        if (!file_exists($localPath)) {
            return ['success' => false, 'message' => 'Folder backup tidak ditemukan'];
        }

        // Buat zip dari folder backup
        $zipFile = $this->createZipBackup($localPath, $backupName);

        if ($zipFile && file_exists($zipFile)) {
            return [
                'success' => true,
                'zip_file' => $zipFile,
                'backup_name' => $backupName
            ];
        }

        return ['success' => false, 'message' => 'Gagal membuat file zip'];
    }

    // Buat ZIP dari folder backup
    private function createZipBackup($folderPath, $backupName)
    {
        if (!class_exists('ZipArchive')) {
            return false;
        }

        $zip = new ZipArchive();
        $zipFileName = $this->localBackupPath . $backupName . '.zip';

        if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folderPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($folderPath));
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();
            return $zipFileName;
        }

        return false;
    }

    // Cleanup backup lokal yang sudah tua (hanya untuk admin)
    public function cleanupLocalBackups(int $daysOld = 30)
    {
        $mappingFile = $this->localBackupPath . '.backup_mappings.json';

        if (!file_exists($mappingFile)) {
            return ['success' => true, 'message' => 'Tidak ada backup lokal', 'cleaned' => 0];
        }

        $mappings = json_decode(file_get_contents($mappingFile), true);

        $cleanedCount = 0;
        $cutoffTime = strtotime("-{$daysOld} days");

        foreach ($mappings as $backupName => $mapping) {
            if (strtotime($mapping['created']) < $cutoffTime) {
                $localPath = base64_decode($mapping['encrypted_path']);

                if (file_exists($localPath)) {
                    $this->deleteDirectory($localPath);
                    $cleanedCount++;
                }

                // Hapus file zip jika ada
                $zipFile = $this->localBackupPath . $backupName . '.zip';
                if (file_exists($zipFile)) {
                    unlink($zipFile);
                }

                // Hapus dari mapping
                unset($mappings[$backupName]);
            }
        }

        // Update mapping file
        file_put_contents($mappingFile, json_encode($mappings, JSON_PRETTY_PRINT));

        return [
            'success' => true,
            'message' => "Berhasil membersihkan {$cleanedCount} backup lokal yang lebih tua dari {$daysOld} hari",
            'cleaned' => $cleanedCount
        ];
    }

    // List backup lokal yang tersedia (hanya untuk admin)
    public function listLocalBackups()
    {
        $mappingFile = $this->localBackupPath . '.backup_mappings.json';

        if (!file_exists($mappingFile)) {
            return ['success' => true, 'backups' => []];
        }

        $mappings = json_decode(file_get_contents($mappingFile), true);

        $backups = [];
        foreach ($mappings as $backupName => $mapping) {
            $localPath = base64_decode($mapping['encrypted_path']);

            $backups[] = [
                'name' => $backupName,
                'created' => $mapping['created'],
                'exists' => file_exists($localPath),
                'size' => file_exists($localPath) ? $this->getDirectorySize($localPath) : 0
            ];
        }

        return ['success' => true, 'backups' => $backups];
    }

    // Hitung ukuran direktori
    private function getDirectorySize($path)
    {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
}
