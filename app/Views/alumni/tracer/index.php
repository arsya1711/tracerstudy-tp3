<?php
$tracer = is_array($tracer ?? null) ? $tracer : [];
$daftarAktivitas = is_array($daftarAktivitas ?? null) ? $daftarAktivitas : [];
$penghasilanOptions = is_array($penghasilanOptions ?? null) ? $penghasilanOptions : [];
$nilai = static function (string $key, string $default = '') use ($tracer): string {
    return (string) old($key, $tracer[$key] ?? $default);
};
$selected = static function (string $key, string $value) use ($tracer): string {
    return (string) old($key, $tracer[$key] ?? '') === $value ? 'selected' : '';
};
$checked = static function (string $key, string $value) use ($tracer): string {
    return (string) old($key, $tracer[$key] ?? '') === $value ? 'checked' : '';
};
$tampil = static function (mixed $value, string $empty = '-'): string {
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? $value : $empty;
};
$gabung = static function (mixed ...$values): string {
    $parts = array_filter(array_map(static fn (mixed $value): string => trim((string) ($value ?? '')), $values));
    return $parts !== [] ? implode(' - ', $parts) : '-';
};
$kuliahPernah = (string) old(
    'kuliah_pernah',
    (
        trim((string) ($tracer['universitas'] ?? '')) !== ''
        || trim((string) ($tracer['program_studi'] ?? '')) !== ''
        || trim((string) ($tracer['status_kuliah'] ?? '')) !== ''
    ) ? '1' : ($tracer === [] ? '' : '0')
);
$tipeAktivitas = static function (string $nama): string {
    $nama = strtolower($nama);
    if (str_contains($nama, 'belum') || str_contains($nama, 'mencari')) {
        return 'rencana';
    }
    if (str_contains($nama, 'kuliah') || str_contains($nama, 'studi')) {
        return 'kuliah';
    }
    if (str_contains($nama, 'wirausaha') || str_contains($nama, 'usaha')) {
        return 'wirausaha';
    }
    if (str_contains($nama, 'bekerja') || str_contains($nama, 'kerja')) {
        return 'pekerjaan';
    }

    return 'rencana';
};
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Tracer</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Alumni</li>
                <li class="breadcrumb-item text-muted">Tracer</li>
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
            <div class="card-header pt-7">
                <div class="card-title flex-column">
                    <h3 class="fw-bolder mb-1">Data Tracer</h3>
                    <div class="text-muted fw-semibold fs-7">Kelola data tracer kamu dari halaman ini.</div>
                </div>
                <div class="card-toolbar d-flex gap-2">
                    <?php if ($tracer === []): ?>
                        <button type="button" class="btn btn-primary" data-tracer-form-open>Tambah Tracer</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-light-primary" data-tracer-form-open>Edit Tracer</button>
                        <form method="post" action="<?= site_url('alumni/tracer/hapus') ?>" onsubmit="return confirm('Hapus data tracer kamu?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-light-danger">Hapus</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if ($tracer === []): ?>
                    <div class="text-center text-muted fw-semibold py-8">Belum ada data tracer. Klik tombol Tambah Tracer untuk mengisi data.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-4">
                            <tbody class="fw-semibold text-gray-700">
                                <tr>
                                    <th class="w-225px text-muted">Kuliah</th>
                                    <td><?= esc($gabung($tracer['universitas'] ?? null, $tracer['program_studi'] ?? null)) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Aktivitas</th>
                                    <td><?= esc($tampil($tracer['nama_aktivitas'] ?? null)) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Pekerjaan/Instansi</th>
                                    <td><?= esc($gabung($tracer['posisi_kerja'] ?? null, $tracer['nama_instansi'] ?? null)) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Wirausaha</th>
                                    <td><?= esc($gabung($tracer['nama_usaha'] ?? null, $tracer['bidang_usaha'] ?? null)) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Rencana</th>
                                    <td><?= esc($tampil($tracer['rencana_kedepan'] ?? null)) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <form method="post" action="<?= site_url('alumni/tracer/simpan') ?>" class="<?= $tracer === [] ? '' : 'd-none' ?>" data-tracer-form>
            <?= csrf_field() ?>
            <div class="card card-flush mb-8">
                <div class="card-header pt-7">
                    <div class="card-title flex-column">
                        <h3 class="fw-bolder mb-1"><?= $tracer === [] ? 'Tambah Tracer' : 'Edit Tracer' ?></h3>
                        <div class="text-muted fw-semibold fs-7">Isi riwayat kuliah terlebih dahulu, lalu pilih aktivitas utama kamu saat ini.</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-8">
                        <label class="form-label required">Apakah kamu pernah atau sedang berkuliah?</label>
                        <div class="d-flex flex-wrap gap-6 pt-2">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="kuliah_pernah" value="1" data-kuliah-toggle <?= $kuliahPernah === '1' ? 'checked' : '' ?> required>
                                <span class="form-check-label">Ya, pernah/sedang kuliah</span>
                            </label>
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="kuliah_pernah" value="0" data-kuliah-toggle <?= $kuliahPernah === '0' ? 'checked' : '' ?> required>
                                <span class="form-check-label">Tidak</span>
                            </label>
                        </div>
                    </div>

                    <div class="<?= $kuliahPernah === '1' ? '' : 'd-none' ?>" data-kuliah-fields>
                        <div class="separator mb-7"></div>
                        <div class="row g-5">
                            <div class="col-lg-4">
                                <label class="form-label">Universitas/Perguruan Tinggi</label>
                                <input type="text" name="universitas" value="<?= esc($nilai('universitas'), 'attr') ?>" class="form-control form-control-solid">
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label">Program Studi</label>
                                <input type="text" name="program_studi" value="<?= esc($nilai('program_studi'), 'attr') ?>" class="form-control form-control-solid">
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label">Status Kuliah</label>
                                <select name="status_kuliah" class="form-select form-select-solid">
                                    <option value="">Pilih status</option>
                                    <?php foreach (['Aktif', 'Lulus', 'Cuti', 'Berhenti'] as $statusKuliah): ?>
                                        <option value="<?= esc($statusKuliah, 'attr') ?>" <?= $selected('status_kuliah', $statusKuliah) ?>><?= esc($statusKuliah) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush mb-8">
                <div class="card-header pt-7">
                    <div class="card-title flex-column">
                        <h3 class="fw-bolder mb-1">Aktivitas Utama Setelah Lulus</h3>
                        <div class="text-muted fw-semibold fs-7">Pilih kondisi utama kamu saat ini.</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-5">
                        <div class="col-lg-6">
                            <label class="form-label required">Status Aktivitas</label>
                            <select name="id_aktivitas" class="form-select form-select-solid" data-tracer-activity required>
                                <option value="">Pilih aktivitas</option>
                                <?php foreach ($daftarAktivitas as $aktivitas): ?>
                                    <?php $id = (string) ($aktivitas['id_aktivitas'] ?? ''); ?>
                                    <?php $namaAktivitas = (string) ($aktivitas['nama_aktivitas'] ?? '-'); ?>
                                    <option value="<?= esc($id, 'attr') ?>" data-tracer-type="<?= esc($tipeAktivitas($namaAktivitas), 'attr') ?>" <?= (string) old('id_aktivitas', $tracer['id_aktivitas'] ?? '') === $id ? 'selected' : '' ?>>
                                        <?= esc($namaAktivitas) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-6" data-tracer-section="pekerjaan wirausaha">
                            <label class="form-label">Kesesuaian Kompetensi</label>
                            <div class="d-flex gap-6 pt-3">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="radio" name="relevan_jurusan" value="1" <?= $checked('relevan_jurusan', '1') ?>>
                                    <span class="form-check-label">Relevan</span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="radio" name="relevan_jurusan" value="0" <?= $checked('relevan_jurusan', '0') ?>>
                                    <span class="form-check-label">Tidak Relevan</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-5 g-xl-8 mb-8">
                <div class="col-xl-6" data-tracer-section="pekerjaan">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-7"><h3 class="card-title fw-bolder">Data Pekerjaan</h3></div>
                        <div class="card-body">
                            <div class="mb-5">
                                <label class="form-label">Posisi/Jabatan</label>
                                <input type="text" name="posisi_kerja" value="<?= esc($nilai('posisi_kerja'), 'attr') ?>" class="form-control form-control-solid">
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Nama Instansi/Perusahaan</label>
                                <input type="text" name="nama_instansi" value="<?= esc($nilai('nama_instansi'), 'attr') ?>" class="form-control form-control-solid">
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Bidang Instansi</label>
                                <input type="text" name="bidang_instansi" value="<?= esc($nilai('bidang_instansi'), 'attr') ?>" class="form-control form-control-solid">
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Tahun Mulai Kerja</label>
                                <input type="number" name="tahun_mulai_kerja" min="1990" max="<?= date('Y') ?>" value="<?= esc($nilai('tahun_mulai_kerja'), 'attr') ?>" class="form-control form-control-solid">
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Range Penghasilan</label>
                                <select name="penghasilan_range" class="form-select form-select-solid">
                                    <option value="">Pilih range</option>
                                    <?php foreach ($penghasilanOptions as $range): ?>
                                        <option value="<?= esc((string) $range, 'attr') ?>" <?= $selected('penghasilan_range', (string) $range) ?>><?= esc((string) $range) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Alamat Instansi</label>
                                <textarea name="alamat_instansi" rows="3" class="form-control form-control-solid"><?= esc($nilai('alamat_instansi')) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6" data-tracer-section="wirausaha">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-7"><h3 class="card-title fw-bolder">Data Wirausaha</h3></div>
                        <div class="card-body">
                            <div class="mb-5">
                                <label class="form-label">Nama Usaha</label>
                                <input type="text" name="nama_usaha" value="<?= esc($nilai('nama_usaha'), 'attr') ?>" class="form-control form-control-solid">
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Bidang Usaha</label>
                                <input type="text" name="bidang_usaha" value="<?= esc($nilai('bidang_usaha'), 'attr') ?>" class="form-control form-control-solid">
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Modal Awal</label>
                                <input type="number" name="modal_awal" min="0" value="<?= esc($nilai('modal_awal'), 'attr') ?>" class="form-control form-control-solid">
                            </div>
                            <div>
                                <label class="form-label">Penghasilan Usaha</label>
                                <select name="penghasilan_usaha" class="form-select form-select-solid">
                                    <option value="">Pilih range</option>
                                    <?php foreach ($penghasilanOptions as $range): ?>
                                        <option value="<?= esc((string) $range, 'attr') ?>" <?= $selected('penghasilan_usaha', (string) $range) ?>><?= esc((string) $range) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-5 g-xl-8 mb-8">
                <div class="col-xl-6" data-tracer-section="rencana">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-7"><h3 class="card-title fw-bolder">Rencana Ke Depan</h3></div>
                        <div class="card-body">
                            <label class="form-label">Catatan/Rencana</label>
                            <textarea name="rencana_kedepan" rows="10" class="form-control form-control-solid"><?= esc($nilai('rencana_kedepan')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3">
                <a href="<?= site_url('alumni/dashboard') ?>" class="btn btn-light">Kembali</a>
                <button type="submit" class="btn btn-primary">Simpan Tracer</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var activitySelect = document.querySelector('[data-tracer-activity]');
    var sections = Array.prototype.slice.call(document.querySelectorAll('[data-tracer-section]'));
    var form = document.querySelector('[data-tracer-form]');
    var openButtons = Array.prototype.slice.call(document.querySelectorAll('[data-tracer-form-open]'));
    var kuliahToggles = Array.prototype.slice.call(document.querySelectorAll('[data-kuliah-toggle]'));
    var kuliahFields = document.querySelector('[data-kuliah-fields]');

    function setSectionState(section, isActive) {
        section.classList.toggle('d-none', !isActive);
        section.querySelectorAll('input, select, textarea').forEach(function (field) {
            field.disabled = !isActive;
        });
    }

    function syncTracerSections() {
        var selectedOption = activitySelect ? activitySelect.options[activitySelect.selectedIndex] : null;
        var activeType = selectedOption ? selectedOption.getAttribute('data-tracer-type') : '';

        sections.forEach(function (section) {
            var sectionTypes = (section.getAttribute('data-tracer-section') || '').split(/\s+/);
            setSectionState(section, activeType !== '' && sectionTypes.indexOf(activeType) !== -1);
        });
    }

    if (activitySelect) {
        activitySelect.addEventListener('change', syncTracerSections);
        syncTracerSections();
    }

    function syncKuliahFields() {
        var selected = kuliahToggles.find(function (toggle) {
            return toggle.checked;
        });
        var isKuliah = selected && selected.value === '1';

        if (kuliahFields) {
            kuliahFields.classList.toggle('d-none', !isKuliah);
            kuliahFields.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = !isKuliah;
            });
        }
    }

    kuliahToggles.forEach(function (toggle) {
        toggle.addEventListener('change', syncKuliahFields);
    });
    syncKuliahFields();

    openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (form) {
                form.classList.remove('d-none');
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
