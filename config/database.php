<?php
// Set default timezone to WIB (Jakarta)
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_medquest_log');

// Daftar tahapan deadline resmi: H-90, H-60, H-30, H-14, H-7, H-3
define('DEADLINE_MILESTONES', [90, 60, 30, 14, 7, 3]);

// Detect dynamic BASE_URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/medquest-log/index.php'));
$baseUrl = rtrim($protocol . $host . $scriptDir, '/');
define('BASE_URL', $baseUrl);

try {
    $pdoInit = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdoInit = null;

    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $checkTable = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if (!$checkTable) {
        initialize_database_schema($pdo);
    }
    ensure_deadline_columns($pdo);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . htmlspecialchars($e->getMessage()));
}

/**
 * Pastikan kolom notifikasi H-90, H-60, H-30, H-14, H-7, H-3 tersedia di tabel agreements & email_notifications
 */
function ensure_deadline_columns(PDO $pdo) {
    $cols = $pdo->query("SHOW COLUMNS FROM agreements")->fetchAll(PDO::FETCH_COLUMN);
    $newCols = [
        'notif_h90_sent_at' => "ALTER TABLE agreements ADD COLUMN `notif_h90_sent_at` DATETIME DEFAULT NULL AFTER `created_by_log`",
        'notif_h60_sent_at' => "ALTER TABLE agreements ADD COLUMN `notif_h60_sent_at` DATETIME DEFAULT NULL AFTER `notif_h90_sent_at`",
        'notif_h30_sent_at' => "ALTER TABLE agreements ADD COLUMN `notif_h30_sent_at` DATETIME DEFAULT NULL AFTER `notif_h60_sent_at`",
        'notif_h14_sent_at' => "ALTER TABLE agreements ADD COLUMN `notif_h14_sent_at` DATETIME DEFAULT NULL AFTER `notif_h30_sent_at`",
        'file_attachment'   => "ALTER TABLE agreements ADD COLUMN `file_attachment` VARCHAR(255) DEFAULT NULL AFTER `note`",
    ];
    foreach ($newCols as $colName => $alterSql) {
        if (!in_array($colName, $cols, true)) {
            $pdo->exec($alterSql);
        }
    }
    // Ubah tipe_notif di email_notifications menjadi VARCHAR(20) agar mendukung H-90, H-60, H-30, H-14, H-7, H-3, H-1
    $pdo->exec("ALTER TABLE email_notifications MODIFY COLUMN `tipe_notif` VARCHAR(20) NOT NULL");
}

function initialize_database_schema(PDO $pdo) {
    $sqlFile = __DIR__ . '/../database.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE kode_singkatan = 'FJR' LIMIT 1");
    $stmt->execute();
    $fjr = $stmt->fetch();

    if (!$fjr) {
        $defaultPass = password_hash('medquest123', PASSWORD_DEFAULT);
        $insUser = $pdo->prepare("INSERT INTO users (nama_lengkap, kode_singkatan, email, password, departemen_default, role) VALUES (?, ?, ?, ?, ?, ?)");
        $insUser->execute([
            'Fajar (Admin LAC Medquest)',
            'FJR',
            'fjr.medquest@gmail.com',
            $defaultPass,
            'ALL',
            'admin'
        ]);
        $fjrId = (int)$pdo->lastInsertId();
    } else {
        $fjrId = (int)$fjr['id'];
    }
}

function base_url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: " . base_url('?page=login'));
        exit;
    }
}

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/**
 * Format tanggal sesuai form Medquest: "21 OCT 2026", "09 DEC 2025", atau "N/A"
 */
function format_date_medquest($date, $is_na = false) {
    if ($is_na || empty($date) || $date === '0000-00-00') {
        return 'N/A';
    }
    $ts = strtotime($date);
    if (!$ts) return 'N/A';
    return strtoupper(date('d M Y', $ts));
}

/**
 * Buat tanda Created By sesuai standar Medquest: "FJR, 21 OCT 2026"
 */
function format_created_by_stamp($kode_singkatan, $date = null) {
    $kode = strtoupper(trim($kode_singkatan));
    $ts = $date ? strtotime($date) : time();
    return $kode . ', ' . strtoupper(date('d M Y', $ts));
}

/**
 * Hitung selisih hari dari hari ini ke expiry_date
 */
function get_days_until_expiry($expiry_date) {
    $today = new DateTime(date('Y-m-d'));
    $exp = new DateTime(date('Y-m-d', strtotime($expiry_date)));
    $diff = $today->diff($exp);
    return (int)$diff->format('%r%a');
}

/**
 * Hitung jadwal tanggal deadline H-90, H-60, H-30, H-14, H-7, dan H-3 dari suatu Expiry Date
 */
