<?php
$slugPeran = (string) (session()->get('slug_peran') ?? '');
$isAlumni = $slugPeran === 'alumni';
$isAdminSekolah = $slugPeran === 'admin_sekolah';
$dashboardUrl = base_url('dashboard/superadmin');
switch ($slugPeran) {
    case 'alumni':
        $dashboardUrl = base_url('alumni/dashboard');
        break;
    case 'admin_sekolah':
        $dashboardUrl = base_url('admin-sekolah/dashboard');
        break;
}

$menuItems = [];

if ($isAlumni) {
    $menuItems = [
        ['label' => 'Dashboard', 'url' => base_url('alumni/dashboard'), 'active' => uri_string() === 'alumni/dashboard'],
        ['label' => 'Profil', 'url' => base_url('alumni/profil'), 'active' => uri_string() === 'alumni/profil'],
        ['label' => 'Isi Tracer', 'url' => base_url('alumni/tracer'), 'active' => uri_string() === 'alumni/tracer'],
        ['label' => 'Legalisir', 'url' => base_url('alumni/legalisir'), 'active' => uri_string() === 'alumni/legalisir'],
    ];
} elseif ($isAdminSekolah) {
    $menuItems = [
        ['label' => 'Dashboard', 'url' => base_url('admin-sekolah/dashboard'), 'active' => in_array(uri_string(), ['admin-sekolah/dashboard', 'dashboard/admin-sekolah'], true)],
        ['label' => 'Alumni', 'url' => base_url('admin-sekolah/alumni'), 'active' => str_starts_with(uri_string(), 'admin-sekolah/alumni')],
        ['label' => 'Tracer Alumni', 'url' => base_url('admin-sekolah/tracer'), 'active' => uri_string() === 'admin-sekolah/tracer'],
        ['label' => 'Legalisir', 'url' => base_url('admin-sekolah/legalisir'), 'active' => uri_string() === 'admin-sekolah/legalisir'],
        ['label' => 'Angkatan', 'url' => base_url('admin-sekolah/angkatan'), 'active' => uri_string() === 'admin-sekolah/angkatan'],
        ['label' => 'Kompetensi', 'url' => base_url('admin-sekolah/kompetensi'), 'active' => uri_string() === 'admin-sekolah/kompetensi'],
        ['label' => 'Aktivitas', 'url' => base_url('admin-sekolah/aktivitas'), 'active' => uri_string() === 'admin-sekolah/aktivitas'],
    ];
} else {
    $menuItems = [
        ['label' => 'Dashboard', 'url' => base_url('dashboard/superadmin'), 'active' => uri_string() === 'dashboard/superadmin'],
        ['label' => 'Alumni', 'url' => base_url('superadmin/alumni'), 'active' => str_starts_with(uri_string(), 'superadmin/alumni')],
        ['label' => 'Tracer Alumni', 'url' => base_url('superadmin/tracer'), 'active' => uri_string() === 'superadmin/tracer'],
        ['label' => 'Legalisir', 'url' => base_url('superadmin/legalisir'), 'active' => uri_string() === 'superadmin/legalisir'],
        ['label' => 'Angkatan', 'url' => base_url('superadmin/angkatan'), 'active' => uri_string() === 'superadmin/angkatan'],
        ['label' => 'Kompetensi', 'url' => base_url('superadmin/kompetensi'), 'active' => uri_string() === 'superadmin/kompetensi'],
        ['label' => 'Aktivitas', 'url' => base_url('superadmin/aktivitas'), 'active' => uri_string() === 'superadmin/aktivitas'],
        ['label' => 'Admin', 'url' => base_url('superadmin/admin'), 'active' => uri_string() === 'superadmin/admin'],
    ];
}
?>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
    <script>var defaultThemeMode = "light"; var themeMode = localStorage.getItem("data-bs-theme") || defaultThemeMode; document.documentElement.setAttribute("data-bs-theme", themeMode);</script>
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            <div id="kt_app_header" class="app-header" data-kt-sticky="true">
                <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
                    <div class="d-flex align-items-center d-lg-none ms-n3 me-1 me-md-2" id="kt_app_sidebar_mobile_toggle">
                        <div class="btn btn-icon btn-active-color-primary w-35px h-35px">
                            <i class="ki-duotone ki-abstract-14 fs-2"><span class="path1"></span><span class="path2"></span></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
                        <a href="<?= $dashboardUrl ?>" class="fw-bolder text-gray-900 fs-4">Tracer Study</a>
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <span class="text-muted fw-semibold d-none d-md-inline"><?= esc(session()->get('nama_lengkap') ?? 'Pengguna') ?></span>
                        <a href="<?= base_url('logout') ?>" class="btn btn-sm btn-light-danger js-logout-trigger" data-logout-url="<?= base_url('logout') ?>">Logout</a>
                    </div>
                </div>
            </div>
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true"
                    data-kt-drawer-name="app-sidebar" data-kt-drawer-activate="{default: true, lg: false}"
                    data-kt-drawer-overlay="true" data-kt-drawer-width="225px" data-kt-drawer-direction="start"
                    data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
                    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
                        <a href="<?= $dashboardUrl ?>" class="text-white fw-bolder fs-4">Tracer Study</a>
                    </div>
                    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
                        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
                            <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3">
                                <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" data-kt-menu="true">
                                    <?php foreach ($menuItems as $item): ?>
                                        <div class="menu-item">
                                            <a class="menu-link <?= $item['active'] ? 'active' : '' ?>" href="<?= esc($item['url']) ?>">
                                                <span class="menu-icon">
                                                    <i class="ki-duotone ki-element-11 fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                                </span>
                                                <span class="menu-title"><?= esc($item['label']) ?></span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
                        <a href="<?= base_url('logout') ?>" class="btn btn-flex flex-center btn-custom btn-danger overflow-hidden text-nowrap px-0 h-40px w-100 js-logout-trigger" data-logout-url="<?= base_url('logout') ?>">
                            <span class="btn-label">Logout</span>
                            <i class="ki-duotone ki-exit-right btn-icon fs-2 m-0"><span class="path1"></span><span class="path2"></span></i>
                        </a>
                    </div>
                </div>
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">
