<?php
// Set default timezone to WIB (Jakarta)
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
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
    ensure_user_approval_columns($pdo);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . htmlspecialchars($e->getMessage()));
}

/**
 * Pastikan kolom 3 jenis dokumen & notifikasi H-90, H-60, H-30, H-14, H-7, H-3 tersedia di tabel agreements
 */
function ensure_deadline_columns(PDO $pdo) {
    $cols = $pdo->query("SHOW COLUMNS FROM agreements")->fetchAll(PDO::FETCH_COLUMN);
    $newCols = [
        'doc_type'          => "ALTER TABLE agreements ADD COLUMN `doc_type` VARCHAR(30) NOT NULL DEFAULT 'COOPERATION' AFTER `id`",
        'document_title'    => "ALTER TABLE agreements ADD COLUMN `document_title` TEXT DEFAULT NULL AFTER `department`",
        'issued_by'         => "ALTER TABLE agreements ADD COLUMN `issued_by` VARCHAR(255) DEFAULT NULL AFTER `document_number`",
        'principal_name'    => "ALTER TABLE agreements ADD COLUMN `principal_name` VARCHAR(255) DEFAULT NULL AFTER `issued_by`",
        'expiry_date_na'    => "ALTER TABLE agreements ADD COLUMN `expiry_date_na` TINYINT(1) NOT NULL DEFAULT 0 AFTER `expiry_date`",
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
    try {
        $pdo->exec("ALTER TABLE agreements MODIFY COLUMN `expiry_date` DATE DEFAULT NULL");
    } catch (Exception $e) {}
    // Ubah tipe_notif di email_notifications menjadi VARCHAR(20) agar mendukung H-90, H-60, H-30, H-14, H-7, H-3, H-1
    $pdo->exec("ALTER TABLE email_notifications MODIFY COLUMN `tipe_notif` VARCHAR(20) NOT NULL");
}

/**
 * Pastikan kolom role superadmin dan status approval tersedia di tabel users
 */
function ensure_user_approval_columns(PDO $pdo) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('status', $cols, true)) {
            $pdo->exec("ALTER TABLE users MODIFY COLUMN `role` ENUM('superadmin', 'admin', 'user') NOT NULL DEFAULT 'user'");
            $pdo->exec("ALTER TABLE users ADD COLUMN `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved' AFTER `role`");
        }
    } catch (Exception $e) {
        // ignore
    }
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
    }
}

function base_url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user']);
}

function is_superadmin() {
    global $pdo;
    $u = current_user();
    if (!$u || empty($u['id'])) return false;
    $role = strtolower($u['role'] ?? '');
    if (in_array($role, ['superadmin', 'admin'], true)) {
        return true;
    }
    try {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT role, status FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$u['id']]);
            $row = $stmt->fetch();
            if ($row) {
                $_SESSION['user']['role'] = $row['role'];
                $_SESSION['user']['status'] = $row['status'];
                return in_array(strtolower($row['role']), ['superadmin', 'admin'], true);
            }
        }
    } catch (Exception $e) {}
    return false;
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
 * Metadata untuk 3 Jenis Dokumen Resmi Medquest
 */
function get_doc_type_meta($doc_type) {
    $type = strtoupper(trim($doc_type ?: 'COOPERATION'));
    return match ($type) {
        'COMPANY' => [
            'code'         => 'COMPANY',
            'title'        => 'ORIGINAL COMPANY DOCUMENT',
            'short_title'  => 'ORIGINAL COMPANY DOC',
            'form_no'      => 'MJG-FOR-LAC.12-001-04',
            'badge_bg'     => '#f0fdf4',
            'badge_color'  => '#166534',
            'badge_border' => '#bbf7d0'
        ],
        'PRINCIPAL' => [
            'code'         => 'PRINCIPAL',
            'title'        => 'LOG OF PRINCIPAL DOCUMENT',
            'short_title'  => 'PRINCIPAL DOCUMENT',
            'form_no'      => 'MJG-FOR-LAC.12-001-09',
            'badge_bg'     => '#faf5ff',
            'badge_color'  => '#6b21a8',
            'badge_border' => '#e9d5ff'
        ],
        default => [
            'code'         => 'COOPERATION',
            'title'        => 'LOG OF COOPERATION AGREEMENT',
            'short_title'  => 'COOPERATION AGREEMENT',
            'form_no'      => 'MJG-FOR-LAC.12-001-03',
            'badge_bg'     => '#eff6ff',
            'badge_color'  => '#1e40af',
            'badge_border' => '#bfdbfe'
        ]
    };
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
    if (empty($expiry_date) || $expiry_date === '0000-00-00' || strtoupper((string)$expiry_date) === 'N/A') {
        return 9999;
    }
    $today = new DateTime(date('Y-m-d'));
    $exp = new DateTime(date('Y-m-d', strtotime($expiry_date)));
    $diff = $today->diff($exp);
    return (int)$diff->format('%r%a');
}

