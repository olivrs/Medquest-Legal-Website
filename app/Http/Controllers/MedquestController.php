<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\AgreementHistory;
use App\Models\Department;
use App\Models\EmailNotification;
use App\Models\SmtpSetting;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\XlsxExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MedquestController extends Controller
{
    protected function ensureDatabaseReady(): void
    {
        try {
            $revMarker = storage_path('framework/medquest_rev.txt');
            $currentRev = '2026-11-23-v15-export-page';
            if (!@file_exists($revMarker) || trim((string) @file_get_contents($revMarker)) !== $currentRev) {
                \Illuminate\Support\Facades\Artisan::call('view:clear');
                if (function_exists('opcache_invalidate')) {
                    @opcache_invalidate(app_path('helpers.php'), true);
                }
                if (function_exists('opcache_reset')) {
                    @opcache_reset();
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('agreement_histories')) {
                    try {
                        DB::statement("ALTER TABLE agreement_histories MODIFY COLUMN aksi VARCHAR(50) NOT NULL DEFAULT 'CREATE'");
                    } catch (\Throwable $e) {
                    }
                }
                @file_put_contents($revMarker, $currentRev);
            }

            if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', [
                    '--seed'  => true,
                    '--force' => true,
                ]);
            } else {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'allowed_departments')) {
                    \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->text('allowed_departments')->nullable();
                    });
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('agreements')) {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('agreements', 'status_proses')) {
                        \Illuminate\Support\Facades\Schema::table('agreements', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->string('status_proses', 30)->default('PENGAJUAN')->index();
                        });
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('agreements', 'tanggal_selesai')) {
                        \Illuminate\Support\Facades\Schema::table('agreements', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->date('tanggal_selesai')->nullable()->index();
                        });
                    }
                }
            }
        } catch (\Throwable $e) {
        }
    }

    protected function logAgreementHistory(int $agreementId, int $userId, string $kodeSingkatan, string $tanggalInput, string $catatan, string $aksi): void
    {
        try {
            AgreementHistory::create([
                'agreement_id'   => $agreementId,
                'user_id'        => $userId,
                'kode_singkatan' => $kodeSingkatan,
                'tanggal_input'  => $tanggalInput,
                'catatan'        => $catatan,
                'aksi'           => $aksi,
            ]);
        } catch (\Throwable $e) {
            try {
                AgreementHistory::create([
                    'agreement_id'   => $agreementId,
                    'user_id'        => $userId,
                    'kode_singkatan' => $kodeSingkatan,
                    'tanggal_input'  => $tanggalInput,
                    'catatan'        => $catatan,
                    'aksi'           => 'EDIT',
                ]);
            } catch (\Throwable $e2) {
            }
        }
    }
    public function handleRoot(Request $request)
    {
        $this->ensureDatabaseReady();
        $page = $request->query('page', Auth::check() ? 'agreements' : 'login');

        return match ($page) {
            'login'            => $this->loginPage($request),
            'register'         => $this->registerPage($request),
            'register_pending' => $this->registerPendingPage($request),
            'logout'           => $this->logout($request),
            'agreements'       => $this->agreementsPage($request),
            'create'           => $this->createPage($request),
            'edit'             => $this->editPage($request),
            'detail'           => $this->detailPage($request),
            'export'           => $this->exportPage($request),
            'notifications'    => $this->notificationsPage($request),
            'smtp_settings'    => $this->smtpSettingsPage($request),
            'user_approvals'   => $this->userApprovalsPage($request),
            default            => Auth::check() ? $this->agreementsPage($request) : $this->loginPage($request),
        };
    }
    protected function applyAgreementAccessScope($query, string $alias = '')
    {
        if (is_superadmin()) {
            return $query;
        }

        $prefix       = $alias !== '' ? $alias . '.' : '';
        $user         = current_user();
        $uId          = (int) ($user['id'] ?? 0);
        $uCode        = strtoupper(trim((string) ($user['kode_singkatan'] ?? '')));
        $allowedDepts = get_user_allowed_departments($user);

        if (in_array('ALL', $allowedDepts, true)) {
            return $query;
        }

        return $query->where(function ($q) use ($prefix, $uId, $uCode, $allowedDepts) {
            $q->where($prefix . 'created_by_user_id', $uId)
              ->orWhere($prefix . 'created_by_code', $uCode);

            if (!empty($allowedDepts)) {
                $q->orWhereIn($prefix . 'department', $allowedDepts);
            }
        });
    }
    protected function getLayoutData(Request $request, string $currPage): array
    {
        $this->ensureDatabaseReady();
        $currDept = strtoupper(trim((string) $request->query('dept', 'ALL')));
        $selectedProses = strtoupper(trim((string) $request->query('proses', 'ALL')));
        if (!in_array($selectedProses, ['ALL', 'PENGAJUAN', 'BERJALAN', 'SELESAI'], true)) {
            $selectedProses = 'ALL';
        }
        $user = current_user();

        $alertHCount = 0;
        $totalNotifCount = 0;
        $sidebarDepts = [];
        $userDeptCounts = [];
        $userTotalDocs = 0;
        $userPengajuanDocs = 0;
        $userBerjalanDocs = 0;
        $userSelesaiDocs = 0;
        $pendingApprovalsCount = 0;

        if (Auth::check() && $user) {
            $uId          = (int) $user['id'];
            $uCode        = strtoupper($user['kode_singkatan']);
            $allowedDepts = get_user_allowed_departments($user);

            $allDocsQuery = Agreement::query();
            $this->applyAgreementAccessScope($allDocsQuery);
            $cols = ['id', 'department', 'expiry_date', 'expiry_date_na'];
            if (\Illuminate\Support\Facades\Schema::hasColumn('agreements', 'status_proses')) {
                $cols[] = 'status_proses';
            }
            $allUserDocs = $allDocsQuery->get($cols);

            foreach ($allUserDocs as $doc) {
                $dCode = strtoupper((string) $doc->department);
                $userDeptCounts[$dCode] = ($userDeptCounts[$dCode] ?? 0) + 1;
                $userTotalDocs++;

                $stProses = strtoupper(trim((string) ($doc->status_proses ?? 'PENGAJUAN')));
                if ($stProses === 'BERJALAN') {
                    $userBerjalanDocs++;
                } elseif ($stProses === 'SELESAI') {
                    $userSelesaiDocs++;
                } else {
                    $userPengajuanDocs++;
                }

                if (!$doc->expiry_date_na && !empty($doc->expiry_date)) {
                    $days = get_days_until_expiry($doc->expiry_date);
                    if ($days >= 0 && $days <= 90) {
                        $alertHCount++;
                    }
                }
            }

            if (is_superadmin()) {
                $totalNotifCount = EmailNotification::count();
            } else {
                $accessibleIds = $allUserDocs->pluck('id')->all();
                $totalNotifCount = EmailNotification::where(function ($q) use ($uId, $uCode, $accessibleIds) {
                    $q->where('user_id', $uId)
                      ->orWhere('recipient_code', $uCode);
                    if (!empty($accessibleIds)) {
                        $q->orWhereIn('agreement_id', $accessibleIds);
                    }
                })->count();
            }

            $allDepts = Department::orderBy('id', 'asc')->get()->map->toArray()->all();
            if (is_superadmin() || in_array('ALL', $allowedDepts, true)) {
                $sidebarDepts = $allDepts;
            } else {
                $sidebarDepts = array_values(array_filter($allDepts, function ($d) use ($allowedDepts, $userDeptCounts) {
                    $code = strtoupper((string) ($d['kode_dept'] ?? ''));
                    return in_array($code, $allowedDepts, true) || !empty($userDeptCounts[$code]);
                }));
            }

            if (is_superadmin()) {
                $pendingApprovalsCount = User::where('status', 'pending')->count();
            }
        }

        return [
            'currPage'              => $currPage,
            'currDept'              => $currDept,
            'user'                  => $user,
            'alertHCount'           => $alertHCount,
            'totalNotifCount'       => $totalNotifCount,
            'sidebarDepts'          => $sidebarDepts,
            'userDeptCounts'        => $userDeptCounts,
            'userTotalDocs'         => $userTotalDocs,
            'userPengajuanDocs'     => $userPengajuanDocs,
            'userBerjalanDocs'      => $userBerjalanDocs,
            'userSelesaiDocs'       => $userSelesaiDocs,
            'selectedProses'        => $selectedProses,
            'pendingApprovalsCount' => $pendingApprovalsCount,
        ];
    }

    public function loginPage(Request $request)
    {
        $this->ensureDatabaseReady();
        if (Auth::check()) {
            return redirect(base_url('?page=agreements'));
        }

        if ($request->isMethod('post')) {
            $identitas = trim((string) $request->input('identitas', ''));
            $password  = (string) $request->input('password', '');

            if ($identitas === '' || $password === '') {
                set_flash('error', 'Please enter your Email / User Code and Password.');
            } else {
                $user = User::where('email', $identitas)
                    ->orWhere('kode_singkatan', strtoupper($identitas))
                    ->first();

                if ($user && Hash::check($password, $user->password)) {
                    $status = $user->status ?? 'approved';
                    if ($status === 'pending') {
                        set_flash('error', '<div class="fw-bold mb-1"><i class="fas fa-hourglass-half me-1 text-warning"></i> Account Under Review</div>Your account with code <strong>' . e($user->kode_singkatan) . '</strong> has been registered and is <strong>currently under review by the Medquest Team</strong>.<br><span class="small opacity-85">Please wait for Superadmin approval and check back periodically.</span>');
                        return redirect(base_url('?page=login'));
                    } elseif ($status === 'rejected') {
                        set_flash('error', '<div class="fw-bold mb-1"><i class="fas fa-times-circle me-1 text-danger"></i> Account Registration Rejected</div>Registration for account code <strong>' . e($user->kode_singkatan) . '</strong> was rejected by the Superadmin. Please contact the Medquest administrator.');
                        return redirect(base_url('?page=login'));
                    }

                    Auth::login($user);
                    $request->session()->regenerate();

                    set_flash('success', 'Welcome back, <strong>' . e($user->nama_lengkap) . ' (' . e($user->kode_singkatan) . ')</strong>!');
                    $redirectDept = ($user->departemen_default && $user->departemen_default !== 'ALL')
                        ? '&dept=' . urlencode($user->departemen_default)
                        : '';
                    return redirect(base_url('?page=agreements' . $redirectDept));
                }

                set_flash('error', 'Invalid Email / User Code or Password.');
                return redirect(base_url('?page=login'));
            }
        }

        return view('pages.login', $this->getLayoutData($request, 'login'));
    }

    public function registerPage(Request $request)
    {
        if (Auth::check()) {
            return redirect(base_url('?page=agreements'));
        }

        $departments = Department::orderBy('kode_dept', 'asc')->get()->map->toArray()->all();

        if ($request->isMethod('post')) {
            $nama_lengkap     = trim((string) $request->input('nama_lengkap', ''));
            $kode_singkatan   = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim((string) $request->input('kode_singkatan', ''))));
            $departemen       = strtoupper(trim((string) $request->input('departemen', '')));
            $email            = strtolower(trim((string) $request->input('email', '')));
            $password         = (string) $request->input('password', '');
            $password_confirm = (string) $request->input('password_confirm', '');

            if ($nama_lengkap === '' || $kode_singkatan === '' || $departemen === '' || $email === '' || $password === '') {
                set_flash('error', 'All fields are required (Full Name, Initial Code, Department, Email Address, and Password).');
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                set_flash('error', 'Invalid Email Address format.');
            } elseif (strlen($kode_singkatan) < 2 || strlen($kode_singkatan) > 10) {
                set_flash('error', 'Medquest Initial Code must be between 2 and 10 characters (e.g., RYN or FJR).');
            } elseif (strlen($password) < 6) {
                set_flash('error', 'Password must be at least 6 characters.');
            } elseif ($password !== $password_confirm) {
                set_flash('error', 'Password confirmation does not match.');
            } else {
                $existing = User::where('email', $email)
                    ->orWhere('kode_singkatan', $kode_singkatan)
                    ->first();

                if ($existing) {
                    if (strtolower($existing->email) === $email) {
                        set_flash('error', 'Email address <strong>' . e($email) . '</strong> is already registered. Please use another email or contact the admin.');
                    } else {
                        set_flash('error', 'Initial code <strong>' . e($kode_singkatan) . '</strong> is already in use by another user.');
                    }
                } else {
                    User::create([
                        'nama_lengkap'       => $nama_lengkap,
                        'kode_singkatan'     => $kode_singkatan,
                        'email'              => $email,
                        'password'           => Hash::make($password),
                        'departemen_default' => $departemen,
                        'role'               => 'user',
                        'status'             => 'pending',
                    ]);

                    session([
                        'registered_pending' => [
                            'nama_lengkap'   => $nama_lengkap,
                            'kode_singkatan' => $kode_singkatan,
                            'email'          => $email,
                            'departemen'     => $departemen,
                            'registered_at'  => date('Y-m-d H:i:s'),
                        ],
                    ]);

                    return redirect(base_url('?page=register_pending'));
                }
            }
        }

        return view('pages.register', array_merge(
            $this->getLayoutData($request, 'register'),
            ['departments' => $departments]
        ));
    }

    public function registerPendingPage(Request $request)
    {
        return view('pages.register_pending', array_merge(
            $this->getLayoutData($request, 'register_pending'),
            ['pendingInfo' => session('registered_pending')]
        ));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        set_flash('success', 'You have been signed out.');
        return redirect(base_url('?page=login'));
    }

    public function agreementsPage(Request $request)
    {
        $this->ensureDatabaseReady();
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        $user  = current_user();
        $uId   = (int) $user['id'];
        $uCode = strtoupper($user['kode_singkatan']);

        try {
            NotificationService::runAutoExpiryNotifications();
        } catch (\Throwable $e) {
        }

        if ($request->isMethod('post')) {
            $action = (string) $request->input('action', '');

            if ($action === 'add_note') {
                $agrId       = (int) $request->input('agreement_id', 0);
                $catatanBaru = trim((string) $request->input('catatan_baru', ''));
                $tglInput    = parse_date_medquest((string) $request->input('tanggal_input', '')) ?? date('Y-m-d');
                $newExpiry   = parse_date_medquest((string) $request->input('new_expiry_date', ''));

                if ($request->filled('new_expiry_date') && $newExpiry === null) {
                    set_flash('error', 'Invalid New Expiry Date format. Please use dd/mm/yyyy.');
                    return redirect(base_url('?page=agreements&dept=' . urlencode((string) $request->input('return_dept', 'ALL'))));
                }

                $agrQuery = Agreement::where('id', $agrId);
                $this->applyAgreementAccessScope($agrQuery);
                $agr = $agrQuery->first();

                if ($agr && $catatanBaru !== '') {
                    $tglFormatted = format_date_medquest($tglInput, false);
                    $stampBaru    = format_created_by_stamp($uCode, $tglInput);

                    $barisNoteBaru = $tglFormatted . " :\n" . $catatanBaru;
                    $noteLama = trim((string) $agr->note);
                    if ($noteLama === '' || strtoupper($noteLama) === 'N/A') {
                        $noteFinal = $barisNoteBaru;
                    } else {
                        $noteFinal = $noteLama . "\n" . $barisNoteBaru;
                    }

                    $logLama = trim((string) $agr->created_by_log);
                    $linesLog = array_map('trim', explode("\n", $logLama));
                    if (!in_array($stampBaru, $linesLog, true)) {
                        $logFinal = $logLama !== '' ? ($logLama . "\n" . $stampBaru) : $stampBaru;
                    } else {
                        $logFinal = $logLama;
                    }

                    $updateData = [
                        'note'           => $noteFinal,
                        'created_by_log' => $logFinal,
                    ];

                    if ($newExpiry) {
                        $updateData = array_merge($updateData, [
                            'expiry_date'       => $newExpiry,
                            'expiry_date_na'    => 0,
                            'notif_h90_sent_at' => null,
                            'notif_h60_sent_at' => null,
                            'notif_h30_sent_at' => null,
                            'notif_h14_sent_at' => null,
                            'notif_h7_sent_at'  => null,
                            'notif_h3_sent_at'  => null,
                            'notif_h1_sent_at'  => null,
                        ]);
                    }

                    $agr->update($updateData);

                    AgreementHistory::create([
                        'agreement_id'   => $agrId,
                        'user_id'        => $uId,
                        'kode_singkatan' => $uCode,
                        'tanggal_input'  => $tglInput,
                        'catatan'        => $catatanBaru,
                        'aksi'           => 'ADD_NOTE',
                    ]);

                    set_flash('success', "New note saved and stamped with <strong>{$stampBaru}</strong>.");
                }

                return redirect(base_url('?page=agreements&dept=' . urlencode((string) $request->input('return_dept', 'ALL'))));
            }

            if ($action === 'delete') {
                $agrId = (int) $request->input('agreement_id', 0);
                $delQuery = Agreement::where('id', $agrId);
                $this->applyAgreementAccessScope($delQuery);
                $delQuery->delete();

                set_flash('success', 'Document deleted successfully.');
                return redirect(base_url('?page=agreements&dept=' . urlencode((string) $request->input('return_dept', 'ALL'))));
            }

            if ($action === 'bulk_delete') {
                $rawIds = (array) $request->input('agreement_ids', []);
                $ids = array_values(array_unique(array_filter(array_map('intval', $rawIds), fn ($v) => $v > 0)));

                if (!empty($ids)) {
                    $delQuery = Agreement::whereIn('id', $ids);
                    $this->applyAgreementAccessScope($delQuery);
                    $deletedCount = $delQuery->delete();

                    if ($deletedCount > 0) {
                        set_flash('success', "Successfully deleted <strong>{$deletedCount} selected document(s)</strong>.");
                    } else {
                        set_flash('error', 'No documents could be deleted.');
                    }
                } else {
                    set_flash('error', 'Please select at least 1 document to delete.');
                }

                $retDept = urlencode((string) $request->input('return_dept', 'ALL'));
                $retType = urlencode((string) $request->input('return_doc_type', 'ALL'));
                $retStat = urlencode((string) $request->input('return_status', 'ALL'));
                $retSort = urlencode((string) $request->input('return_sort', 'default'));

                return redirect(base_url("?page=agreements&dept={$retDept}&doc_type={$retType}&status={$retStat}&sort={$retSort}"));
            }

            if ($action === 'update_process_status') {
                $agrId = (int) $request->input('agreement_id', 0);
                $newStatus = strtoupper(trim((string) $request->input('status_proses', 'PENGAJUAN')));
                if (!in_array($newStatus, ['PENGAJUAN', 'BERJALAN', 'SELESAI'], true)) {
                    $newStatus = 'PENGAJUAN';
                }

                $tglSelesai = parse_date_medquest((string) $request->input('tanggal_selesai', ''));
                if ($newStatus === 'SELESAI' && empty($tglSelesai)) {
                    $tglSelesai = date('Y-m-d');
                } elseif ($newStatus !== 'SELESAI') {
                    $tglSelesai = null;
                }

                $catatanStatus = trim((string) $request->input('catatan_status', ''));

                $agrQuery = Agreement::where('id', $agrId);
                $this->applyAgreementAccessScope($agrQuery);
                $agr = $agrQuery->first();

                if ($agr) {
                    $statusMeta = get_process_status_meta($newStatus);
                    $oldStatusMeta = get_process_status_meta($agr->status_proses ?? 'PENGAJUAN');

                    $agr->update([
                        'status_proses'   => $newStatus,
                        'tanggal_selesai' => $tglSelesai,
                    ]);

                    $logMsg = "Status changed from [{$oldStatusMeta['label']}] to [{$statusMeta['label']}].";
                    if ($newStatus === 'SELESAI' && $tglSelesai) {
                        $logMsg .= " (Completion Date: " . format_date_medquest($tglSelesai) . ")";
                    }
                    if ($catatanStatus !== '') {
                        $logMsg .= "\nNote: " . $catatanStatus;
                    }

                    $this->logAgreementHistory(
                        $agrId,
                        $uId,
                        $uCode,
                        date('Y-m-d'),
                        $logMsg,
                        'STATUS_' . $newStatus
                    );

                    set_flash('success', "Status for document #<strong>" . ($agr->no_urut_dokumen ?: $agr->id) . "</strong> updated to: <strong class='text-uppercase'>{$statusMeta['label']}</strong>.");
                }

                $retDept   = urlencode((string) $request->input('return_dept', 'ALL'));
                $retType   = urlencode((string) $request->input('return_doc_type', 'ALL'));
                $retStat   = urlencode((string) $request->input('return_status', 'ALL'));
                $retProses = urlencode((string) $request->input('return_proses', 'ALL'));
                $retSort   = urlencode((string) $request->input('return_sort', 'default'));
                $retQ      = urlencode((string) $request->input('return_q', ''));
                $retP      = urlencode((string) $request->input('return_p', '1'));

                return redirect(base_url("?page=agreements&dept={$retDept}&doc_type={$retType}&status={$retStat}&proses={$retProses}&sort={$retSort}&q={$retQ}&p={$retP}"));
            }

            if ($action === 'add_department' && is_superadmin()) {
                $kodeDept = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $request->input('kode_dept', ''))));
                $namaDept = trim((string) $request->input('nama_dept', ''));
                if ($kodeDept !== '' && $namaDept !== '') {
                    Department::firstOrCreate(
                        ['kode_dept' => $kodeDept],
                        ['nama_dept' => $namaDept]
                    );
                    set_flash('success', "Department <strong>{$kodeDept}</strong> added successfully.");
                }
                return redirect(base_url('?page=agreements&dept=' . urlencode($kodeDept ?: 'ALL')));
            }
        }

        $filtered = $this->queryFilteredAgreements($request);
        extract($filtered);
        $currentPage = max(1, (int) $request->query('p', 1));
        $perPage     = 15;

        $statTotal    = count($allMatchingAgreements);
        $statSafe     = 0;
        $statDeadline = 0;
        $statUrgent   = 0;
        $statExpired  = 0;

        foreach ($allMatchingAgreements as $row) {
            if (!empty($row['expiry_date_na']) || empty($row['expiry_date'])) {
                $statSafe++;
                continue;
            }
            $d = get_days_until_expiry($row['expiry_date']);
            if ($d < 0) {
                $statExpired++;
            } else {
                $statSafe++;
                if ($d <= 90) {
                    $statDeadline++;
                }
                if ($d <= 14) {
                    $statUrgent++;
                }
            }
        }

        $pctSafe     = $statTotal > 0 ? round(($statSafe / $statTotal) * 100) : 0;
        $pctDeadline = $statTotal > 0 ? round(($statDeadline / $statTotal) * 100) : 0;
        $pctUrgent   = $statTotal > 0 ? round(($statUrgent / $statTotal) * 100) : 0;
        $pctExpired  = $statTotal > 0 ? round(($statExpired / $statTotal) * 100) : 0;

        $totalPages  = max(1, (int) ceil($statTotal / $perPage));
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset      = ($currentPage - 1) * $perPage;
        $agreements  = array_slice($allMatchingAgreements, $offset, $perPage);

        return view('pages.agreements', array_merge(
            $this->getLayoutData($request, 'agreements'),
            compact(
                'selectedDept',
                'selectedStatus',
                'selectedDocType',
                'selectedProses',
                'countProsesAll',
                'countPengajuan',
                'countBerjalan',
                'countSelesai',
                'searchQuery',
                'selectedSort',
                'currentPage',
                'perPage',
                'totalPages',
                'offset',
                'agreements',
                'statTotal',
                'statSafe',
                'statDeadline',
                'statUrgent',
                'statExpired',
                'pctSafe',
                'pctDeadline',
                'pctUrgent',
                'pctExpired',
                'uCode'
            )
        ));
    }

    public function createPage(Request $request)
    {
        $this->ensureDatabaseReady();
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        $user = current_user();
        $departments = Department::orderBy('id', 'asc')->get()->map->toArray()->all();

        $maxCoop = (int) (Agreement::where('doc_type', 'COOPERATION')->orWhereNull('doc_type')->max('no_urut_dokumen') ?? 730);
        $maxComp = (int) (Agreement::where('doc_type', 'COMPANY')->max('no_urut_dokumen') ?? 184);
        $maxPrin = (int) (Agreement::where('doc_type', 'PRINCIPAL')->max('no_urut_dokumen') ?? 0);

        $nextNums = [
            'COOPERATION' => $maxCoop + 1,
            'COMPANY'     => $maxComp + 1,
            'PRINCIPAL'   => $maxPrin + 1,
        ];

        $selectedDocType = strtoupper(trim((string) ($request->query('doc_type') ?? $request->input('doc_type', 'COOPERATION'))));
        if (!in_array($selectedDocType, ['COOPERATION', 'COMPANY', 'PRINCIPAL'], true)) {
            $selectedDocType = 'COOPERATION';
        }

        $defaultDept = ($selectedDocType === 'COOPERATION') ? 'MHO' : 'LAC';
        $preselectedDept = strtoupper(trim((string) ($request->query('dept') ?? ($user['departemen_default'] !== 'ALL' ? $user['departemen_default'] : $defaultDept))));

        if ($request->isMethod('post')) {
            $doc_type = strtoupper(trim((string) $request->input('doc_type', 'COOPERATION')));
            if (!in_array($doc_type, ['COOPERATION', 'COMPANY', 'PRINCIPAL'], true)) {
                $doc_type = 'COOPERATION';
            }

            $department  = strtoupper(trim((string) $request->input('department', 'LAC')));
            $custom_dept = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $request->input('custom_dept', ''))));
            if ($department === 'OTHER' && $custom_dept !== '' && is_superadmin()) {
                $department = $custom_dept;
                Department::firstOrCreate(
                    ['kode_dept' => $department],
                    ['nama_dept' => $department . ' Division']
                );
            }

            $no_urut_dokumen     = $request->filled('no_urut_dokumen') ? (int) $request->input('no_urut_dokumen') : ($nextNums[$doc_type] ?? 1);
            $document_number     = trim((string) $request->input('document_number', '')) ?: 'N/A';
            $document_title      = trim((string) $request->input('document_title', ''));
            $issued_by           = trim((string) $request->input('issued_by', ''));
            $principal_name      = trim((string) $request->input('principal_name', ''));
            $customer            = trim((string) $request->input('customer', ''));
            $product_description = trim((string) $request->input('product_description', ''));
            $serial_number       = trim((string) $request->input('serial_number', '')) ?: 'N/A';

            if ($doc_type === 'COMPANY') {
                $customer            = $issued_by !== '' ? $issued_by : 'Original Company Document';
                $product_description = $document_title;
            } elseif ($doc_type === 'PRINCIPAL') {
                $customer            = $principal_name !== '' ? $principal_name : $issued_by;
                $product_description = $document_title;
            }

            $effective_date_na = $request->has('effective_date_na') ? 1 : 0;
            $effective_date    = $effective_date_na ? null : parse_date_medquest((string) $request->input('effective_date', ''));
            $invalidEffDate    = (!$effective_date_na && $request->filled('effective_date') && $effective_date === null);
            if ($effective_date === null && !$invalidEffDate) {
                $effective_date_na = 1;
            }

            $expiry_date_na = $request->has('expiry_date_na') ? 1 : 0;
            $expiry_date    = $expiry_date_na ? null : parse_date_medquest((string) $request->input('expiry_date', ''));
            $invalidExpDate = (!$expiry_date_na && $request->filled('expiry_date') && $expiry_date === null);
            if ($expiry_date === null && !$invalidExpDate) {
                $expiry_date_na = 1;
            }

            $tanggal_input    = parse_date_medquest((string) $request->input('tanggal_input', '')) ?? date('Y-m-d');
            $raw_note         = trim((string) $request->input('note', ''));
            $prefix_date_note = $request->has('prefix_date_note');

            $errorMsg = '';
            if ($invalidEffDate || $invalidExpDate) {
                $errorMsg = 'Invalid date format. Please use dd/mm/yyyy.';
            } elseif ($doc_type === 'COOPERATION') {
                if ($document_number === '' || $customer === '' || $product_description === '' || (!$expiry_date_na && !$expiry_date)) {
                    $errorMsg = 'For Log of Cooperation Agreement, please complete Document Number, Customer, Product Description, and Expiry Date.';
                }
            } elseif ($doc_type === 'COMPANY') {
                if ($document_title === '' || $issued_by === '') {
                    $errorMsg = 'For Original Company Document, please complete Document Title and Issued By.';
                }
            } elseif ($doc_type === 'PRINCIPAL') {
                if ($document_title === '' || $issued_by === '' || $principal_name === '') {
                    $errorMsg = 'For Log of Principal Document, please complete Document Title, Issued By, and Principal.';
                }
            }

            if ($errorMsg !== '') {
                set_flash('error', $errorMsg);
            } else {
                if ($raw_note === '' || strtoupper($raw_note) === 'N/A') {
                    $finalNote = 'N/A';
                } elseif ($prefix_date_note) {
                    $finalNote = format_date_medquest($tanggal_input, false) . " :\n" . $raw_note;
                } else {
                    $finalNote = $raw_note;
                }

                $createdByStamp = format_created_by_stamp($user['kode_singkatan'], $tanggal_input);

                $status_proses   = strtoupper(trim((string) $request->input('status_proses', 'PENGAJUAN')));
                if (!in_array($status_proses, ['PENGAJUAN', 'BERJALAN', 'SELESAI'], true)) {
                    $status_proses = 'PENGAJUAN';
                }
                $tanggal_selesai = ($status_proses === 'SELESAI' && $request->filled('tanggal_selesai'))
                    ? (parse_date_medquest((string) $request->input('tanggal_selesai')) ?? date('Y-m-d'))
                    : ($status_proses === 'SELESAI' ? date('Y-m-d') : null);

                $newAgr = Agreement::create([
                    'doc_type'            => $doc_type,
                    'status_proses'       => $status_proses,
                    'tanggal_selesai'     => $tanggal_selesai,
                    'no_urut_dokumen'     => $no_urut_dokumen,
                    'department'          => $department,
                    'document_title'      => $document_title ?: null,
                    'document_number'     => $document_number,
                    'issued_by'           => $issued_by ?: null,
                    'principal_name'      => $principal_name ?: null,
                    'customer'            => $customer,
                    'product_description' => $product_description,
                    'serial_number'       => $serial_number,
                    'effective_date'      => $effective_date,
                    'effective_date_na'   => $effective_date_na,
                    'expiry_date'         => $expiry_date,
                    'expiry_date_na'      => $expiry_date_na,
                    'note'                => $finalNote,
                    'created_by_user_id'  => $user['id'],
                    'created_by_code'     => $user['kode_singkatan'],
                    'created_by_log'      => $createdByStamp,
                ]);

                if ($request->hasFile('file_dokumen') && $request->file('file_dokumen')->isValid()) {
                    $file = $request->file('file_dokumen');
                    $ext = strtolower($file->getClientOriginalExtension());
                    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
                    if (in_array($ext, $allowedExt, true)) {
                        $uploadDir = public_path('assets/uploads');
                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0775, true);
                        }
                        $fileName = 'doc_' . $newAgr->id . '_' . time() . '.' . $ext;
                        $file->move($uploadDir, $fileName);
                        $newAgr->update(['file_attachment' => $fileName]);
                    }
                }

                AgreementHistory::create([
                    'agreement_id'   => $newAgr->id,
                    'user_id'        => $user['id'],
                    'kode_singkatan' => $user['kode_singkatan'],
                    'tanggal_input'  => $tanggal_input,
                    'catatan'        => $raw_note ?: 'N/A',
                    'aksi'           => 'CREATE',
                ]);

                $extraMsg = '';
                if (!$expiry_date_na && $expiry_date) {
                    $autoEmails = NotificationService::runAutoExpiryNotifications();
                    if (!empty($autoEmails)) {
                        $extraMsg = ' Because the date falls within a reminder milestone (H-90/60/30/14/7/3), an automatic notification email was queued for <strong>' . e($user['email']) . '</strong>!';
                    }
                }

                $meta = get_doc_type_meta($doc_type);
                set_flash('success', "Document <strong>{$meta['title']}</strong> was saved to department <strong>{$department}</strong> stamped Created By: <strong>{$createdByStamp}</strong>.{$extraMsg}");
                return redirect(base_url('?page=agreements&dept=' . urlencode($department) . '&doc_type=' . urlencode($doc_type)));
            }
        }

        return view('pages.create', array_merge(
            $this->getLayoutData($request, 'create'),
            compact('departments', 'nextNums', 'selectedDocType', 'preselectedDept')
        ));
    }

    public function editPage(Request $request, ?int $routeId = null)
    {
        $this->ensureDatabaseReady();
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        $user  = current_user();
        $id    = $routeId ?? (int) $request->query('id', 0);
        $uId   = (int) $user['id'];
        $uCode = strtoupper($user['kode_singkatan']);

        $agrQuery = Agreement::where('id', $id);
        $this->applyAgreementAccessScope($agrQuery);
        $agrModel = $agrQuery->first();

        if (!$agrModel) {
            set_flash('error', 'Document record not found.');
            return redirect(base_url('?page=agreements'));
        }

        $agr = $agrModel->toArray();
        $docType = strtoupper(trim((string) ($agr['doc_type'] ?? 'COOPERATION')));
        $typeMeta = get_doc_type_meta($docType);
        $departments = Department::orderBy('id', 'asc')->get()->map->toArray()->all();

        if ($request->isMethod('post')) {
            $department          = strtoupper(trim((string) $request->input('department', $agr['department'])));
            $no_urut_dokumen     = $request->filled('no_urut_dokumen') ? (int) $request->input('no_urut_dokumen') : $agr['no_urut_dokumen'];
            $document_number     = trim((string) $request->input('document_number', '')) ?: 'N/A';
            $document_title      = trim((string) $request->input('document_title', $agr['document_title'] ?? ''));
            $issued_by           = trim((string) $request->input('issued_by', $agr['issued_by'] ?? ''));
            $principal_name      = trim((string) $request->input('principal_name', $agr['principal_name'] ?? ''));
            $customer            = trim((string) $request->input('customer', $agr['customer'] ?? ''));
            $product_description = trim((string) $request->input('product_description', $agr['product_description'] ?? ''));
            $serial_number       = trim((string) $request->input('serial_number', '')) ?: 'N/A';

            if ($docType === 'COMPANY') {
                $customer            = $issued_by !== '' ? $issued_by : 'Original Company Document';
                $product_description = $document_title;
            } elseif ($docType === 'PRINCIPAL') {
                $customer            = $principal_name !== '' ? $principal_name : $issued_by;
                $product_description = $document_title;
            }

            $effective_date_na = $request->has('effective_date_na') ? 1 : 0;
            $effective_date    = $effective_date_na ? null : parse_date_medquest((string) $request->input('effective_date', ''));
            if (!$effective_date_na && $request->filled('effective_date') && $effective_date === null) {
                set_flash('error', 'Invalid Effective Date format. Please use dd/mm/yyyy.');
                return redirect(base_url('?page=edit&id=' . $id));
            }
            if ($effective_date === null) {
                $effective_date_na = 1;
            }

            $expiry_date_na = $request->has('expiry_date_na') ? 1 : 0;
            $expiry_date    = $expiry_date_na ? null : parse_date_medquest((string) $request->input('expiry_date', ''));
            if (!$expiry_date_na && $request->filled('expiry_date') && $expiry_date === null) {
                set_flash('error', 'Invalid Expiry Date format. Please use dd/mm/yyyy.');
                return redirect(base_url('?page=edit&id=' . $id));
            }
            if ($expiry_date === null) {
                $expiry_date_na = 1;
            }

            $note           = trim((string) $request->input('note', '')) ?: 'N/A';
            $created_by_log = trim((string) $request->input('created_by_log', $agr['created_by_log']));

            if ($request->has('append_my_stamp')) {
                $myStamp = format_created_by_stamp($user['kode_singkatan'], date('Y-m-d'));
                $lines = array_map('trim', explode("\n", $created_by_log));
                if (!in_array($myStamp, $lines, true)) {
                    $created_by_log = trim($created_by_log . "\n" . $myStamp);
                }
            }

            $status_proses   = strtoupper(trim((string) $request->input('status_proses', $agr['status_proses'] ?? 'PENGAJUAN')));
            if (!in_array($status_proses, ['PENGAJUAN', 'BERJALAN', 'SELESAI'], true)) {
                $status_proses = 'PENGAJUAN';
            }
            $tanggal_selesai = ($status_proses === 'SELESAI' && $request->filled('tanggal_selesai'))
                ? (parse_date_medquest((string) $request->input('tanggal_selesai')) ?? ($agr['tanggal_selesai'] ?? date('Y-m-d')))
                : ($status_proses === 'SELESAI' ? ($agr['tanggal_selesai'] ?? date('Y-m-d')) : null);

            $updatePayload = [
                'status_proses'       => $status_proses,
                'tanggal_selesai'     => $tanggal_selesai,
                'no_urut_dokumen'     => $no_urut_dokumen,
                'department'          => $department,
                'document_title'      => $document_title ?: null,
                'document_number'     => $document_number,
                'issued_by'           => $issued_by ?: null,
                'principal_name'      => $principal_name ?: null,
                'customer'            => $customer,
                'product_description' => $product_description,
                'serial_number'       => $serial_number,
                'effective_date'      => $effective_date,
                'effective_date_na'   => $effective_date_na,
                'expiry_date'         => $expiry_date,
                'expiry_date_na'      => $expiry_date_na,
                'note'                => $note,
                'created_by_log'      => $created_by_log,
            ];

            if ($expiry_date !== $agr['expiry_date']) {
                $updatePayload = array_merge($updatePayload, [
                    'notif_h90_sent_at' => null,
                    'notif_h60_sent_at' => null,
                    'notif_h30_sent_at' => null,
                    'notif_h14_sent_at' => null,
                    'notif_h7_sent_at'  => null,
                    'notif_h3_sent_at'  => null,
                    'notif_h1_sent_at'  => null,
                ]);
            }

            $agrModel->update($updatePayload);

            $oldStatusProses = strtoupper(trim((string) ($agr['status_proses'] ?? 'PENGAJUAN')));
            if ($status_proses !== $oldStatusProses) {
                $statusMeta = get_process_status_meta($status_proses);
                $oldStatusMeta = get_process_status_meta($oldStatusProses);
                $logMsg = "Status changed from [{$oldStatusMeta['label']}] to [{$statusMeta['label']}].";
                if ($status_proses === 'SELESAI' && $tanggal_selesai) {
                    $logMsg .= " (Completion Date: " . format_date_medquest($tanggal_selesai) . ")";
                }
                $this->logAgreementHistory($id, $uId, $uCode, date('Y-m-d'), $logMsg, 'STATUS_' . $status_proses);
            }

            try {
                if (!$expiry_date_na && $expiry_date) {
                    NotificationService::runAutoExpiryNotifications();
                }
            } catch (\Throwable $e) {
            }

            set_flash('success', 'Document details updated successfully.');
            return redirect(base_url('?page=agreements&dept=' . urlencode($department) . '&doc_type=' . urlencode($docType)));
        }

        $isExpNa = !empty($agr['expiry_date_na']) || empty($agr['expiry_date']);

        return view('pages.edit', array_merge(
            $this->getLayoutData($request, 'edit'),
            compact('id', 'agr', 'docType', 'typeMeta', 'departments', 'isExpNa')
        ));
    }

    public function detailPage(Request $request, ?int $routeId = null)
    {
        $this->ensureDatabaseReady();
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        $user  = current_user();
        $id    = $routeId ?? (int) $request->query('id', 0);
        $uId   = (int) $user['id'];
        $uCode = strtoupper($user['kode_singkatan']);

        $agrQuery = DB::table('agreements as a')
            ->leftJoin('users as u', 'a.created_by_user_id', '=', 'u.id')
            ->select('a.*', 'u.nama_lengkap as creator_name', 'u.email as creator_email')
            ->where('a.id', $id);
        $this->applyAgreementAccessScope($agrQuery, 'a');
        $agrRow = $agrQuery->first();

        if (!$agrRow) {
            set_flash('error', 'Document record not found.');
            return redirect(base_url('?page=agreements'));
        }

        $agr = (array) $agrRow;

        if ($request->isMethod('post')) {
            $action = (string) $request->input('action', '');

            if ($action === 'upload_file') {
                if ($request->hasFile('file_dokumen') && $request->file('file_dokumen')->isValid()) {
                    $file = $request->file('file_dokumen');
                    $ext = strtolower($file->getClientOriginalExtension());
                    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
                    if (in_array($ext, $allowedExt, true)) {
                        $uploadDir = public_path('assets/uploads');
                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0775, true);
                        }
                        $fileName = 'doc_' . $id . '_' . time() . '.' . $ext;
                        $file->move($uploadDir, $fileName);
                        Agreement::where('id', $id)->update(['file_attachment' => $fileName]);
                        set_flash('success', 'Document file attached successfully.');
                        return redirect(base_url('?page=detail&id=' . $id));
                    } else {
                        set_flash('error', 'File format must be PDF, JPG, PNG, Word, or Excel.');
                    }
                }
            }

            if ($action === 'update_process_status') {
                $newStatus = strtoupper(trim((string) $request->input('status_proses', 'PENGAJUAN')));
                if (!in_array($newStatus, ['PENGAJUAN', 'BERJALAN', 'SELESAI'], true)) {
                    $newStatus = 'PENGAJUAN';
                }

                $tglSelesai = parse_date_medquest((string) $request->input('tanggal_selesai', ''));
                if ($newStatus === 'SELESAI' && empty($tglSelesai)) {
                    $tglSelesai = date('Y-m-d');
                } elseif ($newStatus !== 'SELESAI') {
                    $tglSelesai = null;
                }

                $catatanStatus = trim((string) $request->input('catatan_status', ''));

                $agrModel = Agreement::where('id', $id)->first();
                if ($agrModel) {
                    $statusMeta = get_process_status_meta($newStatus);
                    $oldStatusMeta = get_process_status_meta($agrModel->status_proses ?? 'PENGAJUAN');

                    $agrModel->update([
                        'status_proses'   => $newStatus,
                        'tanggal_selesai' => $tglSelesai,
                    ]);

                    $logMsg = "Status changed from [{$oldStatusMeta['label']}] to [{$statusMeta['label']}].";
                    if ($newStatus === 'SELESAI' && $tglSelesai) {
                        $logMsg .= " (Completion Date: " . format_date_medquest($tglSelesai) . ")";
                    }
                    if ($catatanStatus !== '') {
                        $logMsg .= "\nNote: " . $catatanStatus;
                    }

                    $this->logAgreementHistory(
                        $id,
                        $uId,
                        $uCode,
                        date('Y-m-d'),
                        $logMsg,
                        'STATUS_' . $newStatus
                    );

                    set_flash('success', "Document status updated to: <strong class='text-uppercase'>{$statusMeta['label']}</strong>.");
                }

                return redirect(base_url('?page=detail&id=' . $id));
            }
        }

        $docType      = strtoupper(trim((string) ($agr['doc_type'] ?? 'COOPERATION')));
        $typeMeta     = get_doc_type_meta($docType);
        $isExpNa      = !empty($agr['expiry_date_na']) || empty($agr['expiry_date']);
        $expInfo      = get_expiry_info($agr['expiry_date'], $isExpNa);
        $cleanLabel   = preg_replace('/\s*\(Fase\s+H-\d+\)/i', '', $expInfo['short_label']);
        $effFormatted = format_date_medquest($agr['effective_date'], $agr['effective_date_na']);
        $expFormatted = format_date_medquest($agr['expiry_date'], $isExpNa);
        $statusMeta   = get_process_status_meta($agr['status_proses'] ?? 'PENGAJUAN');

        $histories = AgreementHistory::where('agreement_id', $id)->orderBy('id', 'asc')->get()->map->toArray()->all();
        $backUrl = base_url('?page=agreements&dept=' . urlencode((string) $agr['department']) . '&doc_type=' . urlencode($docType));

        return view('pages.detail', array_merge(
            $this->getLayoutData($request, 'detail'),
            compact('id', 'agr', 'docType', 'typeMeta', 'isExpNa', 'expInfo', 'cleanLabel', 'effFormatted', 'expFormatted', 'statusMeta', 'histories', 'backUrl', 'uCode')
        ));
    }

    public function notificationsPage(Request $request)
    {
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        $user  = current_user();
        $uId   = (int) $user['id'];
        $uCode = strtoupper($user['kode_singkatan']);

        try {
            NotificationService::runAutoExpiryNotifications();
        } catch (\Throwable $e) {
        }

        if ($request->isMethod('post') && $request->input('action') === 'clear_logs') {
            if (is_superadmin()) {
                EmailNotification::query()->delete();
            } else {
                EmailNotification::where('user_id', $uId)
                    ->orWhere('recipient_code', $uCode)
                    ->delete();
            }
            set_flash('success', 'Email log history cleared successfully.');
            return redirect(base_url('?page=notifications'));
        }

        $upcomingH = $this->getUpcomingDeadlineItems();

        $emailLogsQuery = DB::table('email_notifications as e')
            ->leftJoin('agreements as a', 'e.agreement_id', '=', 'a.id')
            ->select('e.*', 'a.department', 'a.customer', 'a.document_number', 'a.expiry_date');

        if (!is_superadmin()) {
            $allowedDepts = get_user_allowed_departments($user);
            if (!in_array('ALL', $allowedDepts, true)) {
                $emailLogsQuery->where(function ($q) use ($uId, $uCode, $allowedDepts) {
                    $q->where('e.user_id', $uId)
                      ->orWhere('e.recipient_code', $uCode);
                    if (!empty($allowedDepts)) {
                        $q->orWhereIn('a.department', $allowedDepts);
                    }
                });
            }
        }

        $emailLogs = $emailLogsQuery
            ->orderByDesc('e.id')
            ->limit(100)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        return view('pages.notifications', array_merge(
            $this->getLayoutData($request, 'notifications'),
            compact('upcomingH', 'emailLogs')
        ));
    }

    public function smtpSettingsPage(Request $request)
    {
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        $user = current_user();
        $smtpModel = SmtpSetting::find(1);
        if (!$smtpModel) {
            $smtpModel = SmtpSetting::create([
                'id'              => 1,
                'smtp_enabled'    => false,
                'smtp_host'       => 'smtp.gmail.com',
                'smtp_port'       => 587,
                'smtp_encryption' => 'tls',
                'smtp_username'   => '',
                'smtp_password'   => '',
                'sender_name'     => 'Medquest Agreement Alert (LAC)',
                'sender_email'    => 'noreply@medquest.co.id',
            ]);
        }

        if ($request->isMethod('post')) {
            $action = (string) $request->input('action', 'save');

            if ($action === 'save') {
                $smtp_host       = trim((string) $request->input('smtp_host', 'smtp.gmail.com'));
                $smtp_port       = (int) $request->input('smtp_port', 587);
                $smtp_encryption = trim((string) $request->input('smtp_encryption', 'tls'));
                $smtp_username   = trim((string) $request->input('smtp_username', ''));
                $smtp_password   = str_replace(' ', '', trim((string) $request->input('smtp_password', '')));
                $sender_name     = trim((string) $request->input('sender_name', 'Medquest Agreement Alert (LAC)'));
                $sender_email    = trim((string) $request->input('sender_email', '')) ?: $smtp_username;

                if ($smtp_password === '' && !empty($smtpModel->smtp_password)) {
                    $smtp_password = str_replace(' ', '', $smtpModel->smtp_password);
                }

                $smtp_enabled = ($smtp_username !== '' && $smtp_password !== '') ? 1 : ($request->has('smtp_enabled') ? 1 : 0);

                $smtpModel->update([
                    'smtp_enabled'    => $smtp_enabled,
                    'smtp_host'       => $smtp_host,
                    'smtp_port'       => $smtp_port,
                    'smtp_encryption' => $smtp_encryption,
                    'smtp_username'   => $smtp_username,
                    'smtp_password'   => $smtp_password,
                    'sender_name'     => $sender_name,
                    'sender_email'    => $sender_email,
                    'updated_at'      => now(),
                ]);

                $autoResults = NotificationService::runAutoExpiryNotifications();
                $sentReal = count(array_filter($autoResults, fn ($r) => !empty($r['sent_smtp'])));

                if ($sentReal > 0) {
                    set_flash('success', "Gmail SMTP settings saved & system <strong>immediately sent {$sentReal} automated email(s) (H-7/H-3/H-1)</strong> to the creators' Gmail inboxes!");
                } elseif ($smtp_enabled) {
                    set_flash('success', 'Gmail SMTP configuration enabled! Documents reaching H-7, H-3, and H-1 will now be delivered automatically to user Gmail inboxes.');
                } else {
                    set_flash('info', 'SMTP settings saved.');
                }

                return redirect(base_url('?page=smtp_settings'));
            }

            if ($action === 'test_email') {
                $targetEmail = trim((string) $request->input('test_target_email', $user['email']));
                $sampleAgr = Agreement::orderByDesc('id')->first();
                if ($sampleAgr) {
                    $err = null;
                    $subject = '[TEST GMAIL MEDQUEST] Automated Notification Test H-7, H-3, H-1';
                    $htmlBody = NotificationService::buildExpiryEmailHtml($sampleAgr->toArray(), [
                        'nama_lengkap'   => $user['nama_lengkap'],
                        'kode_singkatan' => $user['kode_singkatan'],
                        'email'          => $targetEmail,
                    ], 'H-3', 3);

                    if ($smtpModel->smtp_enabled && !empty($smtpModel->smtp_username) && !empty($smtpModel->smtp_password)) {
                        if (NotificationService::sendViaDynamicSmtp($smtpModel, $targetEmail, $subject, $htmlBody, $err)) {
                            set_flash('success', "SUCCESS! Test email sent directly to Gmail inbox <strong>{$targetEmail}</strong>. Please check your Inbox or Spam folder!");
                        } else {
                            set_flash('error', "Failed to send to Gmail: <strong>{$err}</strong>");
                        }
                    } else {
                        set_flash('error', 'Please enter the Sender Gmail Address and 16-Digit Google App Password in the form above first, then click Save.');
                    }
                }
                return redirect(base_url('?page=smtp_settings'));
            }
        }

        $smtp = $smtpModel->toArray();

        return view('pages.smtp_settings', array_merge(
            $this->getLayoutData($request, 'smtp_settings'),
            compact('smtp')
        ));
    }

    public function userApprovalsPage(Request $request)
    {
        $this->ensureDatabaseReady();

        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        if (!is_superadmin()) {
            set_flash('error', 'Access denied. Account Approvals can only be accessed by a Superadmin.');
            return redirect(base_url('?page=agreements'));
        }

        $currentUser = current_user();

        if ($request->isMethod('post')) {
            $action   = (string) $request->input('action', '');
            $targetId = (int) $request->input('user_id', 0);

            if ($targetId > 0) {
                $targetUser = User::find($targetId);
                if ($targetUser) {
                    $targetName = e($targetUser->nama_lengkap);
                    $targetCode = e($targetUser->kode_singkatan);

                    if ($action === 'approve') {
                        $targetUser->update(['status' => 'approved']);
                        set_flash('success', "<i class='fas fa-check-circle me-1'></i> Account <strong>{$targetName} ({$targetCode})</strong> has been <strong>APPROVED</strong>! The user can now sign in.");
                    } elseif ($action === 'reject') {
                        $targetUser->update(['status' => 'rejected']);
                        set_flash('error', "<i class='fas fa-times-circle me-1'></i> Account registration for <strong>{$targetName} ({$targetCode})</strong> has been <strong>REJECTED</strong>.");
                    } elseif ($action === 'delete') {
                        if ($targetId === (int) $currentUser['id']) {
                            set_flash('error', 'You cannot delete your own currently active account.');
                        } else {
                            $targetUser->delete();
                            set_flash('success', "<i class='fas fa-trash-alt me-1'></i> Account <strong>{$targetName} ({$targetCode})</strong> deleted successfully.");
                        }
                    } elseif ($action === 'set_role') {
                        $rawRole = strtolower(trim((string) $request->input('new_role', 'user')));
                        $newRole = in_array($rawRole, ['superadmin', 'admin', 'user'], true) ? $rawRole : 'user';
                        $targetUser->update(['role' => $newRole]);

                        if (in_array($newRole, ['superadmin', 'admin'], true)) {
                            set_flash('success', "Account role for <strong>{$targetName} ({$targetCode})</strong> updated to <strong>ADMIN</strong>. This account can now view documents across all departments.");
                        } else {
                            if (empty($targetUser->allowed_departments) && strtoupper((string) $targetUser->departemen_default) !== 'ALL') {
                                $targetUser->update(['allowed_departments' => strtoupper((string) $targetUser->departemen_default)]);
                            }
                            set_flash('success', "Account role for <strong>{$targetName} ({$targetCode})</strong> updated to <strong>USER</strong>. Please check the allowed departments in the Department Access column.");
                        }
                    } elseif ($action === 'update_departments') {
                        $rawDepts = (array) $request->input('departments', []);
                        $cleanDepts = array_values(array_unique(array_filter(array_map(function ($d) {
                            return strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $d)));
                        }, $rawDepts))));

                        if (!empty($cleanDepts)) {
                            $allowedStr = implode(',', $cleanDepts);
                            $targetUser->update([
                                'allowed_departments' => $allowedStr,
                                'departemen_default'  => $cleanDepts[0],
                            ]);
                            set_flash('success', "Department access for User <strong>{$targetName} ({$targetCode})</strong> updated: <strong>" . implode(', ', $cleanDepts) . "</strong>.");
                        } else {
                            $targetUser->update(['allowed_departments' => 'NONE']);
                            set_flash('info', "All department checkboxes for <strong>{$targetName} ({$targetCode})</strong> were cleared. This user can only view documents they created themselves.");
                        }
                    }
                }
            }

            $tabParam = $request->filled('tab') ? '&tab=' . urlencode((string) $request->query('tab')) : '';
            return redirect(base_url('?page=user_approvals' . $tabParam));
        }

        $countPending  = User::where('status', 'pending')->count();
        $countApproved = User::where('status', 'approved')->count();
        $countRejected = User::where('status', 'rejected')->count();
        $countTotal    = User::count();

        $tab    = (string) $request->query('tab', $countPending > 0 ? 'pending' : 'all');
        $search = trim((string) $request->query('q', ''));

        $qUsers = User::query();
        if (in_array($tab, ['pending', 'approved', 'rejected'], true)) {
            $qUsers->where('status', $tab);
        }
        if ($search !== '') {
            $like = "%{$search}%";
            $qUsers->where(function ($q) use ($like) {
                $q->where('nama_lengkap', 'like', $like)
                  ->orWhere('kode_singkatan', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('departemen_default', 'like', $like);
            });
        }

        $usersList = $qUsers
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC")
            ->orderByDesc('created_at')
            ->get()
            ->map->toArray()
            ->all();

        $allDepartments = Department::orderBy('id', 'asc')->get()->map->toArray()->all();

        return view('pages.user_approvals', array_merge(
            $this->getLayoutData($request, 'user_approvals'),
            compact('countPending', 'countApproved', 'countRejected', 'countTotal', 'tab', 'search', 'usersList', 'allDepartments')
        ));
    }

    public function cronNotify()
    {
        $results = NotificationService::runAutoExpiryNotifications();

        return response()->json([
            'timestamp'  => date('Y-m-d H:i:s'),
            'sent_count' => count($results),
            'details'    => $results,
        ]);
    }

    protected function queryFilteredAgreements(Request $request): array
    {
        $selectedDept    = strtoupper(trim((string) $request->query('dept', 'ALL')));
        $selectedStatus  = trim((string) $request->query('status', 'ALL'));
        $selectedDocType = strtoupper(trim((string) $request->query('doc_type', 'ALL')));
        if (!in_array($selectedDocType, ['ALL', 'COOPERATION', 'COMPANY', 'PRINCIPAL'], true)) {
            $selectedDocType = 'ALL';
        }
        $selectedProses  = strtoupper(trim((string) $request->query('proses', 'ALL')));
        if (!in_array($selectedProses, ['ALL', 'PENGAJUAN', 'BERJALAN', 'SELESAI'], true)) {
            $selectedProses = 'ALL';
        }
        $searchQuery  = trim((string) $request->query('q', ''));
        $selectedSort = trim((string) $request->query('sort', 'default'));

        $query = DB::table('agreements as a')
            ->leftJoin('users as u', 'a.created_by_user_id', '=', 'u.id')
            ->select('a.*', 'u.nama_lengkap as creator_name', 'u.email as creator_email');
        $this->applyAgreementAccessScope($query, 'a');

        if ($selectedDept !== 'ALL' && $selectedDept !== '') {
            $query->where('a.department', $selectedDept);
        }

        if (in_array($selectedDocType, ['COOPERATION', 'COMPANY', 'PRINCIPAL'], true)) {
            if ($selectedDocType === 'COOPERATION') {
                $query->where(function ($q) {
                    $q->where('a.doc_type', 'COOPERATION')
                      ->orWhereNull('a.doc_type');
                });
            } else {
                $query->where('a.doc_type', $selectedDocType);
            }
        }

        if ($searchQuery !== '') {
            $like = '%' . $searchQuery . '%';
            $query->where(function ($q) use ($like) {
                $q->where('a.document_number', 'like', $like)
                  ->orWhere('a.customer', 'like', $like)
                  ->orWhere('a.product_description', 'like', $like)
                  ->orWhere('a.document_title', 'like', $like)
                  ->orWhere('a.issued_by', 'like', $like)
                  ->orWhere('a.principal_name', 'like', $like)
                  ->orWhere('a.serial_number', 'like', $like)
                  ->orWhere('a.created_by_log', 'like', $like)
                  ->orWhere('a.note', 'like', $like);
            });
        }

        match ($selectedSort) {
            'newest'        => $query->orderByDesc('a.id'),
            'oldest'        => $query->orderBy('a.id', 'asc'),
            'deadline_asc'  => $query->orderByRaw('CASE WHEN a.expiry_date IS NULL OR a.expiry_date_na = 1 THEN 1 ELSE 0 END ASC, a.expiry_date ASC, a.id DESC'),
            'deadline_desc' => $query->orderByRaw('CASE WHEN a.expiry_date IS NULL OR a.expiry_date_na = 1 THEN 1 ELSE 0 END ASC, a.expiry_date DESC, a.id DESC'),
            default         => $query->orderByRaw('COALESCE(a.no_urut_dokumen, a.id) ASC, a.id ASC'),
        };

        $fetchedRows = $query->get()->map(fn ($r) => (array) $r)->all();

        $countProsesAll = count($fetchedRows);
        $countPengajuan = 0;
        $countBerjalan  = 0;
        $countSelesai   = 0;

        foreach ($fetchedRows as $row) {
            $stP = strtoupper(trim((string) ($row['status_proses'] ?? 'PENGAJUAN')));
            if ($stP === 'BERJALAN') {
                $countBerjalan++;
            } elseif ($stP === 'SELESAI') {
                $countSelesai++;
            } else {
                $countPengajuan++;
            }
        }

        $allMatchingAgreements = array_values(array_filter($fetchedRows, function (array $row) use ($selectedStatus, $selectedProses) {
            if ($selectedProses !== 'ALL') {
                $stP = strtoupper(trim((string) ($row['status_proses'] ?? 'PENGAJUAN')));
                if ($selectedProses === 'PENGAJUAN' && ($stP !== 'PENGAJUAN' && !empty($row['status_proses']))) {
                    return false;
                }
                if ($selectedProses === 'BERJALAN' && $stP !== 'BERJALAN') {
                    return false;
                }
                if ($selectedProses === 'SELESAI' && $stP !== 'SELESAI') {
                    return false;
                }
            }

            if ($selectedStatus === 'ALL' || $selectedStatus === '') {
                return true;
            }
            $isNa = !empty($row['expiry_date_na']) || empty($row['expiry_date']);
            $days = $isNa ? 9999 : get_days_until_expiry($row['expiry_date']);

            return match ($selectedStatus) {
                'EXPIRED'      => !$isNa && $days < 0,
                'H90_60'       => !$isNa && $days >= 31 && $days <= 90,
                'H30_14'       => !$isNa && $days >= 8 && $days <= 30,
                'H7_3'         => !$isNa && $days >= 0 && $days <= 7,
                'DEADLINE_ALL' => !$isNa && $days >= 0 && $days <= 90,
                'ACTIVE'       => $isNa || $days > 90,
                default        => true,
            };
        }));

        return compact(
            'selectedDept',
            'selectedStatus',
            'selectedDocType',
            'selectedProses',
            'searchQuery',
            'selectedSort',
            'countProsesAll',
            'countPengajuan',
            'countBerjalan',
            'countSelesai',
            'allMatchingAgreements'
        );
    }

    protected function getUpcomingDeadlineItems(string $selectedDept = 'ALL'): array
    {
        $agrQuery = DB::table('agreements as a')
            ->leftJoin('users as u', 'a.created_by_user_id', '=', 'u.id')
            ->select('a.*', 'u.nama_lengkap', 'u.email as user_email');
        $this->applyAgreementAccessScope($agrQuery, 'a');
        if ($selectedDept !== 'ALL' && $selectedDept !== '') {
            $agrQuery->where('a.department', $selectedDept);
        }
        $allUserAgreements = $agrQuery->get()->map(fn ($r) => (array) $r)->all();

        $upcomingH = [];
        foreach ($allUserAgreements as $item) {
            if (!empty($item['expiry_date_na']) || empty($item['expiry_date'])) {
                continue;
            }
            $sisa = get_days_until_expiry($item['expiry_date']);
            if ($sisa >= 0 && $sisa <= 90) {
                $item['sisa_hari'] = $sisa;
                $upcomingH[] = $item;
            }
        }
        usort($upcomingH, fn ($a, $b) => $a['sisa_hari'] <=> $b['sisa_hari']);

        return $upcomingH;
    }

    protected function buildExportDataset(string $datasetMode, array $agreements, array $deadlines, array $filters): array
    {
        $selectedDept = $filters['selectedDept'] ?? 'ALL';
        $selectedDocType = $filters['selectedDocType'] ?? 'ALL';
        $selectedProses = $filters['selectedProses'] ?? 'ALL';
        $selectedStatus = $filters['selectedStatus'] ?? 'ALL';
        $searchQuery = $filters['searchQuery'] ?? '';
        $dateStamp = date('Y-m-d');

        if ($datasetMode === 'deadlines') {
            $columns = [
                ['label' => 'Dept', 'width' => 12],
                ['label' => 'Customer & Product', 'width' => 38],
                ['label' => 'Document No', 'width' => 24],
                ['label' => 'Expiry Date', 'width' => 18],
                ['label' => 'Status', 'width' => 20],
                ['label' => 'Created By', 'width' => 28],
            ];
            $rows = [];
            foreach ($deadlines as $item) {
                $expInfo = get_expiry_info($item['expiry_date']);
                $displayTitle = !empty($item['customer']) ? $item['customer'] : ($item['document_title'] ?: 'Document #' . $item['id']);
                $displaySub = !empty($item['product_description']) ? $item['product_description'] : ($item['issued_by'] ?: '-');
                $rowState = $expInfo['status'] === 'expired' ? 'expired' : ($expInfo['status'] !== 'safe' ? 'warning' : 'normal');
                $tone = in_array($expInfo['status'], ['expired', 'critical'], true) ? 'danger' : 'warning';
                $createdText = (string) ($item['created_by_code'] ?? '-');
                if (!empty($item['user_email'])) {
                    $createdText .= "\n" . $item['user_email'];
                }

                $rows[] = [
                    'row_state' => $rowState,
                    'cells' => [
                        ['text' => (string) $item['department'], 'bold' => true],
                        ['text' => $displayTitle . "\n" . $displaySub, 'bold' => true],
                        ['text' => (string) ($item['document_number'] ?: 'N/A')],
                        ['text' => format_date_medquest($item['expiry_date']), 'bold' => true, 'tone' => 'danger'],
                        ['text' => $expInfo['short_label'], 'bold' => true, 'tone' => $tone],
                        ['text' => $createdText],
                    ],
                ];
            }

            $deptTag = $selectedDept !== 'ALL' ? $selectedDept : 'ALL';
            return [
                'sheet_title' => 'Deadline Monitoring',
                'title'       => 'DEADLINE MONITORING (H-90 TO H-0)',
                'subtitle'    => 'Department: ' . ($selectedDept === 'ALL' ? 'All Departments' : $selectedDept) . ' | Exported: ' . strtoupper(date('d M Y H:i')) . ' | Total: ' . count($rows) . ' document(s)',
                'filename'    => 'Medquest_Deadlines_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $deptTag) . '_' . $dateStamp,
                'columns'     => $columns,
                'rows'        => $rows,
            ];
        }

        $colDocLabel = match ($selectedDocType) {
            'COMPANY'     => 'Document Title & Document No.',
            'PRINCIPAL'   => 'Document Title & Document No.',
            'COOPERATION' => 'Customer & Document No',
            default       => 'Document No',
        };
        $colDetailLabel = match ($selectedDocType) {
            'COMPANY'     => 'Issued By',
            'PRINCIPAL'   => 'Issued By & Principal',
            'COOPERATION' => 'Product & S/N',
            default       => 'Detail',
        };

        $columns = [
            ['label' => 'Dept / No', 'width' => 16],
            ['label' => $colDocLabel, 'width' => 32],
            ['label' => $colDetailLabel, 'width' => 30],
            ['label' => 'Effective, Expiry & Deadline', 'width' => 30],
            ['label' => 'Status', 'width' => 18],
        ];
        if ($selectedProses === 'SELESAI') {
            $columns[] = ['label' => 'Completed Date', 'width' => 18];
        }
        $columns[] = ['label' => 'Note', 'width' => 34];
        $columns[] = ['label' => 'Created By', 'width' => 22];

        $rows = [];
        foreach ($agreements as $row) {
            $rowType = strtoupper(trim((string) ($row['doc_type'] ?? 'COOPERATION')));
            $typeMeta = get_doc_type_meta($rowType);
            $isExpNa = !empty($row['expiry_date_na']) || empty($row['expiry_date']);
            $expInfo = get_expiry_info($row['expiry_date'], $isExpNa);
            $effFormatted = format_date_medquest($row['effective_date'], $row['effective_date_na']);
            $expFormatted = format_date_medquest($row['expiry_date'], $isExpNa);
            $seq = (string) ($row['no_urut_dokumen'] ?: $row['id']);
            $displayTitle = ($rowType === 'COOPERATION')
                ? ($row['customer'] ?: '-')
                : ($row['document_title'] ?: $row['product_description'] ?: '-');

            $deptCell = $row['department'] . "\n#" . $seq;
            if ($selectedDocType === 'ALL') {
                $deptCell .= "\n" . $typeMeta['short_title'];
            }

            $docCell = $displayTitle . "\nNo: " . ($row['document_number'] ?: 'N/A');

            if ($rowType === 'COMPANY') {
                $detailCell = 'Issued By: ' . ($row['issued_by'] ?: $row['customer'] ?: '-');
            } elseif ($rowType === 'PRINCIPAL') {
                $detailCell = 'Principal: ' . ($row['principal_name'] ?: $row['customer'] ?: '-') . "\nIssued By: " . ($row['issued_by'] ?: '-');
            } else {
                $detailCell = ($row['product_description'] ?: '-') . "\nS/N: " . ($row['serial_number'] ?: 'N/A');
            }

            $validityCell = 'Effective: ' . $effFormatted . ' -> Expiry: ' . $expFormatted . "\n" . $expInfo['short_label'];
            $validityTone = in_array($expInfo['status'], ['expired', 'critical'], true)
                ? 'danger'
                : (in_array($expInfo['status'], ['warning', 'h30'], true) ? 'warning' : 'success');

            $rowProses = strtoupper(trim((string) ($row['status_proses'] ?? 'PENGAJUAN')));
            $pMeta = get_process_status_meta($rowProses);
            $statusCell = $pMeta['label'];
            if ($rowProses === 'SELESAI' && $selectedProses !== 'SELESAI') {
                $statusCell .= "\nCompleted: " . format_date_medquest($row['tanggal_selesai']);
            }
            $statusTone = $rowProses === 'SELESAI' ? 'success' : ($rowProses === 'BERJALAN' ? '' : 'warning');

            $rowState = $expInfo['status'] === 'expired'
                ? 'expired'
                : (in_array($expInfo['status'], ['critical', 'warning', 'h30'], true) ? 'warning' : 'normal');

            $cells = [
                ['text' => $deptCell, 'bold' => true],
                ['text' => $docCell, 'bold' => true],
                ['text' => $detailCell],
                ['text' => $validityCell, 'bold' => true, 'tone' => $validityTone],
                ['text' => $statusCell, 'bold' => true, 'tone' => $statusTone],
            ];
            if ($selectedProses === 'SELESAI') {
                $cells[] = ['text' => format_date_medquest($row['tanggal_selesai']), 'bold' => true, 'tone' => 'success'];
            }
            $cells[] = ['text' => (string) ($row['note'] ?: 'N/A')];
            $cells[] = ['text' => (string) ($row['created_by_log'] ?: ($row['created_by_code'] ?? '-')), 'bold' => true];

            $rows[] = [
                'row_state' => $rowState,
                'cells'     => $cells,
            ];
        }

        $prosesTitleSuffix = match ($selectedProses) {
            'PENGAJUAN' => ' - SUBMISSION STAGE',
            'BERJALAN'  => ' - IN PROGRESS',
            'SELESAI'   => ' - COMPLETED',
            default     => '',
        };
        $activeTableTitle = match ($selectedDocType) {
            'COMPANY'     => 'ORIGINAL COMPANY DOCUMENT' . $prosesTitleSuffix,
            'PRINCIPAL'   => 'LOG OF PRINCIPAL DOCUMENT' . $prosesTitleSuffix,
            'COOPERATION' => 'LOG OF COOPERATION AGREEMENT' . $prosesTitleSuffix,
            default       => 'MEDQUEST LEGAL DOCUMENTS LIST' . $prosesTitleSuffix,
        };

        $filterParts = [
            'Dept: ' . ($selectedDept === 'ALL' ? 'All' : $selectedDept),
            'Type: ' . $selectedDocType,
            'Status: ' . ($selectedProses === 'ALL' ? 'All' : $selectedProses),
            'Deadline: ' . ($selectedStatus === 'ALL' ? 'All' : $selectedStatus),
        ];
        if ($searchQuery !== '') {
            $filterParts[] = 'Search: "' . $searchQuery . '"';
        }
        $filterParts[] = 'Exported: ' . strtoupper(date('d M Y H:i'));
        $filterParts[] = 'Total: ' . count($rows) . ' document(s)';

        $fileDept = preg_replace('/[^A-Za-z0-9_-]/', '_', $selectedDept);
        $fileType = preg_replace('/[^A-Za-z0-9_-]/', '_', $selectedDocType);

        return [
            'sheet_title' => $selectedDocType === 'ALL' ? 'All Documents' : ucfirst(strtolower($selectedDocType)),
            'title'       => $activeTableTitle,
            'subtitle'    => implode(' | ', $filterParts),
            'filename'    => 'Medquest_Documents_' . $fileDept . '_' . $fileType . '_' . $dateStamp,
            'columns'     => $columns,
            'rows'        => $rows,
        ];
    }

    public function exportPage(Request $request)
    {
        $this->ensureDatabaseReady();
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        $datasetMode = strtolower(trim((string) $request->query('dataset', 'agreements')));
        if (!in_array($datasetMode, ['agreements', 'deadlines'], true)) {
            $datasetMode = 'agreements';
        }

        $filtered = $this->queryFilteredAgreements($request);
        $deadlines = $datasetMode === 'deadlines'
            ? $this->getUpcomingDeadlineItems($filtered['selectedDept'])
            : [];

        $dataset = $this->buildExportDataset(
            $datasetMode,
            $filtered['allMatchingAgreements'],
            $deadlines,
            $filtered
        );

        if (strtolower((string) $request->query('download', '')) === 'xlsx') {
            $xlsxBytes = XlsxExportService::generate(
                $dataset['sheet_title'],
                $dataset['title'],
                $dataset['subtitle'],
                $dataset['columns'],
                $dataset['rows']
            );

            return response($xlsxBytes, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $dataset['filename'] . '.xlsx"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            ]);
        }

        return view('pages.export', array_merge(
            $this->getLayoutData($request, 'export'),
            $filtered,
            compact('datasetMode', 'dataset')
        ));
    }
}
