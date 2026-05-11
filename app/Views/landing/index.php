<?php
/*
|-------------------------------------------------------------------
| VIEW LANDING PAGE PUBLIK BKK & TRACER STUDY
|-------------------------------------------------------------------
| Landing page publik dengan arah visual mendekati versi awal:
| clean, Metronic-friendly, informatif, dan menonjolkan lowongan aktif.
*/

$filters = is_array($filters ?? null) ? $filters : [];
$statistik = is_array($statistik ?? null) ? $statistik : [];
$lowongan = is_array($lowongan ?? null) ? $lowongan : [];
$lowonganHero = array_slice($lowongan, 0, 3);
$daftarKota = is_array($daftarKota ?? null) ? $daftarKota : [];
$isLogin = session()->get('pengguna_login') === true;
$slugPeran = (string) session()->get('slug_peran');

$dashboardUrl = match ($slugPeran) {
    'superadmin' => site_url('dashboard/superadmin'),
    'admin_sekolah' => site_url('admin-sekolah/dashboard'),
    'admin_dudi', 'admin_perusahaan' => site_url('admin-dudi/dashboard'),
    'pelamar_umum', 'pelamar_alumni' => site_url('pelamar/dashboard'),
    default => site_url('login'),
};

$formatTanggal = static function (?string $tanggal): string {
    if ($tanggal === null || trim($tanggal) === '') {
        return 'Fleksibel';
    }

    try {
        return (new DateTime($tanggal))->format('d M Y');
    } catch (Throwable) {
        return (string) $tanggal;
    }
};

$jenisPekerjaan = [
    'fulltime' => 'Fulltime',
    'parttime' => 'Parttime',
    'magang' => 'Magang',
    'kontrak' => 'Kontrak',
    'freelance' => 'Freelance',
];

$statCards = [
    ['label' => 'Pelamar Terdaftar', 'value' => (int) ($statistik['pelamar'] ?? 0), 'icon' => 'ki-profile-user', 'class' => 'text-primary'],
    ['label' => 'Alumni', 'value' => (int) ($statistik['alumni'] ?? 0), 'icon' => 'ki-teacher', 'class' => 'text-success'],
    ['label' => 'Lowongan Aktif', 'value' => (int) ($statistik['lowongan'] ?? 0), 'icon' => 'ki-briefcase', 'class' => 'text-warning'],
    ['label' => 'DUDI Mitra', 'value' => (int) ($statistik['dudi'] ?? 0), 'icon' => 'ki-office-bag', 'class' => 'text-info'],
];

