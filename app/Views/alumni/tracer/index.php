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
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Isi Tracer Study</h1>
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

        <form method="post" action="<?= site_url('alumni/tracer/simpan') ?>">
            <?= csrf_field() ?>
            <div class="card card-flush mb-8">
                <div class="card-header pt-7">
                    <div class="card-title flex-column">
                        <h3 class="fw-bolder mb-1">Aktivitas Setelah Lulus</h3>
                        <div class="text-muted fw-semibold fs-7">Pilih aktivitas utama kamu saat ini.</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-5">
                        <div class="col-lg-6">
                            <label class="form-label required">Status Aktivitas</label>
                            <select name="id_aktivitas" class="form-select form-select-solid" required>
                                <option value="">Pilih aktivitas</option>
                                <?php foreach ($daftarAktivitas as $aktivitas): ?>
                                    <?php $id = (string) ($aktivitas['id_aktivitas'] ?? ''); ?>
                                    <option value="<?= esc($id, 'attr') ?>" <?= (string) old('id_aktivitas', $tracer['id_aktivitas'] ?? '') === $id ? 'selected' : '' ?>>
                                        <?= esc((string) ($aktivitas['nama_aktivitas'] ?? '-')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Kesesuaian Jurusan</label>
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
                <div class="col-xl-6">
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

                <div class="col-xl-6">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-7"><h3 class="card-title fw-bolder">Data Kuliah</h3></div>
                        <div class="card-body">
                            <div class="mb-5">
                                <label class="form-label">Universitas/Perguruan Tinggi</label>
                                <input type="text" name="universitas" value="<?= esc($nilai('universitas'), 'attr') ?>" class="form-control form-control-solid">
                            </div>
                            <div class="mb-5">
                                <label class="form-label">Program Studi</label>
                                <input type="text" name="program_studi" value="<?= esc($nilai('program_studi'), 'attr') ?>" class="form-control form-control-solid">
                            </div>
                            <div>
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

            <div class="row g-5 g-xl-8 mb-8">
                <div class="col-xl-6">
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

                <div class="col-xl-6">
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
                <button type="submit" class="btn btn-primary">Simpan Tracer Study</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
