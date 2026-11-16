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
            Dashboard Overview <?= $selectedDept !== 'ALL' ? ('&mdash; ' . htmlspecialchars($selectedDept)) : '' ?>
        </h1>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <?php
        $isHFilterActive = in_array($selectedStatus, ['H7_3', 'H30_14', 'H90_60', 'DEADLINE_ALL'], true);
        $hFilterLabel = match ($selectedStatus) {
            'H7_3'   => '3 - 7 Days Left',
            'H30_14' => '14 - 30 Days Left',
            'H90_60' => '60 - 90 Days Left',
            default  => 'Days Remaining',
        };
        $baseFilterUrl = '?page=agreements&dept=' . urlencode($selectedDept) . '&doc_type=' . urlencode($selectedDocType) . '&proses=' . urlencode($selectedProses);
        if ($selectedSort !== 'default') { $baseFilterUrl .= '&sort=' . urlencode($selectedSort); }
        if ($searchQuery !== '') { $baseFilterUrl .= '&q=' . urlencode($searchQuery); }

        $baseFilterUrlWithoutSort = '?page=agreements&dept=' . urlencode($selectedDept) . '&doc_type=' . urlencode($selectedDocType) . '&status=' . urlencode($selectedStatus) . '&proses=' . urlencode($selectedProses);
        if ($searchQuery !== '') { $baseFilterUrlWithoutSort .= '&q=' . urlencode($searchQuery); }

        $prosesFilterBase = '?page=agreements&dept=' . urlencode($selectedDept) . '&doc_type=' . urlencode($selectedDocType) . '&status=' . urlencode($selectedStatus);
        if ($selectedSort !== 'default') { $prosesFilterBase .= '&sort=' . urlencode($selectedSort); }
        if ($searchQuery !== '') { $prosesFilterBase .= '&q=' . urlencode($searchQuery); }
        $typeFilterBase = '?page=agreements&dept=' . urlencode($selectedDept) . '&status=' . urlencode($selectedStatus) . '&proses=' . urlencode($selectedProses);
        if ($selectedSort !== 'default') { $typeFilterBase .= '&sort=' . urlencode($selectedSort); }
        if ($searchQuery !== '') { $typeFilterBase .= '&q=' . urlencode($searchQuery); }
        ?>
        <div class="cp-pill-group">
            <a href="<?= base_url($baseFilterUrl . '&status=ALL') ?>"
               class="cp-pill-link <?= $selectedStatus === 'ALL' ? 'active' : '' ?>">All</a>

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
                            3 - 7 Days Left
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-0 small py-1 px-2 fw-semibold <?= $selectedStatus === 'H30_14' ? 'active' : '' ?>"
                           href="<?= base_url($baseFilterUrl . '&status=H30_14') ?>">
                            14 - 30 Days Left
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-0 small py-1 px-2 fw-semibold <?= $selectedStatus === 'H90_60' ? 'active' : '' ?>"
                           href="<?= base_url($baseFilterUrl . '&status=H90_60') ?>">
                            60 - 90 Days Left
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
        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold px-2 py-1" style="font-size:0.75rem;" data-bs-toggle="modal" data-bs-target="#modalAddDept" title="Add New Department (Superadmin Only)">
            + Dept
        </button>
        <?php endif; ?>

        <a href="<?= base_url('?page=create' . ($selectedDept !== 'ALL' ? '&dept=' . urlencode($selectedDept) : '')) ?>"
           class="btn btn-sm btn-primary fw-bold text-white px-3 py-1 d-inline-flex align-items-center gap-1"
           style="font-size:0.76rem;">
            <i class="fas fa-plus me-1"></i> Add Document
        </a>
    </div>
</div>

