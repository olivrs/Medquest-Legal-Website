@extends('layouts.app')

@section('content')
<div class="cp-header-row no-print">
    <div class="d-flex align-items-center gap-2">
        <?php if ($selectedDept !== 'ALL' || $selectedStatus !== 'ALL' || $selectedDocType !== 'ALL' || $searchQuery !== ''): ?>
            <a href="<?= base_url('?page=agreements') ?>" class="btn btn-sm btn-light border fw-semibold px-2 py-1" style="font-size:0.74rem;">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        <?php endif; ?>
        <h1 class="cp-page-title">
            Overview Dashboard <?= $selectedDept !== 'ALL' ? ('&mdash; ' . htmlspecialchars($selectedDept)) : '' ?>
        </h1>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <?php
        $isHFilterActive = in_array($selectedStatus, ['H7_3', 'H30_14', 'H90_60', 'DEADLINE_ALL'], true);
        $hFilterLabel = match ($selectedStatus) {
            'H7_3'   => 'Sisa 3 - 7 Hari',
            'H30_14' => 'Sisa 14 - 30 Hari',
            'H90_60' => 'Sisa 60 - 90 Hari',
            default  => 'Sisa Hari',
        };
        $baseFilterUrl = '?page=agreements&dept=' . urlencode($selectedDept) . '&doc_type=' . urlencode($selectedDocType);
        if ($selectedSort !== 'default') { $baseFilterUrl .= '&sort=' . urlencode($selectedSort); }
        if ($searchQuery !== '') { $baseFilterUrl .= '&q=' . urlencode($searchQuery); }

        $baseFilterUrlWithoutSort = '?page=agreements&dept=' . urlencode($selectedDept) . '&doc_type=' . urlencode($selectedDocType) . '&status=' . urlencode($selectedStatus);
        if ($searchQuery !== '') { $baseFilterUrlWithoutSort .= '&q=' . urlencode($searchQuery); }
        ?>
        <div class="cp-pill-group">
            <a href="<?= base_url($baseFilterUrl . '&status=ALL') ?>"
               class="cp-pill-link <?= $selectedStatus === 'ALL' ? 'active' : '' ?>">Semua</a>

            <div class="dropdown d-inline-block">
                <a href="#"
                   class="cp-pill-link d-inline-flex align-items-center gap-1 <?= $isHFilterActive ? 'active' : '' ?>"
                   data-bs-toggle="dropdown"
                   aria-expanded="false">
                    <span><?= $hFilterLabel ?></span>
                    <i class="fas fa-chevron-down" style="font-size:0.62rem;"></i>
                </a>
                <ul class="dropdown-menu shadow-sm border p-1 mt-1" style="min-width: 145px; border-radius: 0;">
                    <li>
                        <a class="dropdown-item rounded-0 small py-1 px-2 fw-semibold <?= $selectedStatus === 'H7_3' ? 'active' : '' ?>"
                           href="<?= base_url($baseFilterUrl . '&status=H7_3') ?>">
                            Sisa 3 - 7 Hari
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-0 small py-1 px-2 fw-semibold <?= $selectedStatus === 'H30_14' ? 'active' : '' ?>"
                           href="<?= base_url($baseFilterUrl . '&status=H30_14') ?>">
                            Sisa 14 - 30 Hari
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-0 small py-1 px-2 fw-semibold <?= $selectedStatus === 'H90_60' ? 'active' : '' ?>"
                           href="<?= base_url($baseFilterUrl . '&status=H90_60') ?>">
                            Sisa 60 - 90 Hari
                        </a>
                    </li>
                </ul>
            </div>

            <a href="<?= base_url($baseFilterUrl . '&status=EXPIRED') ?>"
               class="cp-pill-link <?= $selectedStatus === 'EXPIRED' ? 'active' : '' ?>">Expired</a>
        </div>

        <div class="cp-pill-group px-3 py-2 text-secondary small fw-semibold">
            <?= strtoupper(date('d M Y')) ?>
        </div>

        <?php if (is_superadmin()): ?>
        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold px-2 py-1" style="font-size:0.75rem;" data-bs-toggle="modal" data-bs-target="#modalAddDept" title="Tambah Departemen Baru (Khusus Superadmin)">
            + Dept
        </button>
        <?php endif; ?>

        <a href="<?= base_url('?page=create' . ($selectedDept !== 'ALL' ? '&dept=' . urlencode($selectedDept) : '')) ?>"
           class="btn btn-sm btn-primary fw-bold text-white px-3 py-1 d-inline-flex align-items-center gap-1"
           style="font-size:0.76rem;">
            <i class="fas fa-plus me-1"></i> Input Dokumen
        </a>
    </div>
