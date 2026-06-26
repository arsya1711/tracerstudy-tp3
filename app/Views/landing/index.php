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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title><?= esc($title ?? 'Tracer Study Alumni') ?></title>
    <meta charset="utf-8">
    <meta name="description" content="Sistem Informasi Tracer Study Alumni SMK Teratai Putih 3.">
    <meta name="keywords" content="tracer study, alumni, legalisir, SMK Teratai Putih 3">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?= base_url('assets/media/logos/favicon.ico') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700">
    <link href="<?= base_url('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css">
    <style>
        body.tracer-public {
            margin: 0;
            font-family: Inter, sans-serif;
            color: #172033;
            background: #f5f8fc;
        }

        .landing-wrap {
            width: min(1160px, calc(100% - 32px));
            margin: 0 auto;
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
            color: #172033;
            font-weight: 800;
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

        .activity-list {
            display: grid;
            gap: 12px;
        }

        .activity-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 15px 0;
            border-bottom: 1px solid #edf2f7;
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
            .landing-nav-inner {
                align-items: flex-start;
                flex-direction: column;
                padding: 14px 0;
            }

            .landing-stats {
                grid-template-columns: 1fr;
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
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-4 mb-6">
                        <div>
                            <div class="text-primary fw-bold text-uppercase fs-8 mb-2">Ringkasan Aktivitas</div>
                            <h2 class="fw-bolder text-gray-900 mb-0">Status Alumni Setelah Lulus</h2>
                        </div>
                        <a href="<?= site_url('login') ?>" class="btn btn-light-primary fw-bold align-self-start">Kelola Data</a>
                    </div>
                    <div class="activity-list">
                        <?php if ($aktivitas === []): ?>
                            <div class="text-muted fw-semibold py-8 text-center">Belum ada data aktivitas alumni.</div>
                        <?php else: ?>
                            <?php foreach ($aktivitas as $row): ?>
                                <div class="activity-row">
                                    <span class="fw-bold text-gray-800"><?= esc((string) ($row['nama_aktivitas'] ?? 'Lainnya')) ?></span>
                                    <span class="badge badge-light-success fs-7"><?= (int) ($row['total'] ?? 0) ?> alumni</span>
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
