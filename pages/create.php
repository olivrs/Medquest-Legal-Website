<?php
require_login();
$user = current_user();

$departments = $pdo->query("SELECT * FROM departments ORDER BY id ASC")->fetchAll();

// Hitung saran nomor urut berikutnya untuk masing-masing jenis dokumen
$maxCoop = (int)$pdo->query("SELECT COALESCE(MAX(no_urut_dokumen), 730) FROM agreements WHERE doc_type = 'COOPERATION' OR doc_type IS NULL")->fetchColumn();
$maxComp = (int)$pdo->query("SELECT COALESCE(MAX(no_urut_dokumen), 184) FROM agreements WHERE doc_type = 'COMPANY'")->fetchColumn();
$maxPrin = (int)$pdo->query("SELECT COALESCE(MAX(no_urut_dokumen), 0) FROM agreements WHERE doc_type = 'PRINCIPAL'")->fetchColumn();

$nextNums = [
    'COOPERATION' => $maxCoop + 1,
    'COMPANY'     => $maxComp + 1,
    'PRINCIPAL'   => $maxPrin + 1,
];

$selectedDocType = strtoupper(trim($_GET['doc_type'] ?? ($_POST['doc_type'] ?? 'COOPERATION')));
if (!in_array($selectedDocType, ['COOPERATION', 'COMPANY', 'PRINCIPAL'], true)) {
    $selectedDocType = 'COOPERATION';
}

