<?php
$statistik = is_array($statistik ?? null) ? $statistik : [];
$aktivitas = is_array($aktivitas ?? null) ? $aktivitas : [];
$isLogin = session()->get('pengguna_login') === true;
$slugPeran = (string) session()->get('slug_peran');

$dashboardUrl = site_url('login');
switch ($slugPeran) {
    case 'superadmin':
        $dashboardUrl = site_url('dashboard/superadmin');
        break;
    case 'admin_sekolah':
        $dashboardUrl = site_url('admin-sekolah/dashboard');
        break;
    case 'alumni':
        $dashboardUrl = site_url('alumni/dashboard');
        break;
}

$angka = static function (string $key) use ($statistik): int {
    return (int) ($statistik[$key] ?? 0);
};
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <base href="">
    <title><?= esc($title ?? 'Tracer Study') ?></title>
    <meta charset="utf-8">
    <meta name="description" content="Sistem tracer study alumni SMK Teratai Putih Global 4.">
    <meta name="keywords" content="tracer study, alumni, SMK">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?= base_url('assets/media/logos/favicon.ico') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700">
    <link href="<?= base_url('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css">
    <style>
    body.tracer-public {
        margin: 0;
        font-family: Inter, sans-serif;
        color: #111827;
        background: #f7fafc;
    }

    .tracer-nav {
        background: #0f172a;
        color: #fff;
    }

    .tracer-wrap {
        width: min(1120px, calc(100% - 32px));
        margin: 0 auto;
    }

    .tracer-nav-inner {
        min-height: 68px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .tracer-brand {
        color: #fff;
        font-weight: 800;
        font-size: 18px;
    }

    .tracer-hero {
        padding: 72px 0 56px;
        background: linear-gradient(135deg, #0f172a 0%, #164e63 62%, #065f46 100%);
        color: #fff;
    }

    .tracer-hero-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(320px, .95fr);
        gap: 34px;
        align-items: center;
    }

    .tracer-title {
        font-size: clamp(34px, 4vw, 56px);
        line-height: 1.05;
        font-weight: 900;
        margin: 0 0 18px;
    }

    .tracer-copy {
        color: rgba(255, 255, 255, .76);
        font-size: 17px;
        line-height: 1.7;
        max-width: 680px;
    }

    .tracer-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .tracer-stat {
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .16);
        border-radius: 14px;
        padding: 22px;
    }

    .tracer-stat strong {
        display: block;
        font-size: 34px;
        line-height: 1;
        color: #fff;
    }

    .tracer-stat span {
        display: block;
        margin-top: 10px;
        color: rgba(255, 255, 255, .72);
        font-weight: 700;
    }

    .tracer-section {
        padding: 54px 0;
    }

    .tracer-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 26px;
        box-shadow: 0 18px 50px rgba(15, 23, 42, .08);
    }

    .tracer-activity {
        display: grid;
        gap: 12px;
    }

    .tracer-activity-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 0;
        border-bottom: 1px solid #eef2f7;
    }

    .tracer-footer {
        padding: 24px 0;
        color: rgba(255, 255, 255, .65);
        background: #0f172a;
    }

    @media (max-width: 800px) {

        .tracer-hero-grid,
        .tracer-stats {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body class="tracer-public">
    <nav class="tracer-nav">
        <div class="tracer-wrap tracer-nav-inner">
            <a href="<?= site_url('/') ?>" class="tracer-brand">Tracer Study</a>
            <div class="d-flex gap-2">
                <a href="<?= $isLogin ? esc($dashboardUrl) : site_url('login') ?>" class="btn btn-light fw-bold">
                    <?= $isLogin ? 'Dashboard' : 'Login' ?>
                </a>
                <?php if (! $isLogin): ?>
                <a href="<?= site_url('daftar') ?>" class="btn btn-success fw-bold">Daftar Alumni</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main>
        <section class="tracer-hero">
            <div class="tracer-wrap tracer-hero-grid">
                <div>
                    <div class="badge badge-light-success mb-4">SMK Teratai Putih Global 4</div>
                    <h1 class="tracer-title">Sistem Tracer Study Alumni</h1>
                    <p class="tracer-copy">
                        Aplikasi ini membantu sekolah mencatat profil alumni, aktivitas setelah lulus, riwayat kerja,
                        dan rekap keterserapan alumni secara lebih rapi.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mt-8">
                        <a href="<?= site_url('daftar') ?>" class="btn btn-success btn-lg fw-bold">Isi Data Alumni</a>
                        <a href="<?= site_url('login') ?>" class="btn btn-light btn-lg fw-bold">Masuk Sistem</a>
                    </div>
                </div>
                <div class="tracer-stats">
                    <div class="tracer-stat">
                        <strong><?= number_format($angka('alumni'), 0, ',', '.') ?></strong>
                        <span>Alumni Terdata</span>
                    </div>
                    <div class="tracer-stat">
                        <strong><?= number_format($angka('tracer'), 0, ',', '.') ?></strong>
                        <span>Tracer Terisi</span>
                    </div>
                    <div class="tracer-stat">
                        <strong><?= number_format($angka('belum_tracer'), 0, ',', '.') ?></strong>
                        <span>Belum Mengisi</span>
                    </div>
                    <div class="tracer-stat">
                        <strong><?= number_format($angka('pengguna'), 0, ',', '.') ?></strong>
                        <span>Akun Sistem</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="tracer-section">
            <div class="tracer-wrap">
                <div class="tracer-card">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-4 mb-6">
                        <div>
                            <div class="text-muted fw-bold text-uppercase fs-8 mb-2">Ringkasan Aktivitas</div>
                            <h2 class="fw-bolder text-gray-900 mb-0">Status Alumni Setelah Lulus</h2>
                        </div>
                        <a href="<?= site_url('login') ?>" class="btn btn-light-primary fw-bold align-self-start">Kelola
                            Data</a>
                    </div>
                    <div class="tracer-activity">
                        <?php if ($aktivitas === []): ?>
                        <div class="text-muted fw-semibold py-8 text-center">Belum ada data aktivitas alumni.</div>
                        <?php else: ?>
                        <?php foreach ($aktivitas as $row): ?>
                        <div class="tracer-activity-row">
                            <span
                                class="fw-bold text-gray-800"><?= esc((string) ($row['nama_aktivitas'] ?? 'Lainnya')) ?></span>
                            <span class="badge badge-light-success fs-7"><?= (int) ($row['total'] ?? 0) ?> alumni</span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="tracer-footer">
        <div class="tracer-wrap">Tracer Study SMK Teratai Putih Global 3</div>
    </footer>

    <script>
    var hostUrl = "<?= base_url('assets/') ?>";
    </script>
    <script src="<?= base_url('assets/plugins/global/plugins.bundle.js') ?>"></script>
    <script src="<?= base_url('assets/js/scripts.bundle.js') ?>"></script>
</body>

</html>