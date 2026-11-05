<?php
require_login();
$user  = current_user();
$uId   = (int)$user['id'];
$uCode = strtoupper($user['kode_singkatan']);

// Jalankan pengecekan otomatis notifikasi H-90, H-60, H-30, H-14, H-7, H-3
$autoSentResults = run_auto_expiry_notifications($pdo);

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_note') {
        $agrId       = (int)($_POST['agreement_id'] ?? 0);
        $catatanBaru = trim($_POST['catatan_baru'] ?? '');
        $tglInput    = !empty($_POST['tanggal_input']) ? $_POST['tanggal_input'] : date('Y-m-d');
        $newExpiry   = !empty($_POST['new_expiry_date']) ? $_POST['new_expiry_date'] : null;

        $stmt = $pdo->prepare("SELECT * FROM agreements WHERE id = ? AND (created_by_user_id = ? OR created_by_code = ?) LIMIT 1");
        $stmt->execute([$agrId, $uId, $uCode]);
        $agr = $stmt->fetch();

        if ($agr && $catatanBaru !== '') {
            $tglFormatted = format_date_medquest($tglInput, false);
            $stampBaru    = format_created_by_stamp($uCode, $tglInput);

            $barisNoteBaru = $tglFormatted . " :\n" . $catatanBaru;
            $noteLama = trim($agr['note'] ?? '');
            if ($noteLama === '' || strtoupper($noteLama) === 'N/A') {
                $noteFinal = $barisNoteBaru;
            } else {
                $noteFinal = $noteLama . "\n" . $barisNoteBaru;
            }

            $logLama = trim($agr['created_by_log'] ?? '');
            $linesLog = array_map('trim', explode("\n", $logLama));
            if (!in_array($stampBaru, $linesLog, true)) {
                $logFinal = $logLama !== '' ? ($logLama . "\n" . $stampBaru) : $stampBaru;
            } else {
                $logFinal = $logLama;
            }

            if ($newExpiry) {
                $upd = $pdo->prepare("
                    UPDATE agreements 
                    SET note = ?, created_by_log = ?, expiry_date = ?, expiry_date_na = 0,
                        notif_h90_sent_at = NULL, notif_h60_sent_at = NULL, notif_h30_sent_at = NULL,
                        notif_h14_sent_at = NULL, notif_h7_sent_at = NULL, notif_h3_sent_at = NULL, notif_h1_sent_at = NULL
                    WHERE id = ?
                ");
                $upd->execute([$noteFinal, $logFinal, $newExpiry, $agrId]);
            } else {
                $upd = $pdo->prepare("UPDATE agreements SET note = ?, created_by_log = ? WHERE id = ?");
                $upd->execute([$noteFinal, $logFinal, $agrId]);
            }

            $insH = $pdo->prepare("INSERT INTO agreement_histories (agreement_id, user_id, kode_singkatan, tanggal_input, catatan, aksi) VALUES (?, ?, ?, ?, ?, 'ADD_NOTE')");
            $insH->execute([$agrId, $uId, $uCode, $tglInput, $catatanBaru]);

            set_flash('success', "Catatan baru berhasil disimpan dan otomatis ditandai dengan <strong>{$stampBaru}</strong>.");
        }
        header("Location: " . base_url('?page=agreements&dept=' . urlencode($_POST['return_dept'] ?? 'ALL')));
        exit;
    }

    if ($action === 'delete') {
        $agrId = (int)($_POST['agreement_id'] ?? 0);
        $pdo->prepare("DELETE FROM agreements WHERE id = ? AND (created_by_user_id = ? OR created_by_code = ?)")->execute([$agrId, $uId, $uCode]);
        set_flash('success', 'Data dokumen berhasil dihapus.');
        header("Location: " . base_url('?page=agreements&dept=' . urlencode($_POST['return_dept'] ?? 'ALL')));
        exit;
    }

    if ($action === 'add_department' && is_superadmin()) {
        $kodeDept = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim($_POST['kode_dept'] ?? '')));
        $namaDept = trim($_POST['nama_dept'] ?? '');
        if ($kodeDept !== '' && $namaDept !== '') {
            $insD = $pdo->prepare("INSERT IGNORE INTO departments (kode_dept, nama_dept) VALUES (?, ?)");
            $insD->execute([$kodeDept, $namaDept]);
            set_flash('success', "Departemen <strong>{$kodeDept}</strong> berhasil ditambahkan.");
        }
        header("Location: " . base_url('?page=agreements&dept=' . urlencode($kodeDept ?: 'ALL')));
        exit;
    }
}

