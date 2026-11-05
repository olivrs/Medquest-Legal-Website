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

    $pendingApprovalsCount = 0;
    if (is_superadmin()) {
        try {
            $pendingApprovalsCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
        } catch (Exception $e) {}
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medquest - Internal Legal Document Monitoring</title>
    <link rel="icon" href="<?= base_url('assets/img/logo_medquest.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>?v=mq_square_v4" rel="stylesheet">
</head>
<body>

<?php if (is_logged_in()):
    // Dropdown Departemen tertutup secara default agar sidebar ringkas & tidak perlu scroll
    $isDeptOpen = ($currPage === 'agreements' && $currDept !== 'ALL');
?>
<div class="app-layout">
    <!-- ========================================================================
         1. LEFT SIDEBAR (COMPACT, TANPA SCROLLBAR)
         ======================================================================== -->
    <aside class="app-sidebar no-print">
        <a href="<?= base_url('?page=agreements') ?>" class="sidebar-brand justify-content-center">
            <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest Logo" class="sidebar-logo-centered">
        </a>

        <div class="sidebar-menu">
            <div class="sidebar-section-title">MENU INTERNAL</div>

            <!-- Menu 1: Dashboard -->
            <a href="<?= base_url('?page=agreements') ?>"
               class="sidebar-link <?= ($currPage === 'agreements' && $currDept === 'ALL') ? 'active' : '' ?>">
                <span class="s-icon"><i class="fas fa-th-large"></i></span>
                <span>Dashboard</span>
            </a>

            <!-- Menu 2: Departemen (Dropdown Ringkas) -->
            <a class="sidebar-link d-flex justify-content-between align-items-center <?= ($currPage === 'agreements' && $currDept !== 'ALL') ? 'active' : '' ?>"
               data-bs-toggle="collapse" href="#deptSubmenu" role="button"
               aria-expanded="<?= $isDeptOpen ? 'true' : 'false' ?>" aria-controls="deptSubmenu">
                <div class="d-flex align-items-center gap-2">
                    <span class="s-icon"><i class="fas fa-building"></i></span>
                    <span>Departemen</span>
                </div>
                <i class="fas fa-chevron-down" style="font-size:0.68rem;opacity:0.75;"></i>
            </a>

            <div class="collapse <?= $isDeptOpen ? 'show' : '' ?>" id="deptSubmenu">
                <div class="sidebar-submenu">
                    <a href="<?= base_url('?page=agreements&dept=ALL') ?>"
                       class="sidebar-sublink sidebar-sublink-full <?= ($currPage === 'agreements' && $currDept === 'ALL') ? 'active' : '' ?>">
                        <span>Semua Dept</span>
                        <span class="sub-badge"><?= $userTotalDocs ?></span>
                    </a>
                    <div class="sidebar-dept-grid">
                        <?php foreach ($sidebarDepts as $sd):
                            $dCode = strtoupper($sd['kode_dept']);
                            $dCount = $userDeptCounts[$dCode] ?? 0;
                        ?>
                            <a href="<?= base_url('?page=agreements&dept=' . urlencode($dCode)) ?>"
                               class="sidebar-sublink <?= ($currPage === 'agreements' && $currDept === $dCode) ? 'active' : '' ?>"
                               title="<?= htmlspecialchars($sd['nama_dept']) ?>">
                                <span><?= htmlspecialchars($dCode) ?></span>
                                <span class="sub-badge"><?= $dCount ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Menu 3: Input Dokumen -->
            <a href="<?= base_url('?page=create' . ($currDept !== 'ALL' ? '&dept=' . urlencode($currDept) : '')) ?>"
               class="sidebar-link <?= $currPage === 'create' ? 'active' : '' ?>">
                <span class="s-icon"><i class="fas fa-plus"></i></span>
                <span>Input Dokumen</span>
            </a>

            <!-- Menu 4: Notifikasi Deadline -->
            <a href="<?= base_url('?page=notifications') ?>"
               class="sidebar-link d-flex justify-content-between align-items-center <?= $currPage === 'notifications' ? 'active' : '' ?>">
                <div class="d-flex align-items-center gap-2">
                    <span class="s-icon"><i class="fas fa-bell"></i></span>
                    <span>Deadline</span>
                </div>
                <?php if ($alertHCount > 0): ?>
                    <span class="badge rounded-0 bg-danger" style="font-size:0.66rem;"><?= $alertHCount ?></span>
                <?php endif; ?>
            </a>

            <?php if (is_superadmin()): ?>
            <!-- Menu 5: Persetujuan Akun (Superadmin) -->
            <a href="<?= base_url('?page=user_approvals') ?>"
               class="sidebar-link d-flex justify-content-between align-items-center <?= $currPage === 'user_approvals' ? 'active' : '' ?>">
                <div class="d-flex align-items-center gap-2">
                    <span class="s-icon"><i class="fas fa-user-shield"></i></span>
                    <span>Persetujuan Akun</span>
                </div>
                <?php if (!empty($pendingApprovalsCount) && $pendingApprovalsCount > 0): ?>
                    <span class="badge rounded-0 bg-warning text-dark fw-bold" style="font-size:0.66rem;"><?= $pendingApprovalsCount ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-user-card mb-1">
                <div class="sidebar-avatar"><?= htmlspecialchars($user['kode_singkatan']) ?></div>
                <div class="overflow-hidden flex-grow-1">
                    <div class="fw-bold text-white text-truncate" style="font-size:0.76rem;"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                    <div class="text-white-50 text-truncate" style="font-size:0.68rem;">Kode: <?= htmlspecialchars($user['kode_singkatan']) ?></div>
                </div>
                <span class="badge bg-white bg-opacity-10 text-white-50" style="font-size:0.66rem;" title="Total Dokumen Anda"><?= $userTotalDocs ?></span>
            </div>
            <a href="<?= base_url('?page=logout') ?>" class="sidebar-link text-white-50 py-1 px-2 mb-0"
               onclick="return confirm('Keluar dari akun <?= htmlspecialchars($user['kode_singkatan']) ?>?')">
                <span class="s-icon" style="width:22px;height:22px;"><i class="fas fa-sign-out-alt"></i></span>
                <span style="font-size:0.74rem;">Log Out</span>
            </a>
        </div>
    </aside>

    <!-- ========================================================================
         2. RIGHT MAIN COLUMN (CLEAN INTERNAL TOPBAR + CONTENT)
         ======================================================================== -->
    <div class="app-main">
        <!-- Main Internal Topbar Header -->
        <header id="main-header" class="app-topbar no-print">
            <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 440px;">
                <button type="button" id="sidebarToggleBtn" class="sidebar-toggle-btn" title="Buka / Tutup Sidebar" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <form method="GET" action="<?= base_url() ?>" class="topbar-search">
                    <input type="hidden" name="page" value="agreements">
                    <input type="hidden" name="dept" value="<?= htmlspecialchars($currDept) ?>">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="q" placeholder="Cari dokumen <?= htmlspecialchars($user['kode_singkatan']) ?> (nomor, customer, produk)..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                </form>
            </div>

            <div class="topbar-actions">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border fw-semibold text-dark dropdown-toggle d-inline-flex align-items-center gap-2 px-3" style="font-size:0.76rem;border-radius: 0;" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-building text-primary" style="font-size:0.72rem;"></i>
                        <span>Departemen: <strong><?= $currDept === 'ALL' ? 'Semua' : htmlspecialchars($currDept) ?></strong></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border" style="min-width:220px;border-radius: 0;">
                        <li>
                            <a class="dropdown-item small d-flex justify-content-between align-items-center py-1 <?= $currDept === 'ALL' ? 'fw-bold text-primary bg-light' : '' ?>" href="<?= base_url('?page=agreements&dept=ALL') ?>">
                                <span>Semua Departemen</span>
                                <span class="badge bg-secondary-subtle text-dark ms-2"><?= $userTotalDocs ?></span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php foreach ($sidebarDepts as $sd):
                            $dCode = strtoupper($sd['kode_dept']);
                            $dCount = $userDeptCounts[$dCode] ?? 0;
                        ?>
                            <li>
                                <a class="dropdown-item small d-flex justify-content-between align-items-center py-1 <?= $currDept === $dCode ? 'fw-bold text-primary bg-light' : '' ?>" href="<?= base_url('?page=agreements&dept=' . urlencode($dCode)) ?>">
                                    <span><strong><?= htmlspecialchars($dCode) ?></strong> &mdash; <?= htmlspecialchars($sd['nama_dept']) ?></span>
                                    <span class="badge bg-light text-dark border ms-2"><?= $dCount ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="d-flex align-items-center gap-2 border-start ps-3">
                    <div class="sidebar-avatar" style="width:28px;height:28px;font-size:0.7rem;background:#2b579a;">
                        <?= htmlspecialchars($user['kode_singkatan']) ?>
                    </div>
                    <div class="d-none d-md-block" style="line-height:1.15;">
                        <div class="fw-bold text-dark" style="font-size:0.76rem;"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                        <div class="text-muted" style="font-size:0.66rem;"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.68rem;">
                        <?= htmlspecialchars($user['kode_singkatan']) ?>
                    </span>
                </div>

                <?php if (is_superadmin()): ?>
                <a href="<?= base_url('?page=user_approvals') ?>" class="topbar-icon-btn position-relative" title="Persetujuan Akun User (Superadmin)">
                    <i class="fas fa-user-shield"></i>
                    <?php if (!empty($pendingApprovalsCount) && $pendingApprovalsCount > 0): ?>
                        <span class="dot-badge" style="background:#f59e0b !important;"></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <a href="<?= base_url('?page=smtp_settings') ?>" class="topbar-icon-btn" title="Pengaturan Email Otomatis (SMTP)">
                    <i class="fas fa-envelope-open-text"></i>
                </a>

                <a href="<?= base_url('?page=notifications') ?>" class="topbar-icon-btn" title="Notifikasi Email Deadline">
                    <i class="far fa-bell"></i>
                    <?php if ($alertHCount > 0): ?>
                        <span class="dot-badge"></span>
                    <?php endif; ?>
                </a>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="app-content">
            <!-- Toast Container untuk Notifikasi Pengiriman Email Otomatis Background -->
            <div id="autoEmailToastContainer" class="no-print" style="position:fixed;bottom:20px;right:20px;z-index:9999;max-width:420px;"></div>

            <?php
            $flash = get_flash();
            if ($flash):
                $alertType = $flash['type'] === 'error' ? 'danger' : ($flash['type'] ?: 'info');
            ?>
                <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show shadow-sm border-0 mb-3 py-2 small no-print" role="alert">
                    <i class="fas <?= $alertType === 'success' ? 'fa-check-circle' : ($alertType === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle') ?> me-2"></i>
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding:0.65rem;"></button>
                </div>
            <?php endif; ?>
<?php else: ?>
    <?php
    $flash = get_flash();
    if ($flash):
        $alertType = $flash['type'] === 'error' ? 'danger' : ($flash['type'] ?: 'info');
    ?>
        <div class="container pt-3" style="position:fixed;top:16px;left:0;right:0;z-index:9999;max-width:520px;">
            <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show shadow border-0 py-2 small" role="alert">
                <?= $flash['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" style="padding:0.65rem;"></button>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
