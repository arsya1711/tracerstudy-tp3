<?php
$slugPeran = (string) (session()->get('slug_peran') ?? '');
$isAlumni = $slugPeran === 'alumni';
$isAdminSekolah = $slugPeran === 'admin_sekolah';
$dashboardUrl = base_url('dashboard/superadmin');
$appName = 'Tracer Study Alumni';
$schoolName = 'SMK Teratai Putih 3';
$schoolLogoUrl = base_url('assets/media/logos/logo-smk-teratai-putih-3.svg');
$userName = trim((string) (session()->get('nama_lengkap') ?? 'Pengguna')) ?: 'Pengguna';
$userInitial = strtoupper(substr($userName, 0, 1));
$profileUrl = $isAlumni ? base_url('alumni/profil') : base_url('profil-akun');
$roleLabel = match ($slugPeran) {
    'alumni' => 'Alumni',
    'admin_sekolah' => 'Admin Sekolah',
    default => 'Superadmin',
};
switch ($slugPeran) {
    case 'alumni':
        $dashboardUrl = base_url('alumni/dashboard');
        break;
    case 'admin_sekolah':
        $dashboardUrl = base_url('admin-sekolah/dashboard');
        break;
}

$legalisirBadgeCount = 0;
try {
    /*
    | Badge legalisir dihitung langsung di sidebar karena partial ini
    | dipakai oleh semua role. Admin melihat jumlah pengajuan baru,
    | alumni melihat pengajuan miliknya yang masih perlu perhatian.
    */
    $dbSidebar = db_connect();
    if ($dbSidebar->tableExists('tb_pengajuan_legalisir')) {
        if ($isAdminSekolah || (! $isAlumni && $slugPeran === 'superadmin')) {
            $legalisirBadgeCount = (int) $dbSidebar->table('tb_pengajuan_legalisir')
                ->where('status', 'diajukan')
                ->countAllResults();
        } elseif ($isAlumni && $dbSidebar->tableExists('tb_alumni')) {
            $alumniSidebar = $dbSidebar->table('tb_alumni')
                ->select('id_alumni')
                ->where('id_pengguna', (int) session()->get('id_pengguna'))
                ->get()
                ->getRowArray();

            if ($alumniSidebar !== null) {
                $legalisirBadgeCount = (int) $dbSidebar->table('tb_pengajuan_legalisir')
                    ->where('id_alumni', (int) $alumniSidebar['id_alumni'])
                    ->whereIn('status', ['diajukan', 'diproses', 'ditolak'])
                    ->countAllResults();
            }
        }
    }
} catch (Throwable) {
    $legalisirBadgeCount = 0;
}

$menuItems = [];

