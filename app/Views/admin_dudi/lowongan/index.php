<?php
/*
|-------------------------------------------------------------------
| VIEW LOWONGAN SAYA ADMIN DUDI
|-------------------------------------------------------------------
| View ini menampilkan lowongan milik perusahaan yang sedang login.
| Admin DUDI dapat mencari, memfilter, menambah, dan mengedit lowongan
| tanpa bisa memilih DUDI lain.
|
| Alur kerja:
| 1. Search dan filter status dikirim lewat query string.
| 2. Tabel menampilkan checkbox, identitas lowongan, jumlah pelamar,
|    status, dan aksi edit.
| 3. Modal tambah/edit menyimpan data ke controller Admin DUDI.
|
| Tips Debugging:
| - Jika tombol tambah tidak menyimpan, cek route admin-dudi/lowongan/simpan.
| - Jika edit tertolak, cek id_perusahaan lowongan harus sama dengan
|   id_perusahaan pada akun Admin DUDI login.
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

$datetimeLocal = static function (?string $tanggal): string {
    if ($tanggal === null || trim($tanggal) === '') {
        return '';
    }

    try {
        return (new DateTime($tanggal))->format('Y-m-d\TH:i');
    } catch (Throwable) {
        return '';
    }
};

$statusBadge = static function (?string $status): array {
    return match ((string) $status) {
        'aktif'      => ['badge badge-light-success', 'Aktif'],
        'draft'      => ['badge badge-light-secondary', 'Draft'],
        'ditutup'    => ['badge badge-light-danger', 'Ditutup'],
        'kadaluarsa' => ['badge badge-light-warning', 'Kadaluarsa'],
        default      => ['badge badge-light-secondary', '-'],
    };
};

$jenisPekerjaanOptions = [
    'fulltime' => 'Full Time',
    'parttime' => 'Part Time',
    'magang' => 'Magang',
    'kontrak' => 'Kontrak',
    'freelance' => 'Freelance',
];

$sistemKerjaOptions = [
    'onsite' => 'Onsite',
    'remote' => 'Remote',
    'hybrid' => 'Hybrid',
];

$pendidikanOptions = [
    'SMP' => 'SMP',
    'SMA/SMK' => 'SMA/SMK',
    'D3' => 'D3',
    'S1' => 'S1',
    'S2' => 'S2',
];

$blankFlyerUrl = base_url('assets/media/svg/files/blank-image.svg');
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('extra_css') ?>
<style>
    .kt-dudi-lowongan-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .kt-dudi-lowongan-thumb {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--bs-gray-300);
        background: var(--bs-light);
        flex-shrink: 0;
    }

    .kt-dudi-lowongan-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .kt-dudi-lowongan-clamp {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.5;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Lowongan Saya</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= site_url('admin-dudi/dashboard') ?>" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted"><?= esc((string) ($perusahaan['nama_perusahaan'] ?? 'DUDI')) ?></li>
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
                <form method="get" action="<?= site_url('admin-dudi/lowongan') ?>" class="d-flex flex-stack flex-wrap gap-4 w-100">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" name="q" class="form-control form-control-solid w-250px ps-13" placeholder="Cari lowongan" value="<?= esc($keyword) ?>" />
                        </div>
                    </div>

                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end gap-3">
                            <select name="status" class="form-select form-select-solid w-175px">
                                <option value="">Semua Status</option>
                                <?php foreach ($daftarStatus as $value => $label): ?>
                                    <option value="<?= esc($value, 'attr') ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-light-primary">Filter</button>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_tambah_lowongan_dudi">
                                <i class="ki-duotone ki-plus fs-2"></i>Tambah Lowongan
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body py-4">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target=".kt-dudi-lowongan-check" value="1" />
                                </div>
                            </th>
                            <th class="min-w-280px">Lowongan</th>
                            <th class="min-w-150px">Posisi</th>
                            <th class="min-w-250px">Kualifikasi</th>
                            <th class="min-w-150px">Batas Lamaran</th>
                            <th class="min-w-120px">Pelamar</th>
                            <th class="min-w-120px">Status</th>
                            <th class="text-end min-w-100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 fw-semibold">
                        <?php if ($lowongan === []): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-10">Belum ada lowongan yang bisa ditampilkan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lowongan as $item): ?>
                                <?php
                                $idLowongan = (int) ($item['id_lowongan'] ?? 0);
                                $modalEditId = 'kt_modal_edit_lowongan_dudi_' . $idLowongan;
                                [$badgeClass, $badgeLabel] = $statusBadge($item['status'] ?? null);
                                $flyerUrl = ! empty($item['flyer_lowongan']) ? base_url((string) $item['flyer_lowongan']) : $blankFlyerUrl;
                                $kualifikasi = trim((string) ($item['kualifikasi'] ?? ''));
                                ?>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input kt-dudi-lowongan-check" type="checkbox" value="<?= $idLowongan ?>" />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="kt-dudi-lowongan-item">
                                            <div class="kt-dudi-lowongan-thumb">
                                                <img src="<?= esc($flyerUrl) ?>" alt="<?= esc((string) ($item['judul_lowongan'] ?? 'Lowongan')) ?>">
                                            </div>
                                            <div>
                                                <div class="fw-bold text-gray-900"><?= esc((string) ($item['judul_lowongan'] ?? '-')) ?></div>
                                                <div class="text-muted fs-7"><?= esc((string) ($perusahaan['nama_perusahaan'] ?? '-')) ?></div>
                                                <div class="d-flex flex-wrap gap-2 mt-2">
                                                    <span class="badge badge-light-primary"><?= esc(ucfirst((string) ($item['jenis_pekerjaan'] ?? '-'))) ?></span>
                                                    <span class="badge badge-light-info"><?= esc(ucfirst((string) ($item['sistem_kerja'] ?? '-'))) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= esc((string) ($item['posisi'] ?? '-')) ?></td>
                                    <td>
                                        <?php if ($kualifikasi !== ''): ?>
                                            <span class="kt-dudi-lowongan-clamp"><?= nl2br(esc($kualifikasi)) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Belum diisi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($formatTanggal($item['batas_lamaran'] ?? null)) ?></td>
                                    <td><span class="badge badge-light-primary"><?= (int) ($jumlahLamaran[$idLowongan] ?? 0) ?> pelamar</span></td>
                                    <td><span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-icon btn-active-light-warning w-30px h-30px" data-bs-toggle="modal" data-bs-target="#<?= esc($modalEditId) ?>" title="Edit Lowongan">
                                            <i class="ki-duotone ki-pencil fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </button>
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

<?php
/*
|-------------------------------------------------------------------
| MODAL TAMBAH LOWONGAN
|-------------------------------------------------------------------
| Modal ini khusus Admin DUDI. Tidak ada dropdown DUDI karena sistem
| otomatis memakai perusahaan login sebagai pemilik lowongan.
|
| Tips Debugging:
| - Jika simpan gagal dengan pesan kerjasama, pastikan perusahaan
|   memiliki relasi tb_kerjasama slug `rekrutmen`.
*/
?>
<div class="modal fade" id="kt_modal_tambah_lowongan_dudi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Tambah Lowongan</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <form action="<?= site_url('admin-dudi/lowongan/simpan') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body px-5 py-7">
                    <?= view('admin_dudi/lowongan/_form', [
                        'mode' => 'tambah',
                        'item' => [],
                        'perusahaan' => $perusahaan,
                        'blankFlyerUrl' => $blankFlyerUrl,
                        'jenisPekerjaanOptions' => $jenisPekerjaanOptions,
                        'sistemKerjaOptions' => $sistemKerjaOptions,
                        'pendidikanOptions' => $pendidikanOptions,
                        'daftarStatus' => $daftarStatus,
                        'datetimeLocal' => $datetimeLocal,
                    ]) ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Lowongan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($lowongan as $item): ?>
    <?php $idLowongan = (int) ($item['id_lowongan'] ?? 0); ?>
    <div class="modal fade" id="kt_modal_edit_lowongan_dudi_<?= $idLowongan ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-850px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Lowongan</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <form action="<?= site_url('admin-dudi/lowongan/update/' . $idLowongan) ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="modal-body px-5 py-7">
                        <?= view('admin_dudi/lowongan/_form', [
                            'mode' => 'edit',
                            'item' => $item,
                            'perusahaan' => $perusahaan,
                            'blankFlyerUrl' => $blankFlyerUrl,
                            'jenisPekerjaanOptions' => $jenisPekerjaanOptions,
                            'sistemKerjaOptions' => $sistemKerjaOptions,
                            'pendidikanOptions' => $pendidikanOptions,
                            'daftarStatus' => $daftarStatus,
                            'datetimeLocal' => $datetimeLocal,
                        ]) ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?= $this->endSection() ?>
