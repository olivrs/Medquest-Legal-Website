@extends('layouts.app')

@section('content')
<div class="auth-wrapper">
    <div class="auth-card" style="max-width:560px;">
        <div class="auth-card-header">
            <div class="d-inline-block mb-2">
                <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest" style="height:34px;">
            </div>
            <h4 class="fw-bold mb-1">PENDAFTARAN AKUN USER MEDQUEST</h4>
            <div class="small opacity-85">Wajib mencantumkan Email Pribadi &amp; Singkatan Nama Medquest (Contoh: RYN / FJR)</div>
        </div>

        <div class="p-4 p-md-4">
            <form method="POST" action="<?= base_url('?page=register') ?>">
                @csrf
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user text-primary"></i></span>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Ryan" value="<?= htmlspecialchars(request('nama_lengkap', '')) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Kode Singkatan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-id-badge text-primary"></i></span>
                            <input type="text" name="kode_singkatan" id="inputSingkatan" class="form-control text-uppercase fw-bold" placeholder="CONTOH: RYN" maxlength="10" value="<?= htmlspecialchars(request('kode_singkatan', '')) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Departemen <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-building text-primary"></i></span>
                            <select name="departemen" class="form-select fw-semibold" required>
                                <option value="">-- Pilih --</option>
                                <option value="ALL" <?= (request('departemen', '') === 'ALL') ? 'selected' : '' ?>>ALL (Semua)</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept['kode_dept']) ?>" <?= (request('departemen', '') === $dept['kode_dept']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['kode_dept']) ?> - <?= htmlspecialchars($dept['nama_dept']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Email Pribadi <span class="text-secondary fw-normal small">(Untuk Notifikasi Pengingat)</span> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-primary"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="contoh: ryan@gmail.com" value="<?= htmlspecialchars(request('email', '')) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-primary"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ulangi Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-shield-alt text-primary"></i></span>
                            <input type="password" name="password_confirm" class="form-control" placeholder="Ketik ulang password" required>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-2 px-3 rounded-0 bg-light border small text-muted">
                    <i class="fas fa-info-circle text-primary me-1"></i> Setelah mendaftar, akun akan ditinjau oleh <strong>Superadmin Medquest</strong> terlebih dahulu sebelum dapat digunakan untuk login.
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mt-3 shadow-sm">
                    <i class="fas fa-user-plus me-1"></i> Daftarkan Akun
                </button>
            </form>

            <div class="text-center mt-3">
                <span class="small text-muted">Sudah memiliki akun yang disetujui?</span>
                <a href="<?= base_url('?page=login') ?>" class="small fw-bold text-decoration-none ms-1">Login di sini</a>
            </div>
        </div>
    </div>
</div>
@endsection
