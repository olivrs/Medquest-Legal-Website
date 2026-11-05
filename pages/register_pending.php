<?php
$pendingInfo = $_SESSION['registered_pending'] ?? null;
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width:540px;">
        <div class="auth-card-header">
            <div class="d-inline-block mb-2">
                <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest" style="height:34px; border-radius:6px;">
            </div>
            <h4 class="fw-bold mb-1">PENDAFTARAN AKUN USER</h4>
            <div class="small opacity-85">PT Medquest Jaya Global - Document System Monitoring</div>
        </div>

        <div class="p-4 p-md-4 text-center">
            <!-- Icon Status Pending -->
            <div class="mb-3">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle shadow-sm"
                     style="width:80px; height:80px; background: #fff8e6; border: 3px solid #f6b73c; color: #d97706; font-size: 38px;">
                    <i class="fas fa-user-clock"></i>
                </div>
            </div>

            <!-- Pesan Utama Sesuai Arahan User -->
            <h5 class="fw-bold text-dark mb-2">Pendaftaran Berhasil Dikirim</h5>
            
            <div class="alert alert-warning border border-warning-subtle text-start p-3 my-3 rounded-3 shadow-sm" style="background:#fffbeb;">
                <div class="d-flex align-items-start gap-2">
                    <i class="fas fa-hourglass-half text-warning fs-5 mt-1"></i>
                    <div>
                        <div class="fw-bold text-dark" style="font-size:0.95rem;">
                            Akun sudah di daftarkan dan sedang di Tinjau oleh Team Medquest, Mohon menunggu dan cek secara Berkala.
                        </div>
                        <div class="text-secondary small mt-1">
                            Akun Anda memerlukan persetujuan (ACC) dari <strong>Superadmin</strong> sebelum dapat digunakan untuk login ke sistem.
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($pendingInfo): ?>
                <!-- Ringkasan Data Akun yang Didaftarkan -->
                <div class="card border bg-light text-start p-3 rounded-3 mb-4">
                    <div class="fw-bold text-dark small mb-2 border-bottom pb-1">
                        <i class="fas fa-id-card text-primary me-1"></i> Rincian Akun Terdaftar:
                    </div>
                    <div class="row g-2 small">
                        <div class="col-5 text-muted">Nama Lengkap:</div>
                        <div class="col-7 fw-bold text-dark"><?= htmlspecialchars($pendingInfo['nama_lengkap']) ?></div>

                        <div class="col-5 text-muted">Kode Singkatan:</div>
                        <div class="col-7">
                            <span class="badge bg-primary px-2 py-1"><?= htmlspecialchars($pendingInfo['kode_singkatan']) ?></span>
                        </div>

                        <div class="col-5 text-muted">Departemen:</div>
                        <div class="col-7 fw-semibold text-dark"><?= htmlspecialchars($pendingInfo['departemen']) ?></div>

                        <div class="col-5 text-muted">Email Pribadi:</div>
                        <div class="col-7 text-dark"><?= htmlspecialchars($pendingInfo['email']) ?></div>

                        <div class="col-5 text-muted">Status Saat Ini:</div>
                        <div class="col-7">
                            <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-clock me-1"></i> Menunggu Peninjauan</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-grid gap-2">
                <a href="<?= base_url('?page=login') ?>" class="btn btn-primary fw-bold py-2 shadow-sm">
                    <i class="fas fa-sign-in-alt me-1"></i> Ke Halaman Login
                </a>
                <a href="<?= base_url('?page=register') ?>" class="btn btn-outline-secondary btn-sm fw-semibold">
                    <i class="fas fa-user-plus me-1"></i> Daftarkan Akun Lain
                </a>
            </div>

            <div class="mt-4 pt-2 border-top text-muted small">
                Jika memerlukan aktivasi segera untuk kebutuhan mendesak, silakan konfirmasi kepada <strong>Superadmin Medquest</strong>.
            </div>
        </div>
    </div>
</div>
