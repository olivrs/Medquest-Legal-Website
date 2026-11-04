<?php
/**
 * Sistem Pengiriman Notifikasi Email Otomatis TANPA KONFIGURASI RIBET (Zero-Config)
 * Mendukung H-90, H-60, H-30, H-14, H-7, dan H-3 otomatis ke email pembuat data (Created By)
 */

/**
 * Pastikan Mailpit bawaan Laragon jalan otomatis di background (Port 1025 & Webmail 8025)
 */
function ensure_laragon_mailpit_running(): bool {
    $fp = @fsockopen('127.0.0.1', 1025, $errno, $errstr, 0.15);
    if ($fp) {
        fclose($fp);
        return true;
    }
    $mailpitExe = 'C:\laragon\bin\mailpit\1.22.3\mailpit.exe';
    if (file_exists($mailpitExe) && stripos(PHP_OS, 'WIN') === 0) {
        @pclose(@popen('start /B "" "' . $mailpitExe . '" >NUL 2>&1', 'r'));
        usleep(250000);
        $fp2 = @fsockopen('127.0.0.1', 1025, $errno2, $errstr2, 0.2);
        if ($fp2) {
            fclose($fp2);
            return true;
        }
    }
    return false;
}

/**
 * Kirim ke Inbox Gmail asli TANPA perlu setting SMTP/Password (Zero-Config Cloud Relay via FormSubmit HTTPS API)
 */
function send_via_zero_config_cloud_relay(string $toEmail, string $subject, array $agreement, array $creator, string $tipeNotif, string &$relayMsg = ''): bool {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $url = 'https://formsubmit.co/ajax/' . rawurlencode($toEmail);
    $payload = [
        '_subject'  => $subject,
        '_template' => 'table',
        '_captcha'  => 'false',
        'Penerima'           => $creator['nama_lengkap'] . ' (' . $creator['kode_singkatan'] . ')',
        'Tahap_Deadline'     => $tipeNotif,
        'Department'         => $agreement['department'],
        'Document_Number'    => $agreement['document_number'],
        'Customer'           => $agreement['customer'],
        'Product'            => $agreement['product_description'] . ' (S/N: ' . ($agreement['serial_number'] ?: 'N/A') . ')',
        'Effective_Date'     => format_date_medquest($agreement['effective_date'], $agreement['effective_date_na']),
        'Expiry_Date'        => format_date_medquest($agreement['expiry_date'], false),
        'Note'               => $agreement['note'] ?: 'N/A',
        'Created_By'         => $agreement['created_by_log'],
    ];

    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAccept: application/json\r\nOrigin: http://localhost\r\nReferer: http://localhost/medquest-log/\r\nUser-Agent: Mozilla/5.0\r\n",
            'content' => json_encode($payload),
            'timeout' => 8,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];

    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) {
        return false;
    }
    $decoded = @json_decode($resp, true);
    $relayMsg = $decoded['message'] ?? '';
    return isset($decoded['success']) && ($decoded['success'] === true || $decoded['success'] === 'true');
}

/**
 * Bangun template HTML Email resmi Medquest untuk pengingat H-90, H-60, H-30, H-14, H-7, H-3
 */
