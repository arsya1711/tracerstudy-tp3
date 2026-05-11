<?php
/*
|-------------------------------------------------------------------
| VIEW DAFTAR LOWONGAN PELAMAR
|-------------------------------------------------------------------
| Halaman ini menampilkan lowongan aktif yang masih bisa dilamar oleh
| pelamar. Fokus tampilannya dibuat ringkas agar pelamar bisa cepat
| memindai perusahaan, posisi, dan deadline lamaran.
|
| Tips Debugging:
| - Jika daftar kosong padahal lowongan ada, cek status lowongan
|   harus `aktif` dan batas_lamaran belum lewat.
*/

$formatTanggal = static function (?string $tanggal): string {
    if ($tanggal === null || trim($tanggal) === '') {
        return '-';
    }

    try {
        return (new DateTime($tanggal))->format('d M Y');
    } catch (Throwable) {
        return (string) $tanggal;
    }
};

$statusLamaranLabel = static function (?string $status): array {
    return match ((string) $status) {
        'menunggu_verifikasi'     => ['badge badge-light-warning', 'Menunggu Verifikasi'],
        'perlu_perbaikan_berkas'  => ['badge badge-light-danger', 'Perlu Perbaikan'],
        'diproses'                => ['badge badge-light-info', 'Diproses'],
        'wawancara'               => ['badge badge-light-primary', 'Wawancara'],
        'diterima'                => ['badge badge-light-success', 'Diterima'],
        'ditolak'                 => ['badge badge-light-danger', 'Ditolak'],
        'mengundurkan_diri'       => ['badge badge-light-dark', 'Mengundurkan Diri'],
        default                   => ['badge badge-light-secondary', 'Belum Melamar'],
    };
};

$blankFlyerUrl = base_url('assets/media/svg/files/blank-image.svg');
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('extra_css') ?>
<style>
    .kt-pelamar-lowongan-card {
        border: 1px solid var(--bs-gray-200);
        border-radius: 1rem;
        overflow: hidden;
        height: 100%;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .kt-pelamar-lowongan-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
    }

    .kt-pelamar-lowongan-thumb {
        height: 180px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .kt-pelamar-lowongan-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Lowongan Kerja</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= base_url('pelamar/dashboard') ?>" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Lowongan</li>
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

        <?php if (! ($berkasProfilInfo['siapMelamar'] ?? false)): ?>
            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6 mb-8">
                <div class="fw-semibold text-gray-800">
                    <div class="fw-bold mb-2">Berkas profil wajib belum lengkap.</div>
                    <div class="fs-7 mb-3">Lengkapi dulu dokumen umum seperti KTP, ijazah, atau pas foto sebelum melamar.</div>
                    <a href="<?= site_url('pelamar/profil') ?>" class="btn btn-sm btn-warning">Lengkapi Profil</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mb-8">
            <div class="card-body">
                <form method="get" action="<?= site_url('pelamar/lowongan') ?>" class="d-flex flex-column flex-lg-row gap-4 align-items-lg-center justify-content-between">
                    <div>
                        <div class="fs-3 fw-bold text-gray-900 mb-1">Lowongan Aktif</div>
                        <div class="text-muted fs-6">Cari lowongan yang sesuai lalu buka detailnya untuk melamar.</div>
                    </div>
                    <div class="d-flex gap-3 w-100 w-lg-auto">
                        <input type="text" name="q" class="form-control form-control-solid w-lg-300px" placeholder="Cari perusahaan, posisi, atau lokasi" value="<?= esc($keyword) ?>">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-5 g-xl-8">
            <?php if ($lowongan === []): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-15 text-muted">
                            Belum ada lowongan aktif yang cocok ditampilkan saat ini.
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($lowongan as $item): ?>
                    <?php
                    $lamaranItem = $lamaranMap[(int) ($item['id_lowongan'] ?? 0)] ?? null;
                    [$badgeClass, $badgeLabel] = $statusLamaranLabel($lamaranItem['status'] ?? null);
                    $flyerUrl = ! empty($item['flyer_lowongan']) ? base_url((string) $item['flyer_lowongan']) : $blankFlyerUrl;
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="kt-pelamar-lowongan-card bg-white">
                            <div class="kt-pelamar-lowongan-thumb">
                                <img src="<?= esc($flyerUrl) ?>" alt="<?= esc((string) ($item['judul_lowongan'] ?? 'Lowongan')) ?>">
                            </div>
                            <div class="p-6">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                    <div>
                                        <div class="fw-bold text-gray-900 fs-5 mb-1"><?= esc((string) ($item['judul_lowongan'] ?? '-')) ?></div>
                                        <div class="text-muted fs-7"><?= esc((string) ($item['nama_perusahaan'] ?? '-')) ?></div>
                                    </div>
                                    <?php if ($lamaranItem !== null): ?>
                                        <span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    <span class="badge badge-light-primary"><?= esc((string) ($item['posisi'] ?? '-')) ?></span>
                                    <span class="badge badge-light-info"><?= esc(ucfirst((string) ($item['jenis_pekerjaan'] ?? '-'))) ?></span>
                                    <span class="badge badge-light-success"><?= esc(ucfirst((string) ($item['sistem_kerja'] ?? '-'))) ?></span>
                                </div>

                                <div class="text-muted fs-7 mb-2">Lokasi: <?= esc((string) (($item['lokasi_kerja'] ?? '') !== '' ? $item['lokasi_kerja'] : ($item['kota'] ?? '-'))) ?></div>
                                <div class="text-muted fs-7 mb-5">Batas Lamaran: <?= esc($formatTanggal($item['batas_lamaran'] ?? null)) ?></div>

                                <a href="<?= $lamaranItem !== null ? site_url('pelamar/lamaran/' . (int) $lamaranItem['id_lamaran']) : site_url('pelamar/lowongan/' . (string) ($item['slug_lowongan'] ?? '')) ?>" class="btn btn-sm btn-primary">
                                    <?= $lamaranItem !== null ? 'Lihat Detail Lamaran' : 'Lihat Detail & Melamar' ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
