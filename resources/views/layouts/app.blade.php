<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Medquest - Internal Legal Document Monitoring</title>
    <link rel="icon" href="<?= base_url('assets/img/logo_medquest.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>?v=mq_square_v5_mobile" rel="stylesheet">
</head>
<body>

<?php if (is_logged_in()):
    $isDeptOpen = ($currPage === 'agreements' && $currDept !== 'ALL');
?>
<div class="app-layout">
    <div class="sidebar-backdrop no-print" id="sidebarBackdrop"></div>
    <aside class="app-sidebar no-print">
        <button type="button" class="sidebar-close-mobile" id="sidebarCloseBtn" aria-label="Tutup Menu">
            <i class="fas fa-times"></i>
        </button>
        <a href="<?= base_url('?page=agreements') ?>" class="sidebar-brand justify-content-center">
            <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest Logo" class="sidebar-logo-centered">
        </a>

        <div class="sidebar-menu">
            <div class="sidebar-section-title">MENU INTERNAL</div>

            <a href="<?= base_url('?page=agreements') ?>"
               class="sidebar-link <?= ($currPage === 'agreements' && $currDept === 'ALL') ? 'active' : '' ?>">
                <span class="s-icon"><i class="fas fa-th-large"></i></span>
                <span>Dashboard</span>
            </a>

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

            <a href="<?= base_url('?page=create' . ($currDept !== 'ALL' ? '&dept=' . urlencode($currDept) : '')) ?>"
               class="sidebar-link <?= $currPage === 'create' ? 'active' : '' ?>">
                <span class="s-icon"><i class="fas fa-plus"></i></span>
                <span>Input Dokumen</span>
            </a>

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

    <div class="app-main">
        <header id="main-header" class="app-topbar no-print">
            <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 440px;">
                <button type="button" id="sidebarToggleBtn" class="sidebar-toggle-btn" title="Buka / Tutup Sidebar" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <form method="GET" action="<?= base_url() ?>" class="topbar-search">
                    <input type="hidden" name="page" value="agreements">
                    <input type="hidden" name="dept" value="<?= htmlspecialchars($currDept) ?>">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="q" placeholder="Cari dokumen <?= htmlspecialchars($user['kode_singkatan']) ?> (nomor, customer, produk)..." value="<?= htmlspecialchars(request('q', '')) ?>">
                </form>
            </div>

            <div class="topbar-actions">
                <div class="dropdown mq-topbar-dept">
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

                <div class="d-flex align-items-center gap-2 border-start ps-3 mq-topbar-user">
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

        <main class="app-content">
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

            @yield('content')
        </main>

        <footer class="mq-footer no-print">
            <div class="mq-footer-main">
                <div class="mq-fluid-bar mq-footer-grid">
                    <div class="mq-footer-col mq-footer-about">
                        <div class="mq-footer-logo-box">
                            <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="PT Medquest Jaya Global">
                        </div>
                        <p class="mq-footer-desc">
                            PT. Medquest Jaya Global since its establishment in 2004 concentrating in distributing medical device and pharmaceutical, by promoting innovation and latest technology. We currently distribute more than 30 leading brands in the world, which includes the equipment in the field of blood bank, diagnostics, research and industry.
                        </p>
                        <ul class="mq-footer-contacts">
                            <li><i class="fas fa-phone-volume"></i> <span>+62-21 39842961</span></li>
                            <li><i class="fas fa-envelope"></i> <a href="mailto:info@medquest.co.id">info@medquest.co.id</a></li>
                            <li><i class="fas fa-map-marker-alt"></i> <span>Menara Salemba 6th Fl Zone 1, Jl. Salemba Raya No. 5 - 5A, Jakarta Pusat 10440</span></li>
                        </ul>
                        <div class="mq-iso-badges">
                            <span class="mq-iso-pill"><i class="fas fa-certificate"></i> ISO 9001:2015 SGS Certified</span>
                            <span class="mq-iso-pill"><i class="fas fa-shield-alt"></i> ISO 37001:2016 Anti-Bribery</span>
                        </div>
                    </div>

                    <div class="mq-footer-col mq-footer-links">
                        <h4 class="mq-footer-title">Explore</h4>
                        <ul>
                            <li><a href="<?= base_url('?page=agreements') ?>">Dashboard Agreement</a></li>
                            <li><a href="<?= base_url('?page=create') ?>">Input Agreement Baru</a></li>
                            <li><a href="<?= base_url('?page=notifications') ?>">Monitoring Deadline</a></li>
                            <li><a href="<?= base_url('?page=smtp_settings') ?>">Pengaturan Email SMTP</a></li>
                            <li><a href="https://medquest.co.id/" target="_blank" rel="noopener">Official Website (medquest.co.id)</a></li>
                            <li><a href="https://medquest.co.id/products/" target="_blank" rel="noopener">Products &amp; Calibration</a></li>
                            <li><a href="https://medquest.co.id/whistle-blowing-system/" target="_blank" rel="noopener">Whistle Blowing System (WBS)</a></li>
                        </ul>
                    </div>

                    <div class="mq-footer-col mq-footer-hotline">
                        <h4 class="mq-footer-title">Customer &amp; Internal Support</h4>
                        <div class="mq-hotline-card">
                            <div class="mq-hotline-icon"><i class="fas fa-headset"></i></div>
                            <div>
                                <div class="mq-hotline-label">Hotline Service 24 Hours</div>
                                <div class="mq-hotline-number">+62-21-29260000</div>
                            </div>
                        </div>
                        <div class="mq-footer-socials">
                            <a href="https://www.instagram.com/medquest_id/" target="_blank" rel="noopener" title="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="https://www.facebook.com/MedquestID" target="_blank" rel="noopener" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="https://twitter.com/medquest_id" target="_blank" rel="noopener" title="X / Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="https://medquest.co.id/contact-us/" target="_blank" rel="noopener" title="Contact Us"><i class="fas fa-external-link-alt"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mq-footer-bottom">
                <div class="mq-fluid-bar">
                    &copy; <?= date('Y') ?> <strong>PT Medquest Jaya Global</strong> &middot; Document System Monitoring (MJG-FOR-LAC.12-001-03). All Rights Reserved.
                </div>
            </div>
        </footer>
    </div>
