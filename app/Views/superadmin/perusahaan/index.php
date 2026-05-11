<?php
/*
|-------------------------------------------------------------------
| VIEW DATA DUDI
|-------------------------------------------------------------------
| View ini menampilkan daftar perusahaan/DUDI dengan pola yang
| konsisten seperti modul admin: DataTables server-side, search,
| filter kota, aksi massal, dan modal tambah/edit.
*/
?>
<?= $this->extend('layouts/main') ?>
<?php
/*
|-------------------------------------------------------------------
| KONTEKS VIEW DATA DUDI
|-------------------------------------------------------------------
| View Data DUDI dipakai oleh Super Admin dan Admin Sekolah/BKK.
| Konteks ini menjaga breadcrumb dan endpoint AJAX tetap sesuai
| dengan area menu yang sedang dibuka.
|
| Tips Debugging:
| - Jika simpan/edit DUDI 404, cek window.ktPerusahaanConfig.
*/
$blankLogoUrl = base_url('assets/media/svg/files/blank-image.svg');
$areaPrefix = (string) ($areaPrefix ?? (session()->get('slug_peran') === 'admin_sekolah' ? 'admin-sekolah' : 'superadmin'));
$dashboardUrl = (string) ($dashboardUrl ?? base_url($areaPrefix === 'admin-sekolah' ? 'admin-sekolah/dashboard' : 'dashboard/superadmin'));
$pageHeading = (string) ($pageHeading ?? 'Data DUDI');
$breadcrumbParent = (string) ($breadcrumbParent ?? 'Manajemen DUDI');
$breadcrumbCurrent = (string) ($breadcrumbCurrent ?? 'Data DUDI');
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
                        <input type="text" data-kt-perusahaan-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari DUDI" />
                    </div>
                </div>
                <div class="card-toolbar">
                    <div class="d-flex justify-content-end" data-kt-perusahaan-table-toolbar="base">
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            <i class="ki-duotone ki-filter fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>Filter
                        </button>
                        <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                            <div class="px-7 py-5">
                                <div class="fs-5 text-dark fw-bold">Filter Kota</div>
                            </div>
                            <div class="separator border-gray-200"></div>
                            <div class="px-7 py-5" data-kt-perusahaan-table-filter="form">
                                <div class="mb-10">
                                    <label class="form-label fs-6 fw-semibold">Kota:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true" data-placeholder="Pilih kota" data-allow-clear="true" data-kt-perusahaan-table-filter="kota" data-hide-search="true">
                                        <option></option>
                                        <?php foreach ($daftar_kota as $item): ?>
                                            <option value="<?= esc((string) $item['kota'], 'attr') ?>"><?= esc((string) $item['kota']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6" data-kt-menu-dismiss="true" data-kt-perusahaan-table-filter="reset">Reset</button>
                                    <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true" data-kt-perusahaan-table-filter="filter">Apply</button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_tambah_perusahaan">
                            <i class="ki-duotone ki-plus fs-2"></i>Tambah DUDI
                        </button>
                    </div>

                    <div class="d-flex justify-content-end align-items-center d-none" data-kt-perusahaan-table-toolbar="selected">
                        <div class="fw-bold me-5">
                            <span class="me-2" data-kt-perusahaan-table-select="selected_count"></span>Selected
                        </div>
                        <button type="button" class="btn btn-danger" data-kt-perusahaan-table-select="delete_selected">Hapus Terpilih</button>
                    </div>
                </div>
            </div>

            <div class="card-body py-4">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_perusahaan">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </th>
                            <th class="min-w-250px">DUDI</th>
                            <th class="min-w-150px">Telepon</th>
                            <th class="min-w-150px">Kota</th>
                            <th class="min-w-250px">Alamat</th>
                            <th class="min-w-225px">Kerjasama</th>
                            <th class="text-end min-w-120px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="kt_modal_tambah_perusahaan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_tambah_perusahaan_header">
                <h2 class="fw-bold">Tambah DUDI</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-perusahaan-modal-action="close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_tambah_perusahaan_form" class="form" action="#">
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_tambah_perusahaan_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_tambah_perusahaan_header" data-kt-scroll-wrappers="#kt_modal_tambah_perusahaan_scroll" data-kt-scroll-offset="300px">
                        <div class="fv-row mb-7">
                            <label class="d-block fw-semibold fs-6 mb-5">Logo</label>
                            <div class="image-input image-input-outline image-input-placeholder image-input-empty" data-kt-image-input="true" data-kt-perusahaan-logo-input="true" data-image-input-initial="" data-image-input-placeholder="<?= esc($blankLogoUrl, 'attr') ?>">
                                <div class="image-input-wrapper w-125px h-125px" style="background-image: url('<?= esc($blankLogoUrl) ?>');"></div>
                                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change logo">
                                    <i class="ki-duotone ki-pencil fs-7">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <input type="file" name="logo" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="logo_remove" />
                                </label>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel logo">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove logo">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Nama DUDI</label>
                            <input type="text" name="nama_perusahaan" class="form-control form-control-solid" placeholder="Nama perusahaan / DUDI" required />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" name="email" class="form-control form-control-solid" placeholder="perusahaan@example.com" />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Nomor Telepon</label>
                            <input type="text" name="no_telepon" class="form-control form-control-solid" placeholder="08xxxxxxxxxx / 021xxxxxxx" />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Kota</label>
                            <input type="text" name="kota" class="form-control form-control-solid" placeholder="Contoh: Surabaya" />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Alamat</label>
                            <textarea name="alamat" class="form-control form-control-solid" rows="4" placeholder="Alamat lengkap perusahaan"></textarea>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-5">Kerjasama</label>
                            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-5">
                                <div class="fw-semibold fs-7 text-gray-700">
                                    Pilih jenis kerjasama yang sudah atau akan dijalankan DUDI ini bersama sekolah.
                                </div>
                            </div>
                            <?php foreach ($daftar_kerjasama as $index => $kerjasama): ?>
                                <div class="d-flex fv-row">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="id_kerjasama[]" type="checkbox" value="<?= (int) $kerjasama['id_kerjasama'] ?>" id="kt_modal_tambah_perusahaan_kerjasama_<?= (int) $kerjasama['id_kerjasama'] ?>" />
                                        <label class="form-check-label" for="kt_modal_tambah_perusahaan_kerjasama_<?= (int) $kerjasama['id_kerjasama'] ?>">
                                            <div class="fw-bold text-gray-800"><?= esc((string) $kerjasama['nama_kerjasama']) ?></div>
                                            <?php if (! empty($kerjasama['deskripsi'])): ?>
                                                <div class="text-gray-600"><?= esc((string) $kerjasama['deskripsi']) ?></div>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </div>
                                <?php if ($index !== count($daftar_kerjasama) - 1): ?>
                                    <div class="separator separator-dashed my-4"></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-kt-perusahaan-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-primary" data-kt-perusahaan-modal-action="submit">
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

<div class="modal fade" id="kt_modal_edit_perusahaan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_edit_perusahaan_header">
                <h2 class="fw-bold">Edit DUDI</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-perusahaan-edit-modal-action="close">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_perusahaan_form" class="form" action="#">
                    <input type="hidden" name="id_perusahaan" />
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_perusahaan_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_edit_perusahaan_header" data-kt-scroll-wrappers="#kt_modal_edit_perusahaan_scroll" data-kt-scroll-offset="300px">
                        <div class="fv-row mb-7">
                            <label class="d-block fw-semibold fs-6 mb-5">Logo</label>
                            <div class="image-input image-input-outline image-input-placeholder image-input-empty" data-kt-image-input="true" data-kt-perusahaan-logo-input="true" data-image-input-initial="" data-image-input-placeholder="<?= esc($blankLogoUrl, 'attr') ?>">
                                <div class="image-input-wrapper w-125px h-125px" style="background-image: url('<?= esc($blankLogoUrl) ?>');"></div>
                                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change logo">
                                    <i class="ki-duotone ki-pencil fs-7">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <input type="file" name="logo" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="logo_remove" />
                                </label>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel logo">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove logo">
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Nama DUDI</label>
                            <input type="text" name="nama_perusahaan" class="form-control form-control-solid" placeholder="Nama perusahaan / DUDI" required />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" name="email" class="form-control form-control-solid" placeholder="perusahaan@example.com" />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Nomor Telepon</label>
                            <input type="text" name="no_telepon" class="form-control form-control-solid" placeholder="08xxxxxxxxxx / 021xxxxxxx" />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Kota</label>
                            <input type="text" name="kota" class="form-control form-control-solid" placeholder="Contoh: Surabaya" />
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Alamat</label>
                            <textarea name="alamat" class="form-control form-control-solid" rows="4" placeholder="Alamat lengkap perusahaan"></textarea>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-5">Kerjasama</label>
                            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-5">
                                <div class="fw-semibold fs-7 text-gray-700">
                                    Pilih jenis kerjasama yang aktif untuk DUDI ini bersama sekolah.
                                </div>
                            </div>
                            <?php foreach ($daftar_kerjasama as $index => $kerjasama): ?>
                                <div class="d-flex fv-row">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input me-3" name="id_kerjasama[]" type="checkbox" value="<?= (int) $kerjasama['id_kerjasama'] ?>" id="kt_modal_edit_perusahaan_kerjasama_<?= (int) $kerjasama['id_kerjasama'] ?>" />
                                        <label class="form-check-label" for="kt_modal_edit_perusahaan_kerjasama_<?= (int) $kerjasama['id_kerjasama'] ?>">
                                            <div class="fw-bold text-gray-800"><?= esc((string) $kerjasama['nama_kerjasama']) ?></div>
                                            <?php if (! empty($kerjasama['deskripsi'])): ?>
                                                <div class="text-gray-600"><?= esc((string) $kerjasama['deskripsi']) ?></div>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </div>
                                <?php if ($index !== count($daftar_kerjasama) - 1): ?>
                                    <div class="separator separator-dashed my-4"></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-kt-perusahaan-edit-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-primary" data-kt-perusahaan-edit-modal-action="submit">
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
    window.ktPerusahaanConfig = {
        indexUrl: '<?= site_url($areaPrefix . '/perusahaan') ?>',
        simpanUrl: '<?= site_url($areaPrefix . '/perusahaan/simpan') ?>',
        updateUrl: '<?= site_url($areaPrefix . '/perusahaan/update') ?>',
        hapusUrl: '<?= site_url($areaPrefix . '/perusahaan/hapus') ?>',
        hapusMassalUrl: '<?= site_url($areaPrefix . '/perusahaan/hapus-massal') ?>',
        blankLogoUrl: '<?= base_url('assets/media/svg/files/blank-image.svg') ?>'
    };
</script>
<script src="<?= base_url('assets/js/custom/perusahaan/perusahaan.js') ?>"></script>
<?= $this->endSection() ?>
