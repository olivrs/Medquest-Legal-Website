<?php
if (is_logged_in()) {
    header("Location: " . base_url('?page=agreements'));
    exit;
}

$todayPreview = strtoupper(date('d M Y'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap       = trim($_POST['nama_lengkap'] ?? '');
    $kode_singkatan     = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($_POST['kode_singkatan'] ?? '')));
    $email              = strtolower(trim($_POST['email'] ?? ''));
    $departemen_default = 'ALL';
    $password           = $_POST['password'] ?? '';
    $password_confirm   = $_POST['password_confirm'] ?? '';

    if ($nama_lengkap === '' || $kode_singkatan === '' || $email === '' || $password === '') {
        set_flash('error', 'Semua kolom wajib diisi (termasuk Email Pribadi & Singkatan Nama Medquest).');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Format Email Pribadi tidak valid.');
    } elseif (strlen($kode_singkatan) < 2 || strlen($kode_singkatan) > 10) {
        set_flash('error', 'Nama Singkatan Medquest harus terdiri dari 2 sampai 10 karakter (contoh: RYN atau FJR).');
    } elseif ($password !== $password_confirm) {
        set_flash('error', 'Konfirmasi password tidak cocok.');
    } else {
        $cek = $pdo->prepare("SELECT id, email, kode_singkatan FROM users WHERE email = ? OR kode_singkatan = ? LIMIT 1");
        $cek->execute([$email, $kode_singkatan]);
        $existing = $cek->fetch();

        if ($existing) {
            if (strtolower($existing['email']) === $email) {
                set_flash('error', 'Email pribadi <strong>' . htmlspecialchars($email) . '</strong> sudah terdaftar. Silakan gunakan email lain atau login.');
            } else {
                set_flash('error', 'Singkatan nama <strong>' . htmlspecialchars($kode_singkatan) . '</strong> sudah digunakan user lain.');
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (nama_lengkap, kode_singkatan, email, password, departemen_default, role) VALUES (?, ?, ?, ?, 'ALL', 'user')");
            $ins->execute([$nama_lengkap, $kode_singkatan, $email, $hash]);

            $newUserId = (int)$pdo->lastInsertId();
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user'] = [
                'id' => $newUserId,
                'nama_lengkap' => $nama_lengkap,
                'kode_singkatan' => $kode_singkatan,
                'email' => $email,
                'departemen_default' => 'ALL',
                'role' => 'user'
            ];

            set_flash('success', 'Akun <strong>' . htmlspecialchars($nama_lengkap) . ' (' . htmlspecialchars($kode_singkatan) . ')</strong> berhasil dibuat! Anda dapat menginput dokumen lintas departemen dan memantau khusus dokumen berkode <strong>' . htmlspecialchars($kode_singkatan) . '</strong>.');
            header("Location: " . base_url('?page=agreements'));
            exit;
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width:560px;">
        <div class="auth-card-header">
            <div class="bg-white d-inline-block px-3 py-2 rounded-3 mb-2">
                <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest" style="height:30px;">
            </div>
            <h4 class="fw-bold mb-1">PENDAFTARAN AKUN USER MEDQUEST</h4>
            <div class="small opacity-85">Wajib mencantumkan Email Pribadi &amp; Singkatan Nama Medquest (Contoh: RYN / FJR)</div>
        </div>

        <div class="p-4">
            <form method="POST" action="<?= base_url('?page=register') ?>">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Ryan" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Kode Singkatan Medquest <span class="text-danger">*</span></label>
                        <input type="text" name="kode_singkatan" id="inputSingkatan" class="form-control text-uppercase fw-bold" placeholder="Contoh: RYN" maxlength="10" value="<?= htmlspecialchars($_POST['kode_singkatan'] ?? '') ?>" required>
                        <div class="form-text" style="font-size:0.74rem;">Kode unik dokumen Anda</div>
                    </div>

                    <!-- Preview Stempel Created By Otomatis -->
                    <div class="col-12">
                        <div class="p-2 px-3 rounded-3 bg-primary-subtle border border-primary-subtle d-flex justify-content-between align-items-center">
                            <span class="small fw-semibold text-primary-emphasis">
                                <i class="fas fa-stamp me-1"></i> Tanda Kolom "Created By" Anda:
                            </span>
                            <span class="badge bg-dark font-monospace fs-6 px-3 py-2" id="stampPreview">
                                RYN, <?= $todayPreview ?>
                            </span>
                        </div>
                        <div class="form-text small text-muted mt-1">
                            Saat login, Anda hanya akan melihat dokumen yang memiliki kode singkatan Anda sendiri (bisa dari berbagai departemen seperti MHO, MBB, MPC, RA, GA).
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Email Pribadi (Wajib untuk Notifikasi H-90, 60, 30, 14, 7, 3) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-danger"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="contoh: ryan@gmail.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ulangi Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirm" class="form-control" placeholder="Ketik ulang password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mt-4">
                    <i class="fas fa-check-circle me-1"></i> Daftarkan Akun &amp; Masuk
                </button>
            </form>

            <div class="text-center mt-3">
                <span class="small text-muted">Sudah memiliki akun?</span>
                <a href="<?= base_url('?page=login') ?>" class="small fw-bold text-decoration-none ms-1">Login di sini</a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('inputSingkatan')?.addEventListener('input', function() {
    const val = this.value.trim().toUpperCase() || 'RYN';
    document.getElementById('stampPreview').textContent = val + ', <?= $todayPreview ?>';
});
</script>
