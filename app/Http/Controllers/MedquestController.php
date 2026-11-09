<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\AgreementHistory;
use App\Models\Department;
use App\Models\EmailNotification;
use App\Models\SmtpSetting;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MedquestController extends Controller
{
    /**
     * Pastikan tabel database sudah terbuat (otomatis jalankan migrate --seed jika database CloudPanel masih kosong)
     */
    protected function ensureDatabaseReady(): void
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', [
                    '--seed'  => true,
                    '--force' => true,
                ]);
            }
        } catch (\Throwable $e) {
            // Abaikan jika koneksi belum diatur di .env
        }
    }

    /**
     * Entrypoint utama yang mendukung ?page=... maupun route Laravel langsung
     */
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
            'notifications'    => $this->notificationsPage($request),
            'smtp_settings'    => $this->smtpSettingsPage($request),
            'user_approvals'   => $this->userApprovalsPage($request),
            default            => Auth::check() ? $this->agreementsPage($request) : $this->loginPage($request),
        };
    }

    /**
     * Siapkan variabel layout global untuk header/sidebar
     */
    protected function getLayoutData(Request $request, string $currPage): array
    {
        $currDept = strtoupper(trim((string) $request->query('dept', 'ALL')));
        $user = current_user();

        $alertHCount = 0;
        $totalNotifCount = 0;
        $sidebarDepts = [];
        $userDeptCounts = [];
        $userTotalDocs = 0;
        $pendingApprovalsCount = 0;

        if (Auth::check() && $user) {
            $uId = (int) $user['id'];
            $uCode = strtoupper($user['kode_singkatan']);

            $allUserDocs = Agreement::where(function ($q) use ($uId, $uCode) {
                $q->where('created_by_user_id', $uId)
                  ->orWhere('created_by_code', $uCode);
            })->get(['id', 'department', 'expiry_date', 'expiry_date_na']);

            foreach ($allUserDocs as $doc) {
                $dCode = strtoupper((string) $doc->department);
                $userDeptCounts[$dCode] = ($userDeptCounts[$dCode] ?? 0) + 1;
                $userTotalDocs++;

                if (!$doc->expiry_date_na && !empty($doc->expiry_date)) {
                    $days = get_days_until_expiry($doc->expiry_date);
                    if ($days >= 0 && $days <= 90) {
                        $alertHCount++;
                    }
                }
            }

            $totalNotifCount = EmailNotification::where('user_id', $uId)
                ->orWhere('recipient_code', $uCode)
                ->count();

            $sidebarDepts = Department::orderBy('id', 'asc')->get()->map->toArray()->all();

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
                set_flash('error', 'Harap isi Email / NIK dan Password Anda.');
            } else {
                $user = User::where('email', $identitas)
                    ->orWhere('kode_singkatan', strtoupper($identitas))
                    ->first();

                if ($user && Hash::check($password, $user->password)) {
                    $status = $user->status ?? 'approved';
                    if ($status === 'pending') {
                        set_flash('error', '<div class="fw-bold mb-1"><i class="fas fa-hourglass-half me-1 text-warning"></i> Akun Sedang Ditinjau!</div>Akun dengan kode <strong>' . e($user->kode_singkatan) . '</strong> sudah didaftarkan dan <strong>sedang ditinjau oleh Team Medquest</strong>.<br><span class="small opacity-85">Mohon menunggu persetujuan (ACC) dari Superadmin dan cek secara berkala.</span>');
                        return redirect(base_url('?page=login'));
                    } elseif ($status === 'rejected') {
                        set_flash('error', '<div class="fw-bold mb-1"><i class="fas fa-times-circle me-1 text-danger"></i> Pendaftaran Akun Ditolak</div>Pendaftaran akun dengan kode <strong>' . e($user->kode_singkatan) . '</strong> ditolak oleh Superadmin. Silakan hubungi admin Medquest.');
                        return redirect(base_url('?page=login'));
                    }

                    Auth::login($user);
                    $request->session()->regenerate();

                    set_flash('success', 'Selamat datang kembali, <strong>' . e($user->nama_lengkap) . ' (' . e($user->kode_singkatan) . ')</strong>!');
                    $redirectDept = ($user->departemen_default && $user->departemen_default !== 'ALL')
                        ? '&dept=' . urlencode($user->departemen_default)
                        : '';
                    return redirect(base_url('?page=agreements' . $redirectDept));
                }

                set_flash('error', 'Email / NIK atau Password tidak sesuai.');
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
                set_flash('error', 'Semua kolom wajib diisi (Nama Lengkap, Kode Singkatan, Departemen, Email Pribadi, dan Password).');
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                set_flash('error', 'Format Email Pribadi tidak valid.');
            } elseif (strlen($kode_singkatan) < 2 || strlen($kode_singkatan) > 10) {
                set_flash('error', 'Nama Singkatan Medquest harus terdiri dari 2 sampai 10 karakter (contoh: RYN atau FJR).');
            } elseif (strlen($password) < 6) {
                set_flash('error', 'Password minimal terdiri dari 6 karakter.');
            } elseif ($password !== $password_confirm) {
                set_flash('error', 'Konfirmasi password tidak cocok.');
            } else {
                $existing = User::where('email', $email)
                    ->orWhere('kode_singkatan', $kode_singkatan)
                    ->first();

                if ($existing) {
                    if (strtolower($existing->email) === $email) {
                        set_flash('error', 'Email pribadi <strong>' . e($email) . '</strong> sudah terdaftar. Silakan gunakan email lain atau hubungi admin.');
                    } else {
                        set_flash('error', 'Singkatan nama <strong>' . e($kode_singkatan) . '</strong> sudah digunakan user lain.');
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
        set_flash('success', 'Anda telah keluar dari sistem.');
        return redirect(base_url('?page=login'));
    }

    public function agreementsPage(Request $request)
    {
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        $user  = current_user();
        $uId   = (int) $user['id'];
        $uCode = strtoupper($user['kode_singkatan']);

        NotificationService::runAutoExpiryNotifications();

        if ($request->isMethod('post')) {
            $action = (string) $request->input('action', '');

            if ($action === 'add_note') {
                $agrId       = (int) $request->input('agreement_id', 0);
                $catatanBaru = trim((string) $request->input('catatan_baru', ''));
                $tglInput    = $request->filled('tanggal_input') ? (string) $request->input('tanggal_input') : date('Y-m-d');
                $newExpiry   = $request->filled('new_expiry_date') ? (string) $request->input('new_expiry_date') : null;

                $agr = Agreement::where('id', $agrId)
                    ->where(function ($q) use ($uId, $uCode) {
                        $q->where('created_by_user_id', $uId)
                          ->orWhere('created_by_code', $uCode);
                    })->first();

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

                    set_flash('success', "Catatan baru berhasil disimpan dan otomatis ditandai dengan <strong>{$stampBaru}</strong>.");
                }

                return redirect(base_url('?page=agreements&dept=' . urlencode((string) $request->input('return_dept', 'ALL'))));
            }

            if ($action === 'delete') {
                $agrId = (int) $request->input('agreement_id', 0);
                Agreement::where('id', $agrId)
                    ->where(function ($q) use ($uId, $uCode) {
                        $q->where('created_by_user_id', $uId)
                          ->orWhere('created_by_code', $uCode);
                    })->delete();

                set_flash('success', 'Data dokumen berhasil dihapus.');
                return redirect(base_url('?page=agreements&dept=' . urlencode((string) $request->input('return_dept', 'ALL'))));
            }

            if ($action === 'add_department' && is_superadmin()) {
                $kodeDept = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $request->input('kode_dept', ''))));
                $namaDept = trim((string) $request->input('nama_dept', ''));
                if ($kodeDept !== '' && $namaDept !== '') {
                    Department::firstOrCreate(
                        ['kode_dept' => $kodeDept],
                        ['nama_dept' => $namaDept]
                    );
                    set_flash('success', "Departemen <strong>{$kodeDept}</strong> berhasil ditambahkan.");
                }
                return redirect(base_url('?page=agreements&dept=' . urlencode($kodeDept ?: 'ALL')));
            }
        }

        $selectedDept    = strtoupper(trim((string) $request->query('dept', 'ALL')));
        $selectedStatus  = trim((string) $request->query('status', 'ALL'));
        $selectedDocType = strtoupper(trim((string) $request->query('doc_type', 'ALL')));
        $searchQuery     = trim((string) $request->query('q', ''));
        $selectedSort    = trim((string) $request->query('sort', 'default'));
        $currentPage     = max(1, (int) $request->query('p', 1));
        $perPage         = 15;

        $query = DB::table('agreements as a')
            ->leftJoin('users as u', 'a.created_by_user_id', '=', 'u.id')
            ->select('a.*', 'u.nama_lengkap as creator_name', 'u.email as creator_email')
            ->where(function ($q) use ($uId, $uCode) {
                $q->where('a.created_by_user_id', $uId)
                  ->orWhere('a.created_by_code', $uCode);
            });

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

        // Filter berdasarkan status menggunakan fungsi perhitungan tanggal PHP agar identik di MySQL & SQLite
        $allMatchingAgreements = array_values(array_filter($fetchedRows, function (array $row) use ($selectedStatus) {
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
                    ['nama_dept' => 'Divisi ' . $department]
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
            $effective_date    = ($effective_date_na || !$request->filled('effective_date')) ? null : (string) $request->input('effective_date');
            if ($effective_date === null) {
                $effective_date_na = 1;
            }

            $expiry_date_na = $request->has('expiry_date_na') ? 1 : 0;
            $expiry_date    = ($expiry_date_na || !$request->filled('expiry_date')) ? null : (string) $request->input('expiry_date');
            if ($expiry_date === null) {
                $expiry_date_na = 1;
            }

            $tanggal_input    = $request->filled('tanggal_input') ? (string) $request->input('tanggal_input') : date('Y-m-d');
            $raw_note         = trim((string) $request->input('note', ''));
            $prefix_date_note = $request->has('prefix_date_note');

            $errorMsg = '';
            if ($doc_type === 'COOPERATION') {
                if ($document_number === '' || $customer === '' || $product_description === '' || (!$expiry_date_na && !$expiry_date)) {
                    $errorMsg = 'Untuk Log of Cooperation Agreement, harap lengkapi Document Number, Customer, Product Description, dan Expiry Date.';
                }
            } elseif ($doc_type === 'COMPANY') {
                if ($document_title === '' || $issued_by === '') {
                    $errorMsg = 'Untuk Original Company Document, harap lengkapi Document Title dan Issued By.';
                }
            } elseif ($doc_type === 'PRINCIPAL') {
                if ($document_title === '' || $issued_by === '' || $principal_name === '') {
                    $errorMsg = 'Untuk Log of Principal Document, harap lengkapi Document Title, Issued By, dan Principal.';
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

                $newAgr = Agreement::create([
                    'doc_type'            => $doc_type,
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
                        $extraMsg = ' Karena tanggal berada di titik pengingat (H-90/60/30/14/7/3), sistem langsung memproses pengiriman email ke <strong>' . e($user['email']) . '</strong>!';
                    }
                }

                $meta = get_doc_type_meta($doc_type);
                set_flash('success', "Dokumen <strong>{$meta['title']}</strong> berhasil disimpan ke departemen <strong>{$department}</strong> dengan tanda Created By: <strong>{$createdByStamp}</strong>.{$extraMsg}");
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
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        $user  = current_user();
        $id    = $routeId ?? (int) $request->query('id', 0);
        $uId   = (int) $user['id'];
        $uCode = strtoupper($user['kode_singkatan']);

        $agrModel = Agreement::where('id', $id)
            ->where(function ($q) use ($uId, $uCode) {
                $q->where('created_by_user_id', $uId)
                  ->orWhere('created_by_code', $uCode);
            })->first();

        if (!$agrModel) {
            set_flash('error', 'Data dokumen tidak ditemukan.');
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
            $effective_date    = ($effective_date_na || !$request->filled('effective_date')) ? null : (string) $request->input('effective_date');
            if ($effective_date === null) {
                $effective_date_na = 1;
            }

            $expiry_date_na = $request->has('expiry_date_na') ? 1 : 0;
            $expiry_date    = ($expiry_date_na || !$request->filled('expiry_date')) ? null : (string) $request->input('expiry_date');
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

            $updatePayload = [
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

            if (!$expiry_date_na && $expiry_date) {
                NotificationService::runAutoExpiryNotifications();
            }

            set_flash('success', 'Data dokumen berhasil diperbarui.');
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
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        $user  = current_user();
        $id    = $routeId ?? (int) $request->query('id', 0);
        $uId   = (int) $user['id'];
        $uCode = strtoupper($user['kode_singkatan']);

        $agrRow = DB::table('agreements as a')
            ->leftJoin('users as u', 'a.created_by_user_id', '=', 'u.id')
            ->select('a.*', 'u.nama_lengkap as creator_name', 'u.email as creator_email')
            ->where('a.id', $id)
            ->where(function ($q) use ($uId, $uCode) {
                $q->where('a.created_by_user_id', $uId)
                  ->orWhere('a.created_by_code', $uCode);
            })->first();

        if (!$agrRow) {
            set_flash('error', 'Data dokumen tidak ditemukan.');
            return redirect(base_url('?page=agreements'));
        }

        $agr = (array) $agrRow;

        if ($request->isMethod('post') && $request->input('action') === 'upload_file') {
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
                    set_flash('success', 'File dokumen berhasil dilampirkan.');
                    return redirect(base_url('?page=detail&id=' . $id));
                } else {
                    set_flash('error', 'Format file harus PDF, JPG, PNG, Word, atau Excel.');
                }
            }
        }

        $docType      = strtoupper(trim((string) ($agr['doc_type'] ?? 'COOPERATION')));
        $typeMeta     = get_doc_type_meta($docType);
        $isExpNa      = !empty($agr['expiry_date_na']) || empty($agr['expiry_date']);
        $expInfo      = get_expiry_info($agr['expiry_date'], $isExpNa);
        $cleanLabel   = preg_replace('/\s*\(Fase\s+H-\d+\)/i', '', $expInfo['short_label']);
        $effFormatted = format_date_medquest($agr['effective_date'], $agr['effective_date_na']);
        $expFormatted = format_date_medquest($agr['expiry_date'], $isExpNa);

        $histories = AgreementHistory::where('agreement_id', $id)->orderBy('id', 'asc')->get()->map->toArray()->all();
        $backUrl = base_url('?page=agreements&dept=' . urlencode((string) $agr['department']) . '&doc_type=' . urlencode($docType));

        return view('pages.detail', array_merge(
            $this->getLayoutData($request, 'detail'),
            compact('id', 'agr', 'docType', 'typeMeta', 'isExpNa', 'expInfo', 'cleanLabel', 'effFormatted', 'expFormatted', 'histories', 'backUrl', 'uCode')
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

        NotificationService::runAutoExpiryNotifications();

        if ($request->isMethod('post') && $request->input('action') === 'clear_logs') {
            EmailNotification::where('user_id', $uId)
                ->orWhere('recipient_code', $uCode)
                ->delete();
            set_flash('success', 'Riwayat log email berhasil dibersihkan.');
            return redirect(base_url('?page=notifications'));
        }

        $allUserAgreements = DB::table('agreements as a')
            ->leftJoin('users as u', 'a.created_by_user_id', '=', 'u.id')
            ->select('a.*', 'u.nama_lengkap', 'u.email as user_email')
            ->where(function ($q) use ($uId, $uCode) {
                $q->where('a.created_by_user_id', $uId)
                  ->orWhere('a.created_by_code', $uCode);
            })->get()->map(fn ($r) => (array) $r)->all();

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

        $emailLogs = DB::table('email_notifications as e')
            ->leftJoin('agreements as a', 'e.agreement_id', '=', 'a.id')
            ->select('e.*', 'a.department', 'a.customer', 'a.document_number', 'a.expiry_date')
            ->where(function ($q) use ($uId, $uCode) {
                $q->where('e.user_id', $uId)
                  ->orWhere('e.recipient_code', $uCode);
            })
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
                    set_flash('success', "Konfigurasi Gmail SMTP tersimpan & sistem <strong>langsung mengirimkan {$sentReal} email otomatis (H-7/H-3/H-1)</strong> ke Inbox Gmail penginput data!");
                } elseif ($smtp_enabled) {
                    set_flash('success', 'Konfigurasi Gmail SMTP berhasil diaktifkan! Mulai sekarang setiap dokumen yang masuk H-7, H-3, dan H-1 akan langsung masuk ke Inbox Gmail user secara otomatis.');
                } else {
                    set_flash('info', 'Pengaturan SMTP disimpan.');
                }

                return redirect(base_url('?page=smtp_settings'));
            }

            if ($action === 'test_email') {
                $targetEmail = trim((string) $request->input('test_target_email', $user['email']));
                $sampleAgr = Agreement::orderByDesc('id')->first();
                if ($sampleAgr) {
                    $err = null;
                    $subject = '[TEST GMAIL MEDQUEST] Uji Coba Notifikasi Otomatis H-7, H-3, H-1';
                    $htmlBody = NotificationService::buildExpiryEmailHtml($sampleAgr->toArray(), [
                        'nama_lengkap'   => $user['nama_lengkap'],
                        'kode_singkatan' => $user['kode_singkatan'],
                        'email'          => $targetEmail,
                    ], 'H-3', 3);

                    if ($smtpModel->smtp_enabled && !empty($smtpModel->smtp_username) && !empty($smtpModel->smtp_password)) {
                        if (NotificationService::sendViaDynamicSmtp($smtpModel, $targetEmail, $subject, $htmlBody, $err)) {
                            set_flash('success', "BERHASIL! Email uji coba telah dikirim langsung ke Inbox Gmail <strong>{$targetEmail}</strong>. Silakan cek Inbox/Spam Anda sekarang!");
                        } else {
                            set_flash('error', "Gagal mengirim ke Gmail: <strong>{$err}</strong>");
                        }
                    } else {
                        set_flash('error', 'Harap isi Email Gmail Pengirim dan 16-Digit App Password Google pada form di atas terlebih dahulu, lalu klik Simpan.');
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
        if (!Auth::check()) {
            return redirect(base_url('?page=login'));
        }

        if (!is_superadmin()) {
            set_flash('error', 'Akses ditolak. Halaman Persetujuan Akun hanya dapat diakses oleh Superadmin.');
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
                        set_flash('success', "<i class='fas fa-check-circle me-1'></i> Akun <strong>{$targetName} ({$targetCode})</strong> berhasil <strong>DISETUJUI (ACC)</strong>! User sekarang dapat login.");
                    } elseif ($action === 'reject') {
                        $targetUser->update(['status' => 'rejected']);
                        set_flash('error', "<i class='fas fa-times-circle me-1'></i> Pendaftaran akun <strong>{$targetName} ({$targetCode})</strong> telah <strong>DITOLAK</strong>.");
                    } elseif ($action === 'delete') {
                        if ($targetId === (int) $currentUser['id']) {
                            set_flash('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.');
                        } else {
                            $targetUser->delete();
                            set_flash('success', "<i class='fas fa-trash-alt me-1'></i> Akun <strong>{$targetName} ({$targetCode})</strong> berhasil dihapus.");
                        }
                    } elseif ($action === 'set_role') {
                        $newRole = in_array($request->input('new_role'), ['superadmin', 'admin', 'user'], true)
                            ? (string) $request->input('new_role')
                            : 'user';
                        $targetUser->update(['role' => $newRole]);
                        set_flash('success', "Role user <strong>{$targetName}</strong> berhasil diubah menjadi <strong>{$newRole}</strong>.");
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

        return view('pages.user_approvals', array_merge(
            $this->getLayoutData($request, 'user_approvals'),
            compact('countPending', 'countApproved', 'countRejected', 'countTotal', 'tab', 'search', 'usersList')
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
}
