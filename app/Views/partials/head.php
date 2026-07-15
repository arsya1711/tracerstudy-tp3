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
<html lang="id">
<!--begin::Head-->
<head>
	<base href="../../" />
	<title><?= esc($title ?? 'Dashboard - Sistem Tracer Study') ?></title>
	<meta charset="utf-8" />
	<meta name="description"
		content="Dashboard Sistem Informasi Tracer Study Alumni SMK Teratai Putih 3." />
	<meta name="keywords"
		content="tracer study, alumni, dashboard, SMK Teratai Putih 3" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="theme-color" content="#071a33" />
	<meta property="og:locale" content="id_ID" />
	<meta property="og:type" content="article" />
	<meta property="og:title" content="Dashboard Sistem Tracer Study" />
	<meta property="og:url" content="<?= current_url() ?>" />
	<meta property="og:site_name" content="Sistem Tracer Study" />
	<link rel="canonical" href="<?= current_url() ?>" />
	<link rel="icon" type="image/svg+xml" href="<?= base_url('assets/media/logos/logo-smk-teratai-putih-3.svg') ?>" />
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
	<link href="<?= base_url('assets/css/custom/dashboard-responsive.css') ?>" rel="stylesheet" type="text/css" />
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
