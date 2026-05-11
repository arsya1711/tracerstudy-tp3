<?php
/*
|-------------------------------------------------------------------
| VIEW RIWAYAT LAMARAN PELAMAR
|-------------------------------------------------------------------
| Halaman ini menampilkan semua lamaran yang pernah diajukan pelamar
| beserta status proses terkininya. Dari halaman ini pelamar dapat
| membuka detail untuk membaca catatan admin/HRD dan review dokumen.
|
| Tips Debugging:
| - Jika data kosong, cek tb_lamaran berdasarkan id_pelamar login.
| - Jika tombol detail 404, cek route pelamar/lamaran/(:num).
*/

$formatTanggal = static function (?string $tanggal, bool $pakaiJam = false): string {
    if ($tanggal === null || trim($tanggal) === '') {
        return '-';
    }

    try {
        return (new DateTime($tanggal))->format($pakaiJam ? 'd M Y, H:i' : 'd M Y');
    } catch (Throwable) {
        return (string) $tanggal;
    }
};

$statusBadge = static function (?string $status): array {
    return match ((string) $status) {
        'menunggu_verifikasi'    => ['badge badge-light-warning', 'Menunggu Verifikasi'],
        'perlu_perbaikan_berkas' => ['badge badge-light-danger', 'Perlu Perbaikan'],
        'diproses'               => ['badge badge-light-info', 'Diproses'],
        'wawancara'              => ['badge badge-light-primary', 'Wawancara'],
        'diterima'               => ['badge badge-light-success', 'Diterima'],
        'ditolak'                => ['badge badge-light-danger', 'Ditolak'],
        'mengundurkan_diri'      => ['badge badge-light-dark', 'Mengundurkan Diri'],
        default                  => ['badge badge-light-secondary', '-'],
    };
};
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Riwayat Lamaran</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= base_url('pelamar/dashboard') ?>" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Riwayat Lamaran</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success mb-8"><?= esc((string) session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger mb-8"><?= esc((string) session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <form method="get" action="<?= site_url('pelamar/lamaran') ?>" class="d-flex flex-stack flex-wrap gap-4 w-100">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" name="q" class="form-control form-control-solid w-250px ps-13" placeholder="Cari lowongan atau DUDI" value="<?= esc((string) $keyword, 'attr') ?>" />
                        </div>
                    </div>

                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end gap-3">
                            <select name="status" class="form-select form-select-solid w-225px" data-control="select2" data-hide-search="true" data-placeholder="Filter status">
                                <option value="">Semua Status</option>
                                <?php foreach ($daftarStatus as $value => $label): ?>
                                    <option value="<?= esc($value, 'attr') ?>" <?= $statusFilter === $value ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="<?= site_url('pelamar/lamaran') ?>" class="btn btn-light">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-250px">Lowongan</th>
                                <th class="min-w-180px">DUDI</th>
                                <th class="min-w-160px">Tanggal Melamar</th>
                                <th class="min-w-160px">Status</th>
                                <th class="text-end min-w-120px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            <?php if ($lamaran === []): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-10">
                                        Belum ada riwayat lamaran. Mulai dari halaman lowongan kerja.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lamaran as $item): ?>
                                    <?php [$badgeClass, $badgeLabel] = $statusBadge($item['status'] ?? null); ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="text-gray-900 fw-bold"><?= esc((string) ($item['judul_lowongan'] ?? '-')) ?></span>
                                                <span class="text-muted fs-7"><?= esc((string) ($item['posisi'] ?? '-')) ?></span>
                                            </div>
                                        </td>
                                        <td><?= esc((string) ($item['nama_perusahaan'] ?? '-')) ?></td>
                                        <td><?= esc($formatTanggal($item['tanggal_melamar'] ?? null, true)) ?></td>
                                        <td>
                                            <span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span>
                                            <?php if (($item['status'] ?? '') === 'perlu_perbaikan_berkas' && ! empty($item['batas_perbaikan_berkas'])): ?>
                                                <div class="text-danger fs-8 mt-2">Batas perbaikan: <?= esc($formatTanggal($item['batas_perbaikan_berkas'])) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= site_url('pelamar/lamaran/' . (int) $item['id_lamaran']) ?>" class="btn btn-icon btn-active-light-primary w-30px h-30px" title="Detail Lamaran">
                                                <i class="ki-duotone ki-eye fs-3">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                </i>
                                            </a>
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
