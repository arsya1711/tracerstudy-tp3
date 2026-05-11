<?php
/*
|-------------------------------------------------------------------
| VIEW DATA LOWONGAN
|-------------------------------------------------------------------
| View ini menampilkan daftar lowongan kerja dengan pola DataTables
| server-side, search, filter DUDI/status, serta modal tambah/edit.
|
| Alur kerja:
| 1. Toolbar menyediakan pencarian, filter, dan tombol tambah.
| 2. Data tabel dimuat via AJAX dari controller lowongan.
| 3. Modal tambah/edit dipakai untuk mengelola data tanpa pindah halaman.
|
| Tips Debugging:
| - Jika tabel kosong, pastikan route AJAX dan file JS lowongan termuat.
| - Jika modal tidak tampil, periksa atribut data-bs-target dan id modal.
*/
?>
<?= $this->extend('layouts/main') ?>
<?php
/*
|-------------------------------------------------------------------
| KONTEKS VIEW LOWONGAN
|-------------------------------------------------------------------
| View ini dipakai di Super Admin sebagai Data Lowongan dan di Admin
| Sekolah/BKK sebagai Lowongan Kerja. Konteks menentukan judul,
| breadcrumb, dan URL AJAX yang dipakai frontend.
|
| Tips Debugging:
| - Jika filter atau tombol tambah lowongan gagal, cek object
|   window.ktLowonganConfig pada bagian extra_js.
*/
$blankFlyerUrl = base_url('assets/media/svg/files/blank-image.svg');
$areaPrefix = (string) ($areaPrefix ?? (session()->get('slug_peran') === 'admin_sekolah' ? 'admin-sekolah' : 'superadmin'));
$dashboardUrl = (string) ($dashboardUrl ?? base_url($areaPrefix === 'admin-sekolah' ? 'admin-sekolah/dashboard' : 'dashboard/superadmin'));
$pageHeading = (string) ($pageHeading ?? 'Data Lowongan');
$breadcrumbParent = (string) ($breadcrumbParent ?? 'Manajemen DUDI');
$breadcrumbCurrent = (string) ($breadcrumbCurrent ?? 'Lowongan Kerja');
?>

