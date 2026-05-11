<?php
/*
|-------------------------------------------------------------------
| VIEW DETAIL PELAMAR
|-------------------------------------------------------------------
| View ini mengadaptasi struktur detail Metronic 8.2.0 untuk modul
| Detail Pelamar Super Admin dengan tab riwayat kerja, berkas,
| riwayat lamaran, tracer study, dan kartu anggota.
| Alur kerja: controller mengirim data pelamar lengkap, lalu halaman
| dirender sekali dan aksi tambah/edit/hapus ringan ditangani AJAX.
|
| Tips Debugging:
| - Jika tab kosong, cek data yang dikirim controller detail().
| - Jika modal tidak submit, cek assets/js/custom/pelamar/detail.js.
*/

$fotoPath = trim((string) ($pelamar['foto'] ?: ($pelamar['foto_profil'] ?? '')));
$fotoUrl  = '';
$fotoPreviewUrl = '';

if ($fotoPath !== '') {
    $fotoUrl = str_starts_with($fotoPath, 'uploads/')
        ? base_url($fotoPath)
        : base_url('uploads/foto/' . ltrim($fotoPath, '/'));
    $fotoPreviewUrl = $fotoUrl;
}

$blankImageUrl = base_url('assets/media/avatars/blank.png');
$blankImageDarkUrl = base_url('assets/media/avatars/blank-dark.png');

if ($fotoPreviewUrl === '') {
    $fotoPreviewUrl = $blankImageUrl;
}

$namaLengkap = (string) ($pelamar['nama_lengkap'] ?? '-');
$detailMode = (string) ($detail_mode ?? 'superadmin');
$isSuperadmin = $detailMode === 'superadmin';
$isAdminSekolahView = $detailMode === 'admin_sekolah';
$isPelamarView = $detailMode === 'pelamar';
$isBackofficeView = $isSuperadmin || $isAdminSekolahView;
$backofficePrefix = $isAdminSekolahView ? 'admin-sekolah' : 'superadmin';
$dashboardUrl = $isPelamarView
    ? base_url('pelamar/dashboard')
    : base_url($isAdminSekolahView ? 'admin-sekolah/dashboard' : 'dashboard/superadmin');
$toolbarTitle = $isPelamarView ? 'Profil Pelamar' : 'Detail Pelamar';
$breadcrumbParent = $isPelamarView ? 'Pelamar' : 'Manajemen Pengguna';
$breadcrumbCurrent = $isPelamarView ? 'Profil' : 'Data Pelamar';
$detailSectionTitle = 'Detail Akun';
$accountIdLabel = $isPelamarView ? 'ID Anggota' : 'ID Akun';
$lastLoginLabel = 'Terakhir Login';
$editDetailTitle = $isPelamarView ? 'Edit Profil Saya' : 'Edit Detail Pelamar';
$berkasHeaderTitle = $isPelamarView ? 'Berkas Profil Saya' : 'Berkas Profil';
$berkasHeaderSubtitle = $isPelamarView
    ? 'Kelola dokumen umum yang berlaku untuk profil. CV, surat lamaran, dan portofolio dilampirkan saat melamar lowongan.'
    : 'Kelola dokumen umum milik pelamar. CV, surat lamaran, dan portofolio diproses saat pelamar mengajukan lamaran.';
$berkasNoticeText = $isPelamarView
    ? 'Dokumen di tab ini bersifat umum. Dokumen yang harus menyesuaikan perusahaan atau posisi akan diminta kembali pada alur melamar agar tidak tertukar antar lowongan.'
    : 'Dokumen di tab ini bersifat umum untuk profil pelamar. Dokumen yang sensitif terhadap perusahaan atau lowongan akan dilampirkan per lamaran.';
$potonganNama = preg_split('/\s+/', trim($namaLengkap)) ?: [];
$inisial = '';

foreach (array_slice($potonganNama, 0, 2) as $bagianNama) {
    $inisial .= strtoupper(substr($bagianNama, 0, 1));
}

$inisial = $inisial !== '' ? $inisial : 'PL';

$formatTanggal = static function (?string $tanggal, bool $pakaiJam = false): string {
    if ($tanggal === null || trim($tanggal) === '') {
        return '-';
    }

    try {
        $date = new DateTime($tanggal);
        return $date->format($pakaiJam ? 'd M Y, H:i' : 'd M Y');
    } catch (Throwable) {
        return (string) $tanggal;
    }
};

$statusBadge = static function (?string $status, string $jenis = 'default'): string {
    $status = strtolower((string) $status);

    if ($jenis === 'akun') {
        return $status === '1'
            ? 'badge badge-light-success'
            : 'badge badge-light-danger';
    }

    if ($jenis === 'pendaftaran') {
        return match ($status) {
            'aktif' => 'badge badge-light-success',
            'terdaftar' => 'badge badge-light-info',
            'menunggu_aktivasi' => 'badge badge-light-warning',
            default => 'badge badge-light-secondary',
        };
    }

    if ($jenis === 'verifikasi') {
        return match ($status) {
            'terverifikasi', 'valid', 'disetujui' => 'badge badge-light-success',
            'ditolak', 'tidak_valid' => 'badge badge-light-danger',
            default => 'badge badge-light-warning',
        };
    }

    if ($jenis === 'lamaran') {
        return match ($status) {
            'diterima' => 'badge badge-light-success',
            'diproses', 'seleksi', 'interview' => 'badge badge-light-primary',
            'mengundurkan_diri' => 'badge badge-light-warning',
            'ditolak' => 'badge badge-light-danger',
            default => 'badge badge-light-secondary',
        };
    }

    if ($jenis === 'unggah') {
        return match ($status) {
            'sudah_diunggah', 'uploaded', 'lengkap' => 'badge badge-light-success',
            'pending', 'menunggu' => 'badge badge-light-warning',
            default => 'badge badge-light-secondary',
        };
    }

    return 'badge badge-light-secondary';
};

$statusBerkasMeta = static function (?string $status): array {
    return match (strtolower((string) $status)) {
        'sudah_diunggah' => ['badge badge-light-success', 'Sudah Diunggah', 'Dokumen sudah tersimpan dan siap ditinjau.'],
        'ditolak'        => ['badge badge-light-danger', 'Ditolak', 'Dokumen perlu diperbaiki lalu diunggah ulang.'],
        default          => ['badge badge-light-warning', 'Belum Diunggah', 'Dokumen belum tersedia untuk pelamar ini.'],
    };
};

$statusTracerMeta = static function (?string $status): array {
    return match (strtolower((string) $status)) {
        'terkirim'      => ['badge badge-light-primary', 'Terkirim'],
        'terverifikasi' => ['badge badge-light-info', 'Terverifikasi'],
        'disetujui'     => ['badge badge-light-success', 'Disetujui'],
        default         => ['badge badge-light-secondary', 'Draft'],
    };
};

$slugBerkasKhususLamaran = ['cv', 'surat_lamaran', 'portofolio'];
$filterBerkasProfil = static function (array $item) use ($slugBerkasKhususLamaran): bool {
    return ! in_array(strtolower((string) ($item['slug_berkas'] ?? '')), $slugBerkasKhususLamaran, true);
};

