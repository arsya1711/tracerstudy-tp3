<?php
/*
|-------------------------------------------------------------------
| VIEW DASHBOARD PELAMAR
|-------------------------------------------------------------------
| View ini menjadi halaman awal setelah pelamar login. Fokusnya
| adalah memberi ringkasan singkat agar pelamar cepat tahu status
| akun, kesiapan berkas profil, dan jumlah lamaran.
|
| Tips Debugging:
| - Jika data ringkasan kosong, periksa payload dari controller index().
*/
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$statusPendaftaran = (string) ($pelamar['status_pendaftaran'] ?? '');
$menungguPersetujuan = $statusPendaftaran !== '' && $statusPendaftaran !== 'aktif';
$labelStatusPendaftaran = $statusPendaftaran !== '' ? ucwords(str_replace('_', ' ', $statusPendaftaran)) : 'Belum Diketahui';
$kelasStatusPendaftaran = $menungguPersetujuan ? 'badge badge-light-warning' : 'badge badge-light-success';
?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Dashboard Pelamar</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= base_url('pelamar/dashboard') ?>" class="text-muted text-hover-primary">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Dashboard</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <div class="card mb-8">
            <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                <div>
                    <div class="fs-2 fw-bold text-gray-900 mb-2">Selamat datang, <?= esc((string) ($pelamar['nama_lengkap'] ?? 'Pelamar')) ?></div>
                    <div class="text-muted fs-6">
                        <?php if ($menungguPersetujuan): ?>
                            Akun kamu sudah berhasil dibuat dan saat ini sedang menunggu persetujuan admin BKK. Selama masa review, hanya dashboard dan lini masa ini yang dapat diakses.
                        <?php else: ?>
                            Lengkapi berkas profil umum terlebih dahulu. CV, surat lamaran, dan portofolio akan disiapkan saat kamu benar-benar mengajukan lamaran.
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <?php if (! $menungguPersetujuan): ?>
                        <a href="<?= base_url('pelamar/lowongan') ?>" class="btn btn-light-primary">Cari Lowongan</a>
                        <a href="<?= base_url('pelamar/lamaran') ?>" class="btn btn-light-info">Riwayat Lamaran</a>
                        <a href="<?= base_url('pelamar/profil') ?>" class="btn btn-primary">Buka Profil</a>
                    <?php endif; ?>
                    <a href="<?= base_url('logout') ?>" class="btn btn-light-danger">Logout</a>
                </div>
            </div>
        </div>

        <?php if ($menungguPersetujuan): ?>
            <div class="alert alert-warning d-flex align-items-start gap-3 mb-8">
                <i class="ki-duotone ki-information-5 fs-2hx text-warning">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                <div>
                    <div class="fw-bold mb-1">Akun menunggu review admin BKK</div>
                    <div class="text-gray-700 fs-6">Setelah admin menyetujui keanggotaan kamu sebagai pelamar umum atau alumni, menu profil, lowongan, dan riwayat lamaran akan terbuka otomatis.</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-5 g-xl-8 mb-8">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Status Persetujuan</div>
                        <div class="mb-2"><span class="<?= $kelasStatusPendaftaran ?> fs-7"><?= esc($labelStatusPendaftaran) ?></span></div>
                        <div class="text-gray-600 fs-7">Persetujuan admin BKK menentukan kapan menu pelamar lain dapat mulai digunakan.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Berkas Profil</div>
                        <div class="fs-2hx fw-bold text-gray-900 mb-2"><?= (int) ($ringkasanBerkas['uploaded'] ?? 0) ?>/<?= (int) ($ringkasanBerkas['total'] ?? 0) ?></div>
                        <div class="text-gray-600 fs-7">Dokumen umum yang sudah siap dipakai sebelum masuk ke alur melamar.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Riwayat Lamaran</div>
                        <div class="fs-2hx fw-bold text-gray-900 mb-2"><?= count($lamaran) ?></div>
                        <div class="text-gray-600 fs-7">Lamaran yang pernah kamu ajukan ke lowongan aktif di sistem BKK.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column">
                    <h2 class="mb-1">Checklist Awal</h2>
                    <div class="text-muted fw-semibold fs-6">Urutan yang disarankan sebelum kamu mulai melamar.</div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="timeline timeline-border-dashed">
                    <div class="timeline-item">
                        <div class="timeline-line"></div>
                        <div class="timeline-icon">
                            <i class="ki-duotone ki-profile-circle fs-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </div>
                        <div class="timeline-content mb-10 mt-n2">
                            <div class="overflow-auto pe-3">
                                <div class="fs-5 fw-bold mb-2">Periksa data akun dan profil</div>
                                <div class="text-gray-600 fs-7">
                                    <?php if ($menungguPersetujuan): ?>
                                        Admin BKK sedang meninjau status keanggotaan akun kamu sebelum akses pelamar dibuka penuh.
                                    <?php else: ?>
                                        Pastikan email, nomor telepon, dan identitas dasar sudah benar.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-line"></div>
                        <div class="timeline-icon">
                            <i class="ki-duotone ki-document fs-2 text-success">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                        <div class="timeline-content mb-10 mt-n2">
                            <div class="overflow-auto pe-3">
                                <div class="fs-5 fw-bold mb-2">Lengkapi Berkas Profil</div>
                                <div class="text-gray-600 fs-7">
                                    <?php if ($menungguPersetujuan): ?>
                                        Tahap ini akan terbuka setelah akun kamu disetujui. Untuk sementara, pantau status review dari dashboard ini.
                                    <?php else: ?>
                                        Unggah KTP, ijazah, pas foto, dan dokumen umum lain di halaman profil pelamar.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-line"></div>
                        <div class="timeline-icon">
                            <i class="ki-duotone ki-send fs-2 text-warning">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                        <div class="timeline-content mt-n2">
                            <div class="overflow-auto pe-3">
                                <div class="fs-5 fw-bold mb-2">Ajukan lamaran per lowongan</div>
                                <div class="text-gray-600 fs-7">
                                    <?php if ($menungguPersetujuan): ?>
                                        Setelah akun aktif, kamu bisa mulai membuka lowongan dan mengirim lamaran sesuai posisi yang diinginkan.
                                    <?php else: ?>
                                        CV, surat lamaran, dan portofolio akan dilampirkan khusus pada lamaran yang kamu pilih.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
