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
        $urgencyText = "{$daysRemaining} Days Remaining ({$tipeNotif})";

        if ($daysRemaining <= 3 || $tipeNotif === 'H-3') {
            $badgeColor = '#dc2626';
            $urgencyText = '3 Days Left (H-3) - URGENT ACTION REQUIRED!';
        } elseif ($daysRemaining <= 7 || $tipeNotif === 'H-7') {
            $badgeColor = '#ea580c';
            $urgencyText = '7 Days Left (H-7) - Immediate Follow-up Needed';
        } elseif ($daysRemaining <= 14 || $tipeNotif === 'H-14') {
            $badgeColor = '#d97706';
            $urgencyText = '14 Days Left (H-14) - Final Renewal Preparation';
        } elseif ($daysRemaining <= 30 || $tipeNotif === 'H-30') {
            $badgeColor = '#ca8a04';
            $urgencyText = '30 Days Left (H-30) - 1-Month Pre-Expiry Review';
        } elseif ($daysRemaining <= 60 || $tipeNotif === 'H-60') {
            $badgeColor = '#0284c7';
            $urgencyText = '60 Days Left (H-60) - 2-Month Expiry Reminder';
        } elseif ($daysRemaining <= 90 || $tipeNotif === 'H-90') {
            $badgeColor = '#4f46e5';
            $urgencyText = '90 Days Left (H-90) - 3-Month Early Expiry Notice';
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
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Deadline Notification ' . e($tipeNotif) . ' - Medquest</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Open Sans,Helvetica,Arial,sans-serif;color:#282828;">
  <div style="max-width:680px;margin:24px auto;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;">
    <div style="background:linear-gradient(135deg,#101399 0%,#0059fb 65%,#2ea3f2 100%);padding:22px 28px;color:#ffffff;">
      <div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;opacity:0.85;">PT Medquest Jaya Global &bull; Doc No: MJG-FOR-LAC.12-001-03</div>
      <h2 style="margin:6px 0 0;font-size:20px;font-weight:700;">CONTRACT DEADLINE REMINDER: ' . e($tipeNotif) . '</h2>
    </div>

    <div style="background:' . $badgeColor . ';color:#ffffff;padding:12px 28px;font-weight:700;font-size:14px;">
      Automated Alert: Contract Entered Stage ' . $urgencyText . '
    </div>

    <div style="padding:26px 28px;">
      <p style="margin-top:0;font-size:15px;line-height:1.6;">
        Hello <strong>' . e($creator['nama_lengkap'] ?? '') . ' (' . e($creator['kode_singkatan'] ?? '') . ')</strong>,
      </p>
      <p style="font-size:14px;line-height:1.6;color:#475569;">
        The cooperation document you submitted (<em>Created By: ' . e($creator['kode_singkatan'] ?? '') . '</em>) has reached the <strong>' . e($tipeNotif) . '</strong> reminder milestone ahead of its expiration date (<em>Expiry Date: ' . $expDateStr . '</em>).
      </p>

      <div style="font-weight:700;font-size:13px;margin:18px 0 6px;color:#334155;">Contract Reminder Schedule:</div>
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
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Note History</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . $noteHtml . '</td>
        </tr>
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Created By</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-family:monospace;font-weight:700;">' . $createdLog . '</td>
        </tr>
      </table>

      <div style="text-align:center;margin:26px 0 10px;">
        <a href="' . e($appUrl) . '" style="background:#0059fb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:700;font-size:14px;display:inline-block;">
          Open Department ' . e($agreement['department'] ?? '') . ' on Website
        </a>
      </div>
    </div>

    <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 28px;font-size:12px;color:#64748b;text-align:center;">
      This email was sent automatically to <strong>' . e($creator['email'] ?? '') . '</strong> because you are listed as the creator (<strong>' . e($creator['kode_singkatan'] ?? '') . '</strong>).
    </div>
  </div>
</body>
</html>';
    }

    public static function sendViaDynamicSmtp(?SmtpSetting $smtp, string $toEmail, string $subject, string $htmlBody, ?string &$errorMsg = null): bool
    {
        if (!$smtp || !$smtp->smtp_enabled || empty($smtp->smtp_username) || empty($smtp->smtp_password)) {
            $errorMsg = 'SMTP configuration is not enabled.';
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
            'Recipient'       => ($creator['nama_lengkap'] ?? '') . ' (' . ($creator['kode_singkatan'] ?? '') . ')',
            'Deadline_Stage'  => $tipeNotif,
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
            return ['success' => false, 'message' => 'Creator user does not have a registered email address.'];
        }

        $creatorArr = $creator->toArray();
        $days = get_days_until_expiry($agreement['expiry_date']);
        $expFormatted = format_date_medquest($agreement['expiry_date'], false);
        $subject = "[MEDQUEST {$tipeNotif}] Deadline Reminder ({$expFormatted}) - Dept {$agreement['department']} - {$agreement['customer']}";
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
            $statusKirim = $sentSmtp ? 'SENT_GMAIL_SMTP' : 'SENT TO GMAIL';
            $keterangan  = 'Automatically sent to Gmail Inbox ' . $creator->email;
        } else {
            $cekWait = EmailNotification::where('agreement_id', $agreement['id'])
                ->where('tipe_notif', $tipeNotif)
                ->exists();
            if ($cekWait && !$forceSend) {
                return ['success' => false, 'skipped' => true, 'message' => 'Waiting for activation / SMTP.'];
            }
            $statusKirim = 'AWAITING ACTIVATE CLICK IN GMAIL';
            $keterangan  = 'Check Inbox/Spam ' . $creator->email . ' or enable SMTP settings.';
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

    public static function runAutoExpiryNotifications(bool $force = false): array
    {
        $results = [];

        try {
            if (!$force) {
                $lastRun = cache()->get('medquest_last_auto_notify');
                if ($lastRun && (time() - (int) $lastRun) < 300) {
                    return [];
                }
                cache()->put('medquest_last_auto_notify', time(), 600);
            }

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

    public static function generateApprovalToken(User $user, string $action = 'approve'): string
    {
        $secret = config('app.key') ?: 'medquest-legal-secret-key-2026';
        return hash_hmac('sha256', $user->id . '|' . $user->email . '|' . $action . '|' . $user->created_at, $secret);
    }

    public static function verifyApprovalToken(User $user, string $token, string $action = 'approve'): bool
    {
        if (empty($token)) {
            return false;
        }
        $expected = self::generateApprovalToken($user, $action);
        return hash_equals($expected, $token);
    }

    public static function buildUserApprovalEmailHtml(User $user): string
    {
        $approveUrl = base_url('quick-approve?action=approve&id=' . $user->id . '&token=' . self::generateApprovalToken($user, 'approve'));
        $rejectUrl = base_url('quick-approve?action=reject&id=' . $user->id . '&token=' . self::generateApprovalToken($user, 'reject'));
        $dashboardUrl = base_url('?page=user_approvals');
        $regTime = $user->created_at ? $user->created_at->format('d/m/Y H:i') : date('d/m/Y H:i');

        return '
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Persetujuan Registrasi Pengguna Baru - Medquest Legal</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Open Sans,Helvetica,Arial,sans-serif;color:#282828;">
  <div style="max-width:620px;margin:24px auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;">
    <div style="background:linear-gradient(135deg,#101399 0%,#0059fb 70%,#2ea3f2 100%);padding:22px 28px;color:#ffffff;">
      <div style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;opacity:0.9;font-weight:600;">PT MEDQUEST JAYA GLOBAL &bull; LEGAL &amp; AGREEMENT SYSTEM</div>
      <h2 style="margin:8px 0 0;font-size:20px;font-weight:700;">PERMOHONAN REGISTRASI AKUN BARU</h2>
    </div>

    <div style="background:#fef3c7;color:#92400e;padding:12px 28px;font-weight:700;font-size:13.5px;border-bottom:1px solid #fde68a;">
      &#9203; Menunggu Persetujuan (Approval) Superadmin
    </div>

    <div style="padding:28px;">
      <p style="margin-top:0;font-size:15px;line-height:1.6;color:#334155;">
        Halo <strong>Superadmin Medquest</strong>,
      </p>
      <p style="font-size:14px;line-height:1.6;color:#475569;">
        Seorang pengguna baru telah mendaftarkan akun di <strong>Sistem Monitoring Dokumen Legal Medquest</strong> dan memerlukan verifikasi serta persetujuan Anda:
      </p>

      <table style="width:100%;border-collapse:collapse;margin:20px 0;font-size:13.5px;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:700;width:35%;color:#475569;">Nama Lengkap</td>
          <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:700;color:#0f172a;">' . e($user->nama_lengkap) . '</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:700;color:#475569;">Inisial / Kode User</td>
          <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:700;color:#0059fb;"><span style="background:#eff6ff;padding:2px 8px;border-radius:4px;border:1px solid #bfdbfe;">' . e($user->kode_singkatan) . '</span></td>
        </tr>
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:700;color:#475569;">Departemen / Divisi</td>
          <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:600;color:#0f172a;">' . e($user->departemen_default) . '</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:700;color:#475569;">Alamat Email</td>
          <td style="padding:10px 14px;border:1px solid #e2e8f0;color:#0f172a;">' . e($user->email) . '</td>
        </tr>
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #e2e8f0;font-weight:700;color:#475569;">Waktu Mendaftar</td>
          <td style="padding:10px 14px;border:1px solid #e2e8f0;color:#64748b;">' . $regTime . ' WIB</td>
        </tr>
      </table>

      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:18px 20px;margin:24px 0 20px;text-align:center;">
        <div style="font-weight:700;font-size:14.5px;color:#166534;margin-bottom:12px;">
          Persetujuan Cepat Langsung Dari Gmail (1-Click ACC):
        </div>
        <div style="margin-bottom:8px;">
          <a href="' . e($approveUrl) . '" style="background:#16a34a;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:6px;font-weight:700;font-size:14px;display:inline-block;box-shadow:0 3px 6px rgba(22,163,74,0.3);margin:4px;">
            &#10004; SETUJUI / ACC AKUN INI
          </a>
          <a href="' . e($rejectUrl) . '" style="background:#fee2e2;color:#dc2626;border:1px solid #f87171;text-decoration:none;padding:11px 20px;border-radius:6px;font-weight:600;font-size:13px;display:inline-block;margin:4px;">
            &#10006; Tolak
          </a>
        </div>
        <div style="font-size:12px;color:#15803d;margin-top:8px;">
          Cukup klik tombol hijau di atas untuk langsung mengaktifkan akun pendaftar tanpa perlu login web.
        </div>
      </div>

      <div style="text-align:center;margin-top:16px;">
        <a href="' . e($dashboardUrl) . '" style="color:#0059fb;text-decoration:none;font-size:13px;font-weight:600;">
          Atau kelola lewat Menu User Approvals di Web &rarr;
        </a>
      </div>
    </div>

    <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 28px;font-size:12px;color:#64748b;text-align:center;">
      Email otomatis dari Sistem Monitoring Dokumen Legal PT Medquest Jaya Global.<br>
      Jika tombol di atas tidak dapat diklik, salin dan buka tautan berikut:<br>
      <span style="color:#0059fb;word-break:break-all;font-size:11px;">' . e($approveUrl) . '</span>
    </div>
  </div>
</body>
</html>';
    }

    public static function buildUserApprovedEmailHtml(User $user): string
    {
        $loginUrl = base_url('?page=login');

        return '
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Akun Medquest Disetujui</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Open Sans,Helvetica,Arial,sans-serif;color:#282828;">
  <div style="max-width:600px;margin:24px auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;">
    <div style="background:linear-gradient(135deg,#101399 0%,#0059fb 70%,#2ea3f2 100%);padding:22px 28px;color:#ffffff;">
      <div style="font-size:11px;letter-spacing:1px;text-transform:uppercase;opacity:0.9;">PT Medquest Jaya Global</div>
      <h2 style="margin:6px 0 0;font-size:20px;font-weight:700;">AKUN ANDA TELAH DISETUJUI (APPROVED)</h2>
    </div>
    <div style="background:#16a34a;color:#ffffff;padding:10px 28px;font-weight:700;font-size:13.5px;">
      &#10004; Akun Telah Aktif dan Siap Digunakan
    </div>
    <div style="padding:28px;">
      <p style="margin-top:0;font-size:15px;line-height:1.6;color:#334155;">
        Halo <strong>' . e($user->nama_lengkap) . ' (' . e($user->kode_singkatan) . ')</strong>,
      </p>
      <p style="font-size:14px;line-height:1.6;color:#475569;">
        Kabar baik! Pendaftaran akun Anda di <strong>Sistem Monitoring Dokumen Legal Medquest</strong> telah <strong>disetujui (ACC)</strong> oleh Administrator.
      </p>
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:13.5px;">
        <div style="margin-bottom:6px;"><strong>Departemen:</strong> ' . e($user->departemen_default) . '</div>
        <div style="margin-bottom:6px;"><strong>Email:</strong> ' . e($user->email) . '</div>
        <div><strong>Status:</strong> <span style="color:#16a34a;font-weight:bold;">Aktif (Approved)</span></div>
      </div>
      <div style="text-align:center;margin:24px 0 10px;">
        <a href="' . e($loginUrl) . '" style="background:#0059fb;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:6px;font-weight:700;font-size:14px;display:inline-block;box-shadow:0 3px 6px rgba(0,89,251,0.3);">
          Login ke Sistem Sekarang &rarr;
        </a>
      </div>
    </div>
    <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:12px 28px;font-size:11.5px;color:#64748b;text-align:center;">
      PT Medquest Jaya Global &bull; Legal and Compliance Department
    </div>
  </div>
</body>
</html>';
    }

    public static function dispatchUserRegistrationApproval(User $user): array
    {
        $admins = User::whereIn('role', ['superadmin', 'admin'])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        $adminEmails = [];
        foreach ($admins as $adm) {
            if (filter_var($adm->email, FILTER_VALIDATE_EMAIL)) {
                $adminEmails[] = $adm->email;
            }
        }

        $smtp = SmtpSetting::find(1);
        if ($smtp && !empty($smtp->sender_email) && filter_var($smtp->sender_email, FILTER_VALIDATE_EMAIL)) {
            $adminEmails[] = $smtp->sender_email;
        }

        if (empty($adminEmails)) {
            $adminEmails[] = 'fjr.medquest@gmail.com';
        }
        $adminEmails = array_values(array_unique($adminEmails));

        $subject = "[ACTION REQUIRED] Permohonan Registrasi Akun: " . $user->nama_lengkap . " (" . $user->kode_singkatan . ") - Dept " . $user->departemen_default;
        $htmlBody = self::buildUserApprovalEmailHtml($user);

        // Always save a copy in mail outbox for reference & offline testing
        $outboxDir = storage_path('app/mail_outbox');
        if (!is_dir($outboxDir)) {
            @mkdir($outboxDir, 0775, true);
        }
        $safeFile = 'approval_user_' . $user->id . '_' . date('Ymd_His') . '.html';
        @file_put_contents($outboxDir . '/' . $safeFile, $htmlBody);

        $sentCount = 0;
        foreach ($adminEmails as $email) {
            $err = null;
            $ok = false;
            if ($smtp && $smtp->smtp_enabled && !empty($smtp->smtp_username) && !empty($smtp->smtp_password)) {
                $ok = self::sendViaDynamicSmtp($smtp, $email, $subject, $htmlBody, $err);
            }
            if ($ok) {
                $sentCount++;
            }
        }

        return [
            'success'   => $sentCount > 0,
            'recipients'=> $adminEmails,
            'outbox'    => $safeFile,
        ];
    }

    public static function dispatchUserApprovedNotification(User $user): array
    {
        if (empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'User does not have a valid email.'];
        }

        $subject = "[MEDQUEST] Akun Anda Telah Disetujui (Approved) - Silakan Login";
        $htmlBody = self::buildUserApprovedEmailHtml($user);

        $outboxDir = storage_path('app/mail_outbox');
        if (!is_dir($outboxDir)) {
            @mkdir($outboxDir, 0775, true);
        }
        $safeFile = 'approved_notify_user_' . $user->id . '_' . date('Ymd_His') . '.html';
        @file_put_contents($outboxDir . '/' . $safeFile, $htmlBody);

        $smtp = SmtpSetting::find(1);
        $ok = false;
        $err = null;
        if ($smtp && $smtp->smtp_enabled && !empty($smtp->smtp_username) && !empty($smtp->smtp_password)) {
            $ok = self::sendViaDynamicSmtp($smtp, $user->email, $subject, $htmlBody, $err);
        }

        return [
            'success' => $ok,
            'email'   => $user->email,
            'outbox'  => $safeFile,
        ];
    }
}