$defaultDept = ($selectedDocType === 'COOPERATION') ? 'MHO' : 'LAC';
$preselectedDept = strtoupper(trim($_GET['dept'] ?? ($user['departemen_default'] !== 'ALL' ? $user['departemen_default'] : $defaultDept)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_type    = strtoupper(trim($_POST['doc_type'] ?? 'COOPERATION'));
    if (!in_array($doc_type, ['COOPERATION', 'COMPANY', 'PRINCIPAL'], true)) {
        $doc_type = 'COOPERATION';
    }

    $department  = strtoupper(trim($_POST['department'] ?? 'LAC'));
    $custom_dept = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim($_POST['custom_dept'] ?? '')));
    if ($department === 'OTHER' && $custom_dept !== '' && is_superadmin()) {
        $department = $custom_dept;
        $pdo->prepare("INSERT IGNORE INTO departments (kode_dept, nama_dept) VALUES (?, ?)")->execute([$department, 'Divisi ' . $department]);
    }

    $no_urut_dokumen     = !empty($_POST['no_urut_dokumen']) ? (int)$_POST['no_urut_dokumen'] : ($nextNums[$doc_type] ?? 1);
    $document_number     = trim($_POST['document_number'] ?? '');
    if ($document_number === '') $document_number = 'N/A';

    // Field dinamis sesuai jenis dokumen
    $document_title      = trim($_POST['document_title'] ?? '');
    $issued_by           = trim($_POST['issued_by'] ?? '');
    $principal_name      = trim($_POST['principal_name'] ?? '');
    $customer            = trim($_POST['customer'] ?? '');
    $product_description = trim($_POST['product_description'] ?? '');
    $serial_number       = trim($_POST['serial_number'] ?? '');
    if ($serial_number === '') $serial_number = 'N/A';

    // Sinkronisasi kolom pencarian/tampilan untuk COMPANY & PRINCIPAL
    if ($doc_type === 'COMPANY') {
        $customer            = $issued_by !== '' ? $issued_by : 'Original Company Document';
        $product_description = $document_title;
    } elseif ($doc_type === 'PRINCIPAL') {
        $customer            = $principal_name !== '' ? $principal_name : $issued_by;
        $product_description = $document_title;
    }

    $effective_date_na   = isset($_POST['effective_date_na']) ? 1 : 0;
    $effective_date      = ($effective_date_na || empty($_POST['effective_date'])) ? null : $_POST['effective_date'];
    if ($effective_date === null) $effective_date_na = 1;

    $expiry_date_na      = isset($_POST['expiry_date_na']) ? 1 : 0;
    $expiry_date         = ($expiry_date_na || empty($_POST['expiry_date'])) ? null : $_POST['expiry_date'];
    if ($expiry_date === null) $expiry_date_na = 1;

    $tanggal_input       = !empty($_POST['tanggal_input']) ? $_POST['tanggal_input'] : date('Y-m-d');
    $raw_note            = trim($_POST['note'] ?? '');
    $prefix_date_note    = isset($_POST['prefix_date_note']);

    // Validasi sesuai jenis dokumen yang dipilih
    $errorMsg = '';
    if ($doc_type === 'COOPERATION') {
        if ($document_number === '' || $customer === '' || $product_description === '' || (!$expiry_date_na && !$expiry_date)) {
            $errorMsg = 'Untuk Log of Cooperation Agreement, harap lengkapi Document Number, Customer, Product Description, dan Expiry Date.';
        }
    } elseif ($doc_type === 'COMPANY') {
        if ($document_title === '' || $issued_by === '') {
            $errorMsg = 'Untuk Original Company Document, harap lengkapi Document Title dan Issued By.';
        }
    } elseif ($doc_type === 'PRINCIPAL') {
        if ($document_title === '' || $issued_by === '' || $principal_name === '') {
            $errorMsg = 'Untuk Log of Principal Document, harap lengkapi Document Title, Issued By, dan Principal.';
        }
    }

    if ($errorMsg !== '') {
        set_flash('error', $errorMsg);
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
            (doc_type, no_urut_dokumen, department, document_title, document_number, issued_by, principal_name, customer, product_description, serial_number, effective_date, effective_date_na, expiry_date, expiry_date_na, note, created_by_user_id, created_by_code, created_by_log)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $doc_type,
            $no_urut_dokumen,
            $department,
            $document_title ?: null,
            $document_number,
            $issued_by ?: null,
            $principal_name ?: null,
            $customer,
            $product_description,
            $serial_number,
            $effective_date,
            $effective_date_na,
            $expiry_date,
            $expiry_date_na,
            $finalNote,
            $user['id'],
            $user['kode_singkatan'],
            $createdByStamp
        ]);
        $newAgrId = (int)$pdo->lastInsertId();

        // Simpan file lampiran jika ada yang diupload
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

        // Cek otomatis H-90, H-60, H-30, H-14, H-7, H-3 jika memiliki Expiry Date
        $extraMsg = '';
        if (!$expiry_date_na && $expiry_date) {
            $autoEmails = run_auto_expiry_notifications($pdo);
            if (!empty($autoEmails)) {
                $extraMsg = " Karena tanggal berada di titik pengingat (H-90/60/30/14/7/3), sistem langsung memproses pengiriman email ke <strong>" . htmlspecialchars($user['email']) . "</strong>!";
            }
        }

        $meta = get_doc_type_meta($doc_type);
        set_flash('success', "Dokumen <strong>{$meta['title']}</strong> berhasil disimpan ke departemen <strong>{$department}</strong> dengan tanda Created By: <strong>{$createdByStamp}</strong>.{$extraMsg}");
        header("Location: " . base_url('?page=agreements&dept=' . urlencode($department) . '&doc_type=' . urlencode($doc_type)));
        exit;
    }
}

