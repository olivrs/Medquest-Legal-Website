@extends('layouts.app')

@section('content')
<div class="cp-header-row mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('?page=notifications') ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <h1 class="cp-page-title ms-1">Email SMTP Settings</h1>
    </div>
    <div>
        <?php if (!empty($smtpStatus['ready'])): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                <i class="fas fa-check-circle me-1"></i> Active via <?= strtoupper(htmlspecialchars($smtpStatus['source'])) ?> (<?= htmlspecialchars($smtpStatus['sender_email']) ?>)
            </span>
        <?php else: ?>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">
                <i class="fas fa-exclamation-triangle me-1"></i> Direct SMTP Not Enabled (Using FormSubmit Relay)
            </span>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="cp-table-card mt-0">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="fas fa-server text-primary me-2"></i>System Outgoing SMTP Gateway (1 Sender for All Alerts)
            </h6>
            <p class="small text-muted mb-3">
                Configure 1 sender account (e.g. a Gmail account with a 16-digit <strong>App Password</strong> or office SMTP). Once active, all registered email recipients receive H-90 to Hari H reminders directly in their Inbox without requiring FormSubmit confirmation links.
            </p>

            <form method="POST" action="<?= base_url('?page=smtp_settings') ?>">
                @csrf
                <input type="hidden" name="action" value="save">

                <div class="form-check form-switch mb-3 p-2 bg-light border ps-5">
                    <input class="form-check-input" type="checkbox" role="switch" id="smtpEnabledSwitch" name="smtp_enabled" value="1" <?= !empty($smtp['smtp_enabled']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold text-dark" for="smtpEnabledSwitch">
                        Enable System SMTP Gateway
                    </label>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control form-control-sm" value="<?= htmlspecialchars($smtp['smtp_host'] ?? 'smtp.gmail.com') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">SMTP Port</label>
                        <input type="number" name="smtp_port" class="form-control form-control-sm" value="<?= (int) ($smtp['smtp_port'] ?? 587) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Encryption</label>
                        <select name="smtp_encryption" class="form-select form-select-sm">
                            <option value="tls" <?= ($smtp['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (587)</option>
                            <option value="ssl" <?= ($smtp['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (465)</option>
                            <option value="" <?= ($smtp['smtp_encryption'] ?? '') === '' ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">SMTP Username / Email</label>
                        <input type="text" name="smtp_username" class="form-control form-control-sm" placeholder="medquest.legal.alert@gmail.com" value="<?= htmlspecialchars($smtp['smtp_username'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">SMTP Password / Gmail App Password</label>
                        <input type="password" name="smtp_password" class="form-control form-control-sm" placeholder="<?= !empty($smtp['smtp_password']) ? '•••••••••••• (leave blank to keep existing)' : '16-char Gmail App Password' ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Sender Display Name</label>
                        <input type="text" name="sender_name" class="form-control form-control-sm" value="<?= htmlspecialchars($smtp['sender_name'] ?? 'Medquest Agreement Alert (LAC)') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Sender From Email</label>
                        <input type="email" name="sender_email" class="form-control form-control-sm" value="<?= htmlspecialchars($smtp['sender_email'] ?? 'noreply@medquest.co.id') ?>">
                    </div>
                </div>

                <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">
                        <i class="fas fa-save me-1"></i> Save SMTP Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="cp-table-card mt-0">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="fas fa-paper-plane text-success me-2"></i>Send Test Email
            </h6>
            <form method="POST" action="<?= base_url('?page=smtp_settings') ?>">
                @csrf
                <input type="hidden" name="action" value="test_email">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Recipient Email Address</label>
                    <input type="email" name="test_to_email" class="form-control form-control-sm" placeholder="your.email@gmail.com" required>
                </div>
                <button type="submit" class="btn btn-success btn-sm fw-bold w-100">
                    <i class="fas fa-check-double me-1"></i> Test Deliver Email Now
                </button>
            </form>
            <div class="alert alert-light border mt-3 mb-0 small text-muted">
                <strong>CloudPanel Tip:</strong> You can also set <code>MAIL_USERNAME</code> and <code>MAIL_PASSWORD</code> inside your server <code>.env</code> file instead of configuring in UI.
            </div>
        </div>
    </div>
</div>
@endsection
