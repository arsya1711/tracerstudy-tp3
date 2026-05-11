<?php
/*
|-------------------------------------------------------------------
| PARTIAL SIDEBAR
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: partial ini memuat bagian awal body
| dashboard Metronic, termasuk app-root, app-page, header, sidebar,
| user menu, dan pembuka area main sebelum toolbar atau konten halaman
| dirender oleh view turunan.
| Alur kerja: layouts/main memanggil partial ini setelah partial head,
| lalu partial membuka struktur body sampai tepat sebelum toolbar dan
| konten halaman ditampilkan oleh section content.
|
| Tips Debugging:
| - Jika nama user tidak tampil, periksa session nama_lengkap dan nama_peran saat login sukses.
| - Jika menu Kompetensi, Angkatan, Aktivitas, atau Kerjasama tidak aktif, periksa uri_string() sesuai route superadmin yang dibuka.
*/

$slugPeran = (string) (session()->get('slug_peran') ?? '');
$isPelamar = in_array($slugPeran, ['pelamar_umum', 'pelamar_alumni'], true);
$isAdminSekolah = $slugPeran === 'admin_sekolah';
$isAdminDudi = in_array($slugPeran, ['admin_dudi', 'admin_perusahaan'], true);
$statusPendaftaranPelamar = (string) (session()->get('status_pendaftaran') ?? '');
$pelamarMenungguPersetujuan = $isPelamar && $statusPendaftaranPelamar !== '' && $statusPendaftaranPelamar !== 'aktif';
$dashboardUrl = $isPelamar ? base_url('pelamar/dashboard') : ($isAdminSekolah ? base_url('admin-sekolah/dashboard') : ($isAdminDudi ? base_url('admin-dudi/dashboard') : base_url('dashboard/superadmin')));
$headerTitle = $isPelamar ? 'Pelamar' : ($isAdminSekolah ? 'Admin Sekolah/BKK' : ($isAdminDudi ? 'Admin DUDI' : 'Super Admin'));
$headerMenuAktif = false;