</div>
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

    @yield('content')

    <footer class="mq-auth-footer no-print">
        &copy; <?= date('Y') ?> <strong>PT Medquest Jaya Global</strong> &middot; Menara Salemba 6th Fl Zone 1, Jakarta Pusat &middot; Document System Monitoring
    </footer>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if (is_logged_in()): ?>
<script>
(function() {
    const layoutEl = document.querySelector('.app-layout');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const backdrop = document.getElementById('sidebarBackdrop');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    if (!layoutEl || !toggleBtn) return;

    const mobileQuery = window.matchMedia('(max-width: 991.98px)');
    const isMobile = () => mobileQuery.matches;

    if (localStorage.getItem('mq_sidebar_collapsed') === '1') {
        layoutEl.classList.add('sidebar-collapsed');
    }

    function closeMobileSidebar() {
        layoutEl.classList.remove('sidebar-mobile-open');
        document.body.classList.remove('mq-no-scroll');
    }

    toggleBtn.addEventListener('click', function() {
        if (isMobile()) {
            const willOpen = !layoutEl.classList.contains('sidebar-mobile-open');
            layoutEl.classList.toggle('sidebar-mobile-open', willOpen);
            document.body.classList.toggle('mq-no-scroll', willOpen);
            return;
        }
        layoutEl.classList.toggle('sidebar-collapsed');
        const isCollapsed = layoutEl.classList.contains('sidebar-collapsed');
        localStorage.setItem('mq_sidebar_collapsed', isCollapsed ? '1' : '0');
    });

    if (backdrop) backdrop.addEventListener('click', closeMobileSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeMobileSidebar);

    document.querySelectorAll('.app-sidebar a').forEach(function(link) {
        link.addEventListener('click', function() {
            if (isMobile() && link.getAttribute('data-bs-toggle') !== 'collapse') {
                closeMobileSidebar();
            }
        });
    });

    mobileQuery.addEventListener('change', function(e) {
        if (!e.matches) closeMobileSidebar();
    });
})();

function checkAndSendAutoExpiryEmails() {
    fetch('<?= base_url('cron_notify.php') ?>', { cache: 'no-store' })
        .then(res => res.json())
        .then(data => {
            if (data && data.sent_count > 0 && Array.isArray(data.details)) {
                const container = document.getElementById('autoEmailToastContainer');
                if (!container) return;
                data.details.forEach(item => {
                    const toastEl = document.createElement('div');
                    toastEl.className = 'alert alert-success shadow-lg border-success mb-2 p-3';
                    toastEl.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold"><i class="fas fa-paper-plane me-1"></i> Email Otomatis ${item.tipe} Terkirim!</div>
                                <div class="small mt-1">Dokumen <strong>${item.customer || ''}</strong> otomatis dikirim ke <strong>${item.recipient} (${item.kode})</strong>.</div>
                            </div>
                            <button type="button" class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>
                        </div>
                    `;
                    container.appendChild(toastEl);
                });
            }
        })
        .catch(() => {});
}

setTimeout(checkAndSendAutoExpiryEmails, 2000);
setInterval(checkAndSendAutoExpiryEmails, 20000);
</script>
<?php endif; ?>
</body>
</html>