</div>

<div class="row g-2 mb-2 no-print">
    <div class="col-6 col-xl-3">
        <a href="<?= base_url($baseFilterUrl . '&status=ALL') ?>" class="cp-stat-card">
            <div>
                <div class="cp-stat-title">Total Dokumen</div>
                <div class="cp-stat-value"><?= $statTotal ?></div>
                <div class="cp-stat-sublabel"><?= $selectedDept === 'ALL' ? 'Semua Dept' : htmlspecialchars($selectedDept) ?></div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#2b579a 0% 90%, #e2e8f0 90% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-file-contract" style="color:#2b579a;"></i></div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="<?= base_url($baseFilterUrl . '&status=ACTIVE') ?>" class="cp-stat-card">
            <div>
                <div class="cp-stat-title">Dokumen Berlaku</div>
                <div class="cp-stat-value"><?= $statSafe ?></div>
                <div class="cp-stat-sublabel"><?= $pctSafe ?>% Aktif</div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#10b981 0% <?= max(12, $pctSafe) ?>%, #e2e8f0 <?= max(12, $pctSafe) ?>% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-check-circle" style="color:#10b981;"></i></div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="<?= base_url($baseFilterUrl . '&status=DEADLINE_ALL') ?>" class="cp-stat-card">
            <div>
                <div class="cp-stat-title">Deadline</div>
                <div class="cp-stat-value"><?= $statDeadline ?></div>
                <div class="cp-stat-sublabel"><?= $statUrgent ?> Mendesak</div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#f59e0b 0% <?= max(15, $pctDeadline) ?>%, #e2e8f0 <?= max(15, $pctDeadline) ?>% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-clock" style="color:#f59e0b;"></i></div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="<?= base_url($baseFilterUrl . '&status=EXPIRED') ?>" class="cp-stat-card">
            <div>
                <div class="cp-stat-title">Sudah Expired</div>
                <div class="cp-stat-value"><?= $statExpired ?></div>
                <div class="cp-stat-sublabel"><?= $pctExpired ?>% Habis</div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#ef4444 0% <?= max(10, $pctExpired) ?>%, #e2e8f0 <?= max(10, $pctExpired) ?>% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-exclamation-circle" style="color:#ef4444;"></i></div>
            </div>
        </a>
    </div>
</div>

