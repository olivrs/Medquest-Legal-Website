@extends('layouts.app')

@section('content')
<div class="mq-detail-fullpage">
    <div class="cp-header-row mb-3 no-print">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $backUrl ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            <h1 class="cp-page-title ms-1">
                <?= htmlspecialchars($typeMeta['title']) ?> (#<?= htmlspecialchars((string) ($agr['no_urut_dokumen'] ?: $agr['id'])) ?>)
            </h1>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="window.print()" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <a href="<?= base_url('?page=edit&id=' . $agr['id']) ?>" class="btn btn-sm btn-primary fw-semibold px-3 py-1">
                <i class="fas fa-edit me-1"></i> Edit Data
            </a>
        </div>
    </div>

    <div class="row g-3 flex-grow-1 align-items-stretch">
        <div class="col-lg-8 d-flex flex-column">
            <div class="cp-table-card mt-0 flex-grow-1 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <div>
                        <span class="badge mq-dept-badge px-2 py-1 me-2">
                            DEPT: <?= htmlspecialchars($agr['department']) ?>
                        </span>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($typeMeta['title']) ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge" style="background:<?= $statusMeta['badge_bg'] ?>;color:<?= $statusMeta['badge_color'] ?>;border:1px solid <?= $statusMeta['badge_border'] ?>;font-size:0.75rem;">
                            <i class="<?= $statusMeta['icon_static'] ?> me-1"></i> <?= htmlspecialchars($statusMeta['label']) ?>
                        </span>
                        <span class="expiry-pill <?= $expInfo['badge_class'] ?>">
                            <?= htmlspecialchars($cleanLabel) ?>
                        </span>
                    </div>
                </div>

                <div class="p-2 mb-3 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                    <div class="d-flex align-items-center gap-1">
                        <span class="small fw-bold text-secondary me-1">Pilihan Status:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" onclick="openDetailChangeStatus('PENGAJUAN')" class="btn btn-sm <?= ($agr['status_proses'] ?? 'PENGAJUAN') === 'PENGAJUAN' ? 'btn-warning text-dark fw-bold active shadow-sm' : 'btn-outline-secondary bg-white' ?>" style="font-size:0.73rem;">
                                <i class="fas fa-file-signature me-1"></i> Pengajuan
                            </button>

                            <button type="button" onclick="openDetailChangeStatus('BERJALAN')" class="btn btn-sm <?= ($agr['status_proses'] ?? '') === 'BERJALAN' ? 'btn-primary text-white fw-bold active shadow-sm' : 'btn-outline-secondary bg-white' ?>" style="font-size:0.73rem;">
                                <i class="fas fa-spinner fa-spin me-1"></i> Sedang Berjalan
                            </button>

                            <button type="button" class="btn btn-sm <?= ($agr['status_proses'] ?? '') === 'SELESAI' ? 'btn-success text-white fw-bold active shadow-sm' : 'btn-outline-secondary bg-white' ?>" style="font-size:0.73rem;" data-bs-toggle="modal" data-bs-target="#modalDetailSetSelesai">
                                <i class="fas fa-check-circle me-1"></i> Sudah Selesai
                            </button>
                        </div>
                    </div>

                    <?php if (strtoupper(trim((string)($agr['status_proses'] ?? ''))) === 'SELESAI'): ?>
                        <div class="text-success small fw-bold">
                            <i class="fas fa-calendar-check me-1"></i> Selesai Tanggal: <?= format_date_medquest($agr['tanggal_selesai']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-0">
                    <table class="table table-bordered mq-detail-table mb-0">
                        <tbody>
                            <tr class="mq-row-compact">
                                <th class="bg-light text-secondary" style="width:32%;">Jenis Dokumen</th>
                                <td>
                                    <span class="badge" style="background:<?= $typeMeta['badge_bg'] ?>;color:<?= $typeMeta['badge_color'] ?>;border:1px solid <?= $typeMeta['badge_border'] ?>;">
                                        <?= htmlspecialchars($typeMeta['title']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr class="mq-row-compact">
                                <th class="bg-light text-secondary">No. Urut Form (No.)</th>
                                <td class="fw-bold">#<?= htmlspecialchars((string) ($agr['no_urut_dokumen'] ?: $agr['id'])) ?></td>
                            </tr>
                            <tr class="mq-row-compact">
                                <th class="bg-light text-secondary">Department / Division</th>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($agr['department']) ?></td>
                            </tr>
                            <tr class="mq-row-compact">
                                <th class="bg-light text-secondary">Status Proses Alur</th>
                                <td>
                                    <span class="badge px-2 py-1" style="background:<?= $statusMeta['badge_bg'] ?>;color:<?= $statusMeta['badge_color'] ?>;border:1px solid <?= $statusMeta['badge_border'] ?>;font-size:0.75rem;">
                                        <i class="<?= $statusMeta['icon_static'] ?> me-1"></i><?= htmlspecialchars($statusMeta['label']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if (strtoupper(trim((string)($agr['status_proses'] ?? ''))) === 'SELESAI'): ?>
                            <tr class="mq-row-compact">
                                <th class="bg-light text-success fw-bold">Selesai Tanggal</th>
                                <td class="fw-bold text-success font-monospace" style="font-size:0.86rem;">
                                    <i class="fas fa-calendar-check me-1"></i><?= format_date_medquest($agr['tanggal_selesai']) ?>
                                </td>
                            </tr>
                            <?php endif; ?>

                            <?php if ($docType === 'COMPANY'): ?>
                                <tr>
                                    <th class="bg-light text-secondary">Document Title</th>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($agr['document_title'] ?: $agr['product_description']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-secondary">Document No.</th>
                                    <td class="fw-semibold" style="white-space:pre-line;"><?= htmlspecialchars($agr['document_number'] ?: 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-secondary">Issued By</th>
                                    <td class="fw-semibold"><?= htmlspecialchars($agr['issued_by'] ?: $agr['customer']) ?></td>
                                </tr>
                            <?php elseif ($docType === 'PRINCIPAL'): ?>
                                <tr>
                                    <th class="bg-light text-secondary">Document Title</th>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($agr['document_title'] ?: $agr['product_description']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-secondary">Document No.</th>
                                    <td class="fw-semibold" style="white-space:pre-line;"><?= htmlspecialchars($agr['document_number'] ?: 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-secondary">Issued By</th>
                                    <td class="fw-semibold"><?= htmlspecialchars($agr['issued_by'] ?: '-') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-secondary">Principal</th>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($agr['principal_name'] ?: $agr['customer']) ?></td>
                                </tr>
                            <?php else: ?>
                                <tr class="mq-row-compact">
                                    <th class="bg-light text-secondary">Document Number</th>
                                    <td class="fw-semibold" style="white-space:pre-line;"><?= htmlspecialchars($agr['document_number']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-secondary">Customer</th>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($agr['customer']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-secondary">Product (Description)</th>
                                    <td><?= htmlspecialchars($agr['product_description']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-secondary">Product (Serial Number)</th>
                                    <td><?= htmlspecialchars($agr['serial_number'] ?: 'N/A') ?></td>
                                </tr>
                            <?php endif; ?>

                            <tr>
                                <th class="bg-light text-secondary">Effective Date</th>
                                <td class="fw-semibold"><?= $effFormatted ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-danger">Expiry Date</th>
                                <td class="fw-bold <?= $isExpNa ? 'text-secondary' : 'text-danger' ?>"><?= $expFormatted ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-secondary">Note</th>
                                <td style="white-space:pre-line;"><?= htmlspecialchars($agr['note'] ?: 'N/A') ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-secondary"><?= $docType === 'PRINCIPAL' ? 'Created By / Update' : 'Created By' ?></th>
                                <td class="fw-bold font-monospace" style="white-space:pre-line;"><?= htmlspecialchars($agr['created_by_log']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-3">
            <div class="cp-table-card mt-0">
                <div class="fw-bold text-dark border-bottom pb-2 mb-3" style="font-size:0.84rem;">
                    <i class="fas fa-paperclip text-primary me-1"></i> File Lampiran Dokumen
                </div>

                <?php if (!empty($agr['file_attachment'])):
                    $fileUrl = base_url('assets/uploads/' . $agr['file_attachment']);
                    $ext = strtolower(pathinfo($agr['file_attachment'], PATHINFO_EXTENSION));
                ?>
                    <div class="p-2 rounded-0 bg-light border mb-2 d-flex justify-content-between align-items-center">
                        <span class="small text-truncate me-2"><i class="far fa-file-pdf text-danger me-1"></i><?= htmlspecialchars($agr['file_attachment']) ?></span>
                        <a href="<?= $fileUrl ?>" target="_blank" class="btn btn-sm btn-primary py-0 px-2" style="font-size:0.72rem;">
                            Buka File
                        </a>
                    </div>
                    <?php if (in_array($ext, ['jpg', 'jpeg', 'png'], true)): ?>
                        <img src="<?= $fileUrl ?>" alt="Lampiran" class="img-fluid rounded-0 border mb-2">
                    <?php elseif ($ext === 'pdf'): ?>
                        <iframe src="<?= $fileUrl ?>" style="width:100%;height:210px;border:1px solid #e2e8f0;border-radius: 0;" class="mb-2"></iframe>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="mq-empty-upload-box mb-3">
                        <i class="fas fa-file-upload text-primary opacity-50 mb-1" style="font-size:1.35rem;"></i>
                        <div class="text-muted small">Belum ada file scan/dokumen yang diunggah.</div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url('?page=detail&id=' . $id) ?>" enctype="multipart/form-data" class="no-print">
                    @csrf
                    <input type="hidden" name="action" value="upload_file">
                    <div class="input-group input-group-sm">
                        <input type="file" name="file_dokumen" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required>
                        <button type="submit" class="btn btn-outline-primary fw-semibold">Upload</button>
                    </div>
                    <div class="text-muted mt-1" style="font-size:0.7rem;">Mendukung PDF, JPG, PNG, Word, Excel</div>
                </form>
            </div>

            <div class="cp-table-card mt-0 flex-grow-1 d-flex flex-column">
                <div class="fw-bold text-dark border-bottom pb-2 mb-3" style="font-size:0.84rem;">
                    <i class="fas fa-history text-primary me-1"></i> Riwayat Log Input (<?= htmlspecialchars($uCode) ?>)
                </div>
                <?php if (empty($histories)): ?>
                    <div class="text-muted small">Belum ada riwayat tercatat.</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2 overflow-auto" style="max-height:360px;">
                        <?php foreach ($histories as $h): ?>
                            <div class="p-2 px-3 rounded-0 bg-light border" style="font-size:0.76rem;">
                                <div class="d-flex justify-content-between align-items-center fw-bold text-dark">
                                    <span><?= htmlspecialchars($h['kode_singkatan']) ?>, <?= format_date_medquest($h['tanggal_input']) ?></span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($h['aksi']) ?></span>
                                </div>
                                <div class="text-secondary mt-1"><?= nl2br(htmlspecialchars($h['catatan'] ?: 'N/A')) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade no-print" id="modalDetailSetSelesai" tabindex="-1" aria-labelledby="modalDetailSetSelesaiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= base_url('?page=detail&id=' . $id) ?>" class="modal-content rounded-0 border-0 shadow">
            @csrf
            <input type="hidden" name="action" value="update_process_status">
            <input type="hidden" name="status_proses" value="SELESAI">

            <div class="modal-header bg-success text-white py-2 px-3">
                <h6 class="modal-title fw-bold" id="modalDetailSetSelesaiLabel">
                    <i class="fas fa-check-circle me-1"></i> Tandai Dokumen Sudah Selesai
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-light border mb-3 p-2 small">
                    <div class="text-muted small">Dokumen:</div>
                    <strong class="text-dark">#<?= htmlspecialchars((string) ($agr['no_urut_dokumen'] ?: $agr['id'])) ?> &mdash; <?= htmlspecialchars($agr['customer'] ?: ($agr['document_title'] ?? 'N/A')) ?></strong>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">
                        <i class="fas fa-calendar-alt text-success me-1"></i> Selesai Tanggal Berapa <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="tanggal_selesai" class="form-control" value="<?= htmlspecialchars($agr['tanggal_selesai'] ?: date('Y-m-d')) ?>" required>
                    <div class="form-text small text-muted">Tanggal resmi saat proses dokumen ini selesai diproses/diarsipkan.</div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold text-dark">Catatan Tambahan (Opsional)</label>
                    <textarea name="catatan_status" class="form-control" rows="2" placeholder="Contoh: Seluruh paraf dan tanda tangan telah lengkap"></textarea>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-success fw-bold px-3">
                    <i class="fas fa-check me-1"></i> Simpan Status Selesai
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ganti Status Proses di Halaman Detail -->
<div class="modal fade no-print" id="modalDetailChangeProcessStatus" tabindex="-1" aria-labelledby="modalDetailChangeProcessStatusLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= base_url('?page=detail&id=' . $id) ?>" class="modal-content rounded-0 border-0 shadow">
            @csrf
            <input type="hidden" name="action" value="update_process_status">
            <input type="hidden" name="status_proses" id="modalDetailStatusVal" value="">

            <div class="modal-header text-white py-2 px-3" id="modalDetailStatusHeader">
                <h6 class="modal-title fw-bold" id="modalDetailStatusTitle">
                    <i id="modalDetailStatusIcon" class="fas fa-play-circle me-1"></i> <span id="modalDetailStatusHeaderLabel">Ubah Status Proses</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-light border mb-3 p-2 small">
                    <div class="text-muted small">Dokumen:</div>
                    <strong class="text-dark">#<?= htmlspecialchars((string) ($agr['no_urut_dokumen'] ?: $agr['id'])) ?> &mdash; <?= htmlspecialchars($agr['customer'] ?: ($agr['document_title'] ?? 'N/A')) ?></strong>
                </div>

                <p class="mb-3 small text-secondary">
                    Apakah Anda yakin ingin mengubah status dokumen ini menjadi <strong id="modalDetailTargetLabel"></strong>?
                </p>

                <div class="mb-1">
                    <label class="form-label small fw-bold text-dark">Catatan Perubahan (Opsional)</label>
                    <textarea name="catatan_status" class="form-control" rows="2" placeholder="Contoh: Dokumen mulai direview dan diproses"></textarea>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm fw-bold px-3" id="modalDetailStatusSubmitBtn">
                    <i class="fas fa-check me-1"></i> Ya, Ubah Status
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDetailChangeStatus(status) {
    document.getElementById('modalDetailStatusVal').value = status;
    const headerEl = document.getElementById('modalDetailStatusHeader');
    const titleEl = document.getElementById('modalDetailStatusHeaderLabel');
    const iconEl = document.getElementById('modalDetailStatusIcon');
    const targetEl = document.getElementById('modalDetailTargetLabel');
    const submitBtn = document.getElementById('modalDetailStatusSubmitBtn');

    if (status === 'BERJALAN') {
        headerEl.className = 'modal-header bg-primary text-white py-2 px-3';
        titleEl.textContent = 'Ubah Status: Sedang Berjalan';
        iconEl.className = 'fas fa-spinner fa-spin me-1';
        targetEl.textContent = 'Sedang Berjalan';
        targetEl.style.color = '#1d4ed8';
        submitBtn.className = 'btn btn-sm btn-primary fw-bold px-3';
        submitBtn.innerHTML = '<i class="fas fa-play me-1"></i> Ya, Ubah Menjadi Sedang Berjalan';
    } else {
        headerEl.className = 'modal-header bg-warning text-dark py-2 px-3';
        titleEl.textContent = 'Ubah Status: Pengajuan';
        iconEl.className = 'fas fa-file-signature me-1';
        targetEl.textContent = 'Pengajuan';
        targetEl.style.color = '#b45309';
        submitBtn.className = 'btn btn-sm btn-warning text-dark fw-bold px-3';
        submitBtn.innerHTML = '<i class="fas fa-check me-1"></i> Ya, Ubah Menjadi Pengajuan';
    }

    const modalEl = document.getElementById('modalDetailChangeProcessStatus');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}
</script>
@endsection
