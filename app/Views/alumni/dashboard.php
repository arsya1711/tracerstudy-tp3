<?php
/*
|-------------------------------------------------------------------
| VIEW DASHBOARD PELAMAR
|-------------------------------------------------------------------
| View ini menjadi halaman awal setelah alumni login. Dashboard tidak
| hanya menampilkan angka ringkasan, tetapi juga memandu alumni lewat
| checklist onboarding sampai data tracer alumni lengkap.
|
| Alur kerja:
| 1. Controller mengirim data alumni dan checklist onboarding.
| 2. View menampilkan status akun dan langkah berikutnya yang paling
|    relevan ketika data belum lengkap.
| 3. Checklist hanya menampilkan data yang masih perlu dilengkapi.
|
| Tips Debugging:
| - Jika checklist kosong, periksa payload onboarding dari
|   Alumni\DashboardController::index().
*/
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('extra_css') ?>
<style>
    .tracer-onboarding-hero {
        background:
            radial-gradient(circle at top right, rgba(80, 205, 137, .18), transparent 34%),
            linear-gradient(135deg, #071a33 0%, #102a4c 54%, #0f4c81 100%);
        border-radius: 1.25rem;
        overflow: hidden;
    }

    .tracer-onboarding-step {
        border: 1px solid var(--bs-gray-200);
        border-radius: 1rem;
        padding: 1.25rem;
        transition: .18s ease;
    }

    .tracer-onboarding-step:hover {
        border-color: var(--bs-primary);
        box-shadow: 0 12px 32px rgba(15, 23, 42, .08);
    }

    .tracer-onboarding-step.is-done {
        background: linear-gradient(135deg, rgba(80, 205, 137, .12), rgba(255, 255, 255, .96));
    }

    .tracer-onboarding-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 46px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
/*
|-------------------------------------------------------------------
| DATA STATUS DASHBOARD
|-------------------------------------------------------------------
| Blok ini menyiapkan variabel tampilan agar HTML di bawah tetap rapi.
| Data utama sudah dihitung di controller, sementara view hanya memilih
| label, warna badge, dan tombol yang perlu ditampilkan.
|
| Tips Debugging:
| - Jika status pendaftaran salah tampil, cek nilai
|   tb_alumni.status_pendaftaran.
| - Jika tombol utama tidak sesuai, cek array onboarding.next_step.
*/
$statusPendaftaran = (string) ($alumni['status_pendaftaran'] ?? '');
$labelStatusPendaftaran = $statusPendaftaran !== '' ? ucwords(str_replace('_', ' ', $statusPendaftaran)) : 'Belum Diketahui';
$kelasStatusPendaftaran = 'badge badge-light-success';
$isAlumni = (bool) ($isAlumni ?? false);
$onboarding = $onboarding ?? ['steps' => [], 'next_step' => null, 'ready' => false, 'progress' => ['total' => 0, 'selesai' => 0, 'persen' => 0]];
$nextStep = $onboarding['next_step'] ?? null;
$siapMelamar = (bool) ($onboarding['ready'] ?? false);
$langkahBelumSelesai = array_values(array_filter(
    $onboarding['steps'] ?? [],
    static fn (array $step): bool => empty($step['done'])
));
$tracerTerakhir = is_array($tracerTerakhir ?? null) ? $tracerTerakhir : [];
$legalisirTerbaru = is_array($legalisirTerbaru ?? null) ? $legalisirTerbaru : [];
$teks = static function (mixed $value, string $empty = '-'): string {
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? $value : $empty;
};

/*
| Status legalisir terbaru ditampilkan sebagai alert di dashboard alumni.
| Tujuannya agar alumni langsung melihat apakah pengajuan masih diajukan,
| sedang diproses, selesai, atau ditolak beserta catatan adminnya.
*/
$statusLegalisirOptions = [
    'diajukan' => 'Diajukan',
    'diproses' => 'Diproses',
    'selesai' => 'Selesai',
    'ditolak' => 'Ditolak',
];
$legalisirStatus = (string) ($legalisirTerbaru['status'] ?? '');
$legalisirAlertClass = match ($legalisirStatus) {
    'diproses' => 'alert-primary',
    'selesai' => 'alert-success',
    'ditolak' => 'alert-danger',
    'diajukan' => 'alert-warning',
    default => 'alert-info',
};
$legalisirIconClass = match ($legalisirStatus) {
    'diproses' => 'text-primary',
    'selesai' => 'text-success',
    'ditolak' => 'text-danger',
    default => 'text-warning',
};
$punyaProfilKuliah = $tracerTerakhir !== [] && (
    trim((string) ($tracerTerakhir['universitas'] ?? '')) !== ''
    || trim((string) ($tracerTerakhir['program_studi'] ?? '')) !== ''
    || trim((string) ($tracerTerakhir['status_kuliah'] ?? '')) !== ''
    || str_contains(strtolower((string) ($tracerTerakhir['nama_aktivitas'] ?? '')), 'kuliah')
    || str_contains(strtolower((string) ($tracerTerakhir['nama_aktivitas'] ?? '')), 'studi')
);

$statusStep = static function (array $step): array {
    if (! empty($step['done'])) {
        return [
            'badge' => 'badge badge-light-success',
            'label' => 'Selesai',
            'iconBg' => 'bg-light-success',
            'iconText' => 'text-success',
            'icon' => 'ki-check-circle',
        ];
    }

    return [
        'badge' => 'badge badge-light-primary',
        'label' => 'Perlu dilengkapi',
        'iconBg' => 'bg-light-primary',
        'iconText' => 'text-primary',
        'icon' => 'ki-pencil',
    ];
};
?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Dashboard Alumni</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= base_url('alumni/dashboard') ?>" class="text-muted text-hover-primary">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Dashboard</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <div class="card tracer-onboarding-hero mb-8">
            <div class="card-body p-8 p-lg-10">
                <div class="row align-items-center g-8">
                    <div class="<?= $siapMelamar ? 'col-lg-12' : 'col-lg-8' ?>">
                        <span class="badge badge-light-success mb-4">Alumni</span>
                        <h1 class="text-white fw-bold mb-3">Selamat datang, <?= esc((string) ($alumni['nama_lengkap'] ?? 'Alumni')) ?></h1>
                        <p class="text-white opacity-75 fs-5 mb-0">
                            <?php if ($siapMelamar): ?>
                                Semua langkah utama sudah selesai. Data tracer alumni kamu sudah lengkap.
                            <?php else: ?>
                                Ikuti checklist di bawah ini agar profil dan tracer study kamu rapi dari awal.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if (! $siapMelamar): ?>
                    <div class="col-lg-4">
                        <div class="bg-white bg-opacity-10 rounded-4 p-6">
                            <?php if (! empty($nextStep['url'])): ?>
                                <div class="text-white fw-semibold mb-2">Langkah Berikutnya</div>
                                <div class="text-white opacity-75 fs-7 mb-5"><?= esc((string) ($nextStep['title'] ?? 'Lengkapi data')) ?></div>
                                <a href="<?= esc((string) $nextStep['url']) ?>" class="btn btn-success w-100">
                                    <?= esc((string) ($nextStep['button'] ?? 'Lanjutkan')) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($legalisirTerbaru !== []): ?>
            <div class="alert <?= esc($legalisirAlertClass) ?> d-flex align-items-start gap-3 mb-8">
                <i class="ki-duotone ki-information-5 fs-2hx <?= esc($legalisirIconClass) ?>">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <div class="fw-bold">Status pengajuan legalisir terbaru: <?= esc($statusLegalisirOptions[$legalisirStatus] ?? ucfirst($legalisirStatus)) ?></div>
                        <a href="<?= site_url('alumni/legalisir') ?>" class="btn btn-sm btn-light">Lihat Riwayat</a>
                    </div>
                    <div class="text-gray-700 fs-6">
                        <?= esc($teks($legalisirTerbaru['jenis_dokumen'] ?? null, 'Dokumen')) ?>
                        <?= ! empty($legalisirTerbaru['jumlah_lembar']) ? '- ' . (int) $legalisirTerbaru['jumlah_lembar'] . ' lembar' : '' ?>
                    </div>
                    <?php if (trim((string) ($legalisirTerbaru['catatan_admin'] ?? '')) !== ''): ?>
                        <div class="mt-3 p-3 bg-white bg-opacity-75 rounded border border-gray-300">
                            <div class="fw-bold fs-7 mb-1">Catatan admin</div>
                            <div class="text-gray-700"><?= esc((string) $legalisirTerbaru['catatan_admin']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-5 g-xl-8 mb-8">
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Status Akun</div>
                        <div class="mb-2"><span class="<?= $kelasStatusPendaftaran ?> fs-7"><?= esc($labelStatusPendaftaran) ?></span></div>
                        <div class="text-gray-600 fs-7">Akun alumni aktif dan bisa mengakses fitur utama.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Profil Akademik</div>
                        <div class="fs-4 fw-bold text-gray-900 mb-2"><?= ! empty($alumni['nis']) ? 'Terisi' : 'Belum Terisi' ?></div>
                        <div class="text-gray-600 fs-7">Data NIS, angkatan, dan kompetensi alumni.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Tracer Study</div>
                        <div class="fs-4 fw-bold text-gray-900 mb-2">
                            <?php if ($isAlumni): ?>
                                <?= ! empty($tracerTerakhir) ? 'Sudah Diisi' : 'Belum Diisi' ?>
                            <?php else: ?>
                                Umum
                            <?php endif; ?>
                        </div>
                        <div class="text-gray-600 fs-7"><?= $isAlumni ? 'Status pengisian tracer alumni.' : 'Tidak memerlukan pengisian tracer alumni.' ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 text-uppercase fw-bold mb-2">Status Data</div>
                        <div class="fs-4 fw-bold text-gray-900 mb-2"><?= ! empty($tracerTerakhir) ? 'Lengkap' : 'Berproses' ?></div>
                        <div class="text-gray-600 fs-7">Ringkasan kesiapan data tracer.</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($punyaProfilKuliah): ?>
            <div class="card card-flush mb-8">
                <div class="card-header pt-7">
                    <div class="card-title flex-column">
                        <h3 class="fw-bolder mb-1">Profil Kuliah</h3>
                        <div class="text-muted fw-semibold fs-7">Ringkasan pendidikan lanjutan berdasarkan data tracer kamu.</div>
                    </div>
                    <div class="card-toolbar">
                        <a href="<?= site_url('alumni/tracer') ?>" class="btn btn-sm btn-light-primary">Kelola Tracer</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Perguruan Tinggi</div>
                            <div class="fs-5 fw-bold text-gray-900"><?= esc($teks($tracerTerakhir['universitas'] ?? null)) ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Program Studi</div>
                            <div class="fs-5 fw-bold text-gray-900"><?= esc($teks($tracerTerakhir['program_studi'] ?? null)) ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Status Kuliah</div>
                            <div><span class="badge badge-light-info fs-7"><?= esc($teks($tracerTerakhir['status_kuliah'] ?? null, 'Kuliah')) ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (! $siapMelamar && $langkahBelumSelesai !== []): ?>
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column">
                    <h2 class="mb-1">Data Yang Belum Lengkap</h2>
                    <div class="text-muted fw-semibold fs-6">Lengkapi daftar berikut agar data tracer kamu selesai.</div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="timeline timeline-border-dashed">
                    <?php foreach ($langkahBelumSelesai as $step): ?>
                        <?php
                        $stepStatus = $statusStep($step);
                        $bolehKlik = empty($step['done']) && ! empty($step['url']);
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-line"></div>
                            <div class="timeline-icon">
                                <div class="tracer-onboarding-icon <?= esc($stepStatus['iconBg']) ?>">
                                    <i class="ki-duotone <?= esc($stepStatus['icon']) ?> fs-2 <?= esc($stepStatus['iconText']) ?>">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </div>
                            </div>
                            <div class="timeline-content mb-7 mt-n1">
                                <div class="tracer-onboarding-step <?= ! empty($step['done']) ? 'is-done' : '' ?>">
                                    <div class="d-flex flex-column flex-md-row justify-content-between gap-4">
                                        <div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                <div class="fs-5 fw-bold text-gray-900"><?= esc((string) ($step['title'] ?? '-')) ?></div>
                                                <span class="<?= esc($stepStatus['badge']) ?>"><?= esc($stepStatus['label']) ?></span>
                                            </div>
                                            <div class="text-gray-600 fs-7"><?= esc((string) ($step['description'] ?? '')) ?></div>
                                        </div>
                                        <?php if ($bolehKlik): ?>
                                            <div class="d-flex align-items-center">
                                                <a href="<?= esc((string) $step['url']) ?>" class="btn btn-sm btn-light-primary text-nowrap">
                                                    <?= esc((string) ($step['button'] ?? 'Lanjutkan')) ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