<?php
$activeTableTitle = match ($selectedDocType) {
    'COMPANY'     => 'ORIGINAL COMPANY DOCUMENT',
    'PRINCIPAL'   => 'LOG OF PRINCIPAL DOCUMENT',
    'COOPERATION' => 'LOG OF COOPERATION AGREEMENT',
    default       => 'DAFTAR DOKUMEN LEGAL MEDQUEST',
};
$typeFilterBase = '?page=agreements&dept=' . urlencode($selectedDept) . '&status=' . urlencode($selectedStatus);
if ($selectedSort !== 'default') { $typeFilterBase .= '&sort=' . urlencode($selectedSort); }
if ($searchQuery !== '') { $typeFilterBase .= '&q=' . urlencode($searchQuery); }
?>
<div class="cp-table-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h6 class="fw-bold text-dark mb-0 me-2">
                <?= htmlspecialchars($activeTableTitle) ?>
            </h6>
            <div class="cp-pill-group no-print">
                <a href="<?= base_url($typeFilterBase . '&doc_type=ALL') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'ALL' ? 'active' : '' ?>">SEMUA JENIS</a>
                <a href="<?= base_url($typeFilterBase . '&doc_type=COOPERATION') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'COOPERATION' ? 'active' : '' ?>">COOPERATION AGREEMENT</a>
                <a href="<?= base_url($typeFilterBase . '&doc_type=COMPANY') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'COMPANY' ? 'active' : '' ?>">ORIGINAL COMPANY DOC</a>
                <a href="<?= base_url($typeFilterBase . '&doc_type=PRINCIPAL') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'PRINCIPAL' ? 'active' : '' ?>">PRINCIPAL DOCUMENT</a>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-3 small no-print">
            <button type="button"
                    id="btnBulkDelete"
                    class="btn btn-sm btn-danger fw-bold px-3 py-1 d-none align-items-center gap-1 shadow-sm"
                    style="font-size:0.75rem;border-radius:0;"
                    onclick="submitBulkDelete()">
                <i class="fas fa-trash-alt me-1"></i> Hapus Terpilih (<span id="selectedDocCount">0</span>)
            </button>
            <span><span class="d-inline-block rounded-0 me-1" style="width:8px;height:8px;background:#10b981;"></span> Aktif</span>
            <span><span class="d-inline-block rounded-0 me-1" style="width:8px;height:8px;background:#f59e0b;"></span> Akan Expired</span>
            <span><span class="d-inline-block rounded-0 me-1" style="width:8px;height:8px;background:#ef4444;"></span> Expired</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="cp-table mq-mobile-cards">
            <thead>
                <tr>
                    <th style="width:38px;" class="text-center no-print">
                        <input type="checkbox"
                               id="selectAllDocs"
                               class="form-check-input m-0"
                               style="width:15px;height:15px;cursor:pointer;"
                               title="Pilih Semua Dokumen di Halaman Ini">
                    </th>
                    <th style="width:90px;">Dept / No</th>
                    <?php if ($selectedDocType === 'COMPANY'): ?>
                        <th style="width:250px;">Document Title &amp; Document No.</th>
                        <th style="width:190px;">Issued By</th>
                    <?php elseif ($selectedDocType === 'PRINCIPAL'): ?>
                        <th style="width:240px;">Document Title &amp; Document No.</th>
                        <th style="width:200px;">Issued By &amp; Principal</th>
                    <?php elseif ($selectedDocType === 'COOPERATION'): ?>
                        <th style="width:230px;">Customer &amp; Document No</th>
                        <th style="width:195px;">Product &amp; S/N</th>
                    <?php else: ?>
                        <th style="width:240px;">Dokumen / Customer &amp; No</th>
                        <th style="width:200px;">Detail (Product / Issued By / Principal)</th>
                    <?php endif; ?>
                    <th style="min-width:275px;">
                        <div class="d-flex align-items-center justify-content-between gap-1">
                            <span>Effective, Expiry &amp; Deadline</span>
                            <div class="dropdown no-print d-inline-block">
                                <button class="btn btn-xs py-0 px-2 d-inline-flex align-items-center gap-1 dropdown-toggle <?= $selectedSort !== 'default' ? 'btn-primary text-white' : 'btn-outline-secondary bg-white text-dark' ?>"
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                        style="font-size:0.72rem; font-weight:600; text-transform:none; border-radius:0;">
                                    <span>Filter</span>
                                    <i class="fas fa-chevron-down" style="font-size:0.58rem;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border p-1 mt-1" style="min-width: 230px; border-radius: 0; font-size:0.76rem;">
                                    <li><h6 class="dropdown-header py-1 px-2 text-uppercase text-muted" style="font-size:0.66rem;">Urutkan Dokumen:</h6></li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'newest' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=newest') ?>">
                                            <i class="fas fa-arrow-down-wide-short me-1"></i> Data Terbaru &gt; Data Lama
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'oldest' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=oldest') ?>">
                                            <i class="fas fa-arrow-up-wide-short me-1"></i> Data Paling Lama &gt; Data Paling Baru
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= ($selectedSort === 'default' || $selectedSort === '') ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=default') ?>">
                                            <i class="fas fa-sort-numeric-down me-1"></i> No. Urut Form (Default)
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'deadline_asc' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=deadline_asc') ?>">
                                            <i class="fas fa-clock text-danger me-1"></i> Deadline Paling Dekat
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'deadline_desc' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=deadline_desc') ?>">
                                            <i class="fas fa-calendar-check text-success me-1"></i> Deadline Paling Jauh
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </th>
                    <th style="width:200px;">Note</th>
                    <th style="width:160px;">Created By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($agreements)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block opacity-50"></i>
                            <?php if (is_superadmin()): ?>
                                Belum ada dokumen yang ditemukan pada filter ini.
                            <?php else: ?>
                                Belum ada dokumen untuk akun <strong><?= htmlspecialchars($uCode) ?></strong> atau departemen Anda pada filter ini.
                            <?php endif; ?>
                            <div class="mt-3">
                                <a href="<?= base_url('?page=create' . ($selectedDocType !== 'ALL' ? '&doc_type=' . urlencode($selectedDocType) : '') . ($selectedDept !== 'ALL' ? '&dept=' . urlencode($selectedDept) : '')) ?>" class="btn btn-sm btn-primary fw-bold px-3 py-2">
                                    <i class="fas fa-plus-circle me-1"></i> + Input Dokumen Baru
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($agreements as $row):
                        $rowType = strtoupper(trim($row['doc_type'] ?? 'COOPERATION'));
                        $typeMeta = get_doc_type_meta($rowType);
                        $isExpNa = !empty($row['expiry_date_na']) || empty($row['expiry_date']);
                        $expInfo = get_expiry_info($row['expiry_date'], $isExpNa);
                        $effFormatted = format_date_medquest($row['effective_date'], $row['effective_date_na']);
                        $expFormatted = format_date_medquest($row['expiry_date'], $isExpNa);
                        $displayTitle = ($rowType === 'COOPERATION')
                            ? ($row['customer'] ?: '-')
                            : ($row['document_title'] ?: $row['product_description'] ?: '-');
                    ?>
                        <tr class="<?= $expInfo['row_class'] ?>">
                            <td class="text-center no-print mc-check" style="vertical-align:middle;">
                                <input type="checkbox"
                                       class="form-check-input doc-row-checkbox m-0"
                                       value="<?= (int)$row['id'] ?>"
                                       style="width:15px;height:15px;cursor:pointer;"
                                       title="Centang untuk pilih dokumen <?= htmlspecialchars($displayTitle) ?>">
                            </td>
                            <td class="mc-dept">
                                <a href="<?= base_url('?page=agreements&dept=' . urlencode($row['department'])) ?>"
                                   class="badge mq-dept-badge text-decoration-none px-2 py-1 mb-1 d-inline-block" style="font-size:0.74rem;">
                                    <?= htmlspecialchars($row['department']) ?>
                                </a>
                                <div class="text-muted small fw-semibold">#<?= htmlspecialchars((string) ($row['no_urut_dokumen'] ?: $row['id'])) ?></div>
                                <?php if ($selectedDocType === 'ALL'): ?>
                                    <span class="badge mt-1" style="background:<?= $typeMeta['badge_bg'] ?>;color:<?= $typeMeta['badge_color'] ?>;border:1px solid <?= $typeMeta['badge_border'] ?>;font-size:0.62rem;">
                                        <?= htmlspecialchars($typeMeta['short_title']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="mc-title">
                                <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($displayTitle) ?></div>
                                <div class="text-secondary small" style="white-space:pre-line;font-size:0.76rem;">
                                    <span class="text-muted">No:</span> <?= htmlspecialchars($row['document_number'] ?: 'N/A') ?>
                                </div>
                            </td>

                            <td>
                                <?php if ($rowType === 'COMPANY'): ?>
                                    <div class="small text-muted">Issued By:</div>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($row['issued_by'] ?: $row['customer'] ?: '-') ?></div>
                                <?php elseif ($rowType === 'PRINCIPAL'): ?>
                                    <div class="mb-1">
                                        <span class="text-muted small">Principal:</span>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['principal_name'] ?: $row['customer'] ?: '-') ?></div>
                                    </div>
                                    <div class="small text-secondary">
                                        <span class="text-muted">Issued By:</span> <?= htmlspecialchars($row['issued_by'] ?: '-') ?>
                                    </div>
                                <?php else: ?>
                                    <div class="fw-semibold text-dark mb-1"><?= htmlspecialchars($row['product_description']) ?></div>
                                    <span class="badge bg-light text-secondary border" style="font-size:0.7rem;">
                                        S/N: <?= htmlspecialchars($row['serial_number'] ?: 'N/A') ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="mc-dates" data-label="Masa Berlaku">
                                <div class="small mb-1">
                                    <span class="text-muted">Effective:</span> <strong><?= $effFormatted ?></strong>
                                    <span class="mx-1 text-muted">&rarr;</span>
                                    <span class="<?= $isExpNa ? 'text-secondary' : 'text-danger' ?> fw-bold">Expiry: <?= $expFormatted ?></span>
                                </div>
                                <div>
                                    <span class="expiry-pill <?= $expInfo['badge_class'] ?>">
                                        <?= htmlspecialchars($expInfo['short_label']) ?>
                                    </span>
                                </div>
                            </td>

                            <td class="mc-note" data-label="Note">
                                <div class="small text-secondary mb-2" style="white-space:pre-line;max-height:85px;overflow-y:auto;"><?= htmlspecialchars($row['note'] ?: 'N/A') ?></div>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary py-0 px-2 no-print"
                                        style="font-size:0.72rem;font-weight:600;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalAddNote"
                                        data-id="<?= $row['id'] ?>"
                                        data-customer="<?= htmlspecialchars($displayTitle) ?>"
                                        data-docno="<?= htmlspecialchars($row['document_number']) ?>">
                                    <i class="fas fa-plus me-1"></i> + Note
                                </button>
                            </td>

                            <td class="mc-created" data-label="Created By">
                                <div class="fw-bold text-dark font-monospace small mb-2" style="white-space:pre-line;"><?= htmlspecialchars($row['created_by_log']) ?></div>
                                <div class="d-flex gap-1 no-print">
                                    <a href="<?= base_url('?page=detail&id=' . $row['id']) ?>" class="btn btn-sm btn-light border py-0 px-2 small text-primary" title="Inspect / Lihat Detail Dokumen">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('?page=edit&id=' . $row['id']) ?>" class="btn btn-sm btn-light border py-0 px-2 small" title="Edit">
                                        <i class="fas fa-edit text-secondary"></i>
                                    </a>
                                    <form method="POST" action="<?= base_url('?page=agreements') ?>" class="d-inline" onsubmit="return confirm('Hapus dokumen <?= htmlspecialchars(addslashes($displayTitle)) ?>?')">
                                        @csrf
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="agreement_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="return_dept" value="<?= htmlspecialchars($selectedDept) ?>">
                                        <button type="submit" class="btn btn-sm btn-light border py-0 px-2 small text-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between p-3 border-top bg-light no-print">
        <div class="small text-muted fw-semibold mb-2 mb-md-0">
            <?php if ($statTotal > 0): ?>
                Menampilkan <span class="text-dark fw-bold"><?= $offset + 1 ?> &ndash; <?= min($offset + $perPage, $statTotal) ?></span> dari total <span class="text-dark fw-bold"><?= $statTotal ?></span> dokumen
                <span class="ms-1 text-secondary">(Halaman <?= $currentPage ?> dari <?= $totalPages ?>)</span>
            <?php else: ?>
                Tidak ada dokumen yang ditampilkan
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
            $pageUrlHelper = function($p) use ($selectedDept, $selectedDocType, $selectedStatus, $searchQuery, $selectedSort) {
                $q = [
                    'page'     => 'agreements',
                    'dept'     => $selectedDept,
                    'doc_type' => $selectedDocType,
                    'status'   => $selectedStatus,
                    'p'        => $p
                ];
                if ($searchQuery !== '') $q['q'] = $searchQuery;
                if ($selectedSort !== 'default') $q['sort'] = $selectedSort;
                return base_url('?' . http_build_query($q));
            };
            ?>
            <nav aria-label="Navigasi Halaman Dokumen">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link rounded-0 fw-semibold" href="<?= $currentPage <= 1 ? '#' : $pageUrlHelper($currentPage - 1) ?>" tabindex="-1">
                            <i class="fas fa-chevron-left me-1"></i> Prev
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link rounded-0 fw-bold" href="<?= $pageUrlHelper($i) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link rounded-0 fw-semibold" href="<?= $currentPage >= $totalPages ? '#' : $pageUrlHelper($currentPage + 1) ?>">
                            Next <i class="fas fa-chevron-right ms-1"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade no-print" id="modalAddNote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= base_url('?page=agreements') ?>" class="modal-content rounded-0 border-0 shadow">
            @csrf
            <input type="hidden" name="action" value="add_note">
            <input type="hidden" name="agreement_id" id="noteModalAgrId">
            <input type="hidden" name="return_dept" value="<?= htmlspecialchars($selectedDept) ?>">

            <div class="modal-header mq-modal-header text-white">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-sticky-note me-2"></i> Tambah Note / Riwayat Tindak Lanjut
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="p-2 rounded-0 bg-light border mb-3 small">
                    <div class="fw-bold text-dark" id="noteModalCustomer"></div>
                    <div class="text-muted" id="noteModalDocNo"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Tanggal Catatan Baru <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_input" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Isi Catatan Tindak Lanjut <span class="text-danger">*</span></label>
                    <textarea name="catatan_baru" class="form-control" rows="3" placeholder="Contoh: Masih proses review oleh legal customer..." required></textarea>
                </div>

                <div class="mb-1">
                    <label class="form-label fw-semibold small">Perpanjang Tanggal Berakhir / Expiry Date Baru (Opsional)</label>
                    <input type="date" name="new_expiry_date" class="form-control">
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Note</button>
            </div>
        </form>
    </div>
