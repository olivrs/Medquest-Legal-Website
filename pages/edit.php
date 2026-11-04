<?php
require_login();
$user = current_user();

$id    = (int)($_GET['id'] ?? 0);
$uId   = (int)$user['id'];
$uCode = strtoupper($user['kode_singkatan']);

$stmt = $pdo->prepare("SELECT * FROM agreements WHERE id = ? AND (created_by_user_id = ? OR created_by_code = ?) LIMIT 1");
$stmt->execute([$id, $uId, $uCode]);
$agr = $stmt->fetch();

if (!$agr) {
    set_flash('error', 'Data dokumen tidak ditemukan.');
    header("Location: " . base_url('?page=agreements'));
    exit;
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY id ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department          = strtoupper(trim($_POST['department'] ?? $agr['department']));
    $no_urut_dokumen     = !empty($_POST['no_urut_dokumen']) ? (int)$_POST['no_urut_dokumen'] : $agr['no_urut_dokumen'];
    $document_number     = trim($_POST['document_number'] ?? '');
    $customer            = trim($_POST['customer'] ?? '');
    $product_description = trim($_POST['product_description'] ?? '');
    $serial_number       = trim($_POST['serial_number'] ?? '') ?: 'N/A';
    $effective_date_na   = isset($_POST['effective_date_na']) ? 1 : 0;
    $effective_date      = ($effective_date_na || empty($_POST['effective_date'])) ? null : $_POST['effective_date'];
    if ($effective_date === null) $effective_date_na = 1;

    $expiry_date         = $_POST['expiry_date'] ?? $agr['expiry_date'];
    $note                = trim($_POST['note'] ?? '') ?: 'N/A';
    $created_by_log      = trim($_POST['created_by_log'] ?? $agr['created_by_log']);

    if (isset($_POST['append_my_stamp'])) {
        $myStamp = format_created_by_stamp($user['kode_singkatan'], date('Y-m-d'));
        $lines = array_map('trim', explode("\n", $created_by_log));
        if (!in_array($myStamp, $lines, true)) {
            $created_by_log = trim($created_by_log . "\n" . $myStamp);
        }
    }

    // Jika expiry_date berubah, reset flag notifikasi H-7, H-3, H-1 agar bisa dikirim ulang sesuai tanggal baru
    $resetNotifSql = '';
    if ($expiry_date !== $agr['expiry_date']) {
        $resetNotifSql = ', notif_h7_sent_at = NULL, notif_h3_sent_at = NULL, notif_h1_sent_at = NULL';
    }

    $upd = $pdo->prepare("
        UPDATE agreements
        SET no_urut_dokumen = ?, department = ?, document_number = ?, customer = ?, product_description = ?, serial_number = ?, effective_date = ?, effective_date_na = ?, expiry_date = ?, note = ?, created_by_log = ? {$resetNotifSql}
        WHERE id = ?
    ");
    $upd->execute([
        $no_urut_dokumen,
        $department,
        $document_number,
        $customer,
        $product_description,
        $serial_number,
        $effective_date,
        $effective_date_na,
        $expiry_date,
        $note,
        $created_by_log,
        $id
    ]);

    run_auto_expiry_notifications($pdo);

    set_flash('success', 'Data dokumen berhasil diperbarui.');
    header("Location: " . base_url('?page=agreements&dept=' . urlencode($department)));
    exit;
}
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="cp-header-row mb-3">
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('?page=agreements&dept=' . urlencode($agr['department'])) ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                <h1 class="cp-page-title ms-1">Edit Agreement (#<?= htmlspecialchars($agr['no_urut_dokumen'] ?: $agr['id']) ?>)</h1>
            </div>
            <a href="<?= base_url('?page=detail&id=' . $agr['id']) ?>" class="btn btn-sm btn-outline-primary fw-semibold px-3 py-1">
                <i class="fas fa-eye me-1"></i> Inspect Detail
            </a>
        </div>

        <div class="cp-table-card mt-0">
            <div>
                <form method="POST" action="<?= base_url('?page=edit&id=' . $id) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department / Division</label>
                            <select name="department" class="form-select fw-bold" required>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= htmlspecialchars($d['kode_dept']) ?>" <?= (strtoupper($agr['department']) === strtoupper($d['kode_dept'])) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['kode_dept']) ?> &mdash; <?= htmlspecialchars($d['nama_dept']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Urut Baris Form</label>
                            <input type="number" name="no_urut_dokumen" class="form-control" value="<?= htmlspecialchars($agr['no_urut_dokumen']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Document Number</label>
                            <textarea name="document_number" class="form-control" rows="2" required><?= htmlspecialchars($agr['document_number']) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer</label>
                            <textarea name="customer" class="form-control" rows="2" required><?= htmlspecialchars($agr['customer']) ?></textarea>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Product &mdash; Description</label>
                            <input type="text" name="product_description" class="form-control" value="<?= htmlspecialchars($agr['product_description']) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Product &mdash; Serial Number</label>
                            <input type="text" name="serial_number" class="form-control" value="<?= htmlspecialchars($agr['serial_number']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Effective Date</label>
                            <div class="input-group">
                                <input type="date" name="effective_date" id="editEffDate" class="form-control" value="<?= htmlspecialchars($agr['effective_date'] ?? '') ?>" <?= $agr['effective_date_na'] ? 'disabled' : '' ?>>
                                <div class="input-group-text">
                                    <input class="form-check-input mt-0 me-1" type="checkbox" name="effective_date_na" id="editEffNa" <?= $agr['effective_date_na'] ? 'checked' : '' ?> onchange="document.getElementById('editEffDate').disabled = this.checked;">
                                    <label class="small mb-0" for="editEffNa">Set N/A</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-danger">Expiry Date (Target Notifikasi H-7, H-3, H-1)</label>
                            <input type="date" name="expiry_date" class="form-control border-danger" value="<?= htmlspecialchars($agr['expiry_date']) ?>" required>
                        </div>

                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Note (Riwayat Catatan)</label>
                            <textarea name="note" class="form-control" rows="4"><?= htmlspecialchars($agr['note']) ?></textarea>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Kolom Created By (Riwayat Singkatan &amp; Tgl)</label>
                            <textarea name="created_by_log" class="form-control font-monospace fw-bold" rows="4"><?= htmlspecialchars($agr['created_by_log']) ?></textarea>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="append_my_stamp" id="appendStampCheck" checked>
                                <label class="form-check-label small" for="appendStampCheck">
                                    Tambahkan tanda hari ini: <strong><?= format_created_by_stamp($user['kode_singkatan']) ?></strong>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('?page=agreements&dept=' . urlencode($agr['department'])) ?>" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