$berkas = array_values(array_filter($berkas, $filterBerkasProfil));
$jenis_berkas = array_values(array_filter($jenis_berkas, $filterBerkasProfil));
$jumlahBerkas = count($berkas);
$jumlahBerkasTersedia = count(array_filter($berkas, static fn(array $item): bool => ($item['status_unggah'] ?? '') === 'sudah_diunggah'));
$jumlahBerkasDitolak = count(array_filter($berkas, static fn(array $item): bool => ($item['status_unggah'] ?? '') === 'ditolak'));
$jumlahBerkasWajib = count(array_filter($berkas, static fn(array $item): bool => ! empty($item['wajib'])));
$jumlahBerkasWajibTersedia = count(array_filter($berkas, static fn(array $item): bool => ! empty($item['wajib']) && ($item['status_unggah'] ?? '') === 'sudah_diunggah'));
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('extra_css') ?>
<style>
    .kt-berkas-summary {
        border: 1px dashed var(--bs-gray-300);
        border-radius: 1rem;
        height: 100%;
        padding: 1.25rem;
        background: linear-gradient(180deg, rgba(248, 249, 250, 0.9) 0%, #ffffff 100%);
    }

    .kt-berkas-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .kt-berkas-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
    }

    .kt-berkas-card.is-ready {
        border-color: rgba(80, 205, 137, 0.35) !important;
    }

    .kt-berkas-card.is-pending {
        border-color: rgba(255, 199, 0, 0.35) !important;
    }

    .kt-berkas-card.is-rejected {
        border-color: rgba(241, 65, 108, 0.35) !important;
    }

    .kt-berkas-icon {
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.14) 0%, rgba(13, 110, 253, 0.05) 100%);
        border-radius: 1rem;
    }

    .kt-berkas-actions .btn:not(.btn-icon) {
        min-width: 88px;
    }

    .kt-berkas-actions .btn-icon {
        width: 34px;
        height: 34px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <!--begin::Toolbar container-->
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <!--begin::Page title-->
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <!--begin::Title-->
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0"><?= esc($toolbarTitle) ?></h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <!--begin::Item-->
                <li class="breadcrumb-item text-muted">
                    <a href="<?= $dashboardUrl ?>" class="text-muted text-hover-primary">Home</a>
                </li>
                <!--end::Item-->
                <!--begin::Item-->
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <!--end::Item-->
                <!--begin::Item-->
                <li class="breadcrumb-item text-muted"><?= esc($breadcrumbParent) ?></li>
                <!--end::Item-->
                <!--begin::Item-->
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <!--end::Item-->
                <!--begin::Item-->
                <li class="breadcrumb-item text-muted"><?= esc($breadcrumbCurrent) ?></li>
                <!--end::Item-->
            </ul>
            <!--end::Breadcrumb-->
        </div>
        <!--end::Page title-->
    </div>
    <!--end::Toolbar container-->
</div>
<!--end::Toolbar-->
<!--begin::Content-->
<div id="kt_app_content" class="app-content flex-column-fluid" data-id-pelamar="<?= (int) $pelamar['id_pelamar'] ?>">
    <!--begin::Content container-->
    <div id="kt_app_content_container" class="app-container container-xxl">
        <!--begin::Layout-->
        <div class="d-flex flex-column flex-lg-row">
            <!--begin::Sidebar-->
            <div class="flex-column flex-lg-row-auto w-lg-250px w-xl-350px mb-10">
                <!--begin::Card-->
                <div class="card mb-5 mb-xl-8">
                    <!--begin::Card body-->
                    <div class="card-body">
                        <!--begin::Summary-->
                        <!--begin::User Info-->
                        <div class="d-flex flex-center flex-column py-5">
                            <!--begin::Avatar-->
                            <div class="symbol symbol-100px symbol-circle mb-7">
                                <?php if ($fotoUrl !== ''): ?>
                                    <img src="<?= esc($fotoUrl) ?>" alt="<?= esc($namaLengkap) ?>" />
                                <?php else: ?>
                                    <div class="symbol-label fs-2x fw-bold text-primary bg-light-primary"><?= esc($inisial) ?></div>
                                <?php endif; ?>
                            </div>
                            <!--end::Avatar-->
                            <!--begin::Name-->
                            <a href="#" class="fs-3 text-gray-800 text-hover-primary fw-bold mb-3"><?= esc($namaLengkap) ?></a>
                            <!--end::Name-->
                            <!--begin::Position-->
                            <div class="mb-9">
                                <!--begin::Badge-->
                                <div class="badge badge-lg <?= $isAlumni ? 'badge-light-success' : 'badge-light-primary' ?> d-inline"><?= $isAlumni ? 'Alumni' : 'Umum' ?></div>
                                <!--begin::Badge-->
                            </div>
                            <!--end::Position-->
                            <!--begin::Info-->
                            <?php if ($isAlumni): ?>
                                <!--begin::Info heading-->
                                <div class="fw-bold mb-3">Tracer Summary
                                    <span class="ms-2" ddata-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="Ringkasan singkat data alumni dan tracer study terbaru.">
                                        <i class="ki-duotone ki-information fs-7">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </span>
                                </div>
                                <!--end::Info heading-->
                                <div class="d-flex flex-wrap flex-center">
                                    <!--begin::Stats-->
                                    <div class="border border-gray-300 border-dashed rounded py-3 px-3 mb-3">
                                        <div class="fs-4 fw-bold text-gray-700">
                                            <span class="w-75px"><?= esc((string) ($alumni['tahun_lulus'] ?? '-')) ?></span>
                                        </div>
                                        <div class="fw-semibold text-muted">Tahun Lulus</div>
                                    </div>
                                    <!--end::Stats-->
                                    <!--begin::Stats-->
                                    <div class="border border-gray-300 border-dashed rounded py-3 px-3 mx-4 mb-3">
                                        <div class="fs-4 fw-bold text-gray-700">
                                            <span class="w-50px"><?= esc((string) ($alumni['akronim'] ?? '-')) ?></span>
                                        </div>
                                        <div class="fw-semibold text-muted">Kompetensi</div>
                                    </div>
                                    <!--end::Stats-->
                                    <!--begin::Stats-->
                                    <div class="border border-gray-300 border-dashed rounded py-3 px-3 mb-3">
                                        <div class="fs-4 fw-bold text-gray-700">
                                            <span class="w-50px"><?= esc((string) ($tracer_terakhir['nama_aktivitas'] ?? '-')) ?></span>
                                        </div>
                                        <div class="fw-semibold text-muted">Tracer</div>
                                    </div>
                                    <!--end::Stats-->
                                </div>
                            <?php endif; ?>
                            <!--end::Info-->
                        </div>
                        <!--end::User Info-->
                        <!--end::Summary-->
                        <!--begin::Details toggle-->
                        <div class="d-flex flex-stack fs-4 py-3">
                                <div class="fw-bold rotate collapsible" data-bs-toggle="collapse" href="#kt_user_view_details" role="button" aria-expanded="false" aria-controls="kt_user_view_details"><?= esc($detailSectionTitle) ?>
                                <span class="ms-2 rotate-180">
                                    <i class="ki-duotone ki-down fs-3"></i>
                                </span>
                            </div>
                            <?php if ($isBackofficeView || $isPelamarView): ?>
                                <span data-bs-toggle="tooltip" data-bs-trigger="hover" title="Edit customer details">
                                    <a href="#" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_update_details">Edit</a>
                                </span>
                            <?php else: ?>
                                <a href="<?= base_url('pelamar/lowongan') ?>" class="btn btn-sm btn-light-primary">Cari Lowongan</a>
                            <?php endif; ?>
                        </div>
                        <!--end::Details toggle-->
                        <div class="separator"></div>
                        <!--begin::Details content-->
                        <div id="kt_user_view_details" class="collapse show">
                            <div class="pb-5 fs-6">
                                <!--begin::Details item-->
                                <div class="fw-bold mt-5"><?= esc($accountIdLabel) ?></div>
                                <div class="text-gray-600"><?= esc((string) ($pelamar['account_id'] ?? '-')) ?></div>
                                <!--begin::Details item-->
                                <!--begin::Details item-->
                                <div class="fw-bold mt-5">Email</div>
                                <div class="text-gray-600">
                                    <a href="mailto:<?= esc((string) ($pelamar['email'] ?? '')) ?>" class="text-gray-600 text-hover-primary"><?= esc((string) ($pelamar['email'] ?? '-')) ?></a>
                                </div>
                                <!--begin::Details item-->
                                <!--begin::Details item-->
                                <div class="fw-bold mt-5">Status Pendaftaran</div>
                                <div class="text-gray-600"><span class="<?= $statusBadge($pelamar['status_pendaftaran'] ?? null, 'pendaftaran') ?>"><?= esc(ucwords(str_replace('_', ' ', (string) ($pelamar['status_pendaftaran'] ?? '-')))) ?></span></div>
                                <!--begin::Details item-->
                                <?php if ($isAlumni): ?>
                                    <!--begin::Details item-->
                                    <div class="fw-bold mt-5">Status Verifikasi</div>
                                    <div class="text-gray-600"><span class="<?= $statusBadge($alumni['status_verifikasi'] ?? null, 'verifikasi') ?>"><?= esc(ucwords(str_replace('_', ' ', (string) ($alumni['status_verifikasi'] ?? 'belum diverifikasi')))) ?></span></div>
                                    <!--begin::Details item-->
                                <?php endif; ?>
                                <!--begin::Details item-->
                                <div class="fw-bold mt-5">Alamat</div>
                                <div class="text-gray-600"><?= nl2br(esc((string) ($pelamar['alamat'] ?? '-'))) ?></div>
                                <!--begin::Details item-->
                                <!--begin::Details item-->
                                <div class="fw-bold mt-5">Status Akun</div>
                                <div class="text-gray-600"><span class="<?= $statusBadge((string) ($pelamar['status_aktif'] ?? '0'), 'akun') ?>"><?= (int) ($pelamar['status_aktif'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?></span></div>
                                <!--begin::Details item-->
                                <!--begin::Details item-->
                                <div class="fw-bold mt-5"><?= esc($lastLoginLabel) ?></div>
                                <div class="text-gray-600"><?= esc($formatTanggal($pelamar['terakhir_login'] ?? null, true)) ?></div>
                                <!--begin::Details item-->
                            </div>
                        </div>
                        <!--end::Details content-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end::Sidebar-->
            <!--begin::Content-->
            <div class="flex-lg-row-fluid ms-lg-15">
                <!--begin:::Tabs-->
                <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
                    <!--begin:::Tab item-->
                    <li class="nav-item">
                        <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_tab_akun">Akun</a>
                    </li>
                    <!--end:::Tab item-->
                    <!--begin:::Tab item-->
                    <li class="nav-item">
                        <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_user_view_overview_tab">Riwayat Kerja</a>
                    </li>
                    <!--end:::Tab item-->
                    <!--begin:::Tab item-->
                    <li class="nav-item">
                        <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_tab_berkas"><?= esc($isPelamarView ? 'Berkas Saya' : 'Berkas Profil') ?></a>
                    </li>
                    <!--end:::Tab item-->
                    <!--begin:::Tab item-->
                    <li class="nav-item">
                        <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_user_view_overview_events_and_logs_tab">Riwayat Lamaran</a>
                    </li>
                    <!--end:::Tab item-->
                    <?php if ($isAlumni): ?>
                        <!--begin:::Tab item-->
                        <li class="nav-item">
                            <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_user_view_overview_tracer_tab">Tracer Study</a>
                        </li>
                        <!--end:::Tab item-->
                    <?php endif; ?>
                    <!--begin:::Tab item-->
                    <li class="nav-item">
                        <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_tab_kartu_anggota">Kartu Anggota</a>
                    </li>
                    <!--end:::Tab item-->
                </ul>
                <!--end:::Tabs-->
                <!--begin:::Tab content-->
                <div class="tab-content" id="myTabContent">
                    <!--begin:::Tab pane - Akun-->
                    <div class="tab-pane fade show active" id="kt_tab_akun" role="tabpanel">
                        <div class="card pt-4 mb-6 mb-xl-9">
                            <div class="card-header border-0">
                                <div class="card-title">
                                    <h2>Informasi Akun</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0 pb-5">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-5" id="kt_table_akun">
                                        <tbody class="fs-6 fw-semibold text-gray-600">
                                            <tr>
                                                <td class="w-200px">Email</td>
                                                <td><?= esc((string) ($pelamar['email'] ?? '-')) ?></td>
                                                <td class="text-end">
                                                    <?php if ($isBackofficeView || $isPelamarView): ?>
                                                        <button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto" data-bs-toggle="modal" data-bs-target="#kt_modal_update_email">
                                                            <i class="ki-duotone ki-pencil fs-3">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Password</td>
                                                <td>••••••</td>
                                                <td class="text-end">
                                                    <?php if ($isBackofficeView || $isPelamarView): ?>
                                                        <button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto" data-bs-toggle="modal" data-bs-target="#kt_modal_update_password">
                                                            <i class="ki-duotone ki-pencil fs-3">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Status Akun</td>
                                                <td>
                                                    <span class="badge <?= (int) ($pelamar['status_aktif'] ?? 0) === 1 ? 'badge-light-success' : 'badge-light-danger' ?>">
                                                        <?= (int) ($pelamar['status_aktif'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end:::Tab pane - Akun-->
                    <!--begin:::Tab pane-->
                    <div class="tab-pane fade" id="kt_user_view_overview_tab" role="tabpanel">
                        <!--begin::Card-->
                        <div class="card card-flush mb-6 mb-xl-9">
                            <!--begin::Card header-->
                            <div class="card-header mt-6">
                                <!--begin::Card title-->
                                <div class="card-title flex-column">
                                    <h2 class="mb-1">Riwayat Kerja</h2>
                                    <div class="fs-6 fw-semibold text-muted"><?= count($riwayat_kerja) ?> data riwayat kerja</div>
                                </div>
                                <!--end::Card title-->
                                <!--begin::Card toolbar-->
                                <?php if ($isBackofficeView || $isPelamarView): ?>
                                    <div class="card-toolbar">
                                        <button type="button" class="btn btn-light-primary btn-sm" data-bs-toggle="modal" data-bs-target="#kt_modal_add_schedule">
                                            <i class="ki-duotone ki-brush fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>Tambah Riwayat</button>
                                    </div>
                                <?php endif; ?>
                                <!--end::Card toolbar-->
                            </div>
                            <!--end::Card header-->
                            <div class="card-body pt-0">
                                <?php if ($riwayat_kerja === []): ?>
                                    <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4 mb-4 mt-4">
                                        <i class="ki-duotone ki-information fs-2tx text-info me-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        <div class="d-flex flex-column">
                                            <h4 class="text-gray-900 fw-bold mb-1 fs-6">Belum ada riwayat kerja</h4>
                                            <div class="fs-7 text-gray-700">Tambahkan pengalaman kerja pertama pelamar melalui tombol di kanan atas</div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($riwayat_kerja as $riwayat): ?>
                                        <div class="py-4 d-flex align-items-center flex-wrap gap-3 border-bottom border-gray-300 border-bottom-dashed" data-id="<?= (int) ($riwayat['id_riwayat'] ?? 0) ?>" data-perusahaan="<?= esc((string) ($riwayat['nama_perusahaan'] ?? ''), 'attr') ?>" data-posisi="<?= esc((string) ($riwayat['posisi_jabatan'] ?? ''), 'attr') ?>" data-bidang="<?= esc((string) ($riwayat['bidang_usaha'] ?? ''), 'attr') ?>" data-lokasi="<?= esc((string) ($riwayat['lokasi'] ?? ''), 'attr') ?>" data-mulai="<?= esc((string) ($riwayat['tanggal_mulai'] ?? ''), 'attr') ?>" data-selesai="<?= esc((string) ($riwayat['tanggal_selesai'] ?? ''), 'attr') ?>" data-masih_bekerja="<?= (int) ($riwayat['masih_bekerja'] ?? 0) ?>" data-keterangan="<?= esc((string) ($riwayat['keterangan'] ?? ''), 'attr') ?>">
                                            <div class="symbol symbol-40px">
                                                <span class="symbol-label bg-light-primary">
                                                    <i class="ki-duotone ki-briefcase fs-2 text-primary">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="text-gray-900 fw-bold fs-6"><?= esc((string) ($riwayat['nama_perusahaan'] ?? '-')) ?></div>
                                                <div class="text-muted fs-7 fw-semibold"><?= esc((string) ($riwayat['posisi_jabatan'] ?? '-')) ?></div>
                                                <div class="text-muted fs-8">
                                                    <?= esc($formatTanggal($riwayat['tanggal_mulai'] ?? null)) ?> - <?= $riwayat['tanggal_selesai'] ? esc($formatTanggal($riwayat['tanggal_selesai'])) : '<span class="badge badge-light-success badge-sm">Saat ini</span>' ?>
                                                </div>
                                            </div>
                                            <?php if ($isBackofficeView || $isPelamarView): ?>
                                                <div class="d-flex gap-1 ms-auto flex-shrink-0">
                                                    <button type="button" class="btn btn-icon btn-active-light-warning w-30px h-30px" data-action="edit-riwayat" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_riwayat">
                                                        <i class="ki-duotone ki-pencil fs-3">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </button>
                                                    <button type="button" class="btn btn-icon btn-active-light-danger w-30px h-30px" data-action="hapus-riwayat">
                                                        <i class="ki-duotone ki-trash fs-3">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                            <span class="path3"></span>
                                                            <span class="path4"></span>
                                                            <span class="path5"></span>
                                                        </i>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!--end::Card-->
                    </div>
                    <!--end:::Tab pane-->
                    <!--begin:::Tab pane-->
                    <div class="tab-pane fade" id="kt_tab_berkas" role="tabpanel">
                        <div class="card pt-4 mb-6 mb-xl-9">
                            <div class="card-header border-0">
                                <div class="card-title flex-column">
                                    <h2 class="mb-1"><?= esc($berkasHeaderTitle) ?></h2>
                                    <div class="fs-6 fw-semibold text-muted"><?= esc($berkasHeaderSubtitle) ?></div>
                                </div>
                                <?php if (! empty($jenis_berkas)): ?>
                                    <div class="card-toolbar">
                                        <button type="button" class="btn btn-light-primary btn-sm" data-bs-toggle="modal" data-bs-target="#kt_modal_upload_berkas" data-action="tambah-berkas">
                                            <i class="ki-duotone ki-plus fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i><?= esc($isPelamarView ? 'Upload Dokumen' : 'Upload Berkas') ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body pt-0 pb-5">
                                <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5 mb-8">
                                    <div class="fw-semibold fs-7 text-gray-700">
                                        <?= esc($berkasNoticeText) ?>
                                    </div>
                                </div>
                                <div class="row g-5 mb-8">
                                    <div class="col-md-4">
                                        <div class="kt-berkas-summary">
                                            <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Kelengkapan</div>
                                            <div class="d-flex align-items-end justify-content-between">
                                                <div class="fs-2hx fw-bold text-gray-900"><?= $jumlahBerkasTersedia ?></div>
                                                <div class="text-muted fs-7">dari <?= $jumlahBerkas ?> dokumen</div>
                                            </div>
                                            <div class="text-gray-600 fs-7 mt-3">Dokumen yang sudah tersedia untuk pelamar ini.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="kt-berkas-summary">
                                            <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Berkas Wajib</div>
                                            <div class="d-flex align-items-end justify-content-between">
                                                <div class="fs-2hx fw-bold text-gray-900"><?= $jumlahBerkasWajibTersedia ?></div>
                                                <div class="text-muted fs-7">dari <?= $jumlahBerkasWajib ?> wajib</div>
                                            </div>
                                            <div class="text-gray-600 fs-7 mt-3">Pantau dokumen profil wajib sebelum melanjutkan proses berikutnya.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="kt-berkas-summary">
                                            <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Perlu Tindak Lanjut</div>
                                            <div class="d-flex align-items-end justify-content-between">
                                                <div class="fs-2hx fw-bold text-gray-900"><?= $jumlahBerkasDitolak ?></div>
                                                <div class="text-muted fs-7">dokumen ditolak</div>
                                            </div>
                                            <div class="text-gray-600 fs-7 mt-3">Dokumen profil yang ditolak atau perlu diperbarui.</div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (empty($berkas)): ?>
                                    <div class="text-center text-muted py-10">Belum ada master berkas profil umum yang bisa ditampilkan.</div>
                                <?php else: ?>
                                    <div class="row g-5">
                                        <?php foreach ($berkas as $b): ?>
                                            <?php
                                            [$statusBadgeClass, $statusLabel, $statusHint] = $statusBerkasMeta($b['status_unggah'] ?? null);
                                            $statusBerkas = (string) ($b['status_unggah'] ?? 'belum_diunggah');
                                            $isUploaded = $statusBerkas === 'sudah_diunggah';
                                            $isRejected = $statusBerkas === 'ditolak';
                                            $namaBerkas = (string) ($b['nama_berkas'] ?? 'Berkas');
                                            $pathFile = trim((string) ($b['path_file'] ?? ''));
                                            $fileUrl = $pathFile !== '' ? base_url($pathFile) : '';
                                            ?>
                                            <div class="col-md-6 col-xl-4">
                                                <div class="card border border-dashed h-100 kt-berkas-card <?= $isUploaded ? 'is-ready' : ($isRejected ? 'is-rejected' : 'is-pending') ?>">
                                                    <div class="card-body d-flex flex-column">
                                                        <div class="d-flex justify-content-between align-items-start mb-5">
                                                            <div class="symbol symbol-60px kt-berkas-icon me-4">
                                                                <img src="<?= base_url('assets/media/svg/files/pdf.svg') ?>" class="theme-light-show" alt="<?= esc($namaBerkas, 'attr') ?>" />
                                                                <img src="<?= base_url('assets/media/svg/files/pdf-dark.svg') ?>" class="theme-dark-show" alt="<?= esc($namaBerkas, 'attr') ?>" />
                                                            </div>
                                                            <div class="text-end">
                                                                <div class="<?= esc($statusBadgeClass) ?> mb-2"><?= esc($statusLabel) ?></div>
                                                                <div class="badge <?= ! empty($b['wajib']) ? 'badge-light-danger' : 'badge-light-info' ?>">
                                                                    <?= ! empty($b['wajib']) ? 'Wajib' : 'Opsional' ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="fw-bold fs-5 text-gray-900 mb-1"><?= esc($namaBerkas) ?></div>
                                                        <div class="text-muted fs-7 mb-5"><?= esc((string) (($b['keterangan'] ?? '') !== '' ? $b['keterangan'] : $statusHint)) ?></div>

                                                        <?php if ($isRejected && ! empty($b['catatan'])): ?>
                                                            <div class="notice d-flex bg-light-danger rounded border-danger border border-dashed mb-5 p-4">
                                                                <div class="fw-semibold fs-7 text-gray-700">
                                                                    <span class="d-block text-danger fw-bold mb-1">Catatan Admin</span>
                                                                    <?= esc((string) $b['catatan']) ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>

                                                        <div class="mt-auto d-flex align-items-center flex-wrap gap-2 kt-berkas-actions">
                                                            <?php if ($isUploaded || $isRejected): ?>
                                                                <?php if ($fileUrl !== ''): ?>
                                                                    <a
                                                                        href="<?= esc($fileUrl) ?>"
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                        class="btn btn-icon btn-sm btn-light-primary"
                                                                        data-bs-toggle="tooltip"
                                                                        title="Lihat berkas"
                                                                        aria-label="Lihat berkas <?= esc($namaBerkas, 'attr') ?>"
                                                                    >
                                                                        <i class="ki-duotone ki-eye fs-4">
                                                                            <span class="path1"></span>
                                                                            <span class="path2"></span>
                                                                            <span class="path3"></span>
                                                                        </i>
                                                                    </a>
                                                                <?php endif; ?>

                                                                <button
                                                                    type="button"
                                                                    class="btn btn-icon btn-sm btn-light-warning"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#kt_modal_upload_berkas"
                                                                    data-action="ganti-berkas"
                                                                    data-id="<?= (int) ($b['id_berkas'] ?? 0) ?>"
                                                                    data-jenis-id="<?= (int) ($b['id_jenis_berkas'] ?? 0) ?>"
                                                                    data-jenis-nama="<?= esc($namaBerkas, 'attr') ?>"
                                                                    title="Ganti berkas"
                                                                    aria-label="Ganti berkas <?= esc($namaBerkas, 'attr') ?>"
                                                                >
                                                                    <i class="ki-duotone ki-pencil fs-4">
                                                                        <span class="path1"></span>
                                                                        <span class="path2"></span>
                                                                    </i>
                                                                </button>

                                                                <?php if (! empty($b['id_berkas'])): ?>
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-icon btn-sm btn-light-danger"
                                                                        data-action="hapus-berkas"
                                                                        data-id="<?= (int) $b['id_berkas'] ?>"
                                                                        data-bs-toggle="tooltip"
                                                                        title="Hapus berkas"
                                                                        aria-label="Hapus berkas <?= esc($namaBerkas, 'attr') ?>"
                                                                    >
                                                                        <i class="ki-duotone ki-trash fs-4">
                                                                            <span class="path1"></span>
                                                                            <span class="path2"></span>
                                                                            <span class="path3"></span>
                                                                            <span class="path4"></span>
                                                                            <span class="path5"></span>
                                                                        </i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-primary"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#kt_modal_upload_berkas"
                                                                    data-action="upload-berkas"
                                                                    data-id="<?= (int) ($b['id_berkas'] ?? 0) ?>"
                                                                    data-jenis-id="<?= (int) ($b['id_jenis_berkas'] ?? 0) ?>"
                                                                    data-jenis-nama="<?= esc($namaBerkas, 'attr') ?>"
                                                                >
                                                                    Upload
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!--end:::Tab pane-->
                    <!--begin:::Tab pane-->
                    <div class="tab-pane fade" id="kt_user_view_overview_events_and_logs_tab" role="tabpanel">
                        <!--begin::Card-->
                        <div class="card pt-4 mb-6 mb-xl-9">
                            <!--begin::Card header-->
                            <div class="card-header border-0">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2>Riwayat Lamaran</h2>
                                </div>
                                <!--end::Card title-->
                                <!--begin::Card toolbar-->
                                <div class="card-toolbar">
                                    <!--begin::Filter-->
                                    <button type="button" class="btn btn-sm btn-flex btn-light-primary" id="kt_modal_sign_out_sesions">
                                        <i class="ki-duotone ki-entrance-right fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>Total <?= count($lamaran) ?> Lamaran</button>
                                    <!--end::Filter-->
                                </div>
                                <!--end::Card toolbar-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0 pb-5">
                                <!--begin::Table wrapper-->
                                <div class="table-responsive">
                                    <!--begin::Table-->
                                    <table class="table align-middle table-row-dashed gy-5" id="kt_table_riwayat_lamaran">
                                        <thead class="border-bottom border-gray-200 fs-7 fw-bold">
                                            <tr class="text-start text-muted text-uppercase gs-0">
                                                <th class="min-w-100px">Posisi</th>
                                                <th>Perusahaan</th>
                                                <th>Tgl Melamar</th>
                                                <th class="min-w-125px">Wawancara</th>
                                                <th class="min-w-125px">Status</th>
                                                <th class="min-w-70px">Catatan</th>
                                                <?php if ($isPelamarView): ?>
                                                    <th class="text-end min-w-80px">Aksi</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody class="fs-6 fw-semibold text-gray-600">
                                            <?php if ($lamaran === []): ?>
                                                <tr>
                                                    <td colspan="<?= $isPelamarView ? 7 : 6 ?>" class="text-center text-muted py-10">Belum ada riwayat lamaran.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($lamaran as $lamaranItem): ?>
                                                    <tr>
                                                        <td><?= esc((string) ($lamaranItem['posisi'] ?? '-')) ?></td>
                                                        <td><?= esc((string) ($lamaranItem['nama_perusahaan'] ?? '-')) ?></td>
                                                        <td><?= esc($formatTanggal($lamaranItem['tanggal_melamar'] ?? null)) ?></td>
                                                        <td><?= esc($formatTanggal($lamaranItem['tanggal_wawancara'] ?? null, true)) ?></td>
                                                        <td><span class="<?= $statusBadge($lamaranItem['status'] ?? null, 'lamaran') ?>"><?= esc(ucwords(str_replace('_', ' ', (string) ($lamaranItem['status'] ?? '-')))) ?></span></td>
                                                        <td><?= esc((string) ($lamaranItem['catatan'] ?? '-')) ?></td>
                                                        <?php if ($isPelamarView): ?>
                                                            <td class="text-end">
                                                                <a href="<?= site_url('pelamar/lamaran/' . (int) ($lamaranItem['id_lamaran'] ?? 0)) ?>" class="btn btn-icon btn-active-light-primary w-30px h-30px" title="Detail Lamaran">
                                                                    <i class="ki-duotone ki-eye fs-3">
                                                                        <span class="path1"></span>
                                                                        <span class="path2"></span>
                                                                        <span class="path3"></span>
                                                                    </i>
                                                                </a>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <!--end::Table-->
                                </div>
                                <!--end::Table wrapper-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Card-->
                    </div>
                    <!--end:::Tab pane-->
                    <?php if ($isAlumni): ?>
                        <!--begin:::Tab pane-->
                        <div class="tab-pane fade" id="kt_user_view_overview_tracer_tab" role="tabpanel">
                            <!--begin::Card-->
                            <div class="card pt-4 mb-6 mb-xl-9">
                                <!--begin::Card header-->
                                <div class="card-header border-0">
                                    <!--begin::Card title-->
                                    <div class="card-title">
                                        <h2>Tracer Study</h2>
                                    </div>
                                    <!--end::Card title-->
                                    <!--begin::Card toolbar-->
                                    <?php if ($isBackofficeView || $isPelamarView): ?>
                                        <div class="card-toolbar">
                                            <button type="button" class="btn btn-light-primary btn-sm" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_tracer">
                                                <i class="ki-duotone ki-pencil fs-3">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>Edit
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    <!--end::Card toolbar-->
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body pt-0 pb-5">
                                    <?php if ($tracer_terakhir === null && ($alumni['tahun_lulus'] ?? null) === null): ?>
                                        <div class="text-muted py-10 text-center">Belum ada data tracer study.</div>
                                    <?php else: ?>
                                        <?php if (! empty($alumni['tahun_lulus']) || ! empty($alumni['nama_kompetensi'])): ?>
                                            <div class="pb-6">
                                                <div class="fw-bold text-gray-900 fs-6 mb-3">Data Alumni</div>
                                                <div class="d-flex flex-stack py-2">
                                                    <div class="text-gray-700">Tahun Lulus</div>
                                                    <div class="text-gray-600"><?= esc((string) ($alumni['tahun_lulus'] ?? '-')) ?></div>
                                                </div>
                                                <div class="d-flex flex-stack py-2">
                                                    <div class="text-gray-700">Kompetensi</div>
                                                    <div class="text-gray-600"><?= esc((string) ($alumni['nama_kompetensi'] ?? ($alumni['akronim'] ?? '-'))) ?></div>
                                                </div>
                                            </div>
                                            <div class="separator separator-dashed mb-6"></div>
                                        <?php endif; ?>
                                        <?php if ($tracer_terakhir !== null): ?>
                                            <?php [$statusTracerClass, $statusTracerLabel] = $statusTracerMeta($tracer_terakhir['status'] ?? null); ?>
                                            <div class="pb-6">
                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                                    <div class="fw-bold text-gray-900 fs-6">Aktivitas Terakhir</div>
                                                    <span class="<?= esc($statusTracerClass) ?>"><?= esc($statusTracerLabel) ?></span>
                                                </div>
                                                <span class="badge badge-light-success"><?= esc((string) ($tracer_terakhir['nama_aktivitas'] ?? '-')) ?></span>
                                            </div>
                                            <div class="separator separator-dashed mb-6"></div>
                                            <?php if ($tracer_fields === []): ?>
                                                <div class="text-muted">Data tracer tersedia, tetapi belum memiliki field rinci.</div>
                                            <?php else: ?>
                                                <?php foreach ($tracer_fields as $field): ?>
                                                    <div class="d-flex flex-stack py-3 border-bottom border-gray-200">
                                                        <div class="fw-bold text-gray-700"><?= esc((string) $field['label']) ?></div>
                                                        <div class="text-gray-600 text-end"><?= esc((string) $field['value']) ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <!--end::Card body-->
                            </div>
                            <!--end::Card-->
                        </div>
                        <!--end:::Tab pane-->
                    <?php endif; ?>
                    <!--begin:::Tab pane-->
                    <div class="tab-pane fade" id="kt_tab_kartu_anggota" role="tabpanel">
                        <!--begin::Card-->
                        <div class="card pt-4 mb-6 mb-xl-9">
                            <!--begin::Card header-->
                            <div class="card-header border-0">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2>Kartu Anggota</h2>
                                </div>
                                <!--end::Card title-->
                                <div class="card-toolbar">
                                    <button type="button" class="btn btn-primary btn-sm" id="kt_unduh_kartu">
                                        <i class="ki-duotone ki-download fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>Unduh Kartu
                                    </button>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0 pb-5">
                                <div class="d-flex justify-content-center">
                                    <div id="kt_kartu_anggota" class="rounded p-6" style="width: 85.6mm; height: 53.98mm; background: linear-gradient(135deg, #0d6efd 0%, #0a4fb3 100%); color: #fff; position: relative; overflow: hidden;">
                                        <div style="position: absolute; inset: -30px -30px -30px auto; width: 120px; height: 120px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                                        <div style="position: relative; z-index: 1; height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="text-uppercase opacity-75 fs-7">Tracer Study & BKK</div>
                                                    <div class="fs-5 fw-bold"><?= esc($namaLengkap) ?></div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="symbol symbol-40px">
                                                        <?php if (! empty($fotoUrl)): ?>
                                                            <img src="<?= esc($fotoUrl) ?>" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" />
                                                        <?php else: ?>
                                                            <div class="symbol-label bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <span class="fs-5 fw-bold"><?= esc($inisial) ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-end">
                                                <div>
                                                    <div class="opacity-75 fs-7"><?= $isAlumni ? 'Alumni' : 'Umum' ?></div>
                                                    <div class="fs-6 fw-bold"><?= esc((string) ($pelamar['account_id'] ?? '-')) ?></div>
                                                    <div class="fs-7 opacity-75 mt-1"><?= esc((string) ($pelamar['email'] ?? '')) ?></div>
                                                </div>
                                                <div id="kt_qrcode" class="text-end"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 text-muted text-center fs-7">
                                    Scan QR code untuk verifikasi keabsahan kartu anggota.
                                </div>
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Card-->
                    </div>
                    <!--end:::Tab pane-->
                </div>
                <!--end:::Tab content-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Layout-->
    </div>
    <!--end::Content container-->
</div>
<!--end::Content-->

<?php if ($isBackofficeView || $isPelamarView): ?>
<div class="modal fade" id="kt_modal_update_details" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_update_details_header">
                <h2 class="fw-bold"><?= esc($editDetailTitle) ?></h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" data-kt-pelamar-detail-modal-action="close-edit-detail">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_update_details_form" class="form" action="#" enctype="multipart/form-data">
                    <input type="hidden" name="id_pelamar" value="<?= (int) $pelamar['id_pelamar'] ?>" />
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_update_details_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_update_details_header" data-kt-scroll-wrappers="#kt_modal_update_details_scroll" data-kt-scroll-offset="300px">
                        <!--begin::Image Input-->
                        <div class="fv-row mb-7">
                            <label class="d-block fw-semibold fs-6 mb-5">Foto Profil</label>
                            <style>
                                .image-input-placeholder-detail {
                                    background-image: url('<?= esc($blankImageUrl) ?>');
                                }

                                [data-bs-theme="dark"] .image-input-placeholder-detail {
                                    background-image: url('<?= esc($blankImageDarkUrl) ?>');
                                }
                            </style>
                            <div
                                class="image-input image-input-outline image-input-placeholder-detail<?= $fotoUrl === '' ? ' image-input-empty' : '' ?>"
                                data-kt-image-input="true"
                                data-image-input-initial="<?= esc((string) ($fotoUrl ?? ''), 'attr') ?>"
                                data-image-input-placeholder="<?= esc($blankImageUrl, 'attr') ?>">
                                <div class="image-input-wrapper w-125px h-125px" style="background-image: url('<?= esc($fotoPreviewUrl) ?>');"></div>
                                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Ubah foto">
                                    <i class="ki-duotone ki-pencil fs-7">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <input type="file" name="foto" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="foto_remove" />
                                </label>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Batalkan perubahan foto">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Hapus foto">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="form-text">Format yang diizinkan: png, jpg, jpeg. Klik ikon x untuk menghapus foto.</div>
                        </div>
                        <!--end::Image Input-->
                        <!--begin::Nama Lengkap-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control form-control-solid" value="<?= esc($namaLengkap) ?>" />
                        </div>
                        <!--end::Nama Lengkap-->
                        <!--begin::Nomor Telepon-->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Nomor Telepon</label>
                            <input type="text" name="nomor_telepon" class="form-control form-control-solid" value="<?= esc((string) ($pelamar['nomor_telepon'] ?? '')) ?>" />
                        </div>
                        <!--end::Nomor Telepon-->
                        <!--begin::Jenis Kelamin-->
                        <div class="mb-7">
                            <label class="fs-6 fw-semibold mb-5">Jenis Kelamin</label>
                            <div class="d-flex fv-row">
                                <div class="form-check form-check-custom form-check-solid me-10">
                                    <input class="form-check-input me-3" name="jenis_kelamin" type="radio" value="L" id="edit_jenis_kelamin_l" <?= (string) ($pelamar['jenis_kelamin'] ?? '') === 'L' ? 'checked' : '' ?> />
                                    <label class="form-check-label" for="edit_jenis_kelamin_l">
                                        <div class="fw-bold text-gray-800">Laki-laki</div>
                                    </label>
                                </div>
                                <div class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input me-3" name="jenis_kelamin" type="radio" value="P" id="edit_jenis_kelamin_p" <?= (string) ($pelamar['jenis_kelamin'] ?? '') === 'P' ? 'checked' : '' ?> />
                                    <label class="form-check-label" for="edit_jenis_kelamin_p">
                                        <div class="fw-bold text-gray-800">Perempuan</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <!--end::Jenis Kelamin-->
                        <!--begin::Tempat & Tanggal Lahir-->
                        <div class="row g-9 mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" class="form-control form-control-solid" placeholder="Kabupaten/Kota" value="<?= esc((string) ($pelamar['tempat_lahir'] ?? '')) ?>" />
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Tanggal Lahir</label>
                                <div class="position-relative d-flex align-items-center">
                                    <i class="ki-duotone ki-calendar-8 fs-2 position-absolute ms-4">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                        <span class="path6"></span>
                                    </i>
                                    <input
                                        type="text"
                                        name="tanggal_lahir"
                                        class="form-control form-control-solid ps-12"
                                        placeholder="Pilih tanggal lahir"
                                        value="<?= esc((string) ($pelamar['tanggal_lahir'] ?? ''), 'attr') ?>"
                                        data-kt-profile-datepicker="true"
                                    />
                                </div>
                            </div>
                        </div>
                        <!--end::Tempat & Tanggal Lahir-->
                        <!--begin::NIK-->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">NIK</label>
                            <input type="text" name="nomer_nik" class="form-control form-control-solid" value="<?= esc((string) ($pelamar['nomer_nik'] ?? '')) ?>" />
                        </div>
                        <!--end::NIK-->
                        <!--begin::Alamat-->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Alamat</label>
                            <textarea name="alamat" class="form-control form-control-solid" rows="4"><?= esc((string) ($pelamar['alamat'] ?? '')) ?></textarea>
                        </div>
                        <!--end::Alamat-->
                        <?php if ($isBackofficeView): ?>
                            <!--begin::Status Pendaftaran-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Status Pendaftaran</label>
                                <select class="form-select form-select-solid fw-bold" name="status_pendaftaran" data-kt-select2="true" data-placeholder="Select option" data-hide-search="true">
                                    <option value="menunggu_aktivasi" <?= (string) ($pelamar['status_pendaftaran'] ?? '') === 'menunggu_aktivasi' ? 'selected' : '' ?>>Menunggu Aktivasi</option>
                                    <option value="aktif" <?= (string) ($pelamar['status_pendaftaran'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="terdaftar" <?= (string) ($pelamar['status_pendaftaran'] ?? '') === 'terdaftar' ? 'selected' : '' ?>>Terdaftar</option>
                                </select>
                            </div>
                            <!--end::Status Pendaftaran-->
                        <?php endif; ?>
                    </div>
                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal" data-kt-pelamar-detail-modal-action="cancel-edit-detail">Batal</button>
                        <button type="submit" class="btn btn-primary" data-kt-pelamar-detail-modal-action="submit-edit-detail">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Menyimpan...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isBackofficeView || $isPelamarView): ?>
<div class="modal fade" id="kt_modal_add_schedule" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_schedule_header">
                <h2 class="fw-bold">Tambah Riwayat Kerja</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" data-kt-pelamar-detail-modal-action="close-add-riwayat">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_schedule_form" class="form" action="#">
                    <input type="hidden" name="id_pelamar" value="<?= (int) $pelamar['id_pelamar'] ?>" />
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_schedule_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_schedule_header" data-kt-scroll-wrappers="#kt_modal_add_schedule_scroll" data-kt-scroll-offset="300px">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Nama Perusahaan</label>
                            <input type="text" name="nama_perusahaan" class="form-control form-control-solid" placeholder="Nama perusahaan" />
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Posisi / Jabatan</label>
                            <input type="text" name="posisi_jabatan" class="form-control form-control-solid" placeholder="Posisi / jabatan" />
                        </div>
                        <div class="row g-4 mb-7">
                            <div class="col-md-6">
                                <div class="fv-row">
                                    <label class="fw-semibold fs-6 mb-2">Bidang Usaha</label>
                                    <input type="text" name="bidang_usaha" class="form-control form-control-solid" placeholder="Contoh: IT, Retail, Banking" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="fv-row">
                                    <label class="fw-semibold fs-6 mb-2">Lokasi / Kota</label>
                                    <input type="text" name="lokasi" class="form-control form-control-solid" placeholder="Nama kota / kabupaten" />
                                </div>
                            </div>
                        </div>
                        <div class="row g-4 mb-7">
                            <div class="col-md-6">
                                <div class="fv-row">
                                    <label class="required fw-semibold fs-6 mb-2">Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" class="form-control form-control-solid" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="fv-row">
                                    <label class="fw-semibold fs-6 mb-2">Tanggal Selesai</label>
                                    <input type="date" name="tanggal_selesai" class="form-control form-control-solid" />
                                </div>
                            </div>
                        </div>
                        <div class="fv-row mb-7">
                            <div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="masih_bekerja" value="1" id="kt_riwayat_masih_bekerja_add" />
                                <label class="form-check-label fw-semibold fs-6" for="kt_riwayat_masih_bekerja_add">
                                    Masih bekerja di posisi ini
                                </label>
                            </div>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Keterangan</label>
                            <textarea name="keterangan" class="form-control form-control-solid" rows="3" placeholder="Deskripsi tugas, tanggung jawab, pencapaian"></textarea>
                        </div>
                    </div>
                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal" data-kt-pelamar-detail-modal-action="cancel-add-riwayat">Batal</button>
                        <button type="submit" class="btn btn-primary" data-kt-pelamar-detail-modal-action="submit-add-riwayat">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Menyimpan...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="kt_modal_edit_riwayat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_edit_riwayat_header">
                <h2 class="fw-bold">Edit Riwayat Kerja</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" data-kt-pelamar-detail-modal-action="close-edit-riwayat">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_riwayat_form" class="form" action="#">
                    <input type="hidden" name="id_pelamar" value="<?= (int) $pelamar['id_pelamar'] ?>" />
                    <input type="hidden" name="id_riwayat" value="" />
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_riwayat_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_edit_riwayat_header" data-kt-scroll-wrappers="#kt_modal_edit_riwayat_scroll" data-kt-scroll-offset="300px">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Nama Perusahaan</label>
                            <input type="text" name="nama_perusahaan" class="form-control form-control-solid" placeholder="Nama perusahaan" />
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Posisi / Jabatan</label>
                            <input type="text" name="posisi_jabatan" class="form-control form-control-solid" placeholder="Posisi / jabatan" />
                        </div>
                        <div class="row g-4 mb-7">
                            <div class="col-md-6">
                                <div class="fv-row">
                                    <label class="fw-semibold fs-6 mb-2">Bidang Usaha</label>
                                    <input type="text" name="bidang_usaha" class="form-control form-control-solid" placeholder="Contoh: IT, Retail, Banking" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="fv-row">
                                    <label class="fw-semibold fs-6 mb-2">Lokasi / Kota</label>
                                    <input type="text" name="lokasi" class="form-control form-control-solid" placeholder="Nama kota / kabupaten" />
                                </div>
                            </div>
                        </div>
                        <div class="row g-4 mb-7">
                            <div class="col-md-6">
                                <div class="fv-row">
                                    <label class="required fw-semibold fs-6 mb-2">Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" class="form-control form-control-solid" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="fv-row">
                                    <label class="fw-semibold fs-6 mb-2">Tanggal Selesai</label>
                                    <input type="date" name="tanggal_selesai" class="form-control form-control-solid" />
                                </div>
                            </div>
                        </div>
                        <div class="fv-row mb-7">
                            <div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="masih_bekerja" value="1" id="kt_riwayat_masih_bekerja" />
                                <label class="form-check-label fw-semibold fs-6" for="kt_riwayat_masih_bekerja">
                                    Masih bekerja di posisi ini
                                </label>
                            </div>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Keterangan</label>
                            <textarea name="keterangan" class="form-control form-control-solid" rows="3" placeholder="Deskripsi tugas, tanggung jawab, pencapaian"></textarea>
                        </div>
                    </div>
                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal" data-kt-pelamar-detail-modal-action="cancel-edit-riwayat">Batal</button>
                        <button type="submit" class="btn btn-primary" data-kt-pelamar-detail-modal-action="submit-edit-riwayat">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Menyimpan...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="kt_modal_upload_berkas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_upload_berkas_header">
                <h2 class="fw-bold"><?= esc($isPelamarView ? 'Upload Dokumen Profil' : 'Upload Berkas Profil') ?></h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" data-kt-pelamar-detail-modal-action="close-upload-berkas">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_upload_berkas_form" class="form" action="#">
                    <input type="hidden" name="id_pelamar" value="<?= (int) $pelamar['id_pelamar'] ?>" />
                    <input type="hidden" name="id_berkas" value="" />
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_upload_berkas_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_upload_berkas_header" data-kt-scroll-wrappers="#kt_modal_upload_berkas_scroll" data-kt-scroll-offset="300px">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Jenis Dokumen Profil</label>
                            <select name="id_jenis_berkas" class="form-select form-select-solid">
                                <option value="">Pilih jenis dokumen profil</option>
                                <?php foreach ($jenis_berkas as $jenisBerkas): ?>
                                    <option value="<?= (int) $jenisBerkas['id_jenis_berkas'] ?>"><?= esc((string) $jenisBerkas['nama_berkas']) ?><?= ! empty($jenisBerkas['wajib']) ? ' (Wajib)' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">File Dokumen</label>
                            <input type="file" name="file_berkas" class="form-control form-control-solid" accept=".pdf,.jpg,.jpeg,.png" />
                            <div class="form-text">Format file: pdf, jpg, jpeg, png. Maksimal 5 MB. CV, surat lamaran, dan portofolio dilampirkan saat melamar lowongan.</div>
                        </div>
                    </div>
                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal" data-kt-pelamar-detail-modal-action="cancel-upload-berkas">Batal</button>
                        <button type="submit" class="btn btn-primary" data-kt-pelamar-detail-modal-action="submit-upload-berkas">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Menyimpan...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($isBackofficeView || $isPelamarView): ?>
<!--begin::Modal - Update Email-->
<div class="modal fade" id="kt_modal_update_email" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Ubah Email</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-users-modal-action="close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <form id="kt_modal_update_email_form" class="form" action="#">
                    <input type="hidden" name="id_pelamar" value="<?= (int) $pelamar['id_pelamar'] ?>" />
                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed mb-9 p-6">
                        <i class="ki-duotone ki-information fs-2tx text-primary me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div class="d-flex flex-stack flex-grow-1">
                            <div class="fw-semibold">
                                <div class="fs-6 text-gray-700">Perubahan email memerlukan konfirmasi password akun.</div>
                            </div>
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold form-label mb-2">
                            <span class="required">Email Baru</span>
                        </label>
                        <input class="form-control form-control-solid" name="email_baru" value="<?= esc((string) ($pelamar['email'] ?? '')) ?>" />
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold form-label mb-2">
                            <span class="required">Konfirmasi Password</span>
                        </label>
                        <input class="form-control form-control-solid" type="password" name="konfirmasi_password" />
                    </div>
                    <div class="text-center pt-15">
                        <button type="reset" class="btn btn-light me-3" data-kt-users-modal-action="cancel">Batal</button>
                        <button type="submit" class="btn btn-primary" data-kt-users-modal-action="submit">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Menyimpan...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--end::Modal - Update Email-->

<!--begin::Modal - Update Password-->
<div class="modal fade" id="kt_modal_update_password" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Ubah Password</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-users-modal-action="close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <form id="kt_modal_update_password_form" class="form" action="#">
                    <input type="hidden" name="id_pelamar" value="<?= (int) $pelamar['id_pelamar'] ?>" />
                    <div class="fv-row mb-10">
                        <label class="required form-label fs-6 mb-2">Password Saat Ini</label>
                        <input class="form-control form-control-lg form-control-solid" type="password" name="password_saat_ini" autocomplete="off" />
                    </div>
                    <div class="mb-10 fv-row" data-kt-password-meter="true">
                        <div class="mb-1">
                            <label class="form-label fw-semibold fs-6 mb-2">Password Baru</label>
                            <div class="position-relative mb-3">
                                <input class="form-control form-control-lg form-control-solid" type="password" name="password_baru" autocomplete="off" />
                                <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" data-kt-password-meter-control="visibility">
                                    <i class="ki-duotone ki-eye-slash fs-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                    <i class="ki-duotone ki-eye d-none fs-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
                            </div>
                        </div>
                        <div class="text-muted">Gunakan 8 karakter atau lebih dengan kombinasi huruf, angka & simbol.</div>
                    </div>
                    <div class="fv-row mb-10">
                        <label class="form-label fw-semibold fs-6 mb-2">Konfirmasi Password Baru</label>
                        <input class="form-control form-control-lg form-control-solid" type="password" name="konfirmasi_password_baru" autocomplete="off" />
                    </div>
                    <div class="text-center pt-15">
                        <button type="reset" class="btn btn-light me-3" data-kt-users-modal-action="cancel">Batal</button>
                        <button type="submit" class="btn btn-primary" data-kt-users-modal-action="submit">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Menyimpan...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--end::Modal - Update Password-->

<!--begin::Modal Edit Tracer-->
<div class="modal fade" id="kt_modal_edit_tracer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_edit_tracer_header">
                <h2 class="fw-bold"><?= esc($isPelamarView ? 'Isi Tracer Study' : 'Edit Tracer Alumni') ?></h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-tracer-modal-action="close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_tracer_form" class="form" action="#">
                    <input type="hidden" name="id_pelamar" value="<?= (int) $pelamar['id_pelamar'] ?>" />
                    <input type="hidden" name="status_tracer" value="<?= esc((string) ($tracer_terakhir['status'] ?? 'draft'), 'attr') ?>" />
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-5">Kegiatan Saat Ini</label>
                        <?php foreach ($aktivitas as $a): ?>
                            <div class="d-flex fv-row mb-3">
                                <div class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input me-3" type="radio" name="id_aktivitas" value="<?= (int) $a['id_aktivitas'] ?>" id="tracer_aktivitas_<?= (int) $a['id_aktivitas'] ?>" data-slug="<?= esc(strtolower(str_replace(' ', '_', (string) $a['nama_aktivitas'])), 'attr') ?>" <?= (! empty($tracer_terakhir) && (int) ($tracer_terakhir['id_aktivitas'] ?? 0) === (int) $a['id_aktivitas']) ? 'checked' : '' ?> />
                                    <label class="form-check-label" for="tracer_aktivitas_<?= (int) $a['id_aktivitas'] ?>">
                                        <div class="fw-bold text-gray-800"><?= esc((string) $a['nama_aktivitas']) ?></div>
                                    </label>
                                </div>
                            </div>
                            <div class="separator separator-dashed my-3"></div>
                        <?php endforeach; ?>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6 mb-5">
                            <label class="fw-semibold fs-6 mb-2">Tahun Lulus</label>
                            <select name="id_angkatan" class="form-select form-select-solid">
                                <option value="">-- Pilih Tahun Lulus --</option>
                                <?php foreach (($daftar_angkatan ?? []) as $angkatanItem): ?>
                                    <option value="<?= (int) $angkatanItem['id_angkatan'] ?>" <?= (int) ($alumni['id_angkatan'] ?? 0) === (int) $angkatanItem['id_angkatan'] ? 'selected' : '' ?>>
                                        <?= esc((string) $angkatanItem['tahun_lulus']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-5">
                            <label class="fw-semibold fs-6 mb-2">Kompetensi Keahlian</label>
                            <select name="id_kompetensi" class="form-select form-select-solid">
                                <option value="">-- Pilih Kompetensi Keahlian --</option>
                                <?php foreach (($daftar_kompetensi ?? []) as $kompetensiItem): ?>
                                    <?php
                                    $kompetensiOption = (string) ($kompetensiItem['nama_kompetensi'] ?? '-');
                                    if (! empty($kompetensiItem['akronim'])) {
                                        $kompetensiOption .= ' (' . $kompetensiItem['akronim'] . ')';
                                    }
                                    ?>
                                    <option value="<?= (int) $kompetensiItem['id_kompetensi'] ?>" <?= (int) ($alumni['id_kompetensi'] ?? 0) === (int) $kompetensiItem['id_kompetensi'] ? 'selected' : '' ?>>
                                        <?= esc($kompetensiOption) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-5">Apakah Pekerjaan Ini Relevan Dengan Kompetensi Anda?</label>
                        <div class="d-flex gap-5 mt-2 flex-wrap">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="relevan_jurusan" value="1" <?= isset($tracer_terakhir['relevan_jurusan']) && (int) $tracer_terakhir['relevan_jurusan'] === 1 ? 'checked' : '' ?> />
                                <span class="form-check-label">Ya, Relevan</span>
                            </label>
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="relevan_jurusan" value="0" <?= isset($tracer_terakhir['relevan_jurusan']) && (string) $tracer_terakhir['relevan_jurusan'] === '0' ? 'checked' : '' ?> />
                                <span class="form-check-label">Tidak</span>
                            </label>
                        </div>
                    </div>

                    <div id="kt_tracer_form_bekerja" class="d-none">
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Posisi / Jabatan</label>
                            <input type="text" name="posisi_kerja" class="form-control form-control-solid" value="<?= esc((string) ($tracer_terakhir['posisi_kerja'] ?? ''), 'attr') ?>" />
                        </div>
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Nama Perusahaan / DUDI</label>
                            <input type="text" name="nama_dudi" class="form-control form-control-solid" value="<?= esc((string) ($tracer_terakhir['nama_dudi'] ?? ''), 'attr') ?>" />
                        </div>
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Bidang Perusahaan</label>
                            <input type="text" name="bidang_dudi" class="form-control form-control-solid" value="<?= esc((string) ($tracer_terakhir['bidang_dudi'] ?? ''), 'attr') ?>" />
                        </div>
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Alamat</label>
                            <textarea name="alamat_dudi" class="form-control form-control-solid" rows="3"><?= esc((string) ($tracer_terakhir['alamat_dudi'] ?? '')) ?></textarea>
                        </div>
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Tahun Mulai Kerja</label>
                            <input type="number" name="tahun_mulai_kerja" class="form-control form-control-solid" min="2000" max="<?= date('Y') ?>" value="<?= esc((string) ($tracer_terakhir['tahun_mulai_kerja'] ?? ''), 'attr') ?>" />
                        </div>
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Penghasilan</label>
                            <select name="penghasilan_range" class="form-select form-select-solid">
                                <option value="">-- Pilih Range --</option>
                                <?php foreach (
                                    [
                                        '< 1 juta' => 'Di bawah Rp 1 juta',
                                        '1-2 juta' => 'Rp 1 - 2 juta',
                                        '2-5 juta' => 'Rp 2 - 5 juta',
                                        '> 5 juta' => 'Di atas Rp 5 juta',
                                    ] as $val => $label
                                ): ?>
                                    <option value="<?= esc($val, 'attr') ?>" <?= (($tracer_terakhir['penghasilan_range'] ?? '') === $val) ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="kt_tracer_form_kuliah" class="d-none">
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Universitas</label>
                            <input type="text" name="universitas" class="form-control form-control-solid" value="<?= esc((string) ($tracer_terakhir['universitas'] ?? ''), 'attr') ?>" />
                        </div>
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Program Studi</label>
                            <input type="text" name="program_studi" class="form-control form-control-solid" value="<?= esc((string) ($tracer_terakhir['program_studi'] ?? ''), 'attr') ?>" />
                        </div>
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Status Kuliah</label>
                            <select name="status_kuliah" class="form-select form-select-solid">
                                <option value="">-- Pilih Status --</option>
                                <?php foreach (['Aktif', 'Cuti', 'Lulus'] as $s): ?>
                                    <option value="<?= esc($s, 'attr') ?>" <?= (($tracer_terakhir['status_kuliah'] ?? '') === $s) ? 'selected' : '' ?>>
                                        <?= esc($s) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="kt_tracer_form_wirausaha" class="d-none">
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Nama Usaha</label>
                            <input type="text" name="nama_usaha" class="form-control form-control-solid" value="<?= esc((string) ($tracer_terakhir['nama_usaha'] ?? ''), 'attr') ?>" />
                        </div>
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Bidang Usaha</label>
                            <input type="text" name="bidang_usaha" class="form-control form-control-solid" value="<?= esc((string) ($tracer_terakhir['bidang_usaha'] ?? ''), 'attr') ?>" />
                        </div>
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Modal Awal</label>
                            <input type="number" name="modal_awal" class="form-control form-control-solid" value="<?= esc((string) ($tracer_terakhir['modal_awal'] ?? ''), 'attr') ?>" />
                        </div>
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Penghasilan</label>
                            <select name="penghasilan_usaha" class="form-select form-select-solid">
                                <option value="">-- Pilih Range --</option>
                                <?php foreach (
                                    [
                                        '< 1 juta' => 'Di bawah Rp 1 juta',
                                        '1-2 juta' => 'Rp 1 - 2 juta',
                                        '2-5 juta' => 'Rp 2 - 5 juta',
                                        '> 5 juta' => 'Di atas Rp 5 juta',
                                    ] as $val => $label
                                ): ?>
                                    <option value="<?= esc($val, 'attr') ?>" <?= (($tracer_terakhir['penghasilan_usaha'] ?? '') === $val) ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="kt_tracer_form_belum_bekerja" class="d-none">
                        <div class="fv-row mb-5">
                            <label class="fw-semibold fs-6 mb-2">Rencana Kuliah Di</label>
                            <input type="text" name="rencana_kedepan" class="form-control form-control-solid" value="<?= esc((string) ($tracer_terakhir['rencana_kedepan'] ?? ''), 'attr') ?>" />
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-kt-tracer-modal-action="cancel">Batal</button>
                        <?php if ($isPelamarView): ?>
                            <button type="submit" class="btn btn-light-primary me-3" data-kt-tracer-modal-action="submit" data-tracer-status="draft">
                                <span class="indicator-label">Simpan Draft</span>
                                <span class="indicator-progress">Menyimpan...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                            <button type="submit" class="btn btn-primary" data-kt-tracer-modal-action="submit" data-tracer-status="terkirim">
                                <span class="indicator-label">Kirim Tracer</span>
                                <span class="indicator-progress">Mengirim...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary" data-kt-tracer-modal-action="submit" data-tracer-status="draft">
                                <span class="indicator-label">Simpan</span>
                                <span class="indicator-progress">Menyimpan...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--end::Modal Edit Tracer-->
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<script>
    window.pelamarDetailConfig = {
        pelamarId: <?= (int) $pelamar['id_pelamar'] ?>,
        accountId: '<?= esc((string) ($pelamar['account_id'] ?? '')) ?>',
        updateUrl: '<?= $isBackofficeView ? site_url($backofficePrefix . '/pelamar/update') : site_url('pelamar/profil/update') ?>',
        updateEmailUrl: '<?= $isBackofficeView ? site_url($backofficePrefix . '/pelamar/update-email') : site_url('pelamar/profil/update-email') ?>',
        updatePasswordUrl: '<?= $isBackofficeView ? site_url($backofficePrefix . '/pelamar/update-password') : site_url('pelamar/profil/update-password') ?>',
        simpanTracerUrl: '<?= $isBackofficeView ? site_url($backofficePrefix . '/pelamar/simpan-tracer') : site_url('pelamar/profil/simpan-tracer') ?>',
        simpanRiwayatUrl: '<?= $isBackofficeView ? site_url($backofficePrefix . '/pelamar/simpan-riwayat-kerja') : site_url('pelamar/profil/simpan-riwayat-kerja') ?>',
        updateRiwayatUrl: '<?= $isBackofficeView ? site_url($backofficePrefix . '/pelamar/update-riwayat-kerja') : site_url('pelamar/profil/update-riwayat-kerja') ?>',
        hapusRiwayatUrl: '<?= $isBackofficeView ? site_url($backofficePrefix . '/pelamar/hapus-riwayat-kerja') : site_url('pelamar/profil/hapus-riwayat-kerja') ?>',
        hapusRiwayatMethod: '<?= $isBackofficeView ? 'GET' : 'POST' ?>',
        uploadBerkasUrl: '<?= $isBackofficeView ? site_url($backofficePrefix . '/pelamar/upload-berkas') : site_url('pelamar/profil/upload-berkas') ?>',
        hapusBerkasUrl: '<?= $isBackofficeView ? site_url($backofficePrefix . '/pelamar/hapus-berkas') : site_url('pelamar/profil/hapus-berkas') ?>',
        hapusBerkasMethod: '<?= $isBackofficeView ? 'GET' : 'POST' ?>'
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="<?= base_url('assets/js/custom/pelamar/detail.js') ?>"></script>
<?= $this->endSection() ?>
