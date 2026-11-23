SET time_zone = "+07:00";

INSERT IGNORE INTO `departments` (`kode_dept`, `nama_dept`, `deskripsi`, `created_at`) VALUES
('MHO', 'Medical Healthcare & Oncology (MHO)', 'Divisi MHO', NOW()),
('MBB', 'Medical Blood Bank (MBB)', 'Divisi MBB - Palang Merah & UDD', NOW()),
('MPC', 'Medical Primary Care / Corporate (MPC)', 'Divisi MPC', NOW()),
('RA',  'Regulatory Affairs (RA)', 'Divisi Regulatory Affairs', NOW()),
('GA',  'General Affairs (GA)', 'Divisi General Affairs & Operasional', NOW()),
('LAC', 'Legal & Agreement Compliance (LAC)', 'Divisi Legal & Compliance', NOW()),
('ITD', 'Information Technology Department (ITD)', 'Divisi IT & Systems', NOW());

DELETE FROM `email_notifications`;
DELETE FROM `agreement_histories`;
DELETE FROM `agreements`;

ALTER TABLE `email_notifications` AUTO_INCREMENT = 1;
ALTER TABLE `agreement_histories` AUTO_INCREMENT = 1;
ALTER TABLE `agreements` AUTO_INCREMENT = 1;

SET @uid = COALESCE(
  (SELECT `id` FROM `users` ORDER BY CASE WHEN `kode_singkatan` = 'NWL' THEN 1 WHEN `kode_singkatan` = 'FJR' THEN 2 ELSE 3 END, `id` ASC LIMIT 1),
  1
);
SET @ucode = COALESCE(
  (SELECT `kode_singkatan` FROM `users` WHERE `id` = @uid LIMIT 1),
  'NWL'
);
SET @stamp = CONCAT(@ucode, ', ', UPPER(DATE_FORMAT(CURDATE(), '%d %b %Y')));

