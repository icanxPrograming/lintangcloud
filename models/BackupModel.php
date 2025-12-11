<?php

namespace Models;

use Core\Model;
use PDO;

require_once __DIR__ . '/../core/Model.php';

class BackupModel extends Model
{
    // Ambil semua file milik user
    public function getAllFilesByUser(int $id_user): array
    {
        $stmt = $this->db->prepare("SELECT * FROM tfile WHERE id_user = ?");
        $stmt->execute([$id_user]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil semua id_user unik dari tfile
    public function getAllUserIds(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT id_user FROM tfile");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil semua file untuk user tertentu
    public function getFilesByParent($id_user, $parent_id = null)
    {
        $query = "
            SELECT *
            FROM tfile
            WHERE id_user = :id_user 
            AND parent_id " . ($parent_id === null ? "IS NULL" : "= :parent_id") . "
            ORDER BY uploaded_at ASC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id_user', $id_user, PDO::PARAM_INT);

        if ($parent_id !== null) {
            $stmt->bindValue(':parent_id', $parent_id, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Simpan record backup sesuai struktur tbackup
    public function createBackup(int $id_user, string $backup_name, string $file_name, string $file_path, int $file_size): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO tbackup (
                backup_name, 
                file_name, 
                file_path, 
                file_size, 
                backup_date, 
                status, 
                created_by
            ) VALUES (?, ?, ?, ?, NOW(), 'completed', ?)
        ");

        return $stmt->execute([
            $backup_name,
            $file_name,
            $file_path,
            $file_size,
            $id_user
        ]);
    }

    // Dapatkan ID terakhir yang diinsert
    public function getLastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    // Ambil backup berdasarkan ID (dengan atau tanpa user)
    public function getBackupById(int $id_backup, ?int $id_user = null): ?array
    {
        if ($id_user !== null) {
            $stmt = $this->db->prepare("
                SELECT tb.*, tu.full_name as created_by_name
                FROM tbackup tb
                LEFT JOIN tuser tu ON tb.created_by = tu.id_user
                WHERE tb.id_backup = ? AND tb.created_by = ?
            ");
            $stmt->execute([$id_backup, $id_user]);
        } else {
            $stmt = $this->db->prepare("
                SELECT tb.*, tu.full_name as created_by_name
                FROM tbackup tb
                LEFT JOIN tuser tu ON tb.created_by = tu.id_user
                WHERE tb.id_backup = ?
            ");
            $stmt->execute([$id_backup]);
        }

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Hapus backup (dengan atau tanpa user)
    public function deleteBackup(int $id_backup, ?int $id_user = null): bool
    {
        if ($id_user !== null) {
            $stmt = $this->db->prepare("
                DELETE FROM tbackup 
                WHERE id_backup = ? AND created_by = ?
            ");
            return $stmt->execute([$id_backup, $id_user]);
        } else {
            $stmt = $this->db->prepare("
                DELETE FROM tbackup 
                WHERE id_backup = ?
            ");
            return $stmt->execute([$id_backup]);
        }
    }

    // Ambil semua backup
    public function getAllBackups(): array
    {
        $stmt = $this->db->query("
            SELECT 
                tb.*,
                tu.full_name as created_by_name
            FROM tbackup tb
            LEFT JOIN tuser tu ON tb.created_by = tu.id_user
            ORDER BY tb.backup_date DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update status backup
    public function updateBackupStatus(int $id_backup, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE tbackup 
            SET status = ?
            WHERE id_backup = ?
        ");

        return $stmt->execute([$status, $id_backup]);
    }

    // Ambil backup terakhir berdasarkan user
    public function getLastBackupByUser(int $id_user): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM tbackup
            WHERE created_by = ?
            ORDER BY backup_date DESC
            LIMIT 1
        ");
        $stmt->execute([$id_user]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Ambil statistik backup
    public function getBackupStats(): array
    {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_backups,
                SUM(file_size) as total_size,
                AVG(file_size) as avg_size,
                MIN(backup_date) as oldest_backup,
                MAX(backup_date) as latest_backup
            FROM tbackup
        ");

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: [
            'total_backups' => 0,
            'total_size' => 0,
            'avg_size' => 0,
            'oldest_backup' => null,
            'latest_backup' => null
        ];
    }

    // Ambil backup berdasarkan rentang tanggal
    public function getBackupsByDateRange(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare("
            SELECT tb.*, tu.full_name as created_by_name
            FROM tbackup tb
            LEFT JOIN tuser tu ON tb.created_by = tu.id_user
            WHERE tb.backup_date BETWEEN ? AND ?
            ORDER BY tb.backup_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Hitung jumlah backup per user
    public function getBackupCountPerUser(): array
    {
        $stmt = $this->db->query("
            SELECT 
                created_by,
                COUNT(*) as backup_count,
                SUM(file_size) as total_size
            FROM tbackup
            GROUP BY created_by
            ORDER BY backup_count DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
