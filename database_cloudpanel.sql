-- ============================================================================
-- MEDQUEST LEGAL DOCUMENT SYSTEM MONITORING (LARAVEL 13)
-- File SQL Siap Import via phpMyAdmin di CloudPanel
-- (Tanpa CREATE DATABASE / USE agar tidak error akses di CloudPanel)
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- --------------------------------------------------------
-- Struktur dari tabel `agreement_histories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `agreement_histories`;
CREATE TABLE `agreement_histories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agreement_id` int NOT NULL,
  `user_id` int NOT NULL,
  `kode_singkatan` varchar(20) NOT NULL,
  `tanggal_input` date NOT NULL,
  `catatan` text,
  `aksi` enum('CREATE','ADD_NOTE','EDIT') DEFAULT 'CREATE',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `agreement_id` (`agreement_id`),
  CONSTRAINT `agreement_histories_ibfk_1` FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data untuk tabel `agreement_histories`
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('1', '1', '2', 'NWL', '2026-11-04', 'N/A', 'CREATE', '2026-11-04 13:45:30');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('2', '2', '2', 'NWL', '2026-11-05', 'N/A', 'CREATE', '2026-11-05 09:34:23');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('3', '3', '2', 'NWL', '2026-11-05', 'N/A', 'CREATE', '2026-11-05 15:16:53');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('4', '4', '2', 'NWL', '2026-11-05', 'N/A', 'CREATE', '2026-11-05 16:31:17');

-- --------------------------------------------------------
-- Struktur dari tabel `agreements`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `agreements`;
CREATE TABLE `agreements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doc_type` varchar(30) NOT NULL DEFAULT 'COOPERATION',
  `no_urut_dokumen` int DEFAULT NULL COMMENT 'Nomor urut di pinggir form seperti 731, 732, dst',
  `department` varchar(30) NOT NULL,
  `document_title` text,
  `document_number` text NOT NULL,
  `issued_by` varchar(255) DEFAULT NULL,
  `principal_name` varchar(255) DEFAULT NULL,
  `customer` varchar(255) NOT NULL,
  `product_description` text NOT NULL,
  `serial_number` varchar(150) NOT NULL DEFAULT 'N/A',
  `effective_date` date DEFAULT NULL,
  `effective_date_na` tinyint(1) NOT NULL DEFAULT '0',
  `expiry_date` date DEFAULT NULL,
  `expiry_date_na` tinyint(1) NOT NULL DEFAULT '0',
  `note` text,
  `file_attachment` varchar(255) DEFAULT NULL,
  `created_by_user_id` int NOT NULL,
  `created_by_code` varchar(20) NOT NULL COMMENT 'Singkatan pembuat data utama, misal FJR',
  `created_by_log` text NOT NULL COMMENT 'Riwayat tanda input misal: FJR, 21 OCT 2026',
  `notif_h90_sent_at` datetime DEFAULT NULL,
  `notif_h60_sent_at` datetime DEFAULT NULL,
  `notif_h30_sent_at` datetime DEFAULT NULL,
  `notif_h14_sent_at` datetime DEFAULT NULL,
  `notif_h7_sent_at` datetime DEFAULT NULL,
  `notif_h3_sent_at` datetime DEFAULT NULL,
  `notif_h1_sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dept` (`department`),
  KEY `idx_expiry` (`expiry_date`),
  KEY `idx_creator` (`created_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data untuk tabel `agreements`
