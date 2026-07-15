<?php
$statistik = is_array($statistik ?? null) ? $statistik : [];
$aktivitas = is_array($aktivitas ?? null) ? $aktivitas : [];
$isLogin = session()->get('pengguna_login') === true;
$slugPeran = (string) session()->get('slug_peran');

$dashboardUrl = site_url('login');
if ($slugPeran === 'superadmin') {
    $dashboardUrl = site_url('dashboard/superadmin');
} elseif ($slugPeran === 'admin_sekolah') {
    $dashboardUrl = site_url('admin-sekolah/dashboard');
} elseif ($slugPeran === 'alumni') {
    $dashboardUrl = site_url('alumni/dashboard');
}

$angka = static fn (string $key): string => number_format((int) ($statistik[$key] ?? 0), 0, ',', '.');
$maksAktivitas = 0;
foreach ($aktivitas as $itemAktivitas) {
    $maksAktivitas = max($maksAktivitas, (int) ($itemAktivitas['total'] ?? 0));
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title><?= esc($title ?? 'Tracer Study Alumni') ?></title>
    <meta charset="utf-8">
    <meta name="description" content="Sistem Informasi Tracer Study Alumni SMK Teratai Putih 3.">
    <meta name="keywords" content="tracer study, alumni, legalisir, SMK Teratai Putih 3">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/media/logos/logo-smk-teratai-putih-3.svg') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700">
    <link href="<?= base_url('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css">
    <style>
        html,
        body.tracer-public {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        body.tracer-public,
        body.tracer-public * {
            box-sizing: border-box;
        }

        body.tracer-public {
            margin: 0;
            font-family: Inter, sans-serif;
            color: #172033;
            background: #f5f8fc;
        }

        .landing-wrap {
            width: min(1160px, calc(100% - 32px));
            max-width: calc(100vw - 32px);
            margin: 0 auto;
        }

        .landing-wrap,
        .landing-hero-grid > *,
        .landing-panel,
        .landing-logo-card > div {
            min-width: 0;
            max-width: 100%;
        }

        .landing-nav {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255, 255, 255, .92);
            border-bottom: 1px solid #e5edf7;
            backdrop-filter: blur(16px);
        }

        .landing-nav-inner {
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .landing-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            color: #172033;
            font-weight: 800;
        }

        .landing-brand span,
        .landing-title,
        .landing-copy,
        .landing-logo-card {
            overflow-wrap: anywhere;
        }

        .landing-brand img {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }

        .landing-hero {
            padding: 74px 0 58px;
            background:
                linear-gradient(135deg, rgba(5, 150, 105, .1), rgba(37, 99, 235, .11)),
                #f8fbff;
        }

        .landing-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(360px, .95fr);
            gap: 42px;
            align-items: center;
        }

        .landing-title {
            font-size: clamp(36px, 4.8vw, 62px);
            line-height: 1.04;
            font-weight: 900;
            letter-spacing: 0;
            margin: 0 0 20px;
            color: #0f172a;
        }

        .landing-copy {
            max-width: 640px;
            color: #64748b;
            font-size: 17px;
            line-height: 1.75;
        }

        .landing-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 28px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .08);
        }

        .landing-logo-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px;
            border: 1px solid #dbeafe;
            background: #f8fbff;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .landing-logo-card img {
            width: 78px;
            height: 78px;
            object-fit: contain;
        }

        .landing-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .landing-stat {
            padding: 18px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
        }

        .landing-stat strong {
            display: block;
            font-size: 28px;
            color: #0f172a;
            line-height: 1;
        }

        .landing-stat span {
            display: block;
            margin-top: 8px;
            color: #64748b;
            font-weight: 700;
            font-size: 13px;
        }

        .landing-section {
            padding: 56px 0;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .feature-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            min-height: 190px;
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef6ff;
            color: #2563eb;
            margin-bottom: 18px;
        }

        .activity-chart {
            display: grid;
            gap: 20px;
            padding-top: 6px;
        }

        .activity-chart-row {
            display: grid;
            grid-template-columns: minmax(130px, 190px) minmax(0, 1fr) 90px;
            align-items: center;
            gap: 18px;
        }

        .activity-chart-label {
            min-width: 0;
            overflow-wrap: anywhere;
            color: #334155;
            font-weight: 700;
        }

        .activity-chart-track {
            height: 22px;
            overflow: hidden;
            border-radius: 999px;
            background: #eaf0f8;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, .06);
        }

        .activity-chart-bar {
            width: var(--chart-value);
            min-width: 8px;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #2563eb, #60a5fa);
            transition: width .4s ease;
        }

        .activity-chart-row:nth-child(2) .activity-chart-bar {
            background: linear-gradient(90deg, #059669, #34d399);
        }

        .activity-chart-row:nth-child(3) .activity-chart-bar {
            background: linear-gradient(90deg, #7c3aed, #a78bfa);
        }

        .activity-chart-row:nth-child(4) .activity-chart-bar {
            background: linear-gradient(90deg, #ea580c, #fb923c);
        }

        .activity-chart-value {
            color: #0f172a;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .landing-footer {
            padding: 26px 0;
            background: #0f172a;
            color: rgba(255, 255, 255, .72);
        }

        @media (max-width: 900px) {
            .landing-hero-grid,
            .feature-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .landing-wrap {
                width: calc(100% - 24px);
                max-width: calc(100vw - 24px);
            }

            .landing-nav-inner {
                align-items: stretch;
                flex-direction: column;
                padding: 14px 0;
            }

            .landing-brand img {
                width: 38px;
                height: 38px;
                flex: 0 0 auto;
            }

            .landing-hero {
                padding: 44px 0 40px;
            }

            .landing-hero-grid {
                gap: 28px;
            }

            .landing-title {
                font-size: clamp(32px, 10vw, 42px);
                line-height: 1.1;
            }

            .landing-panel,
            .feature-card {
                width: 100%;
                max-width: 100%;
                padding: 20px;
            }

            .landing-logo-card {
                align-items: flex-start;
                padding: 14px;
            }

            .landing-logo-card img {
                width: 58px;
                height: 58px;
                flex: 0 0 auto;
            }

            .landing-stats {
                grid-template-columns: 1fr;
            }

            .activity-chart {
                gap: 18px;
            }

            .activity-chart-row {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 8px 12px;
            }

            .activity-chart-track {
                grid-column: 1 / -1;
                grid-row: 2;
                height: 18px;
            }

            .activity-chart-value {
                grid-column: 2;
                grid-row: 1;
            }
        }
    </style>
</head>

<body class="tracer-public">
    <nav class="landing-nav">
        <div class="landing-wrap landing-nav-inner">
            <a href="<?= site_url('/') ?>" class="landing-brand">
                <img src="<?= base_url('assets/media/logos/logo-smk-teratai-putih-3.svg') ?>" alt="Logo SMK Teratai Putih 3">
                <span>Tracer Study SMK Teratai Putih 3</span>
            </a>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= $isLogin ? esc($dashboardUrl) : site_url('login') ?>" class="btn btn-light-primary fw-bold">
                    <?= $isLogin ? 'Dashboard' : 'Login' ?>
                </a>
                <?php if (! $isLogin): ?>
                    <a href="<?= site_url('daftar') ?>" class="btn btn-primary fw-bold">Daftar Alumni</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main>
        <section class="landing-hero">
            <div class="landing-wrap landing-hero-grid">
                <div>
                    <div class="badge badge-light-success fw-bold px-4 py-2 mb-5">Sistem Informasi Alumni</div>
                    <h1 class="landing-title">Tracer Study Alumni SMK Teratai Putih 3</h1>
                    <p class="landing-copy">
                        Platform untuk mencatat data alumni, aktivitas setelah lulus, pengajuan legalisir,
                        dan laporan keterserapan alumni secara lebih rapi dan terpusat.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mt-8">
                        <a href="<?= site_url('daftar') ?>" class="btn btn-primary btn-lg fw-bold">Daftar Sebagai Alumni</a>
                        <a href="<?= site_url('login') ?>" class="btn btn-light-primary btn-lg fw-bold">Masuk Sistem</a>
                    </div>
                </div>

                <div class="landing-panel">
                    <div class="landing-logo-card">
                        <img src="<?= base_url('assets/media/logos/logo-smk-teratai-putih-3.svg') ?>" alt="Logo SMK Teratai Putih 3">
                        <div>
                            <div class="text-muted fw-bold text-uppercase fs-8 mb-1">Sekolah</div>
                            <div class="fw-bolder fs-4 text-gray-900">SMK Teratai Putih 3</div>
                            <div class="text-muted fw-semibold">Tracer Study Alumni</div>
                        </div>
                    </div>

                    <div class="landing-stats">
                        <div class="landing-stat">
                            <strong><?= $angka('alumni') ?></strong>
                            <span>Alumni Terdata</span>
                        </div>
                        <div class="landing-stat">
                            <strong><?= $angka('tracer') ?></strong>
                            <span>Tracer Terisi</span>
                        </div>
                        <div class="landing-stat">
                            <strong><?= $angka('legalisir') ?></strong>
                            <span>Pengajuan Legalisir</span>
                        </div>
                        <div class="landing-stat">
                            <strong><?= $angka('kompetensi') ?></strong>
                            <span>Kompetensi Keahlian</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="landing-wrap">
                <div class="text-center mb-10">
                    <div class="text-primary fw-bold text-uppercase fs-8 mb-2">Fitur Utama</div>
                    <h2 class="fw-bolder text-gray-900 mb-3">Dibuat untuk kebutuhan tracer study sekolah</h2>
                    <div class="text-muted fw-semibold">Alumni, admin sekolah, dan super admin dapat bekerja dalam satu sistem yang sama.</div>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="ki-duotone ki-profile-user fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></div>
                        <h3 class="fw-bold text-gray-900">Data Alumni</h3>
                        <p class="text-muted fw-semibold mb-0">Alumni dapat mendaftar, melengkapi profil, dan memperbarui informasi setelah lulus.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="ki-duotone ki-chart-simple-3 fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></div>
                        <h3 class="fw-bold text-gray-900">Tracer Study</h3>
                        <p class="text-muted fw-semibold mb-0">Sekolah dapat memantau status alumni: bekerja, kuliah, wirausaha, atau mencari kerja.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="ki-duotone ki-file-down fs-2"><span class="path1"></span><span class="path2"></span></i></div>
                        <h3 class="fw-bold text-gray-900">Laporan & Export</h3>
                        <p class="text-muted fw-semibold mb-0">Data tracer dapat difilter dan diexport ke Excel atau PDF untuk kebutuhan laporan.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section pt-0">
            <div class="landing-wrap">
                <div class="landing-panel">
                    <div class="mb-8">
                        <div class="text-primary fw-bold text-uppercase fs-8 mb-2">Grafik Aktivitas Alumni</div>
                        <h2 class="fw-bolder text-gray-900 mb-2">Status Alumni Setelah Lulus</h2>
                        <div class="text-muted fw-semibold">Perbandingan jumlah alumni berdasarkan aktivitas utama yang tercatat.</div>
                    </div>
                    <div class="activity-chart" role="img" aria-label="Grafik jumlah alumni berdasarkan aktivitas setelah lulus">
                        <?php if ($aktivitas === []): ?>
                            <div class="text-muted fw-semibold py-8 text-center">Belum ada data aktivitas alumni.</div>
                        <?php else: ?>
                            <?php foreach ($aktivitas as $row): ?>
                                <?php
                                $totalAktivitas = (int) ($row['total'] ?? 0);
                                $persentaseAktivitas = $maksAktivitas > 0 ? ($totalAktivitas / $maksAktivitas) * 100 : 0;
                                ?>
                                <div class="activity-chart-row">
                                    <span class="activity-chart-label"><?= esc((string) ($row['nama_aktivitas'] ?? 'Lainnya')) ?></span>
                                    <div class="activity-chart-track" aria-hidden="true">
                                        <div class="activity-chart-bar" style="--chart-value: <?= esc(number_format($persentaseAktivitas, 2, '.', ''), 'attr') ?>%"></div>
                                    </div>
                                    <span class="activity-chart-value"><?= number_format($totalAktivitas, 0, ',', '.') ?> alumni</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="landing-wrap d-flex flex-column flex-md-row justify-content-between gap-2">
            <span>Tracer Study SMK Teratai Putih 3</span>
            <span>Sistem Informasi Alumni</span>
        </div>
    </footer>

    <script>
        var hostUrl = "<?= base_url('assets/') ?>";
    </script>
    <script src="<?= base_url('assets/plugins/global/plugins.bundle.js') ?>"></script>
    <script src="<?= base_url('assets/js/scripts.bundle.js') ?>"></script>
</body>

</html>
