<?php
$grafikAktivitas = is_array($grafik_aktivitas ?? null) ? $grafik_aktivitas : ['labels' => [], 'series' => [], 'map' => []];
$grafikAngkatan = is_array($grafik_angkatan ?? null) ? $grafik_angkatan : ['labels' => [], 'series' => []];
$legalisirMenunggu = (int) ($legalisir_menunggu ?? 0);
$legalisirDiproses = (int) ($legalisir_diproses ?? 0);
$alumniMenungguAktivasi = (int) ($alumni_menunggu_aktivasi ?? 0);

/*
| Card legalisir menggunakan status "diajukan" sebagai indikator utama.
| Warna kuning dan border tebal membantu admin sekolah melihat adanya
| pengajuan baru tanpa harus membuka menu legalisir terlebih dahulu.
*/
$cards = [
    [
        'label' => 'Menunggu Aktivasi',
        'value' => $alumniMenungguAktivasi,
        'url' => site_url('admin-sekolah/tracer?status_akun=menunggu_aktivasi'),
        'class' => $alumniMenungguAktivasi > 0 ? 'bg-light-warning border border-warning border-2' : '',
        'hint' => $alumniMenungguAktivasi > 0 ? 'Akun alumni perlu diverifikasi' : 'Tidak ada akun yang menunggu',
        'valueClass' => $alumniMenungguAktivasi > 0 ? 'text-warning' : 'text-gray-900',
    ],
    ['label' => 'Tracer Terisi', 'value' => (int) ($tracer_terkirim ?? 0), 'url' => site_url('admin-sekolah/tracer'), 'class' => ''],
    ['label' => 'Belum Tracer', 'value' => (int) ($tracer_belum_lengkap ?? 0), 'url' => site_url('admin-sekolah/tracer'), 'class' => ''],
    [
        'label' => 'Pengajuan Legalisir',
        'value' => $legalisirMenunggu,
        'url' => site_url('admin-sekolah/legalisir'),
        'class' => $legalisirMenunggu > 0 ? 'bg-light-warning border border-warning border-2' : 'bg-light',
        'hint' => $legalisirMenunggu > 0 ? $legalisirMenunggu . ' pengajuan baru perlu ditindaklanjuti' : $legalisirDiproses . ' pengajuan sedang diproses',
        'valueClass' => $legalisirMenunggu > 0 ? 'text-warning' : 'text-gray-900',
    ],
    ['label' => 'Total Alumni', 'value' => (int) ($total_alumni ?? 0), 'url' => site_url('admin-sekolah/tracer'), 'class' => ''],
];
$formatTanggal = static function (?string $tanggal): string {
    if ($tanggal === null || trim($tanggal) === '') {
        return '-';
    }

    try {
        return (new DateTime($tanggal))->format('d M Y, H:i');
    } catch (Throwable $e) {
        return (string) $tanggal;
    }
};
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Dashboard Admin Sekolah</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Tracer Study</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <div class="row g-5 g-xl-8 mb-8">
            <?php foreach ($cards as $card): ?>
                <div class="col-md-6 col-xl">
                    <a href="<?= esc($card['url']) ?>" class="card card-flush h-100 hover-elevate-up <?= esc($card['class'] ?? '') ?>">
                        <div class="card-body">
                            <div class="text-gray-500 fw-semibold fs-7 mb-2"><?= esc($card['label']) ?></div>
                            <div class="<?= esc($card['valueClass'] ?? 'text-gray-900') ?> fw-bolder fs-2hx"><?= number_format($card['value'], 0, ',', '.') ?></div>
                            <?php if (! empty($card['hint'])): ?>
                                <div class="fw-semibold fs-7 text-gray-700 mt-2"><?= esc((string) $card['hint']) ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-5 g-xl-8 mb-8">
            <div class="col-xl-7">
                <div class="card card-flush h-100">
                    <div class="card-header pt-7"><h3 class="card-title fw-bolder">Tracer per Angkatan</h3></div>
                    <div class="card-body">
                        <?php if (($grafikAngkatan['series'] ?? []) === []): ?>
                            <div class="text-muted fw-semibold py-10 text-center">Belum ada data tracer per angkatan.</div>
                        <?php else: ?>
                            <div id="kt_admin_chart_angkatan" style="height: 270px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card card-flush h-100">
                    <div class="card-header pt-7"><h3 class="card-title fw-bolder">Aktivitas Alumni</h3></div>
                    <div class="card-body">
                        <?php if (array_sum($grafikAktivitas['series'] ?? []) <= 0): ?>
                            <div class="text-muted fw-semibold py-10 text-center">Belum ada data aktivitas alumni.</div>
                        <?php else: ?>
                            <div id="kt_admin_chart_aktivitas" style="height: 230px;"></div>
                            <?php foreach (($grafikAktivitas['map'] ?? []) as $label => $jumlah): ?>
                                <div class="d-flex flex-stack border-bottom border-gray-200 py-3">
                                    <span class="fw-semibold text-gray-700"><?= esc((string) $label) ?></span>
                                    <span class="fw-bold text-gray-900"><?= (int) $jumlah ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-flush">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bolder">Tracer Alumni Terbaru</h3>
                <div class="card-toolbar"><a href="<?= site_url('admin-sekolah/tracer') ?>" class="btn btn-sm btn-light-primary">Buka Data Tracer</a></div>
            </div>
            <div class="card-body">
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
                                <tr><td colspan="4" class="text-center text-muted py-8">Belum ada tracer terbaru.</td></tr>
                            <?php else: ?>
                                <?php foreach ($tracer_terbaru as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?></div>
                                            <div class="text-muted fs-7"><?= esc((string) ($item['account_id'] ?? '-')) ?></div>
                                        </td>
                                        <td><?= esc((string) (($item['nama_aktivitas'] ?? '') !== '' ? $item['nama_aktivitas'] : '-')) ?></td>
                                        <td><span class="badge badge-light-primary"><?= esc(ucwords((string) ($item['status'] ?? '-'))) ?></span></td>
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
    aktivitas: <?= json_encode(['labels' => $grafikAktivitas['labels'] ?? [], 'series' => $grafikAktivitas['series'] ?? []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    angkatan: <?= json_encode(['labels' => $grafikAngkatan['labels'] ?? [], 'series' => $grafikAngkatan['series'] ?? []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
};
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;
    var config = window.ktAdminSekolahDashboard || {};
    var bar = document.getElementById('kt_admin_chart_angkatan');
    var donut = document.getElementById('kt_admin_chart_aktivitas');
    if (bar && config.angkatan.series.length > 0) {
        new ApexCharts(bar, {
            chart: { type: 'bar', height: 270, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [{ name: 'Tracer', data: config.angkatan.series }],
            xaxis: { categories: config.angkatan.labels },
            dataLabels: { enabled: false },
            colors: ['#3E97FF']
        }).render();
    }
    if (donut && config.aktivitas.series.length > 0) {
        new ApexCharts(donut, {
            chart: { type: 'donut', height: 230, fontFamily: 'inherit' },
            labels: config.aktivitas.labels,
            series: config.aktivitas.series,
            legend: { show: false },
            dataLabels: { enabled: false },
            colors: ['#50CD89', '#3E97FF', '#F6C000', '#F1416C', '#7239EA']
        }).render();
    }
});
</script>
<?= $this->endSection() ?>