INSERT INTO `agreements` (`id`, `doc_type`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('1', 'COOPERATION', '731', 'ITD', NULL, '512312623424', NULL, NULL, 'PT Siloam International', 'Rapid HUV', 'N/A', '2026-11-04', '0', '2026-11-30', '0', 'N/A', 'doc_1_1793775945.pdf', '2', 'NWL', 'NWL, 04 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-04 13:45:30', '2026-11-04 14:05:45');
INSERT INTO `agreements` (`id`, `doc_type`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('2', 'COOPERATION', '732', 'LAC', NULL, '88737271831', NULL, NULL, 'St Carolus', 'Obat Demam', 'N/A', '2026-11-05', '0', '2026-11-08', '0', 'N/A', NULL, '2', 'NWL', 'NWL, 05 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 09:34:23', '2026-11-05 09:34:23');
INSERT INTO `agreements` (`id`, `doc_type`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('3', 'COOPERATION', '733', 'MHO', NULL, 'KKO/76271./3872913', NULL, NULL, 'Rumah Sakit Jatinegara', 'Scanner', 'N/A', '2026-11-05', '0', '2026-11-07', '0', 'N/A', NULL, '2', 'NWL', 'NWL, 05 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 15:16:53', '2026-11-05 15:16:53');
INSERT INTO `agreements` (`id`, `doc_type`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('4', 'PRINCIPAL', '1', 'MHO', 'Kontrak', 'N/A', 'Ryandha Wildan', 'Diagast', 'Diagast', 'Kontrak', 'N/A', '2026-11-05', '0', '2026-11-19', '0', 'N/A', NULL, '2', 'NWL', 'NWL, 05 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-05 16:31:17', '2026-11-05 16:31:17');

-- --------------------------------------------------------
-- Struktur dari tabel `cache`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur dari tabel `cache_locks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur dari tabel `departments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_dept` varchar(30) NOT NULL,
  `nama_dept` varchar(120) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_dept` (`kode_dept`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data untuk tabel `departments`
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('1', 'MHO', 'Medical Healthcare & Oncology (MHO)', 'Divisi MHO', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('2', 'MBB', 'Medical Blood Bank (MBB)', 'Divisi MBB - Palang Merah & UDD', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('3', 'MPC', 'Medical Primary Care / Corporate (MPC)', 'Divisi MPC', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('4', 'RA', 'Regulatory Affairs (RA)', 'Divisi Regulatory Affairs', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('5', 'GA', 'General Affairs (GA)', 'Divisi General Affairs & Operasional', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('6', 'LAC', 'Legal & Agreement Compliance (LAC)', 'Divisi Legal & Compliance', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('7', 'ITD', 'Information Technology Department (ITD)', 'Divisi IT & Systems', '2026-11-04 13:45:30');

-- --------------------------------------------------------
-- Struktur dari tabel `email_notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `email_notifications`;
CREATE TABLE `email_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agreement_id` int NOT NULL,
  `user_id` int NOT NULL,
  `recipient_email` varchar(150) NOT NULL,
  `recipient_code` varchar(20) NOT NULL,
  `tipe_notif` varchar(20) NOT NULL,
  `days_remaining` int NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` longtext NOT NULL,
  `status_kirim` varchar(50) NOT NULL DEFAULT 'SENT',
  `keterangan_status` text,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `agreement_id` (`agreement_id`),
  CONSTRAINT `email_notifications_ibfk_1` FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data untuk tabel `email_notifications`
INSERT INTO `email_notifications` (`id`, `agreement_id`, `user_id`, `recipient_email`, `recipient_code`, `tipe_notif`, `days_remaining`, `subject`, `body_html`, `status_kirim`, `keterangan_status`, `is_read`, `sent_at`) VALUES ('1', '2', '2', 'nawalfadhh@gmail.com', 'NWL', 'H-3', '3', '[MEDQUEST H-3] Pengingat Deadline (08 NOV 2026) - Dept LAC - St Carolus', '\r\n<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n<meta charset=\"UTF-8\">\r\n<title>Notifikasi Deadline H-3 - Medquest</title>\r\n</head>\r\n<body style=\"margin:0;padding:0;background-color:#f1f5f9;font-family:Open Sans,Helvetica,Arial,sans-serif;color:#282828;\">\r\n  <div style=\"max-width:680px;margin:24px auto;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;\">\r\n    <div style=\"background:linear-gradient(135deg,#101399 0%,#0059fb 65%,#2ea3f2 100%);padding:22px 28px;color:#ffffff;\">\r\n      <div style=\"font-size:12px;letter-spacing:1px;text-transform:uppercase;opacity:0.85;\">PT Medquest Jaya Global &bull; Doc No: MJG-FOR-LAC.12-001-03</div>\r\n      <h2 style=\"margin:6px 0 0;font-size:20px;font-weight:700;\">PENGINGAT DEADLINE KONTRAK: H-3</h2>\r\n    </div>\r\n\r\n    <div style=\"background:#dc2626;color:#ffffff;padding:12px 28px;font-weight:700;font-size:14px;\">\r\n      Peringatan Otomatis: Kontrak Memasuki Tahap 3 Hari Lagi (H-3) - SANGAT MENDESAK!\r\n    </div>\r\n\r\n    <div style=\"padding:26px 28px;\">\r\n      <p style=\"margin-top:0;font-size:15px;line-height:1.6;\">\r\n        Halo <strong>Muhammad Nawwal Fadhil (NWL)</strong>,\r\n      </p>\r\n      <p style=\"font-size:14px;line-height:1.6;color:#475569;\">\r\n        Dokumen kerja sama yang Anda input (<em>Created By: NWL</em>) saat ini telah mencapai batas waktu pengingat <strong>H-3</strong> menuju tanggal berakhir kontrak (<em>Expiry Date: 08 NOV 2026</em>).\r\n      </p>\r\n\r\n      <div style=\"font-weight:700;font-size:13px;margin:18px 0 6px;color:#334155;\">Jadwal Tanggal Deadline Kontrak Ini:</div>\r\n      <table style=\"width:100%;border-collapse:collapse;margin-bottom:18px;\">\r\n        <tr>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#f8fafc;border:1px solid #cbd5e1;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-90</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">10 AUG 2026</div>\r\n          </td>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#f8fafc;border:1px solid #cbd5e1;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-60</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">09 SEP 2026</div>\r\n          </td>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#f8fafc;border:1px solid #cbd5e1;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-30</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">09 OCT 2026</div>\r\n          </td>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#f8fafc;border:1px solid #cbd5e1;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-14</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">25 OCT 2026</div>\r\n          </td>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#f8fafc;border:1px solid #cbd5e1;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-7</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">01 NOV 2026</div>\r\n          </td>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#fef08a;border:2px solid #ca8a04;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-3</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">05 NOV 2026</div>\r\n          </td></tr>\r\n      </table>\r\n\r\n      <table style=\"width:100%;border-collapse:collapse;margin:16px 0;font-size:13.5px;border:1px solid #cbd5e1;\">\r\n        <tr style=\"background:#f8fafc;\">\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;width:34%;color:#334155;\">Department / Division</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#0059fb;\">LAC</td>\r\n        </tr>\r\n        <tr>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Document Number</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;\">88737271831</td>\r\n        </tr>\r\n        <tr style=\"background:#f8fafc;\">\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Customer</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:600;\">St Carolus</td>\r\n        </tr>\r\n        <tr>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Product (Description)</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;\">Obat Demam</td>\r\n        </tr>\r\n        <tr style=\"background:#f8fafc;\">\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Product (Serial Number)</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;\">N/A</td>\r\n        </tr>\r\n        <tr>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Effective Date</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;\">05 NOV 2026</td>\r\n        </tr>\r\n        <tr style=\"background:#fef2f2;\">\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#dc2626;\">Expiry Date</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:800;color:#dc2626;font-size:15px;\">08 NOV 2026 (H-3)</td>\r\n        </tr>\r\n        <tr>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Note Riwayat</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;\">N/A</td>\r\n        </tr>\r\n        <tr style=\"background:#f8fafc;\">\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Created By</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-family:monospace;font-weight:700;\">NWL, 05 NOV 2026</td>\r\n        </tr>\r\n      </table>\r\n\r\n      <div style=\"text-align:center;margin:26px 0 10px;\">\r\n        <a href=\"http://localhost:8000/?page=agreements&amp;dept=LAC\" style=\"background:#0059fb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:700;font-size:14px;display:inline-block;\">\r\n          Buka Departemen LAC di Website\r\n        </a>\r\n      </div>\r\n    </div>\r\n\r\n    <div style=\"background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 28px;font-size:12px;color:#64748b;text-align:center;\">\r\n      Email ini dikirim secara otomatis ke <strong>nawalfadhh@gmail.com</strong> karena Anda tercatat sebagai penginput data (<strong>NWL</strong>).\r\n    </div>\r\n  </div>\r\n</body>\r\n</html>', 'MENUNGGU KLIK ACTIVATE DI GMAIL', 'Cek Inbox/Spam nawalfadhh@gmail.com lalu klik tombol \"Activate Form\" (1 kali saja) agar email otomatis masuk.', '0', '2026-11-05 09:34:32');
INSERT INTO `email_notifications` (`id`, `agreement_id`, `user_id`, `recipient_email`, `recipient_code`, `tipe_notif`, `days_remaining`, `subject`, `body_html`, `status_kirim`, `keterangan_status`, `is_read`, `sent_at`) VALUES ('2', '4', '2', 'nawalfadhh@gmail.com', 'NWL', 'H-14', '14', '[MEDQUEST H-14] Pengingat Deadline (19 NOV 2026) - Dept MHO - Diagast', '\r\n<!DOCTYPE html>\r\n<html lang=\"id\">\r\n<head>\r\n<meta charset=\"UTF-8\">\r\n<title>Notifikasi Deadline H-14 - Medquest</title>\r\n</head>\r\n<body style=\"margin:0;padding:0;background-color:#f1f5f9;font-family:Open Sans,Helvetica,Arial,sans-serif;color:#282828;\">\r\n  <div style=\"max-width:680px;margin:24px auto;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;\">\r\n    <div style=\"background:linear-gradient(135deg,#101399 0%,#0059fb 65%,#2ea3f2 100%);padding:22px 28px;color:#ffffff;\">\r\n      <div style=\"font-size:12px;letter-spacing:1px;text-transform:uppercase;opacity:0.85;\">PT Medquest Jaya Global &bull; Doc No: MJG-FOR-LAC.12-001-03</div>\r\n      <h2 style=\"margin:6px 0 0;font-size:20px;font-weight:700;\">PENGINGAT DEADLINE KONTRAK: H-14</h2>\r\n    </div>\r\n\r\n    <div style=\"background:#d97706;color:#ffffff;padding:12px 28px;font-weight:700;font-size:14px;\">\r\n      Peringatan Otomatis: Kontrak Memasuki Tahap 14 Hari Lagi (H-14) - Persiapan Akhir Perpanjangan\r\n    </div>\r\n\r\n    <div style=\"padding:26px 28px;\">\r\n      <p style=\"margin-top:0;font-size:15px;line-height:1.6;\">\r\n        Halo <strong>Muhammad Nawwal Fadhil (NWL)</strong>,\r\n      </p>\r\n      <p style=\"font-size:14px;line-height:1.6;color:#475569;\">\r\n        Dokumen kerja sama yang Anda input (<em>Created By: NWL</em>) saat ini telah mencapai batas waktu pengingat <strong>H-14</strong> menuju tanggal berakhir kontrak (<em>Expiry Date: 19 NOV 2026</em>).\r\n      </p>\r\n\r\n      <div style=\"font-weight:700;font-size:13px;margin:18px 0 6px;color:#334155;\">Jadwal Tanggal Deadline Kontrak Ini:</div>\r\n      <table style=\"width:100%;border-collapse:collapse;margin-bottom:18px;\">\r\n        <tr>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#f8fafc;border:1px solid #cbd5e1;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-90</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">21 AUG 2026</div>\r\n          </td>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#f8fafc;border:1px solid #cbd5e1;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-60</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">20 SEP 2026</div>\r\n          </td>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#f8fafc;border:1px solid #cbd5e1;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-30</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">20 OCT 2026</div>\r\n          </td>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#fef08a;border:2px solid #ca8a04;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-14</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">05 NOV 2026</div>\r\n          </td>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#f8fafc;border:1px solid #cbd5e1;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-7</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">12 NOV 2026</div>\r\n          </td>\r\n          <td style=\"padding:8px 6px;text-align:center;background:#f8fafc;border:1px solid #cbd5e1;\">\r\n            <div style=\"font-weight:800;font-size:12px;color:#0059fb;\">H-3</div>\r\n            <div style=\"font-size:11px;color:#334155;margin-top:2px;\">16 NOV 2026</div>\r\n          </td></tr>\r\n      </table>\r\n\r\n      <table style=\"width:100%;border-collapse:collapse;margin:16px 0;font-size:13.5px;border:1px solid #cbd5e1;\">\r\n        <tr style=\"background:#f8fafc;\">\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;width:34%;color:#334155;\">Department / Division</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#0059fb;\">MHO</td>\r\n        </tr>\r\n        <tr>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Document Number</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;\">N/A</td>\r\n        </tr>\r\n        <tr style=\"background:#f8fafc;\">\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Customer</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:600;\">Diagast</td>\r\n        </tr>\r\n        <tr>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Product (Description)</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;\">Kontrak</td>\r\n        </tr>\r\n        <tr style=\"background:#f8fafc;\">\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Product (Serial Number)</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;\">N/A</td>\r\n        </tr>\r\n        <tr>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Effective Date</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;\">05 NOV 2026</td>\r\n        </tr>\r\n        <tr style=\"background:#fef2f2;\">\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#dc2626;\">Expiry Date</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:800;color:#dc2626;font-size:15px;\">19 NOV 2026 (H-14)</td>\r\n        </tr>\r\n        <tr>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Note Riwayat</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;\">N/A</td>\r\n        </tr>\r\n        <tr style=\"background:#f8fafc;\">\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;\">Created By</td>\r\n          <td style=\"padding:10px 14px;border:1px solid #cbd5e1;font-family:monospace;font-weight:700;\">NWL, 05 NOV 2026</td>\r\n        </tr>\r\n      </table>\r\n\r\n      <div style=\"text-align:center;margin:26px 0 10px;\">\r\n        <a href=\"http://localhost:8000/?page=agreements&amp;dept=MHO\" style=\"background:#0059fb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:700;font-size:14px;display:inline-block;\">\r\n          Buka Departemen MHO di Website\r\n        </a>\r\n      </div>\r\n    </div>\r\n\r\n    <div style=\"background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 28px;font-size:12px;color:#64748b;text-align:center;\">\r\n      Email ini dikirim secara otomatis ke <strong>nawalfadhh@gmail.com</strong> karena Anda tercatat sebagai penginput data (<strong>NWL</strong>).\r\n    </div>\r\n  </div>\r\n</body>\r\n</html>', 'MENUNGGU KLIK ACTIVATE DI GMAIL', 'Cek Inbox/Spam nawalfadhh@gmail.com lalu klik tombol \"Activate Form\" (1 kali saja) agar email otomatis masuk.', '0', '2026-11-05 16:31:33');

-- --------------------------------------------------------
-- Struktur dari tabel `failed_jobs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur dari tabel `job_batches`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur dari tabel `jobs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur dari tabel `migrations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data untuk tabel `migrations`
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('1', '0001_01_01_000000_create_users_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('2', '0001_01_01_000001_create_cache_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('3', '0001_01_01_000002_create_jobs_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('4', '2026_11_09_000001_create_medquest_tables', '1');

-- --------------------------------------------------------
-- Struktur dari tabel `password_reset_tokens`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur dari tabel `sessions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur dari tabel `smtp_settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `smtp_settings`;
CREATE TABLE `smtp_settings` (
  `id` int NOT NULL,
  `smtp_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `smtp_host` varchar(150) NOT NULL DEFAULT 'smtp.gmail.com',
  `smtp_port` int NOT NULL DEFAULT '587',
  `smtp_encryption` varchar(20) NOT NULL DEFAULT 'tls',
  `smtp_username` varchar(150) DEFAULT '',
  `smtp_password` varchar(255) DEFAULT '',
  `sender_name` varchar(150) NOT NULL DEFAULT 'Medquest Agreement Reminder',
  `sender_email` varchar(150) NOT NULL DEFAULT 'noreply@medquest.co.id',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data untuk tabel `smtp_settings`
INSERT INTO `smtp_settings` (`id`, `smtp_enabled`, `smtp_host`, `smtp_port`, `smtp_encryption`, `smtp_username`, `smtp_password`, `sender_name`, `sender_email`, `updated_at`) VALUES ('1', '0', 'smtp.gmail.com', '587', 'tls', '', '', 'Medquest Agreement Alert (LAC)', 'noreply@medquest.co.id', '2026-11-04 09:46:50');

-- --------------------------------------------------------
-- Struktur dari tabel `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(120) NOT NULL,
  `kode_singkatan` varchar(20) NOT NULL COMMENT 'Singkatan nama dari Medquest, contoh: FJR',
  `email` varchar(150) NOT NULL COMMENT 'Email pribadi wajib untuk menerima notifikasi H-7, H-3, H-1',
  `password` varchar(255) NOT NULL,
  `departemen_default` varchar(30) DEFAULT 'ALL',
  `role` enum('superadmin','admin','user') NOT NULL DEFAULT 'user',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `remember_token` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_singkatan` (`kode_singkatan`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data untuk tabel `users`
INSERT INTO `users` (`id`, `nama_lengkap`, `kode_singkatan`, `email`, `password`, `departemen_default`, `role`, `status`, `created_at`, `remember_token`, `updated_at`) VALUES ('1', 'Fajar (Admin LAC Medquest)', 'FJR', 'fjr.medquest@gmail.com', '$2y$12$rQFR5gyZG8Avsw5wPbErt.v0Ih6QBBDVngaJG59Xrbpq7eRtrIgKe', 'ALL', 'superadmin', 'approved', '2026-11-04 09:46:50', NULL, '2026-11-09 11:12:29');
INSERT INTO `users` (`id`, `nama_lengkap`, `kode_singkatan`, `email`, `password`, `departemen_default`, `role`, `status`, `created_at`, `remember_token`, `updated_at`) VALUES ('2', 'Muhammad Nawwal Fadhil', 'NWL', 'nawalfadhh@gmail.com', '$2y$10$hWX/js9bOMf3AN5AUO7PX.Hj4HndUrsYwWStlUJ8ire72ePFUsWY.', 'ALL', 'superadmin', 'approved', '2026-11-04 13:25:01', NULL, NULL);
INSERT INTO `users` (`id`, `nama_lengkap`, `kode_singkatan`, `email`, `password`, `departemen_default`, `role`, `status`, `created_at`, `remember_token`, `updated_at`) VALUES ('5', 'Nawal Fadhil', 'NAW', 'anzaikuid@gmail.com', '$2y$10$fdFZVvndlK98FciT4gxydu6KZ3lbly0v81Y7W5BTXGgmJyHo.Fx.e', 'GA', 'user', 'approved', '2026-11-05 11:12:11', NULL, NULL);

SET FOREIGN_KEY_CHECKS = 1;
