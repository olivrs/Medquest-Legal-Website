@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="cp-header-row mb-3">
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('?page=agreements&dept=' . urlencode($agr['department']) . '&doc_type=' . urlencode($docType)) ?>" class="btn btn-sm btn-light border fw-semibold px-3 py-1">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                <h1 class="cp-page-title ms-1">Edit <?= htmlspecialchars($typeMeta['title']) ?> (#<?= htmlspecialchars($agr['no_urut_dokumen'] ?: $agr['id']) ?>)</h1>
            </div>
            <a href="<?= base_url('?page=detail&id=' . $agr['id']) ?>" class="btn btn-sm btn-outline-primary fw-semibold px-3 py-1">
                <i class="fas fa-eye me-1"></i> Inspect Detail
            </a>
        </div>

        <div class="cp-table-card mt-0">
            <form method="POST" action="<?= base_url('?page=edit&id=' . $id) ?>">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Department / Division</label>
                        <select name="department" class="form-select fw-bold" required>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= htmlspecialchars($d['kode_dept']) ?>" <?= (strtoupper($agr['department']) === strtoupper($d['kode_dept'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['kode_dept']) ?> &mdash; <?= htmlspecialchars($d['nama_dept']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">No. Urut Baris Form (No.)</label>
                        <input type="number" name="no_urut_dokumen" class="form-control" value="<?= htmlspecialchars((string) $agr['no_urut_dokumen']) ?>">
                    </div>

                    <?php if ($docType === 'COMPANY' || $docType === 'PRINCIPAL'): ?>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Document Title <span class="text-danger">*</span></label>
                            <textarea name="document_title" class="form-control" rows="2" required><?= htmlspecialchars($agr['document_title'] ?: $agr['product_description']) ?></textarea>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Document No.</label>
                            <textarea name="document_number" class="form-control" rows="2"><?= htmlspecialchars($agr['document_number']) ?></textarea>
                        </div>

                        <div class="<?= $docType === 'PRINCIPAL' ? 'col-md-6' : 'col-md-12' ?>">
                            <label class="form-label fw-semibold">Issued By <span class="text-danger">*</span></label>
                            <input type="text" name="issued_by" class="form-control" value="<?= htmlspecialchars($agr['issued_by'] ?: $agr['customer']) ?>" required>
                        </div>

                        <?php if ($docType === 'PRINCIPAL'): ?>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Principal <span class="text-danger">*</span></label>
                                <input type="text" name="principal_name" class="form-control" value="<?= htmlspecialchars($agr['principal_name'] ?: $agr['customer']) ?>" required>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Document Number <span class="text-danger">*</span></label>
                            <textarea name="document_number" class="form-control" rows="2" required><?= htmlspecialchars($agr['document_number']) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                            <textarea name="customer" class="form-control" rows="2" required><?= htmlspecialchars($agr['customer']) ?></textarea>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Product &mdash; Description <span class="text-danger">*</span></label>
                            <input type="text" name="product_description" class="form-control" value="<?= htmlspecialchars($agr['product_description']) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Product &mdash; Serial Number</label>
                            <input type="text" name="serial_number" class="form-control" value="<?= htmlspecialchars($agr['serial_number']) ?>">
                        </div>
                    <?php endif; ?>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Effective Date</label>
                        <div class="input-group">
                            <input type="date" name="effective_date" id="editEffDate" class="form-control" value="<?= htmlspecialchars($agr['effective_date'] ?? '') ?>" <?= $agr['effective_date_na'] ? 'disabled' : '' ?>>
                            <div class="input-group-text">
                                <input class="form-check-input mt-0 me-1" type="checkbox" name="effective_date_na" id="editEffNa" <?= $agr['effective_date_na'] ? 'checked' : '' ?> onchange="document.getElementById('editEffDate').disabled = this.checked;">
                                <label class="small mb-0" for="editEffNa">Set N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-danger">Expiry Date</label>
                        <div class="input-group">
                            <input type="date" name="expiry_date" id="editExpDate" class="form-control border-danger" value="<?= htmlspecialchars($agr['expiry_date'] ?? '') ?>" <?= $isExpNa ? 'disabled' : 'required' ?>>
                            <div class="input-group-text">
                                <input class="form-check-input mt-0 me-1" type="checkbox" name="expiry_date_na" id="editExpNa" <?= $isExpNa ? 'checked' : '' ?> onchange="const el=document.getElementById('editExpDate'); el.disabled=this.checked; el.required=!this.checked;">
                                <label class="small mb-0" for="editExpNa">Set N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Note (Riwayat Catatan)</label>
                        <textarea name="note" class="form-control" rows="4"><?= htmlspecialchars($agr['note'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Kolom Created By (Riwayat Singkatan &amp; Tgl)</label>
                        <textarea name="created_by_log" class="form-control font-monospace fw-bold" rows="4"><?= htmlspecialchars($agr['created_by_log']) ?></textarea>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="append_my_stamp" id="appendStampCheck" checked>
                            <label class="form-check-label small" for="appendStampCheck">
                                Tambahkan tanda hari ini: <strong><?= format_created_by_stamp($user['kode_singkatan']) ?></strong>
                            </label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('?page=agreements&dept=' . urlencode($agr['department']) . '&doc_type=' . urlencode($docType)) ?>" class="btn btn-light border px-4">Batal</a>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
