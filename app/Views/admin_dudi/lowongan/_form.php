<?php
/*
|-------------------------------------------------------------------
| PARTIAL FORM LOWONGAN ADMIN DUDI
|-------------------------------------------------------------------
| Partial ini dipakai ulang oleh modal tambah dan edit lowongan milik
| Admin DUDI agar struktur field tetap konsisten.
|
| Alur kerja:
| 1. View induk mengirim mode, data item, dan opsi dropdown.
| 2. Partial mengisi value lama jika mode edit.
| 3. Controller tetap memaksa id_perusahaan dari session, bukan form.
|
| Tips Debugging:
| - Jika value edit kosong, cek array item dari LowonganModel.
| - Jika upload flyer tidak berubah, cek enctype form pada view induk.
*/

$item = is_array($item ?? null) ? $item : [];
$mode = (string) ($mode ?? 'tambah');
$selected = static function (string $current, ?string $value): string {
    return $current === (string) $value ? 'selected' : '';
};
$flyerUrl = ! empty($item['flyer_lowongan'] ?? null) ? base_url((string) $item['flyer_lowongan']) : (string) $blankFlyerUrl;
?>

<div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5 mb-7">
    <div class="fw-semibold text-gray-700 fs-7">
        Lowongan ini otomatis terhubung ke <strong><?= esc((string) ($perusahaan['nama_perusahaan'] ?? 'perusahaan Anda')) ?></strong>.
        Admin DUDI tidak perlu memilih DUDI agar data tidak tertukar dengan perusahaan lain.
    </div>
</div>

<div class="row g-7">
    <div class="col-lg-4">
        <div class="fv-row">
            <label class="d-block fw-semibold fs-6 mb-5">Flyer Lowongan</label>
            <div class="image-input image-input-outline image-input-placeholder" data-kt-image-input="true">
                <div class="image-input-wrapper w-150px h-150px" style="background-image: url('<?= esc($flyerUrl) ?>');"></div>
                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Ganti flyer">
                    <i class="ki-duotone ki-pencil fs-7">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="file" name="flyer_lowongan" accept=".png, .jpg, .jpeg" />
                    <input type="hidden" name="flyer_remove" />
                </label>
                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Batalkan">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </span>
                <?php if ($mode === 'edit' && ! empty($item['flyer_lowongan'])): ?>
                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow js-lowongan-remove-flyer" data-bs-toggle="tooltip" title="Hapus flyer">
                        <i class="ki-duotone ki-cross fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </span>
                <?php endif; ?>
            </div>
            <div class="form-text">Format jpg, jpeg, png. Maksimal 4 MB.</div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row">
            <div class="col-md-8 fv-row mb-7">
                <label class="required fw-semibold fs-6 mb-2">Judul Lowongan</label>
                <input type="text" name="judul_lowongan" class="form-control form-control-solid" value="<?= esc((string) ($item['judul_lowongan'] ?? ''), 'attr') ?>" placeholder="Contoh: Lowongan Operator Produksi" required />
            </div>
            <div class="col-md-4 fv-row mb-7">
                <label class="required fw-semibold fs-6 mb-2">Posisi</label>
                <input type="text" name="posisi" class="form-control form-control-solid" value="<?= esc((string) ($item['posisi'] ?? ''), 'attr') ?>" placeholder="Operator Produksi" required />
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 fv-row mb-7">
                <label class="fw-semibold fs-6 mb-2">Jenis Pekerjaan</label>
                <select name="jenis_pekerjaan" class="form-select form-select-solid">
                    <?php foreach ($jenisPekerjaanOptions as $value => $label): ?>
                        <option value="<?= esc($value, 'attr') ?>" <?= $selected($value, $item['jenis_pekerjaan'] ?? 'fulltime') ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 fv-row mb-7">
                <label class="fw-semibold fs-6 mb-2">Sistem Kerja</label>
                <select name="sistem_kerja" class="form-select form-select-solid">
                    <?php foreach ($sistemKerjaOptions as $value => $label): ?>
                        <option value="<?= esc($value, 'attr') ?>" <?= $selected($value, $item['sistem_kerja'] ?? 'onsite') ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 fv-row mb-7">
                <label class="fw-semibold fs-6 mb-2">Status</label>
                <select name="status" class="form-select form-select-solid">
                    <?php foreach ($daftarStatus as $value => $label): ?>
                        <option value="<?= esc($value, 'attr') ?>" <?= $selected($value, $item['status'] ?? 'draft') ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="fv-row mb-7">
    <label class="fw-semibold fs-6 mb-2">Kualifikasi</label>
    <textarea name="kualifikasi" class="form-control form-control-solid" rows="4" placeholder="Tuliskan persyaratan utama pelamar"><?= esc((string) ($item['kualifikasi'] ?? '')) ?></textarea>
