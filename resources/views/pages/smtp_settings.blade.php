@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="cp-header-row mb-3">
            <h1 class="cp-page-title">Email SMTP Settings (System Sender Gateway)</h1>
            <a href="<?= base_url('?page=notifications') ?>" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2">
                Email Logs
            </a>
        </div>

        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4 p-3 rounded-0" style="background:#e0f2fe; border-left: 4px solid #0284c7 !important;">
            <i class="fas fa-info-circle fa-2x me-3 text-primary"></i>
            <div class="small" style="line-height:1.55;">
                <strong class="text-dark">PENTING (Untuk IT / Superadmin):</strong> Halaman ini adalah pengaturan <u>Akun Kurir Pengirim Email (Sender Gateway)</u> milik kantor. Cukup disetel <strong>1 kali saja</strong> oleh Superadmin/IT. Pengguna atau karyawan yang mendaftar akun <strong>TIDAK PERLU</strong> mengisi halaman ini. Begitu disimpan, setiap ada pendaftaran user baru, sistem akan langsung mengirim email notifikasi ke Gmail Superadmin lengkap dengan tombol <strong>[1-Click ACC]</strong>.
            </div>
        </div>

        <div class="card border-primary border-2 shadow-sm rounded-0 mb-4">
            <div class="card-body p-3 bg-primary-subtle text-dark">
                <div class="fw-bold mb-1"><i class="fas fa-key text-primary me-1"></i> How to Get a 16-Digit Gmail App Password (Takes 1 Minute):</div>
                <ol class="small mb-0 ps-3" style="line-height:1.6;">
                    <li>Open Google's official App Passwords page: <a href="https://myaccount.google.com/apppasswords" target="_blank" class="fw-bold text-primary">https://myaccount.google.com/apppasswords</a> (Make sure 2-Step Verification is enabled on your Gmail account).</li>
                    <li>Enter application name: <code>Medquest Log</code> and click <strong>Create</strong>.</li>
                    <li>Copy the <strong>16-character code shown</strong> (e.g., <code>abcd efgh ijkl mnop</code>) and paste it into the <strong>16-Digit Gmail App Password</strong> field below.</li>
                </ol>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-0 mb-4">
            <div class="card-body p-4">
                <form method="POST" action="<?= base_url('?page=smtp_settings') ?>">
                    @csrf
                    <input type="hidden" name="action" value="save">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">1. System Sender Gmail Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-envelope text-danger"></i></span>
                                <input type="email" name="smtp_username" class="form-control" value="<?= htmlspecialchars($smtp['smtp_username'] ?? '') ?>" placeholder="e.g., youremail@gmail.com" required>
                            </div>
                            <div class="form-text small">The Gmail account used to deliver reminder notifications to users.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">2. 16-Digit Gmail App Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-key text-warning"></i></span>
                                <input type="password" name="smtp_password" class="form-control font-monospace" placeholder="<?= !empty($smtp['smtp_password']) ? '•••••••••••••••• (Saved)' : 'Paste 16-digit Google App Password...' ?>" <?= empty($smtp['smtp_password']) ? 'required' : '' ?>>
                            </div>
                            <div class="form-text small">Spaces are removed automatically when saved.</div>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control form-control-sm" value="<?= htmlspecialchars($smtp['smtp_host'] ?? 'smtp.gmail.com') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">SMTP Port</label>
                            <input type="number" name="smtp_port" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($smtp['smtp_port'] ?? 587)) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Encryption</label>
                            <select name="smtp_encryption" class="form-select form-select-sm">
                                <option value="tls" <?= ($smtp['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (Port 587 - Recommended)</option>
                                <option value="ssl" <?= ($smtp['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Sender Display Name</label>
                            <input type="text" name="sender_name" class="form-control form-control-sm" value="<?= htmlspecialchars($smtp['sender_name'] ?? 'Medquest Agreement Alert (LAC)') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Sender Email (From)</label>
                            <input type="email" name="sender_email" class="form-control form-control-sm" value="<?= htmlspecialchars($smtp['sender_email'] ?? '') ?>" placeholder="Defaults to Sender Gmail Address">
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary fw-bold px-4 py-2">
                            <i class="fas fa-bolt me-1"></i> Save &amp; Enable Auto-Send H-7, H-3, H-1
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-0">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-2"><i class="fas fa-paper-plane text-success me-1"></i> Send Test Email to Your Gmail Now</h6>
                <p class="small text-muted mb-3">After saving your Gmail &amp; App Password above, test direct delivery to an inbox:</p>
                <form method="POST" action="<?= base_url('?page=smtp_settings') ?>" class="row g-2 align-items-center">
                    @csrf
                    <input type="hidden" name="action" value="test_email">
                    <div class="col-md-8">
                        <input type="email" name="test_target_email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Enter recipient Gmail address..." required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success fw-bold w-100">
                            <i class="fas fa-check me-1"></i> Send Test Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
