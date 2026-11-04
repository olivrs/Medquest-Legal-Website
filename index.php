<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/mailer.php';

$page = $_GET['page'] ?? (is_logged_in() ? 'agreements' : 'login');

if ($page === 'logout') {
    session_destroy();
    session_start();
    set_flash('success', 'Anda telah keluar dari sistem.');
    header("Location: " . base_url('?page=login'));
    exit;
}

$allowedPages = ['login', 'register', 'agreements', 'create', 'edit', 'detail', 'notifications', 'smtp_settings'];
if (!in_array($page, $allowedPages, true)) {
    $page = is_logged_in() ? 'agreements' : 'login';
}

// Start output buffering so pages can redirect cleanly even after header include
ob_start();
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/pages/' . $page . '.php';
require_once __DIR__ . '/includes/footer.php';
ob_end_flush();

