<?php
$statusOptions = is_array($statusOptions ?? null) ? $statusOptions : [];
$rekapStatus = is_array($rekapStatus ?? null) ? $rekapStatus : [];
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
                <li class="breadcrumb-item text-muted">Administrasi Alumni</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <div class="row g-5 g-xl-8 mb-8">
            <?php foreach ($statusOptions as $key => $label): ?>
                <div class="col-md-6 col-xl-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fw-semibold fs-7 mb-2"><?= esc($label) ?></div>
                            <div class="text-gray-900 fw-bolder fs-2hx"><?= number_format((int) ($rekapStatus[$key] ?? 0), 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card card-flush">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bolder">Daftar Pengajuan Legalisir</h3>
            </div>
            <div class="card-body">
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= esc((string) session()->getFlashdata('error')) ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('sukses')): ?>
                    <div class="alert alert-success"><?= esc((string) session()->getFlashdata('sukses')) ?></div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                <th>Alumni</th>
                                <th>Dokumen</th>
                                <th>Status</th>
                                <th>Diajukan</th>
                                <th class="min-w-300px">Tindakan</th>
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
                                            <div class="fw-bold text-gray-900"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?></div>
                                            <div class="text-muted fs-7">
                                                <?= esc((string) ($item['nis'] ?? '-')) ?> · <?= esc((string) ($item['nama_kompetensi'] ?? '-')) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-gray-900"><?= esc((string) ($item['jenis_dokumen'] ?? '-')) ?></div>
                                            <div class="text-muted fs-7"><?= (int) ($item['jumlah_lembar'] ?? 0) ?> lembar · <?= esc((string) ($item['keperluan'] ?? '-')) ?></div>
                                        </td>
                                        <td><span class="badge <?= $badgeClass($status) ?>"><?= esc($statusOptions[$status] ?? ucfirst($status)) ?></span></td>
                                        <td><?= esc($formatTanggal($item['dibuat_pada'] ?? null)) ?></td>
                                        <td>
                                            <form method="post" action="<?= esc((string) $updateUrl) ?>/<?= (int) $item['id_pengajuan_legalisir'] ?>" class="d-flex gap-2 align-items-start">
                                                <?= csrf_field() ?>
                                                <select name="status" class="form-select form-select-sm form-select-solid w-125px">
                                                    <?php foreach ($statusOptions as $key => $label): ?>
                                                        <option value="<?= esc($key, 'attr') ?>" <?= $status === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="catatan_admin" value="<?= esc((string) ($item['catatan_admin'] ?? ''), 'attr') ?>" class="form-control form-control-sm form-control-solid" placeholder="Catatan admin">
                                                <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                            </form>
                                            <?php if ($status === 'diajukan'): ?>
                                                <form method="post" action="<?= esc((string) $deleteUrl) ?>/<?= (int) $item['id_pengajuan_legalisir'] ?>" class="d-flex justify-content-end mt-2 js-hapus-legalisir-form">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-light-danger">Hapus Pengajuan</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
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
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-hapus-legalisir-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (typeof Swal === 'undefined') {
                if (window.confirm('Hapus pengajuan legalisir ini?')) {
                    form.submit();
                }
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: 'Hapus pengajuan?',
                text: 'Pengajuan legalisir yang dihapus tidak dapat dikembalikan.',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
