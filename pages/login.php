<?php
if (is_logged_in()) {
    header("Location: " . base_url('?page=agreements'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identitas = trim($_POST['identitas'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($identitas === '' || $password === '') {
        set_flash('error', 'Harap isi Email / NIK dan Password Anda.');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR kode_singkatan = ? LIMIT 1");
        $stmt->execute([$identitas, strtoupper($identitas)]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $status = $user['status'] ?? 'approved';
            if ($status === 'pending') {
                set_flash('error', '<div class="fw-bold mb-1"><i class="fas fa-hourglass-half me-1 text-warning"></i> Akun Sedang Ditinjau!</div>Akun dengan kode <strong>' . htmlspecialchars($user['kode_singkatan']) . '</strong> sudah didaftarkan dan <strong>sedang ditinjau oleh Team Medquest</strong>.<br><span class="small opacity-85">Mohon menunggu persetujuan (ACC) dari Superadmin dan cek secara berkala.</span>');
                header("Location: " . base_url('?page=login'));
                exit;
            } elseif ($status === 'rejected') {
                set_flash('error', '<div class="fw-bold mb-1"><i class="fas fa-times-circle me-1 text-danger"></i> Pendaftaran Akun Ditolak</div>Pendaftaran akun dengan kode <strong>' . htmlspecialchars($user['kode_singkatan']) . '</strong> ditolak oleh Superadmin. Silakan hubungi admin Medquest.');
                header("Location: " . base_url('?page=login'));
                exit;
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'kode_singkatan' => strtoupper($user['kode_singkatan']),
                    'email' => $user['email'],
                    'departemen_default' => $user['departemen_default'],
                    'role' => $user['role'],
                    'status' => $status
                ];
                set_flash('success', 'Selamat datang kembali, <strong>' . htmlspecialchars($user['nama_lengkap']) . ' (' . htmlspecialchars($user['kode_singkatan']) . ')</strong>!');
                $redirectDept = ($user['departemen_default'] && $user['departemen_default'] !== 'ALL')
                    ? '&dept=' . urlencode($user['departemen_default'])
                    : '';
                header("Location: " . base_url('?page=agreements' . $redirectDept));
                exit;
            }
        } else {
            set_flash('error', 'Email / NIK atau Password tidak sesuai.');
            header("Location: " . base_url('?page=login'));
            exit;
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-card-header">
            <div class="d-inline-block mb-2">
                <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest" style="height:34px;">
            </div>
            <h4 class="fw-bold mb-1">LOG OF COOPERATION AGREEMENT</h4>
            <div class="small opacity-75">Document System Monitoring</div>
        </div>

        <div class="p-4 p-md-4">
            <form method="POST" action="<?= base_url('?page=login') ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email / NIK </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-user-tag text-primary"></i></span>
                        <input type="text" name="identitas" class="form-control" placeholder="Contoh: FJR, NIK, atau fjr.medquest@gmail.com" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-lock text-primary"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password..." required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mt-1">
                    <i class="fas fa-sign-in-alt me-1"></i> Masuk ke Sistem
                </button>
            </form>

            <hr class="my-4">

            <div class="text-center">
                <p class="mb-2 text-muted small">Belum punya akun atau ingin mendaftarkan singkatan nama &amp; email pribadi?</p>
                <a href="<?= base_url('?page=register') ?>" class="btn btn-outline-primary w-100 fw-semibold">
                    <i class="fas fa-user-plus me-1"></i> Buat Akun Baru (Wajib Email &amp; Singkatan Nama)
                </a>
            </div>

            <div class="mt-4 p-3 rounded-0 bg-light border small">
                <div class="fw-bold text-dark mb-1"><i class="fas fa-info-circle text-primary me-1"></i> Akun Demo Bawaan (Sesuai Foto Form):</div>
                <div>Kode Singkatan / NIK: <code class="fw-bold">FJR</code> &bull; Email: <code>fjr.medquest@gmail.com</code></div>
                <div>Password: <code class="fw-bold">medquest123</code></div>
            </div>
        </div>
    </div>
</div>
