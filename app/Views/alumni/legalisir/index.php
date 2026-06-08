<?php
$statusOptions = is_array($statusOptions ?? null) ? $statusOptions : [];
$badgeClass = static function (string $status): string {
    return match ($status) {
        'diproses' => 'badge-light-primary',
        'selesai' => 'badge-light-success',
        'ditolak' => 'badge-light-danger',
        default => 'badge-light-warning',
    };
};
$formatTanggal = static function (?string $tanggal): string {
    if ($tanggal === null || trim($tanggal) === '') {
        return '-';
    }

    try {
        return (new DateTime($tanggal))->format('d M Y, H:i');
    } catch (Throwable $e) {
        return (string) $tanggal;
    }
};
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Pengajuan Legalisir</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Alumni</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <div class="row g-5 g-xl-8">
            <div class="col-xl-4">
                <div class="card card-flush">
                    <div class="card-header pt-7">
                        <h3 class="card-title fw-bolder">Ajukan Legalisir</h3>
                    </div>
                    <div class="card-body">
                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger"><?= esc((string) session()->getFlashdata('error')) ?></div>
                        <?php endif; ?>
                        <?php if (session()->getFlashdata('sukses')): ?>
                            <div class="alert alert-success"><?= esc((string) session()->getFlashdata('sukses')) ?></div>
                        <?php endif; ?>
                        <form method="post" action="<?= site_url('alumni/legalisir/simpan') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-5">
                                <label class="form-label required">Jenis Dokumen</label>
                                <select name="jenis_dokumen" class="form-select form-select-solid" required>
                                    <option value="">Pilih dokumen</option>
                                    <?php foreach (['Ijazah', 'SKL', 'Transkrip Nilai', 'Rapor'] as $dokumen): ?>
                                        <option value="<?= esc($dokumen, 'attr') ?>" <?= old('jenis_dokumen') === $dokumen ? 'selected' : '' ?>><?= esc($dokumen) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-5">
                                <label class="form-label required">Jumlah Lembar</label>
                                <input type="number" name="jumlah_lembar" min="1" value="<?= esc((string) old('jumlah_lembar', '1'), 'attr') ?>" class="form-control form-control-solid" required>
                            </div>
                            <div class="mb-6">
                                <label class="form-label">Keperluan</label>
                                <textarea name="keperluan" rows="4" class="form-control form-control-solid" placeholder="Contoh: persyaratan pendaftaran kerja"><?= esc((string) old('keperluan')) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Kirim Pengajuan</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card card-flush">
                    <div class="card-header pt-7">
                        <h3 class="card-title fw-bolder">Riwayat Pengajuan</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-4">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                        <th>Dokumen</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Diajukan</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-700">
                                    <?php if (($pengajuan ?? []) === []): ?>
                                        <tr><td colspan="5" class="text-center text-muted py-8">Belum ada pengajuan legalisir.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($pengajuan as $item): ?>
                                            <?php $status = (string) ($item['status'] ?? 'diajukan'); ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-gray-900"><?= esc((string) ($item['jenis_dokumen'] ?? '-')) ?></div>
                                                    <div class="text-muted fs-7"><?= esc((string) ($item['keperluan'] ?? '-')) ?></div>
                                                </td>
                                                <td><?= (int) ($item['jumlah_lembar'] ?? 0) ?> lembar</td>
                                                <td><span class="badge <?= $badgeClass($status) ?>"><?= esc($statusOptions[$status] ?? ucfirst($status)) ?></span></td>
                                                <td><?= esc($formatTanggal($item['dibuat_pada'] ?? null)) ?></td>
                                                <td><?= esc((string) (($item['catatan_admin'] ?? '') !== '' ? $item['catatan_admin'] : '-')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
