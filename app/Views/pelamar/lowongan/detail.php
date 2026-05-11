<?php
/*
|-------------------------------------------------------------------
| VIEW DETAIL LOWONGAN PELAMAR
|-------------------------------------------------------------------
| Halaman ini menampilkan informasi lengkap lowongan dan form submit
| lamaran dengan dokumen yang sifatnya spesifik per perusahaan.
|
| Tips Debugging:
| - Jika form submit tidak muncul, cek apakah pelamar sudah pernah
|   melamar atau kelengkapan profil masih belum lengkap.
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

$statusLamaranLabel = static function (?string $status): array {
    return match ((string) $status) {
        'menunggu_verifikasi'     => ['badge badge-light-warning', 'Menunggu Verifikasi'],
        'perlu_perbaikan_berkas'  => ['badge badge-light-danger', 'Perlu Perbaikan Berkas'],
        'diproses'                => ['badge badge-light-info', 'Diproses'],
        'wawancara'               => ['badge badge-light-primary', 'Wawancara'],
        'diterima'                => ['badge badge-light-success', 'Diterima'],
        'ditolak'                 => ['badge badge-light-danger', 'Ditolak'],
        'mengundurkan_diri'       => ['badge badge-light-dark', 'Mengundurkan Diri'],
        default                   => ['badge badge-light-secondary', 'Belum Melamar'],
    };
};

$flyerUrl = ! empty($lowongan['flyer_lowongan']) ? base_url((string) $lowongan['flyer_lowongan']) : base_url('assets/media/svg/files/blank-image.svg');
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('extra_css') ?>
<style>
    .kt-lowongan-detail-flyer {
        border-radius: 1rem;
        overflow: hidden;
        background: #f8fafc;
    }

    .kt-lowongan-detail-flyer img {
        width: 100%;
        max-height: 420px;
        object-fit: cover;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Detail Lowongan</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= base_url('pelamar/dashboard') ?>" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">
                    <a href="<?= base_url('pelamar/lowongan') ?>" class="text-muted text-hover-primary">Lowongan</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Detail</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger mb-8"><?= esc((string) session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success mb-8"><?= esc((string) session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <div class="row g-5 g-xl-8">
            <div class="col-xl-8">
                <div class="card mb-8">
                    <div class="card-body">
                        <div class="kt-lowongan-detail-flyer mb-6">
                            <img src="<?= esc($flyerUrl) ?>" alt="<?= esc((string) ($lowongan['judul_lowongan'] ?? 'Lowongan')) ?>">
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <span class="badge badge-light-primary"><?= esc((string) ($lowongan['posisi'] ?? '-')) ?></span>
                            <span class="badge badge-light-info"><?= esc(ucfirst((string) ($lowongan['jenis_pekerjaan'] ?? '-'))) ?></span>
                            <span class="badge badge-light-success"><?= esc(ucfirst((string) ($lowongan['sistem_kerja'] ?? '-'))) ?></span>
                            <?php if (! empty($lowongan['pendidikan_min'])): ?>
                                <span class="badge badge-light-warning">Min. <?= esc((string) $lowongan['pendidikan_min']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="fs-2 fw-bold text-gray-900 mb-2"><?= esc((string) ($lowongan['judul_lowongan'] ?? '-')) ?></div>
                        <div class="text-muted fs-6 mb-6"><?= esc((string) ($lowongan['nama_perusahaan'] ?? '-')) ?> • <?= esc((string) (($lowongan['lokasi_kerja'] ?? '') !== '' ? $lowongan['lokasi_kerja'] : ($lowongan['kota'] ?? '-'))) ?></div>

                        <div class="row g-5 mb-6">
                            <div class="col-md-6">
                                <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Batas Lamaran</div>
                                <div class="fw-semibold text-gray-800"><?= esc($formatTanggal($lowongan['batas_lamaran'] ?? null)) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Jumlah Kebutuhan</div>
                                <div class="fw-semibold text-gray-800"><?= esc((string) (($lowongan['jumlah_kebutuhan'] ?? '') !== '' ? $lowongan['jumlah_kebutuhan'] : '-')) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Pengalaman Minimum</div>
                                <div class="fw-semibold text-gray-800"><?= esc((string) (($lowongan['pengalaman_min'] ?? '') !== '' ? $lowongan['pengalaman_min'] : '-')) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Rentang Gaji</div>
                                <div class="fw-semibold text-gray-800"><?= esc((string) (($lowongan['rentang_gaji'] ?? '') !== '' ? $lowongan['rentang_gaji'] : '-')) ?></div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <div class="fs-5 fw-bold text-gray-900 mb-2">Deskripsi Pekerjaan</div>
                            <div class="text-gray-700 fs-6"><?= nl2br(esc((string) (($lowongan['deskripsi_pekerjaan'] ?? '') !== '' ? $lowongan['deskripsi_pekerjaan'] : '-'))) ?></div>
                        </div>

                        <div>
                            <div class="fs-5 fw-bold text-gray-900 mb-2">Kualifikasi</div>
                            <div class="text-gray-700 fs-6"><?= nl2br(esc((string) (($lowongan['kualifikasi'] ?? '') !== '' ? $lowongan['kualifikasi'] : '-'))) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card mb-8">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title flex-column">
                            <h2 class="mb-1">Ajukan Lamaran</h2>
                            <div class="text-muted fw-semibold fs-6">Dokumen di form ini khusus untuk lowongan <?= esc((string) ($lowongan['nama_perusahaan'] ?? '-')) ?>.</div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <?php if ($lamaranSaya !== null): ?>
                            <?php [$badgeClass, $badgeLabel] = $statusLamaranLabel($lamaranSaya['status'] ?? null); ?>
                            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5">
                                <div class="fw-semibold">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span>
                                    </div>
                                    <div class="text-gray-700 fs-7 mb-2">Kamu sudah pernah mengajukan lamaran untuk lowongan ini.</div>
                                    <div class="text-muted fs-8">Tanggal melamar: <?= esc($formatTanggal($lamaranSaya['tanggal_melamar'] ?? null, true)) ?></div>
                                    <a href="<?= site_url('pelamar/lamaran/' . (int) $lamaranSaya['id_lamaran']) ?>" class="btn btn-sm btn-light-primary mt-4">Buka Detail Lamaran</a>
                                </div>
                            </div>
                        <?php elseif (! ($berkasProfilInfo['siapMelamar'] ?? false)): ?>
                            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-5">
                                <div class="fw-semibold text-gray-700">
                                    <div class="fw-bold mb-2">Berkas profil wajib belum lengkap.</div>
                                    <div class="fs-7 mb-3">Lengkapi dulu dokumen profil umum sebelum mengirim lamaran.</div>
                                    <ul class="ps-4 mb-4">
                                        <?php foreach (($berkasProfilInfo['belumLengkap'] ?? []) as $item): ?>
                                            <li><?= esc((string) ($item['nama_berkas'] ?? 'Berkas')) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <a href="<?= site_url('pelamar/profil') ?>" class="btn btn-sm btn-warning">Lengkapi Profil</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form action="<?= site_url('pelamar/lowongan/lamar/' . (int) $lowongan['id_lowongan']) ?>" method="post" enctype="multipart/form-data">
                                <?= csrf_field() ?>
                                <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-5 mb-6">
                                    <div class="fw-semibold text-gray-700 fs-7">
                                        Gunakan CV dan surat lamaran yang sudah disesuaikan dengan nama perusahaan ini agar proses review HRD lebih rapi dan profesional.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label class="required form-label fw-semibold">CV</label>
                                    <input type="file" name="cv_file" class="form-control form-control-solid" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <div class="text-muted fs-8 mt-2">Format: pdf, jpg, jpeg, png. Maksimal 5 MB.</div>
                                </div>

                                <div class="mb-5">
                                    <label class="required form-label fw-semibold">Surat Lamaran</label>
                                    <input type="file" name="surat_lamaran_file" class="form-control form-control-solid" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <div class="text-muted fs-8 mt-2">Pastikan nama perusahaan di surat lamaran sesuai dengan lowongan ini.</div>
                                </div>

                                <div class="mb-6">
                                    <label class="form-label fw-semibold">Portofolio</label>
                                    <input type="file" name="portofolio_file" class="form-control form-control-solid" accept=".pdf,.jpg,.jpeg,.png">
                                    <div class="text-muted fs-8 mt-2">Opsional. Gunakan jika lowongan membutuhkan contoh karya atau proyek.</div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Kirim Lamaran</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="fs-5 fw-bold text-gray-900 mb-4">Checklist Sebelum Submit</div>
                        <div class="d-flex flex-column gap-4">
                            <div>
                                <div class="fw-semibold text-gray-800">1. Berkas profil lengkap</div>
                                <div class="text-muted fs-7">KTP, ijazah, pas foto, dan dokumen umum lain sudah tersedia.</div>
                            </div>
                            <div>
                                <div class="fw-semibold text-gray-800">2. CV spesifik lowongan</div>
                                <div class="text-muted fs-7">Gunakan CV versi terbaru yang relevan dengan posisi yang dilamar.</div>
                            </div>
                            <div>
                                <div class="fw-semibold text-gray-800">3. Surat lamaran sesuai perusahaan</div>
                                <div class="text-muted fs-7">Cek ulang nama perusahaan agar tidak tertukar dengan lamaran sebelumnya.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
