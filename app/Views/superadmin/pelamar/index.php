<?php
/*
|-------------------------------------------------------------------
| VIEW DAFTAR PELAMAR
|-------------------------------------------------------------------
| View ini mengadaptasi struktur list Metronic 8.2.0 untuk modul
| Manajemen Pelamar Super Admin dengan DataTables server-side,
| filter dropdown, aksi massal, dan modal tambah/edit.
| Alur kerja: halaman dirender sekali oleh controller, lalu data tabel
| dan seluruh aksi form ditangani oleh file JS pelamar berbasis AJAX.
|
| Tips Debugging:
| - Jika tabel tidak muncul, cek assets/js/custom/pelamar/pelamar.js termuat.
| - Jika filter tidak bereaksi, cek atribut data-kt-user-table-filter pada elemen form.
*/
?>
<?= $this->extend('layouts/main') ?>
<?php
/*
|-------------------------------------------------------------------
| KONTEKS VIEW PELAMAR
|-------------------------------------------------------------------
| View ini dipakai ulang oleh Super Admin dan Admin Sekolah/BKK.
| Variabel di bawah menentukan breadcrumb dan endpoint AJAX agar
| halaman tetap terasa berada di area pengguna yang sedang login.
|
| Tips Debugging:
| - Jika tombol aksi 404, cek nilai $areaPrefix dan konfigurasi
|   window.ktPelamarConfig pada bagian extra_js.
*/
$areaPrefix = (string) ($areaPrefix ?? (session()->get('slug_peran') === 'admin_sekolah' ? 'admin-sekolah' : 'superadmin'));
$dashboardUrl = (string) ($dashboardUrl ?? base_url($areaPrefix === 'admin-sekolah' ? 'admin-sekolah/dashboard' : 'dashboard/superadmin'));
$pageHeading = (string) ($pageHeading ?? 'Daftar Pelamar');
$breadcrumbParent = (string) ($breadcrumbParent ?? 'Manajemen Pengguna');
$breadcrumbCurrent = (string) ($breadcrumbCurrent ?? 'Data Pelamar');
?>

