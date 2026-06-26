<?php
/*
|-------------------------------------------------------------------
| VIEW LOGIN
|-------------------------------------------------------------------
| Fungsi file ini: menampilkan konten form login Metronic yang akan
| ditempatkan ke dalam layout auth.
| Alur kerja: LoginController::index() memuat view ini, lalu file
| melakukan extend ke layouts/auth dan hanya mengisi section content
| berupa form login ke endpoint POST /login.
|
| Tips Debugging:
| - Jika form submit 404, periksa route POST /login di app/Config/Routes.php.
| - Jika layout auth pecah, periksa view ini hanya berisi form tanpa wrapper halaman penuh.
*/
?>
<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<?php
/*
|-------------------------------------------------------------------
| ALERT ERROR LOGIN
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: menampilkan pesan error autentikasi
| dalam gaya alert Metronic ketika login gagal.
| Alur kerja: controller mengirim flashdata error, lalu view ini
| memeriksa nilainya sebelum form login dirender.
|
| Tips Debugging:
| - Jika pesan gagal login tidak muncul, periksa flashdata 'error' dari LoginController::authenticate().
| - Jika styling alert rusak, periksa CSS bundle Metronic sudah termuat di layout auth.
*/
?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
        <i class="ki-duotone ki-shield-cross fs-2hx text-danger me-4">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        <div class="d-flex flex-column">
            <h4 class="mb-1 text-danger">Login Gagal</h4>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('sukses')): ?>
    <div class="alert alert-success d-flex align-items-center p-5 mb-10">
        <i class="ki-duotone ki-shield-tick fs-2hx text-success me-4">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        <div class="d-flex flex-column">
            <h4 class="mb-1 text-success">Berhasil</h4>
            <span><?= esc(session()->getFlashdata('sukses')) ?></span>
        </div>
    </div>
<?php endif; ?>

<form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" action="<?= base_url('login') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="auth-login-hero text-center mb-9">
        <div class="auth-school-logo mx-auto mb-5">
            <img src="<?= base_url('assets/media/logos/logo-smk-teratai-putih-3.svg') ?>" alt="Logo SMK Teratai Putih 3">
        </div>
        <div class="badge badge-light-primary fw-bold px-4 py-2 mb-4">Sistem Informasi Tracer Study</div>
        <h1 class="text-dark fw-bolder mb-3">Selamat Datang</h1>
        <div class="text-gray-600 fw-semibold fs-6 lh-lg">
            Masuk untuk mengelola data alumni<br>
            <span class="text-primary fw-bold">SMK Teratai Putih 3</span>
        </div>
    </div>

    <div class="auth-register-callout d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 mb-8">
        <span class="text-gray-600 fw-semibold">Belum punya akun alumni?</span>
        <a href="<?= base_url('daftar') ?>" class="btn btn-light-primary btn-sm fw-bold">Daftar Alumni</a>
    </div>

    <div class="separator separator-content my-8">
        <span class="w-150px text-gray-500 fw-semibold fs-7">Masuk dengan email</span>
    </div>

    <div class="fv-row mb-8">
        <label class="form-label fw-bold text-gray-700">Email</label>
        <input type="email" placeholder="nama@email.com" name="email" autocomplete="username" class="form-control form-control-lg form-control-solid auth-input" value="<?= esc(old('email')) ?>" />
    </div>

    <div class="fv-row mb-6">
        <label class="form-label fw-bold text-gray-700">Password</label>
        <div class="input-group">
            <input type="password" placeholder="Masukkan password" name="password" autocomplete="current-password" class="form-control form-control-lg form-control-solid auth-input" data-password-input />
            <button type="button" class="btn btn-light-primary fw-bold px-6" data-password-toggle>Lihat</button>
        </div>
    </div>

    <div class="d-grid mb-2">
        <button type="submit" id="kt_sign_in_submit" class="btn btn-primary btn-lg fw-bold">
            <span class="indicator-label">Masuk</span>
            <span class="indicator-progress">Please wait...
                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
            </span>
        </button>
    </div>
</form>
<?= $this->endSection() ?>
