<?php
/*
|-------------------------------------------------------------------
| PARTIAL HEAD
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: partial ini menampung seluruh bagian
| head dashboard Metronic mulai dari doctype sampai penutup head,
| termasuk metadata, asset CSS global, asset vendor, dan token CSRF
| untuk kebutuhan request AJAX.
| Alur kerja: layouts/main memanggil partial ini paling awal agar
| browser memuat metadata, stylesheet, token CSRF, dan slot extra_css
| sebelum body dashboard dirender.
|
| Tips Debugging:
| - Jika CSS dashboard tidak termuat, periksa path base_url('assets/...') pada partial ini.
| - Jika request AJAX gagal CSRF, periksa meta csrf-token dan csrf-header-name di partial ini.
*/
?>
<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->
<head>
	<base href="../../" />
	<title><?= esc($title ?? 'Dashboard - Sistem Tracer Study') ?></title>
	<meta charset="utf-8" />
	<meta name="description"
		content="Dashboard Sistem Tracer Study berbasis Metronic 8.2.0." />
	<meta name="keywords"
		content="metronic, bootstrap, tracer study, dashboard, kompetensi" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta property="og:locale" content="en_US" />
	<meta property="og:type" content="article" />
	<meta property="og:title" content="Dashboard Sistem Tracer Study" />
	<meta property="og:url" content="<?= current_url() ?>" />
	<meta property="og:site_name" content="Sistem Tracer Study" />
	<link rel="canonical" href="<?= current_url() ?>" />
	<link rel="shortcut icon" href="<?= base_url('assets/media/logos/favicon.ico') ?>" />
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
	<link href="<?= base_url('assets/plugins/custom/datatables/datatables.bundle.css') ?>" rel="stylesheet" type="text/css" />
	<link href="<?= base_url('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css" />
	<link href="<?= base_url('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css" />
	<?php
	/*
	|-------------------------------------------------------------------
	| TOASTR CSS DASHBOARD
	|-------------------------------------------------------------------
	| Penjelasan fungsi kode ini: memuat stylesheet Toastr agar notifikasi
	| kecil di dashboard tampil rapi di atas layout Metronic.
	| Alur kerja: browser memuat CSS ini di bagian head sebelum body
	| dirender, sehingga popup Toastr sudah punya styling saat dipanggil
	| oleh script footer.
	|
	| Tips Debugging:
	| - Jika popup muncul tanpa styling, periksa CDN toastr.css bisa diakses.
	| - Jika layout notifikasi tertutup elemen lain, periksa urutan CSS dashboard dan z-index Toastr.
	*/
	?>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
	<style>
		#kt_app_header {
			position: sticky;
			top: 0;
			z-index: 100;
			background: var(--bs-app-header-base-bg-color, #ffffff);
			box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
		}

		.drawer-overlay {
			display: none !important;
			pointer-events: none !important;
		}

		.kt-school-brand {
			display: flex;
			align-items: center;
			gap: 10px;
			min-width: 0;
			text-decoration: none;
		}

		.kt-school-brand-logo {
			width: 42px;
			height: 50px;
			object-fit: contain;
			flex: 0 0 auto;
			background: #ffffff;
			border-radius: 6px;
			padding: 3px;
		}

		.kt-school-brand-title {
			color: #ffffff;
			font-size: 13px;
			font-weight: 800;
			line-height: 1.2;
			letter-spacing: 0;
			white-space: normal;
		}

		.kt-sidebar-close {
			flex: 0 0 auto;
		}

		#kt_app_sidebar_logo {
			gap: 10px;
			justify-content: space-between;
			min-height: 82px;
		}

		body.kt-sidebar-manual-closed #kt_app_sidebar {
			display: none !important;
		}

		@media (min-width: 992px) {
			:root {
				--kt-app-sidebar-width: 280px;
				--kt-app-sidebar-width-actual: 280px;
			}

			body.kt-sidebar-manual-closed #kt_app_sidebar_mobile_toggle {
				display: flex !important;
			}

			body.kt-sidebar-manual-closed #kt_app_wrapper,
			body.kt-sidebar-manual-closed #kt_app_header,
			body.kt-sidebar-manual-closed #kt_app_toolbar {
				padding-left: 0 !important;
				margin-left: 0 !important;
			}
		}
	</style>
	<script>
		if (window.top != window.self) {
			window.top.location.replace(window.self.location.href);
		}
	</script>
	<meta name="csrf-token" content="<?= csrf_hash() ?>">
	<meta name="csrf-header-name" content="<?= csrf_token() ?>">
	<?= $this->renderSection('extra_css') ?>
</head>
<!--end::Head-->