INSERT INTO `agreements` (
  `id`, `doc_type`, `status_proses`, `tanggal_selesai`, `no_urut_dokumen`,
  `department`, `document_title`, `document_number`, `issued_by`, `principal_name`,
  `customer`, `product_description`, `serial_number`,
  `effective_date`, `effective_date_na`, `expiry_date`, `expiry_date_na`,
  `note`, `file_attachment`, `created_by_user_id`, `created_by_code`, `created_by_log`,
  `created_at`, `updated_at`
) VALUES
(1, 'COOPERATION', 'PENGAJUAN', NULL, 731,
 'MHO', NULL, '015/PKS/RSCM-MJG/VII/2025', NULL, NULL,
 'RSUPN Dr. Cipto Mangunkusumo', 'Analyzer Kimia Klinik Cobas c311', 'SN-MHO-31109',
 DATE_SUB(CURDATE(), INTERVAL 377 DAY), 0, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 0,
 'Menunggu konfirmasi addendum perpanjangan kontrak dengan pihak RSCM.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW()),

(2, 'COOPERATION', 'BERJALAN', NULL, 732,
 'MBB', NULL, '041/SP-KSO/UDD-PMI/I/2026', NULL, NULL,
 'UDD PMI Kota Surabaya', 'Alat NAT & Chemiluminescence Screening Darah', 'SN-MBB-99201',
 DATE_SUB(CURDATE(), INTERVAL 347 DAY), 0, DATE_ADD(CURDATE(), INTERVAL 18 DAY), 0,
 'Draft perpanjangan KSO tahun 2027 sudah dikirim ke tim pengadaan UDD PMI.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW()),

(3, 'COOPERATION', 'SELESAI', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 733,
 'MPC', NULL, '088/PKS/SHTB-MJG/III/2026', NULL, NULL,
 'PT Siloam International Hospitals Tbk', 'Point-of-Care Blood Gas & Electrolyte System', 'SN-MPC-44120',
 DATE_SUB(CURDATE(), INTERVAL 305 DAY), 0, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 0,
 'Perjanjian telah ditandatangani kedua belah pihak & diarsipkan di LAC.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW()),

(4, 'COOPERATION', 'BERJALAN', NULL, 734,
 'MHO', NULL, '102/PKS/RSUD-BDG/VIII/2026', NULL, NULL,
 'RSUD Kota Bandung', 'Reagen Onkologi & Diagnostik Molekuler', 'N/A',
 DATE_SUB(CURDATE(), INTERVAL 115 DAY), 0, DATE_ADD(CURDATE(), INTERVAL 250 DAY), 0,
 'Kontrak aktif tahun berjalan, jadwal evaluasi berkala semester II.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW()),

(5, 'COMPANY', 'PENGAJUAN', NULL, 185,
 'LAC', 'Sertifikat Cara Distribusi Alat Kesehatan yang Baik (CDAKB)', 'FK.01.05/VI/0419-e/2024',
 'Kementerian Kesehatan Republik Indonesia', NULL,
 'PT Medquest Jaya Global', 'Sertifikat Cara Distribusi Alat Kesehatan yang Baik (CDAKB)', 'N/A',
 DATE_SUB(CURDATE(), INTERVAL 738 DAY), 0, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 0,
 'Pengajuan perpanjangan sertifikat CDAKB sedang diproses di OSS-RBA.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW()),

(6, 'COMPANY', 'BERJALAN', NULL, 186,
 'GA', 'Izin Operasional & Surat Keterangan Domisili Kantor Salemba', '412/SKDP-PTSP/XII/2024',
 'DPMPTSP Provinsi DKI Jakarta', NULL,
 'PT Medquest Jaya Global', 'Izin Operasional & Surat Keterangan Domisili Kantor Salemba', 'N/A',
 DATE_SUB(CURDATE(), INTERVAL 702 DAY), 0, DATE_ADD(CURDATE(), INTERVAL 28 DAY), 0,
 'Verifikasi berkas perpanjangan domisili kantor Menara Salemba Lt. 6.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW()),

(7, 'COMPANY', 'SELESAI', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 187,
 'LAC', 'Akta Pengangkatan Kembali Dewan Direksi & Dewan Komisaris', '01/AKTA-NOT/2025',
 'Notaris Putranto, S.H., M.Kn.', NULL,
 'PT Medquest Jaya Global', 'Akta Pengangkatan Kembali Dewan Direksi & Dewan Komisaris', 'N/A',
 DATE_SUB(CURDATE(), INTERVAL 400 DAY), 0, DATE_ADD(CURDATE(), INTERVAL 1825 DAY), 0,
 'Salinan akta asli & pengesahan Kemenkumham tersimpan di brankas LAC.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW()),

(8, 'COMPANY', 'PENGAJUAN', NULL, 188,
 'LAC', 'SK Kemenkumham RI Pelaporan & Persetujuan Anggaran Dasar', 'AHU-AH.01.03-0117878',
 'Kementerian Hukum dan HAM RI', NULL,
 'PT Medquest Jaya Global', 'SK Kemenkumham RI Pelaporan & Persetujuan Anggaran Dasar', 'N/A',
 DATE_SUB(CURDATE(), INTERVAL 395 DAY), 0, NULL, 1,
 'Berlaku tetap (permanen) selama tidak ada perubahan anggaran dasar.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW()),

(9, 'PRINCIPAL', 'PENGAJUAN', NULL, 1,
 'RA', 'Letter of Authorization (LoA) Produk Blood Grouping', 'LOA-DIAGAST-ID-2024',
 'Nanik Gunawan - Cedric Debonte', 'Diagast SA France',
 'Diagast SA France', 'Letter of Authorization (LoA) Produk Blood Grouping', 'N/A',
 DATE_SUB(CURDATE(), INTERVAL 745 DAY), 0, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 0,
 'Menunggu pengiriman LoA pembaruan bertanda tangan apostille dari Prancis.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW()),

(10, 'PRINCIPAL', 'BERJALAN', NULL, 2,
 'RA', 'Certificate of Distribution GeneXpert Systems', 'CERT-CEPHEID-ID-2025',
 'Nanik Gunawan - Nick Vavias', 'Cepheid Europe SAS',
 'Cepheid Europe SAS', 'Certificate of Distribution GeneXpert Systems', 'N/A',
 DATE_SUB(CURDATE(), INTERVAL 323 DAY), 0, DATE_ADD(CURDATE(), INTERVAL 42 DAY), 0,
 'Dalam review perpanjangan wilayah distribusi Indonesia 2027.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW()),

(11, 'PRINCIPAL', 'SELESAI', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 3,
 'LAC', 'Exclusive Distribution Agreement Diagnostic Reagents', 'DIST-GRIFOLS-2026-03',
 'Maria Santos - VP Asia Pacific', 'Grifols Diagnostic Solutions Inc.',
 'Grifols Diagnostic Solutions Inc.', 'Exclusive Distribution Agreement Diagnostic Reagents', 'N/A',
 DATE_SUB(CURDATE(), INTERVAL 175 DAY), 0, DATE_ADD(CURDATE(), INTERVAL 365 DAY), 0,
 'Perjanjian prinsipal telah di-ACC Direksi dan terdaftar di Kemenkes.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW()),

(12, 'PRINCIPAL', 'PENGAJUAN', NULL, 4,
 'RA', 'Quality Agreement Medical Laboratory Automation', 'QA-TECAN-2026-08',
 'Nanik Gunawan - Rudolf Eugster', 'Tecan Trading AG',
 'Tecan Trading AG', 'Quality Agreement Medical Laboratory Automation', 'N/A',
 DATE_SUB(CURDATE(), INTERVAL 65 DAY), 0, DATE_ADD(CURDATE(), INTERVAL 300 DAY), 0,
 'Dokumen kualitas terdaftar, review berkala dijadwalkan kuartal III.', NULL,
 @uid, @ucode, @stamp, NOW(), NOW());

INSERT INTO `agreement_histories` (`agreement_id`, `user_id`, `kode_singkatan`, `tanggal_input`, `catatan`, `aksi`, `created_at`) VALUES
(1,  @uid, @ucode, CURDATE(), 'Input contoh dokumen COOPERATION (Expired / Merah)', 'CREATE', NOW()),
(2,  @uid, @ucode, CURDATE(), 'Input contoh dokumen COOPERATION (Mepet Deadline / Kuning)', 'CREATE', NOW()),
(3,  @uid, @ucode, CURDATE(), 'Input contoh dokumen COOPERATION (Completed ACC / Hijau)', 'CREATE', NOW()),
(3,  @uid, @ucode, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Dokumen telah disetujui dan ditandatangani (ACC).', 'STATUS_SELESAI', NOW()),
(4,  @uid, @ucode, CURDATE(), 'Input contoh dokumen COOPERATION (Aktif Normal / Putih)', 'CREATE', NOW()),
(5,  @uid, @ucode, CURDATE(), 'Input contoh dokumen COMPANY (Expired / Merah)', 'CREATE', NOW()),
(6,  @uid, @ucode, CURDATE(), 'Input contoh dokumen COMPANY (Mepet Deadline / Kuning)', 'CREATE', NOW()),
(7,  @uid, @ucode, CURDATE(), 'Input contoh dokumen COMPANY (Completed ACC / Hijau)', 'CREATE', NOW()),
(7,  @uid, @ucode, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Dokumen akta disetujui & selesai diarsipkan (ACC).', 'STATUS_SELESAI', NOW()),
(8,  @uid, @ucode, CURDATE(), 'Input contoh dokumen COMPANY (Permanen N/A / Putih)', 'CREATE', NOW()),
(9,  @uid, @ucode, CURDATE(), 'Input contoh dokumen PRINCIPAL (Expired / Merah)', 'CREATE', NOW()),
(10, @uid, @ucode, CURDATE(), 'Input contoh dokumen PRINCIPAL (Mepet Deadline / Kuning)', 'CREATE', NOW()),
(11, @uid, @ucode, CURDATE(), 'Input contoh dokumen PRINCIPAL (Completed ACC / Hijau)', 'CREATE', NOW()),
(11, @uid, @ucode, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Perjanjian prinsipal telah di-ACC Direksi.', 'STATUS_SELESAI', NOW()),
(12, @uid, @ucode, CURDATE(), 'Input contoh dokumen PRINCIPAL (Aktif Normal / Putih)', 'CREATE', NOW());