// Ambil Filter & Urutan dari URL
$selectedDept    = strtoupper(trim($_GET['dept'] ?? 'ALL'));
$selectedStatus  = trim($_GET['status'] ?? 'ALL');
$selectedDocType = strtoupper(trim($_GET['doc_type'] ?? 'ALL'));
$searchQuery     = trim($_GET['q'] ?? '');
$selectedSort    = trim($_GET['sort'] ?? 'default');
$currentPage     = max(1, (int)($_GET['p'] ?? 1));
$perPage         = 15;

// Query Data Agreements: HANYA MENAMPILKAN DOKUMEN DENGAN KODE SINGKATAN USER YANG SEDANG LOGIN
$whereClauses = ["(a.created_by_user_id = ? OR a.created_by_code = ?)"];
$params = [$uId, $uCode];

if ($selectedDept !== 'ALL' && $selectedDept !== '') {
    $whereClauses[] = "a.department = ?";
    $params[] = $selectedDept;
}

if (in_array($selectedDocType, ['COOPERATION', 'COMPANY', 'PRINCIPAL'], true)) {
    if ($selectedDocType === 'COOPERATION') {
        $whereClauses[] = "(a.doc_type = 'COOPERATION' OR a.doc_type IS NULL)";
    } else {
        $whereClauses[] = "a.doc_type = ?";
        $params[] = $selectedDocType;
    }
}

if ($searchQuery !== '') {
    $whereClauses[] = "(a.document_number LIKE ? OR a.customer LIKE ? OR a.product_description LIKE ? OR a.document_title LIKE ? OR a.issued_by LIKE ? OR a.principal_name LIKE ? OR a.serial_number LIKE ? OR a.created_by_log LIKE ? OR a.note LIKE ?)";
    $like = '%' . $searchQuery . '%';
    array_push($params, $like, $like, $like, $like, $like, $like, $like, $like, $like);
}

if ($selectedStatus === 'EXPIRED') {
    $whereClauses[] = "(a.expiry_date_na = 0 AND a.expiry_date IS NOT NULL AND DATEDIFF(a.expiry_date, CURDATE()) < 0)";
} elseif ($selectedStatus === 'H90_60') {
    $whereClauses[] = "(a.expiry_date_na = 0 AND a.expiry_date IS NOT NULL AND DATEDIFF(a.expiry_date, CURDATE()) BETWEEN 31 AND 90)";
} elseif ($selectedStatus === 'H30_14') {
    $whereClauses[] = "(a.expiry_date_na = 0 AND a.expiry_date IS NOT NULL AND DATEDIFF(a.expiry_date, CURDATE()) BETWEEN 8 AND 30)";
} elseif ($selectedStatus === 'H7_3') {
    $whereClauses[] = "(a.expiry_date_na = 0 AND a.expiry_date IS NOT NULL AND DATEDIFF(a.expiry_date, CURDATE()) BETWEEN 0 AND 7)";
} elseif ($selectedStatus === 'DEADLINE_ALL') {
    $whereClauses[] = "(a.expiry_date_na = 0 AND a.expiry_date IS NOT NULL AND DATEDIFF(a.expiry_date, CURDATE()) BETWEEN 0 AND 90)";
} elseif ($selectedStatus === 'ACTIVE') {
    $whereClauses[] = "(a.expiry_date_na = 1 OR a.expiry_date IS NULL OR DATEDIFF(a.expiry_date, CURDATE()) > 90)";
}

$whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

// Opsi Urutan Sorting
$orderBySql = match ($selectedSort) {
    'newest'        => "a.id DESC",
    'oldest'        => "a.id ASC",
    'deadline_asc'  => "CASE WHEN a.expiry_date IS NULL OR a.expiry_date_na = 1 THEN 1 ELSE 0 END, a.expiry_date ASC, a.id DESC",
    'deadline_desc' => "CASE WHEN a.expiry_date IS NULL OR a.expiry_date_na = 1 THEN 1 ELSE 0 END, a.expiry_date DESC, a.id DESC",
    default         => "COALESCE(a.no_urut_dokumen, a.id) ASC, a.id ASC",
};