$blankFlyerUrl = base_url('assets/media/svg/files/blank-image.svg');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <base href="">
    <title><?= esc($title ?? 'BKK & Tracer Study') ?></title>
    <meta charset="utf-8">
    <meta name="description" content="Sistem Bursa Kerja Khusus dan Tracer Study SMK Teratai Putih Global 4 Kota Bekasi.">
    <meta name="keywords" content="BKK, tracer study, lowongan kerja, alumni, DUDI, SMK">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?= base_url('assets/media/logos/favicon.ico') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700">
    <link href="<?= base_url('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css">
    <style>
        :root {
            --bkk-navy: #081426;
            --bkk-blue: #1b84ff;
            --bkk-cyan: #37d5ff;
            --bkk-green: #50cd89;
            --bkk-soft: #f5f8fb;
            --bkk-orange: #ff8a1f;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: var(--bkk-soft);
        }

        .bkk-landing-hero {
            background:
                radial-gradient(circle at 82% 18%, rgba(55, 213, 255, 0.23), transparent 30%),
                radial-gradient(circle at 10% 88%, rgba(80, 205, 137, 0.20), transparent 34%),
                linear-gradient(135deg, #07111f 0%, #0b2443 50%, #0f3d69 100%);
            position: relative;
            overflow: hidden;
        }

        .bkk-landing-hero::after {
            content: "";
            position: absolute;
            width: 520px;
            height: 520px;
            right: -180px;
            bottom: -260px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
        }

        .bkk-landing-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.09);
            background: rgba(8, 20, 38, 0.34);
            backdrop-filter: blur(12px);
            position: relative;
            z-index: 5;
        }

        .bkk-logo-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--bkk-orange), #ffc75a);
            box-shadow: 0 14px 28px rgba(255, 138, 31, .28);
        }

        .bkk-nav a {
            color: rgba(255, 255, 255, .68);
            transition: color .18s ease;
        }

        .bkk-nav a:hover {
            color: #fff;
        }

        .bkk-mobile-menu {
            display: none;
            border-top: 1px solid rgba(255, 255, 255, .1);
            background: rgba(8, 20, 38, .96);
        }

        .bkk-mobile-menu.is-open {
            display: block;
        }

        .bkk-glass-card {
            border: 1px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .1);
            box-shadow: 0 28px 80px rgba(0, 0, 0, .18);
            backdrop-filter: blur(12px);
            border-radius: 1.5rem;
        }

        .bkk-hero-job {
            border: 1px solid rgba(255, 255, 255, .16);
            background: rgba(255, 255, 255, .1);
            border-radius: 1rem;
            transition: transform .18s ease, background .18s ease;
        }

        .bkk-hero-job:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, .15);
        }

        .bkk-hero-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .16);
            color: #fff;
            flex: 0 0 auto;
        }

        .bkk-stat-card,
        .bkk-filter-card,
        .bkk-job-card,
        .bkk-step-card {
            border: 0;
            border-radius: 1.25rem;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }

        .bkk-stat-card {
            overflow: hidden;
            position: relative;
        }

        .bkk-stat-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--bkk-blue), var(--bkk-green));
        }

        .bkk-section-soft {
            background: linear-gradient(180deg, #f5f8fb 0%, #eef5ff 100%);
        }

        .bkk-job-card {
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .bkk-job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 26px 65px rgba(15, 23, 42, .13);
        }

        .bkk-job-thumb {
            height: 180px;
            background: #eef4ff;
            overflow: hidden;
        }

        .bkk-job-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .bkk-cta {
            background:
                radial-gradient(circle at 86% 12%, rgba(255, 138, 31, .28), transparent 22%),
                linear-gradient(135deg, #0b2443 0%, #081426 100%);
            border-radius: 1.75rem;
            overflow: hidden;
        }

        .bkk-footer {
            background: #07111f;
        }
    </style>
</head>
<body id="kt_body" class="app-blank">
    <script>
        var defaultThemeMode = "light";
        var themeMode = localStorage.getItem("data-bs-theme") || defaultThemeMode;
        document.documentElement.setAttribute("data-bs-theme", themeMode);
    </script>

    <div class="bkk-landing-hero" id="home">
        <header class="bkk-landing-header">
            <div class="container">
                <div class="d-flex align-items-center justify-content-between h-80px">
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-icon btn-active-color-primary me-3 d-flex d-lg-none" id="bkk_mobile_toggle" aria-label="Buka menu">
                            <i class="ki-duotone ki-abstract-14 fs-2hx text-white">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </button>
                        <a href="<?= site_url('/') ?>" class="d-flex align-items-center gap-3">
                            <span class="bkk-logo-mark d-inline-flex"></span>
                            <span class="d-flex flex-column">
                                <span class="text-white fw-bolder fs-3 lh-1">BKK Teratai Putih</span>
                                <span class="text-white-50 fw-semibold fs-8 text-uppercase">Tracer Study & Karier</span>
                            </span>
                        </a>
                    </div>

                    <nav class="bkk-nav d-none d-lg-flex align-items-center gap-8 fw-semibold fs-6">
                        <a href="#home">Beranda</a>
                        <a href="#lowongan">Lowongan</a>
                        <a href="#statistik">Statistik</a>
                        <a href="#alur">Alur</a>
                    </nav>

                    <div class="d-flex align-items-center gap-3">
                        <a href="<?= $isLogin ? esc($dashboardUrl) : site_url('login') ?>" class="btn btn-light-success fw-bold">
                            <?= $isLogin ? 'Dashboard' : 'Sign In' ?>
                        </a>
                        <?php if (! $isLogin): ?>
                            <a href="<?= site_url('daftar') ?>" class="btn btn-primary fw-bold d-none d-sm-inline-flex">Daftar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="bkk-mobile-menu" id="bkk_mobile_menu">
                <div class="container py-5">
                    <div class="d-flex flex-column gap-4 fw-semibold fs-6">
                        <a href="#home" class="text-white">Beranda</a>
                        <a href="#lowongan" class="text-white">Lowongan</a>
                        <a href="#statistik" class="text-white">Statistik</a>
                        <a href="#alur" class="text-white">Alur</a>
                        <a href="<?= site_url('daftar') ?>" class="btn btn-primary fw-bold">Daftar Pelamar</a>
                    </div>
                </div>
            </div>
        </header>

        <section class="container position-relative py-15 py-lg-20" style="z-index: 2;">
            <div class="row align-items-center g-10">
                <div class="col-lg-7">
                    <span class="badge badge-light-success mb-5">Bursa Kerja Khusus & Tracer Study</span>
                    <h1 class="text-white fw-bolder fs-2hx fs-lg-3x mb-6">
                        Temukan lowongan aktif dan kelola perjalanan karier alumni.
                    </h1>
                    <div class="text-white-50 fs-4 fw-semibold mb-9">
                        Portal BKK SMK Teratai Putih Global 4 untuk mencari lowongan kerja terbaru, mengirim lamaran, dan mengisi tracer alumni secara lebih tertata.
                    </div>
                    <div class="d-flex flex-wrap gap-4">
                        <a href="#lowongan" class="btn btn-primary btn-lg fw-bold">Cari Lowongan</a>
                        <a href="<?= site_url('daftar') ?>" class="btn btn-light btn-lg fw-bold">Daftar Pelamar</a>
                        <a href="<?= site_url('lowongan') ?>" class="btn btn-light-success btn-lg fw-bold">Semua Lowongan</a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="bkk-glass-card p-7">
                        <div class="d-flex align-items-center justify-content-between mb-7">
                            <div>
                                <div class="text-white-50 fw-bold fs-8 text-uppercase mb-1">Lowongan Terbaru</div>
                                <div class="text-white fw-bolder fs-2">Aktif saat ini</div>
                            </div>
                            <span class="symbol symbol-50px">
                                <span class="symbol-label bg-white bg-opacity-10">
                                    <i class="ki-duotone ki-briefcase text-white fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </span>
                        </div>

                        <div class="d-flex flex-column gap-3 mb-7">
                            <?php if ($lowonganHero === []): ?>
                                <div class="bkk-hero-job p-5 text-center">
                                    <div class="text-white fw-bold mb-1">Belum ada lowongan aktif</div>
                                    <div class="text-white-50 fw-semibold fs-7">Lowongan terbaru akan tampil di panel ini.</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($lowonganHero as $item): ?>
                                    <?php
                                    $judulHero = trim((string) ($item['posisi'] ?? '')) !== '' ? (string) $item['posisi'] : (string) ($item['judul_lowongan'] ?? '-');
                                    $lokasiHero = trim((string) ($item['lokasi_kerja'] ?? '')) !== '' ? (string) $item['lokasi_kerja'] : (string) ($item['kota'] ?? '-');
                                    ?>
                                    <a href="<?= site_url('lowongan/' . (string) ($item['slug_lowongan'] ?? '')) ?>" class="bkk-hero-job p-4 d-flex align-items-center gap-4">
                                        <span class="bkk-hero-icon">
                                            <i class="ki-duotone ki-briefcase fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="flex-grow-1 min-w-0">
                                            <span class="d-block text-white fw-bold text-truncate"><?= esc($judulHero) ?></span>
                                            <span class="d-block text-white-50 fw-semibold fs-8 text-truncate"><?= esc((string) ($item['nama_perusahaan'] ?? '-')) ?> · <?= esc($lokasiHero) ?></span>
                                        </span>
                                        <span class="badge badge-light-success">Aktif</span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <a href="<?= site_url('lowongan') ?>" class="btn btn-light w-100 fw-bold">Lihat Semua Lowongan Aktif</a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <section class="container mt-n10 position-relative" id="statistik" style="z-index: 3;">
        <div class="row g-5">
            <?php foreach ($statCards as $stat): ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="card bkk-stat-card h-100">
                        <div class="card-body p-6">
                            <div class="symbol symbol-45px mb-5">
                                <span class="symbol-label bg-light">
                                    <i class="ki-duotone <?= esc($stat['icon']) ?> fs-2 <?= esc($stat['class']) ?>">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="text-gray-900 fw-bolder fs-2hx lh-1 mb-2"><?= (int) $stat['value'] ?></div>
                            <div class="text-muted fw-semibold"><?= esc($stat['label']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="bkk-section-soft py-15" id="lowongan">
        <div class="container">
            <div class="text-center mb-10">
                <span class="badge badge-light-primary mb-4">Lowongan Aktif</span>
                <h2 class="fw-bolder text-gray-900 fs-2hx mb-3">Peluang kerja terbaru dari DUDI mitra</h2>
                <div class="text-muted fs-5">Cari posisi, perusahaan, atau kota tujuan dengan filter sederhana.</div>
            </div>

            <div class="card bkk-filter-card mb-10">
                <div class="card-body p-6">
                    <form method="get" action="<?= site_url('/') ?>" class="row g-4 align-items-end">
                        <div class="col-lg-5">
                            <label class="form-label fw-semibold">Pencarian</label>
                            <input type="text" name="q" class="form-control form-control-solid" placeholder="Judul, posisi, perusahaan, lokasi" value="<?= esc((string) ($filters['search'] ?? '')) ?>">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label fw-semibold">Kota</label>
                            <select name="kota" class="form-select form-select-solid">
                                <option value="">Semua Kota</option>
                                <?php foreach ($daftarKota as $kota): ?>
                                    <option value="<?= esc($kota, 'attr') ?>" <?= ($filters['kota'] ?? '') === $kota ? 'selected' : '' ?>><?= esc($kota) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Jenis</label>
                            <select name="jenis_pekerjaan" class="form-select form-select-solid">
                                <option value="">Semua</option>
                                <?php foreach ($jenisPekerjaan as $value => $label): ?>
                                    <option value="<?= esc($value, 'attr') ?>" <?= ($filters['jenis_pekerjaan'] ?? '') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">Cari</button>
                            <a href="<?= site_url('/') ?>" class="btn btn-light">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-6">
                <?php if ($lowongan === []): ?>
                    <div class="col-12">
                        <div class="card border-0 rounded-4">
                            <div class="card-body text-center py-15 text-muted">
                                Belum ada lowongan aktif yang cocok dengan filter saat ini.
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($lowongan as $item): ?>
                        <?php
                        $flyerUrl = ! empty($item['flyer_lowongan']) ? base_url((string) $item['flyer_lowongan']) : $blankFlyerUrl;
                        $judulTampil = trim((string) ($item['posisi'] ?? '')) !== '' ? (string) $item['posisi'] : (string) ($item['judul_lowongan'] ?? '-');
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card bkk-job-card h-100">
                                <div class="bkk-job-thumb">
                                    <img src="<?= esc($flyerUrl) ?>" alt="<?= esc((string) ($item['judul_lowongan'] ?? 'Lowongan')) ?>">
                                </div>
                                <div class="card-body p-6">
                                    <div class="d-flex flex-wrap gap-2 mb-4">
                                        <span class="badge badge-light-primary"><?= esc(ucfirst((string) ($item['jenis_pekerjaan'] ?? '-'))) ?></span>
                                        <span class="badge badge-light-success"><?= esc(ucfirst((string) ($item['sistem_kerja'] ?? '-'))) ?></span>
                                    </div>
                                    <h3 class="fw-bolder text-gray-900 fs-4 mb-2"><?= esc($judulTampil) ?></h3>
                                    <div class="text-muted fw-semibold mb-4"><?= esc((string) ($item['nama_perusahaan'] ?? '-')) ?></div>
                                    <div class="d-flex flex-column gap-2 text-gray-700 fs-7 mb-6">
                                        <div>Lokasi: <?= esc((string) (($item['lokasi_kerja'] ?? '') !== '' ? $item['lokasi_kerja'] : ($item['kota'] ?? '-'))) ?></div>
                                        <div>Batas Lamaran: <?= esc($formatTanggal($item['batas_lamaran'] ?? null)) ?></div>
                                        <div>Gaji: <?= esc((string) (($item['rentang_gaji'] ?? '') !== '' ? $item['rentang_gaji'] : '-')) ?></div>
                                    </div>
                                    <a href="<?= site_url('lowongan/' . (string) ($item['slug_lowongan'] ?? '')) ?>" class="btn btn-primary w-100">Lihat Detail</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="container py-15" id="alur">
        <div class="text-center mb-12">
            <span class="badge badge-light-success mb-4">Alur Sistem</span>
            <h2 class="fw-bolder text-gray-900 fs-2hx mb-3">Dari daftar akun sampai lamaran terkirim</h2>
            <div class="text-muted fs-5">Aplikasi menjaga alur pelamar tetap jelas dan mudah dipantau BKK.</div>
        </div>
        <div class="row g-6">
            <?php foreach ([
                ['title' => 'Daftar Akun', 'text' => 'Pelamar umum atau alumni membuat akun dan bisa login ke dashboard awal.'],
                ['title' => 'Review BKK', 'text' => 'Admin BKK menyetujui akun sebelum menu pelamar dibuka penuh.'],
                ['title' => 'Lengkapi Profil', 'text' => 'Pelamar menyiapkan data diri, berkas profil, dan tracer khusus alumni.'],
                ['title' => 'Kirim Lamaran', 'text' => 'Pelamar memilih lowongan aktif dan mengirim dokumen lamaran.'],
            ] as $index => $step): ?>
                <div class="col-md-6 col-xl-3">
                    <div class="card bkk-step-card h-100">
                        <div class="card-body p-7">
                            <div class="symbol symbol-45px mb-5">
                                <span class="symbol-label bg-light-primary text-primary fw-bolder"><?= $index + 1 ?></span>
                            </div>
                            <h3 class="fw-bolder text-gray-900 mb-3"><?= esc($step['title']) ?></h3>
                            <div class="text-muted fw-semibold fs-6"><?= esc($step['text']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="container pb-15">
        <div class="bkk-cta p-8 p-lg-12">
            <div class="row align-items-center g-8">
                <div class="col-lg-8">
                    <span class="badge badge-light-success mb-4">BKK Teratai Putih</span>
                    <h2 class="text-white fw-bolder fs-2hx mb-4">Siap mencari peluang kerja terbaru?</h2>
                    <div class="text-white-50 fs-5 fw-semibold">Lihat lowongan aktif dari DUDI mitra, daftar sebagai pelamar, lalu ikuti proses review BKK.</div>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex flex-column gap-3">
                        <a href="<?= site_url('lowongan') ?>" class="btn btn-primary btn-lg fw-bold">Lihat Semua Lowongan</a>
                        <a href="<?= site_url('daftar') ?>" class="btn btn-light btn-lg fw-bold">Daftar Pelamar</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bkk-footer py-8">
        <div class="container">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="bkk-logo-mark"></span>
                    <div>
                        <div class="text-white fw-bolder">BKK & Tracer Study</div>
                        <div class="text-white-50 fw-semibold fs-7">SMK Teratai Putih Global 4 Kota Bekasi</div>
                    </div>
                </div>
                <div class="d-flex flex-wrap justify-content-center gap-5 fw-bold">
                    <a href="#lowongan" class="text-white-50 text-hover-white">Lowongan</a>
                    <a href="#statistik" class="text-white-50 text-hover-white">Statistik</a>
                    <a href="<?= site_url('login') ?>" class="text-white-50 text-hover-white">Login</a>
                    <a href="<?= site_url('daftar') ?>" class="text-white-50 text-hover-white">Daftar</a>
                </div>
            </div>
        </div>
    </footer>

    <script>var hostUrl = "<?= base_url('assets/') ?>";</script>
    <script src="<?= base_url('assets/plugins/global/plugins.bundle.js') ?>"></script>
    <script src="<?= base_url('assets/js/scripts.bundle.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('bkk_mobile_toggle');
            var mobileMenu = document.getElementById('bkk_mobile_menu');

            if (!toggle || !mobileMenu) {
                return;
            }

            toggle.addEventListener('click', function () {
                mobileMenu.classList.toggle('is-open');
            });

            mobileMenu.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    mobileMenu.classList.remove('is-open');
                });
            });
        });
    </script>
</body>
</html>
