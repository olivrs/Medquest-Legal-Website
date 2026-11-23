@extends('layouts.app')

@section('content')
<?php
$exportQueryParams = [
    'page'     => 'export',
    'dataset'  => $datasetMode,
    'dept'     => $selectedDept,
    'doc_type' => $selectedDocType,
    'proses'   => $selectedProses,
    'status'   => $selectedStatus,
    'sort'     => $selectedSort,
];
if ($searchQuery !== '') {
    $exportQueryParams['q'] = $searchQuery;
}
$xlsxDownloadUrl = base_url('?' . http_build_query(array_merge($exportQueryParams, ['download' => 'xlsx'])));
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
                        <option value="COOPERATION" <?= $selectedDocType === 'COOPERATION' ? 'selected' : '' ?>>COOPERATION AGREEMENT</option>
                        <option value="COMPANY" <?= $selectedDocType === 'COMPANY' ? 'selected' : '' ?>>ORIGINAL COMPANY DOC</option>
                        <option value="PRINCIPAL" <?= $selectedDocType === 'PRINCIPAL' ? 'selected' : '' ?>>PRINCIPAL DOCUMENT</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Process Status</label>
                    <select name="proses" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="ALL" <?= $selectedProses === 'ALL' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="PENGAJUAN" <?= $selectedProses === 'PENGAJUAN' ? 'selected' : '' ?>>Submission</option>
                        <option value="BERJALAN" <?= $selectedProses === 'BERJALAN' ? 'selected' : '' ?>>In Progress</option>
                        <option value="SELESAI" <?= $selectedProses === 'SELESAI' ? 'selected' : '' ?>>Completed</option>
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

                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-bold small text-dark mb-1">Sort By</label>
                    <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="default" <?= $selectedSort === 'default' ? 'selected' : '' ?>>Form Number (Default)</option>
                        <option value="newest" <?= $selectedSort === 'newest' ? 'selected' : '' ?>>Newest &gt; Oldest</option>
                        <option value="oldest" <?= $selectedSort === 'oldest' ? 'selected' : '' ?>>Oldest &gt; Newest</option>
                        <option value="deadline_asc" <?= $selectedSort === 'deadline_asc' ? 'selected' : '' ?>>Nearest Deadline</option>
                        <option value="deadline_desc" <?= $selectedSort === 'deadline_desc' ? 'selected' : '' ?>>Farthest Deadline</option>
                    </select>
                </div>

                <div class="col-md-6 col-sm-8">
                    <label class="form-label fw-bold small text-dark mb-1">Search Keyword (Optional)</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search document number, customer, product, principal, note...">
                </div>

                <div class="col-md-3 col-sm-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold flex-grow-1">
                        <i class="fas fa-filter me-1"></i> Apply Filter
                    </button>
                    <a href="<?= base_url('?page=export') ?>" class="btn btn-sm btn-light border fw-semibold">Reset</a>
                </div>
            <?php else: ?>
                <div class="col-md-7 col-sm-12 d-flex align-items-center justify-content-between bg-light border px-3 py-2 small text-muted">
                    <span>Showing all accessible documents approaching expiry within H-90 to H-0 sorted by nearest deadline.</span>
                    <a href="<?= base_url('?page=export&dataset=deadlines') ?>" class="btn btn-xs btn-light border ms-2">Reset</a>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="cp-table-card mq-export-print-area mt-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3 gap-2">
        <div>
            <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($dataset['title']) ?></h6>
            <div class="text-muted small"><?= htmlspecialchars($dataset['subtitle']) ?></div>
        </div>
        <div class="small text-secondary no-print">
            Preview matches the exact exported Excel &amp; PDF table layout.
        </div>
    </div>

    <div class="table-responsive">
        <table class="cp-table" id="mqExportPreviewTable">
            <thead>
                <tr>
                    <?php foreach ($dataset['columns'] as $col): ?>
                        <th><?= htmlspecialchars($col['label']) ?></th>
                    <?php endforeach; ?>
                </tr>
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
                            'expired' => 'row-expired',
                            'warning' => 'row-warning',
                            default   => '',
                        };
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <?php foreach ($row['cells'] as $cell):
                                $text = is_array($cell) ? (string) ($cell['text'] ?? '') : (string) $cell;
                                $bold = is_array($cell) && !empty($cell['bold']);
                                $tone = is_array($cell) ? (string) ($cell['tone'] ?? '') : '';
                                $toneClass = match ($tone) {
                                    'danger'  => 'text-danger',
                                    'warning' => 'text-warning-emphasis',
                                    'success' => 'text-success',
                                    default   => 'text-dark',
                                };
                            ?>
                                <td class="<?= $bold ? 'fw-semibold ' : '' ?><?= $toneClass ?>" style="white-space:pre-line;vertical-align:top;"><?= nl2br(htmlspecialchars($text)) ?></td>
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

    const columns = MQ_EXPORT_DATASET.columns || [];
    const totalWeight = columns.reduce((sum, col) => sum + (Number(col.width) || 20), 0) || 1;
    const usableWidth = 277;
    const columnStyles = {};
    columns.forEach((col, idx) => {
        columnStyles[idx] = {
            cellWidth: Number((((Number(col.width) || 20) / totalWeight) * usableWidth).toFixed(2))
        };
    });

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.setTextColor(30, 41, 59);
    doc.text(MQ_EXPORT_DATASET.title || 'MEDQUEST LEGAL DOCUMENTS', 10, 13);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(71, 85, 105);
    doc.text(MQ_EXPORT_DATASET.subtitle || '', 10, 18.5);

    const head = [columns.map(c => c.label || '')];
    const rowStates = [];
    const cellMeta = [];

    const body = (MQ_EXPORT_DATASET.rows || []).map((row, rIdx) => {
        rowStates[rIdx] = row.row_state || 'normal';
        cellMeta[rIdx] = [];
        return (row.cells || []).map((cell, cIdx) => {
            const text = typeof cell === 'object' && cell !== null ? String(cell.text || '') : String(cell || '');
            cellMeta[rIdx][cIdx] = {
                bold: Boolean(cell && cell.bold),
                tone: (cell && cell.tone) ? String(cell.tone) : ''
            };
            return text;
        });
    });

    doc.autoTable({
        head: head,
        body: body,
        startY: 22,
        margin: { top: 14, right: 10, bottom: 14, left: 10 },
        theme: 'grid',
        showHead: 'everyPage',
        headStyles: {
            fillColor: [30, 41, 59],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 8,
            halign: 'left',
            valign: 'middle',
            cellPadding: 2.3,
            lineColor: [203, 213, 225],
            lineWidth: 0.2
        },
        styles: {
            font: 'helvetica',
            fontSize: 7.5,
            textColor: [30, 41, 59],
            cellPadding: 2.1,
            lineColor: [203, 213, 225],
            lineWidth: 0.2,
            overflow: 'linebreak',
            valign: 'top'
        },
        columnStyles: columnStyles,
        didParseCell: function (data) {
            if (data.section !== 'body') {
                return;
            }
            const state = rowStates[data.row.index] || 'normal';
            if (state === 'expired') {
                data.cell.styles.fillColor = [254, 242, 242];
            } else if (state === 'warning') {
                data.cell.styles.fillColor = [255, 251, 235];
            } else if (data.row.index % 2 === 1) {
                data.cell.styles.fillColor = [248, 250, 252];
            }
            const meta = (cellMeta[data.row.index] && cellMeta[data.row.index][data.column.index]) || {};
            if (meta.bold) {
                data.cell.styles.fontStyle = 'bold';
            }
            if (meta.tone === 'danger') {
                data.cell.styles.textColor = [185, 28, 28];
            } else if (meta.tone === 'warning') {
                data.cell.styles.textColor = [180, 83, 9];
            } else if (meta.tone === 'success') {
                data.cell.styles.textColor = [4, 120, 87];
            }
        },
        didDrawPage: function (data) {
            const pageSize = doc.internal.pageSize;
            const pageHeight = pageSize.height ? pageSize.height : pageSize.getHeight();
            const pageWidth = pageSize.width ? pageSize.width : pageSize.getWidth();
            doc.setFontSize(7.5);
            doc.setTextColor(100, 116, 139);
            doc.text('PT Medquest Jaya Global - Legal Document Monitoring', 10, pageHeight - 6);
            doc.text('Page ' + data.pageNumber, pageWidth - 22, pageHeight - 6);
        }
    });

    doc.save((MQ_EXPORT_DATASET.filename || 'Medquest_Export') + '.pdf');
}
</script>
@endsection