$sqlAgreements = "
    SELECT a.*, u.nama_lengkap AS creator_name, u.email AS creator_email
    FROM agreements a
    LEFT JOIN users u ON a.created_by_user_id = u.id
    {$whereSql}
    ORDER BY {$orderBySql}
";
$stmtAgr = $pdo->prepare($sqlAgreements);
$stmtAgr->execute($params);
$allMatchingAgreements = $stmtAgr->fetchAll();

// Hitung statistik untuk 4 Kartu Lingkaran (menggunakan seluruh data filter)
$statTotal    = count($allMatchingAgreements);
$statSafe     = 0;
$statDeadline = 0;
$statUrgent   = 0;
$statExpired  = 0;

foreach ($allMatchingAgreements as $row) {
    if (!empty($row['expiry_date_na']) || empty($row['expiry_date'])) {
        $statSafe++;
        continue;
    }
    $d = get_days_until_expiry($row['expiry_date']);
    if ($d < 0) {
        $statExpired++;
    } else {
        $statSafe++;
        if ($d <= 90) $statDeadline++;
        if ($d <= 14) $statUrgent++;
    }
}

$pctSafe     = $statTotal > 0 ? round(($statSafe / $statTotal) * 100) : 0;
$pctDeadline = $statTotal > 0 ? round(($statDeadline / $statTotal) * 100) : 0;
$pctUrgent   = $statTotal > 0 ? round(($statUrgent / $statTotal) * 100) : 0;
$pctExpired  = $statTotal > 0 ? round(($statExpired / $statTotal) * 100) : 0;

// LOGIKA PAGINATION: Setiap 15 Dokumen Otomatis Buat Page Baru (Kelipatan 15)
$totalPages  = max(1, (int)ceil($statTotal / $perPage));
$currentPage = max(1, min($currentPage, $totalPages));
$offset      = ($currentPage - 1) * $perPage;

// Ambil 15 dokumen yang ditampilkan pada halaman saat ini
$agreements  = array_slice($allMatchingAgreements, $offset, $perPage);
?>

