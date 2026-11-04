# Medquest - Log of Cooperation Agreement (MJG-FOR-LAC.12-001-03)

Sistem Informasi Monitoring Kontrak & Pengingat Deadline Otomatis (`H-90`, `H-60`, `H-30`, `H-14`, `H-7`, `H-3`) berbasis PHP Native & MySQL (Laragon).

## Fitur Utama
- **Isolasi Dokumen per Kode User Legal**: Setiap user yang mendaftar wajib mencantumkan **Kode Singkatan Medquest** (contoh: `RYN`, `FJR`) dan **Email Pribadi**. Saat login, user hanya melihat dokumen dengan kode singkatannya sendiri lintas departemen.
- **Filter Departemen / Divisi**: Mendukung filter per departemen (`MHO`, `MBB`, `MPC`, `RA`, `GA`, `LAC`, atau tambah departemen baru).
- **Stempel Otomatis `Created By` & Riwayat Note**: Setiap input data baru atau penambahan tindak lanjut (`+ Note`) otomatis mencatat stempel `[KODE], [DD MMM YYYY]` (contoh: `RYN, 03 NOV 2026`).
- **Jadwal Tanggal Deadline Otomatis**: Menghitung otomatis tanggal jatuh tempo `H-90`, `H-60`, `H-30`, `H-14`, `H-7`, dan `H-3` sebelum `Expiry Date`.
- **Notifikasi Email Otomatis**: Mengirimkan pengingat ke email pribadi penginput data saat kontrak memasuki tahapan deadline.
- **Inspect Detail & Lampiran File**: Mendukung preview rincian dokumen dan upload lampiran file (PDF, JPG, PNG, Word, Excel).

## Cara Instalasi di Laragon
1. Simpan folder project di `C:\laragon\www\medquest-log`.
2. Nyalakan **Apache** dan **MySQL** di Laragon.
3. Buka browser dan akses `http://localhost/medquest-log` (Database `db_medquest_log` dan seluruh tabel akan otomatis dibuat saat pertama kali dibuka).