</div>

<?php if (is_superadmin()): ?>
<div class="modal fade no-print" id="modalAddDept" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" action="<?= base_url('?page=agreements') ?>" class="modal-content rounded-0 border-0 shadow">
            @csrf
            <input type="hidden" name="action" value="add_department">
            <div class="modal-header mq-modal-header text-white">
                <h6 class="modal-title fw-bold">Tambah Departemen Baru</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Kode Singkatan Departemen <span class="text-danger">*</span></label>
                    <input type="text" name="kode_dept" class="form-control text-uppercase fw-bold" placeholder="Contoh: MKT / FIN" maxlength="15" required>
                </div>
                <div class="mb-1">
                    <label class="form-label small fw-semibold">Nama Lengkap Divisi <span class="text-danger">*</span></label>
                    <input type="text" name="nama_dept" class="form-control" placeholder="Contoh: Divisi Marketing" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100 fw-bold">Simpan Departemen</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<form id="bulkDeleteForm" method="POST" action="<?= base_url('?page=agreements') ?>" class="d-none">
    @csrf
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="return_dept" value="<?= htmlspecialchars($selectedDept) ?>">
    <input type="hidden" name="return_doc_type" value="<?= htmlspecialchars($selectedDocType) ?>">
    <input type="hidden" name="return_status" value="<?= htmlspecialchars($selectedStatus) ?>">
    <input type="hidden" name="return_sort" value="<?= htmlspecialchars($selectedSort) ?>">
    <div id="bulkDeleteInputsContainer"></div>
