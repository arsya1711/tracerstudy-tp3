<?php
/*
|-------------------------------------------------------------------
| VIEW REGISTER ALUMNI
|-------------------------------------------------------------------
| Form pendaftaran mandiri untuk alumni. Akun baru
| dibuat dengan status menunggu aktivasi agar bisa direview admin sekolah.
*/
?>
<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<?php
$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];
$jenisAlumniTerpilih = 'alumni';
$daftar_angkatan = is_array($daftar_angkatan ?? null) ? $daftar_angkatan : [];
$daftar_kompetensi = is_array($daftar_kompetensi ?? null) ? $daftar_kompetensi : [];
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
        <h1 class="text-dark fw-bolder mb-3">Daftar Alumni</h1>
        <div class="text-gray-500 fw-semibold fs-6">Buat akun untuk mengisi tracer study</div>
    </div>

    <div class="alert alert-warning d-flex align-items-start p-5 mb-8">
        <i class="ki-duotone ki-information-5 fs-2hx text-warning me-4">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
        <div class="text-gray-700 fs-7">
            Setelah daftar, kamu bisa login. Menu profil dan tracer akan terbuka setelah admin sekolah menyetujui akun kamu.
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
        <label class="form-label fw-semibold text-gray-700">Jenis akun</label>
        <input type="hidden" name="jenis_alumni" id="kt_register_jenis_alumni" value="alumni">
        <input type="text" class="form-control bg-transparent" value="Alumni" disabled>
        <?php if (isset($errors['jenis_alumni'])): ?>
            <div class="text-danger fs-8 mt-2"><?= esc($errors['jenis_alumni']) ?></div>
        <?php endif; ?>
    </div>

    <?php
    /*
    |-------------------------------------------------------------------
    | FIELD AKADEMIK KHUSUS ALUMNI
    |-------------------------------------------------------------------
    | Blok ini menampilkan data akademik alumni. Data yang dikirim akan
    | disimpan ke tb_alumni setelah akun dan profil alumni dibuat.
    |
    | Tips Debugging:
    | - Jika blok tidak muncul, cek script toggle di bagian bawah view ini.
    | - Jika dropdown kosong, cek data aktif pada tb_angkatan dan tb_kompetensi.
    */
    ?>
    <div id="kt_register_alumni_fields" class="<?= $jenisAlumniTerpilih === 'alumni' ? '' : 'd-none' ?>">
        <div class="separator separator-dashed my-8"></div>
        <div class="text-gray-800 fw-bold fs-6 mb-2">Data Akademik Alumni</div>
        <div class="text-gray-500 fs-8 mb-6">Isi data ini agar akun alumni langsung terhubung dengan data sekolah.</div>

        <div class="fv-row mb-8">
            <input type="text" name="nis" placeholder="NIS" autocomplete="off" class="form-control bg-transparent" value="<?= esc(old('nis')) ?>" />
            <?php if (isset($errors['nis'])): ?>
                <div class="text-danger fs-8 mt-2"><?= esc($errors['nis']) ?></div>
            <?php endif; ?>
        </div>

        <div class="fv-row mb-8">
            <select name="id_kompetensi" class="form-select bg-transparent">
                <option value="">Pilih Kompetensi Keahlian</option>
                <?php foreach ($daftar_kompetensi as $kompetensi): ?>
                    <?php $idKompetensi = (string) ($kompetensi['id_kompetensi'] ?? ''); ?>
                    <option value="<?= esc($idKompetensi, 'attr') ?>" <?= (string) old('id_kompetensi') === $idKompetensi ? 'selected' : '' ?>>
                        <?= esc((string) ($kompetensi['nama_kompetensi'] ?? '-')) ?>
                        <?php if (! empty($kompetensi['akronim'])): ?>
                            (<?= esc((string) $kompetensi['akronim']) ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['id_kompetensi'])): ?>
                <div class="text-danger fs-8 mt-2"><?= esc($errors['id_kompetensi']) ?></div>
            <?php endif; ?>
        </div>

        <div class="fv-row mb-8">
            <select name="id_angkatan" class="form-select bg-transparent">
                <option value="">Pilih Tahun Lulus</option>
                <?php foreach ($daftar_angkatan as $angkatan): ?>
                    <?php $idAngkatan = (string) ($angkatan['id_angkatan'] ?? ''); ?>
                    <option value="<?= esc($idAngkatan, 'attr') ?>" <?= (string) old('id_angkatan') === $idAngkatan ? 'selected' : '' ?>>
                        <?= esc((string) ($angkatan['tahun_lulus'] ?? '-')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['id_angkatan'])): ?>
                <div class="text-danger fs-8 mt-2"><?= esc($errors['id_angkatan']) ?></div>
            <?php endif; ?>
        </div>

        <div class="separator separator-dashed my-8"></div>
        <div class="text-gray-800 fw-bold fs-6 mb-2">Data Pribadi Alumni</div>
        <div class="text-gray-500 fs-8 mb-6">Data ini wajib diisi dan akan tampil pada profil alumni.</div>

        <div class="fv-row mb-8">
            <select name="jenis_kelamin" class="form-select bg-transparent">
                <option value="">Pilih Jenis Kelamin</option>
                <option value="Laki-laki" <?= (string) old('jenis_kelamin') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                <option value="Perempuan" <?= (string) old('jenis_kelamin') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
            </select>
            <?php if (isset($errors['jenis_kelamin'])): ?>
                <div class="text-danger fs-8 mt-2"><?= esc($errors['jenis_kelamin']) ?></div>
            <?php endif; ?>
        </div>

        <div class="fv-row mb-8">
            <input type="text" name="tempat_lahir" placeholder="Tempat lahir" autocomplete="off" class="form-control bg-transparent" value="<?= esc(old('tempat_lahir')) ?>" />
            <?php if (isset($errors['tempat_lahir'])): ?>
                <div class="text-danger fs-8 mt-2"><?= esc($errors['tempat_lahir']) ?></div>
            <?php endif; ?>
        </div>

        <div class="fv-row mb-8">
            <input type="date" name="tanggal_lahir" class="form-control bg-transparent" value="<?= esc(old('tanggal_lahir')) ?>" />
            <?php if (isset($errors['tanggal_lahir'])): ?>
                <div class="text-danger fs-8 mt-2"><?= esc($errors['tanggal_lahir']) ?></div>
            <?php endif; ?>
        </div>

        <div class="fv-row mb-8">
            <textarea name="alamat" rows="3" placeholder="Alamat lengkap" class="form-control bg-transparent"><?= esc(old('alamat')) ?></textarea>
            <?php if (isset($errors['alamat'])): ?>
                <div class="text-danger fs-8 mt-2"><?= esc($errors['alamat']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="fv-row mb-8">
        <div class="input-group">
            <input type="password" name="password" placeholder="Password" autocomplete="new-password" class="form-control bg-transparent" data-password-input />
            <button type="button" class="btn btn-light" data-password-toggle>Lihat</button>
        </div>
        <?php if (isset($errors['password'])): ?>
            <div class="text-danger fs-8 mt-2"><?= esc($errors['password']) ?></div>
        <?php endif; ?>
    </div>

    <div class="fv-row mb-8">
        <div class="input-group">
            <input type="password" name="password_confirmation" placeholder="Konfirmasi password" autocomplete="new-password" class="form-control bg-transparent" data-password-input />
            <button type="button" class="btn btn-light" data-password-toggle>Lihat</button>
        </div>
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

<script>
    /*
    |-------------------------------------------------------------------
    | TOGGLE FORM AKADEMIK ALUMNI
    |-------------------------------------------------------------------
    | Script ini mengatur agar field NIS, kompetensi keahlian, dan tahun
    | lulus hanya aktif untuk akun Alumni.
    | Alur kerja: saat halaman dimuat dan saat dropdown berubah, blok
    | alumni akan ditampilkan/disembunyikan serta inputnya diaktifkan.
    |
    | Tips Debugging:
    | - Jika field tetap tersembunyi, cek id kt_register_jenis_alumni.
    | - Jika data tidak terkirim, pastikan input alumni tidak disabled saat Alumni dipilih.
    */
    document.addEventListener('DOMContentLoaded', function () {
        const jenisAlumni = document.getElementById('kt_register_jenis_alumni');
        const alumniFields = document.getElementById('kt_register_alumni_fields');

        if (!jenisAlumni || !alumniFields) {
            return;
        }

        const toggleAlumniFields = function () {
            const isAlumni = jenisAlumni.value === 'alumni';

            alumniFields.classList.toggle('d-none', !isAlumni);
            alumniFields.querySelectorAll('input, select, textarea').forEach(function (input) {
                input.disabled = !isAlumni;
            });
        };

        jenisAlumni.addEventListener('change', toggleAlumniFields);
        toggleAlumniFields();
    });
</script>
<?= $this->endSection() ?>