</div>

<div class="fv-row mb-7">
    <label class="fw-semibold fs-6 mb-2">Deskripsi Pekerjaan</label>
    <textarea name="deskripsi_pekerjaan" class="form-control form-control-solid" rows="4" placeholder="Tuliskan tugas dan tanggung jawab pekerjaan"><?= esc((string) ($item['deskripsi_pekerjaan'] ?? '')) ?></textarea>
</div>

<div class="row">
    <div class="col-md-4 fv-row mb-7">
        <label class="fw-semibold fs-6 mb-2">Pendidikan Minimum</label>
        <select name="pendidikan_min" class="form-select form-select-solid">
            <option value="">Pilih pendidikan</option>
            <?php foreach ($pendidikanOptions as $value => $label): ?>
                <option value="<?= esc($value, 'attr') ?>" <?= $selected($value, $item['pendidikan_min'] ?? '') ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4 fv-row mb-7">
        <label class="fw-semibold fs-6 mb-2">Jumlah Kebutuhan</label>
        <input type="number" name="jumlah_kebutuhan" class="form-control form-control-solid" min="1" value="<?= esc((string) ($item['jumlah_kebutuhan'] ?? '1'), 'attr') ?>" />
    </div>
    <div class="col-md-4 fv-row mb-7">
        <label class="fw-semibold fs-6 mb-2">Pengalaman Minimum</label>
        <input type="text" name="pengalaman_min" class="form-control form-control-solid" value="<?= esc((string) ($item['pengalaman_min'] ?? ''), 'attr') ?>" placeholder="Contoh: 1 tahun" />
    </div>
</div>

<div class="row">
    <div class="col-md-4 fv-row mb-7">
        <label class="fw-semibold fs-6 mb-2">Rentang Gaji</label>
        <input type="text" name="rentang_gaji" class="form-control form-control-solid" value="<?= esc((string) ($item['rentang_gaji'] ?? ''), 'attr') ?>" placeholder="Contoh: 3 - 5 juta" />
    </div>
    <div class="col-md-4 fv-row mb-7">
        <label class="fw-semibold fs-6 mb-2">Lokasi Kerja</label>
        <input type="text" name="lokasi_kerja" class="form-control form-control-solid" value="<?= esc((string) ($item['lokasi_kerja'] ?? ''), 'attr') ?>" placeholder="Contoh: Bekasi" />
    </div>
    <div class="col-md-4 fv-row mb-7">
        <label class="fw-semibold fs-6 mb-2">Batas Lamaran</label>
        <input type="date" name="batas_lamaran" class="form-control form-control-solid" value="<?= esc((string) ($item['batas_lamaran'] ?? ''), 'attr') ?>" />
    </div>
</div>

<div class="fv-row">
    <label class="fw-semibold fs-6 mb-2">Tayang Hingga</label>
    <input type="datetime-local" name="tayang_hingga" class="form-control form-control-solid" value="<?= esc($datetimeLocal($item['tayang_hingga'] ?? null), 'attr') ?>" />
</div>

<?php if ($mode === 'edit' && ! empty($item['flyer_lowongan'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-lowongan-remove-flyer').forEach(function (button) {
                button.addEventListener('click', function () {
                    var wrapper = button.closest('.image-input');
                    var removeInput = wrapper ? wrapper.querySelector('input[name="flyer_remove"]') : null;
                    var imageWrapper = wrapper ? wrapper.querySelector('.image-input-wrapper') : null;

                    if (removeInput) {
                        removeInput.value = '1';
                    }

                    if (imageWrapper) {
                        imageWrapper.style.backgroundImage = "url('<?= esc((string) $blankFlyerUrl) ?>')";
                    }
                });
            });
        });
    </script>
<?php endif; ?>
