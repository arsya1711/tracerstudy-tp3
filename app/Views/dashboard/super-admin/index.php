<?php
$tracerAktivitas = is_array($tracer_aktivitas ?? null) ? $tracer_aktivitas : ['labels' => [], 'series' => [], 'map' => []];
$tracerAngkatan = is_array($tracer_angkatan ?? null) ? $tracer_angkatan : ['labels' => [], 'series' => []];
$legalisirMenunggu = (int) ($legalisir_menunggu ?? 0);
$legalisirDiproses = (int) ($legalisir_diproses ?? 0);

/*
| Card dashboard disusun dari data controller. Card legalisir dibuat
| mencolok ketika ada pengajuan baru agar admin langsung tahu ada
| pekerjaan yang perlu ditindaklanjuti.
*/
$cards = [
    ['label' => 'Tracer Terisi', 'value' => (int) ($tracer_terkirim ?? 0), 'url' => site_url('superadmin/tracer'), 'class' => ''],
    ['label' => 'Belum Tracer', 'value' => (int) ($tracer_belum_lengkap ?? 0), 'url' => site_url('superadmin/tracer'), 'class' => ''],
    [
        'label' => 'Pengajuan Legalisir',
        'value' => $legalisirMenunggu,
        'url' => site_url('superadmin/legalisir'),
        'class' => $legalisirMenunggu > 0 ? 'bg-light-warning border border-warning border-2' : 'bg-light',
        'hint' => $legalisirMenunggu > 0 ? $legalisirMenunggu . ' pengajuan baru perlu ditindaklanjuti' : $legalisirDiproses . ' pengajuan sedang diproses',
        'valueClass' => $legalisirMenunggu > 0 ? 'text-warning' : 'text-gray-900',
    ],
    ['label' => 'Total Alumni', 'value' => (int) ($total_alumni ?? 0), 'url' => site_url('superadmin/tracer'), 'class' => ''],
];
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Dashboard Tracer Study</h1>
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

        <div class="row g-5 g-xl-8">
            <div class="col-xl-7">
                <div class="card card-flush h-100">
                    <div class="card-header pt-7">
                        <h3 class="card-title fw-bolder">Tracer per Angkatan</h3>
                    </div>
                    <div class="card-body">
                        <?php if (($tracerAngkatan['series'] ?? []) === []): ?>
                            <div class="text-muted fw-semibold py-10 text-center">Belum ada data tracer per angkatan.</div>
                        <?php else: ?>
                            <div id="kt_dashboard_tracer_bar" style="height: 280px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card card-flush h-100">
                    <div class="card-header pt-7">
                        <h3 class="card-title fw-bolder">Aktivitas Alumni</h3>
                    </div>
                    <div class="card-body">
                        <?php if (array_sum($tracerAktivitas['series'] ?? []) <= 0): ?>
                            <div class="text-muted fw-semibold py-10 text-center">Belum ada data aktivitas alumni.</div>
                        <?php else: ?>
                            <div id="kt_dashboard_tracer_donut" style="height: 250px;"></div>
                            <?php foreach (($tracerAktivitas['map'] ?? []) as $label => $jumlah): ?>
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
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<script>
window.ktDashboardTracer = {
    aktivitas: <?= json_encode(['labels' => $tracerAktivitas['labels'] ?? [], 'series' => $tracerAktivitas['series'] ?? []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    angkatan: <?= json_encode(['labels' => $tracerAngkatan['labels'] ?? [], 'series' => $tracerAngkatan['series'] ?? []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
};
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;
    var bar = document.getElementById('kt_dashboard_tracer_bar');
    var donut = document.getElementById('kt_dashboard_tracer_donut');
    var data = window.ktDashboardTracer;
    if (bar && data.angkatan.series.length > 0) {
        new ApexCharts(bar, {
            chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [{ name: 'Tracer', data: data.angkatan.series }],
            xaxis: { categories: data.angkatan.labels },
            dataLabels: { enabled: false },
            colors: ['#3E97FF']
        }).render();
    }
    if (donut && data.aktivitas.series.length > 0) {
        new ApexCharts(donut, {
            chart: { type: 'donut', height: 250, fontFamily: 'inherit' },
            labels: data.aktivitas.labels,
            series: data.aktivitas.series,
            dataLabels: { enabled: false },
            colors: ['#50CD89', '#3E97FF', '#F6C000', '#F1416C', '#7239EA']
        }).render();
    }
});
</script>
<?= $this->endSection() ?>
