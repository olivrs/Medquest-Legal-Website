@extends('layouts.app')

@section('content')
<?php
$exportQueryParams = [
    'page'       => 'export',
    'dataset'    => $datasetMode,
    'dept'       => $selectedDept,
    'doc_type'   => $selectedDocType,
    'proses'     => $selectedProses,
    'status'     => $selectedStatus,
    'date_field' => $selectedDateField,
    'sort'       => $selectedSort,
];
if ($dateFrom !== '') {
    $exportQueryParams['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $exportQueryParams['date_to'] = $dateTo;
}
if ($searchQuery !== '') {
    $exportQueryParams['q'] = $searchQuery;
}
$xlsxDownloadUrl = base_url('?' . http_build_query(array_merge($exportQueryParams, ['download' => 'xlsx'])));
$formMeta = $dataset['form_meta'] ?? [
    'doc_no'         => 'MJG-EXP-ALL-ALL-' . date('dmy'),
    'effective_date' => strtoupper(date('d M Y')),
    'revision_no'    => '01',
    'page_no'        => '1 of 1',
];
?>

<div class="cp-header-row no-print">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('?page=agreements' . ($selectedDept !== 'ALL' ? '&dept=' . urlencode($selectedDept) : '')) ?>" class="btn btn-sm btn-light border fw-semibold px-2 py-1" style="font-size:0.74rem;">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <h1 class="cp-page-title">Export Documents</h1>
        <span class="badge bg-light text-dark border px-2 py-1 fw-semibold" style="font-size:0.72rem;">
            <?= count($dataset['rows']) ?> Record(s) Ready
        </span>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="<?= $xlsxDownloadUrl ?>" class="btn btn-sm btn-success fw-bold px-3 py-1 d-inline-flex align-items-center gap-1">
            <i class="fas fa-file-excel"></i>
            <span>Export Excel (.xlsx)</span>
        </a>
        <button type="button" onclick="exportMedquestPdf()" class="btn btn-sm btn-danger fw-bold px-3 py-1 d-inline-flex align-items-center gap-1">
            <i class="fas fa-file-pdf"></i>
            <span>Export PDF (.pdf)</span>
        </button>
        <button type="button" onclick="window.print()" class="btn btn-sm btn-light border fw-semibold px-3 py-1 d-inline-flex align-items-center gap-1">
            <i class="fas fa-print"></i>
            <span>Print Table</span>
        </button>
    </div>
</div>

<div class="cp-table-card mt-0 mb-3 no-print">
    <form method="GET" action="<?= base_url() ?>" id="exportFilterForm">
        <input type="hidden" name="page" value="export">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-bold small text-dark mb-1">Table to Export</label>
                <select name="dataset" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()">
                    <option value="agreements" <?= $datasetMode === 'agreements' ? 'selected' : '' ?>>Documents Table</option>
                    <option value="deadlines" <?= $datasetMode === 'deadlines' ? 'selected' : '' ?>>Deadline Monitoring (H-90 to H-0)</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <label class="form-label fw-bold small text-dark mb-1">Department</label>
                <select name="dept" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ALL" <?= $selectedDept === 'ALL' ? 'selected' : '' ?>>All Departments</option>
                    <?php foreach ($sidebarDepts as $sd):
                        $dCode = strtoupper($sd['kode_dept']);
                    ?>
                        <option value="<?= htmlspecialchars($dCode) ?>" <?= $selectedDept === $dCode ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dCode) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($datasetMode === 'agreements'): ?>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Document Type</label>
                    <select name="doc_type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="ALL" <?= $selectedDocType === 'ALL' ? 'selected' : '' ?>>ALL TYPES</option>
                        <option value="COOPERATION" <?= $selectedDocType === 'COOPERATION' ? 'selected' : '' ?>>LOG OF COOPERATION AGREEMENT</option>
                        <option value="COMPANY" <?= $selectedDocType === 'COMPANY' ? 'selected' : '' ?>>ORIGINAL COMPANY DOCUMENT</option>
                        <option value="PRINCIPAL" <?= $selectedDocType === 'PRINCIPAL' ? 'selected' : '' ?>>LOG OF PRINCIPAL DOCUMENT</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Process Status</label>
                    <select name="proses" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="ALL" <?= $selectedProses === 'ALL' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="PENGAJUAN" <?= $selectedProses === 'PENGAJUAN' ? 'selected' : '' ?>>Submission</option>
                        <option value="BERJALAN" <?= $selectedProses === 'BERJALAN' ? 'selected' : '' ?>>In Progress</option>
                        <option value="SELESAI" <?= $selectedProses === 'SELESAI' ? 'selected' : '' ?>>Completed (ACC)</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Deadline Filter</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="ALL" <?= $selectedStatus === 'ALL' ? 'selected' : '' ?>>All</option>
                        <option value="ACTIVE" <?= $selectedStatus === 'ACTIVE' ? 'selected' : '' ?>>Active (&gt; 90 Days)</option>
                        <option value="H90_60" <?= $selectedStatus === 'H90_60' ? 'selected' : '' ?>>60 - 90 Days Left</option>
                        <option value="H30_14" <?= $selectedStatus === 'H30_14' ? 'selected' : '' ?>>14 - 30 Days Left</option>
                        <option value="H7_3" <?= $selectedStatus === 'H7_3' ? 'selected' : '' ?>>3 - 7 Days Left</option>
                        <option value="EXPIRED" <?= $selectedStatus === 'EXPIRED' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Filter Date By</label>
                    <select name="date_field" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="expiry_date" <?= $selectedDateField === 'expiry_date' ? 'selected' : '' ?>>Expiry / Deadline</option>
                        <option value="effective_date" <?= $selectedDateField === 'effective_date' ? 'selected' : '' ?>>Effective Date</option>
                        <option value="created_at" <?= $selectedDateField === 'created_at' ? 'selected' : '' ?>>Input Date</option>
                        <option value="tanggal_selesai" <?= $selectedDateField === 'tanggal_selesai' ? 'selected' : '' ?>>Completed (ACC) Date</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Date From</label>
                    <input type="text" name="date_from" class="form-control form-control-sm mq-date-input" placeholder="dd/mm/yyyy" maxlength="10" inputmode="numeric" autocomplete="off" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Date To</label>
                    <input type="text" name="date_to" class="form-control form-control-sm mq-date-input" placeholder="dd/mm/yyyy" maxlength="10" inputmode="numeric" autocomplete="off" value="<?= htmlspecialchars($dateTo) ?>">
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Sort By</label>
                    <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="default" <?= $selectedSort === 'default' ? 'selected' : '' ?>>Form Number (Default)</option>
                        <option value="deadline_asc" <?= $selectedSort === 'deadline_asc' ? 'selected' : '' ?>>Deadline (Earliest &gt; Latest)</option>
                        <option value="deadline_desc" <?= $selectedSort === 'deadline_desc' ? 'selected' : '' ?>>Deadline (Latest &gt; Earliest)</option>
                        <option value="effective_asc" <?= $selectedSort === 'effective_asc' ? 'selected' : '' ?>>Effective Date (Earliest &gt; Latest)</option>
                        <option value="effective_desc" <?= $selectedSort === 'effective_desc' ? 'selected' : '' ?>>Effective Date (Latest &gt; Earliest)</option>
                        <option value="completed_desc" <?= $selectedSort === 'completed_desc' ? 'selected' : '' ?>>Completed Date (Newest)</option>
                        <option value="newest" <?= $selectedSort === 'newest' ? 'selected' : '' ?>>Newest &gt; Oldest</option>
                        <option value="oldest" <?= $selectedSort === 'oldest' ? 'selected' : '' ?>>Oldest &gt; Newest</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Search Keyword</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Doc no, customer...">
                </div>

                <div class="col-md-2 col-sm-12 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold flex-grow-1">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                    <a href="<?= base_url('?page=export') ?>" class="btn btn-sm btn-light border fw-semibold">Reset</a>
                </div>
            <?php else: ?>
                <input type="hidden" name="date_field" value="expiry_date">
                <div class="col-md-2 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Expiry From</label>
                    <input type="text" name="date_from" class="form-control form-control-sm mq-date-input" placeholder="dd/mm/yyyy" maxlength="10" inputmode="numeric" autocomplete="off" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Expiry To</label>
                    <input type="text" name="date_to" class="form-control form-control-sm mq-date-input" placeholder="dd/mm/yyyy" maxlength="10" inputmode="numeric" autocomplete="off" value="<?= htmlspecialchars($dateTo) ?>">
                </div>

                <div class="col-md-3 col-sm-12 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold flex-grow-1">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                    <a href="<?= base_url('?page=export&dataset=deadlines') ?>" class="btn btn-sm btn-light border fw-semibold">Reset</a>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="cp-table-card mq-export-print-area mt-0 p-3">
    <div class="table-responsive">
        <table class="mq-iso-header-table mb-0">
            <tbody>
                <tr>
                    <td rowspan="4" style="width:16%;text-align:center;background:#ffffff;padding:8px;">
                        <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="Medquest Logo" style="max-width:140px;width:100%;height:auto;display:inline-block;">
                    </td>
                    <td rowspan="2" style="width:41%;text-align:center;font-weight:700;font-size:0.95rem;letter-spacing:0.4px;">
                        FORM
                    </td>
                    <td style="width:11%;font-size:0.72rem;border-right:none;border-bottom:none;">Doc. No.</td>
                    <td style="width:17%;font-size:0.72rem;border-left:none;border-bottom:none;">: <?= htmlspecialchars($formMeta['doc_no']) ?></td>
                    <td class="mq-legend-expired" style="width:15%;background:#ffffff;font-weight:600;font-size:0.71rem;">Past Deadline / ED</td>
                </tr>
                <tr>
                    <td style="font-size:0.72rem;border-right:none;border-top:none;border-bottom:none;">Effective Date</td>
                    <td style="font-size:0.72rem;border-left:none;border-top:none;border-bottom:none;">: <?= htmlspecialchars($formMeta['effective_date']) ?></td>
                    <td class="mq-legend-warning" style="background:#ffffff;font-weight:600;font-size:0.71rem;">Near Deadline (&lt;=90d)</td>
                </tr>
                <tr>
                    <td rowspan="2" style="text-align:center;font-weight:700;font-size:0.95rem;letter-spacing:0.3px;">
                        <?= htmlspecialchars($dataset['title']) ?>
                    </td>
                    <td style="font-size:0.72rem;border-right:none;border-top:none;border-bottom:none;">Revision No.</td>
                    <td style="font-size:0.72rem;border-left:none;border-top:none;border-bottom:none;">: <?= htmlspecialchars($formMeta['revision_no']) ?></td>
                    <td class="mq-legend-approved" style="background:#ffffff;font-weight:600;font-size:0.71rem;">Approved (ACC)</td>
                </tr>
                <tr>
                    <td style="font-size:0.72rem;border-right:none;border-top:none;">Page No.</td>
                    <td style="font-size:0.72rem;border-left:none;border-top:none;">: <?= htmlspecialchars($formMeta['page_no']) ?></td>
                    <td style="font-size:0.7rem;color:#475569;"><?= count($dataset['rows']) ?> Document(s)</td>
                </tr>
            </tbody>
        </table>

        <div class="border border-top-0 px-2 py-1 bg-light text-secondary small" style="border-color:#1e293b !important;font-size:0.72rem;">
            <?= htmlspecialchars($dataset['subtitle']) ?>
        </div>

        <table class="cp-table mq-iso-table mt-0" id="mqExportPreviewTable">
            <thead>
                <?php
                $headerGroups = $dataset['header_groups'] ?? [];
                $groupSpanSum = 0;
                foreach ($headerGroups as $grp) {
                    $groupSpanSum += max(1, (int) ($grp['colspan'] ?? 1));
                }
                $hasGroupedHeaders = !empty($headerGroups) && $groupSpanSum === count($dataset['columns']);
                ?>
                <?php if ($hasGroupedHeaders): ?>
                    <tr>
                        <?php foreach ($headerGroups as $grp):
                            $span = max(1, (int) ($grp['colspan'] ?? 1));
                        ?>
                            <?php if ($span > 1): ?>
                                <th colspan="<?= $span ?>"><?= htmlspecialchars($grp['label']) ?></th>
                            <?php else: ?>
                                <th rowspan="2"><?= htmlspecialchars($grp['label']) ?></th>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php
                        $leafIdx = 0;
                        foreach ($headerGroups as $grp):
                            $span = max(1, (int) ($grp['colspan'] ?? 1));
                            if ($span > 1):
                                for ($k = 0; $k < $span; $k++):
                                    $subCol = $dataset['columns'][$leafIdx + $k] ?? ['label' => ''];
                        ?>
                                    <th><?= htmlspecialchars($subCol['label']) ?></th>
                        <?php
                                endfor;
                            endif;
                            $leafIdx += $span;
                        endforeach;
                        ?>
                    </tr>
                <?php else: ?>
                    <tr>
                        <?php foreach ($dataset['columns'] as $col): ?>
                            <th><?= htmlspecialchars($col['label']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
            </thead>
            <tbody>
                <?php if (empty($dataset['rows'])): ?>
                    <tr>
                        <td colspan="<?= count($dataset['columns']) ?>" class="text-center py-5 text-muted">
                            No documents match the selected filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dataset['rows'] as $row):
                        $rowClass = match ($row['row_state']) {
                            'approved' => 'mq-row-approved',
                            'expired'  => 'mq-row-expired',
                            'warning'  => 'mq-row-warning',
                            default    => '',
                        };
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <?php foreach ($row['cells'] as $cell):
                                $text = is_array($cell) ? (string) ($cell['text'] ?? '') : (string) $cell;
                                $bold = is_array($cell) && !empty($cell['bold']);
                                $center = is_array($cell) && !empty($cell['center']);
                            ?>
                                <td class="<?= ($bold ? 'fw-semibold ' : '') . ($center ? 'text-center ' : '') ?>" style="white-space:pre-line;"><?= nl2br(htmlspecialchars($text)) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.4/dist/jspdf.plugin.autotable.min.js"></script>
