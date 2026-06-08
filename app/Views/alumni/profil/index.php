<?php
$alumni = is_array($alumni ?? null) ? $alumni : [];
$daftarAngkatan = is_array($daftarAngkatan ?? null) ? $daftarAngkatan : [];
$daftarKompetensi = is_array($daftarKompetensi ?? null) ? $daftarKompetensi : [];
$nilai = static function (string $key, string $default = '') use ($alumni): string {
    return (string) old($key, $alumni[$key] ?? $default);
};
$selected = static function (string $key, string $value) use ($alumni): string {
    return (string) old($key, $alumni[$key] ?? '') === $value ? 'selected' : '';
};
$statusPendaftaran = (string) ($alumni['status_pendaftaran'] ?? '');
$statusLabel = $statusPendaftaran !== '' ? ucwords(str_replace('_', ' ', $statusPendaftaran)) : 'Belum Diketahui';
$statusClass = $statusPendaftaran === 'aktif' ? 'badge-light-success' : 'badge-light-warning';
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Profil Alumni</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Alumni</li>
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
            <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                <div>
                    <div class="text-muted fw-semibold fs-7 mb-1">Akun Alumni</div>
                    <h2 class="fw-bolder text-gray-900 mb-2"><?= esc((string) ($alumni['nama_lengkap'] ?? 'Alumni')) ?></h2>
                    <div class="d-flex flex-wrap gap-3 text-gray-600 fs-7">
                        <span><?= esc((string) ($alumni['email'] ?? '-')) ?></span>
                        <span><?= esc((string) (($alumni['nama_kompetensi'] ?? '') !== '' ? $alumni['nama_kompetensi'] : '-')) ?></span>
                        <span>Angkatan <?= esc((string) (($alumni['tahun_lulus'] ?? '') !== '' ? $alumni['tahun_lulus'] : '-')) ?></span>
                    </div>
                </div>
                <span class="badge <?= $statusClass ?> fs-7"><?= esc($statusLabel) ?></span>
            </div>
        </div>

        <div class="row g-5 g-xl-8">
            <div class="col-xl-8">
                <div class="card card-flush">
                    <div class="card-header pt-7">
                        <h3 class="card-title fw-bolder">Data Diri dan Akademik</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?= site_url('alumni/profil/update/' . (int) ($alumni['id_alumni'] ?? 0)) ?>">
                            <?= csrf_field() ?>
                            <div class="row g-5">
                                <div class="col-lg-6">
                                    <label class="form-label required">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" value="<?= esc($nilai('nama_lengkap'), 'attr') ?>" class="form-control form-control-solid" required>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label">Nomor Telepon</label>
                                    <input type="text" name="nomor_telepon" value="<?= esc($nilai('nomor_telepon'), 'attr') ?>" class="form-control form-control-solid">
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label required">NIS</label>
                                    <input type="text" name="nis" value="<?= esc($nilai('nis'), 'attr') ?>" class="form-control form-control-solid" required>
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label">NISN</label>
                                    <input type="text" name="nisn" value="<?= esc($nilai('nisn'), 'attr') ?>" class="form-control form-control-solid">
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label">No. Ijazah</label>
                                    <input type="text" name="no_ijazah" value="<?= esc($nilai('no_ijazah'), 'attr') ?>" class="form-control form-control-solid">
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label required">Angkatan/Tahun Lulus</label>
                                    <select name="id_angkatan" class="form-select form-select-solid" required>
                                        <option value="">Pilih tahun lulus</option>
                                        <?php foreach ($daftarAngkatan as $angkatan): ?>
                                            <?php $id = (string) ($angkatan['id_angkatan'] ?? ''); ?>
                                            <option value="<?= esc($id, 'attr') ?>" <?= $selected('id_angkatan', $id) ?>>
                                                <?= esc((string) ($angkatan['tahun_lulus'] ?? '-')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label required">Kompetensi Keahlian</label>
                                    <select name="id_kompetensi" class="form-select form-select-solid" required>
                                        <option value="">Pilih kompetensi</option>
                                        <?php foreach ($daftarKompetensi as $kompetensi): ?>
                                            <?php $id = (string) ($kompetensi['id_kompetensi'] ?? ''); ?>
                                            <option value="<?= esc($id, 'attr') ?>" <?= $selected('id_kompetensi', $id) ?>>
                                                <?= esc((string) ($kompetensi['nama_kompetensi'] ?? '-')) ?>
                                                <?php if (! empty($kompetensi['akronim'])): ?>
                                                    (<?= esc((string) $kompetensi['akronim']) ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-select form-select-solid">
                                        <option value="">Pilih jenis kelamin</option>
                                        <option value="Laki-laki" <?= $selected('jenis_kelamin', 'Laki-laki') ?>>Laki-laki</option>
                                        <option value="Perempuan" <?= $selected('jenis_kelamin', 'Perempuan') ?>>Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label">Tempat Lahir</label>
                                    <input type="text" name="tempat_lahir" value="<?= esc($nilai('tempat_lahir'), 'attr') ?>" class="form-control form-control-solid">
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" value="<?= esc($nilai('tanggal_lahir'), 'attr') ?>" class="form-control form-control-solid">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="alamat" rows="4" class="form-control form-control-solid"><?= esc($nilai('alamat')) ?></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-8">
                                <button type="submit" class="btn btn-primary">Simpan Profil</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card card-flush mb-8">
                    <div class="card-header pt-7">
                        <h3 class="card-title fw-bolder">Ubah Email</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?= site_url('alumni/profil/update-email') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-6">
                                <label class="form-label required">Email</label>
                                <input type="email" name="email" value="<?= esc($nilai('email'), 'attr') ?>" class="form-control form-control-solid" required>
                            </div>
                            <button type="submit" class="btn btn-light-primary w-100">Simpan Email</button>
                        </form>
                    </div>
                </div>

                <div class="card card-flush">
                    <div class="card-header pt-7">
                        <h3 class="card-title fw-bolder">Ubah Password</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?= site_url('alumni/profil/update-password') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-5">
                                <label class="form-label required">Password Baru</label>
                                <input type="password" name="password" class="form-control form-control-solid" required minlength="8">
                            </div>
                            <div class="mb-6">
                                <label class="form-label required">Konfirmasi Password</label>
                                <input type="password" name="password_confirmation" class="form-control form-control-solid" required minlength="8">
                            </div>
                            <button type="submit" class="btn btn-light-danger w-100">Simpan Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
