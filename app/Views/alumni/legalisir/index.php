<?php
$statusOptions = is_array($statusOptions ?? null) ? $statusOptions : [];
$bolehMengajukan = (bool) ($bolehMengajukan ?? true);
$alasanBlokir = (string) ($alasanBlokir ?? '');
$pengajuan = is_array($pengajuan ?? null) ? $pengajuan : [];
$pengajuanTerbaru = $pengajuan[0] ?? null;

/*
| Helper tampilan status legalisir. Badge dipakai di tabel riwayat,
| sedangkan alert dipakai untuk ringkasan pengajuan terbaru.
*/
$badgeClass = static function (string $status): string {
    return match ($status) {
        'diproses' => 'badge-light-primary',
        'selesai' => 'badge-light-success',
        'ditolak' => 'badge-light-danger',
        default => 'badge-light-warning',
    };
};
$alertClass = static function (string $status): string {
    return match ($status) {
        'diproses' => 'alert-primary',
        'selesai' => 'alert-success',
        'ditolak' => 'alert-danger',
        default => 'alert-warning',
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
        <?php if (is_array($pengajuanTerbaru)): ?>
            <?php $statusTerbaru = (string) ($pengajuanTerbaru['status'] ?? 'diajukan'); ?>
            <div class="alert <?= esc($alertClass($statusTerbaru)) ?> d-flex align-items-start gap-3 mb-8">
                <i class="ki-duotone ki-information-5 fs-2hx">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                <div class="flex-grow-1">
                    <div class="fw-bold mb-1">Status pengajuan terbaru: <?= esc($statusOptions[$statusTerbaru] ?? ucfirst($statusTerbaru)) ?></div>
                    <div class="text-gray-700">
                        <?= esc((string) ($pengajuanTerbaru['jenis_dokumen'] ?? '-')) ?>,
                        <?= (int) ($pengajuanTerbaru['jumlah_lembar'] ?? 0) ?> lembar,
                        diajukan pada <?= esc($formatTanggal($pengajuanTerbaru['dibuat_pada'] ?? null)) ?>.
                    </div>
                    <?php if (trim((string) ($pengajuanTerbaru['catatan_admin'] ?? '')) !== ''): ?>
                        <div class="mt-3 p-3 bg-white bg-opacity-75 rounded border border-gray-300">
                            <div class="fw-bold fs-7 mb-1">Catatan admin</div>
                            <div><?= esc((string) $pengajuanTerbaru['catatan_admin']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

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
                        <?php if (! $bolehMengajukan): ?>
                            <div class="alert alert-warning">
                                <div class="fw-bold mb-1">Pengajuan belum bisa dikirim</div>
                                <div><?= esc($alasanBlokir) ?></div>
                                <div class="d-flex flex-wrap gap-2 mt-4">
                                    <a href="<?= site_url('alumni/profil') ?>" class="btn btn-sm btn-light-primary">Lengkapi Profil</a>
                                    <a href="<?= site_url('alumni/tracer') ?>" class="btn btn-sm btn-light-success">Tracer</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="<?= site_url('alumni/legalisir/simpan') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-5">
                                <label class="form-label required">Jenis Dokumen</label>
                                <select name="jenis_dokumen" class="form-select form-select-solid" required <?= $bolehMengajukan ? '' : 'disabled' ?>>
                                    <option value="">Pilih dokumen</option>
                                    <?php foreach (['Ijazah', 'SKL', 'Transkrip Nilai', 'Rapor'] as $dokumen): ?>
                                        <option value="<?= esc($dokumen, 'attr') ?>" <?= old('jenis_dokumen') === $dokumen ? 'selected' : '' ?>><?= esc($dokumen) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-5">
                                <label class="form-label required">Jumlah Lembar</label>
                                <input type="number" name="jumlah_lembar" min="1" value="<?= esc((string) old('jumlah_lembar', '1'), 'attr') ?>" class="form-control form-control-solid" required <?= $bolehMengajukan ? '' : 'disabled' ?>>
                            </div>
                            <div class="mb-6">
                                <label class="form-label">Keperluan</label>
                                <textarea name="keperluan" rows="4" class="form-control form-control-solid" placeholder="Contoh: persyaratan pendaftaran kerja" <?= $bolehMengajukan ? '' : 'disabled' ?>><?= esc((string) old('keperluan')) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" <?= $bolehMengajukan ? '' : 'disabled' ?>>Kirim Pengajuan</button>
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
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-700">
                                    <?php if ($pengajuan === []): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-8">Belum ada pengajuan legalisir.</td></tr>
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
                                                <td class="text-end">
                                                    <?php if ($status === 'diajukan'): ?>
                                                        <form method="post" action="<?= site_url('alumni/legalisir/hapus/' . (int) $item['id_pengajuan_legalisir']) ?>" class="d-inline js-hapus-legalisir-form">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn btn-sm btn-light-danger">Hapus</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted fs-7">Tidak tersedia</span>
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