function get_deadline_schedule($expiry_date, array $agreementRow = []): array {
    $milestones = DEADLINE_MILESTONES; // [90, 60, 30, 14, 7, 3]
    $expTs = strtotime($expiry_date);
    $todayStr = date('Y-m-d');
    $schedule = [];

    foreach ($milestones as $m) {
        $targetTs  = strtotime("-{$m} days", $expTs);
        $targetYmd = date('Y-m-d', $targetTs);
        $colSent   = "notif_h{$m}_sent_at";
        $isSent    = !empty($agreementRow[$colSent]);

        if ($targetYmd === $todayStr) {
            $state = 'today'; // Jatuh tepat hari ini!
        } elseif ($targetYmd < $todayStr) {
            $state = 'passed'; // Tanggal H-x sudah lewat
        } else {
            $state = 'upcoming'; // Tanggal H-x masih di masa depan
        }

        $schedule["H-{$m}"] = [
            'milestone' => $m,
            'code'      => "H-{$m}",
            'date_ymd'  => $targetYmd,
            'date_text' => strtoupper(date('d M Y', $targetTs)),
            'state'     => $state,
            'is_sent'   => $isSent,
            'sent_at'   => $agreementRow[$colSent] ?? null
        ];
    }

    return $schedule;
}

/**
 * Status warna & label yang ramah untuk pengguna baru (Newbie-Friendly)
 * Mendukung tahapan H-90, H-60, H-30, H-14, H-7, H-3
 */
function get_expiry_info($expiry_date) {
    $days = get_days_until_expiry($expiry_date);

    if ($days < 0) {
        return [
            'status' => 'expired',
            'row_class' => 'row-expired',
            'badge_class' => 'badge-expired',
            'label' => 'Sudah Expired (' . abs($days) . ' hari lalu)',
            'short_label' => 'EXPIRED (' . abs($days) . ' hr lalu)',
            'next_stage' => 'Kontrak Habis',
            'days' => $days
        ];
    } elseif ($days === 0) {
        return [
            'status' => 'warning',
            'row_class' => 'row-urgent',
            'badge_class' => 'badge-h3',
            'label' => 'Expired Hari Ini!',
            'short_label' => 'HARI INI EXPIRED',
            'next_stage' => 'H-0',
            'days' => 0
        ];
    } elseif ($days <= 3) {
        return [
            'status' => 'warning',
            'row_class' => 'row-urgent',
            'badge_class' => 'badge-h3',
            'label' => "Mendesak: H-{$days} ({$days} hari lagi)",
            'short_label' => ($days === 3 ? 'TEPAT H-3' : "Sisa {$days} Hari (Fase H-3)"),
            'next_stage' => 'H-3',
            'days' => $days
        ];
    } elseif ($days <= 7) {
        return [
            'status' => 'warning',
            'row_class' => 'row-warning',
            'badge_class' => 'badge-h7',
            'label' => "Segera Expired: H-{$days}",
            'short_label' => ($days === 7 ? 'TEPAT H-7' : "Sisa {$days} Hari (Fase H-7)"),
            'next_stage' => 'H-7',
            'days' => $days
        ];
    } elseif ($days <= 14) {
        return [
            'status' => 'warning',
            'row_class' => 'row-warning',
            'badge_class' => 'badge-h14',
            'label' => "Fase H-14 (Sisa {$days} hari)",
            'short_label' => ($days === 14 ? 'TEPAT H-14' : "Sisa {$days} Hari (Fase H-14)"),
            'next_stage' => 'H-14',
            'days' => $days
        ];
    } elseif ($days <= 30) {
        return [
            'status' => 'warning',
            'row_class' => 'row-warning-soft',
            'badge_class' => 'badge-h30',
            'label' => "Fase H-30 (Sisa {$days} hari)",
            'short_label' => ($days === 30 ? 'TEPAT H-30' : "Sisa {$days} Hari (Fase H-30)"),
            'next_stage' => 'H-30',
            'days' => $days
        ];
    } elseif ($days <= 60) {
        return [
            'status' => 'warning',
            'row_class' => 'row-warning-soft',
            'badge_class' => 'badge-h60',
            'label' => "Fase H-60 (Sisa {$days} hari)",
            'short_label' => ($days === 60 ? 'TEPAT H-60' : "Sisa {$days} Hari (Fase H-60)"),
            'next_stage' => 'H-60',
            'days' => $days
        ];
    } elseif ($days <= 90) {
        return [
            'status' => 'warning',
            'row_class' => 'row-warning-soft',
            'badge_class' => 'badge-h90',
            'label' => "Fase H-90 (Sisa {$days} hari)",
            'short_label' => ($days === 90 ? 'TEPAT H-90' : "Sisa {$days} Hari (Fase H-90)"),
            'next_stage' => 'H-90',
            'days' => $days
        ];
    } else {
        return [
            'status' => 'active',
            'row_class' => 'row-active',
            'badge_class' => 'badge-active',
            'label' => "Aman / Aktif ({$days} hari lagi)",
            'short_label' => "Aman ({$days} hari lagi)",
            'next_stage' => 'Menunggu H-90',
            'days' => $days
        ];
    }
}
