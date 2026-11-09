# Panduan Upload & Deploy Laravel 13 ke CloudPanel
**Medquest Legal Document System Monitoring (Framework Laravel 13.x)**

Proyek ini telah dikonversi penuh ke **Laravel 13** dengan standar keamanan produksi:
- **Tidak ada password database di dalam kode PHP** (semua kredensial rahasia hanya disimpan di file `.env` di server CloudPanel yang diabaikan oleh `.gitignore`).
- Dilengkapi **Database Migrations & Seeders** resmi Laravel (`php artisan migrate --seed`).
- Dilengkapi **Laravel Task Scheduler** (`php artisan medquest:notify-deadlines`) untuk pengiriman email otomatis H-90, H-60, H-30, H-14, H-7, dan H-3.

---

## Langkah 1: Buat Site Baru di CloudPanel
1. Login ke dashboard **CloudPanel** Anda.
2. Klik menu **+ Add Site** &rarr; pilih **Create a PHP Site**.
3. Isi pengaturan berikut:
   - **Application**: Pilih **Laravel 12 / 13** (atau versi Laravel terbaru yang tersedia di CloudPanel)
   - **Domain Name**: Masukkan domain atau subdomain kantor (misal: `legal.medquest.co.id`)
   - **PHP Version**: Pilih **PHP 8.3** atau **PHP 8.4**
   - **Site User**: Biarkan otomatis (misal: `medquest-legal`)
4. Klik **Create**.

---

## Langkah 2: Buat Database MySQL di CloudPanel
1. Masuk ke kelola site Anda di CloudPanel &rarr; buka tab **Databases**.
2. Klik **Add Database** lalu isi:
   - **Database Name**: `db_medquest_log`
   - **Database User Name**: `medquest_user`
   - **Database User Password**: Buat password kuat (simpan untuk dimasukkan ke file `.env` pada Langkah 4).

---

## Langkah 3: Upload / Clone Proyek Laravel 13 ke CloudPanel
Masuk ke terminal SSH CloudPanel (atau gunakan fitur **File Manager** / **Git** di CloudPanel):

```bash
# Masuk ke folder htdocs domain Anda
cd /home/<site-user>/htdocs/<domain-anda>

# Hapus isi folder bawaan jika kosong, lalu clone dari GitHub atau upload file proyek medquest-laravel
git clone https://github.com/olivrs/Medquest-Legal-Website.git .

# Install dependency Composer
composer install --no-dev --optimize-autoloader
```

---

## Langkah 4: Konfigurasi File `.env` (Aman & Rahasia)
Salin file `.env.example` menjadi `.env`, lalu sesuaikan koneksi database CloudPanel:

```bash
cp .env.example .env
nano .env
```

Ubah bagian berikut di dalam `.env`:
```env
APP_NAME="Medquest Legal Document Monitoring"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_medquest_log
DB_USERNAME=medquest_user
DB_PASSWORD=password_database_cloudpanel_anda
```

---

## Langkah 5: Generate Key & Jalankan Migrasi Database
Jalankan perintah Artisan berikut satu kali di terminal CloudPanel:

```bash
# 1. Buat enkripsi APP_KEY Laravel
php artisan key:generate

# 2. Buat seluruh tabel & isi data awal (7 Departemen + Akun Superadmin FJR)
php artisan migrate --seed --force

# 3. Pastikan izin folder storage & cache dapat ditulis oleh web server
chmod -R 775 storage bootstrap/cache
```

### Akun Login Bawaan Superadmin:
- **Email / Kode Singkatan**: `FJR` (atau `fjr.medquest@gmail.com`)
- **Password**: `medquest123`

---

## Langkah 6: Aktifkan Cron Job Notifikasi Email di CloudPanel
Agar pengingat email otomatis **H-90, H-60, H-30, H-14, H-7, dan H-3** berjalan sendiri setiap jam di server CloudPanel:
1. Buka tab **Cron Jobs** di CloudPanel.
2. Klik **Add Cron Job**:
   - **Template**: `Every Minute (* * * * *)` (Standar resmi Laravel Scheduler)
   - **Command**:
     ```bash
     /usr/bin/php8.3 /home/<site-user>/htdocs/<domain-anda>/artisan schedule:run >> /dev/null 2>&1
     ```
3. Klik **Create**.
