<?php
/*
|-------------------------------------------------------------------
| VIEW KOMPETENSI KEAHLIAN
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: view ini menampilkan halaman utama
| modul Kompetensi Keahlian untuk Super Admin dengan tabel DataTables,
| modal tambah, dan modal edit berbasis AJAX.
| Alur kerja: controller mengirim daftar kompetensi ke view ini, lalu
| layout main membungkusnya dengan header, sidebar, dan footer
| Metronic sebelum JavaScript kompetensi dijalankan.
|
| Tips Debugging:
| - Jika tabel kosong, cek variabel $kompetensi dari controller.
| - Jika modal tidak bekerja, cek file public/assets/js/custom/kompetensi/kompetensi.js termuat.
*/
$dashboardUrl = $dashboardUrl ?? base_url('dashboard/superadmin');
$indexUrl = $indexUrl ?? site_url('superadmin/kompetensi');
$simpanUrl = $simpanUrl ?? site_url('superadmin/kompetensi/simpan');
$updateUrl = $updateUrl ?? site_url('superadmin/kompetensi/update');
$hapusUrl = $hapusUrl ?? site_url('superadmin/kompetensi/hapus');
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <!--begin::Toolbar container-->
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <!--begin::Page title-->
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <!--begin::Title-->
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Kompetensi Keahlian</h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= esc($dashboardUrl) ?>" class="text-muted text-hover-primary">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Manajemen Sekolah</li>
            </ul>
            <!--end::Breadcrumb-->
        </div>
        <!--end::Page title-->
   
        <!--end::Actions-->
    </div>
    <!--end::Toolbar container-->
</div>
<!--end::Toolbar-->
<!--begin::Content-->
<div id="kt_app_content" class="app-content flex-column-fluid">
    <!--begin::Content container-->
    <div id="kt_app_content_container" class="app-container container-xxl">
        <!--begin::Card-->
        <div class="card card-flush">
            <!--begin::Card header-->
            <div class="card-header mt-6">
                <!--begin::Card title-->
                <div class="card-title">
                    <!--begin::Search-->
                    <div class="d-flex align-items-center position-relative my-1 me-5">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input type="text" data-kt-kompetensi-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Kompetensi Keahlian" />
                    </div>
                    <!--end::Search-->
                </div>
                <!--end::Card title-->
                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <button type="button" class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_tambah_kompetensi">
                        <i class="ki-duotone ki-plus-square fs-3">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>Tambah Kompetensi
                    </button>
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_kompetensi_table">
                    <thead>
                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_kompetensi_table .form-check-input-row" value="1" />
                                </div>
                            </th>
                            <th class="min-w-200px">Kompetensi Keahlian</th>
                            <th class="min-w-125px">Akronim</th>
                            <th class="min-w-125px">Keterserapan</th>
                            <th class="text-end min-w-100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
                        <?php foreach ($kompetensi as $row): ?>
                            <tr data-id="<?= esc($row['id_kompetensi']) ?>" data-nama="<?= esc($row['nama_kompetensi']) ?>" data-akronim="<?= esc($row['akronim']) ?>">
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input form-check-input-row" type="checkbox" value="<?= esc($row['id_kompetensi']) ?>" />
                                    </div>
                                </td>
                                <td class="kompetensi-nama"><?= esc($row['nama_kompetensi']) ?></td>
                                <td class="kompetensi-akronim">
                                    <span class="badge badge-light-primary"><?= esc($row['akronim']) ?></span>
                                </td>
                                <td class="kompetensi-keterserapan"><?= esc($row['keterserapan']) ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px me-3" data-kt-kompetensi-table-filter="edit_row">
                                        <i class="ki-duotone ki-setting-3 fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                            <span class="path5"></span>
                                        </i>
                                    </button>
                                    <button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px" data-kt-kompetensi-table-filter="delete_row">
                                        <i class="ki-duotone ki-trash fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                            <span class="path5"></span>
                                        </i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
        <!--begin::Modals-->
        <div class="modal fade" id="kt_modal_tambah_kompetensi" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-650px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold">Tambah Kompetensi Keahlian</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-kompetensi-modal-action="close">
                            <i class="ki-duotone ki-cross fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                    </div>
                    <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                        <form id="kt_modal_tambah_kompetensi_form" class="form" action="#">
                            <div class="fv-row mb-7">
                                <label class="fs-6 fw-semibold form-label mb-2">
                                    <span class="required">Nama Kompetensi Keahlian</span>
                                </label>
                                <input class="form-control form-control-solid" placeholder="Contoh: Akuntansi dan Keuangan Lembaga (AKL)" name="nama_kompetensi" />
                            </div>
                            <div class="fv-row mb-7">
                                <label class="fs-6 fw-semibold form-label mb-2">
                                    <span class="required">Akronim</span>
                                </label>
                                <input class="form-control form-control-solid" placeholder="Contoh: AKL" name="akronim" />
                            </div>
                            <div class="text-center pt-15">
                                <button type="reset" class="btn btn-light me-3" data-kt-kompetensi-modal-action="cancel">Discard</button>
                                <button type="submit" class="btn btn-primary" data-kt-kompetensi-modal-action="submit">
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
        <div class="modal fade" id="kt_modal_edit_kompetensi" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-650px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold">Edit Kompetensi Keahlian</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-kompetensi-edit-modal-action="close">
                            <i class="ki-duotone ki-cross fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                    </div>
                    <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-9 p-6">
                            <i class="ki-duotone ki-information fs-2tx text-warning me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="d-flex flex-stack flex-grow-1">
                                <div class="fw-semibold">
                                    <div class="fs-6 text-gray-700">
                                        <strong class="me-1">Peringatan!</strong>Pastikan nama kompetensi dan akronim yang diubah sudah benar sebelum disimpan.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <form id="kt_modal_edit_kompetensi_form" class="form" action="#">
                            <input type="hidden" name="id_kompetensi" />
                            <div class="fv-row mb-7">
                                <label class="fs-6 fw-semibold form-label mb-2">
                                    <span class="required">Nama Kompetensi Keahlian</span>
                                </label>
                                <input class="form-control form-control-solid" placeholder="Contoh: Manajemen Perkantoran dan Layanan Bisnis (MPLB)" name="nama_kompetensi" />
                            </div>
                            <div class="fv-row mb-7">
                                <label class="fs-6 fw-semibold form-label mb-2">
                                    <span class="required">Akronim</span>
                                </label>
                                <input class="form-control form-control-solid" placeholder="Contoh: MPLB" name="akronim" />
                            </div>
                            <div class="text-center pt-15">
                                <button type="reset" class="btn btn-light me-3" data-kt-kompetensi-edit-modal-action="cancel">Discard</button>
                                <button type="submit" class="btn btn-primary" data-kt-kompetensi-edit-modal-action="submit">
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
        <!--end::Modals-->
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<script>
    window.ktKompetensiConfig = {
        indexUrl: '<?= esc($indexUrl, 'js') ?>',
        simpanUrl: '<?= esc($simpanUrl, 'js') ?>',
        updateUrl: '<?= esc($updateUrl, 'js') ?>',
        hapusUrl: '<?= esc($hapusUrl, 'js') ?>'
    };
</script>
<script src="<?= base_url('assets/js/custom/kompetensi/kompetensi.js') ?>"></script>
<?= $this->endSection() ?>