<!-- 1. OVERVIEW DASHBOARD HEADER & QUICK FILTER PILLS -->
<div class="cp-header-row no-print">
    <div class="d-flex align-items-center gap-2">
        <?php if ($selectedDept !== 'ALL' || $selectedStatus !== 'ALL' || $selectedDocType !== 'ALL' || $searchQuery !== ''): ?>
            <a href="<?= base_url('?page=agreements') ?>" class="btn btn-sm btn-light border fw-semibold px-2 py-1" style="font-size:0.74rem;">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        <?php endif; ?>
        <h1 class="cp-page-title">
            Overview Dashboard <?= $selectedDept !== 'ALL' ? ('&mdash; ' . htmlspecialchars($selectedDept)) : '' ?>
        </h1>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Quick Filter Tahapan Deadline -->
        <?php
        $isHFilterActive = in_array($selectedStatus, ['H7_3', 'H30_14', 'H90_60', 'DEADLINE_ALL'], true);
        $hFilterLabel = match ($selectedStatus) {
            'H7_3'   => 'Sisa 3 - 7 Hari',
            'H30_14' => 'Sisa 14 - 30 Hari',
            'H90_60' => 'Sisa 60 - 90 Hari',
            default  => 'Sisa Hari',
        };
        $baseFilterUrl = '?page=agreements&dept=' . urlencode($selectedDept) . '&doc_type=' . urlencode($selectedDocType);
        if ($selectedSort !== 'default') { $baseFilterUrl .= '&sort=' . urlencode($selectedSort); }
        if ($searchQuery !== '') { $baseFilterUrl .= '&q=' . urlencode($searchQuery); }

        $baseFilterUrlWithoutSort = '?page=agreements&dept=' . urlencode($selectedDept) . '&doc_type=' . urlencode($selectedDocType) . '&status=' . urlencode($selectedStatus);
        if ($searchQuery !== '') { $baseFilterUrlWithoutSort .= '&q=' . urlencode($searchQuery); }
        ?>
        <div class="cp-pill-group">
            <a href="<?= base_url($baseFilterUrl . '&status=ALL') ?>"
               class="cp-pill-link <?= $selectedStatus === 'ALL' ? 'active' : '' ?>">Semua</a>

            <div class="dropdown d-inline-block">
                <a href="#"
                   class="cp-pill-link d-inline-flex align-items-center gap-1 <?= $isHFilterActive ? 'active' : '' ?>"
                   data-bs-toggle="dropdown"
                   aria-expanded="false">
                    <span><?= $hFilterLabel ?></span>
                    <i class="fas fa-chevron-down" style="font-size:0.62rem;"></i>
                </a>
                <ul class="dropdown-menu shadow-sm border p-1 mt-1" style="min-width: 145px; border-radius: 0;">
                    <li>
                        <a class="dropdown-item rounded-0 small py-1 px-2 fw-semibold <?= $selectedStatus === 'H7_3' ? 'active' : '' ?>"
                           href="<?= base_url($baseFilterUrl . '&status=H7_3') ?>">
                            Sisa 3 - 7 Hari
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-0 small py-1 px-2 fw-semibold <?= $selectedStatus === 'H30_14' ? 'active' : '' ?>"
                           href="<?= base_url($baseFilterUrl . '&status=H30_14') ?>">
                            Sisa 14 - 30 Hari
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-0 small py-1 px-2 fw-semibold <?= $selectedStatus === 'H90_60' ? 'active' : '' ?>"
                           href="<?= base_url($baseFilterUrl . '&status=H90_60') ?>">
                            Sisa 60 - 90 Hari
                        </a>
                    </li>
                </ul>
            </div>

            <a href="<?= base_url($baseFilterUrl . '&status=EXPIRED') ?>"
               class="cp-pill-link <?= $selectedStatus === 'EXPIRED' ? 'active' : '' ?>">Expired</a>
        </div>

        <div class="cp-pill-group px-3 py-2 text-secondary small fw-semibold">
            <?= strtoupper(date('d M Y')) ?>
        </div>

        <?php if (is_superadmin()): ?>
        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold px-2 py-1" style="font-size:0.75rem;" data-bs-toggle="modal" data-bs-target="#modalAddDept" title="Tambah Departemen Baru (Khusus Superadmin)">
            + Dept
        </button>
        <?php endif; ?>

        <!-- Tombol + Input Dokumen -->
        <a href="<?= base_url('?page=create' . ($selectedDept !== 'ALL' ? '&dept=' . urlencode($selectedDept) : '')) ?>"
           class="btn btn-sm btn-primary fw-bold text-white px-3 py-1 d-inline-flex align-items-center gap-1"
           style="font-size:0.76rem;">
            <i class="fas fa-plus me-1"></i> Input Dokumen
        </a>
    </div>
</div>

<!-- 2. EMPAT KARTU STATISTIK RINGKAS (1 BARIS SLIM) -->
<div class="row g-2 mb-2 no-print">
    <div class="col-6 col-xl-3">
        <a href="<?= base_url($baseFilterUrl . '&status=ALL') ?>" class="cp-stat-card">
            <div>
                <div class="cp-stat-title">Total Dokumen</div>
                <div class="cp-stat-value"><?= $statTotal ?></div>
                <div class="cp-stat-sublabel"><?= $selectedDept === 'ALL' ? 'Semua Dept' : htmlspecialchars($selectedDept) ?></div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#2b579a 0% 90%, #e2e8f0 90% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-file-contract" style="color:#2b579a;"></i></div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="<?= base_url($baseFilterUrl . '&status=ACTIVE') ?>" class="cp-stat-card">
            <div>
                <div class="cp-stat-title">Dokumen Berlaku</div>
                <div class="cp-stat-value"><?= $statSafe ?></div>
                <div class="cp-stat-sublabel"><?= $pctSafe ?>% Aktif</div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#10b981 0% <?= max(12, $pctSafe) ?>%, #e2e8f0 <?= max(12, $pctSafe) ?>% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-check-circle" style="color:#10b981;"></i></div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="<?= base_url($baseFilterUrl . '&status=DEADLINE_ALL') ?>" class="cp-stat-card">
            <div>
                <div class="cp-stat-title">Deadline</div>
                <div class="cp-stat-value"><?= $statDeadline ?></div>
                <div class="cp-stat-sublabel"><?= $statUrgent ?> Mendesak</div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#f59e0b 0% <?= max(15, $pctDeadline) ?>%, #e2e8f0 <?= max(15, $pctDeadline) ?>% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-clock" style="color:#f59e0b;"></i></div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="<?= base_url($baseFilterUrl . '&status=EXPIRED') ?>" class="cp-stat-card">
            <div>
                <div class="cp-stat-title">Sudah Expired</div>
                <div class="cp-stat-value"><?= $statExpired ?></div>
                <div class="cp-stat-sublabel"><?= $pctExpired ?>% Habis</div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#ef4444 0% <?= max(10, $pctExpired) ?>%, #e2e8f0 <?= max(10, $pctExpired) ?>% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-exclamation-circle" style="color:#ef4444;"></i></div>
            </div>
        </a>
    </div>
