@extends('layouts.app')

@section('content')
<div class="cp-header-row mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('?page=notifications') ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <h1 class="cp-page-title ms-1">Email Gateway Settings</h1>
    </div>
    <div>
        <?php if (!empty($smtpStatus['ready'])): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                <i class="fas fa-check-circle me-1"></i>
                <?= ($smtpStatus['source'] ?? '') === 'apps_script'
                    ? 'Active via Google Apps Script (Free Gmail Bridge)'
                    : 'Active via ' . strtoupper(htmlspecialchars($smtpStatus['source'])) . ' (' . htmlspecialchars($smtpStatus['sender_email']) . ')' ?>
            </span>
        <?php else: ?>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">
                <i class="fas fa-exclamation-triangle me-1"></i> Gateway Not Set (Fallback FormSubmit Relay)
            </span>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="cp-table-card mt-0 mb-3" style="border-top: 3px solid #16a34a;">
            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3 gap-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-bolt text-success me-2"></i>Opsi 1 (Rekomendasi): Google Apps Script Email Bridge
                </h6>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Gratis &bull; Tanpa App Password &bull; Bebas 429</span>
            </div>
            <p class="small text-muted mb-2">
                Kirim email HTML resmi Medquest langsung dari akun Google Anda ke penerima mana saja tanpa Gmail App Password, tanpa klik <em>Activate Form</em> per email, dan bebas limit IP FormSubmit (`429`). Kuota resmi Google: 100 email/hari (`@gmail.com`) atau 1.500/hari (Google Workspace).
            </p>

            <div class="p-2 bg-light border small mb-3">
                <div class="fw-bold text-dark mb-1">Cara Setup 1 Menit (1 Kali Seumur Hidup):</div>
                <ol class="mb-2 ps-3">
                    <li>Buka <a href="https://script.google.com" target="_blank" rel="noopener noreferrer" class="fw-bold">script.google.com</a> dan login Google -> klik <strong>New project</strong>.</li>
                    <li>Hapus semua isi kode lalu tempel script berikut, kemudian tekan <strong>Save</strong>:</li>
                </ol>
                <div class="position-relative mb-2">
                    <button type="button" class="btn btn-xs btn-primary position-absolute top-0 end-0 m-1 px-2 py-1 fw-bold" style="font-size:0.7rem;" onclick="copyGasScript(this)">
                        <i class="fas fa-copy me-1"></i> Copy Script
                    </button>
                    <textarea id="gasScriptCode" class="form-control font-monospace bg-white" rows="10" readonly style="font-size:0.74rem;">function doPost(e) {
  try {
    var data = JSON.parse((e && e.postData && e.postData.contents) ? e.postData.contents : '{}');
    if (!data.to || !data.subject) {
      return ContentService.createTextOutput(JSON.stringify({ ok: false, error: 'Missing to or subject' }))
        .setMimeType(ContentService.MimeType.JSON);
    }
    var opts = { htmlBody: data.html || data.text || '' };
    if (data.sender_name) {
      opts.name = data.sender_name;
    }
    MailApp.sendEmail(data.to, data.subject, data.text || 'Medquest Legal Notification', opts);
    return ContentService.createTextOutput(JSON.stringify({ ok: true }))
      .setMimeType(ContentService.MimeType.JSON);
  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({ ok: false, error: String(err) }))
      .setMimeType(ContentService.MimeType.JSON);
  }
}</textarea>
                </div>
                <ol start="3" class="mb-0 ps-3">
                    <li>Klik <strong>Deploy</strong> -> <strong>New deployment</strong> -> pilih type <strong>Web app</strong> -> <em>Execute as:</em> <strong>Me</strong> -> <em>Who has access:</em> <strong>Anyone</strong> -> klik <strong>Deploy</strong> -> izinkan akses (<em>Authorize access</em>; bila muncul peringatan <em>Google hasn't verified this app</em>, klik <strong>Advanced</strong> -> <strong>Go to Untitled project</strong> -> <strong>Allow</strong>).</li>
                    <li>Salin URL Web App (`https://script.google.com/macros/s/.../exec`), tempel di bawah ini, lalu klik <strong>Save Webhook URL</strong>.</li>
                </ol>
            </div>

            <form method="POST" action="<?= base_url('?page=smtp_settings') ?>">
                @csrf
                <input type="hidden" name="action" value="save_apps_script">
                <div class="mb-2">
                    <label class="form-label fw-bold small mb-1">Google Apps Script Web App URL (.../exec)</label>
                    <input type="url" name="apps_script_url" class="form-control form-control-sm font-monospace" placeholder="https://script.google.com/macros/s/AKfycb.../exec" value="<?= htmlspecialchars($smtp['apps_script_url'] ?? '') ?>">
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="small text-muted">
                        <?= !empty($smtp['apps_script_url']) ? 'Webhook tersimpan dan aktif digunakan saat ini.' : 'Belum diisi (masih memakai SMTP jika aktif atau fallback FormSubmit).' ?>
                    </span>
                    <div class="d-flex gap-2">
                        <?php if (!empty($smtp['apps_script_url'])): ?>
                            <button type="submit" name="apps_script_url" value="" class="btn btn-outline-secondary btn-sm">
                                Disable Webhook
                            </button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                            <i class="fas fa-link me-1"></i> Save Webhook URL
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="cp-table-card mt-0">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="fas fa-server text-primary me-2"></i>Opsi 2: System Outgoing SMTP Gateway (Opsional)
            </h6>
            <p class="small text-muted mb-3">
                Gunakan jika perusahaan memakai akun SMTP kantor (`@medquest.co.id`) atau Gmail 16-digit App Password. Jika Google Apps Script sudah diset di atas dan SMTP ini tidak diaktifkan, sistem otomatis memakai Google Apps Script.
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
                        <input type="text" name="smtp_username" class="form-control form-control-sm" placeholder="noreply@medquest.co.id" value="<?= htmlspecialchars($smtp['smtp_username'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">SMTP Password / Gmail App Password</label>
                        <input type="password" name="smtp_password" class="form-control form-control-sm" placeholder="<?= !empty($smtp['smtp_password']) ? '•••••••••••• (leave blank to keep existing)' : 'Email password / 16-char App Password' ?>">
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
                <strong>Urutan Prioritas Pengiriman:</strong>
                <ol class="mb-0 ps-3 mt-1">
                    <li>System SMTP Gateway (jika diaktifkan)</li>
                    <li>Google Apps Script Webhook (jika URL diset)</li>
                    <li>`.env` SMTP / Sendmail</li>
                    <li>Fallback FormSubmit Cloud Relay</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
function copyGasScript(btn) {
    var box = document.getElementById('gasScriptCode');
    if (!box) {
        return;
    }
    var orig = btn.innerHTML;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(box.value).then(function () {
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Copied';
            setTimeout(function () { btn.innerHTML = orig; }, 1800);
        });
    } else {
        box.select();
        document.execCommand('copy');
        btn.innerHTML = '<i class="fas fa-check me-1"></i> Copied';
        setTimeout(function () { btn.innerHTML = orig; }, 1800);
    }
}
</script>
@endsection