if ($isAlumni) {
    $menuItems = [
        ['label' => 'Dashboard', 'icon' => 'ki-element-11', 'url' => base_url('alumni/dashboard'), 'active' => uri_string() === 'alumni/dashboard'],
        ['label' => 'Profil', 'icon' => 'ki-profile-user', 'url' => base_url('alumni/profil'), 'active' => uri_string() === 'alumni/profil'],
        ['label' => 'Tracer', 'icon' => 'ki-chart-simple-3', 'url' => base_url('alumni/tracer'), 'active' => uri_string() === 'alumni/tracer'],
        ['label' => 'Legalisir', 'icon' => 'ki-check-circle', 'url' => base_url('alumni/legalisir'), 'active' => uri_string() === 'alumni/legalisir', 'badge' => $legalisirBadgeCount],
    ];
} elseif ($isAdminSekolah) {
    $menuItems = [
        ['label' => 'Dashboard', 'icon' => 'ki-element-11', 'url' => base_url('admin-sekolah/dashboard'), 'active' => in_array(uri_string(), ['admin-sekolah/dashboard', 'dashboard/admin-sekolah'], true)],
        ['label' => 'Tracer Alumni', 'icon' => 'ki-chart-simple-3', 'url' => base_url('admin-sekolah/tracer'), 'active' => uri_string() === 'admin-sekolah/tracer'],
        ['label' => 'Legalisir', 'icon' => 'ki-check-circle', 'url' => base_url('admin-sekolah/legalisir'), 'active' => uri_string() === 'admin-sekolah/legalisir', 'badge' => $legalisirBadgeCount],
        ['label' => 'Angkatan', 'icon' => 'ki-profile-user', 'url' => base_url('admin-sekolah/angkatan'), 'active' => uri_string() === 'admin-sekolah/angkatan'],
        ['label' => 'Kompetensi', 'icon' => 'ki-shield-tick', 'url' => base_url('admin-sekolah/kompetensi'), 'active' => uri_string() === 'admin-sekolah/kompetensi'],
        ['label' => 'Aktivitas', 'icon' => 'ki-abstract-26', 'url' => base_url('admin-sekolah/aktivitas'), 'active' => uri_string() === 'admin-sekolah/aktivitas'],
    ];
} else {
    $menuItems = [
        ['label' => 'Dashboard', 'icon' => 'ki-element-11', 'url' => base_url('dashboard/superadmin'), 'active' => uri_string() === 'dashboard/superadmin'],
        ['label' => 'Tracer Alumni', 'icon' => 'ki-chart-simple-3', 'url' => base_url('superadmin/tracer'), 'active' => uri_string() === 'superadmin/tracer'],
        ['label' => 'Legalisir', 'icon' => 'ki-check-circle', 'url' => base_url('superadmin/legalisir'), 'active' => uri_string() === 'superadmin/legalisir', 'badge' => $legalisirBadgeCount],
        ['label' => 'Angkatan', 'icon' => 'ki-profile-user', 'url' => base_url('superadmin/angkatan'), 'active' => uri_string() === 'superadmin/angkatan'],
        ['label' => 'Kompetensi', 'icon' => 'ki-shield-tick', 'url' => base_url('superadmin/kompetensi'), 'active' => uri_string() === 'superadmin/kompetensi'],
        ['label' => 'Aktivitas', 'icon' => 'ki-abstract-26', 'url' => base_url('superadmin/aktivitas'), 'active' => uri_string() === 'superadmin/aktivitas'],
        ['label' => 'Admin', 'icon' => 'ki-setting-3', 'url' => base_url('superadmin/admin'), 'active' => uri_string() === 'superadmin/admin'],
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
                    <button type="button" class="kt-mobile-menu-button d-flex d-lg-none" id="kt_app_sidebar_mobile_toggle"
                        aria-label="Buka menu navigasi" aria-controls="kt_app_sidebar" aria-expanded="false">
                        <span class="kt-mobile-menu-icon" aria-hidden="true">
                            <span></span><span></span><span></span>
                        </span>
                    </button>

                    <a href="<?= $dashboardUrl ?>" class="kt-header-school-brand">
                        <img src="<?= esc($schoolLogoUrl) ?>" alt="" class="kt-header-school-logo">
                        <span class="kt-header-school-copy">
                            <span class="kt-header-school-title"><?= esc($schoolName) ?></span>
                            <span class="kt-header-school-subtitle"><?= esc($appName) ?></span>
                        </span>
                    </a>

                    <div class="kt-header-actions">
                        <a href="<?= esc($profileUrl) ?>" class="kt-header-user d-none d-xl-flex" title="Buka profil akun" aria-label="Buka profil akun <?= esc($userName) ?>">
                            <span class="kt-header-user-avatar"><?= esc($userInitial) ?></span>
                            <span>
                                <span class="kt-header-user-name"><?= esc($userName) ?></span>
                                <span class="kt-header-user-role"><?= esc($roleLabel) ?></span>
                            </span>
                        </a>
                        <form method="post" action="<?= base_url('logout') ?>" class="d-inline js-logout-form">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn kt-header-logout js-logout-trigger" aria-label="Keluar dari sistem" title="Keluar">
                                <span class="d-none d-sm-inline">Keluar</span>
                                <i class="ki-duotone ki-exit-right fs-2 m-0" aria-hidden="true">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true"
                    data-kt-drawer-name="app-sidebar" data-kt-drawer-activate="{default: true, lg: false}"
                    data-kt-drawer-overlay="true" data-kt-drawer-width="{default: '86%', sm: '320px'}"
                    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle"
                    data-kt-drawer-close="#kt_app_sidebar_close" data-kt-drawer-escape="true">
                    <div class="app-sidebar-logo" id="kt_app_sidebar_logo">
                        <a href="<?= $dashboardUrl ?>" class="kt-school-brand">
                            <img src="<?= esc($schoolLogoUrl) ?>" alt="Logo SMK Teratai Putih 3" class="kt-school-brand-logo">
                            <span class="kt-school-brand-copy">
                                <span class="kt-school-brand-title"><?= esc($schoolName) ?></span>
                                <span class="kt-school-brand-subtitle"><?= esc($appName) ?></span>
                            </span>
                        </a>
                        <button type="button" id="kt_app_sidebar_close"
                            class="kt-sidebar-close d-flex d-lg-none" data-sidebar-close data-kt-drawer-dismiss="true"
                            title="Tutup menu" aria-label="Tutup menu navigasi">
                            <span class="kt-sidebar-close-icon" aria-hidden="true"><span></span><span></span></span>
                        </button>
                    </div>
                    <a href="<?= esc($profileUrl) ?>" class="kt-sidebar-profile" title="Buka profil akun" aria-label="Buka profil akun <?= esc($userName) ?>">
                        <span class="kt-sidebar-profile-avatar"><?= esc($userInitial) ?></span>
                        <span class="kt-sidebar-profile-copy">
                            <span class="kt-sidebar-profile-name"><?= esc($userName) ?></span>
                            <span class="kt-sidebar-profile-role"><?= esc($roleLabel) ?></span>
                        </span>
                    </a>
                    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
                        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
                            <div id="kt_app_sidebar_menu_scroll" class="scroll-y">
                                <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" data-kt-menu="true">
                                    <div class="menu-item">
                                        <div class="menu-content">
                                            <span class="menu-section-label">Menu Utama</span>
                                        </div>
                                    </div>
                                    <?php foreach ($menuItems as $item): ?>
                                        <div class="menu-item">
                                            <a class="menu-link <?= $item['active'] ? 'active' : '' ?>" href="<?= esc($item['url']) ?>">
                                                <span class="menu-icon">
                                                    <i class="ki-duotone <?= esc($item['icon'] ?? 'ki-element-11') ?> fs-2">
                                                        <span class="path1"></span><span class="path2"></span>
                                                        <span class="path3"></span><span class="path4"></span>
                                                    </i>
                                                </span>
                                                <span class="menu-title"><?= esc($item['label']) ?></span>
                                                <?php if ((int) ($item['badge'] ?? 0) > 0): ?>
                                                    <span class="menu-badge">
                                                        <span class="badge badge-danger fw-bold"><?= (int) $item['badge'] ?></span>
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="app-sidebar-footer flex-column-auto" id="kt_app_sidebar_footer">
                        <form method="post" action="<?= base_url('logout') ?>" class="js-logout-form">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn kt-sidebar-logout w-100 js-logout-trigger">
                                <span class="btn-label">Keluar dari Sistem</span>
                                <i class="ki-duotone ki-exit-right fs-2 m-0" aria-hidden="true"><span class="path1"></span><span class="path2"></span></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">
