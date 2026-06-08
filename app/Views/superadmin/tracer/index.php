<?php
/*
|-------------------------------------------------------------------
| VIEW DATA TRACER ALUMNI
|-------------------------------------------------------------------
| View ini menjadi halaman laporan tracer alumni. Tabel dan grafik
| diletakkan dalam satu halaman agar admin dapat membaca data mentah
| dan insight visual dari sumber data yang sama.
|
| Alur kerja:
| 1. Toolbar menyediakan search, filter, dan tombol cetak.
| 2. Tabel menampilkan ringkasan tracer alumni.
| 3. Modal detail menampilkan isian tracer lengkap per alumni.
| 4. Grafik batang dan donut dirender dari data hasil filter.
|
| Tips Debugging:
| - Jika grafik tidak muncul, cek ApexCharts tersedia dari Metronic.
| - Jika filter tidak bekerja, cek name input sesuai query controller.
*/

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
        'terkirim'      => ['badge badge-light-warning', 'Terkirim'],
        'terverifikasi' => ['badge badge-light-info', 'Terverifikasi'],
        'disetujui'     => ['badge badge-light-success', 'Disetujui'],
        default         => ['badge badge-light-secondary', '-'],
    };
};

$ringkasKompetensi = static function (array $row): string {
    $nama = trim((string) ($row['nama_kompetensi'] ?? ''));
    $akronim = trim((string) ($row['akronim'] ?? ''));

    if ($nama !== '' && $akronim !== '') {
        return $nama . ' (' . $akronim . ')';
    }

    return $nama !== '' ? $nama : ($akronim !== '' ? $akronim : '-');
};

$detailFields = [
    'posisi_kerja' => 'Posisi / Jabatan',
    'nama_instansi' => 'Nama Instansi / Perusahaan',
    'bidang_instansi' => 'Bidang Instansi',
    'alamat_instansi' => 'Alamat Instansi',
    'tahun_mulai_kerja' => 'Tahun Mulai Kerja',
    'relevan_jurusan' => 'Relevan Jurusan',
    'penghasilan_range' => 'Penghasilan',
    'universitas' => 'Universitas',
    'program_studi' => 'Program Studi',
    'status_kuliah' => 'Status Kuliah',
    'nama_usaha' => 'Nama Usaha',
    'bidang_usaha' => 'Bidang Usaha',
    'modal_awal' => 'Modal Awal',
    'penghasilan_usaha' => 'Penghasilan Usaha',
    'rencana_kedepan' => 'Rencana Kedepan',
];

