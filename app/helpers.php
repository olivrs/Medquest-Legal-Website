<?php

use Illuminate\Support\Facades\Auth;

if (!defined('DEADLINE_MILESTONES')) {
    define('DEADLINE_MILESTONES', [90, 60, 30, 14, 7, 3]);
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        return rtrim(url('/'), '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return Auth::check();
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        return [
            'id'                  => $user->id,
            'nama_lengkap'        => $user->nama_lengkap,
            'kode_singkatan'      => strtoupper((string) $user->kode_singkatan),
            'email'               => $user->email,
            'departemen_default'  => $user->departemen_default,
            'allowed_departments' => $user->allowed_departments ?? null,
            'role'                => $user->role,
            'status'              => $user->status,
        ];
    }
}

if (!function_exists('get_user_allowed_departments')) {
    /**
     * Ambil daftar kode departemen yang diizinkan untuk seorang user:
     * - Jika role adalah superadmin / admin: mengembalikan ['ALL']
     * - Jika user biasa dan kolom allowed_departments sudah diatur:
     *   - Jika bernilai 'NONE': mengembalikan [] (tidak ada departemen yang dicentang)
     *   - Selain itu: pecah string koma menjadi array kode departemen (contoh: ['GA', 'MHO'])
     * - Jika allowed_departments masih NULL (belum pernah diubah): fallback ke departemen_default
     */
    function get_user_allowed_departments(?array $userRow): array
    {
        if (!$userRow) {
            return [];
        }
        $role = strtolower(trim((string) ($userRow['role'] ?? 'user')));
        if (in_array($role, ['superadmin', 'admin'], true)) {
            return ['ALL'];
        }

        $rawAllowed = $userRow['allowed_departments'] ?? null;
        if ($rawAllowed !== null && trim((string) $rawAllowed) !== '') {
            $trimmed = strtoupper(trim((string) $rawAllowed));
            if ($trimmed === 'NONE') {
                return [];
            }
            return array_values(array_filter(array_map('trim', explode(',', $trimmed))));
        }

        $defaultDept = strtoupper(trim((string) ($userRow['departemen_default'] ?? '')));
        if ($defaultDept === '' || $defaultDept === 'NONE') {
            return [];
        }
        if ($defaultDept === 'ALL') {
            return ['ALL'];
        }

        return array_values(array_filter(array_map('trim', explode(',', $defaultDept))));
    }
}

if (!function_exists('is_superadmin')) {
    function is_superadmin(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->isSuperadmin();
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void
    {
        session()->flash('medquest_flash', [
            'type'    => $type,
            'message' => $message,
        ]);
    }
}

if (!function_exists('get_flash')) {
    function get_flash(): ?array
    {
        return session('medquest_flash');
    }
}

if (!function_exists('get_doc_type_meta')) {
    function get_doc_type_meta(?string $doc_type): array
    {
        $type = strtoupper(trim($doc_type ?: 'COOPERATION'));
        return match ($type) {
            'COMPANY' => [
                'code'         => 'COMPANY',
                'title'        => 'ORIGINAL COMPANY DOCUMENT',
                'short_title'  => 'ORIGINAL COMPANY DOC',
                'form_no'      => 'MJG-FOR-LAC.12-001-04',
                'badge_bg'     => '#f0fdf4',
                'badge_color'  => '#166534',
                'badge_border' => '#bbf7d0',
            ],
            'PRINCIPAL' => [
                'code'         => 'PRINCIPAL',
                'title'        => 'LOG OF PRINCIPAL DOCUMENT',
                'short_title'  => 'PRINCIPAL DOCUMENT',
                'form_no'      => 'MJG-FOR-LAC.12-001-09',
                'badge_bg'     => '#faf5ff',
                'badge_color'  => '#6b21a8',
                'badge_border' => '#e9d5ff',
            ],
            default => [
                'code'         => 'COOPERATION',
                'title'        => 'LOG OF COOPERATION AGREEMENT',
                'short_title'  => 'COOPERATION AGREEMENT',
                'form_no'      => 'MJG-FOR-LAC.12-001-03',
                'badge_bg'     => '#eff6ff',
                'badge_color'  => '#1e40af',
                'badge_border' => '#bfdbfe',
            ],
        };
    }
}

if (!function_exists('format_date_medquest')) {
    function format_date_medquest($date, $is_na = false): string
    {
        if ($is_na || empty($date) || $date === '0000-00-00') {
            return 'N/A';
        }
        $ts = strtotime((string) $date);
        if (!$ts) {
            return 'N/A';
        }
        return strtoupper(date('d M Y', $ts));
    }
}

if (!function_exists('format_created_by_stamp')) {
    function format_created_by_stamp(string $kode_singkatan, ?string $date = null): string
    {
        $kode = strtoupper(trim($kode_singkatan));
        $ts = $date ? strtotime($date) : time();
        return $kode . ', ' . strtoupper(date('d M Y', $ts));
    }
}

if (!function_exists('get_days_until_expiry')) {
    function get_days_until_expiry($expiry_date): int
    {
        if (empty($expiry_date) || $expiry_date === '0000-00-00' || strtoupper((string) $expiry_date) === 'N/A') {
            return 9999;
        }
        $today = new DateTime(date('Y-m-d'));
        $exp = new DateTime(date('Y-m-d', strtotime((string) $expiry_date)));
        $diff = $today->diff($exp);
        return (int) $diff->format('%r%a');
    }
}

if (!function_exists('get_deadline_schedule')) {
    function get_deadline_schedule($expiry_date, array $agreementRow = []): array
    {
        if (empty($expiry_date) || $expiry_date === '0000-00-00' || !empty($agreementRow['expiry_date_na'])) {
            return [];
        }
        $milestones = DEADLINE_MILESTONES;
        $expTs = strtotime((string) $expiry_date);
        if (!$expTs) {
            return [];
        }
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
                'sent_at'   => $agreementRow[$colSent] ?? null,
            ];
        }

        return $schedule;
    }
}