function build_expiry_email_html(array $agreement, array $creator, string $tipeNotif, int $daysRemaining): string {
    $badgeColor = '#2563eb';
    $urgencyText = "{$daysRemaining} Hari Lagi ({$tipeNotif})";

    if ($daysRemaining <= 3 || $tipeNotif === 'H-3') {
        $badgeColor = '#dc2626';
        $urgencyText = "3 Hari Lagi (H-3) - SANGAT MENDESAK!";
    } elseif ($daysRemaining <= 7 || $tipeNotif === 'H-7') {
        $badgeColor = '#ea580c';
        $urgencyText = "7 Hari Lagi (H-7) - Segera Tindak Lanjuti";
    } elseif ($daysRemaining <= 14 || $tipeNotif === 'H-14') {
        $badgeColor = '#d97706';
        $urgencyText = "14 Hari Lagi (H-14) - Persiapan Akhir Perpanjangan";
    } elseif ($daysRemaining <= 30 || $tipeNotif === 'H-30') {
        $badgeColor = '#ca8a04';
        $urgencyText = "30 Hari Lagi (H-30) - Evaluasi 1 Bulan Sebelum Expired";
    } elseif ($daysRemaining <= 60 || $tipeNotif === 'H-60') {
        $badgeColor = '#0284c7';
        $urgencyText = "60 Hari Lagi (H-60) - Pengingat 2 Bulan Sebelum Expired";
    } elseif ($daysRemaining <= 90 || $tipeNotif === 'H-90') {
        $badgeColor = '#4f46e5';
        $urgencyText = "90 Hari Lagi (H-90) - Pengingat Awal 3 Bulan Sebelum Expired";
    }

    $effDateStr = format_date_medquest($agreement['effective_date'], $agreement['effective_date_na']);
    $expDateStr = format_date_medquest($agreement['expiry_date'], false);
    $docNoHtml  = nl2br(htmlspecialchars($agreement['document_number']));
    $noteHtml   = nl2br(htmlspecialchars($agreement['note'] ?: 'N/A'));
    $createdLog = nl2br(htmlspecialchars($agreement['created_by_log']));
    $appUrl     = base_url('?page=agreements&dept=' . urlencode($agreement['department']));

    $schedule = get_deadline_schedule($agreement['expiry_date'], $agreement);
    $scheduleHtml = '';
    foreach ($schedule as $code => $sItem) {
        $isCurrent = ($code === $tipeNotif);
        $bgPill = $isCurrent ? '#fef08a' : '#f8fafc';
        $borderPill = $isCurrent ? '2px solid #ca8a04' : '1px solid #cbd5e1';
        $scheduleHtml .= '
          <td style="padding:8px 6px;text-align:center;background:' . $bgPill . ';border:' . $borderPill . ';">
            <div style="font-weight:800;font-size:12px;color:#1e3a8a;">' . $code . '</div>
            <div style="font-size:11px;color:#334155;margin-top:2px;">' . $sItem['date_text'] . '</div>
          </td>';
    }

    return '
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Notifikasi Deadline ' . htmlspecialchars($tipeNotif) . ' - Medquest</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Segoe UI,Arial,sans-serif;color:#1e293b;">
  <div style="max-width:680px;margin:24px auto;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;">
    <div style="background:linear-gradient(135deg,#0d1f44 0%,#1d4ed8 100%);padding:22px 28px;color:#ffffff;">
      <div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;opacity:0.85;">PT Medquest Jaya Global &bull; Doc No: MJG-FOR-LAC.12-001-03</div>
      <h2 style="margin:6px 0 0;font-size:20px;font-weight:700;">PENGINGAT DEADLINE KONTRAK: ' . htmlspecialchars($tipeNotif) . '</h2>
    </div>

    <div style="background:' . $badgeColor . ';color:#ffffff;padding:12px 28px;font-weight:700;font-size:14px;">
      Peringatan Otomatis: Kontrak Memasuki Tahap ' . $urgencyText . '
    </div>

    <div style="padding:26px 28px;">
      <p style="margin-top:0;font-size:15px;line-height:1.6;">
        Halo <strong>' . htmlspecialchars($creator['nama_lengkap']) . ' (' . htmlspecialchars($creator['kode_singkatan']) . ')</strong>,
      </p>
      <p style="font-size:14px;line-height:1.6;color:#475569;">
        Dokumen kerja sama yang Anda input (<em>Created By: ' . htmlspecialchars($creator['kode_singkatan']) . '</em>) saat ini telah mencapai batas waktu pengingat <strong>' . htmlspecialchars($tipeNotif) . '</strong> menuju tanggal berakhir kontrak (<em>Expiry Date: ' . $expDateStr . '</em>).
      </p>

      <div style="font-weight:700;font-size:13px;margin:18px 0 6px;color:#334155;">Jadwal Tanggal Deadline Kontrak Ini:</div>
      <table style="width:100%;border-collapse:collapse;margin-bottom:18px;">
        <tr>' . $scheduleHtml . '</tr>
      </table>

      <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:13.5px;border:1px solid #cbd5e1;">
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;width:34%;color:#334155;">Department / Division</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#1e3a8a;">' . htmlspecialchars($agreement['department']) . '</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Document Number</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . $docNoHtml . '</td>
        </tr>
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Customer</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:600;">' . htmlspecialchars($agreement['customer']) . '</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Product (Description)</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . htmlspecialchars($agreement['product_description']) . '</td>
        </tr>
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Product (Serial Number)</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . htmlspecialchars($agreement['serial_number']) . '</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Effective Date</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . $effDateStr . '</td>
        </tr>
        <tr style="background:#fef2f2;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#dc2626;">Expiry Date</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:800;color:#dc2626;font-size:15px;">' . $expDateStr . ' (' . htmlspecialchars($tipeNotif) . ')</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Note Riwayat</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . $noteHtml . '</td>
        </tr>
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Created By</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-family:monospace;font-weight:700;">' . $createdLog . '</td>
        </tr>
      </table>

      <div style="text-align:center;margin:26px 0 10px;">
        <a href="' . htmlspecialchars($appUrl) . '" style="background:#1d4ed8;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:700;font-size:14px;display:inline-block;">
          Buka Departemen ' . htmlspecialchars($agreement['department']) . ' di Website
        </a>
      </div>
    </div>

    <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 28px;font-size:12px;color:#64748b;text-align:center;">
      Email ini dikirim secara otomatis ke <strong>' . htmlspecialchars($creator['email']) . '</strong> karena Anda tercatat sebagai penginput data (<strong>' . htmlspecialchars($creator['kode_singkatan']) . '</strong>).
    </div>
  </div>
</body>
</html>';
}

