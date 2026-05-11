<?php
/*
|-------------------------------------------------------------------
| VIEW DASHBOARD ADMIN SEKOLAH/BKK
|-------------------------------------------------------------------
| Dashboard ini menjadi pusat kerja Admin Sekolah/BKK. Fokusnya pada
| tracer alumni, keterserapan, lowongan aktif, dan tindak lanjut lamaran.
|
| Alur kerja:
| 1. Kartu hero memberi konteks pekerjaan BKK hari ini.
| 2. Kartu statistik menampilkan angka penting yang perlu dipantau.
| 3. Grafik kecil memberi gambaran aktivitas alumni.
| 4. Tabel tracer terbaru membantu admin cepat melihat data terakhir.
|
| Tips Debugging:
| - Jika grafik tidak tampil, pastikan ApexCharts dimuat dari footer.
| - Jika angka nol semua, periksa isi tabel alumni, tracer, dan lamaran.
*/

$namaAdmin = trim((string) (session('nama_lengkap') ?? 'Admin BKK'));
$grafikAktivitas = is_array($grafik_aktivitas ?? null) ? $grafik_aktivitas : ['labels' => [], 'series' => [], 'map' => []];
$grafikAngkatan = is_array($grafik_angkatan ?? null) ? $grafik_angkatan : ['labels' => [], 'series' => []];

$formatTanggal = static function (?string $tanggal): string {
    if ($tanggal === null || trim($tanggal) === '') {
        return '-';
    }

    try {
        return (new DateTime($tanggal))->format('d M Y, H:i');
    } catch (Throwable) {
        return (string) $tanggal;
    }
};

$statusBadge = static function (?string $status): array {
    return match ((string) $status) {
        'draft' => ['badge badge-light-secondary', 'Draft'],
        'terkirim' => ['badge badge-light-warning', 'Terkirim'],
        'terverifikasi' => ['badge badge-light-info', 'Terverifikasi'],
        'disetujui' => ['badge badge-light-success', 'Disetujui'],
        default => ['badge badge-light-secondary', '-'],
    };
};

