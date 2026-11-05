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
    <title>Medquest - Document System Monitoring (Log of Cooperation Agreement)</title>
    <link rel="icon" href="<?= base_url('assets/img/logo_medquest.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>?v=mq19" rel="stylesheet">
</head>
<body>

<?php if (is_logged_in()):
    $isDeptOpen = ($currPage === 'agreements');
?>
<div class="app-layout">
    <!-- ========================================================================
         1. LEFT SIDEBAR (TETAP DI SEBELAH KIRI DENGAN MENU LENGKAP SEBELUMNYA)
         ======================================================================== -->
    <aside class="app-sidebar no-print">
        <a href="<?= base_url('?page=agreements') ?>" class="sidebar-brand justify-content-center">
            <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest Logo" class="sidebar-logo-centered">
        </a>

        <div class="sidebar-menu">
            <div class="sidebar-section-title">MAIN</div>

            <!-- Menu 1: Dashboard -->
            <a href="<?= base_url('?page=agreements') ?>"
               class="sidebar-link <?= ($currPage === 'agreements' && $currDept === 'ALL') ? 'active' : '' ?>">
                <span class="s-icon"><i class="fas fa-th-large"></i></span>
                <span>Dashboard</span>
            </a>

            <!-- Menu 2: Departemen (Dropdown Submenu) -->
            <a class="sidebar-link d-flex justify-content-between align-items-center <?= ($currPage === 'agreements' && $currDept !== 'ALL') ? 'active' : '' ?>"
               data-bs-toggle="collapse" href="#deptSubmenu" role="button"
               aria-expanded="<?= $isDeptOpen ? 'true' : 'false' ?>" aria-controls="deptSubmenu">
                <div class="d-flex align-items-center gap-2">
                    <span class="s-icon"><i class="fas fa-building"></i></span>
                    <span>Departemen</span>
                </div>
                <i class="fas fa-chevron-down small transition-chevron"></i>
            </a>

            <div class="collapse <?= $isDeptOpen ? 'show' : '' ?>" id="deptSubmenu">
                <div class="sidebar-submenu">
                    <a href="<?= base_url('?page=agreements&dept=ALL') ?>"
                       class="sidebar-sublink <?= ($currPage === 'agreements' && $currDept === 'ALL') ? 'active' : '' ?>">
                        <span>Semua Departemen</span>
                        <span class="sub-badge"><?= $userTotalDocs ?></span>
                    </a>
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

            <!-- Menu 3: Input Agreement -->
            <a href="<?= base_url('?page=create' . ($currDept !== 'ALL' ? '&dept=' . urlencode($currDept) : '')) ?>"
               class="sidebar-link <?= $currPage === 'create' ? 'active' : '' ?>">
                <span class="s-icon"><i class="fas fa-file-circle-plus"></i></span>
                <span>Input Agreement</span>
            </a>

            <!-- Menu 4: Notifikasi Deadline -->
            <a href="<?= base_url('?page=notifications') ?>"
               class="sidebar-link d-flex justify-content-between align-items-center <?= $currPage === 'notifications' ? 'active' : '' ?>">
                <div class="d-flex align-items-center gap-2">
                    <span class="s-icon"><i class="fas fa-bell"></i></span>
                    <span>Deadline</span>
                </div>
                <?php if ($alertHCount > 0): ?>
                    <span class="badge rounded-pill bg-danger"><?= $alertHCount ?></span>
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
                    <span class="badge rounded-pill bg-warning text-dark fw-bold"><?= $pendingApprovalsCount ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>

            <div class="mt-3 px-2">
                <a href="<?= base_url('?page=create' . ($currDept !== 'ALL' ? '&dept=' . urlencode($currDept) : '')) ?>"
                   class="btn btn-add-agreement w-100 fw-bold btn-sm py-2 shadow-sm">
                    <i class="fas fa-plus-circle me-1"></i> + Tambah Dokumen
                </a>
            </div>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-user-card mb-2">
                <div class="sidebar-avatar"><?= htmlspecialchars($user['kode_singkatan']) ?></div>
                <div class="overflow-hidden flex-grow-1">
                    <div class="fw-bold text-white text-truncate" style="font-size:0.8rem;"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                    <div class="text-white-50 text-truncate" style="font-size:0.7rem;">Kode: <?= htmlspecialchars($user['kode_singkatan']) ?></div>
                </div>
                <span class="badge bg-white bg-opacity-25 text-white" title="Total Dokumen Anda"><?= $userTotalDocs ?></span>
            </div>
            <a href="<?= base_url('?page=logout') ?>" class="sidebar-link text-white-50 py-1 px-2"
               onclick="return confirm('Keluar dari akun <?= htmlspecialchars($user['kode_singkatan']) ?>?')">
                <span class="s-icon" style="width:24px;height:24px;"><i class="fas fa-sign-out-alt"></i></span>
                <span class="small">Log Out</span>
            </a>
        </div>
    </aside>

    <!-- ========================================================================
         2. RIGHT MAIN COLUMN (TOP BAR MEDQUEST.CO.ID + HEADER + CONTENT + FOOTER)
         ======================================================================== -->
    <div class="app-main">
        <!-- Top Utility Bar ala https://medquest.co.id/ (#top-header) -->
        <div id="top-header" class="mq-top-header no-print">
            <div class="mq-fluid-bar">
                <div class="mq-top-left">
                    <span class="mq-top-item"><i class="fas fa-phone-volume"></i> +62 21 39842961</span>
                    <a href="mailto:info@medquest.co.id" class="mq-top-item"><i class="fas fa-envelope"></i> info@medquest.co.id</a>
                    <span class="mq-top-item mq-hide-mobile"><i class="fas fa-map-marker-alt"></i> Menara Salemba 6th Fl Zone 1, Jakarta Pusat</span>
                </div>
                <div class="mq-top-right">
                    <span class="mq-hotline-pill"><i class="fas fa-headset"></i> Hotline 24h: +62-21-29260000</span>
                    <div class="mq-top-socials">
                        <a href="https://www.facebook.com/MedquestID" target="_blank" rel="noopener" title="Facebook Medquest"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/medquest_id" target="_blank" rel="noopener" title="X / Twitter Medquest"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.instagram.com/medquest_id/" target="_blank" rel="noopener" title="Instagram Medquest"><i class="fab fa-instagram"></i></a>
                        <a href="https://medquest.co.id/" target="_blank" rel="noopener" title="Website Resmi medquest.co.id"><i class="fas fa-globe"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Topbar Header ala https://medquest.co.id/ (#main-header) -->
        <header id="main-header" class="app-topbar no-print">
            <div class="d-flex align-items-center gap-3 flex-grow-1" style="max-width: 460px;">
                <button type="button" id="sidebarToggleBtn" class="sidebar-toggle-btn" title="Buka / Tutup Sidebar" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <form method="GET" action="<?= base_url() ?>" class="topbar-search">
                    <input type="hidden" name="page" value="agreements">
                    <input type="hidden" name="dept" value="<?= htmlspecialchars($currDept) ?>">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="q" placeholder="Cari dokumen milik <?= htmlspecialchars($user['kode_singkatan']) ?> (nomor, customer, produk, S/N)..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                </form>
            </div>

            <div class="topbar-actions">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border-0 fw-semibold text-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Departemen (<?= htmlspecialchars($currDept) ?>)
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><a class="dropdown-item small fw-bold" href="<?= base_url('?page=agreements&dept=ALL') ?>">Semua Departemen (<?= $userTotalDocs ?>)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($sidebarDepts as $sd):
                            $dCode = strtoupper($sd['kode_dept']);
                            $dCount = $userDeptCounts[$dCode] ?? 0;
                        ?>
                            <li>
                                <a class="dropdown-item small d-flex justify-content-between align-items-center" href="<?= base_url('?page=agreements&dept=' . urlencode($dCode)) ?>">
                                    <span><strong><?= htmlspecialchars($dCode) ?></strong> - <?= htmlspecialchars($sd['nama_dept']) ?></span>
                                    <span class="badge bg-light text-dark ms-2"><?= $dCount ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="d-flex align-items-center gap-2 border-start ps-3">
                    <div class="sidebar-avatar" style="width:30px;height:30px;font-size:0.72rem;background:#2458f1;">
                        <?= htmlspecialchars($user['kode_singkatan']) ?>
                    </div>
                    <div class="d-none d-md-block" style="line-height:1.15;">
                        <div class="fw-bold text-dark" style="font-size:0.78rem;"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                        <div class="text-muted" style="font-size:0.68rem;"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.7rem;">
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
                <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show shadow-sm border-0 mb-3 no-print" role="alert">
                    <i class="fas <?= $alertType === 'success' ? 'fa-check-circle' : ($alertType === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle') ?> me-2"></i>
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
<?php else: ?>
    <!-- Top Utility Bar ala https://medquest.co.id/ untuk halaman Login / Register -->
    <div id="top-header" class="mq-top-header no-print">
        <div class="mq-container">
            <div class="mq-top-left">
                <span class="mq-top-item"><i class="fas fa-phone-volume"></i> +62 21 39842961</span>
                <a href="mailto:info@medquest.co.id" class="mq-top-item"><i class="fas fa-envelope"></i> info@medquest.co.id</a>
                <span class="mq-top-item mq-hide-mobile"><i class="fas fa-map-marker-alt"></i> Menara Salemba 6th Fl Zone 1, Jakarta Pusat</span>
            </div>
            <div class="mq-top-right">
                <span class="mq-hotline-pill"><i class="fas fa-headset"></i> Hotline 24h: +62-21-29260000</span>
                <div class="mq-top-socials">
                    <a href="https://www.facebook.com/MedquestID" target="_blank" rel="noopener" title="Facebook Medquest"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/medquest_id" target="_blank" rel="noopener" title="X / Twitter Medquest"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/medquest_id/" target="_blank" rel="noopener" title="Instagram Medquest"><i class="fab fa-instagram"></i></a>
                    <a href="https://medquest.co.id/" target="_blank" rel="noopener" title="Website Resmi medquest.co.id"><i class="fas fa-globe"></i></a>
                </div>
            </div>
        </div>
    </div>
    <?php
    $flash = get_flash();
    if ($flash):
        $alertType = $flash['type'] === 'error' ? 'danger' : ($flash['type'] ?: 'info');
    ?>
        <div class="container pt-3" style="position:fixed;top:42px;left:0;right:0;z-index:9999;max-width:520px;">
            <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show shadow border-0" role="alert">
                <?= $flash['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
