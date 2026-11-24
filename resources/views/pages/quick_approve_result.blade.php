@extends('layouts.app')

@section('content')
<div class="auth-wrapper py-5">
    <div class="auth-card" style="max-width:580px;">
        <div class="auth-card-header">
            <div class="d-inline-block mb-2">
                <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest Logo" style="height:36px;">
            </div>
            <h4 class="fw-bold mb-1">USER ACCOUNT APPROVAL</h4>
            <div class="small opacity-85">PT Medquest Jaya Global - Legal &amp; Document Monitoring</div>
        </div>

        <div class="p-4 text-center">
            <?php if ($status === 'approved_success'): ?>
                <div class="mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle shadow-sm"
                         style="width:84px; height:84px; background: #ecfdf5; border: 3px solid #10b981; color: #059669; font-size: 40px;">
                        <i class="fas fa-check"></i>
                    </div>
                </div>

                <h4 class="fw-bold text-success mb-2"><?= htmlspecialchars($title) ?></h4>
                <p class="text-secondary small mb-3"><?= htmlspecialchars($message) ?></p>

                <?php if ($user): ?>
                <div class="card border bg-light text-start p-3 rounded-0 mb-4 shadow-sm">
                    <div class="fw-bold text-dark small mb-2 border-bottom pb-1">
                        <i class="fas fa-user-check text-success me-1"></i> Informasi Akun Terverifikasi:
                    </div>
                    <div class="row g-2 small">
                        <div class="col-5 text-muted">Nama Pengguna:</div>
                        <div class="col-7 fw-bold text-dark"><?= htmlspecialchars($user->nama_lengkap) ?></div>

                        <div class="col-5 text-muted">Inisial / Kode:</div>
                        <div class="col-7">
                            <span class="badge bg-primary px-2 py-1"><?= htmlspecialchars($user->kode_singkatan) ?></span>
                        </div>

                        <div class="col-5 text-muted">Departemen:</div>
                        <div class="col-7 fw-semibold text-dark"><?= htmlspecialchars($user->departemen_default) ?></div>

                        <div class="col-5 text-muted">Email:</div>
                        <div class="col-7 text-dark"><?= htmlspecialchars($user->email) ?></div>

                        <div class="col-5 text-muted">Status Akun:</div>
                        <div class="col-7">
                            <span class="badge bg-success text-white px-2 py-1"><i class="fas fa-check-circle me-1"></i> AKTIF (APPROVED)</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php elseif ($status === 'already_approved'): ?>
                <div class="mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle shadow-sm"
                         style="width:84px; height:84px; background: #eff6ff; border: 3px solid #3b82f6; color: #2563eb; font-size: 40px;">
                        <i class="fas fa-info"></i>
                    </div>
                </div>

                <h4 class="fw-bold text-primary mb-2"><?= htmlspecialchars($title) ?></h4>
                <p class="text-secondary small mb-3"><?= htmlspecialchars($message) ?></p>

                <?php if ($user): ?>
                <div class="card border bg-light text-start p-3 rounded-0 mb-4 shadow-sm">
                    <div class="row g-2 small">
                        <div class="col-5 text-muted">Nama:</div>
                        <div class="col-7 fw-bold text-dark"><?= htmlspecialchars($user->nama_lengkap) ?> (<?= htmlspecialchars($user->kode_singkatan) ?>)</div>
                        <div class="col-5 text-muted">Email:</div>
                        <div class="col-7"><?= htmlspecialchars($user->email) ?></div>
                        <div class="col-5 text-muted">Status:</div>
                        <div class="col-7"><span class="badge bg-success">AKTIF</span></div>
                    </div>
                </div>
                <?php endif; ?>

            <?php elseif ($status === 'rejected_success'): ?>
                <div class="mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle shadow-sm"
                         style="width:84px; height:84px; background: #fef2f2; border: 3px solid #ef4444; color: #dc2626; font-size: 40px;">
                        <i class="fas fa-times"></i>
                    </div>
                </div>

                <h4 class="fw-bold text-danger mb-2"><?= htmlspecialchars($title) ?></h4>
                <p class="text-secondary small mb-4"><?= htmlspecialchars($message) ?></p>

            <?php else: ?>
                <div class="mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle shadow-sm"
                         style="width:84px; height:84px; background: #fffbeb; border: 3px solid #f59e0b; color: #d97706; font-size: 40px;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>

                <h4 class="fw-bold text-warning mb-2"><?= htmlspecialchars($title) ?></h4>
                <p class="text-secondary small mb-4"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <div class="d-grid gap-2">
                <?php if (is_logged_in() && is_superadmin()): ?>
                    <a href="<?= base_url('?page=user_approvals') ?>" class="btn btn-primary fw-bold py-2 shadow-sm">
                        <i class="fas fa-user-shield me-1"></i> Buka Manajemen Pengguna (Approvals)
                    </a>
                    <a href="<?= base_url('?page=agreements') ?>" class="btn btn-outline-secondary btn-sm fw-semibold">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('?page=login') ?>" class="btn btn-primary fw-bold py-2 shadow-sm">
                        <i class="fas fa-sign-in-alt me-1"></i> Buka Halaman Login
                    </a>
                <?php endif; ?>
            </div>

            <div class="mt-4 pt-2 border-top text-muted small">
                PT Medquest Jaya Global &bull; Internal System Notification
            </div>
        </div>
    </div>
</div>
@endsection
