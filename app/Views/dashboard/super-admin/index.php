<?php
/*
|-------------------------------------------------------------------
| VIEW DASHBOARD SUPER ADMIN
|-------------------------------------------------------------------
| Dashboard ini menjadi command center ringkas untuk Super Admin/BKK.
| Tampilan dibuat clean seperti Dashboard Admin DUDI, tetapi tetap
| memuat mini insight tracer dan proses lamaran.
|
| Alur kerja:
| 1. Kartu hero menampilkan fokus utama dan quick action.
| 2. Kartu statistik menampilkan angka penting lintas modul.
| 3. Grafik mini menampilkan tracer alumni tanpa mengambil alih fungsi
|    halaman laporan Data Tracer Alumni.
|
| Tips Debugging:
| - Jika grafik tidak tampil, cek ApexCharts tersedia dari bundle Metronic.
| - Jika data grafik kosong, cek payload tracer_aktivitas dan tracer_angkatan.
*/

$namaAdmin = trim((string) (session('nama_lengkap') ?? 'admin'));
$tanggalHariIni = date('d M Y');
$tracerAktivitas = is_array($tracer_aktivitas ?? null) ? $tracer_aktivitas : ['labels' => [], 'series' => [], 'map' => []];
$tracerAngkatan = is_array($tracer_angkatan ?? null) ? $tracer_angkatan : ['labels' => [], 'series' => []];
$ringkasanLamaran = is_array($ringkasan_lamaran ?? null) ? $ringkasan_lamaran : [];

$formatTanggal = static function (?string $tanggal, bool $pakaiJam = false): string {
    if ($tanggal === null || trim($tanggal) === '') {
        return '-';
    }

    try {
        return (new DateTime($tanggal))->format($pakaiJam ? 'd M Y, H:i' : 'd M Y');
    } catch (Throwable) {
        return (string) $tanggal;
    }
};

$statusBadge = static function (?string $status): array {
    return match ((string) $status) {
        'menunggu_verifikasi'    => ['badge badge-light-warning', 'Menunggu'],
        'perlu_perbaikan_berkas' => ['badge badge-light-danger', 'Perbaikan'],
        'diproses'               => ['badge badge-light-info', 'Diproses'],
        'wawancara'              => ['badge badge-light-primary', 'Wawancara'],
        'diterima'               => ['badge badge-light-success', 'Diterima'],
        'ditolak'                => ['badge badge-light-danger', 'Ditolak'],
        default                  => ['badge badge-light-secondary', '-'],
    };
};

