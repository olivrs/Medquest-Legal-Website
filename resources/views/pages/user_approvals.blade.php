@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
    <div class="cp-header-row mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('?page=agreements') ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            <h1 class="cp-page-title ms-1">Persetujuan Akun</h1>
            <?php if ($countPending > 0): ?>
                <span class="badge bg-warning text-dark rounded-0 px-2 py-1 ms-1" style="font-size:0.72rem;"><?= $countPending ?> Pending</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <a href="<?= base_url('?page=user_approvals&tab=pending') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-0 p-3 transition-hover <?= $tab === 'pending' ? 'ring-primary border-warning' : '' ?>" style="background: #fffdf5; border-left: 4px solid #f59e0b !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold">MENUNGGU ACC</div>
                            <div class="fs-3 fw-bold text-dark mt-1"><?= $countPending ?></div>
                            <div class="text-warning small fw-semibold mt-1">
                                <i class="fas fa-hourglass-half me-1"></i> Perlu Tindakan
                            </div>
                        </div>
                        <div class="rounded-0 d-flex align-items-center justify-content-center" style="width:50px;height:50px;background:#fef3c7;color:#d97706;font-size:22px;">
                            <i class="fas fa-user-clock"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a href="<?= base_url('?page=user_approvals&tab=approved') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-0 p-3 transition-hover <?= $tab === 'approved' ? 'ring-primary border-success' : '' ?>" style="background: #f6fdf9; border-left: 4px solid #10b981 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold">SUDAH DISETUJUI</div>
                            <div class="fs-3 fw-bold text-dark mt-1"><?= $countApproved ?></div>
                            <div class="text-success small fw-semibold mt-1">
                                <i class="fas fa-check-circle me-1"></i> Aktif &amp; Siap Login
                            </div>
                        </div>
                        <div class="rounded-0 d-flex align-items-center justify-content-center" style="width:50px;height:50px;background:#d1fae5;color:#059669;font-size:22px;">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a href="<?= base_url('?page=user_approvals&tab=rejected') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-0 p-3 transition-hover <?= $tab === 'rejected' ? 'ring-primary border-danger' : '' ?>" style="background: #fff8f8; border-left: 4px solid #ef4444 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold">DITOLAK</div>
                            <div class="fs-3 fw-bold text-dark mt-1"><?= $countRejected ?></div>
                            <div class="text-danger small fw-semibold mt-1">
                                <i class="fas fa-ban me-1"></i> Tidak Dapat Login
                            </div>
                        </div>
                        <div class="rounded-0 d-flex align-items-center justify-content-center" style="width:50px;height:50px;background:#fee2e2;color:#dc2626;font-size:22px;">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a href="<?= base_url('?page=user_approvals&tab=all') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-0 p-3 transition-hover <?= $tab === 'all' ? 'ring-primary border-primary' : '' ?>" style="background: #f8fafc; border-left: 4px solid #2b579a !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold">TOTAL SEMUA USER</div>
                            <div class="fs-3 fw-bold text-dark mt-1"><?= $countTotal ?></div>
                            <div class="text-primary small fw-semibold mt-1">
                                <i class="fas fa-users me-1"></i> Basis Data User
                            </div>
                        </div>
                        <div class="rounded-0 d-flex align-items-center justify-content-center" style="width:50px;height:50px;background:#eff6ff;color:#2b579a;font-size:22px;">
                            <i class="fas fa-users-cog"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-0 mb-4">
        <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <ul class="nav nav-pills gap-1">
                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3 py-2 <?= $tab === 'pending' ? 'active' : '' ?>" href="<?= base_url('?page=user_approvals&tab=pending' . ($search ? '&q=' . urlencode($search) : '')) ?>">
                        <i class="fas fa-hourglass-half me-1"></i> Menunggu Peninjauan
                        <?php if ($countPending > 0): ?>
                            <span class="badge rounded-0 bg-warning text-dark ms-1"><?= $countPending ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3 py-2 <?= $tab === 'approved' ? 'active' : '' ?>" href="<?= base_url('?page=user_approvals&tab=approved' . ($search ? '&q=' . urlencode($search) : '')) ?>">
                        <i class="fas fa-check-circle me-1"></i> Disetujui (Approved)
                        <span class="badge rounded-0 bg-light text-dark ms-1"><?= $countApproved ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3 py-2 <?= $tab === 'rejected' ? 'active' : '' ?>" href="<?= base_url('?page=user_approvals&tab=rejected' . ($search ? '&q=' . urlencode($search) : '')) ?>">
                        <i class="fas fa-times-circle me-1"></i> Ditolak (Rejected)
                        <span class="badge rounded-0 bg-light text-dark ms-1"><?= $countRejected ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3 py-2 <?= $tab === 'all' ? 'active' : '' ?>" href="<?= base_url('?page=user_approvals&tab=all' . ($search ? '&q=' . urlencode($search) : '')) ?>">
                        <i class="fas fa-list me-1"></i> Semua User
                        <span class="badge rounded-0 bg-light text-dark ms-1"><?= $countTotal ?></span>
                    </a>
                </li>
            </ul>

            <form method="GET" action="<?= base_url() ?>" class="d-flex align-items-center gap-2">
                <input type="hidden" name="page" value="user_approvals">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <div class="input-group input-group-sm" style="min-width:240px;">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nama, kode, email..." value="<?= htmlspecialchars($search) ?>">
                    <?php if ($search !== ''): ?>
                        <a href="<?= base_url('?page=user_approvals&tab=' . urlencode($tab)) ?>" class="btn btn-outline-secondary" title="Reset Pencarian">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-sm fw-semibold">Cari</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-3" style="width:45px;">No</th>
                        <th>User &amp; Nama Lengkap</th>
                        <th>Kode</th>
                        <th>Role Akses (Edit)</th>
                        <th style="min-width:290px;">Akses Departemen (Centang untuk User)</th>
                        <th>Email Pribadi</th>
                        <th>Waktu Daftar</th>
                        <th>Status</th>
                        <th class="text-end pe-3" style="min-width:190px;">Aksi Superadmin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usersList)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <div class="rounded-0 bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;font-size:28px;">
                                        <i class="fas <?= $tab === 'pending' ? 'fa-check-double text-success' : 'fa-inbox text-secondary' ?>"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark">
                                        <?= $tab === 'pending' ? 'Tidak Ada Akun yang Menunggu Peninjauan' : 'Tidak Ditemukan Data User' ?>
                                    </h6>
                                    <p class="small text-muted mb-0">
                                        <?= $tab === 'pending' ? 'Semua pendaftaran akun baru telah ditinjau dan diproses oleh Superadmin.' : 'Silakan gunakan kata kunci lain atau pilih tab filter yang berbeda.' ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usersList as $idx => $u):
                            $uStatus         = $u['status'] ?? 'approved';
                            $uRole           = strtolower($u['role'] ?? 'user');
                            $isAdminRole     = in_array($uRole, ['superadmin', 'admin'], true);
                            $isSelf          = ((int)$u['id'] === (int)$user['id']);
                            $userAllowedDepts = get_user_allowed_departments($u);
                            $hasAllDepts     = in_array('ALL', $userAllowedDepts, true);
                        ?>
                            <tr class="<?= $uStatus === 'pending' ? 'table-warning' : '' ?>" style="<?= $uStatus === 'pending' ? 'background-color: #fffdf5;' : '' ?>">
                                <td class="ps-3 fw-bold text-muted small"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="sidebar-avatar rounded-0 text-white fw-bold d-flex align-items-center justify-content-center"
                                             style="width:36px;height:36px;font-size:0.8rem;background: <?= $isAdminRole ? '#2b579a' : '#64748b' ?>;">
                                            <?= htmlspecialchars($u['kode_singkatan']) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                            <?php if ($isSelf): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.68rem;">Akun Anda Saat Ini</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-dark px-2 py-1 font-monospace fs-6">
                                        <?= htmlspecialchars($u['kode_singkatan']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="<?= base_url('?page=user_approvals&tab=' . urlencode($tab)) ?>" class="d-inline-block">
                                        @csrf
                                        <input type="hidden" name="action" value="set_role">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <select name="new_role"
                                                class="form-select form-select-sm fw-bold rounded-0 <?= $isAdminRole ? 'border-primary text-primary bg-primary-subtle' : 'border-secondary text-dark bg-light' ?>"
                                                style="min-width:120px;font-size:0.76rem;cursor:pointer;"
                                                onchange="this.form.submit()"
                                                <?= $isSelf ? 'title="Role akun Anda sendiri"' : 'title="Ubah Role antara USER atau ADMIN"' ?>>
                                            <option value="user" <?= !$isAdminRole ? 'selected' : '' ?>>USER</option>
                                            <option value="admin" <?= $isAdminRole ? 'selected' : '' ?>>ADMIN</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <?php if ($isAdminRole): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size:0.74rem;">
                                            <i class="fas fa-unlock-alt me-1"></i> Semua Departemen (Akses Penuh Admin)
                                        </span>
                                    <?php else: ?>
                                        <form method="POST" action="<?= base_url('?page=user_approvals&tab=' . urlencode($tab)) ?>" class="m-0">
                                            @csrf
                                            <input type="hidden" name="action" value="update_departments">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <?php foreach (($allDepartments ?? []) as $deptItem):
                                                    $dCode = strtoupper(trim((string)($deptItem['kode_dept'] ?? '')));
                                                    if ($dCode === '') continue;
                                                    $isChecked = $hasAllDepts || in_array($dCode, $userAllowedDepts, true);
                                                    $chkId = 'dept_chk_' . (int)$u['id'] . '_' . $dCode;
                                                ?>
                                                    <label for="<?= $chkId ?>"
                                                           class="d-inline-flex align-items-center gap-1 px-2 py-1 border small fw-bold user-select-none"
                                                           style="cursor:pointer;font-size:0.72rem;background:<?= $isChecked ? '#eff6ff' : '#f8fafc' ?>;color:<?= $isChecked ? '#1e40af' : '#64748b' ?>;border-color:<?= $isChecked ? '#93c5fd' : '#e2e8f0' ?> !important;"
                                                           title="<?= htmlspecialchars($deptItem['nama_dept'] ?? $dCode) ?>">
                                                        <input type="checkbox"
                                                               id="<?= $chkId ?>"
                                                               name="departments[]"
                                                               value="<?= htmlspecialchars($dCode) ?>"
                                                               class="form-check-input m-0"
                                                               style="width:13px;height:13px;cursor:pointer;"
                                                               onchange="this.form.submit()"
                                                               <?= $isChecked ? 'checked' : '' ?>>
                                                        <span><?= htmlspecialchars($dCode) ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small text-dark">
                                        <i class="fas fa-envelope text-muted me-1"></i><?= htmlspecialchars($u['email']) ?>
                                    </div>
                                </td>
                                <td class="small text-muted">
                                    <i class="far fa-calendar-alt me-1"></i><?= !empty($u['created_at']) ? strtoupper(date('d M Y H:i', strtotime($u['created_at']))) : '-' ?>
                                </td>
                                <td>
                                    <?php if ($uStatus === 'pending'): ?>
                                        <span class="badge bg-warning text-dark px-2 py-1 d-inline-flex align-items-center gap-1 shadow-sm">
                                            <i class="fas fa-hourglass-half"></i> Menunggu ACC
                                        </span>
                                    <?php elseif ($uStatus === 'approved'): ?>
                                        <span class="badge bg-success px-2 py-1 d-inline-flex align-items-center gap-1">
                                            <i class="fas fa-check-circle"></i> Disetujui
                                        </span>
                                    <?php elseif ($uStatus === 'rejected'): ?>
                                        <span class="badge bg-danger px-2 py-1 d-inline-flex align-items-center gap-1">
                                            <i class="fas fa-times-circle"></i> Ditolak
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <?php if ($uStatus === 'pending'): ?>
                                            <form method="POST" action="<?= base_url('?page=user_approvals&tab=' . urlencode($tab)) ?>" class="d-inline" onsubmit="return confirm('Setujui akun <?= htmlspecialchars($u['nama_lengkap']) ?> (<?= htmlspecialchars($u['kode_singkatan']) ?>)? User akan langsung dapat login.');">
                                                @csrf
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-success btn-sm fw-bold px-2 py-1 shadow-sm">
                                                    <i class="fas fa-check me-1"></i> ACC Akun
                                                </button>
                                            </form>

                                            <form method="POST" action="<?= base_url('?page=user_approvals&tab=' . urlencode($tab)) ?>" class="d-inline" onsubmit="return confirm('Tolak pendaftaran akun <?= htmlspecialchars($u['nama_lengkap']) ?> (<?= htmlspecialchars($u['kode_singkatan']) ?>)?');">
                                                @csrf
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm fw-bold px-2 py-1 shadow-sm">
                                                    <i class="fas fa-times me-1"></i> Tolak
                                                </button>
                                            </form>
                                        <?php elseif ($uStatus === 'approved'): ?>
                                            <form method="POST" action="<?= base_url('?page=user_approvals&tab=' . urlencode($tab)) ?>" class="d-inline" onsubmit="return confirm('Nonaktifkan / tolak akun <?= htmlspecialchars($u['nama_lengkap']) ?>? User tidak akan dapat login lagi.');">
                                                @csrf
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1" title="Tolak / Nonaktifkan">
                                                    <i class="fas fa-ban me-1"></i> Nonaktifkan
                                                </button>
                                            </form>
                                        <?php elseif ($uStatus === 'rejected'): ?>
                                            <form method="POST" action="<?= base_url('?page=user_approvals&tab=' . urlencode($tab)) ?>" class="d-inline" onsubmit="return confirm('Setujui kembali akun <?= htmlspecialchars($u['nama_lengkap']) ?>?');">
                                                @csrf
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-outline-success btn-sm fw-semibold px-2 py-1">
                                                    <i class="fas fa-redo me-1"></i> ACC Ulang
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (!$isSelf): ?>
                                            <form method="POST" action="<?= base_url('?page=user_approvals&tab=' . urlencode($tab)) ?>" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus permanen user <?= htmlspecialchars($u['nama_lengkap']) ?> (<?= htmlspecialchars($u['kode_singkatan']) ?>)? Tindakan ini tidak dapat dibatalkan.');">
                                                @csrf
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-light btn-sm text-danger border px-2 py-1" title="Hapus Permanen">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
