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
                    <span class="expiry-pill <?= $expInfo['badge_class'] ?>">
                        <?= htmlspecialchars($cleanLabel) ?>
                    </span>
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
@endsection
