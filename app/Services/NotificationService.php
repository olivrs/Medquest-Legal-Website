<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\EmailNotification;
use App\Models\SmtpSetting;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public static function buildExpiryEmailHtml(array $agreement, array $creator, string $tipeNotif, int $daysRemaining): string
    {
        $badgeColor = '#0059fb';
        $urgencyText = "{$daysRemaining} Hari Lagi ({$tipeNotif})";

        if ($daysRemaining <= 3 || $tipeNotif === 'H-3') {
            $badgeColor = '#dc2626';
            $urgencyText = '3 Hari Lagi (H-3) - SANGAT MENDESAK!';
        } elseif ($daysRemaining <= 7 || $tipeNotif === 'H-7') {
            $badgeColor = '#ea580c';
            $urgencyText = '7 Hari Lagi (H-7) - Segera Tindak Lanjuti';
        } elseif ($daysRemaining <= 14 || $tipeNotif === 'H-14') {
            $badgeColor = '#d97706';
            $urgencyText = '14 Hari Lagi (H-14) - Persiapan Akhir Perpanjangan';
        } elseif ($daysRemaining <= 30 || $tipeNotif === 'H-30') {
            $badgeColor = '#ca8a04';
            $urgencyText = '30 Hari Lagi (H-30) - Evaluasi 1 Bulan Sebelum Expired';
        } elseif ($daysRemaining <= 60 || $tipeNotif === 'H-60') {
            $badgeColor = '#0284c7';
            $urgencyText = '60 Hari Lagi (H-60) - Pengingat 2 Bulan Sebelum Expired';
        } elseif ($daysRemaining <= 90 || $tipeNotif === 'H-90') {
            $badgeColor = '#4f46e5';
            $urgencyText = '90 Hari Lagi (H-90) - Pengingat Awal 3 Bulan Sebelum Expired';
        }

        $effDateStr = format_date_medquest($agreement['effective_date'] ?? null, !empty($agreement['effective_date_na']));
        $expDateStr = format_date_medquest($agreement['expiry_date'] ?? null, false);
        $docNoHtml  = nl2br(e($agreement['document_number'] ?? ''));
        $noteHtml   = nl2br(e(($agreement['note'] ?? '') ?: 'N/A'));
        $createdLog = nl2br(e($agreement['created_by_log'] ?? ''));
        $appUrl     = base_url('?page=agreements&dept=' . urlencode((string) ($agreement['department'] ?? 'ALL')));

        $schedule = get_deadline_schedule($agreement['expiry_date'] ?? null, $agreement);
        $scheduleHtml = '';
        foreach ($schedule as $code => $sItem) {
            $isCurrent = ($code === $tipeNotif);
            $bgPill = $isCurrent ? '#fef08a' : '#f8fafc';
            $borderPill = $isCurrent ? '2px solid #ca8a04' : '1px solid #cbd5e1';
            $scheduleHtml .= '
              <td style="padding:8px 6px;text-align:center;background:' . $bgPill . ';border:' . $borderPill . ';">
                <div style="font-weight:800;font-size:12px;color:#0059fb;">' . $code . '</div>
                <div style="font-size:11px;color:#334155;margin-top:2px;">' . $sItem['date_text'] . '</div>
              </td>';
        }

        return '
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Notifikasi Deadline ' . e($tipeNotif) . ' - Medquest</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Open Sans,Helvetica,Arial,sans-serif;color:#282828;">
  <div style="max-width:680px;margin:24px auto;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;">
    <div style="background:linear-gradient(135deg,#101399 0%,#0059fb 65%,#2ea3f2 100%);padding:22px 28px;color:#ffffff;">
      <div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;opacity:0.85;">PT Medquest Jaya Global &bull; Doc No: MJG-FOR-LAC.12-001-03</div>
      <h2 style="margin:6px 0 0;font-size:20px;font-weight:700;">PENGINGAT DEADLINE KONTRAK: ' . e($tipeNotif) . '</h2>
    </div>

    <div style="background:' . $badgeColor . ';color:#ffffff;padding:12px 28px;font-weight:700;font-size:14px;">
      Peringatan Otomatis: Kontrak Memasuki Tahap ' . $urgencyText . '
    </div>

    <div style="padding:26px 28px;">
      <p style="margin-top:0;font-size:15px;line-height:1.6;">
        Halo <strong>' . e($creator['nama_lengkap'] ?? '') . ' (' . e($creator['kode_singkatan'] ?? '') . ')</strong>,
      </p>
      <p style="font-size:14px;line-height:1.6;color:#475569;">
        Dokumen kerja sama yang Anda input (<em>Created By: ' . e($creator['kode_singkatan'] ?? '') . '</em>) saat ini telah mencapai batas waktu pengingat <strong>' . e($tipeNotif) . '</strong> menuju tanggal berakhir kontrak (<em>Expiry Date: ' . $expDateStr . '</em>).
      </p>

      <div style="font-weight:700;font-size:13px;margin:18px 0 6px;color:#334155;">Jadwal Tanggal Deadline Kontrak Ini:</div>
      <table style="width:100%;border-collapse:collapse;margin-bottom:18px;">
        <tr>' . $scheduleHtml . '</tr>
      </table>

      <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:13.5px;border:1px solid #cbd5e1;">
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;width:34%;color:#334155;">Department / Division</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#0059fb;">' . e($agreement['department'] ?? '') . '</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Document Number</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . $docNoHtml . '</td>
        </tr>
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Customer</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:600;">' . e($agreement['customer'] ?? '') . '</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Product (Description)</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . e($agreement['product_description'] ?? '') . '</td>
        </tr>
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Product (Serial Number)</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . e($agreement['serial_number'] ?? 'N/A') . '</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Effective Date</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . $effDateStr . '</td>
        </tr>
        <tr style="background:#fef2f2;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#dc2626;">Expiry Date</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:800;color:#dc2626;font-size:15px;">' . $expDateStr . ' (' . e($tipeNotif) . ')</td>
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
        <a href="' . e($appUrl) . '" style="background:#0059fb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:700;font-size:14px;display:inline-block;">
          Buka Departemen ' . e($agreement['department'] ?? '') . ' di Website
        </a>
      </div>
    </div>

    <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 28px;font-size:12px;color:#64748b;text-align:center;">
      Email ini dikirim secara otomatis ke <strong>' . e($creator['email'] ?? '') . '</strong> karena Anda tercatat sebagai penginput data (<strong>' . e($creator['kode_singkatan'] ?? '') . '</strong>).
    </div>
  </div>
</body>
</html>';
    }

    public static function sendViaDynamicSmtp(?SmtpSetting $smtp, string $toEmail, string $subject, string $htmlBody, ?string &$errorMsg = null): bool
    {
        if (!$smtp || !$smtp->smtp_enabled || empty($smtp->smtp_username) || empty($smtp->smtp_password)) {
            $errorMsg = 'Konfigurasi SMTP belum diaktifkan.';
            return false;
        }

        try {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $smtp->smtp_host ?: 'smtp.gmail.com');
            Config::set('mail.mailers.smtp.port', (int) ($smtp->smtp_port ?: 587));
            Config::set('mail.mailers.smtp.encryption', $smtp->smtp_encryption ?: 'tls');
            Config::set('mail.mailers.smtp.username', $smtp->smtp_username);
            Config::set('mail.mailers.smtp.password', $smtp->smtp_password);
            Config::set('mail.from.address', $smtp->sender_email ?: $smtp->smtp_username);
            Config::set('mail.from.name', $smtp->sender_name ?: 'Medquest Agreement Alert (LAC)');

            Mail::purge('smtp');
            Mail::html($htmlBody, function ($message) use ($toEmail, $subject, $smtp) {
                $message->to($toEmail)
                    ->subject($subject)
                    ->from($smtp->sender_email ?: $smtp->smtp_username, $smtp->sender_name ?: 'Medquest Agreement Alert (LAC)');
            });

            return true;
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            return false;
        }
    }

    public static function sendViaZeroConfigCloudRelay(string $toEmail, string $subject, array $agreement, array $creator, string $tipeNotif): bool
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $url = 'https://formsubmit.co/ajax/' . rawurlencode($toEmail);
        $payload = [
            '_subject'        => $subject,
            '_template'       => 'table',
            '_captcha'        => 'false',
            'Penerima'        => ($creator['nama_lengkap'] ?? '') . ' (' . ($creator['kode_singkatan'] ?? '') . ')',
            'Tahap_Deadline'  => $tipeNotif,
            'Department'      => $agreement['department'] ?? '',
            'Document_Number' => $agreement['document_number'] ?? '',
            'Customer'        => $agreement['customer'] ?? '',
            'Product'         => ($agreement['product_description'] ?? '') . ' (S/N: ' . (($agreement['serial_number'] ?? '') ?: 'N/A') . ')',
            'Effective_Date'  => format_date_medquest($agreement['effective_date'] ?? null, !empty($agreement['effective_date_na'])),
            'Expiry_Date'     => format_date_medquest($agreement['expiry_date'] ?? null, false),
            'Note'            => ($agreement['note'] ?? '') ?: 'N/A',
            'Created_By'      => $agreement['created_by_log'] ?? '',
        ];

        $opts = [
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\nAccept: application/json\r\nOrigin: http://localhost\r\nReferer: http://localhost/\r\nUser-Agent: Mozilla/5.0\r\n",
                'content'       => json_encode($payload),
                'timeout'       => 8,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ];

        $ctx = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            return false;
        }
        $decoded = @json_decode($resp, true);
        return isset($decoded['success']) && ($decoded['success'] === true || $decoded['success'] === 'true');
    }

    public static function dispatchAgreementNotification(Agreement $agreementModel, string $tipeNotif, bool $forceSend = false): array
    {
        $agreement = $agreementModel->toArray();

        $creator = User::find($agreement['created_by_user_id']);
        if (!$creator && !empty($agreement['created_by_code'])) {
            $creator = User::where('kode_singkatan', $agreement['created_by_code'])->first();
        }

        if (!$creator || empty($creator->email)) {
            return ['success' => false, 'message' => 'User pembuat data tidak memiliki email terdaftar.'];
        }

        $creatorArr = $creator->toArray();
        $days = get_days_until_expiry($agreement['expiry_date']);
        $expFormatted = format_date_medquest($agreement['expiry_date'], false);
        $subject = "[MEDQUEST {$tipeNotif}] Pengingat Deadline ({$expFormatted}) - Dept {$agreement['department']} - {$agreement['customer']}";
        $htmlBody = self::buildExpiryEmailHtml($agreement, $creatorArr, $tipeNotif, $days);

        $outboxDir = storage_path('app/mail_outbox');
        if (!is_dir($outboxDir)) {
            @mkdir($outboxDir, 0775, true);
        }
        $safeFile = 'email_' . strtolower(str_replace('-', '', $tipeNotif)) . '_agr' . $agreement['id'] . '_' . date('Ymd_His') . '.html';
        @file_put_contents($outboxDir . '/' . $safeFile, $htmlBody);

        $smtp = SmtpSetting::find(1);
        $smtpErr = null;
        $sentOk = false;
        $sentSmtp = false;

        if ($smtp && $smtp->smtp_enabled && !empty($smtp->smtp_username) && !empty($smtp->smtp_password)) {
            $sentOk = self::sendViaDynamicSmtp($smtp, $creator->email, $subject, $htmlBody, $smtpErr);
            $sentSmtp = $sentOk;
        }

        if (!$sentOk) {
            $sentOk = self::sendViaZeroConfigCloudRelay($creator->email, $subject, $agreement, $creatorArr, $tipeNotif);
        }

        if ($sentOk) {
            $statusKirim = $sentSmtp ? 'SENT_GMAIL_SMTP' : 'TERKIRIM KE GMAIL';
            $keterangan  = 'Terkirim otomatis ke Inbox Gmail ' . $creator->email;
        } else {
            $cekWait = EmailNotification::where('agreement_id', $agreement['id'])
                ->where('tipe_notif', $tipeNotif)
                ->exists();
            if ($cekWait && !$forceSend) {
                return ['success' => false, 'skipped' => true, 'message' => 'Menunggu aktivasi / SMTP.'];
            }
            $statusKirim = 'MENUNGGU KLIK ACTIVATE DI GMAIL';
            $keterangan  = 'Cek Inbox/Spam ' . $creator->email . ' atau aktifkan pengaturan SMTP.';
        }

        EmailNotification::create([
            'agreement_id'      => $agreement['id'],
            'user_id'           => $creator->id,
            'recipient_email'   => $creator->email,
            'recipient_code'    => $creator->kode_singkatan,
            'tipe_notif'        => $tipeNotif,
            'days_remaining'    => $days,
            'subject'           => $subject,
            'body_html'         => $htmlBody,
            'status_kirim'      => $statusKirim,
            'keterangan_status' => $keterangan,
            'is_read'           => false,
            'sent_at'           => now(),
        ]);

        if ($sentOk) {
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
                $agreementModel->update([$col => now()]);
            }
        }

        return [
            'success'   => $sentOk,
            'sent_smtp' => $sentSmtp,
            'recipient' => $creator->email,
            'kode'      => $creator->kode_singkatan,
            'customer'  => $agreement['customer'],
            'tipe'      => $tipeNotif,
            'status'    => $statusKirim,
            'message'   => $keterangan,
        ];
    }

    public static function runAutoExpiryNotifications(): array
    {
        $results = [];

        try {
            $agreements = Agreement::where('expiry_date_na', 0)
                ->whereNotNull('expiry_date')
                ->get();

            foreach ($agreements as $agr) {
                $sisa = get_days_until_expiry($agr->expiry_date);
                if (in_array($sisa, [90, 60, 30, 14, 7, 3], true)) {
                    $colSent = "notif_h{$sisa}_sent_at";
                    if (empty($agr->{$colSent})) {
                        $res = self::dispatchAgreementNotification($agr, "H-{$sisa}", false);
                        if (!empty($res['success'])) {
                            $results[] = $res;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return $results;
    }
}