function is_smtp_configured(PDO $pdo): bool {
    return true; // Zero-config aktif otomatis tanpa perlu setting manual
}

/**
 * Kirim satu notifikasi email secara otomatis tanpa perlu setting SMTP manual
 */
function dispatch_agreement_notification(PDO $pdo, array $agreement, string $tipeNotif, bool $forceSend = false): array {
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmtUser->execute([$agreement['created_by_user_id']]);
    $creator = $stmtUser->fetch();

    if (!$creator) {
        $stmtUser2 = $pdo->prepare("SELECT * FROM users WHERE kode_singkatan = ? LIMIT 1");
        $stmtUser2->execute([$agreement['created_by_code']]);
        $creator = $stmtUser2->fetch();
    }

    if (!$creator || empty($creator['email'])) {
        return ['success' => false, 'message' => 'User pembuat data tidak memiliki email terdaftar.'];
    }

    $days = get_days_until_expiry($agreement['expiry_date']);
    $expFormatted = format_date_medquest($agreement['expiry_date'], false);
    $subject = "[MEDQUEST {$tipeNotif}] Pengingat Deadline ({$expFormatted}) - Dept {$agreement['department']} - {$agreement['customer']}";
    $htmlBody = build_expiry_email_html($agreement, $creator, $tipeNotif, $days);

    // 1. Simpan ke folder mail_outbox
    $outboxDir = __DIR__ . '/../mail_outbox';
    if (!is_dir($outboxDir)) {
        @mkdir($outboxDir, 0777, true);
    }
    $safeFile = 'email_' . strtolower(str_replace('-', '', $tipeNotif)) . '_agr' . $agreement['id'] . '_' . date('Ymd_His') . '.html';
    @file_put_contents($outboxDir . '/' . $safeFile, $htmlBody);

    // 2. Jalankan Laragon Mailpit otomatis (Zero-Config Local Webmail http://localhost:8025)
    if (ensure_laragon_mailpit_running()) {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Medquest Agreement Alert <noreply@medquest.co.id>\r\n";
        @mail($creator['email'], $subject, $htmlBody, $headers);
    }

    // 3. Kirim ke Cloud Relay tanpa password (FormSubmit AJAX) agar diteruskan ke Gmail user tanpa perlu setting SMTP
    $relayMsg = '';
    $cloudOk = @send_via_zero_config_cloud_relay($creator['email'], $subject, $agreement, $creator, $tipeNotif, $relayMsg);

    if ($cloudOk) {
        $statusKirim = 'TERKIRIM KE GMAIL';
        $keterangan  = 'Terkirim otomatis ke Inbox Gmail ' . $creator['email'];
    } else {
        // Cek apakah sudah ada log menunggu aktivasi untuk dokumen & tipe ini agar tidak duplikat
        $cekWait = $pdo->prepare("SELECT id FROM email_notifications WHERE agreement_id = ? AND tipe_notif = ? LIMIT 1");
        $cekWait->execute([$agreement['id'], $tipeNotif]);
        if ($cekWait->fetch() && !$forceSend) {
            return ['success' => false, 'skipped' => true, 'message' => 'Menunggu klik Activate di Gmail.'];
        }
        $statusKirim = 'MENUNGGU KLIK ACTIVATE DI GMAIL';
        $keterangan  = 'Cek Inbox/Spam ' . $creator['email'] . ' lalu klik tombol "Activate Form" (1 kali saja) agar email otomatis masuk.';
    }

    // Catat ke tabel email_notifications
    $insLog = $pdo->prepare("
        INSERT INTO email_notifications 
        (agreement_id, user_id, recipient_email, recipient_code, tipe_notif, days_remaining, subject, body_html, status_kirim, keterangan_status, sent_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $insLog->execute([
        $agreement['id'],
        $creator['id'],
        $creator['email'],
        $creator['kode_singkatan'],
        $tipeNotif,
        $days,
        $subject,
        $htmlBody,
        $statusKirim,
        $keterangan
    ]);

    // Hanya kunci flag notif_h90_sent_at..notif_h3_sent_at jika benar-benar sudah terkirim ke Gmail ($cloudOk == true)
    // Sehingga begitu user klik "Activate Form" di Gmailnya, sistem otomatis langsung mengirim ulang email H-90 ini!
    if ($cloudOk) {
        $colMap = [
            'H-90' => 'notif_h90_sent_at',
            'H-60' => 'notif_h60_sent_at',
            'H-30' => 'notif_h30_sent_at',
            'H-14' => 'notif_h14_sent_at',
            'H-7'  => 'notif_h7_sent_at',
            'H-3'  => 'notif_h3_sent_at',
            'H-1'  => 'notif_h1_sent_at',
        ];
        if (isset($colMap[$tipeNotif])) {
            $col = $colMap[$tipeNotif];
            $pdo->prepare("UPDATE agreements SET {$col} = NOW() WHERE id = ?")->execute([$agreement['id']]);
        }
    }

    return [
        'success' => $cloudOk,
        'sent_smtp' => $cloudOk,
        'recipient' => $creator['email'],
        'kode' => $creator['kode_singkatan'],
        'customer' => $agreement['customer'],
        'tipe' => $tipeNotif,
        'status' => $statusKirim,
        'message' => $keterangan
    ];
}

/**
 * Fungsi Otomatis Cek seluruh data yang sudah masuk H-90, H-60, H-30, H-14, H-7, atau H-3
 */
function run_auto_expiry_notifications(PDO $pdo): array {
    $results = [];

    $sql = "
        SELECT a.*, DATEDIFF(a.expiry_date, CURDATE()) AS sisa_hari
        FROM agreements a
        WHERE DATEDIFF(a.expiry_date, CURDATE()) IN (90, 60, 30, 14, 7, 3)
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $sisa = (int)$row['sisa_hari'];
        $colSent = "notif_h{$sisa}_sent_at";
        if (in_array($sisa, [90, 60, 30, 14, 7, 3], true) && empty($row[$colSent])) {
            $res = dispatch_agreement_notification($pdo, $row, "H-{$sisa}", false);
            if ($res && !empty($res['success'])) {
                $results[] = $res;
            }
        }
    }

    return $results;
}
