<?php
require_login();
$user  = current_user();
$uId   = (int)$user['id'];
$uCode = strtoupper($user['kode_singkatan']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT a.*, u.nama_lengkap AS creator_name, u.email AS creator_email
    FROM agreements a
    LEFT JOIN users u ON a.created_by_user_id = u.id
    WHERE a.id = ? AND (a.created_by_user_id = ? OR a.created_by_code = ?)
    LIMIT 1
");
$stmt->execute([$id, $uId, $uCode]);
$agr = $stmt->fetch();

if (!$agr) {
    set_flash('error', 'Data dokumen tidak ditemukan.');
    header("Location: " . base_url('?page=agreements'));
    exit;
}

// Handle Upload File Lampiran langsung dari halaman Inspect/Detail jika user mengunggah file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_file') {
    if (!empty($_FILES['file_dokumen']['name']) && $_FILES['file_dokumen']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['file_dokumen']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
        if (in_array($ext, $allowedExt, true)) {
            $fileName = 'doc_' . $id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['file_dokumen']['tmp_name'], $uploadDir . '/' . $fileName)) {
                $pdo->prepare("UPDATE agreements SET file_attachment = ? WHERE id = ?")->execute([$fileName, $id]);
                set_flash('success', 'File dokumen berhasil dilampirkan.');
                header("Location: " . base_url('?page=detail&id=' . $id));
                exit;
            }
        } else {
            set_flash('error', 'Format file harus PDF, JPG, PNG, Word, atau Excel.');
        }
    }
}

$expInfo      = get_expiry_info($agr['expiry_date']);
$effFormatted = format_date_medquest($agr['effective_date'], $agr['effective_date_na']);
$expFormatted = format_date_medquest($agr['expiry_date'], false);
$schedule     = get_deadline_schedule($agr['expiry_date'], $agr);

// Ambil riwayat perubahan / note
$stmtHist = $pdo->prepare("SELECT * FROM agreement_histories WHERE agreement_id = ? ORDER BY id ASC");
$stmtHist->execute([$id]);
$histories = $stmtHist->fetchAll();

$backUrl = base_url('?page=agreements&dept=' . urlencode($agr['department']));
?>