$docMeta = get_doc_type_meta($selectedDocType);
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="cp-header-row mb-3">
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('?page=agreements') ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                <div>
                    <h1 class="cp-page-title ms-1">Input Dokumen Baru</h1>
                </div>
            </div>
        </div>

        <div class="cp-table-card mt-0 p-4">
            <form method="POST" action="<?= base_url('?page=create') ?>" enctype="multipart/form-data">
                <div class="row g-3">
                    <!-- BARIS 1: INFORMASI UMUM & DEPARTEMEN -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-dark mb-1">Type Dokumen <span class="text-danger">*</span></label>
                        <select name="doc_type" id="inputDocType" class="form-select fw-bold text-primary" required onchange="selectDocType(this.value)">
                            <option value="COOPERATION" <?= $selectedDocType === 'COOPERATION' ? 'selected' : '' ?>>1. COOPERATION AGREEMENT</option>
                            <option value="COMPANY" <?= $selectedDocType === 'COMPANY' ? 'selected' : '' ?>>2. ORIGINAL COMPANY DOC</option>
                            <option value="PRINCIPAL" <?= $selectedDocType === 'PRINCIPAL' ? 'selected' : '' ?>>3. PRINCIPAL DOCUMENT</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-dark mb-1">Departemen / Divisi <span class="text-danger">*</span></label>
                        <select name="department" id="selectDept" class="form-select fw-semibold" required onchange="toggleCustomDept(this.value)">
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= htmlspecialchars($d['kode_dept']) ?>" <?= ($preselectedDept === strtoupper($d['kode_dept'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['kode_dept']) ?> &mdash; <?= htmlspecialchars($d['nama_dept']) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (is_superadmin()): ?>
                                <option value="OTHER">+ Tambah Departemen Lainnya...</option>
                            <?php endif; ?>
                        </select>
                        <?php if (is_superadmin()): ?>
                            <input type="text" name="custom_dept" id="customDeptInput" class="form-control mt-1 text-uppercase d-none" placeholder="Ketik singkatan departemen baru (contoh: FIN)">
                        <?php endif; ?>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-dark mb-1">No. Urut (No.)</label>
                        <input type="number" name="no_urut_dokumen" id="inputNoUrut" class="form-control fw-bold" value="<?= $nextNums[$selectedDocType] ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-dark mb-1">Tanggal Input <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_input" id="inputTglCreate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- BARIS 2 & 3 KHUSUS 2 & 3: ORIGINAL COMPANY & PRINCIPAL -->
                    <div class="col-md-7 field-company-principal">
                        <label class="form-label fw-bold small text-dark mb-1">Document Title <span class="text-danger">*</span></label>
                        <input type="text" name="document_title" id="inputDocTitle" class="form-control"
                               placeholder="Contoh: Akta Pengangkatan kembali dewan direksi / Confidential Disclosure Agreement">
                    </div>

                    <div class="col-md-5 field-company-principal">
                        <label class="form-label fw-bold small text-dark mb-1">Document No.</label>
                        <input type="text" name="document_number" id="inputDocNoAlt" class="form-control"
                               placeholder="Contoh: AHU-AH.01.09-0410712 / SN : Q8-v3-1208007241 / ketik N/A jika tidak ada">
                    </div>

                    <div class="col-md-6 field-company-principal" id="colIssuedBy">
                        <label class="form-label fw-bold small text-dark mb-1">Issued By <span class="text-danger">*</span></label>
                        <input type="text" name="issued_by" id="inputIssuedBy" class="form-control"
                               placeholder="Contoh: Notaris Putranto / Nanik Gunawan - Cedric DEBONTE">
                    </div>

                    <div class="col-md-6 field-principal-only">
                        <label class="form-label fw-bold small text-dark mb-1">Principal <span class="text-danger">*</span></label>
                        <input type="text" name="principal_name" id="inputPrincipal" class="form-control"
                               placeholder="Contoh: Cepheid Europe S.A.S / Diagast / Tecan / Novartis V & D">
                    </div>

                    <!-- BARIS 2 & 3 KHUSUS 1: LOG OF COOPERATION AGREEMENT -->
                    <div class="col-md-6 field-cooperation-only">
                        <label class="form-label fw-bold small text-dark mb-1">Document Number <span class="text-danger">*</span></label>
                        <input type="text" name="document_number" id="inputDocNoCoop" class="form-control"
                               placeholder="Contoh: 087/KKO.PL/MBB/MJG/XII/2025">
                    </div>

                    <div class="col-md-6 field-cooperation-only">
                        <label class="form-label fw-bold small text-dark mb-1">Customer <span class="text-danger">*</span></label>
                        <input type="text" name="customer" id="inputCustomer" class="form-control"
                               placeholder="Contoh: Palang Merah Indonesia Provinsi DKI Jakarta">
                    </div>

                    <div class="col-md-8 field-cooperation-only">
                        <label class="form-label fw-bold small text-dark mb-1">Product (Description) <span class="text-danger">*</span></label>
                        <input type="text" name="product_description" id="inputProductDesc" class="form-control"
                               placeholder="Contoh: Fibroscan 530 Compact / Hemocue Hb 301 / NDA">
                    </div>

                    <div class="col-md-4 field-cooperation-only">
                        <label class="form-label fw-bold small text-dark mb-1">Serial Number (S/N)</label>
                        <input type="text" name="serial_number" id="inputSerialNo" class="form-control" value="N/A"
                               placeholder="Ketik S/N atau biarkan N/A">
                    </div>

                    <!-- BARIS 4: TANGGAL EFFECTIVE DATE & EXPIRY DATE (SIMETRIS 50/50, TANPA PILIHAN CEPAT & TANPA JADWAL DEADLINE) -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark mb-1">Effective Date</label>
                        <div class="input-group">
                            <input type="date" name="effective_date" id="effDateInput" class="form-control" value="<?= date('Y-m-d') ?>">
                            <div class="input-group-text bg-light">
                                <input class="form-check-input mt-0 me-1" type="checkbox" name="effective_date_na" id="effDateNaCheck" onchange="toggleEffNa(this.checked)">
                                <label class="small mb-0 fw-semibold user-select-none" for="effDateNaCheck" style="cursor:pointer;">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark mb-1">Expiry Date <span class="text-danger" id="expReqStar">*</span></label>
                        <div class="input-group">
                            <input type="date" name="expiry_date" id="expDateInput" class="form-control fw-semibold" required>
                            <div class="input-group-text bg-light">
                                <input class="form-check-input mt-0 me-1" type="checkbox" name="expiry_date_na" id="expDateNaCheck" onchange="toggleExpNa(this.checked)">
                                <label class="small mb-0 fw-semibold user-select-none" for="expDateNaCheck" style="cursor:pointer;">N/A</label>
                            </div>
                        </div>
                    </div>

                    <!-- BARIS 5: NOTE & UPLOAD FILE DOKUMEN -->
                    <div class="col-md-7">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold small text-dark mb-0">Catatan (Note)</label>
                            <div class="form-check form-switch small mb-0">
                                <input class="form-check-input" type="checkbox" name="prefix_date_note" id="prefixDateCheck">
                                <label class="form-check-label text-muted small" for="prefixDateCheck">Beri tanggal di awal catatan</label>
                            </div>
                        </div>
                        <textarea name="note" class="form-control" rows="2" placeholder="Kosongkan jika N/A, atau ketik catatan (contoh: Distributed as on 02 OCT 2009)..."></textarea>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-bold small text-dark mb-1">Upload File Dokumen (Opsional)</label>
                        <input type="file" name="file_dokumen" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                        <div class="text-muted mt-1" style="font-size:0.7rem;">Mendukung berkas format PDF, Word, Excel, JPG, atau PNG</div>
                    </div>
                </div>

                <hr class="my-4">

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

