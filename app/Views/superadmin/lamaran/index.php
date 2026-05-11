<?php
/*
|-------------------------------------------------------------------
| VIEW DATA LAMARAN SUPERADMIN
|-------------------------------------------------------------------
| View ini menjadi pusat monitoring lamaran masuk. Super Admin dapat
| mencari lamaran, memfilter berdasarkan DUDI/status, mencetak daftar,
| melihat detail lowongan, meninjau dokumen snapshot, dan mengubah
| status utama lamaran.
|
| Alur kerja:
| 1. Toolbar menyediakan search, filter, dan cetak.
| 2. Tabel menampilkan ringkasan lamaran yang mudah dipindai.
| 3. Modal per baris dipakai untuk detail lowongan, review dokumen,
|    dan perubahan status utama lamaran.
|
| Tips Debugging:
| - Jika modal tidak terbuka, cek data-bs-target dan id modal per baris.
| - Jika dokumen snapshot kosong, cek isi detailMap dari controller.
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

$bolehUbahStatus = $bolehUbahStatus ?? true;
$areaPrefix = (string) ($areaPrefix ?? (session()->get('slug_peran') === 'admin_sekolah' ? 'admin-sekolah' : 'superadmin'));
$routeBase = $areaPrefix . '/lamaran';
$dashboardUrl = (string) ($dashboardUrl ?? base_url($areaPrefix === 'admin-sekolah' ? 'admin-sekolah/dashboard' : 'dashboard/superadmin'));
$pageHeading = (string) ($pageHeading ?? 'Data Lamaran');
$breadcrumbParent = (string) ($breadcrumbParent ?? 'Manajemen Pengguna');
$breadcrumbCurrent = (string) ($breadcrumbCurrent ?? 'Data Lamaran');
$ringkasanStatus = is_array($ringkasanStatus ?? null) ? $ringkasanStatus : [];
$summaryCards = [
    [
        'label' => 'Total Lamaran',
        'value' => (int) ($ringkasanStatus['total'] ?? count($lamaran ?? [])),
        'class' => 'primary',
        'icon' => 'ki-document',
    ],
    [
        'label' => 'Menunggu Review',
        'value' => (int) ($ringkasanStatus['menunggu_verifikasi'] ?? 0),
        'class' => 'warning',
        'icon' => 'ki-timer',
    ],
    [
        'label' => 'Perlu Perbaikan',
        'value' => (int) ($ringkasanStatus['perlu_perbaikan_berkas'] ?? 0),
        'class' => 'danger',
        'icon' => 'ki-information-5',
    ],
    [
        'label' => 'Dalam Proses',
        'value' => (int) ($ringkasanStatus['diproses'] ?? 0) + (int) ($ringkasanStatus['wawancara'] ?? 0),
        'class' => 'info',
        'icon' => 'ki-arrow-right-left',
    ],
    [
        'label' => 'Diterima',
        'value' => (int) ($ringkasanStatus['diterima'] ?? 0),
        'class' => 'success',
        'icon' => 'ki-check-circle',
    ],
];

$reviewBadge = static function (?string $status): array {
    return match ((string) $status) {
        'sesuai'          => ['badge badge-light-success', 'Sesuai'],
        'perlu_perbaikan' => ['badge badge-light-warning', 'Perlu Perbaikan'],
        'ditolak'         => ['badge badge-light-danger', 'Ditolak'],
        default           => ['badge badge-light-secondary', 'Menunggu'],
    };
};
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('extra_css') ?>
<style>
    .kt-lamaran-pelamar {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .kt-lamaran-pelamar__name {
        color: var(--bs-gray-800);
        font-weight: 700;
    }

    .kt-lamaran-lowongan-summary {
        border: 1px dashed var(--bs-gray-300);
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        background: var(--bs-light);
    }

    .kt-lamaran-print-meta {
        display: none;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #kt_lamaran_print_area,
        #kt_lamaran_print_area * {
            visibility: visible;
        }

        #kt_lamaran_print_area {
            position: absolute;
            inset: 0;
            width: 100%;
            background: #fff;
        }

        .app-sidebar,
        .app-header,
        .app-toolbar,
        .card-header,
        .kt-lamaran-actions,
        .modal,
        .btn {
            display: none !important;
        }

        .kt-lamaran-print-meta {
            display: block;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0"><?= esc($pageHeading) ?></h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= esc($dashboardUrl, 'attr') ?>" class="text-muted text-hover-primary">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted"><?= esc($breadcrumbParent) ?></li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted"><?= esc($breadcrumbCurrent) ?></li>
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

        <div class="row g-5 g-xl-8 mb-8">
            <?php foreach ($summaryCards as $card): ?>
                <div class="col-sm-6 col-xl">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center gap-4">
                            <div class="symbol symbol-45px">
                                <div class="symbol-label bg-light-<?= esc($card['class']) ?>">
                                    <i class="ki-duotone <?= esc($card['icon']) ?> fs-2 text-<?= esc($card['class']) ?>">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted fs-8 text-uppercase fw-bold mb-1"><?= esc($card['label']) ?></div>
                                <div class="fs-2 fw-bold text-gray-900"><?= (int) $card['value'] ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <form method="get" action="<?= site_url($routeBase) ?>" class="d-flex flex-stack flex-wrap gap-4 w-100">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" name="q" class="form-control form-control-solid w-250px ps-13" placeholder="Cari lamaran" value="<?= esc($keyword) ?>" />
                        </div>
                    </div>

                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-filter fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>Filter
                            </button>

                            <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                                <div class="px-7 py-5">
                                    <div class="fs-5 text-dark fw-bold">Filter Options</div>
                                </div>
                                <div class="separator border-gray-200"></div>
                                <div class="px-7 py-5">
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">DUDI:</label>
                                        <select name="id_perusahaan" class="form-select form-select-solid fw-bold" data-kt-select2="true" data-placeholder="Pilih DUDI" data-allow-clear="true">
                                            <option></option>
                                            <?php foreach ($daftarPerusahaan as $perusahaan): ?>
                                                <option value="<?= (int) $perusahaan['id_perusahaan'] ?>" <?= (int) $perusahaanFilter === (int) $perusahaan['id_perusahaan'] ? 'selected' : '' ?>>
                                                    <?= esc((string) $perusahaan['nama_perusahaan']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Status Lamaran:</label>
                                        <select name="status" class="form-select form-select-solid fw-bold" data-kt-select2="true" data-placeholder="Pilih status" data-allow-clear="true" data-hide-search="true">
                                            <option></option>
                                            <?php foreach ($daftarStatus as $value => $label): ?>
                                                <option value="<?= esc($value, 'attr') ?>" <?= $statusFilter === $value ? 'selected' : '' ?>>
                                                    <?= esc($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <a href="<?= site_url($routeBase) ?>" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6">Reset</a>
                                        <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true">Apply</button>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary" id="kt_lamaran_print_button">
                                <i class="ki-duotone ki-printer fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>Cetak
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body py-4" id="kt_lamaran_print_area">
                <div class="kt-lamaran-print-meta mb-8">
                    <h2 class="fw-bold mb-2">Laporan Data Lamaran</h2>
                    <div class="text-muted">Dicetak pada <?= esc($formatTanggal(date('Y-m-d H:i:s'), true)) ?></div>
                </div>

                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-250px">Pelamar</th>
                            <th class="min-w-180px">DUDI</th>
                            <th class="min-w-180px">Posisi</th>
                            <th class="min-w-150px">Tanggal Melamar</th>
                            <th class="min-w-160px">Status</th>
                            <th class="text-end min-w-150px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        <?php if ($lamaran === []): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-10">Belum ada data lamaran yang bisa ditampilkan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lamaran as $item): ?>
                                <?php
                                $idLamaran = (int) ($item['id_lamaran'] ?? 0);
                                $modalLowonganId = 'kt_modal_detail_lowongan_' . $idLamaran;
                                $modalLamaranId = 'kt_modal_detail_lamaran_' . $idLamaran;
                                $modalStatusId = 'kt_modal_status_lamaran_' . $idLamaran;
                                [$badgeClass, $badgeLabel] = $statusBadge($item['status'] ?? null);
                                ?>
                                <tr>
                                    <td>
                                        <div class="kt-lamaran-pelamar">
                                            <span class="kt-lamaran-pelamar__name"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?></span>
                                            <span class="text-muted fs-7"><?= esc((string) ($item['email'] ?? '-')) ?></span>
                                            <span class="text-muted fs-8"><?= esc((string) ($item['account_id'] ?? '-')) ?></span>
                                        </div>
                                    </td>
                                    <td><?= esc((string) ($item['nama_perusahaan'] ?? '-')) ?></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-800 fw-bold"><?= esc((string) ($item['posisi'] ?? '-')) ?></span>
                                            <span class="text-muted fs-7"><?= esc((string) ($item['judul_lowongan'] ?? '-')) ?></span>
                                        </div>
                                    </td>
                                    <td><?= esc($formatTanggal($item['tanggal_melamar'] ?? null, true)) ?></td>
                                    <td><span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span></td>
                                    <td class="text-end kt-lamaran-actions">
                                        <button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px me-2" data-bs-toggle="modal" data-bs-target="#<?= esc($modalLowonganId) ?>" title="Detail Lowongan">
                                            <i class="ki-duotone ki-eye fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                        </button>
                                        <button type="button" class="btn btn-icon btn-active-light-info w-30px h-30px me-2" data-bs-toggle="modal" data-bs-target="#<?= esc($modalLamaranId) ?>" title="Detail Lamaran">
                                            <i class="ki-duotone ki-eye fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                        </button>
                                        <?php if ($bolehUbahStatus): ?>
                                            <button type="button" class="btn btn-icon btn-active-light-warning w-30px h-30px" data-bs-toggle="modal" data-bs-target="#<?= esc($modalStatusId) ?>" title="Ubah Status Lamaran">
                                                <i class="ki-duotone ki-setting-2 fs-3">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </button>
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

<?php foreach ($lamaran as $item): ?>
    <?php
    $idLamaran = (int) ($item['id_lamaran'] ?? 0);
    $modalLowonganId = 'kt_modal_detail_lowongan_' . $idLamaran;
    $modalLamaranId = 'kt_modal_detail_lamaran_' . $idLamaran;
    $modalStatusId = 'kt_modal_status_lamaran_' . $idLamaran;
    $dokumen = $detailMap[$idLamaran]['dokumen'] ?? [];
    $riwayat = $detailMap[$idLamaran]['riwayat'] ?? [];
    $flyerUrl = ! empty($item['flyer_lowongan']) ? base_url((string) $item['flyer_lowongan']) : base_url('assets/media/svg/files/blank-image.svg');
    [$badgeClass, $badgeLabel] = $statusBadge($item['status'] ?? null);
    ?>

    <div class="modal fade" id="<?= esc($modalLowonganId) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-800px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Detail Lowongan</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body px-5 py-7">
                    <div class="row g-6">
                        <div class="col-lg-5">
                            <img src="<?= esc($flyerUrl) ?>" alt="<?= esc((string) ($item['judul_lowongan'] ?? 'Lowongan')) ?>" class="w-100 rounded-4 border" />
                        </div>
                        <div class="col-lg-7">
                            <div class="fs-2 fw-bold text-gray-900 mb-2"><?= esc((string) ($item['judul_lowongan'] ?? '-')) ?></div>
                            <div class="text-muted fs-6 mb-4"><?= esc((string) ($item['nama_perusahaan'] ?? '-')) ?></div>
                            <div class="d-flex flex-wrap gap-2 mb-5">
                                <span class="badge badge-light-primary"><?= esc((string) ($item['posisi'] ?? '-')) ?></span>
                                <span class="badge badge-light-info"><?= esc(ucfirst((string) ($item['jenis_pekerjaan'] ?? '-'))) ?></span>
                                <span class="badge badge-light-success"><?= esc(ucfirst((string) ($item['sistem_kerja'] ?? '-'))) ?></span>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Pendidikan Min</div>
                                    <div class="fw-semibold text-gray-800"><?= esc((string) (($item['pendidikan_min'] ?? '') !== '' ? $item['pendidikan_min'] : '-')) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Batas Lamaran</div>
                                    <div class="fw-semibold text-gray-800"><?= esc($formatTanggal($item['batas_lamaran'] ?? null)) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Lokasi Kerja</div>
                                    <div class="fw-semibold text-gray-800"><?= esc((string) (($item['lokasi_kerja'] ?? '') !== '' ? $item['lokasi_kerja'] : '-')) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Rentang Gaji</div>
                                    <div class="fw-semibold text-gray-800"><?= esc((string) (($item['rentang_gaji'] ?? '') !== '' ? $item['rentang_gaji'] : '-')) ?></div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="fw-bold text-gray-900 mb-2">Deskripsi Pekerjaan</div>
                                <div class="text-gray-700 fs-7"><?= nl2br(esc((string) (($item['deskripsi_pekerjaan'] ?? '') !== '' ? $item['deskripsi_pekerjaan'] : '-'))) ?></div>
                            </div>

                            <div>
                                <div class="fw-bold text-gray-900 mb-2">Kualifikasi</div>
                                <div class="text-gray-700 fs-7"><?= nl2br(esc((string) (($item['kualifikasi'] ?? '') !== '' ? $item['kualifikasi'] : '-'))) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="<?= esc($modalLamaranId) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-1000px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Detail Lamaran</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body px-5 py-7">
                    <div class="row g-6 mb-8">
                        <div class="col-lg-4">
                            <div class="kt-lamaran-lowongan-summary h-100">
                                <div class="fs-5 fw-bold text-gray-900 mb-4">Ringkasan Pelamar</div>
                                <div class="mb-4">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Nama</div>
                                    <div class="fw-semibold text-gray-800"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?></div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Email</div>
                                    <div class="fw-semibold text-gray-800"><?= esc((string) ($item['email'] ?? '-')) ?></div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Account ID</div>
                                    <div class="fw-semibold text-gray-800"><?= esc((string) ($item['account_id'] ?? '-')) ?></div>
                                </div>
                                <div class="mb-5">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Status Lamaran</div>
                                    <span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span>
                                </div>
                                <a href="<?= site_url($areaPrefix . '/pelamar/detail/' . (int) ($item['id_pelamar'] ?? 0)) ?>" class="btn btn-sm btn-light-primary">Lihat Profil Pelamar Lengkap</a>
                            </div>
                        </div>

                                        <div class="col-lg-8">
                                            <div class="kt-lamaran-lowongan-summary h-100">
                                                <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
                                                    <div class="fs-5 fw-bold text-gray-900">Riwayat Status Lamaran</div>
                                                    <span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span>
                                                </div>
                                                <?php if ($riwayat === []): ?>
                                                    <div class="text-muted fs-7">Belum ada riwayat status yang tercatat.</div>
                                                <?php else: ?>
                                    <div class="d-flex flex-column gap-4">
                                        <?php foreach ($riwayat as $riwayatItem): ?>
                                            <?php [$historyBadgeClass, $historyBadgeLabel] = $statusBadge($riwayatItem['status_baru'] ?? null); ?>
                                            <div class="border border-dashed rounded p-4">
                                                <div class="d-flex flex-wrap justify-content-between gap-3 mb-2">
                                                    <span class="<?= esc($historyBadgeClass) ?>"><?= esc($historyBadgeLabel) ?></span>
                                                    <span class="text-muted fs-8"><?= esc($formatTanggal($riwayatItem['dibuat_pada'] ?? null, true)) ?></span>
                                                </div>
                                                <div class="text-gray-800 fs-7 mb-1">Oleh: <?= esc((string) (($riwayatItem['diubah_oleh_nama'] ?? '') !== '' ? $riwayatItem['diubah_oleh_nama'] : 'System')) ?></div>
                                                <div class="text-gray-600 fs-7"><?= esc((string) (($riwayatItem['catatan'] ?? '') !== '' ? $riwayatItem['catatan'] : 'Tanpa catatan tambahan.')) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <form action="<?= site_url($routeBase . '/update-review/' . $idLamaran) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
                            <div>
                                <div class="fs-5 fw-bold text-gray-900 mb-1">Review Dokumen Snapshot</div>
                                <div class="text-muted fs-7">Jika ada dokumen perlu perbaikan atau ditolak, status lamaran akan otomatis masuk Perlu Perbaikan Berkas.</div>
                            </div>
                        </div>

                        <?php if ($dokumen === []): ?>
                            <div class="alert alert-light-warning">Belum ada dokumen snapshot pada lamaran ini.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th>Dokumen</th>
                                            <th>Status Review</th>
                                            <th>Catatan Reviewer</th>
                                            <th class="text-end">File</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-700 fw-semibold">
                                        <?php foreach ($dokumen as $dokumenItem): ?>
                                            <?php [$reviewBadgeClass, $reviewBadgeLabel] = $reviewBadge($dokumenItem['status_review'] ?? null); ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="text-gray-900 fw-bold"><?= esc((string) ($dokumenItem['nama_berkas'] ?? '-')) ?></span>
                                                        <span class="text-muted fs-7"><?= esc((string) ($dokumenItem['nama_file_snapshot'] ?? '-')) ?></span>
                                                        <?php if (! empty($dokumenItem['wajib_saat_submit'])): ?>
                                                            <span class="badge badge-light-danger mt-2 w-auto">Wajib</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="min-w-200px">
                                                    <div class="mb-2">
                                                        <span class="<?= esc($reviewBadgeClass) ?>"><?= esc($reviewBadgeLabel) ?></span>
                                                    </div>
                                                    <select name="status_review[<?= (int) $dokumenItem['id_lamaran_berkas'] ?>]" class="form-select form-select-solid" data-kt-select2="true" data-hide-search="true">
                                                        <?php foreach ($daftarReview as $value => $label): ?>
                                                            <option value="<?= esc($value, 'attr') ?>" <?= ($dokumenItem['status_review'] ?? '') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td class="min-w-250px">
                                                    <textarea name="catatan_review[<?= (int) $dokumenItem['id_lamaran_berkas'] ?>]" class="form-control form-control-solid" rows="3" placeholder="Catatan review dokumen"><?= esc((string) ($dokumenItem['catatan_review'] ?? '')) ?></textarea>
                                                </td>
                                                <td class="text-end">
                                                    <a href="<?= base_url((string) ($dokumenItem['path_file_snapshot'] ?? '')) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light-primary">Lihat File</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-end pt-4">
                                <button type="submit" class="btn btn-primary">Simpan Review Dokumen</button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($bolehUbahStatus): ?>
    <div class="modal fade" id="<?= esc($modalStatusId) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Ubah Status Lamaran</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body px-5 py-7">
                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5 mb-6">
                        <div class="fw-semibold text-gray-700 fs-7">
                            Perubahan di form ini akan memperbarui <code>tb_lamaran.status</code> dan otomatis menambah histori baru ke <code>tb_lamaran_status</code>.
                        </div>
                    </div>

                    <form action="<?= site_url($routeBase . '/update-status/' . $idLamaran) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="mb-5">
                            <label class="form-label fw-semibold">Pelamar</label>
                            <div class="form-control form-control-solid"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?> - <?= esc((string) ($item['nama_perusahaan'] ?? '-')) ?></div>
                        </div>

                        <div class="mb-5">
                            <label class="required form-label fw-semibold">Status Baru</label>
                            <select name="status_baru" class="form-select form-select-solid" data-kt-select2="true" data-hide-search="true" required>
                                <?php foreach ($daftarStatus as $value => $label): ?>
                                    <option value="<?= esc($value, 'attr') ?>" <?= ($item['status'] ?? '') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-semibold">Batas Perbaikan Berkas</label>
                            <input type="date" name="batas_perbaikan_berkas" class="form-control form-control-solid" value="<?= esc((string) ($item['batas_perbaikan_berkas'] ?? ''), 'attr') ?>">
                            <div class="form-text">Isi jika status diarahkan ke perbaikan berkas.</div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-semibold">Tanggal Wawancara</label>
                            <?php
                            $tanggalWawancaraValue = '';
                            if (! empty($item['tanggal_wawancara'])) {
                                try {
                                    $tanggalWawancaraValue = (new DateTime((string) $item['tanggal_wawancara']))->format('Y-m-d\TH:i');
                                } catch (Throwable) {
                                    $tanggalWawancaraValue = '';
                                }
                            }
                            ?>
                            <input type="datetime-local" name="tanggal_wawancara" class="form-control form-control-solid" value="<?= esc($tanggalWawancaraValue, 'attr') ?>">
                        </div>

                        <div class="mb-8">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea name="catatan" class="form-control form-control-solid" rows="4" placeholder="Tulis alasan atau instruksi tindak lanjut untuk pelamar"></textarea>
                            <div class="form-text">Wajib diisi jika status diubah menjadi Perlu Perbaikan Berkas atau Ditolak.</div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-warning">Simpan Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $('[data-kt-select2="true"]').each(function () {
                var hideSearch = $(this).data('hide-search') === true;
                var allowClear = $(this).data('allow-clear') === true;

                $(this).select2({
                    minimumResultsForSearch: hideSearch ? Infinity : 0,
                    allowClear: allowClear,
                    dropdownParent: $(this).closest('.modal, .menu')
                });
            });
        }

        var printButton = document.getElementById('kt_lamaran_print_button');
        if (printButton) {
            printButton.addEventListener('click', function () {
                window.print();
            });
        }
    });
</script>
<?= $this->endSection() ?>
