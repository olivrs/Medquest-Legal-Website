<?php
if (is_logged_in()) {
    header("Location: " . base_url('?page=agreements'));
    exit;
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY kode_dept ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap       = trim($_POST['nama_lengkap'] ?? '');
    $kode_singkatan     = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($_POST['kode_singkatan'] ?? '')));
    $departemen         = strtoupper(trim($_POST['departemen'] ?? ''));
    $email              = strtolower(trim($_POST['email'] ?? ''));
    $password           = $_POST['password'] ?? '';
    $password_confirm   = $_POST['password_confirm'] ?? '';

    if ($nama_lengkap === '' || $kode_singkatan === '' || $departemen === '' || $email === '' || $password === '') {
        set_flash('error', 'Semua kolom wajib diisi (Nama Lengkap, Kode Singkatan, Departemen, Email Pribadi, dan Password).');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Format Email Pribadi tidak valid.');
    } elseif (strlen($kode_singkatan) < 2 || strlen($kode_singkatan) > 10) {
        set_flash('error', 'Nama Singkatan Medquest harus terdiri dari 2 sampai 10 karakter (contoh: RYN atau FJR).');
    } elseif (strlen($password) < 6) {
        set_flash('error', 'Password minimal terdiri dari 6 karakter.');
    } elseif ($password !== $password_confirm) {
        set_flash('error', 'Konfirmasi password tidak cocok.');
    } else {
        $cek = $pdo->prepare("SELECT id, email, kode_singkatan FROM users WHERE email = ? OR kode_singkatan = ? LIMIT 1");
        $cek->execute([$email, $kode_singkatan]);
        $existing = $cek->fetch();

        if ($existing) {
            if (strtolower($existing['email']) === $email) {
                set_flash('error', 'Email pribadi <strong>' . htmlspecialchars($email) . '</strong> sudah terdaftar. Silakan gunakan email lain atau hubungi admin.');
            } else {
                set_flash('error', 'Singkatan nama <strong>' . htmlspecialchars($kode_singkatan) . '</strong> sudah digunakan user lain.');
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("
                INSERT INTO users (nama_lengkap, kode_singkatan, email, password, departemen_default, role, status) 
                VALUES (?, ?, ?, ?, ?, 'user', 'pending')
            ");
            $ins->execute([$nama_lengkap, $kode_singkatan, $email, $hash, $departemen]);

            $_SESSION['registered_pending'] = [
                'nama_lengkap'   => $nama_lengkap,
                'kode_singkatan' => $kode_singkatan,
                'email'          => $email,
                'departemen'     => $departemen,
                'registered_at'  => date('Y-m-d H:i:s')
            ];

            header("Location: " . base_url('?page=register_pending'));
            exit;
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width:560px;">
        <div class="auth-card-header">
            <div class="d-inline-block mb-2">
                <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest" style="height:34px; border-radius:6px;">
            </div>
            <h4 class="fw-bold mb-1">PENDAFTARAN AKUN USER MEDQUEST</h4>
            <div class="small opacity-85">Wajib mencantumkan Email Pribadi &amp; Singkatan Nama Medquest (Contoh: RYN / FJR)</div>
        </div>

        <div class="p-4 p-md-4">
            <form method="POST" action="<?= base_url('?page=register') ?>">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user text-primary"></i></span>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Ryan" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Kode Singkatan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-id-badge text-primary"></i></span>
                            <input type="text" name="kode_singkatan" id="inputSingkatan" class="form-control text-uppercase fw-bold" placeholder="CONTOH: RYN" maxlength="10" value="<?= htmlspecialchars($_POST['kode_singkatan'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Departemen <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-building text-primary"></i></span>
                            <select name="departemen" class="form-select fw-semibold" required>
                                <option value="">-- Pilih --</option>
                                <option value="ALL" <?= (($_POST['departemen'] ?? '') === 'ALL') ? 'selected' : '' ?>>ALL (Semua)</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept['kode_dept']) ?>" <?= (($_POST['departemen'] ?? '') === $dept['kode_dept']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['kode_dept']) ?> - <?= htmlspecialchars($dept['nama_dept']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Email Pribadi <span class="text-secondary fw-normal small">(Untuk Notifikasi Pengingat)</span> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-primary"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="contoh: ryan@gmail.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-primary"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ulangi Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-shield-alt text-primary"></i></span>
                            <input type="password" name="password_confirm" class="form-control" placeholder="Ketik ulang password" required>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-2 px-3 rounded-2 bg-light border small text-muted">
                    <i class="fas fa-info-circle text-primary me-1"></i> Setelah mendaftar, akun akan ditinjau oleh <strong>Superadmin Medquest</strong> terlebih dahulu sebelum dapat digunakan untuk login.
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mt-3 shadow-sm">
                    <i class="fas fa-user-plus me-1"></i> Daftarkan Akun
                </button>
            </form>

            <div class="text-center mt-3">
                <span class="small text-muted">Sudah memiliki akun yang disetujui?</span>
                <a href="<?= base_url('?page=login') ?>" class="small fw-bold text-decoration-none ms-1">Login di sini</a>
            </div>
        </div>
    </div>
</div>