<script>
const NEXT_NUMS = <?= json_encode($nextNums) ?>;
const FORM_CODES = {
    'COOPERATION': 'Form: MJG-FOR-LAC.12-001-03',
    'COMPANY':     'Form: MJG-FOR-LAC.12-001-04',
    'PRINCIPAL':   'Form: MJG-FOR-LAC.12-001-09'
};

function selectDocType(type) {
    const sel = document.getElementById('inputDocType');
    if (sel && sel.value !== type) {
        sel.value = type;
    }

    if (NEXT_NUMS[type]) {
        document.getElementById('inputNoUrut').value = NEXT_NUMS[type];
    }

    const badge = document.getElementById('formCodeBadge');
    if (badge && FORM_CODES[type]) {
        badge.textContent = FORM_CODES[type];
    }

    const isCoop = (type === 'COOPERATION');
    const isComp = (type === 'COMPANY');
    const isPrin = (type === 'PRINCIPAL');

    // Show/hide field groups & enable/disable duplicate name="document_number"
    document.querySelectorAll('.field-cooperation-only').forEach(el => {
        el.style.display = isCoop ? '' : 'none';
    });
    document.querySelectorAll('.field-company-principal').forEach(el => {
        el.style.display = (isComp || isPrin) ? '' : 'none';
    });
    document.querySelectorAll('.field-principal-only').forEach(el => {
        el.style.display = isPrin ? '' : 'none';
    });

    // Untuk COMPANY, kolom Issued By melebar penuh (col-md-12) karena tidak ada kolom Principal
    const colIssuedBy = document.getElementById('colIssuedBy');
    if (colIssuedBy) {
        colIssuedBy.className = isComp ? 'col-md-12 field-company-principal' : 'col-md-6 field-company-principal';
    }

    // Atur required dan disabled agar form submit sesuai jenis dokumen
    const docNoCoop = document.getElementById('inputDocNoCoop');
    const docNoAlt  = document.getElementById('inputDocNoAlt');
    if (docNoCoop && docNoAlt) {
        docNoCoop.disabled = !isCoop;
        docNoCoop.required = isCoop;
        docNoAlt.disabled  = isCoop;
    }

    const custInput = document.getElementById('inputCustomer');
    const prodInput = document.getElementById('inputProductDesc');
    if (custInput) custInput.required = isCoop;
    if (prodInput) prodInput.required = isCoop;

    const titleInput  = document.getElementById('inputDocTitle');
    const issuedInput = document.getElementById('inputIssuedBy');
    const prinInput   = document.getElementById('inputPrincipal');
    if (titleInput)  titleInput.required  = (isComp || isPrin);
    if (issuedInput) issuedInput.required = (isComp || isPrin);
    if (prinInput)   prinInput.required   = isPrin;

    // Placeholder dinamis sesuai contoh di form fisik asli
    if (isComp) {
        if (titleInput)  titleInput.placeholder  = 'Contoh: Akta Pengangkatan kembali dewan direksi & dewan komisaris / SK Kemenkumham RI';
        if (docNoAlt)    docNoAlt.placeholder    = 'Contoh: 01 / AHU-AH.01.09-0410712';
        if (issuedInput) issuedInput.placeholder = 'Contoh: Notaris Putranto';
    } else if (isPrin) {
        if (titleInput)  titleInput.placeholder  = 'Contoh: Confidential Disclosure Agreement / Certificate Of Distribution / Letter of Authorization';
        if (docNoAlt)    docNoAlt.placeholder    = 'Contoh: N/A / SN : Q8-v3-1208007241 / CM 150403';
        if (issuedInput) issuedInput.placeholder = 'Contoh: Nanik Gunawan - Cedric DEBONTE';
    }

    // Pastikan validasi Expiry Date mengikuti status checkbox N/A
    const expNaChecked = document.getElementById('expDateNaCheck').checked;
    toggleExpNa(expNaChecked);
}

function toggleCustomDept(val) {
    const el = document.getElementById('customDeptInput');
    if (!el) return;
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

function toggleExpNa(checked) {
    const inp = document.getElementById('expDateInput');
    const star = document.getElementById('expReqStar');
    inp.disabled = checked;
    inp.required = !checked;
    if (star) star.style.display = checked ? 'none' : '';
    if (checked) {
        inp.value = '';
    }
}

// Inisialisasi tampilan form sesuai jenis dokumen awal
selectDocType('<?= htmlspecialchars($selectedDocType) ?>');
</script>