</div>

<!-- 3. PANEL BAWAH: TABEL DATA DOKUMEN -->
<?php
$activeTableTitle = match ($selectedDocType) {
    'COMPANY'     => 'ORIGINAL COMPANY DOCUMENT',
    'PRINCIPAL'   => 'LOG OF PRINCIPAL DOCUMENT',
    'COOPERATION' => 'LOG OF COOPERATION AGREEMENT',
    default       => 'DAFTAR DOKUMEN LEGAL MEDQUEST',
};
$typeFilterBase = '?page=agreements&dept=' . urlencode($selectedDept) . '&status=' . urlencode($selectedStatus);
if ($selectedSort !== 'default') { $typeFilterBase .= '&sort=' . urlencode($selectedSort); }
if ($searchQuery !== '') { $typeFilterBase .= '&q=' . urlencode($searchQuery); }
?>
<div class="cp-table-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h6 class="fw-bold text-dark mb-0 me-2">
                <?= htmlspecialchars($activeTableTitle) ?>
            </h6>
            <!-- Filter Tab Jenis Dokumen -->
            <div class="cp-pill-group no-print">
                <a href="<?= base_url($typeFilterBase . '&doc_type=ALL') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'ALL' ? 'active' : '' ?>">SEMUA JENIS</a>
                <a href="<?= base_url($typeFilterBase . '&doc_type=COOPERATION') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'COOPERATION' ? 'active' : '' ?>">COOPERATION AGREEMENT</a>
                <a href="<?= base_url($typeFilterBase . '&doc_type=COMPANY') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'COMPANY' ? 'active' : '' ?>">ORIGINAL COMPANY DOC</a>
                <a href="<?= base_url($typeFilterBase . '&doc_type=PRINCIPAL') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'PRINCIPAL' ? 'active' : '' ?>">PRINCIPAL DOCUMENT</a>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 small no-print">
            <span><span class="d-inline-block rounded-0 me-1" style="width:8px;height:8px;background:#10b981;"></span> Aktif</span>
            <span><span class="d-inline-block rounded-0 me-1" style="width:8px;height:8px;background:#f59e0b;"></span> Akan Expired</span>
            <span><span class="d-inline-block rounded-0 me-1" style="width:8px;height:8px;background:#ef4444;"></span> Expired</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="cp-table">
            <thead>
                <tr>
                    <th style="width:90px;">Dept / No</th>
                    <?php if ($selectedDocType === 'COMPANY'): ?>
                        <th style="width:250px;">Document Title &amp; Document No.</th>
                        <th style="width:190px;">Issued By</th>
                    <?php elseif ($selectedDocType === 'PRINCIPAL'): ?>
                        <th style="width:240px;">Document Title &amp; Document No.</th>
                        <th style="width:200px;">Issued By &amp; Principal</th>
                    <?php elseif ($selectedDocType === 'COOPERATION'): ?>
                        <th style="width:230px;">Customer &amp; Document No</th>
                        <th style="width:195px;">Product &amp; S/N</th>
                    <?php else: ?>
                        <th style="width:240px;">Dokumen / Customer &amp; No</th>
                        <th style="width:200px;">Detail (Product / Issued By / Principal)</th>
                    <?php endif; ?>
                    <th style="min-width:275px;">
                        <div class="d-flex align-items-center justify-content-between gap-1">
                            <span>Effective, Expiry &amp; Deadline</span>
                            <!-- Tombol Filter Urutan Dokumen (Terbaru > Lama, dll) -->
                            <div class="dropdown no-print d-inline-block">
                                <button class="btn btn-xs py-0 px-2 d-inline-flex align-items-center gap-1 dropdown-toggle <?= $selectedSort !== 'default' ? 'btn-primary text-white' : 'btn-outline-secondary bg-white text-dark' ?>"
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                        style="font-size:0.72rem; font-weight:600; text-transform:none; border-radius:0;">
                                    <span>Filter</span>
                                    <i class="fas fa-chevron-down" style="font-size:0.58rem;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border p-1 mt-1" style="min-width: 230px; border-radius: 0; font-size:0.76rem;">
                                    <li><h6 class="dropdown-header py-1 px-2 text-uppercase text-muted" style="font-size:0.66rem;">Urutkan Dokumen:</h6></li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'newest' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=newest') ?>">
                                            <i class="fas fa-arrow-down-wide-short me-1"></i> Data Terbaru &gt; Data Lama
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'oldest' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=oldest') ?>">
                                            <i class="fas fa-arrow-up-wide-short me-1"></i> Data Paling Lama &gt; Data Paling Baru
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= ($selectedSort === 'default' || $selectedSort === '') ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=default') ?>">
                                            <i class="fas fa-sort-numeric-down me-1"></i> No. Urut Form (Default)
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'deadline_asc' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=deadline_asc') ?>">
                                            <i class="fas fa-clock text-danger me-1"></i> Deadline Paling Dekat
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'deadline_desc' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=deadline_desc') ?>">
                                            <i class="fas fa-calendar-check text-success me-1"></i> Deadline Paling Jauh
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </th>
                    <th style="width:200px;">Note</th>
                    <th style="width:160px;">Created By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($agreements)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block opacity-50"></i>
                            Belum ada dokumen dengan kode <strong><?= htmlspecialchars($uCode) ?></strong> pada filter ini.
                            <div class="mt-3">
                                <a href="<?= base_url('?page=create' . ($selectedDocType !== 'ALL' ? '&doc_type=' . urlencode($selectedDocType) : '') . ($selectedDept !== 'ALL' ? '&dept=' . urlencode($selectedDept) : '')) ?>" class="btn btn-sm btn-primary fw-bold px-3 py-2">
                                    <i class="fas fa-plus-circle me-1"></i> + Input Dokumen Baru
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($agreements as $row):
                        $rowType = strtoupper(trim($row['doc_type'] ?? 'COOPERATION'));
                        $typeMeta = get_doc_type_meta($rowType);
                        $isExpNa = !empty($row['expiry_date_na']) || empty($row['expiry_date']);
                        $expInfo = get_expiry_info($row['expiry_date'], $isExpNa);
                        $effFormatted = format_date_medquest($row['effective_date'], $row['effective_date_na']);
                        $expFormatted = format_date_medquest($row['expiry_date'], $isExpNa);
                        $displayTitle = ($rowType === 'COOPERATION')
                            ? ($row['customer'] ?: '-')
                            : ($row['document_title'] ?: $row['product_description'] ?: '-');
                    ?>
                        <tr class="<?= $expInfo['row_class'] ?>">
                            <!-- 1. Dept, No & Jenis Form -->
                            <td>
                                <a href="<?= base_url('?page=agreements&dept=' . urlencode($row['department'])) ?>"
                                   class="badge mq-dept-badge text-decoration-none px-2 py-1 mb-1 d-inline-block" style="font-size:0.74rem;">
                                    <?= htmlspecialchars($row['department']) ?>
                                </a>
                                <div class="text-muted small fw-semibold">#<?= htmlspecialchars($row['no_urut_dokumen'] ?: $row['id']) ?></div>
                                <?php if ($selectedDocType === 'ALL'): ?>
                                    <span class="badge mt-1" style="background:<?= $typeMeta['badge_bg'] ?>;color:<?= $typeMeta['badge_color'] ?>;border:1px solid <?= $typeMeta['badge_border'] ?>;font-size:0.62rem;">
                                        <?= htmlspecialchars($typeMeta['short_title']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- 2. Document Title / Customer & Document Number -->
                            <td>
                                <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($displayTitle) ?></div>
                                <div class="text-secondary small" style="white-space:pre-line;font-size:0.76rem;">
                                    <span class="text-muted">No:</span> <?= htmlspecialchars($row['document_number'] ?: 'N/A') ?>
                                </div>
                            </td>

                            <!-- 3. Product & S/N ATAU Issued By & Principal -->
                            <td>
                                <?php if ($rowType === 'COMPANY'): ?>
                                    <div class="small text-muted">Issued By:</div>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($row['issued_by'] ?: $row['customer'] ?: '-') ?></div>
                                <?php elseif ($rowType === 'PRINCIPAL'): ?>
                                    <div class="mb-1">
                                        <span class="text-muted small">Principal:</span>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['principal_name'] ?: $row['customer'] ?: '-') ?></div>
                                    </div>
                                    <div class="small text-secondary">
                                        <span class="text-muted">Issued By:</span> <?= htmlspecialchars($row['issued_by'] ?: '-') ?>
                                    </div>
                                <?php else: ?>
                                    <div class="fw-semibold text-dark mb-1"><?= htmlspecialchars($row['product_description']) ?></div>
                                    <span class="badge bg-light text-secondary border" style="font-size:0.7rem;">
                                        S/N: <?= htmlspecialchars($row['serial_number'] ?: 'N/A') ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- 4. Effective Date, Expiry Date & Status Deadline -->
                            <td>
                                <div class="small mb-1">
                                    <span class="text-muted">Effective:</span> <strong><?= $effFormatted ?></strong>
                                    <span class="mx-1 text-muted">&rarr;</span>
                                    <span class="<?= $isExpNa ? 'text-secondary' : 'text-danger' ?> fw-bold">Expiry: <?= $expFormatted ?></span>
                                </div>
                                <div>
                                    <span class="expiry-pill <?= $expInfo['badge_class'] ?>">
                                        <?= htmlspecialchars($expInfo['short_label']) ?>
                                    </span>
                                </div>
                            </td>

                            <!-- 5. Note -->
                            <td>
                                <div class="small text-secondary mb-2" style="white-space:pre-line;max-height:85px;overflow-y:auto;"><?= htmlspecialchars($row['note'] ?: 'N/A') ?></div>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary py-0 px-2 no-print"
                                        style="font-size:0.72rem;font-weight:600;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalAddNote"
                                        data-id="<?= $row['id'] ?>"
                                        data-customer="<?= htmlspecialchars($displayTitle) ?>"
                                        data-docno="<?= htmlspecialchars($row['document_number']) ?>">
                                    <i class="fas fa-plus me-1"></i> + Note
                                </button>
                            </td>

                            <!-- 6. Created By & Aksi (Eye Inspect, Edit, Delete) -->
                            <td>
                                <div class="fw-bold text-dark font-monospace small mb-2" style="white-space:pre-line;"><?= htmlspecialchars($row['created_by_log']) ?></div>
                                <div class="d-flex gap-1 no-print">
                                    <a href="<?= base_url('?page=detail&id=' . $row['id']) ?>" class="btn btn-sm btn-light border py-0 px-2 small text-primary" title="Inspect / Lihat Detail Dokumen">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('?page=edit&id=' . $row['id']) ?>" class="btn btn-sm btn-light border py-0 px-2 small" title="Edit">
                                        <i class="fas fa-edit text-secondary"></i>
                                    </a>
                                    <form method="POST" action="<?= base_url('?page=agreements') ?>" class="d-inline" onsubmit="return confirm('Hapus dokumen <?= htmlspecialchars(addslashes($displayTitle)) ?>?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="agreement_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="return_dept" value="<?= htmlspecialchars($selectedDept) ?>">
                                        <button type="submit" class="btn btn-sm btn-light border py-0 px-2 small text-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- BARIS PAGINATION (15 DOKUMEN PER HALAMAN & BERLAKU KELIPATANNYA) -->
    <div class="d-flex flex-wrap align-items-center justify-content-between p-3 border-top bg-light no-print">
        <div class="small text-muted fw-semibold mb-2 mb-md-0">
            <?php if ($statTotal > 0): ?>
                Menampilkan <span class="text-dark fw-bold"><?= $offset + 1 ?> &ndash; <?= min($offset + $perPage, $statTotal) ?></span> dari total <span class="text-dark fw-bold"><?= $statTotal ?></span> dokumen
                <span class="ms-1 text-secondary">(Halaman <?= $currentPage ?> dari <?= $totalPages ?>)</span>
            <?php else: ?>
                Tidak ada dokumen yang ditampilkan
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
            $pageUrlHelper = function($p) use ($selectedDept, $selectedDocType, $selectedStatus, $searchQuery, $selectedSort) {
                $q = [
                    'page'     => 'agreements',
                    'dept'     => $selectedDept,
                    'doc_type' => $selectedDocType,
                    'status'   => $selectedStatus,
                    'p'        => $p
                ];
                if ($searchQuery !== '') $q['q'] = $searchQuery;
                if ($selectedSort !== 'default') $q['sort'] = $selectedSort;
                return base_url('?' . http_build_query($q));
            };
            ?>
            <nav aria-label="Navigasi Halaman Dokumen">
                <ul class="pagination pagination-sm mb-0">
                    <!-- Tombol Prev -->
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link rounded-0 fw-semibold" href="<?= $currentPage <= 1 ? '#' : $pageUrlHelper($currentPage - 1) ?>" tabindex="-1">
                            <i class="fas fa-chevron-left me-1"></i> Prev
                        </a>
                    </li>

                    <!-- Nomor Halaman -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link rounded-0 fw-bold" href="<?= $pageUrlHelper($i) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- Tombol Next -->
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link rounded-0 fw-semibold" href="<?= $currentPage >= $totalPages ? '#' : $pageUrlHelper($currentPage + 1) ?>">
                            Next <i class="fas fa-chevron-right ms-1"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tambah Note -->
