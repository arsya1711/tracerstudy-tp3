<?php
/*
|-------------------------------------------------------------------
| VIEW REGISTER PELAMAR
|-------------------------------------------------------------------
| Form pendaftaran mandiri untuk pelamar umum dan alumni. Akun baru
| dibuat dengan status menunggu aktivasi agar bisa direview admin BKK.
*/
?>
<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<?php
$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];
?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
        <i class="ki-duotone ki-shield-cross fs-2hx text-danger me-4">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        <div class="d-flex flex-column">
            <h4 class="mb-1 text-danger">Pendaftaran Gagal</h4>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    </div>
<?php endif; ?>

<form class="form w-100" action="<?= base_url('daftar') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="text-center mb-11">
        <h1 class="text-dark fw-bolder mb-3">Daftar Pelamar</h1>
        <div class="text-gray-500 fw-semibold fs-6">Buat akun untuk masuk ke sistem BKK</div>
    </div>

    <div class="alert alert-warning d-flex align-items-start p-5 mb-8">
        <i class="ki-duotone ki-information-5 fs-2hx text-warning me-4">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
        <div class="text-gray-700 fs-7">
            Setelah daftar, kamu bisa login. Menu profil, lowongan, dan lamaran akan terbuka setelah admin BKK menyetujui akun kamu.
        </div>
    </div>

    <div class="fv-row mb-8">
        <input type="text" name="nama_lengkap" placeholder="Nama lengkap" autocomplete="name" class="form-control bg-transparent" value="<?= esc(old('nama_lengkap')) ?>" />
        <?php if (isset($errors['nama_lengkap'])): ?>
            <div class="text-danger fs-8 mt-2"><?= esc($errors['nama_lengkap']) ?></div>
        <?php endif; ?>
    </div>

    <div class="fv-row mb-8">
        <input type="email" name="email" placeholder="Email" autocomplete="email" class="form-control bg-transparent" value="<?= esc(old('email')) ?>" />
        <?php if (isset($errors['email'])): ?>
            <div class="text-danger fs-8 mt-2"><?= esc($errors['email']) ?></div>
        <?php endif; ?>
    </div>

    <div class="fv-row mb-8">
        <input type="text" name="nomor_telepon" placeholder="Nomor HP / WhatsApp" autocomplete="tel" class="form-control bg-transparent" value="<?= esc(old('nomor_telepon')) ?>" />
        <?php if (isset($errors['nomor_telepon'])): ?>
            <div class="text-danger fs-8 mt-2"><?= esc($errors['nomor_telepon']) ?></div>
        <?php endif; ?>
    </div>

    <div class="fv-row mb-8">
        <label class="form-label fw-semibold text-gray-700">Jenis pelamar</label>
        <select name="jenis_pelamar" class="form-select bg-transparent">
            <option value="umum" <?= old('jenis_pelamar', 'umum') === 'umum' ? 'selected' : '' ?>>Pelamar Umum</option>
            <option value="alumni" <?= old('jenis_pelamar') === 'alumni' ? 'selected' : '' ?>>Alumni</option>
        </select>
        <?php if (isset($errors['jenis_pelamar'])): ?>
            <div class="text-danger fs-8 mt-2"><?= esc($errors['jenis_pelamar']) ?></div>
        <?php endif; ?>
    </div>

    <div class="fv-row mb-8">
        <input type="password" name="password" placeholder="Password" autocomplete="new-password" class="form-control bg-transparent" />
        <?php if (isset($errors['password'])): ?>
            <div class="text-danger fs-8 mt-2"><?= esc($errors['password']) ?></div>
        <?php endif; ?>
    </div>

    <div class="fv-row mb-8">
        <input type="password" name="password_confirmation" placeholder="Konfirmasi password" autocomplete="new-password" class="form-control bg-transparent" />
        <?php if (isset($errors['password_confirmation'])): ?>
            <div class="text-danger fs-8 mt-2"><?= esc($errors['password_confirmation']) ?></div>
        <?php endif; ?>
    </div>

    <div class="d-grid mb-10">
        <button type="submit" class="btn btn-primary">Daftar</button>
    </div>

    <div class="text-center">
        <span class="text-gray-500 fw-semibold fs-6">Sudah punya akun?</span>
        <a href="<?= base_url('login') ?>" class="link-primary fw-bold">Masuk</a>
    </div>
</form>
<?= $this->endSection() ?>
