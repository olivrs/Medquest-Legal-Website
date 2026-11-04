<?php
$currPage = $_GET['page'] ?? 'agreements';
$currDept = strtoupper(trim($_GET['dept'] ?? 'ALL'));
$user = current_user();

$alertHCount = 0;
$totalNotifCount = 0;
$sidebarDepts = [];
$userDeptCounts = [];
$userTotalDocs = 0;

if (is_logged_in()) {
    $uId   = (int)$user['id'];
    $uCode = strtoupper($user['kode_singkatan']);

    // Hitung hanya dokumen milik user yang sedang login (misal RYN hanya melihat milik RYN)
    $stmtAlert = $pdo->prepare("
        SELECT COUNT(*) FROM agreements 
        WHERE (created_by_user_id = ? OR created_by_code = ?)
          AND DATEDIFF(expiry_date, CURDATE()) BETWEEN 0 AND 90
    ");
    $stmtAlert->execute([$uId, $uCode]);
    $alertHCount = (int)$stmtAlert->fetchColumn();

    $stmtNotif = $pdo->prepare("SELECT COUNT(*) FROM email_notifications WHERE user_id = ? OR recipient_code = ?");
    $stmtNotif->execute([$uId, $uCode]);
    $totalNotifCount = (int)$stmtNotif->fetchColumn();

    $sidebarDepts = $pdo->query("SELECT * FROM departments ORDER BY id ASC")->fetchAll();

    // Hitung jumlah dokumen per departemen khusus untuk user yang login
    $stmtDC = $pdo->prepare("
        SELECT department, COUNT(*) AS total 
        FROM agreements 
        WHERE (created_by_user_id = ? OR created_by_code = ?)
        GROUP BY department
    ");
    $stmtDC->execute([$uId, $uCode]);
    foreach ($stmtDC->fetchAll() as $r) {
        $userDeptCounts[strtoupper($r['department'])] = (int)$r['total'];
        $userTotalDocs += (int)$r['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medquest LAC - Log of Cooperation Agreement</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<?php if (is_logged_in()):
    $isDeptFiltered = ($currPage === 'agreements' && $currDept !== 'ALL');
?>
<div class="mq-app-wrapper">
    <!-- ========================================================================
         LEFT SIDEBAR (Connect Plus Layout with Compact Departemen Dropdown)
         ======================================================================== -->
    <aside class="mq-sidebar" id="mqSidebar">
        <a href="<?= base_url('?page=agreements') ?>" class="mq-sidebar-brand">
            <span class="brand-logo-box">
                <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest" onerror="this.style.display='none'">
            </span>
            <span class="brand-text">Medquest LAC</span>
        </a>

        <div class="mq-sidebar-section">Main</div>
        <ul class="mq-sidebar-nav">
            <!-- 1. Dashboard -->
            <li class="mq-sidebar-item">
                <a href="<?= base_url('?page=agreements') ?>" class="mq-sidebar-link <?= ($currPage === 'agreements' && $currDept === 'ALL') ? 'active' : '' ?>">
                    <span class="mq-sidebar-icon"><i class="fas fa-th-large"></i></span>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- 2. Dropdown Departemen (Ringkas & Tidak Memanjang) -->
            <li class="mq-sidebar-item">
                <a class="mq-sidebar-link <?= $isDeptFiltered ? 'active' : '' ?>"
                   data-bs-toggle="collapse"
                   href="#sidebarDeptDropdown"
                   role="button"
                   aria-expanded="<?= $isDeptFiltered ? 'true' : 'false' ?>"
                   aria-controls="sidebarDeptDropdown">
                    <span class="mq-sidebar-icon"><i class="fas fa-building"></i></span>
                    <span>Departemen <?= $isDeptFiltered ? '(' . htmlspecialchars($currDept) . ')' : '' ?></span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </a>
                <div class="collapse <?= $isDeptFiltered ? 'show' : '' ?>" id="sidebarDeptDropdown">
                    <ul class="mq-sidebar-submenu">
                        <li>
                            <a href="<?= base_url('?page=agreements&dept=ALL') ?>"
                               class="mq-sidebar-sublink <?= ($currPage === 'agreements' && $currDept === 'ALL') ? 'active' : '' ?>">
                                <span>Semua Departemen</span>
                                <span class="badge bg-secondary bg-opacity-25 text-light rounded-pill"><?= $userTotalDocs ?></span>
                            </a>
                        </li>
                        <?php foreach ($sidebarDepts as $sd):
                            $dCode = strtoupper($sd['kode_dept']);
                            $dCount = $userDeptCounts[$dCode] ?? 0;
                        ?>
                            <li>
                                <a href="<?= base_url('?page=agreements&dept=' . urlencode($dCode)) ?>"
                                   class="mq-sidebar-sublink <?= ($currPage === 'agreements' && $currDept === $dCode) ? 'active' : '' ?>">
                                    <span><?= htmlspecialchars($dCode) ?></span>
                                    <span class="badge bg-secondary bg-opacity-25 text-light rounded-pill"><?= $dCount ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </li>

            <!-- 3. Input Data Baru -->
            <li class="mq-sidebar-item">
                <a href="<?= base_url('?page=create') ?>" class="mq-sidebar-link <?= ($currPage === 'create') ? 'active' : '' ?>">
                    <span class="mq-sidebar-icon"><i class="fas fa-file-circle-plus"></i></span>
                    <span>Input Agreement</span>
                </a>
            </li>

            <!-- 4. Jadwal Deadline & Email -->
            <li class="mq-sidebar-item">
                <a href="<?= base_url('?page=notifications') ?>" class="mq-sidebar-link <?= ($currPage === 'notifications') ? 'active' : '' ?>">
                    <span class="mq-sidebar-icon"><i class="fas fa-bell"></i></span>
                    <span>Deadline H-90 s/d H-3</span>
                    <?php if ($alertHCount > 0): ?>
                        <span class="badge bg-danger ms-auto"><?= $alertHCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <div class="mq-sidebar-cta">
            <a href="<?= base_url('?page=create' . ($currDept !== 'ALL' ? '&dept=' . urlencode($currDept) : '')) ?>" class="btn-cta">
                <i class="fas fa-plus-circle me-1"></i> + Tambah Dokumen
            </a>
        </div>

        <!-- Bottom User Profile & Settings -->
        <div class="mq-sidebar-footer">
            <div class="mq-sidebar-user">
                <div class="d-flex align-items-center gap-2">
                    <span class="mq-user-avatar"><?= htmlspecialchars($user['kode_singkatan']) ?></span>
                    <div style="line-height:1.2;">
                        <div class="fw-bold text-white" style="font-size:0.84rem;"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                        <div style="font-size:0.7rem;color:#94a3b8;">Kode: <?= htmlspecialchars($user['kode_singkatan']) ?></div>
                    </div>
                </div>
                <span class="badge bg-primary rounded-2 px-2 py-1" title="Total Dokumen <?= htmlspecialchars($user['kode_singkatan']) ?>"><?= $userTotalDocs ?></span>
            </div>

            <ul class="mq-sidebar-nav px-0">
                <li class="mq-sidebar-item">
                    <a href="<?= base_url('?page=logout') ?>" class="mq-sidebar-link py-2 px-2" onclick="return confirm('Keluar dari akun <?= htmlspecialchars($user['kode_singkatan']) ?>?')">
                        <i class="fas fa-sign-out-alt text-danger" style="width:20px;"></i>
                        <span>Log Out</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- ========================================================================
         RIGHT MAIN PANEL (Topbar + Content)
         ======================================================================== -->
    <div class="mq-main-panel">
        <!-- Top White Bar -->
        <header class="mq-topbar no-print">
            <div class="d-flex align-items-center gap-3 flex-grow-1">
                <button type="button" class="btn btn-sm btn-light border d-lg-none" onclick="document.getElementById('mqSidebar').classList.toggle('show')">
                    <i class="fas fa-bars"></i>
                </button>

                <form method="GET" action="<?= base_url() ?>" class="mq-topbar-search">
                    <input type="hidden" name="page" value="agreements">
                    <input type="hidden" name="dept" value="<?= htmlspecialchars($currDept) ?>">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="q" placeholder="Cari dokumen milik <?= htmlspecialchars($user['kode_singkatan']) ?> (nomor, customer, produk)..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                </form>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Dropdown Departemen di Topbar -->
                <div class="dropdown d-none d-md-block">
                    <a class="text-dark text-decoration-none small fw-semibold dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        Departemen (<?= htmlspecialchars($currDept) ?>)
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li>
                            <a class="dropdown-item small fw-bold d-flex justify-content-between align-items-center" href="<?= base_url('?page=agreements&dept=ALL') ?>">
                                <span>Semua Departemen</span>
                                <span class="badge bg-light text-dark border ms-2"><?= $userTotalDocs ?></span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($sidebarDepts as $sd):
                            $dCode = strtoupper($sd['kode_dept']);
                            $dCnt  = $userDeptCounts[$dCode] ?? 0;
                        ?>
                            <li>
                                <a class="dropdown-item small d-flex justify-content-between align-items-center" href="<?= base_url('?page=agreements&dept=' . urlencode($dCode)) ?>">
                                    <span><strong><?= htmlspecialchars($dCode) ?></strong> &mdash; <?= htmlspecialchars($sd['nama_dept']) ?></span>
                                    <span class="badge bg-light text-dark border ms-2"><?= $dCnt ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- User Info -->
                <div class="d-flex align-items-center gap-2 border-start ps-3">
                    <span class="mq-user-avatar" style="width:32px;height:32px;font-size:0.75rem;"><?= htmlspecialchars($user['kode_singkatan']) ?></span>
                    <div class="d-none d-sm-block" style="line-height:1.15;">
                        <div class="fw-bold text-dark" style="font-size:0.83rem;"><?= htmlspecialchars($user['nama_lengkap']) ?> <span class="badge bg-primary-subtle text-primary ms-1"><?= htmlspecialchars($user['kode_singkatan']) ?></span></div>
                        <div class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>

                <!-- Icon Mail & Bell -->
                <a href="<?= base_url('?page=notifications') ?>" class="text-secondary position-relative ms-1" title="Riwayat Email Terkirim">
                    <i class="far fa-envelope fs-6"></i>
                </a>
                <a href="<?= base_url('?page=notifications') ?>" class="text-secondary position-relative ms-1" title="Jadwal Deadline & Notifikasi">
                    <i class="far fa-bell fs-6"></i>
                    <?php if ($alertHCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                    <?php endif; ?>
                </a>
            </div>
        </header>

        <!-- Main Content Container -->
        <div class="mq-content-area">
            <div id="autoEmailToastContainer" class="no-print" style="position:fixed;bottom:20px;right:20px;z-index:9999;max-width:420px;"></div>

            <?php
            $flash = get_flash();
            if ($flash):
                $alertType = $flash['type'] === 'error' ? 'danger' : ($flash['type'] ?: 'info');
            ?>
                <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show shadow-sm border-0 mb-3 no-print" role="alert">
                    <i class="fas <?= $alertType === 'success' ? 'fa-check-circle' : ($alertType === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle') ?> me-2"></i>
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
<?php else: ?>
    <!-- Jika belum login (Halaman Login / Register) -->
    <?php
    $flash = get_flash();
    if ($flash):
        $alertType = $flash['type'] === 'error' ? 'danger' : ($flash['type'] ?: 'info');
    ?>
        <div class="container pt-3" style="position:fixed;top:10px;left:0;right:0;z-index:9999;max-width:520px;">
            <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show shadow border-0" role="alert">
                <?= $flash['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