/**
 * Hitung jadwal tanggal deadline H-90, H-60, H-30, H-14, H-7, dan H-3 dari suatu Expiry Date
 */
function get_deadline_schedule($expiry_date, array $agreementRow = []): array {
    if (empty($expiry_date) || $expiry_date === '0000-00-00' || !empty($agreementRow['expiry_date_na'])) {
        return [];
    }
    $milestones = DEADLINE_MILESTONES; // [90, 60, 30, 14, 7, 3]
    $expTs = strtotime($expiry_date);
    if (!$expTs) return [];
    $todayStr = date('Y-m-d');
    $schedule = [];

    foreach ($milestones as $m) {
        $targetTs  = strtotime("-{$m} days", $expTs);
        $targetYmd = date('Y-m-d', $targetTs);
        $colSent   = "notif_h{$m}_sent_at";
        $isSent    = !empty($agreementRow[$colSent]);

        if ($targetYmd === $todayStr) {
            $state = 'today';
        } elseif ($targetYmd < $todayStr) {
            $state = 'passed';
        } else {
            $state = 'upcoming';
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
 * Mendukung tahapan H-90, H-60, H-30, H-14, H-7, H-3 serta N/A
 */
function get_expiry_info($expiry_date, $expiry_date_na = 0) {
    if ($expiry_date_na || empty($expiry_date) || $expiry_date === '0000-00-00' || strtoupper((string)$expiry_date) === 'N/A') {
        return [
            'status'      => 'active',
            'row_class'   => 'row-active',
            'badge_class' => 'badge-active',
            'label'       => 'Berlaku Permanen (N/A)',
            'short_label' => 'N/A (Aktif)',
            'next_stage'  => 'N/A',
            'days'        => 9999
        ];
    }
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
            'row_class' => 'row-urgent row-h3',
            'badge_class' => 'badge-h3',
            'label' => 'Expired Hari Ini!',
            'short_label' => 'HARI INI EXPIRED',
            'next_stage' => 'H-0',
            'days' => 0
        ];
    } elseif ($days <= 3) {
        return [
            'status' => 'warning',
            'row_class' => 'row-urgent row-h3',
            'badge_class' => 'badge-h3',
            'label' => "Mendesak: H-{$days} ({$days} hari lagi)",
            'short_label' => ($days === 3 ? 'H-3' : "Sisa {$days} Hari"),
            'next_stage' => 'H-3',
            'days' => $days
        ];
    } elseif ($days <= 7) {
        return [
            'status' => 'warning',
            'row_class' => 'row-warning',
            'badge_class' => 'badge-h7',
            'label' => "Segera Expired: H-{$days}",
            'short_label' => ($days === 7 ? 'TEPAT H-7' : "Sisa {$days} Hari"),
            'next_stage' => 'H-7',
            'days' => $days
        ];
    } elseif ($days <= 14) {
        return [
            'status' => 'warning',
            'row_class' => 'row-warning',
            'badge_class' => 'badge-h14',
            'label' => "Fase H-14 (Sisa {$days} hari)",
            'short_label' => ($days === 14 ? 'TEPAT H-14' : "Sisa {$days} Hari"),
            'next_stage' => 'H-14',
            'days' => $days
        ];
    } elseif ($days <= 30) {
        return [
            'status' => 'warning',
            'row_class' => 'row-warning-soft',
            'badge_class' => 'badge-h30',
            'label' => "Fase H-30 (Sisa {$days} hari)",
            'short_label' => ($days === 30 ? 'TEPAT H-30' : "Sisa {$days} Hari"),
            'next_stage' => 'H-30',
            'days' => $days
        ];
    } elseif ($days <= 60) {
        return [
            'status' => 'warning',
            'row_class' => 'row-warning-soft',
            'badge_class' => 'badge-h60',
            'label' => "Fase H-60 (Sisa {$days} hari)",
            'short_label' => ($days === 60 ? 'TEPAT H-60' : "Sisa {$days} Hari"),
            'next_stage' => 'H-60',
            'days' => $days
        ];
    } elseif ($days <= 90) {
        return [
            'status' => 'warning',
            'row_class' => 'row-warning-soft',
            'badge_class' => 'badge-h90',
            'label' => "Fase H-90 (Sisa {$days} hari)",
            'short_label' => ($days === 90 ? 'TEPAT H-90' : "Sisa {$days} Hari"),
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
