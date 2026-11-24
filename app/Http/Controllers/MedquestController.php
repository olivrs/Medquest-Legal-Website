<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\AgreementHistory;
use App\Models\Department;
use App\Models\EmailNotification;
use App\Models\NotificationRecipient;
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
            $currentRev = '2026-11-24-v26-apps-script-email-bridge';
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
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('agreements', 'notif_h0_sent_at')) {
                        \Illuminate\Support\Facades\Schema::table('agreements', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->dateTime('notif_h0_sent_at')->nullable();
                        });
                    }
                }
                if (!\Illuminate\Support\Facades\Schema::hasTable('notification_recipients')) {
                    \Illuminate\Support\Facades\Schema::create('notification_recipients', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->id();
                        $table->string('email', 150);
                        $table->string('label', 120)->nullable();
                        $table->string('department', 30)->default('ALL');
                        $table->string('formsubmit_token', 191)->nullable();
                        $table->boolean('is_active')->default(true);
                        $table->boolean('is_verified')->default(false);
                        $table->string('last_error', 255)->nullable();
                        $table->unsignedBigInteger('added_by_user_id')->nullable();
                        $table->timestamps();
                        $table->unique(['email', 'department']);
                    });
                } else {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('notification_recipients', 'formsubmit_token')) {
                        \Illuminate\Support\Facades\Schema::table('notification_recipients', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->string('formsubmit_token', 191)->nullable()->after('department');
                        });
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('notification_recipients', 'is_verified')) {
                        \Illuminate\Support\Facades\Schema::table('notification_recipients', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->boolean('is_verified')->default(false)->after('is_active');
                        });
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('notification_recipients', 'last_error')) {
                        \Illuminate\Support\Facades\Schema::table('notification_recipients', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->string('last_error', 255)->nullable()->after('is_verified');
                        });
                    }
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('smtp_settings') && !\Illuminate\Support\Facades\Schema::hasColumn('smtp_settings', 'apps_script_url')) {
                    \Illuminate\Support\Facades\Schema::table('smtp_settings', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('apps_script_url', 500)->nullable()->after('sender_email');
                    });
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
                    $newUser = User::create([
                        'nama_lengkap'       => $nama_lengkap,
                        'kode_singkatan'     => $kode_singkatan,
                        'email'              => $email,
                        'password'           => Hash::make($password),
                        'departemen_default' => $departemen,
                        'role'               => 'user',
                        'status'             => 'pending',
                    ]);

                    try {
                        NotificationService::sendGmailNotificationPermission($newUser);
                    } catch (\Throwable $e) {
                    }

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
                if (!$expiry_date_na && $expiry_date && $status_proses !== 'SELESAI') {
                    $sisaDays = get_days_until_expiry($expiry_date);
                    $dueStage = NotificationService::resolveDueMilestoneCode($sisaDays);
                    if ($dueStage !== null) {
                        $disp = NotificationService::dispatchAgreementNotification($newAgr, $dueStage, false);
                        if (!empty($disp['success'])) {
                            $stageLabel = $dueStage === 'H-0' ? 'Hari H (H-0)' : $dueStage;
                            $extraMsg = ' Because the expiry date enters milestone <strong>' . e($stageLabel) . '</strong>, reminder email(s) were automatically dispatched to registered recipients.';
                        }
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
                $resetCols = [
                    'notif_h90_sent_at' => null,
                    'notif_h60_sent_at' => null,
                    'notif_h30_sent_at' => null,
                    'notif_h14_sent_at' => null,
                    'notif_h7_sent_at'  => null,
                    'notif_h3_sent_at'  => null,
                    'notif_h1_sent_at'  => null,
                ];
                if (\Illuminate\Support\Facades\Schema::hasColumn('agreements', 'notif_h0_sent_at')) {
                    $resetCols['notif_h0_sent_at'] = null;
                }
                $updatePayload = array_merge($updatePayload, $resetCols);
                EmailNotification::where('agreement_id', $id)->delete();
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
                if (!$expiry_date_na && $expiry_date && $status_proses !== 'SELESAI') {
                    $sisaDays = get_days_until_expiry($expiry_date);
                    $dueStage = NotificationService::resolveDueMilestoneCode($sisaDays);
                    if ($dueStage !== null) {
                        NotificationService::dispatchAgreementNotification($agrModel->fresh(), $dueStage, false);
                    }
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
        $this->ensureDatabaseReady();
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        $user  = current_user();
        $uId   = (int) $user['id'];
        $uCode = strtoupper($user['kode_singkatan']);
        $allowedDepts = get_user_allowed_departments($user);

        if ($request->isMethod('post')) {
            $action = (string) $request->input('action', '');

            if ($action === 'clear_logs') {
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

            if ($action === 'add_recipient') {
                $recEmail = strtolower(trim((string) $request->input('email', '')));
                $recLabel = trim((string) $request->input('label', ''));
                $recDept  = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $request->input('department', 'ALL')))) ?: 'ALL';

                if (!filter_var($recEmail, FILTER_VALIDATE_EMAIL)) {
                    set_flash('error', 'Please enter a valid email address.');
                    return redirect(base_url('?page=notifications'));
                }

                if (!is_superadmin() && !in_array('ALL', $allowedDepts, true)) {
                    if ($recDept === 'ALL' || !in_array($recDept, $allowedDepts, true)) {
                        if (!empty($allowedDepts)) {
                            $recDept = $allowedDepts[0];
                        } else {
                            set_flash('error', 'Your account does not have department scope to add alert recipients.');
                            return redirect(base_url('?page=notifications'));
                        }
                    }
                }

                $recModel = NotificationRecipient::updateOrCreate(
                    ['email' => $recEmail, 'department' => $recDept],
                    [
                        'label'            => $recLabel !== '' ? $recLabel : null,
                        'is_active'        => true,
                        'added_by_user_id' => $uId,
                    ]
                );

                $actRes = NotificationService::sendRecipientActivationOrTest($recEmail, $recLabel, $recDept, $recModel->formsubmit_token);
                $statusSummary = mb_substr((string) ($actRes['message'] ?? ''), 0, 250);

                if (!empty($actRes['sent'])) {
                    $recModel->update(['is_verified' => true, 'last_error' => 'Active & verified']);
                    cache()->forget('medquest_last_auto_notify');
                    $dispatched = NotificationService::runAutoExpiryNotifications(true, true);
                    $sentForNew = count(array_filter(
                        $dispatched,
                        fn ($item) => strtolower((string) ($item['recipient'] ?? '')) === $recEmail
                    ));

                    set_flash(
                        'success',
                        'Email <strong>' . e($recEmail) . '</strong> (' . e($recDept) . ') aktif &amp; siap menerima pengingat! '
                        . ($sentForNew > 0 ? "<strong>{$sentForNew}</strong> notifikasi deadline (H-90 s/d Hari H) langsung dikirim." : 'Konfirmasi berhasil dikirim ke inbox.')
                    );
                } elseif (!empty($actRes['needs_activation'])) {
                    $recModel->update(['is_verified' => false, 'last_error' => $statusSummary ?: 'Awaiting Activate Form click']);
                    set_flash(
                        'warning',
                        'Email <strong>' . e($recEmail) . '</strong> (' . e($recDept) . ') disimpan! '
                        . '<strong>Langkah wajib (1x saja):</strong> cek Inbox / folder <strong>Spam</strong> Anda untuk klik <strong>Activate Form</strong> dari FormSubmit (atau klik <strong>Aktivasi via Browser</strong> di baris tabel di bawah), lalu tekan <strong>Test / Retry</strong>.'
                    );
                } else {
                    $recModel->update(['is_verified' => false, 'last_error' => $statusSummary ?: 'FormSubmit relay failed']);
                    set_flash(
                        'error',
                        'Email <strong>' . e($recEmail) . '</strong> tersimpan, tetapi server belum bisa mengirim aktivasi otomatis: <strong>' . e($actRes['message'] ?? 'Unknown error') . '</strong>. '
                        . 'Klik tombol <strong>Aktivasi via Browser</strong> pada baris email Anda di bawah untuk memicu email <em>Activate Form</em> langsung dari browser.'
                    );
                }
                return redirect(base_url('?page=notifications'));
            }

            if ($action === 'test_recipient') {
                $recId = (int) $request->input('recipient_id', 0);
                $rec = NotificationRecipient::find($recId);
                if ($rec && (is_superadmin() || (int) $rec->added_by_user_id === $uId || strtolower($rec->email) === strtolower((string) $user['email']))) {
                    $actRes = NotificationService::sendRecipientActivationOrTest($rec->email, (string) ($rec->label ?? ''), (string) $rec->department, $rec->formsubmit_token);
                    $statusSummary = mb_substr((string) ($actRes['message'] ?? ''), 0, 250);

                    if (!empty($actRes['sent'])) {
                        $rec->update(['is_verified' => true, 'last_error' => 'Active & verified']);
                        cache()->forget('medquest_last_auto_notify');
                        $dispatched = NotificationService::runAutoExpiryNotifications(true, true);
                        $sentCount = count(array_filter(
                            $dispatched,
                            fn ($item) => strtolower((string) ($item['recipient'] ?? '')) === strtolower($rec->email)
                        ));
                        set_flash('success', 'Email <strong>' . e($rec->email) . '</strong> sudah <strong>AKTIF</strong>! Test email terkirim' . ($sentCount > 0 ? " + {$sentCount} pengingat deadline tertunda ikut dikirim." : '.'));
                    } elseif (!empty($actRes['needs_activation'])) {
                        $rec->update(['is_verified' => false, 'last_error' => $statusSummary ?: 'Awaiting Activate Form click']);
                        set_flash('warning', 'Email <strong>' . e($rec->email) . '</strong> masih menunggu klik <strong>Activate Form</strong> di Inbox/Spam Gmail Anda. Jika belum ada email masuk, klik <strong>Aktivasi via Browser</strong> di baris email tersebut.');
                    } else {
                        $rec->update(['is_verified' => false, 'last_error' => $statusSummary ?: 'FormSubmit relay failed']);
                        set_flash('error', 'Verifikasi <strong>' . e($rec->email) . '</strong> tertahan: <strong>' . e($actRes['message'] ?? 'Unknown error') . '</strong>. Silakan gunakan tombol <strong>Aktivasi via Browser</strong> atau tempel FormSubmit Hash Token.');
                    }
                }
                return redirect(base_url('?page=notifications'));
            }

            if ($action === 'save_recipient_token') {
                $recId = (int) $request->input('recipient_id', 0);
                $rawToken = trim((string) $request->input('formsubmit_token', ''));
                $rec = NotificationRecipient::find($recId);
                if ($rec && (is_superadmin() || (int) $rec->added_by_user_id === $uId || strtolower($rec->email) === strtolower((string) $user['email']))) {
                    $normalized = $rawToken !== '' ? NotificationService::normalizeFormSubmitEndpoint($rawToken) : '';
                    $rec->update(['formsubmit_token' => $normalized !== '' ? $normalized : null]);

                    $actRes = NotificationService::sendRecipientActivationOrTest($rec->email, (string) ($rec->label ?? ''), (string) $rec->department, $rec->formsubmit_token);
                    $statusSummary = mb_substr((string) ($actRes['message'] ?? ''), 0, 250);
                    if (!empty($actRes['sent'])) {
                        $rec->update(['is_verified' => true, 'last_error' => 'Active via FormSubmit Token']);
                        cache()->forget('medquest_last_auto_notify');
                        NotificationService::runAutoExpiryNotifications(true, true);
                        set_flash('success', 'Token FormSubmit untuk <strong>' . e($rec->email) . '</strong> disimpan dan terverifikasi aktif!');
                    } else {
                        $rec->update(['is_verified' => false, 'last_error' => $statusSummary ?: 'Pending verification']);
                        set_flash('warning', 'Token FormSubmit disimpan, status cek saat ini: ' . e($actRes['message'] ?? 'Pending verification'));
                    }
                }
                return redirect(base_url('?page=notifications'));
            }

            if ($action === 'toggle_recipient') {
                $recId = (int) $request->input('recipient_id', 0);
                $rec = NotificationRecipient::find($recId);
                if ($rec && (is_superadmin() || (int) $rec->added_by_user_id === $uId || strtolower($rec->email) === strtolower((string) $user['email']))) {
                    $rec->update(['is_active' => !$rec->is_active]);
                    set_flash('success', 'Recipient <strong>' . e($rec->email) . '</strong> is now ' . ($rec->is_active ? 'Active' : 'Paused') . '.');
                }
                return redirect(base_url('?page=notifications'));
            }

            if ($action === 'delete_recipient') {
                $recId = (int) $request->input('recipient_id', 0);
                $rec = NotificationRecipient::find($recId);
                if ($rec && (is_superadmin() || (int) $rec->added_by_user_id === $uId || strtolower($rec->email) === strtolower((string) $user['email']))) {
                    $em = $rec->email;
                    $rec->delete();
                    set_flash('success', 'Recipient <strong>' . e($em) . '</strong> removed.');
                }
                return redirect(base_url('?page=notifications'));
            }

            if ($action === 'run_now' && is_superadmin()) {
                cache()->forget('medquest_last_auto_notify');
                $results = NotificationService::runAutoExpiryNotifications(true, true);
                $countSent = count($results);
                set_flash('success', "Deadline reminder scan completed: <strong>{$countSent}</strong> reminder email(s) dispatched.");
                return redirect(base_url('?page=notifications'));
            }
        }

        try {
            NotificationService::runAutoExpiryNotifications();
        } catch (\Throwable $e) {
        }

        $upcomingH = $this->getUpcomingDeadlineItems();

        $recipientsQuery = NotificationRecipient::query()->orderBy('department')->orderBy('email');
        if (!is_superadmin()) {
            $userEmail = strtolower((string) ($user['email'] ?? ''));
            $recipientsQuery->where(function ($q) use ($uId, $userEmail, $allowedDepts) {
                $q->where('added_by_user_id', $uId);
                if ($userEmail !== '') {
                    $q->orWhere('email', $userEmail);
                }
                if (in_array('ALL', $allowedDepts, true)) {
                    $q->orWhere('department', 'ALL');
                } elseif (!empty($allowedDepts)) {
                    $q->orWhereIn('department', $allowedDepts);
                }
            });
        }
        $recipients = $recipientsQuery->get()->map->toArray()->all();

        $allDepts = Department::orderBy('kode_dept', 'asc')->get()->map->toArray()->all();
        if (is_superadmin() || in_array('ALL', $allowedDepts, true)) {
            $recipientDepts = $allDepts;
        } else {
            $recipientDepts = array_values(array_filter(
                $allDepts,
                fn ($d) => in_array(strtoupper((string) ($d['kode_dept'] ?? '')), $allowedDepts, true)
            ));
        }

        $smtpStatus = NotificationService::getSmtpStatus();

        $emailLogsQuery = DB::table('email_notifications as e')
            ->leftJoin('agreements as a', 'e.agreement_id', '=', 'a.id')
            ->select('e.*', 'a.department', 'a.customer', 'a.document_title', 'a.document_number', 'a.expiry_date');

        if (!is_superadmin()) {
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
            compact('upcomingH', 'emailLogs', 'recipients', 'recipientDepts', 'smtpStatus')
        ));
    }

    public function smtpSettingsPage(Request $request)
    {
        $this->ensureDatabaseReady();
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }
        if (!is_superadmin()) {
            set_flash('error', 'Access denied. System SMTP configuration is restricted to Superadmin.');
            return redirect(base_url('?page=notifications'));
        }

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

            if ($action === 'save_apps_script') {
                $gasUrl = trim((string) $request->input('apps_script_url', ''));
                if ($gasUrl === '') {
                    $smtpModel->update([
                        'apps_script_url' => null,
                        'updated_at'      => now(),
                    ]);
                    set_flash('success', 'Google Apps Script Webhook URL dinonaktifkan.');
                    return redirect(base_url('?page=smtp_settings'));
                }

                if (
                    !filter_var($gasUrl, FILTER_VALIDATE_URL)
                    || stripos($gasUrl, 'https://script.google.com/') !== 0
                ) {
                    set_flash('error', 'URL tidak valid. Harus berupa URL deployment Web App resmi Google Apps Script yang diawali https://script.google.com/macros/s/...');
                    return redirect(base_url('?page=smtp_settings'));
                }

                if (str_ends_with(strtolower(strtok($gasUrl, '?')), '/edit')) {
                    set_flash('error', 'Itu link editor Apps Script (/edit). Klik tombol Deploy -> New deployment -> Web App di Google Apps Script lalu salin URL Web App (.../exec).');
                    return redirect(base_url('?page=smtp_settings'));
                }

                $smtpModel->update([
                    'apps_script_url' => $gasUrl,
                    'updated_at'      => now(),
                ]);
                set_flash('success', 'Google Apps Script Webhook berhasil disimpan! Sekarang silakan uji dengan <strong>Test Deliver Email Now</strong> di kanan.');
                return redirect(base_url('?page=smtp_settings'));
            }

            if ($action === 'save') {
                $smtpEnabled    = $request->boolean('smtp_enabled');
                $smtpHost       = trim((string) $request->input('smtp_host', 'smtp.gmail.com'));
                $smtpPort       = max(1, (int) $request->input('smtp_port', 587));
                $smtpEncryption = strtolower(trim((string) $request->input('smtp_encryption', 'tls')));
                if (!in_array($smtpEncryption, ['tls', 'ssl', ''], true)) {
                    $smtpEncryption = 'tls';
                }
                $smtpUsername = trim((string) $request->input('smtp_username', ''));
                $rawPass      = str_replace(' ', '', trim((string) $request->input('smtp_password', '')));
                $smtpPassword = $rawPass !== '' ? $rawPass : (string) $smtpModel->smtp_password;
                $senderName   = trim((string) $request->input('sender_name', 'Medquest Agreement Alert (LAC)'));
                $senderEmail  = trim((string) $request->input('sender_email', $smtpUsername ?: 'noreply@medquest.co.id'));

                $smtpModel->update([
                    'smtp_enabled'    => $smtpEnabled ? 1 : 0,
                    'smtp_host'       => $smtpHost ?: 'smtp.gmail.com',
                    'smtp_port'       => $smtpPort,
                    'smtp_encryption' => $smtpEncryption,
                    'smtp_username'   => $smtpUsername,
                    'smtp_password'   => $smtpPassword,
                    'sender_name'     => $senderName ?: 'Medquest Agreement Alert (LAC)',
                    'sender_email'    => $senderEmail ?: $smtpUsername,
                    'updated_at'      => now(),
                ]);

                set_flash('success', 'System SMTP configuration saved successfully.');
                return redirect(base_url('?page=smtp_settings'));
            }

            if ($action === 'test_email') {
                $testTo = trim((string) $request->input('test_to_email', ''));
                if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
                    set_flash('error', 'Please provide a valid test recipient email address.');
                    return redirect(base_url('?page=smtp_settings'));
                }

                $channel = null;
                $err = null;
                $ok = NotificationService::sendHtmlViaConfiguredMailer(
                    $testTo,
                    '[MEDQUEST TEST] Email Gateway Verification',
                    '<p>Hello,</p><p>Your Medquest Legal Document Monitoring email gateway is active and ready to send automated H-90 to Hari H reminders.</p>',
                    $channel,
                    $err
                );

                if ($ok) {
                    set_flash('success', 'Test email successfully sent to <strong>' . e($testTo) . '</strong> via <strong>' . e($channel) . '</strong>.');
                } else {
                    set_flash('error', 'Test email failed: ' . e($err ?: 'Email Gateway belum dikonfigurasi (isi Google Apps Script URL atau aktifkan SMTP).'));
                }
                return redirect(base_url('?page=smtp_settings'));
            }
        }

        $smtp = $smtpModel->fresh()->toArray();
        $smtpStatus = NotificationService::getSmtpStatus();

        return view('pages.smtp_settings', array_merge(
            $this->getLayoutData($request, 'smtp_settings'),
            compact('smtp', 'smtpStatus')
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
                        try {
                            if (!empty($targetUser->email) && filter_var($targetUser->email, FILTER_VALIDATE_EMAIL)) {
                                $uDept = strtoupper(trim((string) ($targetUser->departemen_default ?: 'ALL'))) ?: 'ALL';
                                NotificationRecipient::firstOrCreate(
                                    ['email' => strtolower(trim($targetUser->email)), 'department' => $uDept],
                                    [
                                        'label'            => $targetUser->nama_lengkap . ' (' . $targetUser->kode_singkatan . ')',
                                        'is_active'        => true,
                                        'added_by_user_id' => (int) ($currentUser['id'] ?? $targetUser->id),
                                    ]
                                );
                            }
                            NotificationService::dispatchUserApprovedNotification($targetUser);
                            cache()->forget('medquest_last_auto_notify');
                            NotificationService::runAutoExpiryNotifications(true);
                        } catch (\Throwable $e) {
                        }
                        set_flash('success', "<i class='fas fa-check-circle me-1'></i> Account <strong>{$targetName} ({$targetCode})</strong> has been <strong>APPROVED</strong>! Notification email dispatched. The user can now sign in.");
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

    protected function extractRowDateYmd(array $row, string $field): ?string
    {
        return match ($field) {
            'effective_date' => !empty($row['effective_date_na'])
                ? null
                : parse_date_medquest((string) ($row['effective_date'] ?? '')),
            'created_at' => ($raw = trim((string) ($row['created_at'] ?? ''))) !== ''
                ? parse_date_medquest(substr($raw, 0, 10))
                : null,
            'tanggal_selesai' => parse_date_medquest((string) ($row['tanggal_selesai'] ?? '')),
            default => !empty($row['expiry_date_na'])
                ? null
                : parse_date_medquest((string) ($row['expiry_date'] ?? '')),
        };
    }

    protected function rowMatchesDateRange(array $row, string $field, ?string $fromYmd, ?string $toYmd): bool
    {
        if ($fromYmd === null && $toYmd === null) {
            return true;
        }
        $val = $this->extractRowDateYmd($row, $field);
        if ($val === null) {
            return false;
        }
        if ($fromYmd !== null && $val < $fromYmd) {
            return false;
        }
        if ($toYmd !== null && $val > $toYmd) {
            return false;
        }
        return true;
    }

    protected function resolveExportRowState(array $row, array $expInfo): string
    {
        $rowProses = strtoupper(trim((string) ($row['status_proses'] ?? 'PENGAJUAN')));
        if ($rowProses === 'SELESAI') {
            return 'approved';
        }
        if (($expInfo['status'] ?? '') === 'expired') {
            return 'expired';
        }
        if (($expInfo['status'] ?? '') === 'warning') {
            return 'warning';
        }
        return 'normal';
    }

    protected function sanitizeExportLabel(string $text): string
    {
        $clean = str_replace(["\xE2\x9A\xA0\xEF\xB8\x8F", "\xE2\x9A\xA0"], '', $text);
        return trim(preg_replace('/\s+/', ' ', $clean) ?? $clean);
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
        $selectedDateField = strtolower(trim((string) $request->query('date_field', 'expiry_date')));
        if (!in_array($selectedDateField, ['expiry_date', 'effective_date', 'created_at', 'tanggal_selesai'], true)) {
            $selectedDateField = 'expiry_date';
        }
        $dateFromYmd = parse_date_medquest((string) $request->query('date_from', ''));
        $dateToYmd   = parse_date_medquest((string) $request->query('date_to', ''));
        if ($dateFromYmd !== null && $dateToYmd !== null && $dateFromYmd > $dateToYmd) {
            [$dateFromYmd, $dateToYmd] = [$dateToYmd, $dateFromYmd];
        }
        $dateFrom = $dateFromYmd ? format_date_input_medquest($dateFromYmd) : '';
        $dateTo   = $dateToYmd ? format_date_input_medquest($dateToYmd) : '';

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
            'newest'         => $query->orderByDesc('a.id'),
            'oldest'         => $query->orderBy('a.id', 'asc'),
            'deadline_asc'   => $query->orderByRaw('CASE WHEN a.expiry_date IS NULL OR a.expiry_date_na = 1 THEN 1 ELSE 0 END ASC, a.expiry_date ASC, a.id DESC'),
            'deadline_desc'  => $query->orderByRaw('CASE WHEN a.expiry_date IS NULL OR a.expiry_date_na = 1 THEN 1 ELSE 0 END ASC, a.expiry_date DESC, a.id DESC'),
            'effective_asc'  => $query->orderByRaw('CASE WHEN a.effective_date IS NULL OR a.effective_date_na = 1 THEN 1 ELSE 0 END ASC, a.effective_date ASC, a.id DESC'),
            'effective_desc' => $query->orderByRaw('CASE WHEN a.effective_date IS NULL OR a.effective_date_na = 1 THEN 1 ELSE 0 END ASC, a.effective_date DESC, a.id DESC'),
            'completed_desc' => $query->orderByRaw('CASE WHEN a.tanggal_selesai IS NULL THEN 1 ELSE 0 END ASC, a.tanggal_selesai DESC, a.id DESC'),
            default          => $query->orderByRaw('COALESCE(a.no_urut_dokumen, a.id) ASC, a.id ASC'),
        };

        $rawRows = $query->get()->map(fn ($r) => (array) $r)->all();
        $fetchedRows = array_values(array_filter(
            $rawRows,
            fn (array $row) => $this->rowMatchesDateRange($row, $selectedDateField, $dateFromYmd, $dateToYmd)
        ));

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
            'selectedDateField',
            'dateFrom',
            'dateTo',
            'dateFromYmd',
            'dateToYmd',
            'searchQuery',
            'selectedSort',
            'countProsesAll',
            'countPengajuan',
            'countBerjalan',
            'countSelesai',
            'allMatchingAgreements'
        );
    }

    protected function getUpcomingDeadlineItems(string $selectedDept = 'ALL', ?string $fromYmd = null, ?string $toYmd = null): array
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
            if (!$this->rowMatchesDateRange($item, 'expiry_date', $fromYmd, $toYmd)) {
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
        $selectedDateField = $filters['selectedDateField'] ?? 'expiry_date';
        $dateFrom = $filters['dateFrom'] ?? '';
        $dateTo = $filters['dateTo'] ?? '';
        $dateFromYmd = $filters['dateFromYmd'] ?? null;
        $dateToYmd = $filters['dateToYmd'] ?? null;
        $searchQuery = $filters['searchQuery'] ?? '';
        $dateStamp = date('Y-m-d');

        $dateRangeText = '';
        if ($dateFromYmd !== null || $dateToYmd !== null) {
            $fieldName = match ($selectedDateField) {
                'effective_date'  => 'Effective Date',
                'created_at'      => 'Input Date',
                'tanggal_selesai' => 'Completed Date',
                default           => 'Expiry Date',
            };
            $dateRangeText = $fieldName . ': ' . ($dateFrom ?: 'Start') . ' - ' . ($dateTo ?: 'End');
        }

        $deptTagCode = preg_replace('/[^A-Z0-9_-]/', '', $selectedDept) ?: 'ALL';
        $typeTagCode = $datasetMode === 'deadlines'
            ? 'DL'
            : match ($selectedDocType) {
                'COMPANY'     => 'COMP',
                'PRINCIPAL'   => 'PRIN',
                'COOPERATION' => 'COOP',
                default       => 'ALL',
            };
        $prosesTagCode = match ($selectedProses) {
            'SELESAI'   => '-ACC',
            'PENGAJUAN' => '-SUB',
            'BERJALAN'  => '-PRO',
            default     => '',
        };
        $formDocNo = 'MJG-EXP-' . $typeTagCode . '-' . $deptTagCode . $prosesTagCode . '-' . date('dmy');
        $formMeta = [
            'doc_no'         => $formDocNo,
            'effective_date' => strtoupper(date('d M Y')),
            'revision_no'    => '01',
            'page_no'        => '1 of 1',
        ];

        if ($datasetMode === 'deadlines') {
            $columns = [
                ['label' => 'No.', 'width' => 6],
                ['label' => 'Dept', 'width' => 9],
                ['label' => 'Customer / Document Title', 'width' => 28],
                ['label' => 'Document No.', 'width' => 18],
                ['label' => 'Expiry Date', 'width' => 14],
                ['label' => 'Days Left', 'width' => 15],
                ['label' => 'Status', 'width' => 24],
                ['label' => 'Created By', 'width' => 22],
            ];
            $rows = [];
            foreach ($deadlines as $idx => $item) {
                $expInfo = get_expiry_info($item['expiry_date']);
                $rowState = $this->resolveExportRowState($item, $expInfo);
                $rowProses = strtoupper(trim((string) ($item['status_proses'] ?? 'PENGAJUAN')));
                $pMeta = get_process_status_meta($rowProses);
                $displayTitle = !empty($item['customer']) ? $item['customer'] : ($item['document_title'] ?: 'Document #' . $item['id']);
                $displaySub = !empty($item['product_description']) ? $item['product_description'] : ($item['issued_by'] ?: '-');
                $createdText = (string) ($item['created_by_log'] ?: ($item['created_by_code'] ?? '-'));

                $rows[] = [
                    'row_state' => $rowState,
                    'cells' => [
                        ['text' => (string) ($idx + 1), 'center' => true],
                        ['text' => (string) $item['department'], 'center' => true, 'bold' => true],
                        ['text' => $displayTitle . "\n" . $displaySub],
                        ['text' => (string) ($item['document_number'] ?: 'N/A')],
                        ['text' => format_date_medquest($item['expiry_date']), 'center' => true],
                        ['text' => $this->sanitizeExportLabel($expInfo['short_label']), 'center' => true],
                        ['text' => $pMeta['label'], 'center' => true],
                        ['text' => $createdText],
                    ],
                ];
            }

            $deptTag = $selectedDept !== 'ALL' ? $selectedDept : 'ALL';
            $subParts = ['Department: ' . ($selectedDept === 'ALL' ? 'All Departments' : $selectedDept)];
            if ($dateRangeText !== '') {
                $subParts[] = $dateRangeText;
            }
            $subParts[] = 'Exported: ' . strtoupper(date('d M Y H:i'));
            $subParts[] = 'Total: ' . count($rows) . ' document(s)';

            return [
                'sheet_title'   => 'Deadline Monitoring',
                'title'         => 'DEADLINE MONITORING (H-90 TO H-0)',
                'subtitle'      => implode(' | ', $subParts),
                'filename'      => 'Medquest_Deadlines_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $deptTag) . '_' . $dateStamp,
                'form_meta'     => $formMeta,
                'header_groups' => [],
                'columns'       => $columns,
                'rows'          => $rows,
            ];
        }

        $headerGroups = [];
        if ($selectedDocType === 'PRINCIPAL') {
            $columns = [
                ['label' => 'No.', 'width' => 6],
                ['label' => 'Document Title', 'width' => 28],
                ['label' => 'Document No.', 'width' => 18],
                ['label' => 'Issued by', 'width' => 24],
                ['label' => 'Effective Date', 'width' => 14],
                ['label' => 'Expiry Date', 'width' => 14],
                ['label' => 'Principal', 'width' => 20],
                ['label' => 'Note', 'width' => 26],
                ['label' => 'Created By', 'width' => 20],
            ];
        } elseif ($selectedDocType === 'COOPERATION') {
            $headerGroups = [
                ['label' => 'Department / Division', 'colspan' => 1],
                ['label' => 'Document Number', 'colspan' => 1],
                ['label' => 'Customer', 'colspan' => 1],
                ['label' => 'Product', 'colspan' => 2],
                ['label' => 'Effective Date', 'colspan' => 1],
                ['label' => 'Expiry Date', 'colspan' => 1],
                ['label' => 'Note', 'colspan' => 1],
                ['label' => 'Created By', 'colspan' => 1],
                ['label' => 'No.', 'colspan' => 1],
            ];
            $columns = [
                ['label' => 'Department / Division', 'width' => 12],
                ['label' => 'Document Number', 'width' => 22],
                ['label' => 'Customer', 'width' => 24],
                ['label' => 'Description', 'width' => 24],
                ['label' => 'Serial Number', 'width' => 14],
                ['label' => 'Effective Date', 'width' => 14],
                ['label' => 'Expiry Date', 'width' => 14],
                ['label' => 'Note', 'width' => 26],
                ['label' => 'Created By', 'width' => 20],
                ['label' => 'No.', 'width' => 7],
            ];
        } elseif ($selectedDocType === 'COMPANY') {
            $columns = [
                ['label' => 'No.', 'width' => 6],
                ['label' => 'Document Title', 'width' => 30],
                ['label' => 'Document No.', 'width' => 20],
                ['label' => 'Issued by', 'width' => 24],
                ['label' => 'Effective Date', 'width' => 14],
                ['label' => 'Expiry Date', 'width' => 14],
                ['label' => 'Note', 'width' => 28],
                ['label' => 'Created By', 'width' => 22],
            ];
        } else {
            $columns = [
                ['label' => 'No.', 'width' => 6],
                ['label' => 'Dept', 'width' => 9],
                ['label' => 'Document Title / Customer', 'width' => 26],
                ['label' => 'Document No.', 'width' => 18],
                ['label' => 'Detail', 'width' => 24],
                ['label' => 'Effective Date', 'width' => 14],
                ['label' => 'Expiry Date', 'width' => 14],
                ['label' => 'Status', 'width' => 16],
                ['label' => 'Note', 'width' => 24],
                ['label' => 'Created By', 'width' => 19],
            ];
        }

        $rows = [];
        foreach ($agreements as $idx => $row) {
            $rowType = strtoupper(trim((string) ($row['doc_type'] ?? 'COOPERATION')));
            $isExpNa = !empty($row['expiry_date_na']) || empty($row['expiry_date']);
            $expInfo = get_expiry_info($row['expiry_date'], $isExpNa);
            $effFormatted = format_date_medquest($row['effective_date'], $row['effective_date_na']);
            $expFormatted = format_date_medquest($row['expiry_date'], $isExpNa);
            $rowState = $this->resolveExportRowState($row, $expInfo);

            $rowProses = strtoupper(trim((string) ($row['status_proses'] ?? 'PENGAJUAN')));
            $pMeta = get_process_status_meta($rowProses);
            $noteText = (string) ($row['note'] ?: 'N/A');
            $createdByText = (string) ($row['created_by_log'] ?: ($row['created_by_code'] ?? '-'));
            $docOrderNo = (string) (!empty($row['no_urut_dokumen']) ? $row['no_urut_dokumen'] : ($idx + 1));

            if ($selectedDocType === 'PRINCIPAL') {
                $cells = [
                    ['text' => $docOrderNo, 'center' => true],
                    ['text' => (string) ($row['document_title'] ?: $row['product_description'] ?: '-')],
                    ['text' => (string) ($row['document_number'] ?: 'N/A'), 'center' => true],
                    ['text' => (string) ($row['issued_by'] ?: $row['customer'] ?: '-')],
                    ['text' => $effFormatted, 'center' => true],
                    ['text' => $expFormatted, 'center' => true],
                    ['text' => (string) ($row['principal_name'] ?: $row['customer'] ?: '-')],
                    ['text' => $noteText],
                    ['text' => $createdByText],
                ];
            } elseif ($selectedDocType === 'COOPERATION') {
                $cells = [
                    ['text' => (string) $row['department'], 'center' => true, 'bold' => true],
                    ['text' => (string) ($row['document_number'] ?: 'N/A'), 'center' => true],
                    ['text' => (string) ($row['customer'] ?: '-')],
                    ['text' => (string) ($row['product_description'] ?: '-')],
                    ['text' => (string) ($row['serial_number'] ?: 'N/A'), 'center' => true],
                    ['text' => $effFormatted, 'center' => true],
                    ['text' => $expFormatted, 'center' => true],
                    ['text' => $noteText],
                    ['text' => $createdByText],
                    ['text' => $docOrderNo, 'center' => true],
                ];
            } elseif ($selectedDocType === 'COMPANY') {
                $cells = [
                    ['text' => $docOrderNo, 'center' => true],
                    ['text' => (string) ($row['document_title'] ?: $row['product_description'] ?: '-')],
                    ['text' => (string) ($row['document_number'] ?: 'N/A'), 'center' => true],
                    ['text' => (string) ($row['issued_by'] ?: $row['customer'] ?: '-')],
                    ['text' => $effFormatted, 'center' => true],
                    ['text' => $expFormatted, 'center' => true],
                    ['text' => $noteText],
                    ['text' => $createdByText],
                ];
            } else {
                $displayTitle = ($rowType === 'COOPERATION')
                    ? ($row['customer'] ?: '-')
                    : ($row['document_title'] ?: $row['product_description'] ?: '-');
                if ($rowType === 'COMPANY') {
                    $detailCell = 'Issued By: ' . ($row['issued_by'] ?: $row['customer'] ?: '-');
                } elseif ($rowType === 'PRINCIPAL') {
                    $detailCell = 'Principal: ' . ($row['principal_name'] ?: $row['customer'] ?: '-') . "\nIssued By: " . ($row['issued_by'] ?: '-');
                } else {
                    $detailCell = ($row['product_description'] ?: '-') . "\nS/N: " . ($row['serial_number'] ?: 'N/A');
                }
                $statusText = $pMeta['label'];
                if ($rowProses === 'SELESAI' && !empty($row['tanggal_selesai'])) {
                    $statusText .= "\n" . format_date_medquest($row['tanggal_selesai']);
                }

                $cells = [
                    ['text' => (string) ($idx + 1), 'center' => true],
                    ['text' => (string) $row['department'], 'center' => true, 'bold' => true],
                    ['text' => $displayTitle],
                    ['text' => (string) ($row['document_number'] ?: 'N/A'), 'center' => true],
                    ['text' => $detailCell],
                    ['text' => $effFormatted, 'center' => true],
                    ['text' => $expFormatted, 'center' => true],
                    ['text' => $statusText, 'center' => true],
                    ['text' => $noteText],
                    ['text' => $createdByText],
                ];
            }

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
        if ($dateRangeText !== '') {
            $filterParts[] = $dateRangeText;
        }
        if ($searchQuery !== '') {
            $filterParts[] = 'Search: "' . $searchQuery . '"';
        }
        $filterParts[] = 'Exported: ' . strtoupper(date('d M Y H:i'));
        $filterParts[] = 'Total: ' . count($rows) . ' document(s)';

        $fileDept = preg_replace('/[^A-Za-z0-9_-]/', '_', $selectedDept);
        $fileType = preg_replace('/[^A-Za-z0-9_-]/', '_', $selectedDocType);

        return [
            'sheet_title'   => $selectedDocType === 'ALL' ? 'All Documents' : ucfirst(strtolower($selectedDocType)),
            'title'         => $activeTableTitle,
            'subtitle'      => implode(' | ', $filterParts),
            'filename'      => 'Medquest_Documents_' . $fileDept . '_' . $fileType . '_' . $dateStamp,
            'form_meta'     => $formMeta,
            'header_groups' => $headerGroups,
            'columns'       => $columns,
            'rows'          => $rows,
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
            ? $this->getUpcomingDeadlineItems($filtered['selectedDept'], $filtered['dateFromYmd'], $filtered['dateToYmd'])
            : [];

        $dataset = $this->buildExportDataset(
            $datasetMode,
            $filtered['allMatchingAgreements'],
            $deadlines,
            $filtered
        );

        $logoPath = public_path('assets/img/logo_medquest.png');

        if (strtolower((string) $request->query('download', '')) === 'xlsx') {
            $xlsxBytes = XlsxExportService::generate(
                $dataset['sheet_title'],
                $dataset['title'],
                $dataset['subtitle'],
                $dataset['columns'],
                $dataset['rows'],
                $dataset['form_meta'] ?? [],
                $logoPath,
                $dataset['header_groups'] ?? []
            );

            return response($xlsxBytes, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $dataset['filename'] . '.xlsx"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            ]);
        }

        $logoDataUri = is_file($logoPath)
            ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath))
            : '';

        return view('pages.export', array_merge(
            $this->getLayoutData($request, 'export'),
            $filtered,
            compact('datasetMode', 'dataset', 'logoDataUri')
        ));
    }
}
