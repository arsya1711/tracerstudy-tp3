<?php
/*
|-------------------------------------------------------------------
| VIEW MANAJEMEN ADMIN
|-------------------------------------------------------------------
| View ini menampilkan daftar akun admin untuk Super Admin dengan
| pola DataTables server-side,
| filter jenis admin, aksi massal, dan modal tambah/edit.
*/
?>
<?= $this->extend('layouts/main') ?>
<?php
/*
|-------------------------------------------------------------------
| KONTEKS VIEW DATA ADMIN
|-------------------------------------------------------------------
| View ini dipakai ulang oleh Super Admin dan Admin Sekolah.
| Konteks menentukan judul, breadcrumb, dan endpoint AJAX yang sesuai
| dengan area kerja pengguna.
|
| Tips Debugging:
| - Jika Data Admin di Admin Sekolah memanggil superadmin/admin, cek
|   nilai $areaPrefix dari controller.
*/
$defaultAdminFoto = base_url('assets/media/avatars/blank.png');
$areaPrefix = (string) ($areaPrefix ?? (session()->get('slug_peran') === 'admin_sekolah' ? 'admin-sekolah' : 'superadmin'));
$dashboardUrl = (string) ($dashboardUrl ?? base_url($areaPrefix === 'admin-sekolah' ? 'admin-sekolah/dashboard' : 'dashboard/superadmin'));
$pageHeading = (string) ($pageHeading ?? 'Manajemen Admin');
$breadcrumbParent = (string) ($breadcrumbParent ?? 'Manajemen Pengguna');
$breadcrumbCurrent = (string) ($breadcrumbCurrent ?? 'Data Admin');
?>

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
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input type="text" data-kt-admin-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari admin" />
                    </div>
                </div>
                <div class="card-toolbar">
                    <div class="d-flex justify-content-end" data-kt-admin-table-toolbar="base">
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            <i class="ki-duotone ki-filter fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Filter
                        </button>
                        <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                            <div class="px-7 py-5">
                                <div class="fs-5 text-dark fw-bold">Filter Jenis Admin</div>
                            </div>
                            <div class="separator border-gray-200"></div>
                            <div class="px-7 py-5" data-kt-admin-table-filter="form">
                                <div class="mb-10">
                                    <label class="form-label fs-6 fw-semibold">Jenis Admin:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true" data-placeholder="Pilih jenis admin" data-allow-clear="true" data-kt-admin-table-filter="jenis" data-hide-search="true">
                                        <option></option>
                                        <?php foreach ($jenis_admin as $jenisAdmin): ?>
                                            <option value="<?= esc((string) $jenisAdmin['slug_peran'], 'attr') ?>"><?= esc((string) $jenisAdmin['nama_peran']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6" data-kt-menu-dismiss="true" data-kt-admin-table-filter="reset">Reset</button>
                                    <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true" data-kt-admin-table-filter="filter">Apply</button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_tambah_admin">
                            <i class="ki-duotone ki-plus fs-2"></i>Tambah Admin
                        </button>
                    </div>

                    <div class="d-flex justify-content-end align-items-center d-none" data-kt-admin-table-toolbar="selected">
                        <div class="fw-bold me-5">
                            <span class="me-2" data-kt-admin-table-select="selected_count"></span>Selected
                        </div>
                        <button type="button" class="btn btn-danger" data-kt-admin-table-select="delete_selected">Hapus Terpilih</button>
                    </div>
                </div>
            </div>

            <div class="card-body py-4">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_admin">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_admin .form-check-input" value="1" />
                                </div>
                            </th>
                            <th class="min-w-250px">Admin</th>
                            <th class="min-w-150px">Jenis Admin</th>
                            <th class="min-w-125px">Status</th>
                            <th class="min-w-175px">Terakhir Login</th>
                            <th class="text-end min-w-150px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="kt_modal_tambah_admin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_tambah_admin_header">
                <h2 class="fw-bold">Tambah Admin</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-admin-modal-action="close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_tambah_admin_form" class="form" action="#">
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_tambah_admin_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_tambah_admin_header" data-kt-scroll-wrappers="#kt_modal_tambah_admin_scroll" data-kt-scroll-offset="300px">
                        <div class="fv-row mb-7">
                            <label class="d-block fw-semibold fs-6 mb-5">Foto</label>
                            <div class="image-input image-input-outline image-input-placeholder image-input-empty" data-kt-image-input="true" data-kt-admin-photo-input="true" data-image-input-initial="" data-image-input-placeholder="<?= esc($defaultAdminFoto, 'attr') ?>">
                                <div class="image-input-wrapper w-125px h-125px" style="background-image: url(<?= esc($defaultAdminFoto) ?>);"></div>
                                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                    <i class="ki-duotone ki-pencil fs-7">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <input type="file" name="foto" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="foto_remove" />
                                </label>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control form-control-solid" placeholder="Nama lengkap admin" required />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" name="email" class="form-control form-control-solid" placeholder="admin@example.com" required />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Kata Sandi</label>
                            <input type="password" name="kata_sandi" class="form-control form-control-solid" placeholder="Minimal 8 karakter" minlength="8" required />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Nomor Telepon</label>
                            <input type="text" name="nomor_telepon" class="form-control form-control-solid" placeholder="08xxxxxxxxxx" />
                        </div>

                        <div class="mb-10">
                            <label class="required fw-semibold fs-6 mb-5">Jenis Admin</label>
                            <?php foreach ($jenis_admin as $index => $jenisAdmin): ?>
                                <div class="d-flex fv-row">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="jenis_admin" type="radio" value="<?= esc((string) $jenisAdmin['slug_peran'], 'attr') ?>" id="kt_modal_tambah_admin_option_<?= esc((string) $jenisAdmin['slug_peran'], 'attr') ?>" <?= $index === 0 ? 'checked' : '' ?> />
                                        <label class="form-check-label" for="kt_modal_tambah_admin_option_<?= esc((string) $jenisAdmin['slug_peran'], 'attr') ?>">
                                            <div class="fw-bold text-gray-800"><?= esc((string) $jenisAdmin['nama_peran']) ?></div>
                                            <div class="text-gray-600">Akun ini akan dipakai untuk modul <?= esc((string) $jenisAdmin['nama_peran']) ?>.</div>
                                        </label>
                                    </div>
                                </div>
                                <?php if ($index !== count($jenis_admin) - 1): ?>
                                    <div class="separator separator-dashed my-5"></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-kt-admin-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-primary" data-kt-admin-modal-action="submit">
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

<div class="modal fade" id="kt_modal_edit_admin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_edit_admin_header">
                <h2 class="fw-bold">Edit Admin</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-admin-edit-modal-action="close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_admin_form" class="form" action="#">
                    <input type="hidden" name="id_pengguna" />
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_admin_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_edit_admin_header" data-kt-scroll-wrappers="#kt_modal_edit_admin_scroll" data-kt-scroll-offset="300px">
                        <div class="fv-row mb-7">
                            <label class="d-block fw-semibold fs-6 mb-5">Foto</label>
                            <div class="image-input image-input-outline image-input-placeholder image-input-empty" data-kt-image-input="true" data-kt-admin-photo-input="true" data-image-input-initial="" data-image-input-placeholder="<?= esc($defaultAdminFoto, 'attr') ?>">
                                <div class="image-input-wrapper w-125px h-125px" style="background-image: url(<?= esc($defaultAdminFoto) ?>);"></div>
                                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                    <i class="ki-duotone ki-pencil fs-7">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <input type="file" name="foto" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="foto_remove" />
                                </label>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control form-control-solid" placeholder="Nama lengkap admin" required />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" name="email" class="form-control form-control-solid" placeholder="admin@example.com" required />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Kata Sandi Baru</label>
                            <input type="password" name="kata_sandi" class="form-control form-control-solid" placeholder="Kosongkan jika tidak diubah" minlength="8" />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Nomor Telepon</label>
                            <input type="text" name="nomor_telepon" class="form-control form-control-solid" placeholder="08xxxxxxxxxx" />
                        </div>

                        <div class="mb-10">
                            <label class="required fw-semibold fs-6 mb-5">Jenis Admin</label>
                            <?php foreach ($jenis_admin as $index => $jenisAdmin): ?>
                                <div class="d-flex fv-row">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="jenis_admin" type="radio" value="<?= esc((string) $jenisAdmin['slug_peran'], 'attr') ?>" id="kt_modal_edit_admin_option_<?= esc((string) $jenisAdmin['slug_peran'], 'attr') ?>" <?= $index === 0 ? 'checked' : '' ?> />
                                        <label class="form-check-label" for="kt_modal_edit_admin_option_<?= esc((string) $jenisAdmin['slug_peran'], 'attr') ?>">
                                            <div class="fw-bold text-gray-800"><?= esc((string) $jenisAdmin['nama_peran']) ?></div>
                                            <div class="text-gray-600">Akun ini akan dipakai untuk modul <?= esc((string) $jenisAdmin['nama_peran']) ?>.</div>
                                        </label>
                                    </div>
                                </div>
                                <?php if ($index !== count($jenis_admin) - 1): ?>
                                    <div class="separator separator-dashed my-5"></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-kt-admin-edit-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-primary" data-kt-admin-edit-modal-action="submit">
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
<script>
    window.ktAdminConfig = {
        indexUrl: '<?= site_url($areaPrefix . '/admin') ?>',
        simpanUrl: '<?= site_url($areaPrefix . '/admin/simpan') ?>',
        updateUrl: '<?= site_url($areaPrefix . '/admin/update') ?>',
        hapusUrl: '<?= site_url($areaPrefix . '/admin/hapus') ?>',
        hapusMassalUrl: '<?= site_url($areaPrefix . '/admin/hapus-massal') ?>',
        aktivasiUrl: '<?= site_url($areaPrefix . '/admin/aktivasi') ?>',
        defaultFoto: '<?= base_url('assets/media/avatars/blank.png') ?>'
    };
</script>
<script src="<?= base_url('assets/js/custom/admin/admin.js') ?>"></script>
<?= $this->endSection() ?>