<!-- Header dengan Tombol Back, Edit & Cetak -->
<div class="cp-header-row mb-3 no-print">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $backUrl ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <h1 class="cp-page-title ms-1">Detail Agreement (#<?= htmlspecialchars($agr['no_urut_dokumen'] ?: $agr['id']) ?>)</h1>
    </div>

    <div class="d-flex align-items-center gap-2">
        <button type="button" onclick="window.print()" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
            <i class="fas fa-print me-1"></i> Print
        </button>
        <a href="<?= base_url('?page=edit&id=' . $agr['id']) ?>" class="btn btn-sm btn-primary fw-semibold px-3 py-1">
            <i class="fas fa-edit me-1"></i> Edit Data
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Kolom Kiri: Rincian Lengkap Data Agreement -->
    <div class="col-lg-8">
        <div class="cp-table-card mt-0">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <div>
                    <span class="badge text-white px-2 py-1 me-2" style="background:#0d1f44;">
                        DEPT: <?= htmlspecialchars($agr['department']) ?>
                    </span>
                    <span class="fw-bold text-dark">Doc. No: MJG-FOR-LAC.12-001-03</span>
                </div>
                <span class="expiry-pill <?= $expInfo['badge_class'] ?>">
                    <?= htmlspecialchars($expInfo['short_label']) ?>
                </span>
            </div>

            <table class="table table-sm table-bordered mb-3" style="font-size:0.8rem;">
                <tbody>
                    <tr>
                        <th class="bg-light text-secondary" style="width:32%;padding:8px 12px;">No. Urut Form</th>
                        <td class="fw-bold" style="padding:8px 12px;">#<?= htmlspecialchars($agr['no_urut_dokumen'] ?: $agr['id']) ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light text-secondary" style="padding:8px 12px;">Department / Division</th>
                        <td class="fw-bold text-primary" style="padding:8px 12px;"><?= htmlspecialchars($agr['department']) ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light text-secondary" style="padding:8px 12px;">Document Number</th>
                        <td class="fw-semibold" style="padding:8px 12px;white-space:pre-line;"><?= htmlspecialchars($agr['document_number']) ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light text-secondary" style="padding:8px 12px;">Customer</th>
                        <td class="fw-bold text-dark" style="padding:8px 12px;"><?= htmlspecialchars($agr['customer']) ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light text-secondary" style="padding:8px 12px;">Product (Description)</th>
                        <td style="padding:8px 12px;"><?= htmlspecialchars($agr['product_description']) ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light text-secondary" style="padding:8px 12px;">Product (Serial Number)</th>
                        <td style="padding:8px 12px;"><?= htmlspecialchars($agr['serial_number'] ?: 'N/A') ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light text-secondary" style="padding:8px 12px;">Effective Date</th>
                        <td class="fw-semibold" style="padding:8px 12px;"><?= $effFormatted ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light text-danger" style="padding:8px 12px;">Expiry Date</th>
                        <td class="fw-bold text-danger" style="padding:8px 12px;"><?= $expFormatted ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light text-secondary" style="padding:8px 12px;">Note</th>
                        <td style="padding:8px 12px;white-space:pre-line;"><?= htmlspecialchars($agr['note'] ?: 'N/A') ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light text-secondary" style="padding:8px 12px;">Created By</th>
                        <td class="fw-bold font-monospace" style="padding:8px 12px;white-space:pre-line;"><?= htmlspecialchars($agr['created_by_log']) ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Jadwal Tanggal Deadline H-90, H-60, H-30, H-14, H-7, H-3 -->
            <div class="fw-bold text-dark mb-2" style="font-size:0.78rem;">Jadwal Tanggal Deadline (H-90 s/d H-3)</div>
            <div class="row g-2 text-center">
                <?php foreach ($schedule as $code => $dl):
                    $bgClass = ($dl['state'] === 'today') ? 'bg-warning-subtle border-warning' : (($dl['state'] === 'passed') ? 'bg-light text-muted' : 'bg-primary-subtle border-primary-subtle');
                ?>
                    <div class="col-4 col-md-2">
                        <div class="p-2 rounded border <?= $bgClass ?>">
                            <div class="fw-bold" style="font-size:0.72rem;"><?= $code ?></div>
                            <div class="fw-semibold" style="font-size:0.7rem;"><?= $dl['date_text'] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: File Dokumen & Riwayat Input -->
    <div class="col-lg-4">
        <!-- Kartu Lampiran File Dokumen -->
        <div class="cp-table-card mt-0 mb-3">
            <div class="fw-bold text-dark border-bottom pb-2 mb-2" style="font-size:0.8rem;">
                File Lampiran Dokumen
            </div>

            <?php if (!empty($agr['file_attachment'])):
                $fileUrl = base_url('assets/uploads/' . $agr['file_attachment']);
                $ext = strtolower(pathinfo($agr['file_attachment'], PATHINFO_EXTENSION));
            ?>
                <div class="p-2 rounded bg-light border mb-2 d-flex justify-content-between align-items-center">
                    <span class="small text-truncate me-2"><i class="far fa-file-pdf text-danger me-1"></i><?= htmlspecialchars($agr['file_attachment']) ?></span>
                    <a href="<?= $fileUrl ?>" target="_blank" class="btn btn-sm btn-primary py-0 px-2" style="font-size:0.72rem;">
                        Buka File
                    </a>
                </div>
                <?php if (in_array($ext, ['jpg', 'jpeg', 'png'], true)): ?>
                    <img src="<?= $fileUrl ?>" alt="Lampiran" class="img-fluid rounded border mb-2">
                <?php elseif ($ext === 'pdf'): ?>
                    <iframe src="<?= $fileUrl ?>" style="width:100%;height:220px;border:1px solid #e2e8f0;border-radius:6px;" class="mb-2"></iframe>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-muted small mb-2">Belum ada file scan/dokumen yang diunggah.</div>
            <?php endif; ?>

            <form method="POST" action="<?= base_url('?page=detail&id=' . $id) ?>" enctype="multipart/form-data" class="no-print">
                <input type="hidden" name="action" value="upload_file">
                <div class="input-group input-group-sm">
                    <input type="file" name="file_dokumen" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required>
                    <button type="submit" class="btn btn-outline-primary fw-semibold">Upload</button>
                </div>
                <div class="text-muted mt-1" style="font-size:0.68rem;">Mendukung PDF, JPG, PNG, Word, Excel</div>
            </form>
        </div>

        <!-- Kartu Riwayat Input & Update -->
        <div class="cp-table-card mt-0">
            <div class="fw-bold text-dark border-bottom pb-2 mb-2" style="font-size:0.8rem;">
                Riwayat Log Input (<?= htmlspecialchars($uCode) ?>)
            </div>
            <?php if (empty($histories)): ?>
                <div class="text-muted small">Belum ada riwayat tercatat.</div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($histories as $h): ?>
                        <div class="p-2 rounded bg-light border" style="font-size:0.74rem;">
                            <div class="d-flex justify-content-between fw-bold text-dark">
                                <span><?= htmlspecialchars($h['kode_singkatan']) ?>, <?= format_date_medquest($h['tanggal_input']) ?></span>
                                <span class="badge bg-secondary bg-opacity-25 text-dark"><?= htmlspecialchars($h['aksi']) ?></span>
                            </div>
                            <div class="text-secondary mt-1"><?= nl2br(htmlspecialchars($h['catatan'] ?: 'N/A')) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-3 no-print">
    <a href="<?= $backUrl ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
        <i class="fas fa-arrow-left me-1"></i> Back ke Daftar Dokumen
    </a>
</div>