<div class="row g-2 mb-2 no-print">
    <div class="col-6 col-xl-3">
        <a href="<?= base_url($prosesFilterBase . '&proses=ALL') ?>" class="cp-stat-card <?= $selectedProses === 'ALL' ? 'border-primary' : '' ?>">
            <div>
                <div class="cp-stat-title">Total Documents</div>
                <div class="cp-stat-value"><?= $countProsesAll ?></div>
                <div class="cp-stat-sublabel"><?= $selectedDept === 'ALL' ? 'All Depts' : htmlspecialchars($selectedDept) ?></div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#2b579a 0% 90%, #e2e8f0 90% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-file-contract" style="color:#2b579a;"></i></div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="<?= base_url($prosesFilterBase . '&proses=PENGAJUAN') ?>" class="cp-stat-card <?= $selectedProses === 'PENGAJUAN' ? 'border-warning shadow-sm' : '' ?>">
            <div>
                <div class="cp-stat-title" style="color:#b45309;">Submission</div>
                <div class="cp-stat-value" style="color:#b45309;"><?= $countPengajuan ?></div>
                <div class="cp-stat-sublabel"><?= $countProsesAll > 0 ? round(($countPengajuan / $countProsesAll) * 100) : 0 ?>% of Total</div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#f59e0b 0% <?= max(12, $countProsesAll > 0 ? round(($countPengajuan / $countProsesAll) * 100) : 0) ?>%, #e2e8f0 <?= max(12, $countProsesAll > 0 ? round(($countPengajuan / $countProsesAll) * 100) : 0) ?>% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-file-signature" style="color:#f59e0b;"></i></div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="<?= base_url($prosesFilterBase . '&proses=BERJALAN') ?>" class="cp-stat-card <?= $selectedProses === 'BERJALAN' ? 'border-primary shadow-sm' : '' ?>">
            <div>
                <div class="cp-stat-title text-primary">In Progress</div>
                <div class="cp-stat-value text-primary"><?= $countBerjalan ?></div>
                <div class="cp-stat-sublabel"><?= $countProsesAll > 0 ? round(($countBerjalan / $countProsesAll) * 100) : 0 ?>% of Total</div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#0284c7 0% <?= max(15, $countProsesAll > 0 ? round(($countBerjalan / $countProsesAll) * 100) : 0) ?>%, #e2e8f0 <?= max(15, $countProsesAll > 0 ? round(($countBerjalan / $countProsesAll) * 100) : 0) ?>% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-spinner fa-spin" style="color:#0284c7;"></i></div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3">
        <a href="<?= base_url($prosesFilterBase . '&proses=SELESAI') ?>" class="cp-stat-card <?= $selectedProses === 'SELESAI' ? 'border-success shadow-sm' : '' ?>">
            <div>
                <div class="cp-stat-title text-success">Completed</div>
                <div class="cp-stat-value text-success"><?= $countSelesai ?></div>
                <div class="cp-stat-sublabel"><?= $countProsesAll > 0 ? round(($countSelesai / $countProsesAll) * 100) : 0 ?>% of Total</div>
            </div>
            <div class="cp-circle-gauge" style="background: conic-gradient(#10b981 0% <?= max(10, $countProsesAll > 0 ? round(($countSelesai / $countProsesAll) * 100) : 0) ?>%, #e2e8f0 <?= max(10, $countProsesAll > 0 ? round(($countSelesai / $countProsesAll) * 100) : 0) ?>% 100%);">
                <div class="cp-circle-inner"><i class="fas fa-check-circle" style="color:#10b981;"></i></div>
            </div>
        </a>
    </div>
</div>