if ($isPelamar) {
	$headerMenuAktif = str_starts_with(uri_string(), 'pelamar/');
} elseif ($isAdminSekolah) {
	$headerMenuAktif = str_starts_with(uri_string(), 'admin-sekolah/') || uri_string() === 'dashboard/admin-sekolah';
} elseif ($isAdminDudi) {
	$headerMenuAktif = str_starts_with(uri_string(), 'admin-dudi/');
} else {
	$headerMenuAktif = uri_string() === 'dashboard/superadmin' || uri_string() === 'superadmin/kompetensi' || uri_string() === 'superadmin/angkatan' || uri_string() === 'superadmin/aktivitas' || uri_string() === 'superadmin/tracer' || uri_string() === 'superadmin/kerjasama' || uri_string() === 'superadmin/perusahaan' || uri_string() === 'superadmin/lowongan' || uri_string() === 'superadmin/pelamar' || uri_string() === 'superadmin/admin' || uri_string() === 'superadmin/lamaran';
}
?>
<!--begin::Body-->
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
	data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
	data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
	data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
	<!--begin::Theme mode setup on page load-->
	<script>var defaultThemeMode = "light"; var themeMode; if (document.documentElement) { if (document.documentElement.hasAttribute("data-bs-theme-mode")) { themeMode = document.documentElement.getAttribute("data-bs-theme-mode"); } else { if (localStorage.getItem("data-bs-theme") !== null) { themeMode = localStorage.getItem("data-bs-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-bs-theme", themeMode); }</script>
	<!--end::Theme mode setup on page load-->
	<!--begin::App-->
	<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
		<!--begin::Page-->
		<div class="app-page flex-column flex-column-fluid" id="kt_app_page">
			<!--begin::Header-->
			<div id="kt_app_header" class="app-header" data-kt-sticky="true"
				data-kt-sticky-activate="{default: true, lg: true}" data-kt-sticky-name="app-header-minimize"
				data-kt-sticky-offset="{default: '200px', lg: '0'}" data-kt-sticky-animation="false">
				<!--begin::Header container-->
				<div class="app-container container-fluid d-flex align-items-stretch justify-content-between"
					id="kt_app_header_container">
					<!--begin::Sidebar mobile toggle-->
					<div class="d-flex align-items-center d-lg-none ms-n3 me-1 me-md-2" title="Show sidebar menu">
						<div class="btn btn-icon btn-active-color-primary w-35px h-35px"
							id="kt_app_sidebar_mobile_toggle">
							<i class="ki-duotone ki-abstract-14 fs-2 fs-md-1">
								<span class="path1"></span>
								<span class="path2"></span>
							</i>
						</div>
					</div>
					<!--end::Sidebar mobile toggle-->
					<!--begin::Mobile logo-->
					<div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
						<a href="<?= $dashboardUrl ?>" class="d-lg-none">
							<img alt="Logo" src="<?= base_url('assets/media/logos/default-small.svg') ?>" class="h-30px" />
						</a>
					</div>
					<!--end::Mobile logo-->
					<!--begin::Header wrapper-->
					<div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1"
						id="kt_app_header_wrapper">
						<!--begin::Menu wrapper-->
						<div class="app-header-menu app-header-mobile-drawer align-items-stretch" data-kt-drawer="true"
							data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}"
							data-kt-drawer-overlay="true" data-kt-drawer-width="250px" data-kt-drawer-direction="end"
							data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true"
							data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
							data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
							<!--begin::Menu-->
							<div class="menu menu-rounded menu-column menu-lg-row my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0"
								id="kt_app_header_menu" data-kt-menu="true">
								<!--begin:Menu item-->
								<div data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
									data-kt-menu-placement="bottom-start"
									class="menu-item menu-here-bg menu-lg-down-accordion me-0 me-lg-2 <?= $headerMenuAktif ? 'here show' : '' ?>">
									<!--begin:Menu link-->
									<span class="menu-link">
										<span class="menu-title"><?= esc($headerTitle) ?></span>
										<span class="menu-arrow d-lg-none"></span>
									</span>
									<!--end:Menu link-->
								</div>
								<!--end:Menu item-->
							</div>
							<!--end::Menu-->
						</div>
						<!--end::Menu wrapper-->
						<!--begin::Navbar-->
						<div class="app-navbar flex-shrink-0">
							<div class="app-navbar-item ms-1 ms-md-4">
								<div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px"
									data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent"
									data-kt-menu-placement="bottom-end" id="kt_menu_item_wow">
									<i class="ki-duotone ki-notification-status fs-2">
										<span class="path1"></span>
										<span class="path2"></span>
										<span class="path3"></span>
										<span class="path4"></span>
									</i>
								</div>
							</div>
							<!--begin::User menu-->
							<div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">
								<div class="cursor-pointer symbol symbol-35px"
									data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent"
									data-kt-menu-placement="bottom-end">
									<img src="<?= base_url('assets/media/avatars/300-3.jpg') ?>" class="rounded-3" alt="user" />
								</div>
								<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
									data-kt-menu="true">
									<div class="menu-item px-3">
										<div class="menu-content d-flex align-items-center px-3">
											<div class="symbol symbol-50px me-5">
												<img alt="Logo" src="<?= base_url('assets/media/avatars/300-3.jpg') ?>" />
											</div>
											<div class="d-flex flex-column">
												<div class="fw-bold d-flex align-items-center fs-5"><?= esc(session()->get('nama_lengkap') ?? 'Pengguna') ?>
													<span
														class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2"><?= esc(session()->get('nama_peran') ?? 'Role') ?></span>
												</div>
												<span class="fw-semibold text-muted fs-7"><?= esc(session()->get('nama_peran') ?? '-') ?></span>
											</div>
										</div>
									</div>
									<div class="separator my-2"></div>
									<div class="menu-item px-5">
										<a href="<?= $dashboardUrl ?>" class="menu-link px-5">Dashboard</a>
									</div>
									<div class="separator my-2"></div>
									<div class="menu-item px-5 my-1">
										<a href="#" class="menu-link px-5">Account Settings</a>
									</div>
									<?php
									/*
									|-------------------------------------------------------------------
									| LINK LOGOUT DASHBOARD
									|-------------------------------------------------------------------
									| Penjelasan fungsi kode ini: menampilkan tautan logout di user
									| menu dashboard dan menandainya agar bisa ditangkap script
									| konfirmasi SweetAlert2 sebelum session dihancurkan.
									| Alur kerja: user klik link ini, JavaScript footer mencegah
									| redirect langsung, lalu menampilkan popup konfirmasi. Jika
									| user setuju, browser baru diarahkan ke URL logout.
									|
									| Tips Debugging:
									| - Jika klik logout langsung pindah tanpa popup, periksa class js-logout-trigger pada link ini.
									| - Jika URL logout salah, periksa nilai data-logout-url yang dikirim ke script footer.
									*/
									?>
									<div class="menu-item px-5">
										<a href="<?= base_url('logout') ?>" class="menu-link px-5 js-logout-trigger" data-logout-url="<?= base_url('logout') ?>">Sign Out</a>
									</div>
								</div>
							</div>
							<!--end::User menu-->
							<div class="app-navbar-item d-lg-none ms-2 me-n2" title="Show header menu">
								<div class="btn btn-flex btn-icon btn-active-color-primary w-30px h-30px"
									id="kt_app_header_menu_toggle">
									<i class="ki-duotone ki-element-4 fs-1">
										<span class="path1"></span>
										<span class="path2"></span>
									</i>
								</div>
							</div>
						</div>
						<!--end::Navbar-->
					</div>
					<!--end::Header wrapper-->
				</div>
				<!--end::Header container-->
			</div>
			<!--end::Header-->
			<!--begin::Wrapper-->
			<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
				<!--begin::Sidebar-->
				<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true"
					data-kt-drawer-name="app-sidebar" data-kt-drawer-activate="{default: true, lg: false}"
					data-kt-drawer-overlay="true" data-kt-drawer-width="225px" data-kt-drawer-direction="start"
					data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
					<!--begin::Logo-->
					<div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
						<a href="<?= $dashboardUrl ?>">
							<img alt="Logo" src="<?= base_url('assets/media/logos/default-dark.svg') ?>"
								class="h-25px app-sidebar-logo-default" />
							<img alt="Logo" src="<?= base_url('assets/media/logos/default-small.svg') ?>"
								class="h-20px app-sidebar-logo-minimize" />
						</a>
						<div id="kt_app_sidebar_toggle"
							class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate"
							data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
							data-kt-toggle-name="app-sidebar-minimize">
							<i class="ki-duotone ki-black-left-line fs-3 rotate-180">
								<span class="path1"></span>
								<span class="path2"></span>
							</i>
						</div>
					</div>
					<!--end::Logo-->
					<!--begin::sidebar menu-->
					<div class="app-sidebar-menu overflow-hidden flex-column-fluid">
						<div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
							<div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" data-kt-scroll="true"
								data-kt-scroll-activate="true" data-kt-scroll-height="auto"
								data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
								data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
								data-kt-scroll-save-state="true">
								<div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6"
									id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">
									<?php if ($isPelamar): ?>
										<div data-kt-menu-trigger="click" class="menu-item menu-accordion <?= (str_starts_with(uri_string(), 'pelamar/')) ? 'show here' : '' ?>">
											<span class="menu-link">
												<span class="menu-icon">
													<i class="ki-duotone ki-profile-user fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
														<span class="path4"></span>
													</i>
												</span>
												<span class="menu-title">Menu Pelamar</span>
												<span class="menu-arrow"></span>
											</span>
											<div class="menu-sub menu-sub-accordion">
												<div class="menu-item">
													<a class="menu-link <?= (uri_string() === 'pelamar/dashboard') ? 'active' : '' ?>"
														href="<?= base_url('pelamar/dashboard') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Dashboard</span>
													</a>
												</div>
												<?php if (! $pelamarMenungguPersetujuan): ?>
													<div class="menu-item">
														<a class="menu-link <?= (uri_string() === 'pelamar/profil') ? 'active' : '' ?>"
															href="<?= base_url('pelamar/profil') ?>">
															<span class="menu-bullet">
																<span class="bullet bullet-dot"></span>
															</span>
															<span class="menu-title">Profil</span>
														</a>
													</div>
													<div class="menu-item">
														<a class="menu-link <?= (uri_string() === 'pelamar/lowongan' || str_starts_with(uri_string(), 'pelamar/lowongan/')) ? 'active' : '' ?>"
															href="<?= base_url('pelamar/lowongan') ?>">
															<span class="menu-bullet">
																<span class="bullet bullet-dot"></span>
															</span>
															<span class="menu-title">Lowongan</span>
														</a>
													</div>
													<div class="menu-item">
														<a class="menu-link <?= (uri_string() === 'pelamar/lamaran' || str_starts_with(uri_string(), 'pelamar/lamaran/')) ? 'active' : '' ?>"
															href="<?= base_url('pelamar/lamaran') ?>">
															<span class="menu-bullet">
																<span class="bullet bullet-dot"></span>
															</span>
															<span class="menu-title">Riwayat Lamaran</span>
														</a>
													</div>
												<?php else: ?>
													<div class="menu-item px-4 pt-4">
														<div class="rounded bg-light-warning text-warning fw-semibold fs-8 px-3 py-3">
															Menu lain akan terbuka setelah admin BKK menyetujui akun kamu.
														</div>
													</div>
												<?php endif; ?>
											</div>
										</div>
									<?php elseif ($isAdminSekolah): ?>
										<?php
										/*
										|-------------------------------------------------------------------
										| SIDEBAR ADMIN SEKOLAH / BKK
										|-------------------------------------------------------------------
										| Bagian ini mengelompokkan menu Admin Sekolah berdasarkan konteks
										| kerja BKK: data sekolah, data pengguna, dan data DUDI/lowongan.
										| Alur kerja: setiap grup memakai accordion Metronic dan status aktif
										| dihitung dari uri_string() agar menu terbuka sesuai halaman saat ini.
										|
										| Tips Debugging:
										| - Jika menu tidak aktif, cek prefix route admin-sekolah/<modul>.
										| - Jika menu tidak terbuka, pastikan class show here masuk pada grup
										|   accordion yang sesuai.
										*/
										$adminSekolahUri = uri_string();
										$menuSekolahAktif = in_array($adminSekolahUri, [
											'admin-sekolah/dashboard',
											'dashboard/admin-sekolah',
											'admin-sekolah/angkatan',
											'admin-sekolah/kompetensi',
											'admin-sekolah/aktivitas',
											'admin-sekolah/tracer',
										], true);
										$menuPenggunaAktif = in_array($adminSekolahUri, [
											'admin-sekolah/admin',
											'admin-sekolah/pelamar',
										], true);
										$menuDudiAktif = in_array($adminSekolahUri, [
											'admin-sekolah/perusahaan',
											'admin-sekolah/lowongan',
											'admin-sekolah/lamaran',
										], true);
										?>
										<div data-kt-menu-trigger="click" class="menu-item menu-accordion <?= $menuSekolahAktif ? 'show here' : '' ?>">
											<span class="menu-link">
												<span class="menu-icon">
													<i class="ki-duotone ki-abstract-28 fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
													</i>
												</span>
												<span class="menu-title">Manajemen Sekolah</span>
												<span class="menu-arrow"></span>
											</span>
											<div class="menu-sub menu-sub-accordion">
												<div class="menu-item">
													<a class="menu-link <?= (uri_string() === 'admin-sekolah/dashboard' || uri_string() === 'dashboard/admin-sekolah') ? 'active' : '' ?>"
														href="<?= base_url('admin-sekolah/dashboard') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Dashboard BKK</span>
													</a>
												</div>
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-sekolah/angkatan' ? 'active' : '' ?>"
														href="<?= base_url('admin-sekolah/angkatan') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Data Angkatan</span>
													</a>
												</div>
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-sekolah/kompetensi' ? 'active' : '' ?>"
														href="<?= base_url('admin-sekolah/kompetensi') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Data Kompetensi Keahlian</span>
													</a>
												</div>
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-sekolah/aktivitas' ? 'active' : '' ?>"
														href="<?= base_url('admin-sekolah/aktivitas') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Data Aktivitas Alumni</span>
													</a>
												</div>
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-sekolah/tracer' ? 'active' : '' ?>"
														href="<?= base_url('admin-sekolah/tracer') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Data Tracer Alumni</span>
													</a>
												</div>
											</div>
										</div>
										<div data-kt-menu-trigger="click" class="menu-item menu-accordion <?= $menuPenggunaAktif ? 'show here' : '' ?>">
											<span class="menu-link">
												<span class="menu-icon">
													<i class="ki-duotone ki-profile-user fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
														<span class="path4"></span>
													</i>
												</span>
												<span class="menu-title">Manajemen Pengguna</span>
												<span class="menu-arrow"></span>
											</span>
											<div class="menu-sub menu-sub-accordion">
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-sekolah/admin' ? 'active' : '' ?>"
														href="<?= base_url('admin-sekolah/admin') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Data Admin</span>
													</a>
												</div>
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-sekolah/pelamar' ? 'active' : '' ?>"
														href="<?= base_url('admin-sekolah/pelamar') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Data Pelamar</span>
													</a>
												</div>
											</div>
										</div>
										<div data-kt-menu-trigger="click" class="menu-item menu-accordion <?= $menuDudiAktif ? 'show here' : '' ?>">
											<span class="menu-link">
												<span class="menu-icon">
													<i class="ki-duotone ki-office-bag fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
														<span class="path4"></span>
													</i>
												</span>
												<span class="menu-title">Manajemen DUDI</span>
												<span class="menu-arrow"></span>
											</span>
											<div class="menu-sub menu-sub-accordion">
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-sekolah/perusahaan' ? 'active' : '' ?>"
														href="<?= base_url('admin-sekolah/perusahaan') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Data DUDI</span>
													</a>
												</div>
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-sekolah/lowongan' ? 'active' : '' ?>"
														href="<?= base_url('admin-sekolah/lowongan') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Lowongan Kerja</span>
													</a>
												</div>
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-sekolah/lamaran' ? 'active' : '' ?>"
														href="<?= base_url('admin-sekolah/lamaran') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Monitor Lowongan Kerja</span>
													</a>
												</div>
											</div>
										</div>
									<?php elseif ($isAdminDudi): ?>
										<div data-kt-menu-trigger="click" class="menu-item menu-accordion <?= str_starts_with(uri_string(), 'admin-dudi/') ? 'show here' : '' ?>">
											<span class="menu-link">
												<span class="menu-icon">
													<i class="ki-duotone ki-office-bag fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
														<span class="path4"></span>
													</i>
												</span>
												<span class="menu-title">Menu Admin DUDI</span>
												<span class="menu-arrow"></span>
											</span>
											<div class="menu-sub menu-sub-accordion">
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-dudi/dashboard' ? 'active' : '' ?>"
														href="<?= base_url('admin-dudi/dashboard') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Dashboard</span>
													</a>
												</div>
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-dudi/lowongan' ? 'active' : '' ?>"
														href="<?= base_url('admin-dudi/lowongan') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Lowongan Saya</span>
													</a>
												</div>
												<div class="menu-item">
													<a class="menu-link <?= uri_string() === 'admin-dudi/lamaran' ? 'active' : '' ?>"
														href="<?= base_url('admin-dudi/lamaran') ?>">
														<span class="menu-bullet">
															<span class="bullet bullet-dot"></span>
														</span>
														<span class="menu-title">Lamaran Masuk</span>
													</a>
												</div>
											</div>
										</div>
									<?php else: ?>
									<div data-kt-menu-trigger="click" class="menu-item menu-accordion <?= (uri_string() === 'dashboard/superadmin' || uri_string() === 'superadmin/kompetensi' || uri_string() === 'superadmin/angkatan' || uri_string() === 'superadmin/aktivitas' || uri_string() === 'superadmin/tracer') ? 'show here' : '' ?>">
										<span class="menu-link">
											<span class="menu-icon">
												<i class="ki-duotone ki-abstract-28 fs-2">
													<span class="path1"></span>
													<span class="path2"></span>
												</i>
											</span>
											<span class="menu-title">Manajemen Sekolah</span>
											<span class="menu-arrow"></span>
										</span>
										<div class="menu-sub menu-sub-accordion">
											<div class="menu-item">
												<a class="menu-link <?= (uri_string() === 'dashboard/superadmin') ? 'active' : '' ?>"
													href="<?= base_url('dashboard/superadmin') ?>">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
													<span class="menu-title">Dashboard Super Admin</span>
												</a>
											</div>
											<div class="menu-item">
												<a class="menu-link <?= (uri_string() === 'superadmin/kompetensi') ? 'active' : '' ?>"
													href="<?= base_url('superadmin/kompetensi') ?>">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
													<span class="menu-title">Kompetensi Keahlian</span>
												</a>
											</div>
											<div class="menu-item">
												<a class="menu-link <?= (uri_string() === 'superadmin/angkatan') ? 'active' : '' ?>"
													href="<?= base_url('superadmin/angkatan') ?>">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
													<span class="menu-title">Angkatan</span>
												</a>
											</div>
											<div class="menu-item">
												<a class="menu-link <?= (uri_string() === 'superadmin/aktivitas') ? 'active' : '' ?>"
													href="<?= base_url('superadmin/aktivitas') ?>">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
													<span class="menu-title">Aktivitas</span>
												</a>
											</div>
											<div class="menu-item">
												<a class="menu-link <?= (uri_string() === 'superadmin/tracer') ? 'active' : '' ?>"
													href="<?= base_url('superadmin/tracer') ?>">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
													<span class="menu-title">Data Tracer Alumni</span>
												</a>
											</div>
										</div>
									</div>
									<div data-kt-menu-trigger="click" class="menu-item menu-accordion <?= (uri_string() === 'superadmin/perusahaan' || uri_string() === 'superadmin/lowongan' || uri_string() === 'superadmin/kerjasama') ? 'show here' : '' ?>">
										<span class="menu-link">
											<span class="menu-icon">
												<i class="ki-duotone ki-office-bag fs-2">
													<span class="path1"></span>
													<span class="path2"></span>
													<span class="path3"></span>
													<span class="path4"></span>
												</i>
											</span>
											<span class="menu-title">Manajemen DUDI</span>
											<span class="menu-arrow"></span>
										</span>
										<div class="menu-sub menu-sub-accordion">
											<div class="menu-item">
												<a class="menu-link <?= (uri_string() === 'superadmin/perusahaan') ? 'active' : '' ?>"
													href="<?= base_url('superadmin/perusahaan') ?>">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
													<span class="menu-title">Data DUDI</span>
												</a>
											</div>
											<div class="menu-item">
												<a class="menu-link <?= (uri_string() === 'superadmin/lowongan') ? 'active' : '' ?>"
													href="<?= base_url('superadmin/lowongan') ?>">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
													<span class="menu-title">Lowongan</span>
												</a>
											</div>
											<div class="menu-item">
												<a class="menu-link <?= (uri_string() === 'superadmin/kerjasama') ? 'active' : '' ?>"
													href="<?= base_url('superadmin/kerjasama') ?>">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
													<span class="menu-title">Kerjasama</span>
												</a>
											</div>
										</div>
									</div>
									<div data-kt-menu-trigger="click" class="menu-item menu-accordion <?= (uri_string() === 'superadmin/pelamar' || uri_string() === 'superadmin/admin' || uri_string() === 'superadmin/lamaran') ? 'show here' : '' ?>">
										<span class="menu-link">
											<span class="menu-icon">
												<i class="ki-duotone ki-profile-user fs-2">
													<span class="path1"></span>
													<span class="path2"></span>
													<span class="path3"></span>
													<span class="path4"></span>
												</i>
											</span>
											<span class="menu-title">Manajemen Pengguna</span>
											<span class="menu-arrow"></span>
										</span>
										<div class="menu-sub menu-sub-accordion">
											<div class="menu-item">
												<a class="menu-link <?= (uri_string() === 'superadmin/pelamar') ? 'active' : '' ?>"
													href="<?= base_url('superadmin/pelamar') ?>">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
													<span class="menu-title">Pelamar</span>
												</a>
											</div>
											<div class="menu-item">
												<a class="menu-link <?= (uri_string() === 'superadmin/lamaran') ? 'active' : '' ?>"
													href="<?= base_url('superadmin/lamaran') ?>">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
													<span class="menu-title">Data Lamaran</span>
												</a>
											</div>
											<div class="menu-item">
												<a class="menu-link <?= (uri_string() === 'superadmin/admin') ? 'active' : '' ?>"
													href="<?= base_url('superadmin/admin') ?>">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
													<span class="menu-title">Admin</span>
												</a>
											</div>
										</div>
									</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
					<!--end::sidebar menu-->
					<!--begin::Footer-->
					<div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
						<a href="<?= base_url('logout') ?>"
							class="btn btn-flex flex-center btn-custom btn-danger overflow-hidden text-nowrap px-0 h-40px w-100 js-logout-trigger"
							data-logout-url="<?= base_url('logout') ?>"
							data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss-="click"
							title="Keluar dari sistem">
							<span class="btn-label">Logout</span>
							<i class="ki-duotone ki-exit-right btn-icon fs-2 m-0">
								<span class="path1"></span>
								<span class="path2"></span>
							</i>
						</a>
					</div>
					<!--end::Footer-->
				</div>
				<!--end::Sidebar-->
				<!--begin::Main-->
				<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
					<!--begin::Content wrapper-->
					<div class="d-flex flex-column flex-column-fluid">
						
