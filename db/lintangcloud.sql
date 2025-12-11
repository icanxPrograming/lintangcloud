-- ============================================================
-- Database: lintangcloud
-- Struktur Akhir Lintang Cloud Storage (PHP 7 + MinIO Object Storage)
-- Versi FINAL (Admin hanya pengelola, bukan penyimpan)
-- ============================================================

CREATE DATABASE IF NOT EXISTS lintangcloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lintangcloud;

-- ============================================================
-- 1. Tabel User
-- ============================================================
CREATE TABLE tuser (
    id_user INT(11) AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    storage_used BIGINT DEFAULT 0,                     
    storage_limit BIGINT DEFAULT 2147483648,           
    minio_user_folder VARCHAR(100) DEFAULT NULL,       
    
    -- BANDWIDTH SETTINGS (NEW)
    upload_speed_limit INT DEFAULT 256,        -- KB/s (≈2 Mbps)
    download_speed_limit INT DEFAULT 512,      -- KB/s (≈4 Mbps)  
    daily_upload_limit INT DEFAULT 500,        -- MB per day
    daily_download_limit INT DEFAULT 250,      -- MB per day
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif'
);

-- ============================================================
-- 2. Tabel File
-- ============================================================
CREATE TABLE tfile (
    id_file INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_user INT(11) NOT NULL,
    parent_id INT(11) DEFAULT NULL,                    -- untuk struktur folder (jika ada)
    nama_file VARCHAR(255) NOT NULL,
    minio_object_key VARCHAR(500) DEFAULT NULL,            -- key object di MinIO, misal: user123/uploads/foto.jpg
    size BIGINT DEFAULT 0,
    jenis_file VARCHAR(20) DEFAULT NULL,                            -- ex: image/jpeg, application/pdf
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES tuser(id_user) ON DELETE CASCADE
);

-- ============================================================
-- 3. Tabel Trash
-- ============================================================
CREATE TABLE ttrash (
    id_trash INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_user INT(11) NOT NULL,
    original_id_file INT(11) NOT NULL,
    parent_id INT(11) DEFAULT NULL,
    nama_file VARCHAR(255) NOT NULL,
    minio_object_key VARCHAR(500) DEFAULT NULL,            -- key object di MinIO
    size BIGINT(20) DEFAULT 0,
    jenis_file VARCHAR(20) DEFAULT NULL,
    original_parent_id INT DEFAULT 0,
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES tuser(id_user) ON DELETE CASCADE
);

-- ============================================================
-- 4. Tabel Share
-- ============================================================
CREATE TABLE tshare (
    id_share INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_file INT(11) NOT NULL,
    share_token VARCHAR(64) NOT NULL UNIQUE,          -- token unik untuk akses file
    shared_by INT(11) NOT NULL,
    shared_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_public TINYINT(1) DEFAULT 0,                   -- 0: private, 1: public
    FOREIGN KEY (id_file) REFERENCES tfile(id_file) ON DELETE CASCADE,
    FOREIGN KEY (shared_by) REFERENCES tuser(id_user) ON DELETE CASCADE
);

-- ============================================================
-- 5. Tabel Notifikasi
-- ============================================================
CREATE TABLE tnotifikasi (
    id_notif INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_user INT(11) NOT NULL,
    judul VARCHAR(100) NOT NULL,
    pesan TEXT NOT NULL,
    status ENUM('unread','read') DEFAULT 'unread',
    dikirim_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES tuser(id_user) ON DELETE CASCADE
);

-- ============================================================
-- 6. Tabel Aktivitas
-- ============================================================
CREATE TABLE tactivity (
    id_activity INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_user INT(11),
    aktivitas VARCHAR(255),
    waktu DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES tuser(id_user) ON DELETE SET NULL
);

-- ============================================================
-- 7. Tabel MinIO Configuration
-- ============================================================
CREATE TABLE tminioconfig (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    bucket_name VARCHAR(100) NOT NULL,                 -- nama bucket utama
    endpoint_url VARCHAR(255) NOT NULL,                -- contoh: http://localhost:9000
    access_key VARCHAR(255) NOT NULL,                  -- dari MinIO
    secret_key VARCHAR(255) NOT NULL,                  -- dari MinIO
    is_active TINYINT(1) DEFAULT 1                     -- aktif/tidak
);

-- ============================================================
-- 8. Tabel Backup
-- ============================================================
CREATE TABLE tbackup (
    id_backup INT(11) AUTO_INCREMENT PRIMARY KEY,
    backup_name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT(20) DEFAULT 0,
    backup_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed', 'restoring') DEFAULT 'pending',
    created_by INT(11) NOT NULL,
    FOREIGN KEY (created_by) REFERENCES tuser(id_user) ON DELETE CASCADE
);