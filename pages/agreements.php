<?php
require_login();
$user  = current_user();
$uId   = (int)$user['id'];
$uCode = strtoupper($user['kode_singkatan']);

// Jalankan pengecekan otomatis notifikasi H-90, H-60, H-30, H-14, H-7, H-3
$autoSentResults = run_auto_expiry_notifications($pdo);

// Handle POST Actions (Hanya boleh mengubah dokumen milik user sendiri)
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
                    SET note = ?, created_by_log = ?, expiry_date = ?,
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

    if ($action === 'add_department') {
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

// Ambil Filter dari URL
$selectedDept   = strtoupper(trim($_GET['dept'] ?? 'ALL'));
$selectedStatus = trim($_GET['status'] ?? 'ALL');
$searchQuery    = trim($_GET['q'] ?? '');

// Ambil daftar departemen & jumlah dokumen per departemen KHUSUS MILIK USER YANG LOGIN (misal RYN hanya menghitung dokumen RYN)
$deptList = $pdo->query("SELECT * FROM departments ORDER BY id ASC")->fetchAll();
$stmtDeptCounts = $pdo->prepare("
    SELECT department, COUNT(*) AS total 
    FROM agreements 
    WHERE (created_by_user_id = ? OR created_by_code = ?)
    GROUP BY department
");
$stmtDeptCounts->execute([$uId, $uCode]);
$deptCountsRaw = $stmtDeptCounts->fetchAll();

$deptCounts = [];
$totalAllDepts = 0;
foreach ($deptCountsRaw as $dc) {
    $deptCounts[strtoupper($dc['department'])] = (int)$dc['total'];
    $totalAllDepts += (int)$dc['total'];
}

$knownCodes = array_map(fn($d) => strtoupper($d['kode_dept']), $deptList);
foreach ($deptCounts as $code => $cnt) {
    if (!in_array($code, $knownCodes, true)) {
        $deptList[] = ['kode_dept' => $code, 'nama_dept' => 'Divisi ' . $code];
    }
}

// Query Data Agreements: HANYA MENAMPILKAN DOKUMEN DENGAN KODE SINGKATAN USER YANG SEDANG LOGIN (contoh: RYN hanya melihat dokumen RYN)
$whereClauses = ["(a.created_by_user_id = ? OR a.created_by_code = ?)"];
$params = [$uId, $uCode];

if ($selectedDept !== 'ALL' && $selectedDept !== '') {
    $whereClauses[] = "a.department = ?";
    $params[] = $selectedDept;
}

if ($searchQuery !== '') {
    $whereClauses[] = "(a.document_number LIKE ? OR a.customer LIKE ? OR a.product_description LIKE ? OR a.serial_number LIKE ? OR a.created_by_log LIKE ? OR a.note LIKE ?)";
    $like = '%' . $searchQuery . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}

if ($selectedStatus === 'EXPIRED') {
    $whereClauses[] = "DATEDIFF(a.expiry_date, CURDATE()) < 0";
} elseif ($selectedStatus === 'H90_60') {
    $whereClauses[] = "DATEDIFF(a.expiry_date, CURDATE()) BETWEEN 31 AND 90";
} elseif ($selectedStatus === 'H30_14') {
    $whereClauses[] = "DATEDIFF(a.expiry_date, CURDATE()) BETWEEN 8 AND 30";
} elseif ($selectedStatus === 'H7_3') {
    $whereClauses[] = "DATEDIFF(a.expiry_date, CURDATE()) BETWEEN 0 AND 7";
} elseif ($selectedStatus === 'DEADLINE_ALL') {
    $whereClauses[] = "DATEDIFF(a.expiry_date, CURDATE()) BETWEEN 0 AND 90";
} elseif ($selectedStatus === 'ACTIVE') {
    $whereClauses[] = "DATEDIFF(a.expiry_date, CURDATE()) > 90";
}

$whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

$sqlAgreements = "
    SELECT a.*, u.nama_lengkap AS creator_name, u.email AS creator_email
    FROM agreements a
    LEFT JOIN users u ON a.created_by_user_id = u.id
    {$whereSql}
    ORDER BY COALESCE(a.no_urut_dokumen, a.id) ASC, a.id ASC
";
$stmtAgr = $pdo->prepare($sqlAgreements);
$stmtAgr->execute($params);
$agreements = $stmtAgr->fetchAll();

// Hitung statistik untuk 4 Kartu Lingkaran Connect Plus
$statTotal    = count($agreements);
$statSafe     = 0;
$statDeadline = 0;
$statUrgent   = 0;
$statExpired  = 0;

foreach ($agreements as $row) {
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
?>

<!-- 1. OVERVIEW DASHBOARD HEADER & QUICK FILTER PILLS -->
<div class="cp-header-row no-print">
    <div class="d-flex align-items-center gap-2">
        <?php if ($selectedDept !== 'ALL' || $selectedStatus !== 'ALL' || $searchQuery !== ''): ?>
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
            'H7_3'   => 'H-3 s/d H-7',
            'H30_14' => 'H-14 s/d H-30',
            'H90_60' => 'H-60 s/d H-90',
            default  => 'H-7 s/d H-90',
        };
        ?>
        <div class="cp-pill-group">
            <a href="<?= base_url('?page=agreements&dept=' . urlencode($selectedDept) . '&status=ALL') ?>"
               class="cp-pill-link <?= $selectedStatus === 'ALL' ? 'active' : '' ?>">Semua</a>

            <div class="dropdown d-inline-block">
                <a href="#"
                   class="cp-pill-link d-inline-flex align-items-center gap-1 <?= $isHFilterActive ? 'active' : '' ?>"
                   data-bs-toggle="dropdown"
                   aria-expanded="false">
                    <span><?= $hFilterLabel ?></span>
                    <i class="fas fa-chevron-down" style="font-size:0.62rem;"></i>
                </a>
                <ul class="dropdown-menu shadow-sm border p-1 mt-1" style="min-width: 145px; border-radius: 8px;">
                    <li>
                        <a class="dropdown-item rounded-2 small py-1 px-2 fw-semibold <?= $selectedStatus === 'H7_3' ? 'active' : '' ?>"
                           href="<?= base_url('?page=agreements&dept=' . urlencode($selectedDept) . '&status=H7_3') ?>">
                            H-3 s/d H-7
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2 small py-1 px-2 fw-semibold <?= $selectedStatus === 'H30_14' ? 'active' : '' ?>"
                           href="<?= base_url('?page=agreements&dept=' . urlencode($selectedDept) . '&status=H30_14') ?>">
                            H-14 s/d H-30
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2 small py-1 px-2 fw-semibold <?= $selectedStatus === 'H90_60' ? 'active' : '' ?>"
                           href="<?= base_url('?page=agreements&dept=' . urlencode($selectedDept) . '&status=H90_60') ?>">
                            H-60 s/d H-90
                        </a>
                    </li>
                </ul>
            </div>

            <a href="<?= base_url('?page=agreements&dept=' . urlencode($selectedDept) . '&status=EXPIRED') ?>"
               class="cp-pill-link <?= $selectedStatus === 'EXPIRED' ? 'active' : '' ?>">Expired</a>
        </div>

        <div class="cp-pill-group px-3 py-2 text-secondary small fw-semibold">
            <?= strtoupper(date('d M Y')) ?>
        </div>

        <a href="<?= base_url('?page=create' . ($selectedDept !== 'ALL' ? '&dept=' . urlencode($selectedDept) : '')) ?>"
           class="btn btn-sm btn-primary fw-bold text-white px-3 py-2">
            + Input Agreement
        </a>
    </div>
</div>

<!-- 3. UNDERLINE TABS PER DEPARTEMEN -->
<div class="d-flex justify-content-between align-items-center border-bottom mb-2 no-print">
    <div class="d-flex align-items-center flex-wrap" style="min-height: 38px;">
        <div class="cp-underline-tabs border-bottom-0 mb-0">
            <a href="<?= base_url('?page=agreements&dept=ALL&status=' . urlencode($selectedStatus)) ?>"
               class="cp-tab-link <?= $selectedDept === 'ALL' ? 'active' : '' ?>">
                <span>Semua</span>
                <span class="cp-tab-badge"><?= $totalAllDepts ?></span>
            </a>
        </div>

        <!-- Tombol > Buka/Tutup Kolom Departemen -->
        <button type="button"
                id="toggleDeptTabsBtn"
                class="cp-tab-toggle-btn"
                title="Buka / Tutup Daftar Departemen">
            <i class="fas fa-chevron-right dept-toggle-icon <?= ($selectedDept !== 'ALL') ? 'rotate-open' : '' ?>"></i>
            <span>Departemen</span>
        </button>

        <!-- Daftar Departemen yang Muncul Saat Tombol > Dipencet -->
        <div id="deptTabsContainer" class="dept-tabs-wrapper <?= ($selectedDept !== 'ALL') ? 'is-open' : '' ?>">
            <div class="cp-underline-tabs border-bottom-0 mb-0">
                <?php foreach ($deptList as $dept):
                    $code = strtoupper($dept['kode_dept']);
                    $cnt  = $deptCounts[$code] ?? 0;
                ?>
                    <a href="<?= base_url('?page=agreements&dept=' . urlencode($code) . '&status=' . urlencode($selectedStatus)) ?>"
                       class="cp-tab-link <?= $selectedDept === $code ? 'active' : '' ?>"
                       title="<?= htmlspecialchars($dept['nama_dept']) ?>">
                        <span><?= htmlspecialchars($code) ?></span>
                        <span class="cp-tab-badge"><?= $cnt ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <button type="button" class="btn btn-sm btn-light border text-secondary py-0 px-2 mb-1" style="font-size:0.7rem;" data-bs-toggle="modal" data-bs-target="#modalAddDept">
        + Dept
    </button>
</div>

<!-- 4. EMPAT KARTU STATISTIK RINGKAS (1 BARIS SLIM) -->
<div class="row g-2 mb-2 no-print">
    <div class="col-6 col-xl-3">
        <a href="<?= base_url('?page=agreements&dept=' . urlencode($selectedDept) . '&status=ALL') ?>" class="cp-stat-card">
            <div>
                <div class="cp-stat-title">Total Agreement</div>
                <div class="cp-stat-value"><?= $statTotal ?></div>
                <div class="cp-stat-sublabel"><?= $selectedDept === 'ALL' ? 'Semua Dept' : htmlspecialchars($selectedDept) ?></div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#2458f1 0% 90%, #dce6f5 90% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-file-contract" style="color:#2458f1;"></i></div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="<?= base_url('?page=agreements&dept=' . urlencode($selectedDept) . '&status=DEADLINE_ALL') ?>" class="cp-stat-card">
            <div>
                <div class="cp-stat-title">Kontrak Berlaku</div>
                <div class="cp-stat-value"><?= $statSafe ?></div>
                <div class="cp-stat-sublabel"><?= $pctSafe ?>% Aktif</div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#10b981 0% <?= max(12, $pctSafe) ?>%, #e2e8f0 <?= max(12, $pctSafe) ?>% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-check-circle" style="color:#10b981;"></i></div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="<?= base_url('?page=agreements&dept=' . urlencode($selectedDept) . '&status=DEADLINE_ALL') ?>" class="cp-stat-card">
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
        <a href="<?= base_url('?page=agreements&dept=' . urlencode($selectedDept) . '&status=EXPIRED') ?>" class="cp-stat-card">
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

<!-- 5. PANEL BAWAH: TABEL DATA AGREEMENT -->
<div class="cp-table-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <h6 class="fw-bold text-dark mb-0">
            Log of Cooperation Agreement <?= $selectedDept !== 'ALL' ? ('&mdash; ' . htmlspecialchars($selectedDept)) : '' ?>
        </h6>

        <div class="d-flex align-items-center gap-3 small no-print">
            <span><span class="d-inline-block rounded-circle me-1" style="width:9px;height:9px;background:#10b981;"></span> Aktif</span>
            <span><span class="d-inline-block rounded-circle me-1" style="width:9px;height:9px;background:#f59e0b;"></span> Akan Expired</span>
            <span><span class="d-inline-block rounded-circle me-1" style="width:9px;height:9px;background:#ef4444;"></span> Expired</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="cp-table">
            <thead>
                <tr>
                    <th style="width:90px;">Dept / No</th>
                    <th style="width:220px;">Customer &amp; Document No</th>
                    <th style="width:190px;">Product &amp; S/N</th>
                    <th style="width:280px;">Tanggal Kontrak &amp; Deadline</th>
                    <th style="width:210px;">Note</th>
                    <th style="width:165px;">Created By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($agreements)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block opacity-50"></i>
                            Belum ada dokumen dengan kode <strong><?= htmlspecialchars($uCode) ?></strong> pada filter departemen <strong><?= htmlspecialchars($selectedDept) ?></strong>.
                            <div class="mt-3">
                                <a href="<?= base_url('?page=create' . ($selectedDept !== 'ALL' ? '&dept=' . urlencode($selectedDept) : '')) ?>" class="btn btn-sm btn-primary fw-bold px-3 py-2">
                                    <i class="fas fa-plus-circle me-1"></i> + Input Dokumen Baru (<?= htmlspecialchars($uCode) ?>)
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($agreements as $row):
                        $expInfo = get_expiry_info($row['expiry_date']);
                        $effFormatted = format_date_medquest($row['effective_date'], $row['effective_date_na']);
                        $expFormatted = format_date_medquest($row['expiry_date'], false);
                        $schedule = get_deadline_schedule($row['expiry_date'], $row);
                    ?>
                        <tr class="<?= $expInfo['row_class'] ?>">
                            <!-- 1. Dept & No -->
                            <td>
                                <a href="<?= base_url('?page=agreements&dept=' . urlencode($row['department'])) ?>"
                                   class="badge mq-dept-badge text-decoration-none px-2 py-1 mb-1 d-inline-block" style="font-size:0.76rem;">
                                    <?= htmlspecialchars($row['department']) ?>
                                </a>
                                <div class="text-muted small fw-semibold">#<?= htmlspecialchars($row['no_urut_dokumen'] ?: $row['id']) ?></div>
                            </td>

                            <!-- 2. Customer & Document Number -->
                            <td>
                                <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($row['customer']) ?></div>
                                <div class="text-secondary small" style="white-space:pre-line;font-size:0.78rem;"><?= htmlspecialchars($row['document_number']) ?></div>
                            </td>

                            <!-- 3. Product Description & Serial Number -->
                            <td>
                                <div class="fw-semibold text-dark mb-1"><?= htmlspecialchars($row['product_description']) ?></div>
                                <span class="badge bg-light text-secondary border" style="font-size:0.72rem;">
                                    S/N: <?= htmlspecialchars($row['serial_number'] ?: 'N/A') ?>
                                </span>
                            </td>

                            <!-- 4. Effective Date, Expiry Date & Status Deadline -->
                            <td>
                                <div class="small mb-1">
                                    <span class="text-muted">Effective:</span> <strong><?= $effFormatted ?></strong>
                                    <span class="mx-1 text-muted">&rarr;</span>
                                    <span class="text-danger fw-bold">Expiry: <?= $expFormatted ?></span>
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
                                        style="font-size:0.74rem;font-weight:600;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalAddNote"
                                        data-id="<?= $row['id'] ?>"
                                        data-customer="<?= htmlspecialchars($row['customer']) ?>"
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
                                    <form method="POST" action="<?= base_url('?page=agreements') ?>" class="d-inline" onsubmit="return confirm('Hapus dokumen <?= htmlspecialchars(addslashes($row['customer'])) ?>?')">
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
</div>

<!-- Modal Tambah Note -->
<div class="modal fade no-print" id="modalAddNote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= base_url('?page=agreements') ?>" class="modal-content rounded-4 border-0 shadow">
            <input type="hidden" name="action" value="add_note">
            <input type="hidden" name="agreement_id" id="noteModalAgrId">
            <input type="hidden" name="return_dept" value="<?= htmlspecialchars($selectedDept) ?>">

            <div class="modal-header mq-modal-header text-white">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-comment-medical me-1"></i> Tambah Note &amp; Stempel Created By
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="p-3 rounded-3 bg-light border mb-3 small">
                    <div class="mb-1"><strong>Customer:</strong> <span id="noteModalCustomer"></span></div>
                    <div><strong>Doc. Number:</strong> <span id="noteModalDocNo"></span></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Tanggal Input Note</label>
                    <input type="date" name="tanggal_input" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    <div class="form-text small">
                        Otomatis tertulis di kolom <strong>Created By</strong>:
                        <span class="badge bg-dark font-monospace ms-1"><?= htmlspecialchars($uCode) ?>, <?= strtoupper(date('d M Y')) ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Isi Catatan Baru <span class="text-danger">*</span></label>
                    <textarea name="catatan_baru" class="form-control" rows="3" placeholder="Contoh: Email ke MKT / Hard dokumen KKO baru balik ke LAC..." required></textarea>
                </div>

                <div class="mb-1">
                    <label class="form-label fw-semibold small">Perbarui Tanggal Expiry Date? (Opsional)</label>
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

<!-- Modal Tambah Departemen Baru -->
<div class="modal fade no-print" id="modalAddDept" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" action="<?= base_url('?page=agreements') ?>" class="modal-content rounded-4 border-0 shadow">
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
const toggleDeptBtn = document.getElementById('toggleDeptTabsBtn');
const deptTabsWrapper = document.getElementById('deptTabsContainer');
if (toggleDeptBtn && deptTabsWrapper) {
    toggleDeptBtn.addEventListener('click', function(e) {
        e.preventDefault();
        deptTabsWrapper.classList.toggle('is-open');
        const icon = toggleDeptBtn.querySelector('.dept-toggle-icon');
        if (icon) {
            icon.classList.toggle('rotate-open');
        }
    });
}
</script>