if (!function_exists('get_expiry_info')) {
    function get_expiry_info($expiry_date, $expiry_date_na = 0): array
    {
        if ($expiry_date_na || empty($expiry_date) || $expiry_date === '0000-00-00' || strtoupper((string) $expiry_date) === 'N/A') {
            return [
                'status'      => 'active',
                'row_class'   => 'row-active',
                'badge_class' => 'badge-active',
                'label'       => 'Berlaku Permanen (N/A)',
                'short_label' => 'N/A (Aktif)',
                'next_stage'  => 'N/A',
                'days'        => 9999,
            ];
        }
        $days = get_days_until_expiry($expiry_date);

        if ($days < 0) {
            return [
                'status'      => 'expired',
                'row_class'   => 'row-expired',
                'badge_class' => 'badge-expired',
                'label'       => 'Sudah Expired (' . abs($days) . ' hari lalu)',
                'short_label' => 'EXPIRED (' . abs($days) . ' hr lalu)',
                'next_stage'  => 'Kontrak Habis',
                'days'        => $days,
            ];
        } elseif ($days === 0) {
            return [
                'status'      => 'warning',
                'row_class'   => 'row-urgent row-h3',
                'badge_class' => 'badge-h3',
                'label'       => 'Expired Hari Ini! ⚠️',
                'short_label' => 'Hari Ini Expired ⚠️',
                'next_stage'  => 'Hari Ini',
                'days'        => 0,
            ];
        } elseif ($days <= 3) {
            return [
                'status'      => 'warning',
                'row_class'   => 'row-urgent row-h3',
                'badge_class' => 'badge-h3',
                'label'       => "Mendesak: Sisa {$days} Hari ⚠️",
                'short_label' => "Sisa {$days} Hari ⚠️",
                'next_stage'  => 'Sisa 3 Hari',
                'days'        => $days,
            ];
        } elseif ($days <= 7) {
            return [
                'status'      => 'warning',
                'row_class'   => 'row-warning',
                'badge_class' => 'badge-h7',
                'label'       => "Segera Expired (Sisa {$days} Hari)",
                'short_label' => "Sisa {$days} Hari",
                'next_stage'  => 'Sisa 7 Hari',
                'days'        => $days,
            ];
        } elseif ($days <= 14) {
            return [
                'status'      => 'warning',
                'row_class'   => 'row-warning',
                'badge_class' => 'badge-h14',
                'label'       => "Sisa {$days} Hari",
                'short_label' => "Sisa {$days} Hari",
                'next_stage'  => 'Sisa 14 Hari',
                'days'        => $days,
            ];
        } elseif ($days <= 30) {
            return [
                'status'      => 'warning',
                'row_class'   => 'row-warning-soft',
                'badge_class' => 'badge-h30',
                'label'       => "Sisa {$days} Hari",
                'short_label' => "Sisa {$days} Hari",
                'next_stage'  => 'Sisa 30 Hari',
                'days'        => $days,
            ];
        } elseif ($days <= 60) {
            return [
                'status'      => 'warning',
                'row_class'   => 'row-warning-soft',
                'badge_class' => 'badge-h60',
                'label'       => "Sisa {$days} Hari",
                'short_label' => "Sisa {$days} Hari",
                'next_stage'  => 'Sisa 60 Hari',
                'days'        => $days,
            ];
        } elseif ($days <= 90) {
            return [
                'status'      => 'warning',
                'row_class'   => 'row-warning-soft',
                'badge_class' => 'badge-h90',
                'label'       => "Sisa {$days} Hari",
                'short_label' => "Sisa {$days} Hari",
                'next_stage'  => 'Sisa 90 Hari',
                'days'        => $days,
            ];
        } else {
            return [
                'status'      => 'active',
                'row_class'   => 'row-active',
                'badge_class' => 'badge-active',
                'label'       => "Aman (Sisa {$days} Hari)",
                'short_label' => "Aman ({$days} hari)",
                'next_stage'  => 'Menunggu Deadline',
                'days'        => $days,
            ];
        }
    }
}
