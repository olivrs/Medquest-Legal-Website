<?php
/**
 * Script Otomatis Pengecekan & Pengiriman Email H-7, H-3, dan H-1
 * Bisa dijalankan lewat CLI / Task Scheduler:
 * C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe C:\laragon\www\medquest-log\cron_notify.php
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/mailer.php';

$results = run_auto_expiry_notifications($pdo);

if (php_sapi_name() === 'cli') {
    echo "=== MEDQUEST EXPIRY NOTIFICATION CHECK (H-7, H-3, H-1) ===\n";
    echo "Waktu: " . date('Y-m-d H:i:s') . "\n";
    echo "Total Email Dikirim Baru: " . count($results) . "\n";
    foreach ($results as $r) {
        echo " - [{$r['tipe']}] -> {$r['recipient']} ({$r['kode']}) : {$r['status']}\n";
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'sent_count' => count($results),
        'details' => $results
    ], JSON_PRETTY_PRINT);
}

