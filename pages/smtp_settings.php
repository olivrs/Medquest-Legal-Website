<?php
require_login();
$user = current_user();

$smtp = $pdo->query("SELECT * FROM smtp_settings WHERE id = 1")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save') {
        $smtp_host       = trim($_POST['smtp_host'] ?? 'smtp.gmail.com');
        $smtp_port       = (int)($_POST['smtp_port'] ?? 587);
        $smtp_encryption = trim($_POST['smtp_encryption'] ?? 'tls');
        $smtp_username   = trim($_POST['smtp_username'] ?? '');
        // Otomatis hapus spasi dari 16-digit App Password Google
        $smtp_password   = str_replace(' ', '', trim($_POST['smtp_password'] ?? ''));
        $sender_name     = trim($_POST['sender_name'] ?? 'Medquest Agreement Alert (LAC)');
        $sender_email    = trim($_POST['sender_email'] ?? '') ?: $smtp_username;

        // Jika password dikosongkan saat update, gunakan password lama
        if ($smtp_password === '' && !empty($smtp['smtp_password'])) {
            $smtp_password = str_replace(' ', '', $smtp['smtp_password']);
        }

        // Otomatis aktifkan smtp_enabled jika username & password diisi
        $smtp_enabled = ($smtp_username !== '' && $smtp_password !== '') ? 1 : (isset($_POST['smtp_enabled']) ? 1 : 0);

        $upd = $pdo->prepare("
            UPDATE smtp_settings
            SET smtp_enabled = ?, smtp_host = ?, smtp_port = ?, smtp_encryption = ?, smtp_username = ?, smtp_password = ?, sender_name = ?, sender_email = ?
            WHERE id = 1
        ");
        $upd->execute([
            $smtp_enabled,
            $smtp_host,
            $smtp_port,
            $smtp_encryption,
            $smtp_username,
            $smtp_password,
            $sender_name,
            $sender_email
        ]);

        // Reset flag yang tadinya belum terkirim via SMTP asli agar langsung dikirim otomatis ke Gmail sekarang!
        $pdo->exec("
            UPDATE agreements a
            SET notif_h7_sent_at = NULL, notif_h3_sent_at = NULL, notif_h1_sent_at = NULL
            WHERE NOT EXISTS (
                SELECT 1 FROM email_notifications e 
                WHERE e.agreement_id = a.id AND e.status_kirim = 'SENT_GMAIL_SMTP'
            )
        ");

        // Langsung jalankan pengiriman otomatis H-7, H-3, H-1 ke Gmail asli!
        $autoResults = run_auto_expiry_notifications($pdo);
        $sentReal = 0;
        foreach ($autoResults as $r) {
            if (!empty($r['sent_smtp'])) $sentReal++;
        }

        if ($sentReal > 0) {
            set_flash('success', "Konfigurasi Gmail SMTP tersimpan & sistem <strong>langsung mengirimkan {$sentReal} email otomatis (H-7/H-3/H-1)</strong> ke Inbox Gmail penginput data!");
        } elseif ($smtp_enabled) {
            set_flash('success', 'Konfigurasi Gmail SMTP berhasil diaktifkan! Mulai sekarang setiap dokumen yang masuk H-7, H-3, dan H-1 akan langsung masuk ke Inbox Gmail user secara otomatis.');
        } else {
            set_flash('info', 'Pengaturan SMTP disimpan.');
        }

        header("Location: " . base_url('?page=smtp_settings'));
        exit;
    }

    if ($action === 'test_email') {
        $targetEmail = trim($_POST['test_target_email'] ?? $user['email']);
        $sampleAgr = $pdo->query("SELECT * FROM agreements ORDER BY id DESC LIMIT 1")->fetch();
        if ($sampleAgr) {
            $err = '';
            $subject = "[TEST GMAIL MEDQUEST] Uji Coba Notifikasi Otomatis H-7, H-3, H-1";
            $htmlBody = build_expiry_email_html($sampleAgr, [
                'nama_lengkap' => $user['nama_lengkap'],
                'kode_singkatan' => $user['kode_singkatan'],
                'email' => $targetEmail
            ], 'H-3', 3);

            if (!empty($smtp['smtp_enabled']) && !empty($smtp['smtp_username']) && !empty($smtp['smtp_password'])) {
                if (send_via_smtp_socket($smtp, $targetEmail, $subject, $htmlBody, $err)) {
                    set_flash('success', "BERHASIL! Email uji coba telah dikirim langsung ke Inbox Gmail <strong>{$targetEmail}</strong>. Silakan cek Inbox/Spam Anda sekarang!");
                } else {
                    set_flash('error', "Gagal mengirim ke Gmail: <strong>{$err}</strong>");
                }
            } else {
                set_flash('error', 'Harap isi Email Gmail Pengirim dan 16-Digit App Password Google pada form di atas terlebih dahulu, lalu klik Simpan.');
            }
        }
        header("Location: " . base_url('?page=smtp_settings'));
        exit;
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="cp-header-row mb-4">
            <h1 class="cp-page-title">Settings Email SMTP</h1>
            <a href="<?= base_url('?page=notifications') ?>" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2">
                Riwayat Email
            </a>
        </div>

        <!-- Panduan Cepat 30 Detik Cara Ambil 16-Digit App Password Gmail -->
        <div class="card border-primary border-2 shadow-sm rounded-0 mb-4">
            <div class="card-body p-3 bg-primary-subtle text-dark">
                <div class="fw-bold mb-1"><i class="fas fa-key text-primary me-1"></i> Cara Mengambil 16-Digit Sandi Aplikasi (App Password) Gmail (Hanya 1 Menit):</div>
                <ol class="small mb-0 ps-3" style="line-height:1.6;">
                    <li>Buka halaman resmi Google: <a href="https://myaccount.google.com/apppasswords" target="_blank" class="fw-bold text-primary">https://myaccount.google.com/apppasswords</a> (Pastikan Verifikasi 2 Langkah aktif di akun Gmail Anda).</li>
                    <li>Ketik nama aplikasi: <code>Medquest Log</code> lalu klik tombol <strong>Buat (Create)</strong>.</li>
                    <li>Salin <strong>16 huruf kode yang muncul di kotak kuning</strong> (contoh: <code>abcd efgh ijkl mnop</code>) lalu tempelkan ke kolom <strong>App Password Gmail</strong> di bawah ini.</li>
                </ol>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-0 mb-4">
            <div class="card-body p-4">
                <form method="POST" action="<?= base_url('?page=smtp_settings') ?>">
                    <input type="hidden" name="action" value="save">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">1. Alamat Gmail Pengirim Sistem <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-envelope text-danger"></i></span>
                                <input type="email" name="smtp_username" class="form-control" value="<?= htmlspecialchars($smtp['smtp_username'] ?? '') ?>" placeholder="contoh: emailanda@gmail.com" required>
                            </div>
                            <div class="form-text small">Akun Gmail yang bertugas mengirimkan notifikasi ke para user.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">2. 16-Digit App Password Gmail <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-key text-warning"></i></span>
                                <input type="password" name="smtp_password" class="form-control font-monospace" placeholder="<?= !empty($smtp['smtp_password']) ? '•••••••••••••••• (Sudah Tersimpan)' : 'Tempel 16 digit App Password Google...' ?>" <?= empty($smtp['smtp_password']) ? 'required' : '' ?>>
                            </div>
                            <div class="form-text small">Spasi otomatis dihapus saat disimpan.</div>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control form-control-sm" value="<?= htmlspecialchars($smtp['smtp_host'] ?? 'smtp.gmail.com') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">SMTP Port</label>
                            <input type="number" name="smtp_port" class="form-control form-control-sm" value="<?= htmlspecialchars($smtp['smtp_port'] ?? 587) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Enkripsi</label>
                            <select name="smtp_encryption" class="form-select form-select-sm">
                                <option value="tls" <?= ($smtp['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (Port 587 - Recommended)</option>
                                <option value="ssl" <?= ($smtp['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nama Pengirim di Inbox Gmail</label>
                            <input type="text" name="sender_name" class="form-control form-control-sm" value="<?= htmlspecialchars($smtp['sender_name'] ?? 'Medquest Agreement Alert (LAC)') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email Pengirim (From)</label>
                            <input type="email" name="sender_email" class="form-control form-control-sm" value="<?= htmlspecialchars($smtp['sender_email'] ?? '') ?>" placeholder="Otomatis mengikuti Gmail Pengirim">
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary fw-bold px-4 py-2">
                            <i class="fas fa-bolt me-1"></i> Simpan &amp; Langsung Aktifkan Kirim Otomatis H-7, H-3, H-1
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel Kirim Test Email -->
        <div class="card border-0 shadow-sm rounded-0">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-2"><i class="fas fa-paper-plane text-success me-1"></i> Tes Kirim Email ke Gmail Anda Sekarang</h6>
                <p class="small text-muted mb-3">Setelah menyimpan Gmail &amp; App Password di atas, Anda bisa mengetes apakah email langsung masuk ke Inbox Gmail tujuan:</p>
                <form method="POST" action="<?= base_url('?page=smtp_settings') ?>" class="row g-2 align-items-center">
                    <input type="hidden" name="action" value="test_email">
                    <div class="col-md-8">
                        <input type="email" name="test_target_email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Masukkan alamat Gmail tujuan..." required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success fw-bold w-100">
                            <i class="fas fa-check me-1"></i> Kirim Test ke Gmail
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
