<?php
$akun = is_array($akun ?? null) ? $akun : [];
$errorsProfil = (array) (session()->getFlashdata('errors_profil') ?? []);
$errorsPassword = (array) (session()->getFlashdata('errors_password') ?? []);
$nilai = static fn (string $key): string => (string) old($key, $akun[$key] ?? '');
$roleLabel = match ((string) ($akun['slug_peran'] ?? '')) {
    'admin_sekolah' => 'Admin Sekolah',
    'superadmin' => 'Superadmin',
    default => (string) ($akun['nama_peran'] ?? 'Pengguna'),
};
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Profil Akun</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted"><?= esc($roleLabel) ?></li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Profil Akun</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc((string) session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('sukses')): ?>
            <div class="alert alert-success"><?= esc((string) session()->getFlashdata('sukses')) ?></div>
        <?php endif; ?>

        <div class="card card-flush mb-8">
            <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-5">
                <div>
                    <div class="text-muted fw-semibold fs-7 mb-1">Akun <?= esc($roleLabel) ?></div>
                    <h2 class="fw-bolder text-gray-900 mb-2"><?= esc((string) ($akun['nama_lengkap'] ?? 'Pengguna')) ?></h2>
                    <div class="d-flex flex-wrap gap-3 text-gray-600 fs-7">
                        <span><?= esc((string) ($akun['email'] ?? '-')) ?></span>
                        <span><?= esc((string) (($akun['nomor_telepon'] ?? '') !== '' ? $akun['nomor_telepon'] : 'Nomor telepon belum diisi')) ?></span>
                    </div>
                </div>
                <span class="badge badge-light-success fs-7">Akun Aktif</span>
            </div>
        </div>

        <div class="row g-5 g-xl-8 align-items-start">
            <div class="col-xl-7">
                <div class="card card-flush">
                    <div class="card-header pt-7">
                        <div class="card-title flex-column">
                            <h3 class="fw-bolder mb-1">Informasi Profil</h3>
                            <div class="text-muted fw-semibold fs-7">Perbarui nama, email, dan nomor telepon akun.</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?= site_url('profil-akun/update') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-6">
                                <label class="form-label required" for="nama_lengkap">Nama Lengkap</label>
                                <input id="nama_lengkap" type="text" name="nama_lengkap" value="<?= esc($nilai('nama_lengkap'), 'attr') ?>"
                                    class="form-control form-control-solid <?= isset($errorsProfil['nama_lengkap']) ? 'is-invalid' : '' ?>" maxlength="150" required>
                                <?php if (isset($errorsProfil['nama_lengkap'])): ?>
                                    <div class="invalid-feedback"><?= esc((string) $errorsProfil['nama_lengkap']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-6">
                                <label class="form-label required" for="email">Email</label>
                                <input id="email" type="email" name="email" value="<?= esc($nilai('email'), 'attr') ?>"
                                    class="form-control form-control-solid <?= isset($errorsProfil['email']) ? 'is-invalid' : '' ?>" maxlength="150" required>
                                <?php if (isset($errorsProfil['email'])): ?>
                                    <div class="invalid-feedback"><?= esc((string) $errorsProfil['email']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-6">
                                <label class="form-label" for="nomor_telepon">Nomor Telepon</label>
                                <input id="nomor_telepon" type="tel" name="nomor_telepon" value="<?= esc($nilai('nomor_telepon'), 'attr') ?>"
                                    class="form-control form-control-solid <?= isset($errorsProfil['nomor_telepon']) ? 'is-invalid' : '' ?>"
                                    maxlength="30" placeholder="Contoh: 0812 3456 7890">
                                <?php if (isset($errorsProfil['nomor_telepon'])): ?>
                                    <div class="invalid-feedback"><?= esc((string) $errorsProfil['nomor_telepon']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-7">
                                <label class="form-label" for="password_profil">Password Saat Ini</label>
                                <div class="input-group">
                                    <input id="password_profil" type="password" name="password_saat_ini"
                                        class="form-control form-control-solid <?= isset($errorsProfil['password_saat_ini']) ? 'is-invalid' : '' ?>"
                                        autocomplete="current-password" data-password-input>
                                    <button type="button" class="btn btn-light" data-password-toggle>Lihat</button>
                                    <?php if (isset($errorsProfil['password_saat_ini'])): ?>
                                        <div class="invalid-feedback"><?= esc((string) $errorsProfil['password_saat_ini']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">Wajib diisi hanya jika kamu mengubah alamat email.</div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card card-flush">
                    <div class="card-header pt-7">
                        <div class="card-title flex-column">
                            <h3 class="fw-bolder mb-1">Ubah Password</h3>
                            <div class="text-muted fw-semibold fs-7">Gunakan minimal 8 karakter.</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?= site_url('profil-akun/update-password') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-5">
                                <label class="form-label required" for="password_saat_ini">Password Saat Ini</label>
                                <div class="input-group">
                                    <input id="password_saat_ini" type="password" name="password_saat_ini"
                                        class="form-control form-control-solid <?= isset($errorsPassword['password_saat_ini']) ? 'is-invalid' : '' ?>"
                                        autocomplete="current-password" required data-password-input>
                                    <button type="button" class="btn btn-light" data-password-toggle>Lihat</button>
                                    <?php if (isset($errorsPassword['password_saat_ini'])): ?>
                                        <div class="invalid-feedback"><?= esc((string) $errorsPassword['password_saat_ini']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="form-label required" for="password_baru">Password Baru</label>
                                <div class="input-group">
                                    <input id="password_baru" type="password" name="password_baru"
                                        class="form-control form-control-solid <?= isset($errorsPassword['password_baru']) ? 'is-invalid' : '' ?>"
                                        autocomplete="new-password" minlength="8" maxlength="72" required data-password-input>
                                    <button type="button" class="btn btn-light" data-password-toggle>Lihat</button>
                                    <?php if (isset($errorsPassword['password_baru'])): ?>
                                        <div class="invalid-feedback"><?= esc((string) $errorsPassword['password_baru']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-7">
                                <label class="form-label required" for="konfirmasi_password">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input id="konfirmasi_password" type="password" name="konfirmasi_password"
                                        class="form-control form-control-solid <?= isset($errorsPassword['konfirmasi_password']) ? 'is-invalid' : '' ?>"
                                        autocomplete="new-password" minlength="8" maxlength="72" required data-password-input>
                                    <button type="button" class="btn btn-light" data-password-toggle>Lihat</button>
                                    <?php if (isset($errorsPassword['konfirmasi_password'])): ?>
                                        <div class="invalid-feedback"><?= esc((string) $errorsPassword['konfirmasi_password']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-light-danger w-100">Perbarui Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