$statCards = [
    [
        'label' => 'Antrean Review',
        'value' => (int) ($antrean_review ?? 0),
        'helper' => 'Akun baru menunggu konfirmasi BKK.',
        'class' => 'text-warning',
        'icon' => 'ki-timer',
        'url' => site_url('superadmin/pelamar'),
        'action' => 'Review',
    ],
    [
        'label' => 'Pelamar Aktif',
        'value' => (int) ($pelamar_aktif ?? 0),
        'helper' => 'Akun pelamar siap menggunakan fitur lanjutan.',
        'class' => 'text-success',
        'icon' => 'ki-profile-user',
        'url' => site_url('superadmin/pelamar'),
        'action' => 'Lihat',
    ],
    [
        'label' => 'Lowongan Aktif',
        'value' => (int) ($total_lowongan ?? 0),
        'helper' => 'Lowongan yang sedang tayang untuk pelamar.',
        'class' => 'text-info',
        'icon' => 'ki-briefcase',
        'url' => site_url('superadmin/lowongan'),
        'action' => 'Kelola',
    ],
    [
        'label' => 'Lamaran Masuk',
        'value' => (int) ($lamaran_masuk ?? 0),
        'helper' => 'Lamaran menunggu verifikasi dokumen.',
        'class' => 'text-primary',
        'icon' => 'ki-document',
        'url' => site_url('superadmin/lamaran'),
        'action' => 'Periksa',
    ],
    [
        'label' => 'Tracer Belum Lengkap',
        'value' => (int) ($tracer_belum_lengkap ?? 0),
        'helper' => 'Alumni belum mengisi atau masih draft tracer.',
        'class' => 'text-danger',
        'icon' => 'ki-notepad-edit',
        'url' => site_url('superadmin/tracer'),
        'action' => 'Pantau',
    ],
];
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('extra_css') ?>
<style>
    .kt-dashboard-hero {
        background:
            radial-gradient(circle at top right, rgba(80, 205, 137, 0.28), transparent 34%),
            linear-gradient(135deg, #0b1739 0%, #101828 56%, #182230 100%);
        border-radius: 1.5rem;
        overflow: hidden;
        position: relative;
    }

    .kt-dashboard-hero::after {
        content: "";
        position: absolute;
        width: 240px;
        height: 240px;
        right: -80px;
        bottom: -120px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    .kt-dashboard-hero > .card-body {
        position: relative;
        z-index: 2;
    }

    .kt-dashboard-mini-stat {
        border: 1px dashed rgba(255, 255, 255, 0.22);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
    }

    .kt-dashboard-mini-stat__label {
        color: rgba(255, 255, 255, 0.72);
    }

    .kt-dashboard-mini-stat__helper {
        color: rgba(255, 255, 255, 0.7);
    }

    .kt-chart-card {
        min-height: 360px;
    }

    .kt-chart-empty {
        min-height: 260px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bs-gray-500);
        font-weight: 600;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Dashboard Super Admin</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">BKK & Tracer Study</li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted"><?= esc($tanggalHariIni) ?></li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <div class="card kt-dashboard-hero mb-8">
            <div class="card-body p-8 p-lg-10">
                <div class="row g-8 align-items-center">
                    <div class="col-xl-7">
                        <span class="badge badge-light-success mb-4">Command Center BKK</span>
                        <h2 class="text-white fw-bolder fs-2hx mb-4">Selamat datang, <?= esc($namaAdmin) ?></h2>
                        <div class="text-gray-300 fs-5 fw-semibold mb-8">
                            Pantau pelamar, DUDI, lowongan, lamaran, dan sinyal tracer dari satu halaman ringkas. Grafik lengkap tetap kita tempatkan di halaman Data Tracer Alumni agar dashboard tidak terlalu padat.
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="<?= site_url('superadmin/pelamar') ?>" class="btn btn-primary fw-bold">Review Pelamar</a>
                            <a href="<?= site_url('superadmin/lamaran') ?>" class="btn btn-light fw-bold">Data Lamaran</a>
                            <a href="<?= site_url('superadmin/lowongan') ?>" class="btn btn-light-success fw-bold">Lowongan</a>
                        </div>
                    </div>
                    <div class="col-xl-5">
                        <div class="row g-4">
                            <div class="col-sm-6">
                                <div class="kt-dashboard-mini-stat p-5 h-100">
                                    <div class="kt-dashboard-mini-stat__label fw-bold fs-7 text-uppercase mb-2">Antrean Review</div>
                                    <div class="fs-2hx fw-bolder text-warning mb-1"><?= (int) ($antrean_review ?? 0) ?></div>
                                    <div class="kt-dashboard-mini-stat__helper fs-7">Pelamar baru menunggu keputusan BKK.</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="kt-dashboard-mini-stat p-5 h-100">
                                    <div class="kt-dashboard-mini-stat__label fw-bold fs-7 text-uppercase mb-2">Lamaran Masuk</div>
                                    <div class="fs-2hx fw-bolder text-primary mb-1"><?= (int) ($lamaran_masuk ?? 0) ?></div>
                                    <div class="kt-dashboard-mini-stat__helper fs-7">Berkas lamaran siap dicek.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="kt-dashboard-mini-stat p-5">
                                    <div class="d-flex flex-stack">
                                        <div>
                                            <div class="kt-dashboard-mini-stat__label fw-bold fs-7 text-uppercase mb-2">Tracer Belum Lengkap</div>
                                            <div class="fs-2 fw-bolder text-danger"><?= (int) ($tracer_belum_lengkap ?? 0) ?></div>
                                        </div>
                                        <div class="kt-dashboard-mini-stat__helper fs-7 text-end">Belum mengisi<br>atau masih draft</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 g-xl-8 mb-8">
            <?php foreach ($statCards as $card): ?>
                <div class="col-md-6 col-xl">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-5">
                                <div class="symbol symbol-45px">
                                    <span class="symbol-label bg-light">
                                        <i class="ki-duotone <?= esc($card['icon']) ?> fs-2 <?= esc($card['class']) ?>">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                    </span>
                                </div>
                                <a href="<?= esc($card['url']) ?>" class="badge badge-light-primary"><?= esc($card['action']) ?></a>
                            </div>
                            <div class="text-gray-500 fw-semibold fs-7 mb-1"><?= esc($card['label']) ?></div>
                            <div class="text-gray-900 fw-bolder fs-2hx lh-1 mb-3"><?= esc((string) $card['value']) ?></div>
                            <div class="text-gray-500 fs-7"><?= esc($card['helper']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-5 g-xl-8 mb-8">
            <div class="col-xl-7">
                <div class="card card-flush kt-chart-card h-100">
                    <div class="card-header pt-7">
                        <div class="card-title flex-column">
                            <span class="text-gray-400 fw-bold fs-7 text-uppercase mb-2">Mini Insight Tracer</span>
                            <h3 class="fw-bolder text-gray-900 mb-0">Jumlah Tracer per Angkatan</h3>
                        </div>
                        <div class="card-toolbar">
                            <a href="<?= site_url('superadmin/pelamar') ?>" class="btn btn-sm btn-light-primary">Lihat Alumni</a>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <?php if (($tracerAngkatan['series'] ?? []) === []): ?>
                            <div class="kt-chart-empty">Belum ada data tracer per angkatan.</div>
                        <?php else: ?>
                            <div id="kt_dashboard_tracer_bar" style="height: 260px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card card-flush kt-chart-card h-100">
                    <div class="card-header pt-7">
                        <div class="card-title flex-column">
                            <span class="text-gray-400 fw-bold fs-7 text-uppercase mb-2">Komposisi Aktivitas</span>
                            <h3 class="fw-bolder text-gray-900 mb-0">Status Alumni Setelah Lulus</h3>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <?php if (array_sum($tracerAktivitas['series'] ?? []) <= 0): ?>
                            <div class="kt-chart-empty">Belum ada data aktivitas alumni.</div>
                        <?php else: ?>
                            <div id="kt_dashboard_tracer_donut" style="height: 240px;"></div>
                            <div class="d-flex flex-column gap-3 mt-4">
                                <?php foreach (($tracerAktivitas['map'] ?? []) as $label => $jumlah): ?>
                                    <div class="d-flex flex-stack">
                                        <span class="text-gray-600 fw-semibold"><?= esc((string) $label) ?></span>
                                        <span class="fw-bold text-gray-900"><?= (int) $jumlah ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 g-xl-8">
            <div class="col-xl-5">
                <div class="card card-flush h-100">
                    <div class="card-header pt-7">
                        <div class="card-title flex-column">
                            <span class="text-gray-400 fw-bold fs-7 text-uppercase mb-2">Proses Lamaran</span>
                            <h3 class="fw-bolder text-gray-900 mb-0">Ringkasan Status</h3>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <?php foreach ([
                            'menunggu_verifikasi' => 'Menunggu Verifikasi',
                            'perlu_perbaikan_berkas' => 'Perlu Perbaikan',
                            'diproses' => 'Diproses',
                            'wawancara' => 'Wawancara',
                            'diterima' => 'Diterima',
                            'ditolak' => 'Ditolak',
                        ] as $status => $label): ?>
                            <?php [$badgeClass] = $statusBadge($status); ?>
                            <div class="d-flex flex-stack border-bottom border-gray-200 py-3">
                                <div class="d-flex align-items-center">
                                    <span class="<?= esc($badgeClass) ?> me-3"><?= esc($label) ?></span>
                                </div>
                                <span class="fw-bolder text-gray-900"><?= (int) ($ringkasanLamaran[$status] ?? 0) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card card-flush h-100">
                    <div class="card-header pt-7">
                        <div class="card-title flex-column">
                            <span class="text-gray-400 fw-bold fs-7 text-uppercase mb-2">BKK Activity</span>
                            <h3 class="fw-bolder text-gray-900 mb-0">Lamaran Terbaru</h3>
                        </div>
                        <div class="card-toolbar">
                            <a href="<?= site_url('superadmin/lamaran') ?>" class="btn btn-sm btn-light-primary">Semua Lamaran</a>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-4">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th>Pelamar</th>
                                        <th>Lowongan</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700 fw-semibold">
                                    <?php if (($lamaran_terbaru ?? []) === []): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-8">Belum ada lamaran terbaru.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($lamaran_terbaru as $item): ?>
                                            <?php [$badgeClass, $badgeLabel] = $statusBadge($item['status'] ?? null); ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-gray-900"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?></div>
                                                    <div class="text-muted fs-7"><?= esc((string) ($item['nama_perusahaan'] ?? '-')) ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-gray-900"><?= esc((string) ($item['posisi'] ?? '-')) ?></div>
                                                    <div class="text-muted fs-7"><?= esc((string) ($item['judul_lowongan'] ?? '-')) ?></div>
                                                </td>
                                                <td><?= esc($formatTanggal($item['tanggal_melamar'] ?? null, true)) ?></td>
                                                <td><span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<script>
    window.ktDashboardSuperadminCharts = {
        tracerAktivitas: <?= json_encode([
            'labels' => $tracerAktivitas['labels'] ?? [],
            'series' => $tracerAktivitas['series'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        tracerAngkatan: <?= json_encode([
            'labels' => $tracerAngkatan['labels'] ?? [],
            'series' => $tracerAngkatan['series'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };

    document.addEventListener('DOMContentLoaded', function () {
        var config = window.ktDashboardSuperadminCharts || {};

        if (typeof ApexCharts === 'undefined') {
            return;
        }

        var barElement = document.getElementById('kt_dashboard_tracer_bar');
        if (barElement && config.tracerAngkatan && config.tracerAngkatan.series && config.tracerAngkatan.series.length > 0) {
            new ApexCharts(barElement, {
                chart: {
                    type: 'bar',
                    height: 260,
                    toolbar: { show: false },
                    fontFamily: 'inherit'
                },
                series: [{
                    name: 'Tracer',
                    data: config.tracerAngkatan.series
                }],
                xaxis: {
                    categories: config.tracerAngkatan.labels,
                    labels: { style: { colors: '#A1A5B7', fontSize: '12px' } }
                },
                yaxis: {
                    labels: { style: { colors: '#A1A5B7', fontSize: '12px' } }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        columnWidth: '42%'
                    }
                },
                dataLabels: { enabled: false },
                colors: ['#3E97FF'],
                grid: {
                    borderColor: '#E1E3EA',
                    strokeDashArray: 4
                }
            }).render();
        }

        var donutElement = document.getElementById('kt_dashboard_tracer_donut');
        if (donutElement && config.tracerAktivitas && config.tracerAktivitas.series && config.tracerAktivitas.series.length > 0) {
            new ApexCharts(donutElement, {
                chart: {
                    type: 'donut',
                    height: 240,
                    fontFamily: 'inherit'
                },
                labels: config.tracerAktivitas.labels,
                series: config.tracerAktivitas.series,
                legend: { show: false },
                dataLabels: { enabled: false },
                colors: ['#50CD89', '#3E97FF', '#F6C000', '#F1416C', '#7239EA'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Alumni',
                                    color: '#A1A5B7'
                                }
                            }
                        }
                    }
                }
            }).render();
        }
    });
</script>
<?= $this->endSection() ?>
