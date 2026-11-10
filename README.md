# Medquest Legal Document Monitoring

Sistem informasi monitoring dokumen legal dan masa berlaku perjanjian kerjasama PT Medquest.

## Fitur Utama
- **Monitoring Dokumen Legal:** Pencatatan dan pemantauan dokumen kerjasama (PKS/MoU), izin perusahaan, dan perjanjian prinsipal.
- **Early Warning System:** Notifikasi visual dan email otomatis untuk dokumen yang mendekati tenggat masa berlaku (H-90, H-60, H-30, H-14, H-7, H-3).
- **Role-Based Access Control:** Manajemen otorisasi berjenjang (Superadmin, Admin, User) dengan pengaturan hak akses multi-departemen.
- **Batch Action:** Fitur seleksi dan penghapusan massal dokumen.
- **Responsive Interface:** Antarmuka responsif yang nyaman diakses dari perangkat desktop maupun mobile.

## Persyaratan Sistem
- PHP >= 8.2
- Composer
- MySQL / MariaDB
- Web Server (Nginx / Apache)

## Instalasi
1. Clone repositori:
   ```bash
   git clone https://github.com/olivrs/Medquest-Legal-Website.git
   cd Medquest-Legal-Website
   ```
2. Pasang dependensi:
   ```bash
   composer install
   ```
3. Konfigurasi environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Jalankan migrasi & seeder:
   ```bash
   php artisan migrate --seed
   ```
5. Jalankan server lokal:
   ```bash
   php artisan serve
   ```
