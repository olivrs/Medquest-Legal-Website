<?php
require_login();
$user = current_user();

$departments = $pdo->query("SELECT * FROM departments ORDER BY id ASC")->fetchAll();
$maxNoUrut = (int)$pdo->query("SELECT COALESCE(MAX(no_urut_dokumen), 730) FROM agreements")->fetchColumn();
$nextNoUrut = $maxNoUrut + 1;

$preselectedDept = strtoupper(trim($_GET['dept'] ?? ($user['departemen_default'] !== 'ALL' ? $user['departemen_default'] : 'MHO')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department          = strtoupper(trim($_POST['department'] ?? 'MHO'));
    $custom_dept         = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim($_POST['custom_dept'] ?? '')));
    if ($department === 'OTHER' && $custom_dept !== '') {
        $department = $custom_dept;
        $pdo->prepare("INSERT IGNORE INTO departments (kode_dept, nama_dept) VALUES (?, ?)")->execute([$department, 'Divisi ' . $department]);
    }

    $no_urut_dokumen     = !empty($_POST['no_urut_dokumen']) ? (int)$_POST['no_urut_dokumen'] : $nextNoUrut;
    $document_number     = trim($_POST['document_number'] ?? '');
    $customer            = trim($_POST['customer'] ?? '');
    $product_description = trim($_POST['product_description'] ?? '');
    $serial_number       = trim($_POST['serial_number'] ?? '');
    if ($serial_number === '') $serial_number = 'N/A';

    $effective_date_na   = isset($_POST['effective_date_na']) ? 1 : 0;
    $effective_date      = ($effective_date_na || empty($_POST['effective_date'])) ? null : $_POST['effective_date'];
    if ($effective_date === null) $effective_date_na = 1;

    $expiry_date         = $_POST['expiry_date'] ?? '';
    $tanggal_input       = !empty($_POST['tanggal_input']) ? $_POST['tanggal_input'] : date('Y-m-d');
    $raw_note            = trim($_POST['note'] ?? '');
    $prefix_date_note    = isset($_POST['prefix_date_note']);

    if ($document_number === '' || $customer === '' || $product_description === '' || $expiry_date === '') {
        set_flash('error', 'Harap lengkapi Document Number, Customer, Product Description, dan Expiry Date.');
    } else {
        if ($raw_note === '' || strtoupper($raw_note) === 'N/A') {
            $finalNote = 'N/A';
        } elseif ($prefix_date_note) {
            $finalNote = format_date_medquest($tanggal_input, false) . " :\n" . $raw_note;
        } else {
            $finalNote = $raw_note;
        }

        $createdByStamp = format_created_by_stamp($user['kode_singkatan'], $tanggal_input);

        $ins = $pdo->prepare("
            INSERT INTO agreements
            (no_urut_dokumen, department, document_number, customer, product_description, serial_number, effective_date, effective_date_na, expiry_date, note, created_by_user_id, created_by_code, created_by_log)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $no_urut_dokumen,
            $department,
            $document_number,
            $customer,
            $product_description,
            $serial_number,
            $effective_date,
            $effective_date_na,
            $expiry_date,
            $finalNote,
            $user['id'],
            $user['kode_singkatan'],
            $createdByStamp
        ]);
        $newAgrId = (int)$pdo->lastInsertId();

        // Simpan file lampiran jika ada yang diupload saat input
        if (!empty($_FILES['file_dokumen']['name']) && $_FILES['file_dokumen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['file_dokumen']['name'], PATHINFO_EXTENSION));
            $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
            if (in_array($ext, $allowedExt, true)) {
                $fileName = 'doc_' . $newAgrId . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['file_dokumen']['tmp_name'], $uploadDir . '/' . $fileName)) {
                    $pdo->prepare("UPDATE agreements SET file_attachment = ? WHERE id = ?")->execute([$fileName, $newAgrId]);
                }
            }
        }

        $insHist = $pdo->prepare("
            INSERT INTO agreement_histories (agreement_id, user_id, kode_singkatan, tanggal_input, catatan, aksi)
            VALUES (?, ?, ?, ?, ?, 'CREATE')
        ");
        $insHist->execute([$newAgrId, $user['id'], $user['kode_singkatan'], $tanggal_input, $raw_note ?: 'N/A']);

        // Cek otomatis H-90, H-60, H-30, H-14, H-7, H-3
        $autoEmails = run_auto_expiry_notifications($pdo);
        $extraMsg = '';
        if (!empty($autoEmails)) {
            $extraMsg = " Karena tanggal berada di titik pengingat (H-90/60/30/14/7/3), sistem langsung memproses pengiriman email ke <strong>" . htmlspecialchars($user['email']) . "</strong>!";
        }

        set_flash('success', "Dokumen berhasil disimpan ke departemen <strong>{$department}</strong> dengan tanda Created By: <strong>{$createdByStamp}</strong>.{$extraMsg}");
        header("Location: " . base_url('?page=agreements&dept=' . urlencode($department)));
        exit;
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="cp-header-row mb-3">
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('?page=agreements') ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                <h1 class="cp-page-title ms-1">Input Agreement</h1>
            </div>
        </div>

        <div class="cp-table-card mt-0">
            <div>

                <form method="POST" action="<?= base_url('?page=create') ?>" enctype="multipart/form-data">
                    <div class="row g-2">
                        <!-- 1. Department / Division -->
                        <div class="col-md-5">
                            <label class="form-label fw-bold">1. Pilih Departemen / Divisi <span class="text-danger">*</span></label>
                            <select name="department" id="selectDept" class="form-select fw-semibold" required onchange="toggleCustomDept(this.value)">
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= htmlspecialchars($d['kode_dept']) ?>" <?= ($preselectedDept === strtoupper($d['kode_dept'])) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['kode_dept']) ?> &mdash; <?= htmlspecialchars($d['nama_dept']) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="OTHER">+ Tambah Departemen Lainnya...</option>
                            </select>
                            <input type="text" name="custom_dept" id="customDeptInput" class="form-control mt-1 text-uppercase d-none" placeholder="Ketik singkatan departemen baru (contoh: FIN)">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">No. Urut Dokumen</label>
                            <input type="number" name="no_urut_dokumen" class="form-control" value="<?= $nextNoUrut ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tanggal Input Data <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_input" id="inputTglCreate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- 2. Document Number & 3. Customer -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">2. Nomor Dokumen (Document Number) <span class="text-danger">*</span></label>
                            <textarea name="document_number" class="form-control" rows="2" placeholder="Contoh: 087/KKO.PL/MBB/MJG/XII/2025" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">3. Nama Customer / Rumah Sakit / Instansi <span class="text-danger">*</span></label>
                            <textarea name="customer" class="form-control" rows="2" placeholder="Contoh: Palang Merah Indonesia Provinsi DKI Jakarta" required></textarea>
                        </div>

                        <!-- 4. Product (Description & Serial Number) -->
                        <div class="col-md-8">
                            <label class="form-label fw-bold">4a. Nama Produk / Deskripsi Kerjasama (Product Description) <span class="text-danger">*</span></label>
                            <input type="text" name="product_description" class="form-control" placeholder="Contoh: Fibroscan 530 Compact / Hemocue Hb 301 / NDA" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">4b. Serial Number (S/N)</label>
                            <input type="text" name="serial_number" class="form-control" value="N/A" placeholder="Ketik S/N atau biarkan N/A">
                        </div>

                        <!-- 5. Effective Date & 6. Expiry Date -->
                        <div class="col-md-5">
                            <label class="form-label fw-bold">5. Tanggal Mulai Berlaku (Effective Date)</label>
                            <div class="input-group">
                                <input type="date" name="effective_date" id="effDateInput" class="form-control" value="<?= date('Y-m-d') ?>">
                                <div class="input-group-text bg-light">
                                    <input class="form-check-input mt-0 me-1" type="checkbox" name="effective_date_na" id="effDateNaCheck" onchange="toggleEffNa(this.checked)">
                                    <label class="small mb-0 fw-semibold" for="effDateNaCheck">N/A</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <label class="form-label fw-bold text-danger">6. Tanggal Berakhir Kontrak (Expiry Date) <span class="text-danger">*</span></label>
                            <input type="date" name="expiry_date" id="expDateInput" class="form-control border-danger fw-bold" required onchange="updateDeadlinePreview()">
                            <div class="mt-2 d-flex flex-wrap gap-1 align-items-center">
                                <span class="small text-muted me-1">Pilih Cepat Tanggal:</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem;" onclick="setQuickExpiry(90)">H-90</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem;" onclick="setQuickExpiry(60)">H-60</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem;" onclick="setQuickExpiry(30)">H-30</button>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark py-0 px-2" style="font-size:0.75rem;" onclick="setQuickExpiry(14)">H-14</button>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark py-0 px-2" style="font-size:0.75rem;" onclick="setQuickExpiry(7)">H-7</button>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.75rem;" onclick="setQuickExpiry(3)">H-3</button>
                            </div>
                        </div>

                        <!-- Preview Kalkulator Tanggal Deadline H-90, H-60, H-30, H-14, H-7, H-3 -->
                        <div class="col-12" id="deadlinePreviewWrapper" style="display:none;">
                            <div class="p-3 rounded-4 bg-light border">
                                <div class="fw-bold small text-dark mb-2">
                                    <i class="fas fa-calendar-alt text-primary me-1"></i> Jadwal Tanggal Deadline Otomatis untuk Kontrak Ini:
                                </div>
                                <div class="row g-2 text-center" id="deadlinePreviewBoxes"></div>
                            </div>
                        </div>

                        <!-- 7. Note -->
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0">7. Catatan (Note)</label>
                                <div class="form-check form-switch small">
                                    <input class="form-check-input" type="checkbox" name="prefix_date_note" id="prefixDateCheck" checked>
                                    <label class="form-check-label" for="prefixDateCheck">Otomatis beri tanggal di awal catatan</label>
                                </div>
                            </div>
                            <textarea name="note" class="form-control" rows="2" placeholder="Kosongkan jika N/A, atau ketik catatan tindak lanjut..."></textarea>
                        </div>

                        <!-- 8. Upload File Lampiran (Opsional) -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">8. Upload File Dokumen (Opsional)</label>
                            <input type="file" name="file_dokumen" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                            <div class="text-muted mt-1" style="font-size:0.68rem;">PDF, JPG, PNG, Word, atau Excel</div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('?page=agreements') ?>" class="btn btn-light border fw-semibold px-4 py-2">Batal</a>
                        <button type="submit" class="btn btn-primary fw-bold px-5 py-2">
                            <i class="fas fa-save me-1"></i> Simpan Dokumen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const MONTHS_SHORT = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];