<?php
$prosesTitleSuffix = match ($selectedProses) {
    'PENGAJUAN' => ' &mdash; SUBMISSION STAGE',
    'BERJALAN'  => ' &mdash; IN PROGRESS',
    'SELESAI'   => ' &mdash; COMPLETED',
    default     => '',
};
$activeTableTitle = match ($selectedDocType) {
    'COMPANY'     => 'ORIGINAL COMPANY DOCUMENT' . $prosesTitleSuffix,
    'PRINCIPAL'   => 'LOG OF PRINCIPAL DOCUMENT' . $prosesTitleSuffix,
    'COOPERATION' => 'LOG OF COOPERATION AGREEMENT' . $prosesTitleSuffix,
    default       => 'MEDQUEST LEGAL DOCUMENTS LIST' . $prosesTitleSuffix,
};
?>
<div class="cp-table-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h6 class="fw-bold text-dark mb-0 me-2">
                <?= $activeTableTitle ?>
            </h6>
            <div class="cp-pill-group no-print">
                <a href="<?= base_url($typeFilterBase . '&doc_type=ALL') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'ALL' ? 'active' : '' ?>">ALL TYPES</a>
                <a href="<?= base_url($typeFilterBase . '&doc_type=COOPERATION') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'COOPERATION' ? 'active' : '' ?>">COOPERATION AGREEMENT</a>
                <a href="<?= base_url($typeFilterBase . '&doc_type=COMPANY') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'COMPANY' ? 'active' : '' ?>">ORIGINAL COMPANY DOC</a>
                <a href="<?= base_url($typeFilterBase . '&doc_type=PRINCIPAL') ?>"
                   class="cp-pill-link <?= $selectedDocType === 'PRINCIPAL' ? 'active' : '' ?>">PRINCIPAL DOCUMENT</a>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 no-print">
            <div class="cp-pill-group">
                <a href="<?= base_url($prosesFilterBase . '&proses=ALL') ?>"
                   class="cp-pill-link <?= $selectedProses === 'ALL' ? 'active' : '' ?>">All (<?= $countProsesAll ?>)</a>
                <a href="<?= base_url($prosesFilterBase . '&proses=PENGAJUAN') ?>"
                   class="cp-pill-link <?= $selectedProses === 'PENGAJUAN' ? 'active' : '' ?>" style="<?= $selectedProses === 'PENGAJUAN' ? 'background:#f59e0b;color:#fff;' : '' ?>">
                   <i class="fas fa-file-signature me-1"></i>Submission (<?= $countPengajuan ?>)
                </a>
                <a href="<?= base_url($prosesFilterBase . '&proses=BERJALAN') ?>"
                   class="cp-pill-link <?= $selectedProses === 'BERJALAN' ? 'active' : '' ?>" style="<?= $selectedProses === 'BERJALAN' ? 'background:#0284c7;color:#fff;' : '' ?>">
                   <i class="fas fa-spinner fa-spin me-1"></i>In Progress (<?= $countBerjalan ?>)
                </a>
                <a href="<?= base_url($prosesFilterBase . '&proses=SELESAI') ?>"
                   class="cp-pill-link <?= $selectedProses === 'SELESAI' ? 'active' : '' ?>" style="<?= $selectedProses === 'SELESAI' ? 'background:#10b981;color:#fff;' : '' ?>">
                   <i class="fas fa-check-circle me-1"></i>Completed (<?= $countSelesai ?>)
                </a>
            </div>

            <button type="button"
                    id="btnBulkDelete"
                    class="btn btn-sm btn-danger fw-bold px-3 py-1 d-none align-items-center gap-1 shadow-sm"
                    style="font-size:0.75rem;border-radius:0;"
                    onclick="submitBulkDelete()">
                <i class="fas fa-trash-alt me-1"></i> Delete Selected (<span id="selectedDocCount">0</span>)
            </button>
            <span><span class="d-inline-block rounded-0 me-1" style="width:8px;height:8px;background:#10b981;"></span> Active</span>
            <span><span class="d-inline-block rounded-0 me-1" style="width:8px;height:8px;background:#f59e0b;"></span> Expiring Soon</span>
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
                               title="Select All Documents on This Page">
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
                        <th style="width:240px;">Document No</th>
                        <th style="width:200px;">Detail</th>
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
                                    <li><h6 class="dropdown-header py-1 px-2 text-uppercase text-muted" style="font-size:0.66rem;">Sort Documents:</h6></li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'newest' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=newest') ?>">
                                            <i class="fas fa-arrow-down-wide-short me-1"></i> Newest &gt; Oldest
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'oldest' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=oldest') ?>">
                                            <i class="fas fa-arrow-up-wide-short me-1"></i> Oldest &gt; Newest
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= ($selectedSort === 'default' || $selectedSort === '') ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=default') ?>">
                                            <i class="fas fa-sort-numeric-down me-1"></i> Form Number (Default)
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'deadline_asc' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=deadline_asc') ?>">
                                            <i class="fas fa-clock text-danger me-1"></i> Nearest Deadline
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-1 px-2 fw-semibold <?= $selectedSort === 'deadline_desc' ? 'active text-white' : '' ?>"
                                           href="<?= base_url($baseFilterUrlWithoutSort . '&sort=deadline_desc') ?>">
                                            <i class="fas fa-calendar-check text-success me-1"></i> Farthest Deadline
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </th>
                    <th style="min-width:180px;">Status &amp; Actions</th>
                    <?php if ($selectedProses === 'SELESAI'): ?>
                        <th style="width:130px;" class="text-success fw-bold"><i class="fas fa-calendar-check me-1"></i> Completed Date</th>
                    <?php endif; ?>
                    <th style="width:180px;">Note</th>
                    <th style="width:150px;">Created By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($agreements)): ?>
                    <tr>
                        <td colspan="<?= $selectedProses === 'SELESAI' ? '9' : '8' ?>" class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block opacity-50"></i>
                            <?php if (is_superadmin()): ?>
                                No documents found for this filter.
                            <?php else: ?>
                                No documents found for account <strong><?= htmlspecialchars($uCode) ?></strong> or your departments under this filter.
                            <?php endif; ?>
                            <div class="mt-3">
                                <a href="<?= base_url('?page=create' . ($selectedDocType !== 'ALL' ? '&doc_type=' . urlencode($selectedDocType) : '') . ($selectedDept !== 'ALL' ? '&dept=' . urlencode($selectedDept) : '')) ?>" class="btn btn-sm btn-primary fw-bold px-3 py-2">
                                    <i class="fas fa-plus-circle me-1"></i> + Add New Document
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
                                       title="Select document <?= htmlspecialchars($displayTitle) ?>">
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

                            <td class="mc-dates" data-label="Validity">
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

                            <td class="mc-process" data-label="Status">
                                <?php
                                $rowProses = strtoupper(trim($row['status_proses'] ?? 'PENGAJUAN'));
                                $pMeta = get_process_status_meta($rowProses);
                                ?>
                                <div class="d-flex flex-column gap-1">
                                    <div class="btn-group btn-group-xs process-segmented-group no-print" role="group" aria-label="Change Status">
                                        <button type="button"
                                                class="btn btn-xs <?= $rowProses === 'PENGAJUAN' ? 'btn-warning text-dark fw-bold shadow-sm' : 'btn-outline-secondary' ?>"
                                                style="font-size:0.68rem;padding:2px 7px;"
                                                title="Change document status to Submission"
                                                onclick="submitQuickStatus(<?= $row['id'] ?>, 'PENGAJUAN', '<?= htmlspecialchars(addslashes($displayTitle)) ?>')">
                                            Submission
                                        </button>
                                        <button type="button"
                                                class="btn btn-xs <?= $rowProses === 'BERJALAN' ? 'btn-primary text-white fw-bold shadow-sm' : 'btn-outline-secondary' ?>"
                                                style="font-size:0.68rem;padding:2px 7px;"
                                                title="Change document status to In Progress"
                                                onclick="submitQuickStatus(<?= $row['id'] ?>, 'BERJALAN', '<?= htmlspecialchars(addslashes($displayTitle)) ?>')">
                                            In Progress
                                        </button>
                                        <button type="button"
                                                class="btn btn-xs <?= $rowProses === 'SELESAI' ? 'btn-success text-white fw-bold shadow-sm' : 'btn-outline-secondary' ?>"
                                                style="font-size:0.68rem;padding:2px 7px;"
                                                title="Mark document as Completed"
                                                onclick="openModalSetSelesai(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($displayTitle)) ?>', '<?= htmlspecialchars($row['tanggal_selesai'] ?? date('Y-m-d')) ?>')">
                                            Completed
                                        </button>
                                    </div>

                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <span class="badge" style="background:<?= $pMeta['badge_bg'] ?>;color:<?= $pMeta['badge_color'] ?>;border:1px solid <?= $pMeta['badge_border'] ?>;font-size:0.68rem;">
                                            <i class="<?= $pMeta['icon_static'] ?> me-1"></i><?= htmlspecialchars($pMeta['label']) ?>
                                        </span>
                                    </div>

                                    <?php if ($rowProses === 'SELESAI' && $selectedProses !== 'SELESAI'): ?>
                                        <div class="text-success small fw-bold" style="font-size:0.7rem;">
                                            <i class="fas fa-calendar-check me-1"></i>Completed: <?= format_date_medquest($row['tanggal_selesai']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <?php if ($selectedProses === 'SELESAI'): ?>
                            <td class="mc-selesai-tgl align-middle text-nowrap" data-label="Completed Date">
                                <div class="fw-bold text-success" style="font-size:0.78rem;">
                                    <i class="fas fa-calendar-check me-1"></i><?= format_date_medquest($row['tanggal_selesai']) ?>
                                </div>
                                <div class="text-muted small" style="font-size:0.68rem;">Completion Date</div>
                            </td>
                            <?php endif; ?>

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
                                    <a href="<?= base_url('?page=detail&id=' . $row['id']) ?>" class="btn btn-sm btn-light border py-0 px-2 small text-primary" title="View Document Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('?page=edit&id=' . $row['id']) ?>" class="btn btn-sm btn-light border py-0 px-2 small" title="Edit">
                                        <i class="fas fa-edit text-secondary"></i>
                                    </a>
                                    <form method="POST" action="<?= base_url('?page=agreements') ?>" class="d-inline" onsubmit="return confirm('Delete document <?= htmlspecialchars(addslashes($displayTitle)) ?>?')">
                                        @csrf
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="agreement_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="return_dept" value="<?= htmlspecialchars($selectedDept) ?>">
                                        <button type="submit" class="btn btn-sm btn-light border py-0 px-2 small text-danger" title="Delete">
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
                Showing <span class="text-dark fw-bold"><?= $offset + 1 ?> &ndash; <?= min($offset + $perPage, $statTotal) ?></span> of <span class="text-dark fw-bold"><?= $statTotal ?></span> documents
                <span class="ms-1 text-secondary">(Page <?= $currentPage ?> of <?= $totalPages ?>)</span>
            <?php else: ?>
                No documents to display
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
            $pageUrlHelper = function($p) use ($selectedDept, $selectedDocType, $selectedStatus, $selectedProses, $searchQuery, $selectedSort) {
                $q = [
                    'page'     => 'agreements',
                    'dept'     => $selectedDept,
                    'doc_type' => $selectedDocType,
                    'status'   => $selectedStatus,
                    'proses'   => $selectedProses,
                    'p'        => $p
                ];
                if ($searchQuery !== '') $q['q'] = $searchQuery;
                if ($selectedSort !== 'default') $q['sort'] = $selectedSort;
                return base_url('?' . http_build_query($q));
            };
            ?>
            <nav aria-label="Document Page Navigation">
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
                    <i class="fas fa-sticky-note me-2"></i> Add Note / Follow-up History
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="p-2 rounded-0 bg-light border mb-3 small">
                    <div class="fw-bold text-dark" id="noteModalCustomer"></div>
                    <div class="text-muted" id="noteModalDocNo"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Note Date <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_input" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Follow-up Note <span class="text-danger">*</span></label>
                    <textarea name="catatan_baru" class="form-control" rows="3" placeholder="e.g., Currently under review by customer legal team..." required></textarea>
                </div>

                <div class="mb-1">
                    <label class="form-label fw-semibold small">Extend / New Expiry Date (Optional)</label>
                    <input type="date" name="new_expiry_date" class="form-control">
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Save Note</button>
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
                <h6 class="modal-title fw-bold">Add New Department</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Department Code <span class="text-danger">*</span></label>
                    <input type="text" name="kode_dept" class="form-control text-uppercase fw-bold" placeholder="e.g., MKT / FIN" maxlength="15" required>
                </div>
                <div class="mb-1">
                    <label class="form-label small fw-semibold">Department Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="nama_dept" class="form-control" placeholder="e.g., Marketing Division" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100 fw-bold">Save Department</button>
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
    <input type="hidden" name="return_proses" value="<?= htmlspecialchars($selectedProses) ?>">
    <input type="hidden" name="return_sort" value="<?= htmlspecialchars($selectedSort) ?>">
    <div id="bulkDeleteInputsContainer"></div>
</form>

<div class="modal fade no-print" id="modalSetSelesai" tabindex="-1" aria-labelledby="modalSetSelesaiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= base_url('?page=agreements') ?>" class="modal-content rounded-0 border-0 shadow">
            @csrf
            <input type="hidden" name="action" value="update_process_status">
            <input type="hidden" name="status_proses" value="SELESAI">
            <input type="hidden" name="agreement_id" id="modalSelesaiAgrId" value="">
            <input type="hidden" name="return_dept" value="<?= htmlspecialchars($selectedDept) ?>">
            <input type="hidden" name="return_doc_type" value="<?= htmlspecialchars($selectedDocType) ?>">
            <input type="hidden" name="return_status" value="<?= htmlspecialchars($selectedStatus) ?>">
            <input type="hidden" name="return_proses" value="<?= htmlspecialchars($selectedProses) ?>">
            <input type="hidden" name="return_sort" value="<?= htmlspecialchars($selectedSort) ?>">
            <input type="hidden" name="return_q" value="<?= htmlspecialchars($searchQuery) ?>">
            <input type="hidden" name="return_p" value="<?= htmlspecialchars((string) $currentPage) ?>">

            <div class="modal-header bg-success text-white py-2 px-3">
                <h6 class="modal-title fw-bold" id="modalSetSelesaiLabel">
                    <i class="fas fa-check-circle me-1"></i> Mark Document as Completed
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-light border mb-3 p-2 small">
                    <div class="text-muted small">Document:</div>
                    <strong id="modalSelesaiDocTitle" class="text-dark"></strong>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">
                        <i class="fas fa-calendar-alt text-success me-1"></i> Completion Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="tanggal_selesai" id="modalSelesaiTanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    <div class="form-text small text-muted">Official date when the document/agreement process was completed.</div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold text-dark">Completion Note (Optional)</label>
                    <textarea name="catatan_status" class="form-control" rows="2" placeholder="e.g., Document has been approved by all parties and archived"></textarea>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-success fw-bold px-3">
                    <i class="fas fa-check me-1"></i> Save Completed Status
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade no-print" id="modalChangeProcessStatus" tabindex="-1" aria-labelledby="modalChangeProcessStatusLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formQuickStatus" method="POST" action="<?= base_url('?page=agreements') ?>" class="modal-content rounded-0 border-0 shadow">
            @csrf
            <input type="hidden" name="action" value="update_process_status">
            <input type="hidden" name="agreement_id" id="quickStatusAgrId" value="">
            <input type="hidden" name="status_proses" id="quickStatusVal" value="">
            <input type="hidden" name="return_dept" value="<?= htmlspecialchars($selectedDept) ?>">
            <input type="hidden" name="return_doc_type" value="<?= htmlspecialchars($selectedDocType) ?>">
            <input type="hidden" name="return_status" value="<?= htmlspecialchars($selectedStatus) ?>">
            <input type="hidden" name="return_proses" value="<?= htmlspecialchars($selectedProses) ?>">
            <input type="hidden" name="return_sort" value="<?= htmlspecialchars($selectedSort) ?>">
            <input type="hidden" name="return_q" value="<?= htmlspecialchars($searchQuery) ?>">
            <input type="hidden" name="return_p" value="<?= htmlspecialchars((string) $currentPage) ?>">

            <div class="modal-header text-white py-2 px-3" id="quickStatusHeader">
                <h6 class="modal-title fw-bold" id="quickStatusTitle">
                    <i id="quickStatusIcon" class="fas fa-play-circle me-1"></i> <span id="quickStatusHeaderLabel">Change Status</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-light border mb-3 p-2 small">
                    <div class="text-muted small">Document:</div>
                    <strong id="quickStatusDocTitle" class="text-dark"></strong>
                </div>

                <p class="mb-3 small text-secondary" id="quickStatusDesc">
                    Are you sure you want to change the status of this document to <strong id="quickStatusTargetLabel"></strong>?
                </p>

                <div class="mb-1">
                    <label class="form-label small fw-bold text-dark">Change Note (Optional)</label>
                    <textarea name="catatan_status" id="quickStatusCatatan" class="form-control" rows="2" placeholder="e.g., Document review and negotiation started"></textarea>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm fw-bold px-3" id="quickStatusSubmitBtn">
                    <i class="fas fa-check me-1"></i> Yes, Update Status
                </button>
            </div>
        </form>
    </div>
</div>

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
    if (!confirm('Are you sure you want to permanently delete ' + checkedBoxes.length + ' selected document(s)?')) {
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

function submitQuickStatus(id, status, title) {
    document.getElementById('quickStatusAgrId').value = id;
    document.getElementById('quickStatusVal').value = status;
    document.getElementById('quickStatusDocTitle').textContent = title;
    document.getElementById('quickStatusCatatan').value = '';

    const headerEl = document.getElementById('quickStatusHeader');
    const titleEl = document.getElementById('quickStatusHeaderLabel');
    const iconEl = document.getElementById('quickStatusIcon');
    const targetEl = document.getElementById('quickStatusTargetLabel');
    const submitBtn = document.getElementById('quickStatusSubmitBtn');

    if (status === 'BERJALAN') {
        headerEl.className = 'modal-header bg-primary text-white py-2 px-3';
        titleEl.textContent = 'Change Status: In Progress';
        iconEl.className = 'fas fa-spinner fa-spin me-1';
        targetEl.textContent = 'In Progress';
        targetEl.style.color = '#1d4ed8';
        submitBtn.className = 'btn btn-sm btn-primary fw-bold px-3';
        submitBtn.innerHTML = '<i class="fas fa-play me-1"></i> Yes, Set to In Progress';
    } else {
        headerEl.className = 'modal-header bg-warning text-dark py-2 px-3';
        titleEl.textContent = 'Change Status: Submission';
        iconEl.className = 'fas fa-file-signature me-1';
        targetEl.textContent = 'Submission';
        targetEl.style.color = '#b45309';
        submitBtn.className = 'btn btn-sm btn-warning text-dark fw-bold px-3';
        submitBtn.innerHTML = '<i class="fas fa-check me-1"></i> Yes, Set to Submission';
    }

    const modalEl = document.getElementById('modalChangeProcessStatus');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function openModalSetSelesai(id, title, tgl) {
    document.getElementById('modalSelesaiAgrId').value = id;
    document.getElementById('modalSelesaiDocTitle').textContent = title;
    document.getElementById('modalSelesaiTanggal').value = tgl || new Date().toISOString().slice(0, 10);
    const modalEl = document.getElementById('modalSetSelesai');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}
</script>
@endsection
