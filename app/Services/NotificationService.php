<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\EmailNotification;
use App\Models\NotificationRecipient;
use App\Models\SmtpSetting;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    public static function getSmtpStatus(): array
    {
        try {
            if (Schema::hasTable('smtp_settings')) {
                $smtp = SmtpSetting::find(1);
                if ($smtp && $smtp->smtp_enabled && !empty($smtp->smtp_username) && !empty($smtp->smtp_password)) {
                    return [
                        'ready'        => true,
                        'source'       => 'db',
                        'sender_email' => $smtp->sender_email ?: $smtp->smtp_username,
                        'host'         => $smtp->smtp_host ?: 'smtp.gmail.com',
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        $mailer = (string) config('mail.default', 'log');
        $envUser = trim((string) config('mail.mailers.smtp.username', ''));
        $envPass = trim((string) config('mail.mailers.smtp.password', ''));
        if (
            $mailer === 'smtp'
            && $envUser !== ''
            && strtolower($envUser) !== 'null'
            && $envPass !== ''
            && strtolower($envPass) !== 'null'
        ) {
            return [
                'ready'        => true,
                'source'       => 'env',
                'sender_email' => (string) (config('mail.from.address') ?: $envUser),
                'host'         => (string) config('mail.mailers.smtp.host', 'smtp.gmail.com'),
            ];
        }

        return [
            'ready'        => false,
            'source'       => 'relay',
            'sender_email' => 'FormSubmit Cloud Relay',
            'host'         => 'formsubmit.co',
        ];
    }

    public static function resolveDueMilestoneCode(int $daysRemaining): ?string
    {
        if ($daysRemaining === 0) {
            return 'H-0';
        }
        if ($daysRemaining >= 1 && $daysRemaining <= 3) {
            return 'H-3';
        }
        if ($daysRemaining >= 4 && $daysRemaining <= 7) {
            return 'H-7';
        }
        if ($daysRemaining >= 8 && $daysRemaining <= 14) {
            return 'H-14';
        }
        if ($daysRemaining >= 15 && $daysRemaining <= 30) {
            return 'H-30';
        }
        if ($daysRemaining >= 31 && $daysRemaining <= 60) {
            return 'H-60';
        }
        if ($daysRemaining >= 61 && $daysRemaining <= 90) {
            return 'H-90';
        }
        return null;
    }

    public static function buildExpiryEmailHtml(array $agreement, array $recipient, string $tipeNotif, int $daysRemaining): string
    {
        $badgeColor = '#0059fb';
        $urgencyText = "{$daysRemaining} Days Remaining ({$tipeNotif})";

        if ($daysRemaining <= 0 || $tipeNotif === 'H-0') {
            $badgeColor = '#b91c1c';
            $urgencyText = 'TODAY IS THE EXPIRY DEADLINE (HARI H / H-0) - IMMEDIATE ACTION REQUIRED!';
        } elseif ($daysRemaining <= 3 || $tipeNotif === 'H-3') {
            $badgeColor = '#dc2626';
            $urgencyText = "{$daysRemaining} Days Left ({$tipeNotif}) - URGENT ACTION REQUIRED!";
        } elseif ($daysRemaining <= 7 || $tipeNotif === 'H-7') {
            $badgeColor = '#ea580c';
            $urgencyText = "{$daysRemaining} Days Left ({$tipeNotif}) - Immediate Follow-up Needed";
        } elseif ($daysRemaining <= 14 || $tipeNotif === 'H-14') {
            $badgeColor = '#d97706';
            $urgencyText = "{$daysRemaining} Days Left ({$tipeNotif}) - Final Renewal Preparation";
        } elseif ($daysRemaining <= 30 || $tipeNotif === 'H-30') {
            $badgeColor = '#ca8a04';
            $urgencyText = "{$daysRemaining} Days Left ({$tipeNotif}) - 1-Month Pre-Expiry Review";
        } elseif ($daysRemaining <= 60 || $tipeNotif === 'H-60') {
            $badgeColor = '#0284c7';
            $urgencyText = "{$daysRemaining} Days Left ({$tipeNotif}) - 2-Month Expiry Reminder";
        } elseif ($daysRemaining <= 90 || $tipeNotif === 'H-90') {
            $badgeColor = '#4f46e5';
            $urgencyText = "{$daysRemaining} Days Left ({$tipeNotif}) - 3-Month Early Expiry Notice";
        }

        $docTypeMeta = get_doc_type_meta($agreement['doc_type'] ?? 'COOPERATION');
        $docName = !empty($agreement['document_title'])
            ? $agreement['document_title']
            : ($agreement['customer'] ?? '-');
        $effDateStr = format_date_medquest($agreement['effective_date'] ?? null, !empty($agreement['effective_date_na']));
        $expDateStr = format_date_medquest($agreement['expiry_date'] ?? null, false);
        $docNoHtml  = nl2br(e($agreement['document_number'] ?? 'N/A'));
        $noteHtml   = nl2br(e(($agreement['note'] ?? '') ?: 'N/A'));
        $createdLog = nl2br(e($agreement['created_by_log'] ?? ''));
        $appUrl     = base_url('?page=agreements&dept=' . urlencode((string) ($agreement['department'] ?? 'ALL')));

        $schedule = get_deadline_schedule($agreement['expiry_date'] ?? null, $agreement);
        $scheduleHtml = '';
        foreach ($schedule as $code => $sItem) {
            $isCurrent = ($code === $tipeNotif);
            $bgPill = $isCurrent ? '#fef08a' : '#f8fafc';
            $borderPill = $isCurrent ? '2px solid #ca8a04' : '1px solid #cbd5e1';
            $displayCode = ($code === 'H-0') ? 'HARI H' : $code;
            $scheduleHtml .= '
              <td style="padding:8px 4px;text-align:center;background:' . $bgPill . ';border:' . $borderPill . ';">
                <div style="font-weight:800;font-size:11px;color:#0059fb;">' . $displayCode . '</div>
                <div style="font-size:10px;color:#334155;margin-top:2px;">' . $sItem['date_text'] . '</div>
              </td>';
        }

        $stageHeading = ($tipeNotif === 'H-0') ? 'HARI H (H-0)' : $tipeNotif;
        $recName = e($recipient['nama_lengkap'] ?? $recipient['email'] ?? 'Team');
        $recCode = e($recipient['kode_singkatan'] ?? 'PIC');

        return '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Deadline Notification ' . e($stageHeading) . ' - Medquest</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Open Sans,Helvetica,Arial,sans-serif;color:#282828;">
  <div style="max-width:680px;margin:24px auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;">
    <div style="background:linear-gradient(135deg,#101399 0%,#0059fb 65%,#2ea3f2 100%);padding:22px 28px;color:#ffffff;">
      <div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;opacity:0.85;">PT Medquest Jaya Global &bull; ' . e($docTypeMeta['title']) . '</div>
      <h2 style="margin:6px 0 0;font-size:20px;font-weight:700;">DOCUMENT DEADLINE REMINDER: ' . e($stageHeading) . '</h2>
    </div>

    <div style="background:' . $badgeColor . ';color:#ffffff;padding:12px 28px;font-weight:700;font-size:14px;">
      Automated Alert: ' . $urgencyText . '
    </div>

    <div style="padding:26px 28px;">
      <p style="margin-top:0;font-size:15px;line-height:1.6;">
        Hello <strong>' . $recName . ' (' . $recCode . ')</strong>,
      </p>
      <p style="font-size:14px;line-height:1.6;color:#475569;">
        The document <strong>' . e($docName) . '</strong> has reached reminder milestone <strong>' . e($stageHeading) . '</strong> before its expiration date (<em>Expiry Date: ' . $expDateStr . '</em>).
      </p>

      <div style="font-weight:700;font-size:13px;margin:18px 0 6px;color:#334155;">Reminder Schedule:</div>
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
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Title / Customer</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:600;">' . e($docName) . '</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Detail / Product</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . e($agreement['product_description'] ?? '-') . ' (S/N: ' . e(($agreement['serial_number'] ?? '') ?: 'N/A') . ')</td>
        </tr>
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Effective Date</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . $effDateStr . '</td>
        </tr>
        <tr style="background:#fef2f2;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#dc2626;">Expiry Date</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:800;color:#dc2626;font-size:15px;">' . $expDateStr . ' (' . e($stageHeading) . ')</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Note</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;">' . $noteHtml . '</td>
        </tr>
        <tr style="background:#f8fafc;">
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">Created By</td>
          <td style="padding:10px 14px;border:1px solid #cbd5e1;font-family:monospace;font-weight:700;">' . $createdLog . '</td>
        </tr>
      </table>

      <div style="text-align:center;margin:26px 0 10px;">
        <a href="' . e($appUrl) . '" style="background:#0059fb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:4px;font-weight:700;font-size:14px;display:inline-block;">
          Open Department ' . e($agreement['department'] ?? '') . ' on Website
        </a>
      </div>
    </div>

    <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 28px;font-size:12px;color:#64748b;text-align:center;">
      Automated reminder sent to <strong>' . e($recipient['email'] ?? '') . '</strong> by Medquest Legal Document Monitoring.
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

    public static function sendHtmlViaConfiguredMailer(
        string $toEmail,
        string $subject,
        string $htmlBody,
        ?string &$channel = null,
        ?string &$errorMsg = null
    ): bool {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Invalid recipient email address.';
            return false;
        }

        $defaultMailer = (string) config('mail.default', 'log');
        if (in_array($defaultMailer, ['array', 'log'], true)) {
            try {
                Mail::html($htmlBody, function ($message) use ($toEmail, $subject) {
                    $message->to($toEmail)->subject($subject);
                });
                $channel = 'SENT_LARAVEL_MAIL';
                return true;
            } catch (\Throwable $e) {
                $errorMsg = $e->getMessage();
                return false;
            }
        }

        try {
            if (Schema::hasTable('smtp_settings')) {
                $smtp = SmtpSetting::find(1);
                if ($smtp && $smtp->smtp_enabled && !empty($smtp->smtp_username) && !empty($smtp->smtp_password)) {
                    $ok = self::sendViaDynamicSmtp($smtp, $toEmail, $subject, $htmlBody, $errorMsg);
                    if ($ok) {
                        $channel = 'SENT_GMAIL_SMTP';
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
        }

        $status = self::getSmtpStatus();
        if ($status['ready'] && $status['source'] === 'env') {
            try {
                Mail::html($htmlBody, function ($message) use ($toEmail, $subject) {
                    $message->to($toEmail)->subject($subject);
                });
                $channel = 'SENT_ENV_SMTP';
                return true;
            } catch (\Throwable $e) {
                $errorMsg = $e->getMessage();
                return false;
            }
        }

        $channel = 'NEEDS_RELAY';
        return false;
    }

    protected static function resolveOriginUrl(): string
    {
        $origin = rtrim((string) config('app.url', 'http://localhost'), '/');
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $origin = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }
        return $origin;
    }

    protected static function postToFormSubmitRelay(string $toEmail, array $payload): bool
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $origin = self::resolveOriginUrl();
        $url = 'https://formsubmit.co/ajax/' . rawurlencode($toEmail);
        $opts = [
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\nAccept: application/json\r\nOrigin: {$origin}\r\nReferer: {$origin}/\r\nUser-Agent: Mozilla/5.0\r\n",
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

    public static function sendViaZeroConfigCloudRelay(string $toEmail, string $subject, array $agreement, array $recipient, string $tipeNotif): bool
    {
        $docName = !empty($agreement['document_title'])
            ? $agreement['document_title']
            : ($agreement['customer'] ?? '-');
        $stageHeading = ($tipeNotif === 'H-0') ? 'HARI H (H-0)' : $tipeNotif;

        return self::postToFormSubmitRelay($toEmail, [
            '_subject'        => $subject,
            '_template'       => 'table',
            '_captcha'        => 'false',
            'Recipient'       => ($recipient['nama_lengkap'] ?? $toEmail) . ' (' . ($recipient['kode_singkatan'] ?? 'PIC') . ')',
            'Deadline_Stage'  => $stageHeading,
            'Department'      => $agreement['department'] ?? '',
            'Document_Number' => $agreement['document_number'] ?? '',
            'Title_Customer'  => $docName,
            'Product'         => ($agreement['product_description'] ?? '') . ' (S/N: ' . (($agreement['serial_number'] ?? '') ?: 'N/A') . ')',
            'Effective_Date'  => format_date_medquest($agreement['effective_date'] ?? null, !empty($agreement['effective_date_na'])),
            'Expiry_Date'     => format_date_medquest($agreement['expiry_date'] ?? null, false),
            'Note'            => ($agreement['note'] ?? '') ?: 'N/A',
            'Created_By'      => $agreement['created_by_log'] ?? '',
        ]);
    }

    public static function getRecipientsForAgreement(array $agreement): array
    {
        $targets = [];

        $creator = !empty($agreement['created_by_user_id']) ? User::find($agreement['created_by_user_id']) : null;
        if (!$creator && !empty($agreement['created_by_code'])) {
            $creator = User::where('kode_singkatan', $agreement['created_by_code'])->first();
        }
        if ($creator && !empty($creator->email) && filter_var($creator->email, FILTER_VALIDATE_EMAIL)) {
            $emailKey = strtolower(trim($creator->email));
            $targets[$emailKey] = [
                'user_id'        => (int) $creator->id,
                'email'          => trim($creator->email),
                'kode_singkatan' => strtoupper((string) $creator->kode_singkatan),
                'nama_lengkap'   => (string) $creator->nama_lengkap,
            ];
        }

        try {
            if (Schema::hasTable('notification_recipients')) {
                $dept = strtoupper(trim((string) ($agreement['department'] ?? '')));
                $subscribers = NotificationRecipient::where('is_active', true)
                    ->where(function ($q) use ($dept) {
                        $q->where('department', 'ALL');
                        if ($dept !== '') {
                            $q->orWhere('department', $dept);
                        }
                    })
                    ->get();

                foreach ($subscribers as $sub) {
                    $subEmail = strtolower(trim((string) $sub->email));
                    if ($subEmail === '' || !filter_var($subEmail, FILTER_VALIDATE_EMAIL) || isset($targets[$subEmail])) {
                        continue;
                    }
                    $targets[$subEmail] = [
                        'user_id'        => (int) ($sub->added_by_user_id ?: ($creator->id ?? 0)),
                        'email'          => $subEmail,
                        'kode_singkatan' => 'ALERT',
                        'nama_lengkap'   => (string) ($sub->label ?: $subEmail),
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        return array_values($targets);
    }

    public static function dispatchAgreementNotification(Agreement $agreementModel, string $tipeNotif, bool $forceSend = false): array
    {
        $agreement = $agreementModel->toArray();
        $targets = self::getRecipientsForAgreement($agreement);
        if (empty($targets)) {
            return ['success' => false, 'results' => [], 'message' => 'No valid recipient email addresses found.'];
        }

        $days = get_days_until_expiry($agreement['expiry_date']);
        $expFormatted = format_date_medquest($agreement['expiry_date'], false);
        $stageSubject = ($tipeNotif === 'H-0') ? 'HARI H (H-0)' : $tipeNotif;
        $docLabel = !empty($agreement['document_title'])
            ? $agreement['document_title']
            : ($agreement['customer'] ?? '-');
        $subject = "[MEDQUEST {$stageSubject}] Deadline Reminder ({$expFormatted}) - Dept {$agreement['department']} - {$docLabel}";

        $smtpStatus = self::getSmtpStatus();
        $deliveredStatuses = ['SENT_GMAIL_SMTP', 'SENT_ENV_SMTP', 'SENT_LARAVEL_MAIL', 'SENT TO GMAIL'];
        $existingLogs = EmailNotification::where('agreement_id', $agreement['id'])
            ->where('tipe_notif', $tipeNotif)
            ->get(['recipient_email', 'status_kirim']);

        $alreadyDeliveredEmails = [];
        $alreadyWaitingEmails = [];
        foreach ($existingLogs as $log) {
            $em = strtolower(trim((string) $log->recipient_email));
            if (in_array((string) $log->status_kirim, $deliveredStatuses, true)) {
                $alreadyDeliveredEmails[$em] = true;
            } else {
                $alreadyWaitingEmails[$em] = true;
            }
        }

        $outboxDir = storage_path('app/mail_outbox');
        if (!is_dir($outboxDir)) {
            @mkdir($outboxDir, 0775, true);
        }

        $dispatchedList = [];
        $anySuccess = false;

        foreach ($targets as $target) {
            $emailKey = strtolower(trim((string) $target['email']));
            if (!$forceSend && isset($alreadyDeliveredEmails[$emailKey])) {
                continue;
            }
            if (!$forceSend && !$smtpStatus['ready'] && isset($alreadyWaitingEmails[$emailKey])) {
                continue;
            }

            $htmlBody = self::buildExpiryEmailHtml($agreement, $target, $tipeNotif, $days);
            $safeFile = 'email_' . strtolower(str_replace('-', '', $tipeNotif)) . '_agr' . $agreement['id'] . '_' . substr(md5($emailKey), 0, 6) . '_' . date('Ymd_His') . '.html';
            @file_put_contents($outboxDir . '/' . $safeFile, $htmlBody);

            $channel = null;
            $sendErr = null;
            $sentOk = self::sendHtmlViaConfiguredMailer($target['email'], $subject, $htmlBody, $channel, $sendErr);
            if (!$sentOk && $channel === 'NEEDS_RELAY') {
                $sentOk = self::sendViaZeroConfigCloudRelay($target['email'], $subject, $agreement, $target, $tipeNotif);
                if ($sentOk) {
                    $channel = 'SENT TO GMAIL';
                }
            }

            if ($sentOk) {
                $anySuccess = true;
                $statusKirim = $channel ?: 'SENT_GMAIL_SMTP';
                $keterangan  = 'Automatically sent to ' . $target['email'];
            } else {
                $statusKirim = $sendErr ? 'SMTP_FAILED' : 'AWAITING ACTIVATE CLICK IN GMAIL';
                $keterangan  = $sendErr ?: ('Check Inbox/Spam ' . $target['email'] . ' or configure System SMTP.');
            }

            EmailNotification::create([
                'agreement_id'      => $agreement['id'],
                'user_id'           => $target['user_id'] ?? 0,
                'recipient_email'   => $target['email'],
                'recipient_code'    => $target['kode_singkatan'] ?? 'PIC',
                'tipe_notif'        => $tipeNotif,
                'days_remaining'    => $days,
                'subject'           => $subject,
                'body_html'         => $htmlBody,
                'status_kirim'      => $statusKirim,
                'keterangan_status' => $keterangan,
                'is_read'           => false,
                'sent_at'           => now(),
            ]);

            $dispatchedList[] = [
                'success'   => $sentOk,
                'recipient' => $target['email'],
                'kode'      => $target['kode_singkatan'] ?? 'PIC',
                'customer'  => $docLabel,
                'tipe'      => $stageSubject,
                'status'    => $statusKirim,
                'message'   => $keterangan,
            ];
        }

        if ($anySuccess) {
            $colMap = [
                'H-90' => 'notif_h90_sent_at',
                'H-60' => 'notif_h60_sent_at',
                'H-30' => 'notif_h30_sent_at',
                'H-14' => 'notif_h14_sent_at',
                'H-7'  => 'notif_h7_sent_at',
                'H-3'  => 'notif_h3_sent_at',
                'H-1'  => 'notif_h1_sent_at',
                'H-0'  => 'notif_h0_sent_at',
            ];
            if (isset($colMap[$tipeNotif]) && Schema::hasColumn('agreements', $colMap[$tipeNotif])) {
                $agreementModel->update([$colMap[$tipeNotif] => now()]);
            }
        }

        if (empty($dispatchedList)) {
            return ['success' => false, 'skipped' => true, 'results' => [], 'message' => 'All recipients already notified for this stage.'];
        }

        $first = $dispatchedList[0];
        return [
            'success'   => $anySuccess,
            'recipient' => $first['recipient'],
            'kode'      => $first['kode'],
            'customer'  => $first['customer'],
            'tipe'      => $first['tipe'],
            'status'    => $first['status'],
            'message'   => $first['message'],
            'results'   => $dispatchedList,
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

            $query = Agreement::where('expiry_date_na', 0)
                ->whereNotNull('expiry_date');
            if (Schema::hasColumn('agreements', 'status_proses')) {
                $query->where(function ($q) {
                    $q->whereNull('status_proses')
                      ->orWhere('status_proses', '!=', 'SELESAI');
                });
            }
            $agreements = $query->get();

            foreach ($agreements as $agr) {
                $sisa = get_days_until_expiry($agr->expiry_date);
                $dueStage = self::resolveDueMilestoneCode($sisa);
                if ($dueStage !== null) {
                    $res = self::dispatchAgreementNotification($agr, $dueStage, false);
                    if (!empty($res['results'])) {
                        foreach ($res['results'] as $item) {
                            if (!empty($item['success'])) {
                                $results[] = $item;
                            }
                        }
                    } elseif (!empty($res['success'])) {
                        $results[] = $res;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return $results;
    }

    public static function sendGmailNotificationPermission(User $user): bool
    {
        if (empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $subject = '[MEDQUEST] Account Registration & Email Reminder Active - ' . $user->nama_lengkap . ' (' . $user->kode_singkatan . ')';
        $html = '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;background:#f8fafc;padding:24px;">'
            . '<div style="max-width:560px;margin:auto;background:#ffffff;border:1px solid #cbd5e1;padding:24px;">'
            . '<h2 style="margin-top:0;color:#1e3a8a;">PT Medquest Jaya Global</h2>'
            . '<p>Halo <strong>' . e($user->nama_lengkap) . ' (' . e($user->kode_singkatan) . ')</strong>,</p>'
            . '<p>Pendaftaran akun Anda pada departemen <strong>' . e($user->departemen_default) . '</strong> telah diterima dan email Anda otomatis terdaftar sebagai penerima reminder deadline dokumen (<strong>H-90, H-60, H-30, H-14, H-7, H-3, dan Hari H</strong>).</p>'
            . '<p>Status akun saat ini menunggu persetujuan Superadmin di halaman <em>Account Approvals</em>.</p>'
            . '</div></body></html>';

        $channel = null;
        if (self::sendHtmlViaConfiguredMailer($user->email, $subject, $html, $channel)) {
            return true;
        }

        return self::postToFormSubmitRelay($user->email, [
            '_subject'        => $subject,
            '_template'       => 'table',
            '_captcha'        => 'false',
            'Informasi'       => 'Pendaftaran akun Sistem Legal Medquest berhasil diterima.',
            'Nama_Lengkap'    => $user->nama_lengkap,
            'Inisial_User'    => $user->kode_singkatan,
            'Departemen'      => $user->departemen_default,
            'Email_Pendaftar' => $user->email,
            'Status_Akun'     => 'Menunggu verifikasi Superadmin di Halaman Account Approvals',
            'Petunjuk'        => 'Klik tombol aktivasi di email ini jika menggunakan relay FormSubmit agar Gmail diizinkan menerima reminder otomatis.',
        ]);
    }

    public static function dispatchUserApprovedNotification(User $user): bool
    {
        if (empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $loginUrl = base_url('?page=login');
        $subject = '[MEDQUEST] Your Account Has Been Approved - ' . $user->kode_singkatan;
        $html = '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;background:#f8fafc;padding:24px;">'
            . '<div style="max-width:560px;margin:auto;background:#ffffff;border:1px solid #cbd5e1;padding:24px;">'
            . '<h2 style="margin-top:0;color:#15803d;">Account Approved</h2>'
            . '<p>Hello <strong>' . e($user->nama_lengkap) . ' (' . e($user->kode_singkatan) . ')</strong>,</p>'
            . '<p>Your Medquest Legal Document Monitoring account has been approved by the Superadmin. You can now sign in and manage your deadline reminders.</p>'
            . '<p><a href="' . e($loginUrl) . '" style="display:inline-block;background:#1e3a8a;color:#ffffff;padding:10px 18px;text-decoration:none;font-weight:bold;">Sign In Now</a></p>'
            . '</div></body></html>';

        $channel = null;
        if (self::sendHtmlViaConfiguredMailer($user->email, $subject, $html, $channel)) {
            return true;
        }

        return self::postToFormSubmitRelay($user->email, [
            '_subject'     => $subject,
            '_template'    => 'table',
            '_captcha'     => 'false',
            'Status'       => 'APPROVED',
            'Nama_Lengkap' => $user->nama_lengkap,
            'Kode_User'    => $user->kode_singkatan,
            'Login_URL'    => $loginUrl,
        ]);
    }
}