function formatMedquestDateJS(dateObj) {
    const dd = String(dateObj.getDate()).padStart(2, '0');
    const mmm = MONTHS_SHORT[dateObj.getMonth()];
    const yyyy = dateObj.getFullYear();
    return `${dd} ${mmm} ${yyyy}`;
}

function toggleCustomDept(val) {
    const el = document.getElementById('customDeptInput');
    if (val === 'OTHER') {
        el.classList.remove('d-none');
        el.required = true;
    } else {
        el.classList.add('d-none');
        el.required = false;
    }
}

function toggleEffNa(checked) {
    const inp = document.getElementById('effDateInput');
    inp.disabled = checked;
    if (checked) inp.value = '';
}

function setQuickExpiry(days) {
    const d = new Date();
    d.setDate(d.getDate() + days);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    document.getElementById('expDateInput').value = `${yyyy}-${mm}-${dd}`;
    updateDeadlinePreview();
}

function updateDeadlinePreview() {
    const val = document.getElementById('expDateInput').value;
    const wrap = document.getElementById('deadlinePreviewWrapper');
    const boxes = document.getElementById('deadlinePreviewBoxes');
    if (!val) {
        wrap.style.display = 'none';
        return;
    }

    const milestones = [90, 60, 30, 14, 7, 3];
    let html = '';
    milestones.forEach(m => {
        const expDate = new Date(val + 'T00:00:00');
        expDate.setDate(expDate.getDate() - m);
        html += `
            <div class="col-4 col-md-2">
                <div class="p-2 rounded-3 bg-white border shadow-sm">
                    <div class="fw-bold text-primary small">H-${m}</div>
                    <div class="fw-bold text-dark" style="font-size:0.78rem;">${formatMedquestDateJS(expDate)}</div>
                </div>
            </div>
        `;
    });
    boxes.innerHTML = html;
    wrap.style.display = 'block';
}

document.getElementById('inputTglCreate')?.addEventListener('change', function() {
    if (!this.value) return;
    const parts = this.value.split('-');
    if (parts.length === 3) {
        const formatted = `${parts[2]} ${MONTHS_SHORT[parseInt(parts[1], 10) - 1]} ${parts[0]}`;
        document.getElementById('liveStampBadge').textContent = `<?= htmlspecialchars($user['kode_singkatan']) ?>, ${formatted}`;
    }
});
</script>
