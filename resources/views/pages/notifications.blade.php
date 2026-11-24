@extends('layouts.app')

@section('content')
<div class="cp-header-row mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('?page=agreements') ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <h1 class="cp-page-title ms-1">Deadline Monitoring</h1>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <?php if (!empty($smtpStatus['ready'])): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-2" style="font-size:0.74rem;">
                <i class="fas fa-check-circle me-1"></i> Direct SMTP Active (<?= htmlspecialchars($smtpStatus['sender_email']) ?>)
            </span>
        <?php else: ?>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-2" style="font-size:0.74rem;">
                <i class="fas fa-exclamation-triangle me-1"></i> FormSubmit Relay Fallback
            </span>
        <?php endif; ?>

        <?php if (is_superadmin()): ?>
            <form method="POST" action="<?= base_url('?page=notifications') ?>" class="m-0 d-inline">
                @csrf
                <input type="hidden" name="action" value="run_now">
                <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3 py-1">
                    <i class="fas fa-paper-plane me-1"></i> Send Due Reminders Now
                </button>
            </form>
            <a href="<?= base_url('?page=smtp_settings') ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
                <i class="fas fa-cog me-1"></i> System SMTP Gateway
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="cp-table-card mt-0 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div>
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-envelope-open-text text-primary me-1"></i> Auto Reminder Email Recipients (H-90, H-60, H-30, H-14, H-7, H-3, Hari H)
            </h6>
            <div class="small text-muted">
                Add an email once below to immediately receive deadline alerts for active documents in the selected department.
            </div>
        </div>
        <span class="badge bg-light text-dark border px-3 py-1"><?= count($recipients ?? []) ?> Recipient(s)</span>
    </div>

    <form method="POST" action="<?= base_url('?page=notifications') ?>" class="row g-2 align-items-end p-2 bg-light border mb-3 mt-2">
        @csrf
        <input type="hidden" name="action" value="add_recipient">
        <div class="col-md-4 col-sm-6">
            <label class="form-label fw-bold small mb-1">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control form-control-sm" placeholder="pic@medquest.co.id / email@gmail.com" required>
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label fw-bold small mb-1">Recipient Name / Label</label>
            <input type="text" name="label" class="form-control form-control-sm" placeholder="Legal Team / PIC Name">
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label fw-bold small mb-1">Department Scope</label>
            <select name="department" class="form-select form-select-sm">
                <?php if (is_superadmin() || in_array('ALL', get_user_allowed_departments($user), true)): ?>
                    <option value="ALL">ALL DEPARTMENTS</option>
                <?php endif; ?>
                <?php foreach (($recipientDepts ?? []) as $rd): ?>
                    <option value="<?= htmlspecialchars(strtoupper($rd['kode_dept'])) ?>">
                        <?= htmlspecialchars(strtoupper($rd['kode_dept'])) ?> &mdash; <?= htmlspecialchars($rd['nama_dept']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-sm-6">
            <button type="submit" class="btn btn-sm btn-primary fw-bold w-100">
                <i class="fas fa-plus me-1"></i> Add Email
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="cp-table">
            <thead>
                <tr>
                    <th>Email Address</th>
                    <th>Label / PIC</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recipients)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-3 text-muted small">No additional notification recipients registered yet (only document creators receive emails).</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recipients as $rec): ?>
                        <tr>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($rec['email']) ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($rec['label'] ?: '-') ?></td>
                            <td><span class="badge mq-dept-badge px-2 py-1"><?= htmlspecialchars($rec['department']) ?></span></td>
                            <td>
                                <?php if (!empty($rec['is_active'])): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border">Paused</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="<?= base_url('?page=notifications') ?>" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="toggle_recipient">
                                    <input type="hidden" name="recipient_id" value="<?= (int) $rec['id'] ?>">
                                    <button type="submit" class="btn btn-xs btn-light border px-2 py-1" style="font-size:0.72rem;">
                                        <?= !empty($rec['is_active']) ? 'Pause' : 'Activate' ?>
                                    </button>
                                </form>
                                <form method="POST" action="<?= base_url('?page=notifications') ?>" class="d-inline" onsubmit="return confirm('Remove email <?= htmlspecialchars($rec['email']) ?> from reminder recipients?')">
                                    @csrf
                                    <input type="hidden" name="action" value="delete_recipient">
                                    <input type="hidden" name="recipient_id" value="<?= (int) $rec['id'] ?>">
                                    <button type="submit" class="btn btn-xs btn-outline-danger px-2 py-1" style="font-size:0.72rem;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="cp-table-card mt-0 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0">Documents Approaching Deadline</h6>
        <span class="badge bg-light text-dark border px-3 py-1"><?= count($upcomingH) ?> Document(s)</span>
    </div>
    <div class="table-responsive">
        <table class="cp-table mq-mobile-cards">
            <thead>
                <tr>
                    <th style="width: 48px;" class="text-center">Open</th>
                    <th>Dept</th>
                    <th>Customer &amp; Product</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($upcomingH)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No documents approaching deadline.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($upcomingH as $item):
                        $expInfo = get_expiry_info($item['expiry_date']);
                        $itemProses = strtoupper(trim((string) ($item['status_proses'] ?? 'PENGAJUAN')));
                        $itemRowClass = $itemProses === 'SELESAI' ? 'row-completed' : $expInfo['row_class'];
                        $displayTitle = !empty($item['customer']) ? $item['customer'] : ($item['document_title'] ?: 'Document #' . $item['id']);
                        $displaySub   = !empty($item['product_description']) ? $item['product_description'] : ($item['issued_by'] ?: '-');
                    ?>
                        <tr class="<?= $itemRowClass ?>">
                            <td class="text-center mc-open" style="vertical-align: middle;">
                                <a href="<?= base_url('?page=detail&id=' . $item['id']) ?>"
                                   class="btn btn-sm btn-primary py-1 px-2 text-white d-inline-flex align-items-center justify-content-center"
                                   style="width: 32px; height: 32px; font-size: 0.85rem;"
                                   title="Open Document <?= htmlspecialchars($displayTitle) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </td>
                            <td class="mc-dept" style="vertical-align: middle;">
                                <span class="badge mq-dept-badge px-2 py-1"><?= htmlspecialchars($item['department']) ?></span>
                            </td>
                            <td class="mc-title" style="vertical-align: middle;">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($displayTitle) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($displaySub) ?></div>
                            </td>
                            <td class="fw-bold text-danger" data-label="Expiry Date" style="vertical-align: middle;">
                                <?= format_date_medquest($item['expiry_date']) ?>
                            </td>
                            <td data-label="Status" style="vertical-align: middle;">
                                <span class="expiry-pill <?= $expInfo['badge_class'] ?>">
                                    <?= htmlspecialchars($expInfo['short_label']) ?>
                                </span>
                            </td>
                            <td data-label="Created By" style="vertical-align: middle;">
                                <div class="fw-bold font-monospace small"><?= htmlspecialchars($item['created_by_code']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($item['user_email'] ?? '-') ?></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="cp-table-card mt-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0">Email Notification History</h6>
        <?php if (!empty($emailLogs)): ?>
            <form method="POST" action="<?= base_url('?page=notifications') ?>" onsubmit="return confirm('Clear all email log history?')">
                @csrf
                <input type="hidden" name="action" value="clear_logs">
                <button type="submit" class="btn btn-outline-danger btn-sm">Clear History</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="cp-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Stage</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th class="text-end">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($emailLogs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No email notifications sent yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($emailLogs as $log): ?>
                        <tr>
                            <td class="small text-muted"><?= date('d M Y, H:i', strtotime($log['sent_at'])) ?></td>
                            <td>
                                <?php if ($log['tipe_notif'] === 'H-0'): ?>
                                    <span class="badge bg-danger text-white fw-bold px-2 py-1">HARI H (H-0)</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border fw-bold px-2 py-1"><?= htmlspecialchars($log['tipe_notif']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold font-monospace me-1"><?= htmlspecialchars($log['recipient_code']) ?></span>
                                <span class="text-muted small">(<?= htmlspecialchars($log['recipient_email']) ?>)</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($log['subject']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <?= htmlspecialchars($log['status_kirim']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button"
                                        class="btn btn-sm btn-light border fw-semibold"
                                        onclick="showEmailPreview(<?= (int)$log['id'] ?>)">
                                    View
                                </button>
                                <template id="emailTpl_<?= (int)$log['id'] ?>"><?= $log['body_html'] ?></template>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalEmailPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-0 overflow-hidden border-0 shadow">
            <div class="modal-header mq-modal-header text-white py-2">
                <h6 class="modal-title fw-bold">Email Details</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="emailPreviewFrame" style="width:100%;height:560px;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function showEmailPreview(logId) {
    const tpl = document.getElementById('emailTpl_' + logId);
    if (!tpl) return;
    const frame = document.getElementById('emailPreviewFrame');
    const modal = new bootstrap.Modal(document.getElementById('modalEmailPreview'));
    modal.show();
    setTimeout(() => {
        const doc = frame.contentDocument || frame.contentWindow.document;
        doc.open();
        doc.write(tpl.innerHTML);
        doc.close();
    }, 100);
}
</script>
@endsection
