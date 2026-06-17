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

    <div class="text-center mb-11">
        <h1 class="text-dark fw-bolder mb-3">Sign In</h1>
        <div class="text-gray-500 fw-semibold fs-6">Login untuk mengakses dashboard sistem</div>
    </div>

    <div class="text-center mb-8">
        <span class="text-gray-500 fw-semibold fs-6">Belum punya akun?</span>
        <a href="<?= base_url('daftar') ?>" class="link-primary fw-bold">Daftar sebagai alumni</a>
    </div>

    <div class="separator separator-content my-14">
        <span class="w-125px text-gray-500 fw-semibold fs-7">Or with email</span>
    </div>

    <div class="fv-row mb-8">
        <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control bg-transparent" value="<?= esc(old('email')) ?>" />
    </div>

    <div class="fv-row mb-3">
        <div class="input-group">
            <input type="password" placeholder="Password" name="password" autocomplete="off" class="form-control bg-transparent" data-password-input />
            <button type="button" class="btn btn-light" data-password-toggle>Lihat</button>
        </div>
    </div>

    <div class="d-grid mb-10">
        <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
            <span class="indicator-label">Sign In</span>
            <span class="indicator-progress">Please wait...
                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
            </span>
        </button>
    </div>
</form>
<?= $this->endSection() ?>