<?= $this->section('extra_css') ?>
<?php
/*
|-------------------------------------------------------------------
| CSS KHUSUS DATA LOWONGAN
|-------------------------------------------------------------------
| Style ini dipakai untuk membuat tampilan tabel lowongan terasa
| lebih ringkas, rapi, dan seirama dengan modul Data DUDI.
|
| Tips Debugging:
| - Jika thumbnail flyer tidak proporsional, periksa class wrapper
|   pada kolom judul yang dirender dari lowongan.js.
*/
?>
<style>
    .kt-lowongan-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .kt-lowongan-thumb {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--bs-gray-300);
        background: var(--bs-light);
        flex-shrink: 0;
    }

    .kt-lowongan-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .kt-lowongan-content {
        min-width: 0;
    }

    .kt-lowongan-title {
        display: block;
        color: var(--bs-gray-800);
        font-weight: 700;
        margin-bottom: 2px;
    }

    .kt-lowongan-helper {
        display: block;
        color: var(--bs-gray-600);
        font-size: 12px;
        line-height: 1.45;
    }

    .kt-lowongan-badges {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    .kt-lowongan-clamp {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.5;
    }

    .kt-lowongan-meta {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 6px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
/*
|-------------------------------------------------------------------
| TOOLBAR HALAMAN
|-------------------------------------------------------------------
| Toolbar menjaga pola navigasi tetap konsisten dengan modul lain:
| menampilkan judul halaman dan breadcrumb untuk memudahkan orientasi.
|
| Tips Debugging:
| - Jika breadcrumb tidak sesuai, periksa urutan label modul DUDI.
*/
?>
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

<?php
/*
|-------------------------------------------------------------------
| KARTU TABEL LOWONGAN
|-------------------------------------------------------------------
| Bagian ini menjadi pusat interaksi pengguna: search, filter, aksi
| massal, dan tabel daftar lowongan yang dimuat secara dinamis.
|
| Tips Debugging:
| - Jika filter tidak bekerja, cek atribut data-kt-lowongan-table-filter.
| - Jika aksi massal tidak muncul, cek checkbox baris dari DataTables.
*/
?>
<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input type="text" data-kt-lowongan-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari lowongan" />
                    </div>
                </div>
                <div class="card-toolbar">
                    <div class="d-flex justify-content-end" data-kt-lowongan-table-toolbar="base">
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            <i class="ki-duotone ki-filter fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Filter
                        </button>
                        <div class="menu menu-sub menu-sub-dropdown w-300px w-md-350px" data-kt-menu="true">
                            <div class="px-7 py-5">
                                <div class="fs-5 text-dark fw-bold">Filter Lowongan</div>
                            </div>
                            <div class="separator border-gray-200"></div>
                            <div class="px-7 py-5" data-kt-lowongan-table-filter="form">
                                <div class="mb-10">
                                    <label class="form-label fs-6 fw-semibold">DUDI:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true" data-placeholder="Pilih DUDI" data-allow-clear="true" data-kt-lowongan-table-filter="perusahaan">
                                        <option></option>
                                        <?php foreach ($daftar_perusahaan as $perusahaan): ?>
                                            <option value="<?= (int) $perusahaan['id_perusahaan'] ?>"><?= esc((string) $perusahaan['nama_perusahaan']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-10">
                                    <label class="form-label fs-6 fw-semibold">Status:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true" data-placeholder="Pilih status" data-allow-clear="true" data-kt-lowongan-table-filter="status" data-hide-search="true">
                                        <option></option>
                                        <?php foreach ($daftar_status as $statusValue => $statusLabel): ?>
                                            <option value="<?= esc($statusValue, 'attr') ?>"><?= esc($statusLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6" data-kt-menu-dismiss="true" data-kt-lowongan-table-filter="reset">Reset</button>
                                    <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true" data-kt-lowongan-table-filter="filter">Apply</button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_tambah_lowongan">
                            <i class="ki-duotone ki-plus fs-2"></i>Tambah Lowongan
                        </button>
                    </div>

                    <div class="d-flex justify-content-end align-items-center d-none" data-kt-lowongan-table-toolbar="selected">
                        <div class="fw-bold me-5">
                            <span class="me-2" data-kt-lowongan-table-select="selected_count"></span>Selected
                        </div>
                        <button type="button" class="btn btn-danger" data-kt-lowongan-table-select="delete_selected">Hapus Terpilih</button>
                    </div>
                </div>
            </div>

            <div class="card-body py-4">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_lowongan">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </th>
                            <th class="min-w-200px">DUDI</th>
                            <th class="min-w-220px">Judul</th>
                            <th class="min-w-150px">Posisi</th>
                            <th class="min-w-250px">Kualifikasi</th>
                            <th class="min-w-180px">Pemosting</th>
                            <th class="min-w-120px">Status</th>
                            <th class="text-end min-w-120px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
/*
|-------------------------------------------------------------------
| OPSI DROPDOWN FORM
|-------------------------------------------------------------------
| Nilai ini dipakai bersama untuk modal tambah dan edit agar opsi
| tetap konsisten antara frontend dan data yang divalidasi backend.
|
| Tips Debugging:
| - Jika opsi select tidak muncul, pastikan array ini terdefinisi
|   sebelum modal dirender.
*/
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
?>

<?php
/*
|-------------------------------------------------------------------
| MODAL TAMBAH LOWONGAN
|-------------------------------------------------------------------
| Modal ini dipakai untuk input data lowongan baru, termasuk flyer,
| perusahaan tujuan, serta informasi posisi dan kualifikasi kerja.
|
| Tips Debugging:
| - Jika select DUDI kosong, pastikan perusahaan punya kerjasama
|   rekrutmen dan controller mengirimkan $daftar_perusahaan.
*/
?>
<div class="modal fade" id="kt_modal_tambah_lowongan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_tambah_lowongan_header">
                <h2 class="fw-bold">Tambah Lowongan</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-lowongan-modal-action="close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_tambah_lowongan_form" class="form" action="#">
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_tambah_lowongan_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_tambah_lowongan_header" data-kt-scroll-wrappers="#kt_modal_tambah_lowongan_scroll" data-kt-scroll-offset="300px">
                        <?php
                        /*
                        |-------------------------------------------------------------------
                        | AREA UPLOAD FLYER
                        |-------------------------------------------------------------------
                        | Pengguna bisa menambahkan gambar poster/flyer agar lowongan
                        | lebih menarik saat ditampilkan di daftar publik maupun detail.
                        |
                        | Tips Debugging:
                        | - Jika preview tidak berubah, periksa binding JS pada elemen
                        |   data-kt-lowongan-flyer-input.
                        */
                        ?>
                        <div class="fv-row mb-7">
                            <label class="d-block fw-semibold fs-6 mb-5">Flyer Lowongan</label>
                            <div class="image-input image-input-outline image-input-placeholder image-input-empty" data-kt-image-input="true" data-kt-lowongan-flyer-input="true" data-image-input-initial="" data-image-input-placeholder="<?= esc($blankFlyerUrl, 'attr') ?>">
                                <div class="image-input-wrapper w-150px h-150px" style="background-image: url('<?= esc($blankFlyerUrl) ?>');"></div>
                                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change flyer">
                                    <i class="ki-duotone ki-pencil fs-7">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <input type="file" name="flyer_lowongan" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="flyer_remove" />
                                </label>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel flyer">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove flyer">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="form-text">Allowed file types: png, jpg, jpeg. Maksimal 4 MB.</div>
                        </div>

                        <?php
                        /*
                        |-------------------------------------------------------------------
                        | INFORMASI INTI LOWONGAN
                        |-------------------------------------------------------------------
                        | Bagian ini menyimpan identitas utama lowongan: perusahaan,
                        | judul, posisi, dan deskripsi singkat untuk kebutuhan publikasi.
                        */
                        ?>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">DUDI</label>
                            <select name="id_perusahaan" class="form-select form-select-solid" data-kt-select2="true" data-placeholder="Pilih DUDI" required>
                                <option></option>
                                <?php foreach ($daftar_perusahaan as $perusahaan): ?>
                                    <option value="<?= (int) $perusahaan['id_perusahaan'] ?>"><?= esc((string) $perusahaan['nama_perusahaan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Hanya DUDI dengan kerjasama rekrutmen yang bisa dipilih.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Judul Lowongan</label>
                                <input type="text" name="judul_lowongan" class="form-control form-control-solid" placeholder="Contoh: Lowongan Operator Produksi" required />
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Posisi</label>
                                <input type="text" name="posisi" class="form-control form-control-solid" placeholder="Contoh: Operator Produksi" required />
                            </div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Kualifikasi</label>
                            <textarea name="kualifikasi" class="form-control form-control-solid" rows="4" placeholder="Tuliskan persyaratan utama pelamar"></textarea>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Deskripsi Pekerjaan</label>
                            <textarea name="deskripsi_pekerjaan" class="form-control form-control-solid" rows="4" placeholder="Tuliskan tugas dan tanggung jawab pekerjaan"></textarea>
                        </div>

                        <?php
                        /*
                        |-------------------------------------------------------------------
                        | PENGATURAN TIPE PEKERJAAN
                        |-------------------------------------------------------------------
                        | Menentukan jenis pekerjaan, sistem kerja, dan status tayang
                        | agar proses manajemen lowongan lebih terstruktur.
                        */
                        ?>
                        <div class="row">
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Jenis Pekerjaan</label>
                                <select name="jenis_pekerjaan" class="form-select form-select-solid" data-kt-select2="true" data-hide-search="true">
                                    <?php foreach ($jenisPekerjaanOptions as $value => $label): ?>
                                        <option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Sistem Kerja</label>
                                <select name="sistem_kerja" class="form-select form-select-solid" data-kt-select2="true" data-hide-search="true">
                                    <?php foreach ($sistemKerjaOptions as $value => $label): ?>
                                        <option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Status</label>
                                <select name="status" class="form-select form-select-solid" data-kt-select2="true" data-hide-search="true">
                                    <?php foreach ($daftar_status as $value => $label): ?>
                                        <option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <?php
                        /*
                        |-------------------------------------------------------------------
                        | KEBUTUHAN DAN KRITERIA PELAMAR
                        |-------------------------------------------------------------------
                        | Admin dapat menentukan pendidikan minimum, jumlah kebutuhan,
                        | dan pengalaman agar lowongan lebih spesifik dan mudah difilter.
                        */
                        ?>
                        <div class="row">
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Pendidikan Minimum</label>
                                <select name="pendidikan_min" class="form-select form-select-solid" data-kt-select2="true" data-placeholder="Pilih pendidikan" data-allow-clear="true">
                                    <option></option>
                                    <?php foreach ($pendidikanOptions as $value => $label): ?>
                                        <option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Jumlah Kebutuhan</label>
                                <input type="number" name="jumlah_kebutuhan" class="form-control form-control-solid" min="1" value="1" />
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Pengalaman Minimum</label>
                                <input type="text" name="pengalaman_min" class="form-control form-control-solid" placeholder="Contoh: 1 tahun" />
                            </div>
                        </div>

                        <?php
                        /*
                        |-------------------------------------------------------------------
                        | LOKASI DAN BATAS WAKTU
                        |-------------------------------------------------------------------
                        | Data ini membantu sistem membatasi masa tayang lowongan dan
                        | memberi informasi deadline yang jelas kepada pelamar.
                        */
                        ?>
                        <div class="row">
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Rentang Gaji</label>
                                <input type="text" name="rentang_gaji" class="form-control form-control-solid" placeholder="Contoh: 3 - 5 juta" />
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Lokasi Kerja</label>
                                <input type="text" name="lokasi_kerja" class="form-control form-control-solid" placeholder="Contoh: Bekasi" />
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Batas Lamaran</label>
                                <input type="date" name="batas_lamaran" class="form-control form-control-solid" />
                            </div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Tayang Hingga</label>
                            <input type="datetime-local" name="tayang_hingga" class="form-control form-control-solid" />
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-kt-lowongan-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-primary" data-kt-lowongan-modal-action="submit">
                            <span class="indicator-label">Submit</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
/*
|-------------------------------------------------------------------
| MODAL EDIT LOWONGAN
|-------------------------------------------------------------------
| Struktur modal edit dibuat mirip dengan modal tambah agar pengguna
| tidak perlu beradaptasi ulang saat memperbarui data yang sudah ada.
|
| Tips Debugging:
| - Jika form edit kosong, periksa payload row dari DataTables dan
|   fungsi fillEditForm() pada file lowongan.js.
*/
?>
<div class="modal fade" id="kt_modal_edit_lowongan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_edit_lowongan_header">
                <h2 class="fw-bold">Edit Lowongan</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-lowongan-edit-modal-action="close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_lowongan_form" class="form" action="#">
                    <input type="hidden" name="id_lowongan" />
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_lowongan_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_edit_lowongan_header" data-kt-scroll-wrappers="#kt_modal_edit_lowongan_scroll" data-kt-scroll-offset="300px">
                        <?php
                        /*
                        |-------------------------------------------------------------------
                        | AREA UPLOAD FLYER EDIT
                        |-------------------------------------------------------------------
                        | Komponen ini memungkinkan admin mengganti flyer lama atau
                        | menghapusnya jika lowongan ingin tampil lebih sederhana.
                        */
                        ?>
                        <div class="fv-row mb-7">
                            <label class="d-block fw-semibold fs-6 mb-5">Flyer Lowongan</label>
                            <div class="image-input image-input-outline image-input-placeholder image-input-empty" data-kt-image-input="true" data-kt-lowongan-flyer-input="true" data-image-input-initial="" data-image-input-placeholder="<?= esc($blankFlyerUrl, 'attr') ?>">
                                <div class="image-input-wrapper w-150px h-150px" style="background-image: url('<?= esc($blankFlyerUrl) ?>');"></div>
                                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change flyer">
                                    <i class="ki-duotone ki-pencil fs-7">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <input type="file" name="flyer_lowongan" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="flyer_remove" />
                                </label>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel flyer">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove flyer">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="form-text">Allowed file types: png, jpg, jpeg. Maksimal 4 MB.</div>
                        </div>

                        <?php
                        /*
                        |-------------------------------------------------------------------
                        | INFORMASI UTAMA LOWONGAN
                        |-------------------------------------------------------------------
                        | Nilai dari data yang dipilih pada tabel akan diisikan kembali
                        | ke form ini melalui JavaScript agar mudah diperbarui.
                        */
                        ?>
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">DUDI</label>
                            <select name="id_perusahaan" class="form-select form-select-solid" data-kt-select2="true" data-placeholder="Pilih DUDI" required>
                                <option></option>
                                <?php foreach ($daftar_perusahaan as $perusahaan): ?>
                                    <option value="<?= (int) $perusahaan['id_perusahaan'] ?>"><?= esc((string) $perusahaan['nama_perusahaan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-8 fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Judul Lowongan</label>
                                <input type="text" name="judul_lowongan" class="form-control form-control-solid" required />
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Posisi</label>
                                <input type="text" name="posisi" class="form-control form-control-solid" required />
                            </div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Kualifikasi</label>
                            <textarea name="kualifikasi" class="form-control form-control-solid" rows="4"></textarea>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Deskripsi Pekerjaan</label>
                            <textarea name="deskripsi_pekerjaan" class="form-control form-control-solid" rows="4"></textarea>
                        </div>

                        <?php
                        /*
                        |-------------------------------------------------------------------
                        | PENGATURAN STATUS DAN TIPE
                        |-------------------------------------------------------------------
                        | Field ini menjaga agar status publikasi dan model kerja tetap
                        | sinkron dengan aturan bisnis lowongan pada sistem.
                        */
                        ?>
                        <div class="row">
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Jenis Pekerjaan</label>
                                <select name="jenis_pekerjaan" class="form-select form-select-solid" data-kt-select2="true" data-hide-search="true">
                                    <?php foreach ($jenisPekerjaanOptions as $value => $label): ?>
                                        <option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Sistem Kerja</label>
                                <select name="sistem_kerja" class="form-select form-select-solid" data-kt-select2="true" data-hide-search="true">
                                    <?php foreach ($sistemKerjaOptions as $value => $label): ?>
                                        <option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Status</label>
                                <select name="status" class="form-select form-select-solid" data-kt-select2="true" data-hide-search="true">
                                    <?php foreach ($daftar_status as $value => $label): ?>
                                        <option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <?php
                        /*
                        |-------------------------------------------------------------------
                        | KRITERIA DAN JUMLAH KEBUTUHAN
                        |-------------------------------------------------------------------
                        | Blok ini memuat data minimum yang biasanya dipakai sekolah
                        | dan DUDI untuk menyeleksi kesesuaian pelamar.
                        */
                        ?>
                        <div class="row">
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Pendidikan Minimum</label>
                                <select name="pendidikan_min" class="form-select form-select-solid" data-kt-select2="true" data-placeholder="Pilih pendidikan" data-allow-clear="true">
                                    <option></option>
                                    <?php foreach ($pendidikanOptions as $value => $label): ?>
                                        <option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Jumlah Kebutuhan</label>
                                <input type="number" name="jumlah_kebutuhan" class="form-control form-control-solid" min="1" />
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Pengalaman Minimum</label>
                                <input type="text" name="pengalaman_min" class="form-control form-control-solid" />
                            </div>
                        </div>

                        <?php
                        /*
                        |-------------------------------------------------------------------
                        | LOKASI DAN MASA TAYANG
                        |-------------------------------------------------------------------
                        | Digunakan untuk mengatur area kerja dan jadwal aktif lowongan
                        | tanpa harus menghapus data saat masa publikasi selesai.
                        */
                        ?>
                        <div class="row">
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Rentang Gaji</label>
                                <input type="text" name="rentang_gaji" class="form-control form-control-solid" />
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Lokasi Kerja</label>
                                <input type="text" name="lokasi_kerja" class="form-control form-control-solid" />
                            </div>
                            <div class="col-md-4 fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Batas Lamaran</label>
                                <input type="date" name="batas_lamaran" class="form-control form-control-solid" />
                            </div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Tayang Hingga</label>
                            <input type="datetime-local" name="tayang_hingga" class="form-control form-control-solid" />
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-kt-lowongan-edit-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-primary" data-kt-lowongan-edit-modal-action="submit">
                            <span class="indicator-label">Submit</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<?php
/*
|-------------------------------------------------------------------
| KONFIGURASI JAVASCRIPT LOWONGAN
|-------------------------------------------------------------------
| Variabel global ini menjadi jembatan antara view PHP dan file JS
| agar endpoint AJAX serta asset default bisa dibaca di frontend.
|
| Tips Debugging:
| - Jika request AJAX gagal ke URL kosong, periksa object
|   window.ktLowonganConfig yang dirender di bawah ini.
*/
?>
<script>
    window.ktLowonganConfig = {
        indexUrl: '<?= site_url($areaPrefix . '/lowongan') ?>',
        simpanUrl: '<?= site_url($areaPrefix . '/lowongan/simpan') ?>',
        updateUrl: '<?= site_url($areaPrefix . '/lowongan/update') ?>',
        hapusUrl: '<?= site_url($areaPrefix . '/lowongan/hapus') ?>',
        hapusMassalUrl: '<?= site_url($areaPrefix . '/lowongan/hapus-massal') ?>',
        blankFlyerUrl: '<?= $blankFlyerUrl ?>'
    };
</script>
<script src="<?= base_url('assets/js/custom/lowongan/lowongan.js') ?>"></script>
<?= $this->endSection() ?>
