SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

DROP TABLE IF EXISTS `agreement_histories`;
CREATE TABLE `agreement_histories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agreement_id` int NOT NULL,
  `user_id` int NOT NULL,
  `kode_singkatan` varchar(20) NOT NULL,
  `tanggal_input` date NOT NULL,
  `catatan` text,
  `aksi` varchar(50) NOT NULL DEFAULT 'CREATE',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `agreement_id` (`agreement_id`),
  CONSTRAINT `agreement_histories_ibfk_1` FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('1', '1', '2', 'NWL', '2026-11-23', 'Input contoh dokumen COOPERATION (Expired / Merah)', 'CREATE', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('2', '2', '2', 'NWL', '2026-11-23', 'Input contoh dokumen COOPERATION (Mepet Deadline / Kuning)', 'CREATE', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('3', '3', '2', 'NWL', '2026-11-23', 'Input contoh dokumen COOPERATION (Completed ACC / Hijau)', 'CREATE', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('4', '3', '2', 'NWL', '2026-11-20', 'Dokumen telah disetujui dan ditandatangani (ACC).', 'STATUS_SELESAI', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('5', '4', '2', 'NWL', '2026-11-23', 'Input contoh dokumen COOPERATION (Aktif Normal / Putih)', 'CREATE', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('6', '5', '2', 'NWL', '2026-11-23', 'Input contoh dokumen COMPANY (Expired / Merah)', 'CREATE', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('7', '6', '2', 'NWL', '2026-11-23', 'Input contoh dokumen COMPANY (Mepet Deadline / Kuning)', 'CREATE', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('8', '7', '2', 'NWL', '2026-11-23', 'Input contoh dokumen COMPANY (Completed ACC / Hijau)', 'CREATE', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('9', '7', '2', 'NWL', '2026-11-13', 'Dokumen akta disetujui & selesai diarsipkan (ACC).', 'STATUS_SELESAI', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('10', '8', '2', 'NWL', '2026-11-23', 'Input contoh dokumen COMPANY (Permanen N/A / Putih)', 'CREATE', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('11', '9', '2', 'NWL', '2026-11-23', 'Input contoh dokumen PRINCIPAL (Expired / Merah)', 'CREATE', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('12', '10', '2', 'NWL', '2026-11-23', 'Input contoh dokumen PRINCIPAL (Mepet Deadline / Kuning)', 'CREATE', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('13', '11', '2', 'NWL', '2026-11-23', 'Input contoh dokumen PRINCIPAL (Completed ACC / Hijau)', 'CREATE', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('14', '11', '2', 'NWL', '2026-11-18', 'Perjanjian prinsipal telah di-ACC Direksi.', 'STATUS_SELESAI', '2026-11-23 15:55:34');
INSERT INTO `agreement_histories` (`id`, `agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES ('15', '12', '2', 'NWL', '2026-11-23', 'Input contoh dokumen PRINCIPAL (Aktif Normal / Putih)', 'CREATE', '2026-11-23 15:55:34');

DROP TABLE IF EXISTS `agreements`;
CREATE TABLE `agreements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doc_type` varchar(30) NOT NULL DEFAULT 'COOPERATION',
  `status_proses` varchar(30) NOT NULL DEFAULT 'PENGAJUAN',
  `tanggal_selesai` date DEFAULT NULL,
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
  `notif_h0_sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dept` (`department`),
  KEY `idx_expiry` (`expiry_date`),
  KEY `idx_creator` (`created_by_user_id`),
  KEY `agreements_status_proses_index` (`status_proses`),
  KEY `agreements_tanggal_selesai_index` (`tanggal_selesai`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('1', 'COOPERATION', 'PENGAJUAN', NULL, '731', 'MHO', NULL, '015/PKS/RSCM-MJG/VII/2025', NULL, NULL, 'RSUPN Dr. Cipto Mangunkusumo', 'Analyzer Kimia Klinik Cobas c311', 'SN-MHO-31109', '2025-11-11', '0', '2026-11-11', '0', 'Menunggu konfirmasi addendum perpanjangan kontrak dengan pihak RSCM.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');
INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('2', 'COOPERATION', 'BERJALAN', NULL, '732', 'MBB', NULL, '041/SP-KSO/UDD-PMI/I/2026', NULL, NULL, 'UDD PMI Kota Surabaya', 'Alat NAT & Chemiluminescence Screening Darah', 'SN-MBB-99201', '2025-12-11', '0', '2026-12-11', '0', 'Draft perpanjangan KSO tahun 2027 sudah dikirim ke tim pengadaan UDD PMI.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');
INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('3', 'COOPERATION', 'SELESAI', '2026-11-20', '733', 'MPC', NULL, '088/PKS/SHTB-MJG/III/2026', NULL, NULL, 'PT Siloam International Hospitals Tbk', 'Point-of-Care Blood Gas & Electrolyte System', 'SN-MPC-44120', '2026-01-22', '0', '2027-01-22', '0', 'Perjanjian telah ditandatangani kedua belah pihak & diarsipkan di LAC.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');
INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('4', 'COOPERATION', 'BERJALAN', NULL, '734', 'MHO', NULL, '102/PKS/RSUD-BDG/VIII/2026', NULL, NULL, 'RSUD Kota Bandung', 'Reagen Onkologi & Diagnostik Molekuler', 'N/A', '2026-07-31', '0', '2027-07-31', '0', 'Kontrak aktif tahun berjalan, jadwal evaluasi berkala semester II.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');
INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('5', 'COMPANY', 'PENGAJUAN', NULL, '185', 'LAC', 'Sertifikat Cara Distribusi Alat Kesehatan yang Baik (CDAKB)', 'FK.01.05/VI/0419-e/2024', 'Kementerian Kesehatan Republik Indonesia', NULL, 'PT Medquest Jaya Global', 'Sertifikat Cara Distribusi Alat Kesehatan yang Baik (CDAKB)', 'N/A', '2024-11-15', '0', '2026-11-15', '0', 'Pengajuan perpanjangan sertifikat CDAKB sedang diproses di OSS-RBA.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');
INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('6', 'COMPANY', 'BERJALAN', NULL, '186', 'GA', 'Izin Operasional & Surat Keterangan Domisili Kantor Salemba', '412/SKDP-PTSP/XII/2024', 'DPMPTSP Provinsi DKI Jakarta', NULL, 'PT Medquest Jaya Global', 'Izin Operasional & Surat Keterangan Domisili Kantor Salemba', 'N/A', '2024-12-21', '0', '2026-12-21', '0', 'Verifikasi berkas perpanjangan domisili kantor Menara Salemba Lt. 6.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');
INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('7', 'COMPANY', 'SELESAI', '2026-11-13', '187', 'LAC', 'Akta Pengangkatan Kembali Dewan Direksi & Dewan Komisaris', '01/AKTA-NOT/2025', 'Notaris Putranto, S.H., M.Kn.', NULL, 'PT Medquest Jaya Global', 'Akta Pengangkatan Kembali Dewan Direksi & Dewan Komisaris', 'N/A', '2025-10-19', '0', '2031-11-22', '0', 'Salinan akta asli & pengesahan Kemenkumham tersimpan di brankas LAC.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');
INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('8', 'COMPANY', 'PENGAJUAN', NULL, '188', 'LAC', 'SK Kemenkumham RI Pelaporan & Persetujuan Anggaran Dasar', 'AHU-AH.01.03-0117878', 'Kementerian Hukum dan HAM RI', NULL, 'PT Medquest Jaya Global', 'SK Kemenkumham RI Pelaporan & Persetujuan Anggaran Dasar', 'N/A', '2025-10-24', '0', NULL, '1', 'Berlaku tetap (permanen) selama tidak ada perubahan anggaran dasar.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');
INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('9', 'PRINCIPAL', 'PENGAJUAN', NULL, '1', 'RA', 'Letter of Authorization (LoA) Produk Blood Grouping', 'LOA-DIAGAST-ID-2024', 'Nanik Gunawan - Cedric Debonte', 'Diagast SA France', 'Diagast SA France', 'Letter of Authorization (LoA) Produk Blood Grouping', 'N/A', '2024-11-08', '0', '2026-11-08', '0', 'Menunggu pengiriman LoA pembaruan bertanda tangan apostille dari Prancis.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');
INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('10', 'PRINCIPAL', 'BERJALAN', NULL, '2', 'RA', 'Certificate of Distribution GeneXpert Systems', 'CERT-CEPHEID-ID-2025', 'Nanik Gunawan - Nick Vavias', 'Cepheid Europe SAS', 'Cepheid Europe SAS', 'Certificate of Distribution GeneXpert Systems', 'N/A', '2026-01-04', '0', '2027-01-04', '0', 'Dalam review perpanjangan wilayah distribusi Indonesia 2027.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');
INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('11', 'PRINCIPAL', 'SELESAI', '2026-11-18', '3', 'LAC', 'Exclusive Distribution Agreement Diagnostic Reagents', 'DIST-GRIFOLS-2026-03', 'Maria Santos - VP Asia Pacific', 'Grifols Diagnostic Solutions Inc.', 'Grifols Diagnostic Solutions Inc.', 'Exclusive Distribution Agreement Diagnostic Reagents', 'N/A', '2026-06-01', '0', '2027-11-23', '0', 'Perjanjian prinsipal telah di-ACC Direksi dan terdaftar di Kemenkes.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');
INSERT INTO `agreements` (`id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`, `department`, `document_title`, `document_number`, `issued_by`, `principal_name`, `customer`, `product_description`, `serial_number`, `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`, `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`, `notif_h90_sent_at`, `notif_h60_sent_at`, `notif_h30_sent_at`, `notif_h14_sent_at`, `notif_h7_sent_at`, `notif_h3_sent_at`, `notif_h1_sent_at`, `created_at`, `updated_at`) VALUES ('12', 'PRINCIPAL', 'PENGAJUAN', NULL, '4', 'RA', 'Quality Agreement Medical Laboratory Automation', 'QA-TECAN-2026-08', 'Nanik Gunawan - Rudolf Eugster', 'Tecan Trading AG', 'Tecan Trading AG', 'Quality Agreement Medical Laboratory Automation', 'N/A', '2026-09-19', '0', '2027-09-19', '0', 'Dokumen kualitas terdaftar, review berkala dijadwalkan kuartal III.', NULL, '2', 'NWL', 'NWL, 23 NOV 2026', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-23 15:55:34', '2026-11-23 15:55:34');

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_dept` varchar(30) NOT NULL,
  `nama_dept` varchar(120) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_dept` (`kode_dept`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('1', 'MHO', 'Medical Healthcare & Oncology (MHO)', 'Divisi MHO', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('2', 'MBB', 'Medical Blood Bank (MBB)', 'Divisi MBB - Palang Merah & UDD', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('3', 'MPC', 'Medical Primary Care / Corporate (MPC)', 'Divisi MPC', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('4', 'RA', 'Regulatory Affairs (RA)', 'Divisi Regulatory Affairs', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('5', 'GA', 'General Affairs (GA)', 'Divisi General Affairs & Operasional', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('6', 'LAC', 'Legal & Agreement Compliance (LAC)', 'Divisi Legal & Compliance', '2026-11-04 09:46:50');
INSERT INTO `departments` (`id`, `kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES ('7', 'ITD', 'Information Technology Department (ITD)', 'Divisi IT & Systems', '2026-11-04 13:45:30');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('1', '0001_01_01_000000_create_users_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('2', '0001_01_01_000001_create_cache_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('3', '0001_01_01_000002_create_jobs_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('4', '2026_11_09_000001_create_medquest_tables', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('5', '2026_11_12_000001_add_process_status_to_agreements_table', '2');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('6', '2026_11_16_000001_alter_aksi_in_agreement_histories_table', '2');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('7', '2026_11_24_000001_add_h0_and_notification_recipients', '3');

DROP TABLE IF EXISTS `notification_recipients`;
CREATE TABLE `notification_recipients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ALL',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `added_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_recipients_email_department_unique` (`email`,`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT INTO `smtp_settings` (`id`, `smtp_enabled`, `smtp_host`, `smtp_port`, `smtp_encryption`, `smtp_username`, `smtp_password`, `sender_name`, `sender_email`, `updated_at`) VALUES ('1', '0', 'smtp.gmail.com', '587', 'tls', '', '', 'Medquest Agreement Alert (LAC)', 'noreply@medquest.co.id', '2026-11-04 09:46:50');

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
  `allowed_departments` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_singkatan` (`kode_singkatan`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` (`id`, `nama_lengkap`, `kode_singkatan`, `email`, `password`, `departemen_default`, `role`, `status`, `created_at`, `remember_token`, `updated_at`, `allowed_departments`) VALUES ('1', 'Fajar (Admin LAC Medquest)', 'FJR', 'fjr.medquest@gmail.com', '$2y$12$rQFR5gyZG8Avsw5wPbErt.v0Ih6QBBDVngaJG59Xrbpq7eRtrIgKe', 'ALL', 'superadmin', 'approved', '2026-11-04 09:46:50', NULL, '2026-11-09 11:12:29', NULL);
INSERT INTO `users` (`id`, `nama_lengkap`, `kode_singkatan`, `email`, `password`, `departemen_default`, `role`, `status`, `created_at`, `remember_token`, `updated_at`, `allowed_departments`) VALUES ('2', 'Muhammad Nawwal Fadhil', 'NWL', 'nawalfadhh@gmail.com', '$2y$10$hWX/js9bOMf3AN5AUO7PX.Hj4HndUrsYwWStlUJ8ire72ePFUsWY.', 'ALL', 'superadmin', 'approved', '2026-11-04 13:25:01', NULL, NULL, NULL);
INSERT INTO `users` (`id`, `nama_lengkap`, `kode_singkatan`, `email`, `password`, `departemen_default`, `role`, `status`, `created_at`, `remember_token`, `updated_at`, `allowed_departments`) VALUES ('5', 'Nawal Fadhil', 'NAW', 'anzaikuid@gmail.com', '$2y$10$fdFZVvndlK98FciT4gxydu6KZ3lbly0v81Y7W5BTXGgmJyHo.Fx.e', 'GA', 'user', 'approved', '2026-11-05 11:12:11', NULL, NULL, NULL);

SET FOREIGN_KEY_CHECKS = 1;
