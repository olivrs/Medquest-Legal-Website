@extends('layouts.app')

@section('content')
<div class="auth-wrapper">
    <div class="auth-card" style="max-width:540px;">
        <div class="auth-card-header">
            <div class="d-inline-block mb-2">
                <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest" style="height:34px;">
            </div>
            <h4 class="fw-bold mb-1">USER ACCOUNT REGISTRATION</h4>
            <div class="small opacity-85">PT Medquest Jaya Global - Document System Monitoring</div>
        </div>

        <div class="p-4 p-md-4 text-center">
            <div class="mb-3">
                <div class="d-inline-flex align-items-center justify-content-center rounded-0 shadow-sm"
                     style="width:80px; height:80px; background: #fff8e6; border: 3px solid #f6b73c; color: #d97706; font-size: 38px;">
                    <i class="fas fa-user-clock"></i>
                </div>
            </div>

            <h5 class="fw-bold text-dark mb-2">Registration Submitted Successfully</h5>

            <div class="alert alert-warning border border-warning-subtle text-start p-3 my-3 rounded-0 shadow-sm" style="background:#fffbeb;">
                <div class="d-flex align-items-start gap-2">
                    <i class="fas fa-hourglass-half text-warning fs-5 mt-1"></i>
                    <div>
                        <div class="fw-bold text-dark" style="font-size:0.95rem;">
                            Your account has been registered and is currently under review by the Medquest Team.
                        </div>
                        <div class="text-secondary small mt-1">
                            Notifikasi permohonan persetujuan telah otomatis dikirimkan ke <strong>Gmail Superadmin</strong> lengkap dengan tombol <strong>1-Click ACC</strong>. Begitu disetujui, akun Anda langsung aktif dan dapat digunakan untuk login.
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($pendingInfo)): ?>
                <div class="card border bg-light text-start p-3 rounded-0 mb-4">
                    <div class="fw-bold text-dark small mb-2 border-bottom pb-1">
                        <i class="fas fa-id-card text-primary me-1"></i> Registered Account Details:
                    </div>
                    <div class="row g-2 small">
                        <div class="col-5 text-muted">Full Name:</div>
                        <div class="col-7 fw-bold text-dark"><?= htmlspecialchars($pendingInfo['nama_lengkap']) ?></div>

                        <div class="col-5 text-muted">Initial Code:</div>
                        <div class="col-7">
                            <span class="badge bg-primary px-2 py-1"><?= htmlspecialchars($pendingInfo['kode_singkatan']) ?></span>
                        </div>

                        <div class="col-5 text-muted">Department:</div>
                        <div class="col-7 fw-semibold text-dark"><?= htmlspecialchars($pendingInfo['departemen']) ?></div>

                        <div class="col-5 text-muted">Email Address:</div>
                        <div class="col-7 text-dark"><?= htmlspecialchars($pendingInfo['email']) ?></div>

                        <div class="col-5 text-muted">Current Status:</div>
                        <div class="col-7">
                            <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-clock me-1"></i> Pending Review</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-grid gap-2">
                <a href="<?= base_url('?page=login') ?>" class="btn btn-primary fw-bold py-2 shadow-sm">
                    <i class="fas fa-sign-in-alt me-1"></i> Go to Login Page
                </a>
                <a href="<?= base_url('?page=register') ?>" class="btn btn-outline-secondary btn-sm fw-semibold">
                    <i class="fas fa-user-plus me-1"></i> Register Another Account
                </a>
            </div>

            <div class="mt-4 pt-2 border-top text-muted small">
                If you need urgent activation, please contact your <strong>Medquest Superadmin</strong>.
            </div>
        </div>
    </div>
</div>
@endsection