<div class="modal fade no-print" id="modalAddNote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= base_url('?page=agreements') ?>" class="modal-content rounded-0 border-0 shadow">
            <input type="hidden" name="action" value="add_note">
            <input type="hidden" name="agreement_id" id="noteModalAgrId">
            <input type="hidden" name="return_dept" value="<?= htmlspecialchars($selectedDept) ?>">

            <div class="modal-header mq-modal-header text-white">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-sticky-note me-2"></i> Tambah Note / Riwayat Tindak Lanjut
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="p-2 rounded-0 bg-light border mb-3 small">
                    <div class="fw-bold text-dark" id="noteModalCustomer"></div>
                    <div class="text-muted" id="noteModalDocNo"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Tanggal Catatan Baru <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_input" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Isi Catatan Tindak Lanjut <span class="text-danger">*</span></label>
                    <textarea name="catatan_baru" class="form-control" rows="3" placeholder="Contoh: Masih proses review oleh legal customer..." required></textarea>
                </div>

                <div class="mb-1">
                    <label class="form-label fw-semibold small">Perpanjang Tanggal Berakhir / Expiry Date Baru (Opsional)</label>
                    <input type="date" name="new_expiry_date" class="form-control">
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Note</button>
            </div>
        </form>
    </div>
</div>

<?php if (is_superadmin()): ?>
<!-- Modal Tambah Departemen Baru (Khusus Superadmin) -->
<div class="modal fade no-print" id="modalAddDept" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" action="<?= base_url('?page=agreements') ?>" class="modal-content rounded-0 border-0 shadow">
            <input type="hidden" name="action" value="add_department">
            <div class="modal-header mq-modal-header text-white">
                <h6 class="modal-title fw-bold">Tambah Departemen Baru</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Kode Singkatan Departemen <span class="text-danger">*</span></label>
                    <input type="text" name="kode_dept" class="form-control text-uppercase fw-bold" placeholder="Contoh: MKT / FIN" maxlength="15" required>
                </div>
                <div class="mb-1">
                    <label class="form-label small fw-semibold">Nama Lengkap Divisi <span class="text-danger">*</span></label>
                    <input type="text" name="nama_dept" class="form-control" placeholder="Contoh: Divisi Marketing" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100 fw-bold">Simpan Departemen</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
const modalAddNote = document.getElementById('modalAddNote');
if (modalAddNote) {
    modalAddNote.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('noteModalAgrId').value = button.getAttribute('data-id');
        document.getElementById('noteModalCustomer').textContent = button.getAttribute('data-customer');
        document.getElementById('noteModalDocNo').textContent = button.getAttribute('data-docno');
    });
}
</script>
