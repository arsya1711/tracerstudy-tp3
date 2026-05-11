<?php
/*
|-------------------------------------------------------------------
| VIEW DASHBOARD ADMIN DUDI / HRD
|-------------------------------------------------------------------
| Halaman ini merangkum kondisi lowongan dan lamaran yang masuk ke
| perusahaan milik akun Admin DUDI/HRD.
|
| Alur kerja:
| 1. Controller mengirim data perusahaan, ringkasan lowongan, dan
|    ringkasan lamaran.
| 2. View menampilkan kartu angka dan daftar lamaran terbaru.
|
| Tips Debugging:
| - Jika nama perusahaan kosong, cek relasi tb_perusahaan.id_pengguna.
| - Jika angka ringkasan nol, cek data tb_lowongan dan tb_lamaran.
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
        'menunggu_verifikasi'    => ['badge badge-light-warning', 'Menunggu'],
        'perlu_perbaikan_berkas' => ['badge badge-light-danger', 'Perbaikan'],
        'diproses'               => ['badge badge-light-info', 'Diproses'],
        'wawancara'              => ['badge badge-light-primary', 'Wawancara'],
        'diterima'               => ['badge badge-light-success', 'Diterima'],
        'ditolak'                => ['badge badge-light-danger', 'Ditolak'],
        default                  => ['badge badge-light-secondary', '-'],
    };
};
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Dashboard Admin DUDI</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted"><?= esc((string) ($perusahaan['nama_perusahaan'] ?? 'Perusahaan')) ?></li>
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

        <div class="card mb-8">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between gap-6">
                <div>
                    <div class="text-muted fw-semibold mb-2">Perusahaan Login</div>
                    <h2 class="fw-bold text-gray-900 mb-2"><?= esc((string) ($perusahaan['nama_perusahaan'] ?? '-')) ?></h2>
                    <div class="text-gray-600"><?= esc((string) (($perusahaan['kota'] ?? '') !== '' ? $perusahaan['kota'] : 'Kota belum diisi')) ?> · <?= esc((string) (($perusahaan['bidang_usaha'] ?? '') !== '' ? $perusahaan['bidang_usaha'] : 'Bidang usaha belum diisi')) ?></div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= site_url('admin-dudi/lowongan') ?>" class="btn btn-light-primary">Lowongan Saya</a>
                    <a href="<?= site_url('admin-dudi/lamaran') ?>" class="btn btn-primary">Lamaran Masuk</a>
                </div>
            </div>
        </div>

        <div class="row g-6 mb-8">
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fw-semibold mb-2">Total Lowongan</div>
                        <div class="fs-2hx fw-bold text-gray-900"><?= (int) ($ringkasanLowongan['total'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fw-semibold mb-2">Lowongan Aktif</div>
                        <div class="fs-2hx fw-bold text-success"><?= (int) ($ringkasanLowongan['aktif'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fw-semibold mb-2">Lamaran Masuk</div>
                        <div class="fs-2hx fw-bold text-primary"><?= (int) ($ringkasanLamaran['total'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fw-semibold mb-2">Perlu Perbaikan</div>
                        <div class="fs-2hx fw-bold text-danger"><?= (int) ($ringkasanLamaran['perlu_perbaikan_berkas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column">
                    <h2 class="mb-1">Lamaran Terbaru</h2>
                    <div class="fs-6 fw-semibold text-muted">Pantau pelamar terbaru yang masuk ke lowongan perusahaan.</div>
                </div>
                <div class="card-toolbar">
                    <a href="<?= site_url('admin-dudi/lamaran') ?>" class="btn btn-sm btn-light-primary">Lihat Semua</a>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>Pelamar</th>
                                <th>Posisi</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            <?php if ($lamaranTerbaru === []): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-10">Belum ada lamaran masuk.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lamaranTerbaru as $item): ?>
                                    <?php [$badgeClass, $badgeLabel] = $statusBadge($item['status'] ?? null); ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?></div>
                                            <div class="text-muted fs-7"><?= esc((string) ($item['email'] ?? '-')) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-gray-900"><?= esc((string) ($item['posisi'] ?? '-')) ?></div>
                                            <div class="text-muted fs-7"><?= esc((string) ($item['judul_lowongan'] ?? '-')) ?></div>
                                        </td>
                                        <td><?= esc($formatTanggal($item['tanggal_melamar'] ?? null, true)) ?></td>
                                        <td><span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span></td>
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
