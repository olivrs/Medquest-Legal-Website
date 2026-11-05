-- ============================================================================
-- DATABASE: db_medquest_log
-- Sistem Informasi Log of Cooperation Agreement (MJG-FOR-LAC.12-001-03)
-- PT Medquest Jaya Global
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `db_medquest_log` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_medquest_log`;

-- 1. Tabel Users (Wajib Email Pribadi & Singkatan Nama Medquest seperti FJR)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_lengkap` VARCHAR(120) NOT NULL,
    `kode_singkatan` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Singkatan nama dari Medquest, contoh: FJR',
    `email` VARCHAR(150) NOT NULL UNIQUE COMMENT 'Email pribadi wajib untuk menerima notifikasi H-7, H-3, H-1',
    `password` VARCHAR(255) NOT NULL,
    `departemen_default` VARCHAR(30) DEFAULT 'ALL',
    `role` ENUM('superadmin', 'admin', 'user') DEFAULT 'user',
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Departemen / Divisi
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_dept` VARCHAR(30) NOT NULL UNIQUE,
    `nama_dept` VARCHAR(120) NOT NULL,
    `deskripsi` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Agreements (Log of Cooperation Agreement)
CREATE TABLE IF NOT EXISTS `agreements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_urut_dokumen` INT DEFAULT NULL COMMENT 'Nomor urut di pinggir form seperti 731, 732, dst',
    `department` VARCHAR(30) NOT NULL,
    `document_number` TEXT NOT NULL,
    `customer` VARCHAR(255) NOT NULL,
    `product_description` TEXT NOT NULL,
    `serial_number` VARCHAR(150) NOT NULL DEFAULT 'N/A',
    `effective_date` DATE DEFAULT NULL,
    `effective_date_na` TINYINT(1) NOT NULL DEFAULT 0,
    `expiry_date` DATE NOT NULL,
    `note` TEXT DEFAULT NULL,
    `created_by_user_id` INT NOT NULL,
    `created_by_code` VARCHAR(20) NOT NULL COMMENT 'Singkatan pembuat data utama, misal FJR',
    `created_by_log` TEXT NOT NULL COMMENT 'Riwayat tanda input misal: FJR, 21 OCT 2026',
    `notif_h7_sent_at` DATETIME DEFAULT NULL,
    `notif_h3_sent_at` DATETIME DEFAULT NULL,
    `notif_h1_sent_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_dept` (`department`),
    INDEX `idx_expiry` (`expiry_date`),
    INDEX `idx_creator` (`created_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Riwayat Update Note & Created By (Log History per baris)
CREATE TABLE IF NOT EXISTS `agreement_histories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `agreement_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `kode_singkatan` VARCHAR(20) NOT NULL,
    `tanggal_input` DATE NOT NULL,
    `catatan` TEXT DEFAULT NULL,
    `aksi` ENUM('CREATE', 'ADD_NOTE', 'EDIT') DEFAULT 'CREATE',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`agreement_id`) REFERENCES `agreements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Log Notifikasi Email (H-7, H-3, H-1)
CREATE TABLE IF NOT EXISTS `email_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `agreement_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `recipient_email` VARCHAR(150) NOT NULL,
    `recipient_code` VARCHAR(20) NOT NULL,
    `tipe_notif` ENUM('H-7', 'H-3', 'H-1', 'TEST') NOT NULL,
    `days_remaining` INT NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body_html` LONGTEXT NOT NULL,
    `status_kirim` VARCHAR(50) NOT NULL DEFAULT 'SENT',
    `keterangan_status` TEXT DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`agreement_id`) REFERENCES `agreements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel Pengaturan SMTP Email
CREATE TABLE IF NOT EXISTS `smtp_settings` (
    `id` INT PRIMARY KEY,
    `smtp_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `smtp_host` VARCHAR(150) NOT NULL DEFAULT 'smtp.gmail.com',
    `smtp_port` INT NOT NULL DEFAULT 587,
    `smtp_encryption` VARCHAR(20) NOT NULL DEFAULT 'tls',
    `smtp_username` VARCHAR(150) DEFAULT '',
    `smtp_password` VARCHAR(255) DEFAULT '',
    `sender_name` VARCHAR(150) NOT NULL DEFAULT 'Medquest Agreement Reminder',
    `sender_email` VARCHAR(150) NOT NULL DEFAULT 'noreply@medquest.co.id',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `smtp_settings` (`id`, `smtp_enabled`, `smtp_host`, `smtp_port`, `smtp_encryption`, `sender_name`, `sender_email`)
VALUES (1, 0, 'smtp.gmail.com', 587, 'tls', 'Medquest Agreement Alert (LAC)', 'noreply@medquest.co.id');

-- Seed Departemen sesuai foto
INSERT IGNORE INTO `departments` (`kode_dept`, `nama_dept`, `deskripsi`) VALUES
('MHO', 'Medical Healthcare & Oncology (MHO)', 'Divisi MHO'),
('MBB', 'Medical Blood Bank (MBB)', 'Divisi MBB - Palang Merah & UDD'),
('MPC', 'Medical Primary Care / Corporate (MPC)', 'Divisi MPC'),
('RA', 'Regulatory Affairs (RA)', 'Divisi Regulatory Affairs'),
('GA', 'General Affairs (GA)', 'Divisi General Affairs & Operasional'),
('LAC', 'Legal & Agreement Compliance (LAC)', 'Divisi Legal & Compliance'),
('ITD', 'Information Technology Department (ITD)', 'Divisi IT & Systems');