$dashboardUrl = $dashboardUrl ?? site_url('dashboard/superadmin');
$tracerBaseUrl = $tracerBaseUrl ?? site_url('superadmin/tracer');
$tracerRoleLabel = $tracerRoleLabel ?? 'Manajemen Sekolah';
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('extra_css') ?>
<style>
    .kt-tracer-print-meta {
        display: none;
    }

    .kt-tracer-chart-card {
        min-height: 340px;
    }

    .kt-tracer-empty-chart {
        min-height: 240px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bs-gray-500);
        font-weight: 600;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #kt_tracer_print_area,
        #kt_tracer_print_area * {
            visibility: visible;
        }

        #kt_tracer_print_area {
            position: absolute;
            inset: 0;
            width: 100%;
            background: #fff;
        }

        .app-sidebar,
        .app-header,
        .app-toolbar,
        .card-header,
        .modal,
        .btn,
        .kt-tracer-no-print {
            display: none !important;
        }

        .kt-tracer-print-meta {
            display: block;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Data Tracer Alumni</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= esc($dashboardUrl) ?>" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted"><?= esc($tracerRoleLabel) ?></li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Data Tracer Alumni</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <div id="kt_tracer_print_area">
            <div class="card mb-8">
                <div class="card-header border-0 pt-6">
                    <form method="get" action="<?= esc($tracerBaseUrl) ?>" class="d-flex flex-stack flex-wrap gap-4 w-100">
                        <div class="card-title">
                            <div class="d-flex align-items-center position-relative my-1">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" name="q" class="form-control form-control-solid w-250px ps-13" placeholder="Cari alumni / jurusan" value="<?= esc((string) ($filters['search'] ?? '')) ?>" />
                            </div>
                        </div>

                        <div class="card-toolbar kt-tracer-no-print">
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                    <i class="ki-duotone ki-filter fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>Filter
                                </button>

                                <div class="menu menu-sub menu-sub-dropdown w-325px w-md-375px" data-kt-menu="true">
                                    <div class="px-7 py-5">
                                        <div class="fs-5 text-dark fw-bold">Filter Tracer</div>
                                    </div>
                                    <div class="separator border-gray-200"></div>
                                    <div class="px-7 py-5">
                                        <div class="mb-8">
                                            <label class="form-label fs-6 fw-semibold">Angkatan:</label>
                                            <select name="id_angkatan" class="form-select form-select-solid">
                                                <option value="">Semua Angkatan</option>
                                                <?php foreach ($daftarAngkatan as $angkatan): ?>
                                                    <option value="<?= (int) $angkatan['id_angkatan'] ?>" <?= (int) ($filters['id_angkatan'] ?? 0) === (int) $angkatan['id_angkatan'] ? 'selected' : '' ?>>
                                                        <?= esc((string) ($angkatan['tahun_lulus'] ?? '-')) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-8">
                                            <label class="form-label fs-6 fw-semibold">Jurusan:</label>
                                            <select name="id_kompetensi" class="form-select form-select-solid">
                                                <option value="">Semua Jurusan</option>
                                                <?php foreach ($daftarKompetensi as $kompetensi): ?>
                                                    <option value="<?= (int) $kompetensi['id_kompetensi'] ?>" <?= (int) ($filters['id_kompetensi'] ?? 0) === (int) $kompetensi['id_kompetensi'] ? 'selected' : '' ?>>
                                                        <?= esc((string) ($kompetensi['nama_kompetensi'] ?? '-')) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-8">
                                            <label class="form-label fs-6 fw-semibold">Kegiatan:</label>
                                            <select name="id_aktivitas" class="form-select form-select-solid">
                                                <option value="">Semua Kegiatan</option>
                                                <?php foreach ($daftarAktivitas as $aktivitas): ?>
                                                    <option value="<?= (int) $aktivitas['id_aktivitas'] ?>" <?= (int) ($filters['id_aktivitas'] ?? 0) === (int) $aktivitas['id_aktivitas'] ? 'selected' : '' ?>>
                                                        <?= esc((string) ($aktivitas['nama_aktivitas'] ?? '-')) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-8">
                                            <label class="form-label fs-6 fw-semibold">Status:</label>
                                            <select name="status" class="form-select form-select-solid">
                                                <option value="">Semua Status</option>
                                                <?php foreach ($daftarStatus as $value => $label): ?>
                                                    <option value="<?= esc($value, 'attr') ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>>
                                                        <?= esc($label) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <a href="<?= esc($tracerBaseUrl) ?>" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6">Reset</a>
                                            <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true">Apply</button>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-primary" id="kt_tracer_print_button">
                                    <i class="ki-duotone ki-printer fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>Cetak
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body py-4">
                    <div class="kt-tracer-print-meta mb-8">
                        <h2 class="fw-bold mb-2">Laporan Data Tracer Alumni</h2>
                        <div class="text-muted">Dicetak pada <?= esc($formatTanggal(date('Y-m-d H:i:s'), true)) ?></div>
                    </div>

                    <div class="d-flex flex-wrap gap-4 mb-6">
                        <div class="border border-gray-300 border-dashed rounded py-3 px-5">
                            <div class="fs-2 fw-bold text-gray-900"><?= count($tracer) ?></div>
                            <div class="text-muted fw-semibold fs-7">Total Data Tracer</div>
                        </div>
                        <div class="border border-gray-300 border-dashed rounded py-3 px-5">
                            <div class="fs-2 fw-bold text-success"><?= (int) (($grafikAktivitas['map']['Bekerja'] ?? 0) + ($grafikAktivitas['map']['Wirausaha'] ?? 0)) ?></div>
                            <div class="text-muted fw-semibold fs-7">Terserap Kerja/Usaha</div>
                        </div>
                        <div class="border border-gray-300 border-dashed rounded py-3 px-5">
                            <div class="fs-2 fw-bold text-primary"><?= count($grafikAktivitas['labels'] ?? []) ?></div>
                            <div class="text-muted fw-semibold fs-7">Kategori Kegiatan</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-250px">Alumni</th>
                                    <th class="min-w-120px">Angkatan</th>
                                    <th class="min-w-220px">Jurusan</th>
                                    <th class="min-w-160px">Kegiatan</th>
                                    <th class="min-w-140px">Status</th>
                                    <th class="text-end min-w-100px kt-tracer-no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 fw-semibold">
                                <?php if ($tracer === []): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-10">Belum ada data tracer alumni yang sesuai filter.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tracer as $item): ?>
                                        <?php
                                        $idTracer = (int) ($item['id_tracer'] ?? 0);
                                        $modalId = 'kt_modal_detail_tracer_' . $idTracer;
                                        [$badgeClass, $badgeLabel] = $statusBadge($item['status'] ?? null);
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-gray-900"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?></div>
                                                <div class="text-muted fs-7"><?= esc((string) ($item['email'] ?? '-')) ?></div>
                                                <div class="text-muted fs-8"><?= esc((string) ($item['account_id'] ?? '-')) ?></div>
                                            </td>
                                            <td><?= esc((string) (($item['tahun_lulus'] ?? '') !== '' ? $item['tahun_lulus'] : '-')) ?></td>
                                            <td><?= esc($ringkasKompetensi($item)) ?></td>
                                            <td><span class="badge badge-light-primary"><?= esc((string) (($item['nama_aktivitas'] ?? '') !== '' ? $item['nama_aktivitas'] : '-')) ?></span></td>
                                            <td><span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span></td>
                                            <td class="text-end kt-tracer-no-print">
                                                <button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px" data-bs-toggle="modal" data-bs-target="#<?= esc($modalId) ?>" title="Detail Tracer">
                                                    <i class="ki-duotone ki-eye fs-3">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                        <span class="path3"></span>
                                                    </i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-6">
                <div class="col-xl-7">
                    <div class="card card-flush kt-tracer-chart-card h-100">
                        <div class="card-header pt-7">
                            <div class="card-title flex-column">
                                <span class="text-gray-400 fw-bold fs-7 text-uppercase mb-2">Jumlah Keterserapan</span>
                                <h3 class="fw-bolder text-gray-900 mb-0">Tracer Alumni per Angkatan</h3>
                            </div>
                        </div>
                        <div class="card-body pt-4">
                            <?php if (($grafikAngkatan['series'] ?? []) === []): ?>
                                <div class="kt-tracer-empty-chart">Belum ada data untuk grafik angkatan.</div>
                            <?php else: ?>
                                <div id="kt_tracer_chart_bar" style="height: 280px;"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card card-flush kt-tracer-chart-card h-100">
                        <div class="card-header pt-7">
                            <div class="card-title flex-column">
                                <span class="text-gray-400 fw-bold fs-7 text-uppercase mb-2">Komposisi Kegiatan</span>
                                <h3 class="fw-bolder text-gray-900 mb-0">Aktivitas Alumni</h3>
                            </div>
                        </div>
                        <div class="card-body pt-4">
                            <?php if (array_sum($grafikAktivitas['series'] ?? []) <= 0): ?>
                                <div class="kt-tracer-empty-chart">Belum ada data untuk grafik kegiatan.</div>
                            <?php else: ?>
                                <div id="kt_tracer_chart_donut" style="height: 230px;"></div>
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
        </div>
    </div>
</div>

<?php foreach ($tracer as $item): ?>
    <?php
    $idTracer = (int) ($item['id_tracer'] ?? 0);
    $modalId = 'kt_modal_detail_tracer_' . $idTracer;
    [$badgeClass, $badgeLabel] = $statusBadge($item['status'] ?? null);
    ?>
    <div class="modal fade" id="<?= esc($modalId) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-800px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Detail Tracer Alumni</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body px-5 py-7">
                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5 mb-7">
                        <div class="fw-semibold text-gray-700 fs-7">
                            Detail ini membaca data dari <code>tb_tracer_alumni</code> yang terhubung dengan data alumni.
                        </div>
                    </div>

                    <div class="row g-5 mb-7">
                        <div class="col-md-6">
                            <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Alumni</div>
                            <div class="fw-bold text-gray-900"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?></div>
                            <div class="text-muted fs-7"><?= esc((string) ($item['email'] ?? '-')) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Angkatan</div>
                            <div class="fw-bold text-gray-900"><?= esc((string) (($item['tahun_lulus'] ?? '') !== '' ? $item['tahun_lulus'] : '-')) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Status</div>
                            <span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span>
                        </div>
                    </div>

                    <div class="row g-5 mb-7">
                        <div class="col-md-6">
                            <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Jurusan</div>
                            <div class="fw-semibold text-gray-800"><?= esc($ringkasKompetensi($item)) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Kegiatan</div>
                            <div class="fw-semibold text-gray-800"><?= esc((string) (($item['nama_aktivitas'] ?? '') !== '' ? $item['nama_aktivitas'] : '-')) ?></div>
                        </div>
                    </div>

                    <div class="separator my-6"></div>

                    <div class="row g-5">
                        <?php $hasDetail = false; ?>
                        <?php foreach ($detailFields as $field => $label): ?>
                            <?php
                            $value = $item[$field] ?? null;
                            if ($field === 'relevan_jurusan' && $value !== null && $value !== '') {
                                $value = (int) $value === 1 ? 'Ya, relevan' : 'Tidak relevan';
                            }
                            if ($value === null || $value === '') {
                                continue;
                            }
                            $hasDetail = true;
                            ?>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-4 h-100">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1"><?= esc($label) ?></div>
                                    <div class="fw-semibold text-gray-800"><?= nl2br(esc((string) $value)) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (! $hasDetail): ?>
                            <div class="col-12">
                                <div class="text-center text-muted py-8">Belum ada detail tambahan pada tracer ini.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<script>
    window.ktTracerReportCharts = {
        aktivitas: <?= json_encode([
            'labels' => $grafikAktivitas['labels'] ?? [],
            'series' => $grafikAktivitas['series'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        angkatan: <?= json_encode([
            'labels' => $grafikAngkatan['labels'] ?? [],
            'series' => $grafikAngkatan['series'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };

    document.addEventListener('DOMContentLoaded', function () {
        var printButton = document.getElementById('kt_tracer_print_button');
        var config = window.ktTracerReportCharts || {};

        if (printButton) {
            printButton.addEventListener('click', function () {
                window.print();
            });
        }

        if (typeof ApexCharts === 'undefined') {
            return;
        }

        var barElement = document.getElementById('kt_tracer_chart_bar');
        if (barElement && config.angkatan && config.angkatan.series && config.angkatan.series.length > 0) {
            new ApexCharts(barElement, {
                chart: {
                    type: 'bar',
                    height: 280,
                    toolbar: { show: false },
                    fontFamily: 'inherit'
                },
                series: [{
                    name: 'Tracer',
                    data: config.angkatan.series
                }],
                xaxis: {
                    categories: config.angkatan.labels,
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

        var donutElement = document.getElementById('kt_tracer_chart_donut');
        if (donutElement && config.aktivitas && config.aktivitas.series && config.aktivitas.series.length > 0) {
            new ApexCharts(donutElement, {
                chart: {
                    type: 'donut',
                    height: 230,
                    fontFamily: 'inherit'
                },
                labels: config.aktivitas.labels,
                series: config.aktivitas.series,
                legend: { show: false },
                dataLabels: { enabled: false },
                colors: ['#50CD89', '#3E97FF', '#F6C000', '#F1416C', '#7239EA', '#181C32'],
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