</form>

<script>
const modalAddNote = document.getElementById('modalAddNote');
if (modalAddNote) {
    modalAddNote.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('noteModalAgrId').value = button.getAttribute('data-id');
        document.getElementById('noteModalCustomer').textContent = button.getAttribute('data-customer');
        document.getElementById('noteModalDocNo').textContent = button.getAttribute('data-docno');
    });
}

(function () {
    const selectAllEl = document.getElementById('selectAllDocs');
    const rowCheckboxes = Array.from(document.querySelectorAll('.doc-row-checkbox'));
    const btnBulkDelete = document.getElementById('btnBulkDelete');
    const countSpan = document.getElementById('selectedDocCount');

    function updateBulkDeleteState() {
        const checkedBoxes = rowCheckboxes.filter(cb => cb.checked);
        const count = checkedBoxes.length;
        if (countSpan) {
            countSpan.textContent = String(count);
        }
        if (btnBulkDelete) {
            if (count > 0) {
                btnBulkDelete.classList.remove('d-none');
                btnBulkDelete.classList.add('d-inline-flex');
            } else {
                btnBulkDelete.classList.add('d-none');
                btnBulkDelete.classList.remove('d-inline-flex');
            }
        }
        if (selectAllEl && rowCheckboxes.length > 0) {
            selectAllEl.checked = (count === rowCheckboxes.length);
            selectAllEl.indeterminate = (count > 0 && count < rowCheckboxes.length);
        }
    }

    if (selectAllEl) {
        selectAllEl.addEventListener('change', function () {
            const isChecked = selectAllEl.checked;
            rowCheckboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            updateBulkDeleteState();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkDeleteState);
    });
})();

function submitBulkDelete() {
    const checkedBoxes = Array.from(document.querySelectorAll('.doc-row-checkbox:checked'));
    if (checkedBoxes.length === 0) {
        return;
    }
    if (!confirm('Yakin ingin menghapus ' + checkedBoxes.length + ' dokumen yang dicentang secara permanen?')) {
        return;
    }
    const container = document.getElementById('bulkDeleteInputsContainer');
    const form = document.getElementById('bulkDeleteForm');
    if (!container || !form) return;

    container.innerHTML = '';
    checkedBoxes.forEach(cb => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'agreement_ids[]';
        inp.value = cb.value;
        container.appendChild(inp);
    });

    form.submit();
}
</script>
@endsection