<?= $this->section('content') ?>
<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <!--begin::Toolbar container-->
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <!--begin::Page title-->
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <!--begin::Title-->
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0"><?= esc($pageHeading) ?></h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <!--begin::Item-->
                <li class="breadcrumb-item text-muted">
                    <a href="<?= esc($dashboardUrl, 'attr') ?>" class="text-muted text-hover-primary">Home</a>
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
<div id="kt_app_content" class="app-content flex-column-fluid">
    <!--begin::Content container-->
    <div id="kt_app_content_container" class="app-container container-xxl">
        <!--begin::Card-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <!--begin::Card title-->
                <div class="card-title">
                    <!--begin::Search-->
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari pelamar" />
                    </div>
                    <!--end::Search-->
                </div>
                <!--begin::Card title-->
                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                        <button type="button" class="btn btn-light-warning me-3" data-kt-user-table-filter="review">
                        <i class="ki-duotone ki-timer fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>Antrean Review</button>
                        <!--begin::Filter-->
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-filter fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>Filter</button>
                        <!--begin::Menu 1-->
                        <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                            <!--begin::Header-->
                            <div class="px-7 py-5">
                                <div class="fs-5 text-dark fw-bold">Filter Options</div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Separator-->
                            <div class="separator border-gray-200"></div>
                            <!--end::Separator-->
                            <!--begin::Content-->
                            <div class="px-7 py-5" data-kt-user-table-filter="form">
                                <!--begin::Input group-->
                                <div class="mb-10">
                                    <label class="form-label fs-6 fw-semibold">Jenis Pelamar:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true" data-kt-user-table-filter="jenis" data-hide-search="true">
                                        <option></option>
                                        <option value="pelamar_alumni">Pelamar Alumni</option>
                                        <option value="pelamar_umum">Pelamar Umum</option>
                                    </select>
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="mb-10">
                                    <label class="form-label fs-6 fw-semibold">Status Akun:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true" data-kt-user-table-filter="status_akun" data-hide-search="true">
                                        <option></option>
                                        <option value="1">Aktif</option>
                                        <option value="0">Nonaktif</option>
                                    </select>
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="mb-10">
                                    <label class="form-label fs-6 fw-semibold">Status Pendaftaran:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true" data-kt-user-table-filter="status_daftar" data-hide-search="true">
                                        <option></option>
                                        <option value="menunggu_aktivasi">Menunggu Aktivasi</option>
                                        <option value="aktif">Aktif</option>
                                        <option value="terdaftar">Terdaftar</option>
                                    </select>
                                </div>
                                <!--end::Input group-->
                                <!--begin::Actions-->
                                <div class="d-flex justify-content-end">
                                    <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6" data-kt-menu-dismiss="true" data-kt-user-table-filter="reset">Reset</button>
                                    <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true" data-kt-user-table-filter="filter">Apply</button>
                                </div>
                                <!--end::Actions-->
                            </div>
                            <!--end::Content-->
                        </div>
                        <!--end::Menu 1-->
                        <!--end::Filter-->
                        <!--begin::Add user-->
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_tambah_pelamar">
                        <i class="ki-duotone ki-plus fs-2"></i>Tambah Pelamar</button>
                        <!--end::Add user-->
                    </div>
                    <!--end::Toolbar-->
                    <!--begin::Group actions-->
                    <div class="d-flex justify-content-end align-items-center d-none" data-kt-user-table-toolbar="selected">
                        <div class="fw-bold me-5">
                        <span class="me-2" data-kt-user-table-select="selected_count"></span>Selected</div>
                        <button type="button" class="btn btn-danger" data-kt-user-table-select="delete_selected">Delete Selected</button>
                    </div>
                    <!--end::Group actions-->
                    <!--begin::Modal - Add user-->
                    <div class="modal fade" id="kt_modal_tambah_pelamar" tabindex="-1" aria-hidden="true">
                        <!--begin::Modal dialog-->
                        <div class="modal-dialog modal-dialog-centered mw-650px">
                            <!--begin::Modal content-->
                            <div class="modal-content">
                                <!--begin::Modal header-->
                                <div class="modal-header" id="kt_modal_tambah_pelamar_header">
                                    <!--begin::Modal title-->
                                    <h2 class="fw-bold">Tambah Pelamar</h2>
                                    <!--end::Modal title-->
                                    <!--begin::Close-->
                                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-pelamar-modal-action="close">
                                        <i class="ki-duotone ki-cross fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </div>
                                    <!--end::Close-->
                                </div>
                                <!--end::Modal header-->
                                <!--begin::Modal body-->
                                <div class="modal-body px-5 my-7">
                                    <!--begin::Form-->
                                    <form id="kt_modal_tambah_pelamar_form" class="form" action="#">
                                        <!--begin::Scroll-->
                                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_tambah_pelamar_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_tambah_pelamar_header" data-kt-scroll-wrappers="#kt_modal_tambah_pelamar_scroll" data-kt-scroll-offset="300px">
                                            <!--begin::Input group-->
                                            <div class="fv-row mb-7">
                                                <!--begin::Label-->
                                                <label class="d-block fw-semibold fs-6 mb-5">Foto</label>
                                                <!--end::Label-->
                                                <!--begin::Image input-->
                                                <div class="image-input image-input-outline image-input-placeholder" data-kt-image-input="true">
                                                    <!--begin::Preview existing avatar-->
                                                    <div class="image-input-wrapper w-125px h-125px" style="background-image: url(<?= base_url('assets/media/avatars/blank.png') ?>);"></div>
                                                    <!--end::Preview existing avatar-->
                                                    <!--begin::Label-->
                                                    <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                                        <i class="ki-duotone ki-pencil fs-7">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        <!--begin::Inputs-->
                                                        <input type="file" name="foto" accept=".png, .jpg, .jpeg" />
                                                        <input type="hidden" name="avatar_remove" />
                                                        <!--end::Inputs-->
                                                    </label>
                                                    <!--end::Label-->
                                                    <!--begin::Cancel-->
                                                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                                                        <i class="ki-duotone ki-cross fs-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                    <!--end::Cancel-->
                                                    <!--begin::Remove-->
                                                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                                                        <i class="ki-duotone ki-cross fs-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                    <!--end::Remove-->
                                                </div>
                                                <!--end::Image input-->
                                                <!--begin::Hint-->
                                                <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                                                <!--end::Hint-->
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="fv-row mb-7">
                                                <!--begin::Label-->
                                                <label class="required fw-semibold fs-6 mb-2">Nama Lengkap</label>
                                                <!--end::Label-->
                                                <!--begin::Input-->
                                                <input type="text" name="nama_lengkap" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Nama lengkap" />
                                                <!--end::Input-->
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="fv-row mb-7">
                                                <!--begin::Label-->
                                                <label class="required fw-semibold fs-6 mb-2">Email</label>
                                                <!--end::Label-->
                                                <!--begin::Input-->
                                                <input type="email" name="email" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="pelamar@example.com" />
                                                <!--end::Input-->
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="fv-row mb-7">
                                                <!--begin::Label-->
                                                <label class="required fw-semibold fs-6 mb-2">Kata Sandi</label>
                                                <!--end::Label-->
                                                <!--begin::Input-->
                                                <input type="password" name="kata_sandi" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Minimal 8 karakter" />
                                                <!--end::Input-->
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="mb-5">
                                                <!--begin::Label-->
                                                <label class="required fw-semibold fs-6 mb-5">Jenis Pelamar</label>
                                                <!--end::Label-->
                                                <!--begin::Roles-->
                                                <!--begin::Input row-->
                                                <div class="d-flex fv-row">
                                                    <!--begin::Radio-->
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <!--begin::Input-->
                                                        <input class="form-check-input me-3" name="jenis_pelamar" type="radio" value="alumni" id="kt_modal_tambah_pelamar_option_alumni" checked="checked" />
                                                        <!--end::Input-->
                                                        <!--begin::Label-->
                                                        <label class="form-check-label" for="kt_modal_tambah_pelamar_option_alumni">
                                                            <div class="fw-bold text-gray-800">Pelamar Alumni</div>
                                                            <div class="text-gray-600">Dipakai untuk alumni sekolah yang memiliki data lanjutan di tabel alumni.</div>
                                                        </label>
                                                        <!--end::Label-->
                                                    </div>
                                                    <!--end::Radio-->
                                                </div>
                                                <!--end::Input row-->
                                                <div class='separator separator-dashed my-5'></div>
                                                <!--begin::Input row-->
                                                <div class="d-flex fv-row">
                                                    <!--begin::Radio-->
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <!--begin::Input-->
                                                        <input class="form-check-input me-3" name="jenis_pelamar" type="radio" value="umum" id="kt_modal_tambah_pelamar_option_umum" />
                                                        <!--end::Input-->
                                                        <!--begin::Label-->
                                                        <label class="form-check-label" for="kt_modal_tambah_pelamar_option_umum">
                                                            <div class="fw-bold text-gray-800">Pelamar Umum</div>
                                                            <div class="text-gray-600">Dipakai untuk pelamar dari luar alumni sekolah.</div>
                                                        </label>
                                                        <!--end::Label-->
                                                    </div>
                                                    <!--end::Radio-->
                                                </div>
                                                <!--end::Input row-->
                                                <!--end::Roles-->
                                            </div>
                                            <!--end::Input group-->
                                        </div>
                                        <!--end::Scroll-->
                                        <!--begin::Actions-->
                                        <div class="text-center pt-10">
                                            <button type="reset" class="btn btn-light me-3" data-kt-pelamar-modal-action="cancel">Discard</button>
                                            <button type="submit" class="btn btn-primary" data-kt-pelamar-modal-action="submit">
                                                <span class="indicator-label">Submit</span>
                                                <span class="indicator-progress">Please wait...
                                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                            </button>
                                        </div>
                                        <!--end::Actions-->
                                    </form>
                                    <!--end::Form-->
                                </div>
                                <!--end::Modal body-->
                            </div>
                            <!--end::Modal content-->
                        </div>
                        <!--end::Modal dialog-->
                    </div>
                    <!--end::Modal - Add user-->
                    <!--begin::Modal - Edit user-->
                    <div class="modal fade" id="kt_modal_edit_pelamar" tabindex="-1" aria-hidden="true">
                        <!--begin::Modal dialog-->
                        <div class="modal-dialog modal-dialog-centered mw-650px">
                            <!--begin::Modal content-->
                            <div class="modal-content">
                                <!--begin::Modal header-->
                                <div class="modal-header" id="kt_modal_edit_pelamar_header">
                                    <!--begin::Modal title-->
                                    <h2 class="fw-bold">Edit Pelamar</h2>
                                    <!--end::Modal title-->
                                    <!--begin::Close-->
                                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-pelamar-edit-modal-action="close">
                                        <i class="ki-duotone ki-cross fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </div>
                                    <!--end::Close-->
                                </div>
                                <!--end::Modal header-->
                                <!--begin::Modal body-->
                                <div class="modal-body px-5 my-7">
                                    <!--begin::Form-->
                                    <form id="kt_modal_edit_pelamar_form" class="form" action="#">
                                        <input type="hidden" name="id_pelamar" />
                                        <!--begin::Scroll-->
                                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_pelamar_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_edit_pelamar_header" data-kt-scroll-wrappers="#kt_modal_edit_pelamar_scroll" data-kt-scroll-offset="300px">
                                            <!--begin::Input group-->
                                            <div class="fv-row mb-7">
                                                <!--begin::Label-->
                                                <label class="d-block fw-semibold fs-6 mb-5">Foto</label>
                                                <!--end::Label-->
                                                <!--begin::Image input-->
                                                <div class="image-input image-input-outline image-input-placeholder" data-kt-image-input="true">
                                                    <!--begin::Preview existing avatar-->
                                                    <div class="image-input-wrapper w-125px h-125px" style="background-image: url(<?= base_url('assets/media/avatars/blank.png') ?>);"></div>
                                                    <!--end::Preview existing avatar-->
                                                    <!--begin::Label-->
                                                    <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                                        <i class="ki-duotone ki-pencil fs-7">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        <!--begin::Inputs-->
                                                        <input type="file" name="foto" accept=".png, .jpg, .jpeg" />
                                                        <input type="hidden" name="avatar_remove" />
                                                        <!--end::Inputs-->
                                                    </label>
                                                    <!--end::Label-->
                                                    <!--begin::Cancel-->
                                                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                                                        <i class="ki-duotone ki-cross fs-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                    <!--end::Cancel-->
                                                    <!--begin::Remove-->
                                                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                                                        <i class="ki-duotone ki-cross fs-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                    <!--end::Remove-->
                                                </div>
                                                <!--end::Image input-->
                                                <!--begin::Hint-->
                                                <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                                                <!--end::Hint-->
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="fv-row mb-7">
                                                <!--begin::Label-->
                                                <label class="required fw-semibold fs-6 mb-2">Nama Lengkap</label>
                                                <!--end::Label-->
                                                <!--begin::Input-->
                                                <input type="text" name="nama_lengkap" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Nama lengkap" />
                                                <!--end::Input-->
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="fv-row mb-7">
                                                <!--begin::Label-->
                                                <label class="required fw-semibold fs-6 mb-2">Email</label>
                                                <!--end::Label-->
                                                <!--begin::Input-->
                                                <input type="email" name="email" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="pelamar@example.com" />
                                                <!--end::Input-->
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="mb-5">
                                                <!--begin::Label-->
                                                <label class="required fw-semibold fs-6 mb-5">Jenis Pelamar</label>
                                                <!--end::Label-->
                                                <!--begin::Roles-->
                                                <!--begin::Input row-->
                                                <div class="d-flex fv-row">
                                                    <!--begin::Radio-->
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <!--begin::Input-->
                                                        <input class="form-check-input me-3" name="jenis_pelamar" type="radio" value="alumni" id="kt_modal_edit_pelamar_option_alumni" checked="checked" />
                                                        <!--end::Input-->
                                                        <!--begin::Label-->
                                                        <label class="form-check-label" for="kt_modal_edit_pelamar_option_alumni">
                                                            <div class="fw-bold text-gray-800">Pelamar Alumni</div>
                                                            <div class="text-gray-600">Dipakai untuk alumni sekolah yang memiliki data lanjutan di tabel alumni.</div>
                                                        </label>
                                                        <!--end::Label-->
                                                    </div>
                                                    <!--end::Radio-->
                                                </div>
                                                <!--end::Input row-->
                                                <div class='separator separator-dashed my-5'></div>
                                                <!--begin::Input row-->
                                                <div class="d-flex fv-row">
                                                    <!--begin::Radio-->
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <!--begin::Input-->
                                                        <input class="form-check-input me-3" name="jenis_pelamar" type="radio" value="umum" id="kt_modal_edit_pelamar_option_umum" />
                                                        <!--end::Input-->
                                                        <!--begin::Label-->
                                                        <label class="form-check-label" for="kt_modal_edit_pelamar_option_umum">
                                                            <div class="fw-bold text-gray-800">Pelamar Umum</div>
                                                            <div class="text-gray-600">Dipakai untuk pelamar dari luar alumni sekolah.</div>
                                                        </label>
                                                        <!--end::Label-->
                                                    </div>
                                                    <!--end::Radio-->
                                                </div>
                                                <!--end::Input row-->
                                                <!--end::Roles-->
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="mb-10">
                                                <label class="form-label fs-6 fw-semibold">Status Pendaftaran:</label>
                                                <select class="form-select form-select-solid fw-bold" name="status_pendaftaran" data-kt-select2="true" data-placeholder="Select option" data-hide-search="true">
                                                    <option value="menunggu_aktivasi">Menunggu Aktivasi</option>
                                                    <option value="aktif">Aktif</option>
                                                    <option value="terdaftar">Terdaftar</option>
                                                </select>
                                            </div>
                                            <!--end::Input group-->
                                        </div>
                                        <!--end::Scroll-->
                                        <!--begin::Actions-->
                                        <div class="text-center pt-10">
                                            <button type="reset" class="btn btn-light me-3" data-kt-pelamar-edit-modal-action="cancel">Discard</button>
                                            <button type="submit" class="btn btn-primary" data-kt-pelamar-edit-modal-action="submit">
                                                <span class="indicator-label">Submit</span>
                                                <span class="indicator-progress">Please wait...
                                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                            </button>
                                        </div>
                                        <!--end::Actions-->
                                    </form>
                                    <!--end::Form-->
                                </div>
                                <!--end::Modal body-->
                            </div>
                            <!--end::Modal content-->
                        </div>
                        <!--end::Modal dialog-->
                    </div>
                    <!--end::Modal - Edit user-->
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body py-4">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_pelamar">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_pelamar .form-check-input" value="1" />
                                </div>
                            </th>
                            <th class="min-w-125px">Account ID</th>
                            <th class="min-w-250px">Pelamar</th>
                            <th class="min-w-125px">Jenis Pelamar</th>
                            <th class="min-w-150px">Status Pendaftaran</th>
                            <th class="min-w-150px">Terakhir Login</th>
                            <th class="min-w-125px">Status Akun</th>
                            <th class="min-w-150px">Tanggal Daftar</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
    <!--end::Content container-->
</div>
<!--end::Content-->
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<script>
    window.ktPelamarConfig = {
        indexUrl: '<?= site_url($areaPrefix . '/pelamar') ?>',
        detailUrl: '<?= base_url($areaPrefix . '/pelamar/detail') ?>',
        simpanUrl: '<?= site_url($areaPrefix . '/pelamar/simpan') ?>',
        updateUrl: '<?= site_url($areaPrefix . '/pelamar/update') ?>',
        hapusUrl: '<?= site_url($areaPrefix . '/pelamar/hapus') ?>',
        hapusMassalUrl: '<?= site_url($areaPrefix . '/pelamar/hapus-massal') ?>',
        aktivasiUrl: '<?= site_url($areaPrefix . '/pelamar/aktivasi') ?>',
        defaultFoto: '<?= base_url('assets/media/avatars/blank.png') ?>'
    };
</script>
<script src="<?= base_url('assets/js/custom/pelamar/pelamar.js') ?>"></script>
<?= $this->endSection() ?>
