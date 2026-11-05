<?php
require_login();
$user = current_user();

run_auto_expiry_notifications($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'clear_logs') {
        $pdo->exec("DELETE FROM email_notifications");
        set_flash('success', 'Riwayat log email berhasil dibersihkan.');
        header("Location: " . base_url('?page=notifications'));
        exit;
    }
}

$uId   = (int)$user['id'];
$uCode = strtoupper($user['kode_singkatan']);

$stmtUpcoming = $pdo->prepare("
    SELECT a.*, u.nama_lengkap, u.email AS user_email, DATEDIFF(a.expiry_date, CURDATE()) AS sisa_hari
    FROM agreements a
    LEFT JOIN users u ON a.created_by_user_id = u.id
    WHERE (a.created_by_user_id = ? OR a.created_by_code = ?)
      AND DATEDIFF(a.expiry_date, CURDATE()) BETWEEN 0 AND 90
    ORDER BY sisa_hari ASC
");
$stmtUpcoming->execute([$uId, $uCode]);
$upcomingH = $stmtUpcoming->fetchAll();

$stmtLogs = $pdo->prepare("
    SELECT e.*, a.department, a.customer, a.document_number, a.expiry_date
    FROM email_notifications e
    LEFT JOIN agreements a ON e.agreement_id = a.id
    WHERE (e.user_id = ? OR e.recipient_code = ?)
    ORDER BY e.id DESC
    LIMIT 100
");
$stmtLogs->execute([$uId, $uCode]);
$emailLogs = $stmtLogs->fetchAll();
?>

<div class="cp-header-row mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('?page=agreements') ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <h1 class="cp-page-title ms-1">Monitoring Deadline</h1>
    </div>
</div>

<!-- Daftar Dokumen dalam Rentang Deadline 90 Hari -->
<div class="cp-table-card mt-0 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0">Dokumen Mendekati Deadline</h6>
        <span class="badge bg-light text-dark border px-3 py-1"><?= count($upcomingH) ?> Dokumen</span>
    </div>
    <div class="table-responsive">
        <table class="cp-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Dept</th>
                    <th>Customer &amp; Product</th>
                    <th>Expiry Date</th>
                    <th>Jadwal Deadline</th>
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
                        $schedule = get_deadline_schedule($item['expiry_date'], $item);
                    ?>
                        <tr class="<?= $expInfo['row_class'] ?>">
                            <td>
                                <span class="expiry-pill <?= $expInfo['badge_class'] ?>">
                                    <?= htmlspecialchars($expInfo['short_label']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge mq-dept-badge px-2 py-1"><?= htmlspecialchars($item['department']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($item['customer']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($item['product_description']) ?></div>
                            </td>
                            <td class="fw-bold text-danger"><?= format_date_medquest($item['expiry_date']) ?></td>
                            <td style="min-width:300px;">
                                <div class="dl-strip">
                                    <?php foreach ($schedule as $code => $dl):
                                        $pClass = ($dl['state'] === 'today') ? 'dl-today' : (($dl['state'] === 'passed') ? 'dl-passed' : 'dl-upcoming');
                                    ?>
                                        <span class="dl-pill-item <?= $pClass ?>">
                                            <strong><?= $code ?>:</strong> <?= $dl['date_text'] ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
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

<!-- Riwayat Output Email yang Sudah Dikirim -->
<div class="cp-table-card mt-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0">Riwayat Pengiriman Email</h6>
        <?php if (!empty($emailLogs)): ?>
            <form method="POST" action="<?= base_url('?page=notifications') ?>" onsubmit="return confirm('Bersihkan seluruh riwayat log email?')">
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

<!-- Modal Preview Isi Email HTML -->
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