$statCards = [
    ['label' => 'Antrean Review', 'value' => (int) ($antrean_review ?? 0), 'helper' => 'Pelamar baru menunggu persetujuan BKK.', 'class' => 'text-warning', 'icon' => 'ki-timer', 'url' => site_url('admin-sekolah/pelamar'), 'action' => 'Review'],
    ['label' => 'Pelamar Aktif', 'value' => (int) ($pelamar_aktif ?? 0), 'helper' => 'Akun pelamar yang sudah dibuka aksesnya.', 'class' => 'text-success', 'icon' => 'ki-profile-user', 'url' => site_url('admin-sekolah/pelamar'), 'action' => 'Lihat'],
    ['label' => 'Lamaran Masuk', 'value' => (int) ($lamaran_masuk ?? 0), 'helper' => 'Lamaran menunggu verifikasi dokumen.', 'class' => 'text-primary', 'icon' => 'ki-document', 'url' => site_url('admin-sekolah/lamaran'), 'action' => 'Periksa'],
    ['label' => 'Lowongan Aktif', 'value' => (int) ($lowongan_aktif ?? 0), 'helper' => 'Lowongan yang sedang tayang.', 'class' => 'text-info', 'icon' => 'ki-office-bag', 'url' => site_url('admin-sekolah/lowongan'), 'action' => 'Kelola'],
    ['label' => 'Tracer Belum Lengkap', 'value' => (int) ($tracer_belum_lengkap ?? 0), 'helper' => 'Alumni belum mengisi atau masih draft tracer.', 'class' => 'text-danger', 'icon' => 'ki-notepad-edit', 'url' => site_url('admin-sekolah/tracer'), 'action' => 'Pantau'],
];
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('extra_css') ?>
<style>
    .kt-bkk-hero {
        background:
            radial-gradient(circle at 90% 10%, rgba(62, 151, 255, 0.32), transparent 30%),
            linear-gradient(135deg, #0f172a 0%, #1f2937 58%, #0b3b5a 100%);
        border-radius: 1.5rem;
        overflow: hidden;
    }

    .kt-bkk-hero .card-body {
        position: relative;
        z-index: 1;
    }

    .kt-bkk-glass {
        border: 1px solid rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.1);
        border-radius: 1rem;
    }

    .kt-bkk-chart-empty {
        min-height: 240px;
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
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Dashboard Admin Sekolah/BKK</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">BKK & Tracer Study</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted"><?= esc(date('d M Y')) ?></li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <div class="card kt-bkk-hero mb-8">
            <div class="card-body p-8 p-lg-10">
                <div class="row g-8 align-items-center">
                    <div class="col-xl-8">
                        <span class="badge badge-light-info mb-4">Ruang Kerja BKK</span>
                        <h2 class="text-white fw-bolder fs-2hx mb-4">Selamat datang, <?= esc($namaAdmin) ?></h2>
                        <div class="text-gray-300 fs-5 fw-semibold mb-8">
                            Pantau tracer alumni, kesiapan data akademik, lowongan aktif, dan tindak lanjut lamaran dari halaman yang lebih fokus untuk Admin Sekolah/BKK.
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="<?= site_url('admin-sekolah/tracer') ?>" class="btn btn-primary fw-bold">Data Tracer Alumni</a>
                            <a href="<?= site_url('admin-sekolah/pelamar') ?>" class="btn btn-light fw-bold">Review Pelamar</a>
                            <a href="<?= site_url('admin-sekolah/lamaran') ?>" class="btn btn-light-info fw-bold">Data Lamaran</a>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="kt-bkk-glass p-6">
                            <div class="text-white-50 fw-bold fs-7 text-uppercase mb-2">Antrean Review</div>
                            <div class="text-warning fw-bolder fs-2hx mb-2"><?= (int) ($antrean_review ?? 0) ?></div>
                            <div class="text-gray-300 fs-7">Akun pelamar baru yang perlu disetujui sebelum fitur lain terbuka.</div>
                        </div>
                        <div class="kt-bkk-glass p-6 mt-4">
                            <div class="text-white-50 fw-bold fs-7 text-uppercase mb-2">Lamaran Masuk</div>
                            <div class="text-primary fw-bolder fs-2x mb-2"><?= (int) ($lamaran_masuk ?? 0) ?></div>
                            <div class="text-gray-300 fs-7">Lamaran yang menunggu pemeriksaan dokumen.</div>
                        </div>
                        <div class="kt-bkk-glass p-6 mt-4">
                            <div class="text-white-50 fw-bold fs-7 text-uppercase mb-2">Tracer Belum Lengkap</div>
                            <div class="text-danger fw-bolder fs-2x mb-2"><?= (int) ($tracer_belum_lengkap ?? 0) ?></div>
                            <div class="text-gray-300 fs-7">Alumni belum mengisi tracer atau masih menyimpan draft.</div>
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
                <div class="card card-flush h-100">
                    <div class="card-header pt-7">
                        <div class="card-title flex-column">
                            <span class="text-gray-400 fw-bold fs-7 text-uppercase mb-2">Tren Tracer</span>
                            <h3 class="fw-bolder text-gray-900 mb-0">Tracer Alumni per Angkatan</h3>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <?php if (($grafikAngkatan['series'] ?? []) === []): ?>
                            <div class="kt-bkk-chart-empty">Belum ada data grafik angkatan.</div>
                        <?php else: ?>
                            <div id="kt_bkk_chart_angkatan" style="height: 270px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card card-flush h-100">
                    <div class="card-header pt-7">
                        <div class="card-title flex-column">
                            <span class="text-gray-400 fw-bold fs-7 text-uppercase mb-2">Komposisi Alumni</span>
                            <h3 class="fw-bolder text-gray-900 mb-0">Aktivitas Setelah Lulus</h3>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <?php if (array_sum($grafikAktivitas['series'] ?? []) <= 0): ?>
                            <div class="kt-bkk-chart-empty">Belum ada data aktivitas.</div>
                        <?php else: ?>
                            <div id="kt_bkk_chart_aktivitas" style="height: 230px;"></div>
                            <div class="d-flex flex-column gap-3 mt-5">
                                <?php foreach (($grafikAktivitas['map'] ?? []) as $label => $jumlah): ?>
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

        <div class="card card-flush">
            <div class="card-header pt-7">
                <div class="card-title flex-column">
                    <span class="text-gray-400 fw-bold fs-7 text-uppercase mb-2">Update Terbaru</span>
                    <h3 class="fw-bolder text-gray-900 mb-0">Tracer Alumni Terbaru</h3>
                </div>
                <div class="card-toolbar">
                    <a href="<?= site_url('admin-sekolah/tracer') ?>" class="btn btn-sm btn-light-primary">Buka Data Tracer</a>
                </div>
            </div>
            <div class="card-body pt-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                <th>Alumni</th>
                                <th>Kegiatan</th>
                                <th>Status</th>
                                <th>Diperbarui</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            <?php if (($tracer_terbaru ?? []) === []): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-8">Belum ada tracer terbaru.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tracer_terbaru as $item): ?>
                                    <?php [$badgeClass, $badgeLabel] = $statusBadge($item['status'] ?? null); ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?></div>
                                            <div class="text-muted fs-7"><?= esc((string) ($item['account_id'] ?? '-')) ?></div>
                                        </td>
                                        <td><?= esc((string) (($item['nama_aktivitas'] ?? '') !== '' ? $item['nama_aktivitas'] : '-')) ?></td>
                                        <td><span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span></td>
                                        <td><?= esc($formatTanggal($item['diperbarui_pada'] ?? null)) ?></td>
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
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<script>
    window.ktAdminSekolahDashboard = {
        aktivitas: <?= json_encode(['labels' => $grafikAktivitas['labels'] ?? [], 'series' => $grafikAktivitas['series'] ?? []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        angkatan: <?= json_encode(['labels' => $grafikAngkatan['labels'] ?? [], 'series' => $grafikAngkatan['series'] ?? []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };

    document.addEventListener('DOMContentLoaded', function () {
        var config = window.ktAdminSekolahDashboard || {};

        if (typeof ApexCharts === 'undefined') {
            return;
        }

        var bar = document.getElementById('kt_bkk_chart_angkatan');
        if (bar && config.angkatan && config.angkatan.series && config.angkatan.series.length > 0) {
            new ApexCharts(bar, {
                chart: { type: 'bar', height: 270, toolbar: { show: false }, fontFamily: 'inherit' },
                series: [{ name: 'Tracer', data: config.angkatan.series }],
                xaxis: { categories: config.angkatan.labels, labels: { style: { colors: '#A1A5B7' } } },
                yaxis: { labels: { style: { colors: '#A1A5B7' } } },
                plotOptions: { bar: { borderRadius: 8, columnWidth: '42%' } },
                dataLabels: { enabled: false },
                grid: { borderColor: '#E1E3EA', strokeDashArray: 4 },
                colors: ['#3E97FF']
            }).render();
        }

        var donut = document.getElementById('kt_bkk_chart_aktivitas');
        if (donut && config.aktivitas && config.aktivitas.series && config.aktivitas.series.length > 0) {
            new ApexCharts(donut, {
                chart: { type: 'donut', height: 230, fontFamily: 'inherit' },
                labels: config.aktivitas.labels,
                series: config.aktivitas.series,
                legend: { show: false },
                dataLabels: { enabled: false },
                colors: ['#50CD89', '#3E97FF', '#F6C000', '#F1416C', '#7239EA'],
                plotOptions: { pie: { donut: { size: '68%' } } }
            }).render();
        }
    });
</script>
<?= $this->endSection() ?>
