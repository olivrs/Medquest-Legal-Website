@extends('layouts.app')

@section('content')
<div class="cp-header-row mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('?page=agreements') ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <h1 class="cp-page-title ms-1">Monitoring Deadline</h1>
    </div>
</div>

<div class="cp-table-card mt-0 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0">Dokumen Mendekati Deadline</h6>
        <span class="badge bg-light text-dark border px-3 py-1"><?= count($upcomingH) ?> Dokumen</span>
    </div>
    <div class="table-responsive">
        <table class="cp-table">
            <thead>
                <tr>
                    <th style="width: 48px;" class="text-center">Buka</th>
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
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada dokumen yang mendekati deadline.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($upcomingH as $item):
                        $expInfo = get_expiry_info($item['expiry_date']);
                        $displayTitle = !empty($item['customer']) ? $item['customer'] : ($item['document_title'] ?: 'Dokumen #' . $item['id']);
                        $displaySub   = !empty($item['product_description']) ? $item['product_description'] : ($item['issued_by'] ?: '-');
                    ?>
                        <tr class="<?= $expInfo['row_class'] ?>">
                            <td class="text-center" style="vertical-align: middle;">
                                <a href="<?= base_url('?page=detail&id=' . $item['id']) ?>"
                                   class="btn btn-sm btn-primary py-1 px-2 text-white d-inline-flex align-items-center justify-content-center"
                                   style="width: 32px; height: 32px; font-size: 0.85rem;"
                                   title="Buka Dokumen <?= htmlspecialchars($displayTitle) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </td>
                            <td style="vertical-align: middle;">
                                <span class="badge mq-dept-badge px-2 py-1"><?= htmlspecialchars($item['department']) ?></span>
                            </td>
                            <td style="vertical-align: middle;">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($displayTitle) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($displaySub) ?></div>
                            </td>
                            <td class="fw-bold text-danger" style="vertical-align: middle;">
                                <?= format_date_medquest($item['expiry_date']) ?>
                            </td>
                            <td style="vertical-align: middle;">
                                <span class="expiry-pill <?= $expInfo['badge_class'] ?>">
                                    <?= htmlspecialchars($expInfo['short_label']) ?>
                                </span>
                            </td>
                            <td style="vertical-align: middle;">
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
        <h6 class="fw-bold text-dark mb-0">Riwayat Pengiriman Email</h6>
        <?php if (!empty($emailLogs)): ?>
            <form method="POST" action="<?= base_url('?page=notifications') ?>" onsubmit="return confirm('Bersihkan seluruh riwayat log email?')">
                @csrf
                <input type="hidden" name="action" value="clear_logs">
                <button type="submit" class="btn btn-outline-danger btn-sm">Bersihkan Riwayat</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="cp-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Tahap</th>
                    <th>Penerima</th>
                    <th>Subjek</th>
                    <th>Status</th>
                    <th class="text-end">Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($emailLogs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat email terkirim.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($emailLogs as $log): ?>
                        <tr>
                            <td class="small text-muted"><?= date('d M Y, H:i', strtotime($log['sent_at'])) ?></td>
                            <td>
                                <span class="badge bg-light text-dark border fw-bold px-2 py-1"><?= htmlspecialchars($log['tipe_notif']) ?></span>
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
                                    Lihat
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
                <h6 class="modal-title fw-bold">Detail Email</h6>
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
