<?php
/*
|-------------------------------------------------------------------
| VIEW KERJASAMA
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: view ini menampilkan halaman utama
| modul Kerjasama dengan tabel DataTables, modal tambah, dan modal
| edit berbasis AJAX untuk Super Admin.
| Alur kerja: controller me-render shell halaman ini, lalu JavaScript
| memuat data kerjasama dari endpoint JSON index tanpa reload penuh.
|
| Tips Debugging:
| - Jika tabel kosong, cek request AJAX ke route superadmin/kerjasama mengembalikan JSON sukses.
| - Jika modal tidak bekerja, cek file assets/js/custom/kerjasama/kerjasama.js termuat.
*/
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
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Kerjasama</h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= base_url('dashboard/superadmin') ?>" class="text-muted text-hover-primary">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Manajemen DUDI</li>
            </ul>
            <!--end::Breadcrumb-->
        </div>
        <!--end::Page title-->
        <!--begin::Actions-->
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            <button type="button" class="btn btn-sm fw-bold btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_tambah_kerjasama">
                <i class="ki-duotone ki-plus-square fs-3">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>Tambah Kerjasama
            </button>
        </div>
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
                        <input type="text" data-kt-kerjasama-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Kerjasama" />
                    </div>
                    <!--end::Search-->
                </div>
                <!--end::Card title-->
                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <button type="button" class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_tambah_kerjasama">
                        <i class="ki-duotone ki-plus-square fs-3">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>Tambah Kerjasama
                    </button>
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed mb-8 p-6">
                    <i class="ki-duotone ki-information-5 fs-2tx text-primary me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <div class="d-flex flex-stack flex-grow-1">
                        <div class="fw-semibold">
                            <h4 class="text-gray-900 fw-bold mb-1">Validasi Slug Sistem</h4>
                            <div class="fs-6 text-gray-700">Slug digunakan sistem untuk validasi. Contoh: <span class="fw-bold">rekrutmen</span></div>
                        </div>
                    </div>
                </div>
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_kerjasama_table">
                    <thead>
                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_kerjasama_table .form-check-input-row" value="1" />
                                </div>
                            </th>
                            <th class="min-w-200px">Nama Kerjasama</th>
                            <th class="min-w-150px">Slug</th>
                            <th class="min-w-250px">Deskripsi</th>
                            <th class="min-w-125px">Jumlah MoU</th>
                            <th class="text-end min-w-100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
        <!--begin::Modals-->
        <div class="modal fade" id="kt_modal_tambah_kerjasama" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-650px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold">Tambah Kerjasama</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-kerjasama-modal-action="close">
                            <i class="ki-duotone ki-cross fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                    </div>
                    <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                        <form id="kt_modal_tambah_kerjasama_form" class="form" action="#">
                            <div class="fv-row mb-7">
                                <label class="fs-6 fw-semibold form-label mb-2">
                                    <span class="required">Nama Kerjasama</span>
                                </label>
                                <input class="form-control form-control-solid" maxlength="150" placeholder="Contoh: Rekrutmen Tenaga Kerja" name="nama_kerjasama" />
                            </div>
                            <div class="fv-row mb-7">
                                <label class="fs-6 fw-semibold form-label mb-2">
                                    <span class="required">Slug Kerjasama</span>
                                </label>
                                <input class="form-control form-control-solid" maxlength="150" placeholder="Contoh: rekrutmen" name="slug_kerjasama" />
                            </div>
                            <div class="fv-row mb-7">
                                <label class="fs-6 fw-semibold form-label mb-2">
                                    <span>Deskripsi</span>
                                </label>
                                <textarea class="form-control form-control-solid" rows="4" placeholder="Tambahkan penjelasan singkat jenis kerjasama..." name="deskripsi"></textarea>
                            </div>
                            <div class="text-center pt-15">
                                <button type="reset" class="btn btn-light me-3" data-kt-kerjasama-modal-action="cancel">Discard</button>
                                <button type="submit" class="btn btn-primary" data-kt-kerjasama-modal-action="submit">
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
        <div class="modal fade" id="kt_modal_edit_kerjasama" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-650px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold">Edit Kerjasama</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-kerjasama-edit-modal-action="close">
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
                                        <strong class="me-1">Peringatan!</strong>Mengubah slug dapat mempengaruhi relasi dengan data MoU.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <form id="kt_modal_edit_kerjasama_form" class="form" action="#">
                            <input type="hidden" name="id_kerjasama" />
                            <div class="fv-row mb-7">
                                <label class="fs-6 fw-semibold form-label mb-2">
                                    <span class="required">Nama Kerjasama</span>
                                </label>
                                <input class="form-control form-control-solid" maxlength="150" placeholder="Contoh: Sinkronisasi Kurikulum" name="nama_kerjasama" />
                            </div>
                            <div class="fv-row mb-7">
                                <label class="fs-6 fw-semibold form-label mb-2">
                                    <span class="required">Slug Kerjasama</span>
                                </label>
                                <input class="form-control form-control-solid" maxlength="150" placeholder="Contoh: sinkronisasi" name="slug_kerjasama" />
                            </div>
                            <div class="fv-row mb-7">
                                <label class="fs-6 fw-semibold form-label mb-2">
                                    <span>Deskripsi</span>
                                </label>
                                <textarea class="form-control form-control-solid" rows="4" placeholder="Tambahkan penjelasan singkat jenis kerjasama..." name="deskripsi"></textarea>
                            </div>
                            <div class="text-center pt-15">
                                <button type="reset" class="btn btn-light me-3" data-kt-kerjasama-edit-modal-action="cancel">Discard</button>
                                <button type="submit" class="btn btn-primary" data-kt-kerjasama-edit-modal-action="submit">
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
    window.ktKerjasamaConfig = {
        indexUrl: '<?= site_url('superadmin/kerjasama') ?>',
        simpanUrl: '<?= site_url('superadmin/kerjasama/simpan') ?>',
        updateUrl: '<?= site_url('superadmin/kerjasama/update') ?>',
        hapusUrl: '<?= site_url('superadmin/kerjasama/hapus') ?>'
    };
</script>
<script src="<?= base_url('assets/js/custom/kerjasama/kerjasama.js') ?>"></script>
<?= $this->endSection() ?>