<script>
const MQ_EXPORT_DATASET = <?= json_encode($dataset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
const MQ_LOGO_DATA_URI = <?= json_encode($logoDataUri ?? '', JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;

function mqFormatTodayMetaDate() {
    const months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    const now = new Date();
    const dd = String(now.getDate()).padStart(2, '0');
    const mm = months[now.getMonth()];
    const yyyy = now.getFullYear();
    return dd + ' ' + mm + ' ' + yyyy;
}

function drawFormalIsoHeader(doc, pageNumber, totalPagesToken) {
    const startX = 10;
    const headerTop = 8;
    const col1W = 42;
    const col2W = 113;
    const col3W = 76;
    const col4W = 46;
    const totalW = col1W + col2W + col3W + col4W;
    const rowH = 5.5;
    const fullH = rowH * 4;
    const meta = MQ_EXPORT_DATASET.form_meta || {};
    const exportEffDate = mqFormatTodayMetaDate();

    doc.setDrawColor(30, 41, 59);
    doc.setLineWidth(0.25);

    doc.setFillColor(255, 255, 255);
    doc.rect(startX, headerTop, col1W, fullH, 'FD');
    if (MQ_LOGO_DATA_URI) {
        doc.addImage(MQ_LOGO_DATA_URI, 'PNG', startX + 3, headerTop + 6.5, 36, 8.5);
    } else {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(11);
        doc.setTextColor(30, 58, 138);
        doc.text('Medquest', startX + col1W / 2, headerTop + 12, { align: 'center' });
    }

    const col2X = startX + col1W;
    doc.rect(col2X, headerTop, col2W, rowH * 2, 'FD');
    doc.rect(col2X, headerTop + rowH * 2, col2W, rowH * 2, 'FD');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(17, 24, 39);
    doc.text('FORM', col2X + col2W / 2, headerTop + 6.5, { align: 'center' });
    doc.setFontSize(9.5);
    doc.text(String(MQ_EXPORT_DATASET.title || 'MEDQUEST LEGAL DOCUMENTS'), col2X + col2W / 2, headerTop + 17.5, { align: 'center', maxWidth: col2W - 4 });

    const col3X = col2X + col2W;
    doc.rect(col3X, headerTop, col3W, fullH, 'FD');
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(17, 24, 39);
    const metaLabels = [
        ['Doc. No.', ': ' + (meta.doc_no || 'MJG-EXP-ALL-ALL')],
        ['Effective Date', ': ' + exportEffDate],
        ['Revision No.', ': ' + (meta.revision_no || '01')],
        ['Page No.', ': ' + pageNumber + ' of ' + totalPagesToken]
    ];
    metaLabels.forEach((pair, idx) => {
        const y = headerTop + 3.8 + (idx * rowH);
        doc.text(pair[0], col3X + 2, y);
        doc.text(pair[1], col3X + 24, y);
    });

    const col4X = col3X + col3W;
    doc.setFillColor(252, 165, 165);
    doc.rect(col4X, headerTop, col4W, rowH, 'FD');
    doc.setFillColor(254, 240, 138);
    doc.rect(col4X, headerTop + rowH, col4W, rowH, 'FD');
    doc.setFillColor(187, 247, 208);
    doc.rect(col4X, headerTop + rowH * 2, col4W, rowH, 'FD');
    doc.setFillColor(255, 255, 255);
    doc.rect(col4X, headerTop + rowH * 3, col4W, rowH, 'FD');

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7);
    doc.setTextColor(17, 24, 39);
    doc.text('Past Deadline / ED', col4X + 2, headerTop + 3.8);
    doc.text('Near Deadline (<=90d)', col4X + 2, headerTop + rowH + 3.8);
    doc.text('Approved (ACC)', col4X + 2, headerTop + rowH * 2 + 3.8);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(71, 85, 105);
    doc.text(String((MQ_EXPORT_DATASET.rows || []).length) + ' Document(s)', col4X + 2, headerTop + rowH * 3 + 3.8);

    doc.setFillColor(248, 250, 252);
    doc.rect(startX, headerTop + fullH, totalW, 5, 'FD');
    doc.setFontSize(6.8);
    doc.setTextColor(55, 65, 81);
    doc.text(String(MQ_EXPORT_DATASET.subtitle || ''), startX + 2, headerTop + fullH + 3.4, { maxWidth: totalW - 4 });
}

function exportMedquestPdf() {
    if (!window.jspdf || typeof window.jspdf.jsPDF !== 'function') {
        window.print();
        return;
    }

    const doc = new window.jspdf.jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: 'a4'
    });

    if (typeof doc.autoTable !== 'function') {
        window.print();
        return;
    }

    const totalPagesToken = '{total_pages_count_string}';
    const columns = MQ_EXPORT_DATASET.columns || [];
    const totalWeight = columns.reduce((sum, col) => sum + (Number(col.width) || 20), 0) || 1;
    const usableWidth = 277;
    const columnStyles = {};
    columns.forEach((col, idx) => {
        columnStyles[idx] = {
            cellWidth: Number((((Number(col.width) || 20) / totalWeight) * usableWidth).toFixed(2))
        };
    });

    let head;
    const headerGroups = Array.isArray(MQ_EXPORT_DATASET.header_groups) ? MQ_EXPORT_DATASET.header_groups : [];
    const groupSpanSum = headerGroups.reduce((sum, g) => sum + Math.max(1, Number(g.colspan) || 1), 0);
    if (headerGroups.length > 0 && groupSpanSum === columns.length) {
        const row1 = [];
        const row2 = [];
        let leafIdx = 0;
        headerGroups.forEach((grp) => {
            const span = Math.max(1, Number(grp.colspan) || 1);
            if (span > 1) {
                row1.push({
                    content: String(grp.label || ''),
                    colSpan: span,
                    styles: { halign: 'center', valign: 'middle' }
                });
                for (let k = 0; k < span; k++) {
                    const leaf = columns[leafIdx + k] || {};
                    row2.push({
                        content: String(leaf.label || ''),
                        styles: { halign: 'center', valign: 'middle' }
                    });
                }
            } else {
                row1.push({
                    content: String(grp.label || ''),
                    rowSpan: 2,
                    styles: { halign: 'center', valign: 'middle' }
                });
            }
            leafIdx += span;
        });
        head = [row1, row2];
    } else {
        head = [columns.map(c => c.label || '')];
    }
    const rowStates = [];
    const cellMeta = [];

    const body = (MQ_EXPORT_DATASET.rows || []).map((row, rIdx) => {
        rowStates[rIdx] = row.row_state || 'normal';
        cellMeta[rIdx] = [];
        return (row.cells || []).map((cell, cIdx) => {
            const text = typeof cell === 'object' && cell !== null ? String(cell.text || '') : String(cell || '');
            cellMeta[rIdx][cIdx] = {
                bold: Boolean(cell && cell.bold),
                center: Boolean(cell && cell.center)
            };
            return text;
        });
    });

    doc.autoTable({
        head: head,
        body: body,
        startY: 36,
        margin: { top: 36, right: 10, bottom: 12, left: 10 },
        theme: 'grid',
        showHead: 'everyPage',
        headStyles: {
            fillColor: [226, 232, 240],
            textColor: [17, 24, 39],
            fontStyle: 'bold',
            fontSize: 7.5,
            halign: 'center',
            valign: 'middle',
            cellPadding: 2,
            lineColor: [30, 41, 59],
            lineWidth: 0.25
        },
        styles: {
            font: 'helvetica',
            fontSize: 7.2,
            textColor: [17, 24, 39],
            cellPadding: 1.8,
            lineColor: [30, 41, 59],
            lineWidth: 0.2,
            overflow: 'linebreak',
            valign: 'middle'
        },
        columnStyles: columnStyles,
        didParseCell: function (data) {
            if (data.section !== 'body') {
                return;
            }
            const state = rowStates[data.row.index] || 'normal';
            if (state === 'expired') {
                data.cell.styles.fillColor = [252, 165, 165];
            } else if (state === 'warning') {
                data.cell.styles.fillColor = [254, 240, 138];
            } else if (state === 'approved') {
                data.cell.styles.fillColor = [187, 247, 208];
            } else {
                data.cell.styles.fillColor = [255, 255, 255];
            }
            const meta = (cellMeta[data.row.index] && cellMeta[data.row.index][data.column.index]) || {};
            if (meta.bold) {
                data.cell.styles.fontStyle = 'bold';
            }
            if (meta.center) {
                data.cell.styles.halign = 'center';
            }
        },
        didDrawPage: function (data) {
            drawFormalIsoHeader(doc, data.pageNumber, totalPagesToken);
            const pageSize = doc.internal.pageSize;
            const pageHeight = pageSize.height ? pageSize.height : pageSize.getHeight();
            const pageWidth = pageSize.width ? pageSize.width : pageSize.getWidth();
            doc.setFontSize(7);
            doc.setTextColor(71, 85, 105);
            doc.text('PT Medquest Jaya Global - ' + (MQ_EXPORT_DATASET.title || ''), 10, pageHeight - 5);
            doc.text('Page ' + data.pageNumber + ' of ' + totalPagesToken, pageWidth - 28, pageHeight - 5);
        }
    });

    if (typeof doc.putTotalPages === 'function') {
        doc.putTotalPages(totalPagesToken);
    }

    doc.save((MQ_EXPORT_DATASET.filename || 'Medquest_Export') + '.pdf');
}
</script>
@endsection
