<?php
/*
|-------------------------------------------------------------------
| VIEW LANDING LOWONGAN PUBLIK
|-------------------------------------------------------------------
| Etalase utama lowongan aktif untuk pengunjung umum. Fokus halaman
| ini adalah membuat lowongan terbaru cepat terbaca dan mudah dicari.
*/

$lowongan = is_array($lowongan ?? null) ? $lowongan : [];
$keyword = trim((string) ($keyword ?? ''));
$totalAktif = (int) ($totalAktif ?? count($lowongan));
$totalHasilCari = (int) ($totalHasilCari ?? count($lowongan));

$formatTanggal = static function (?string $tanggal): string {
    if ($tanggal === null || trim($tanggal) === '') {
        return 'Tidak dibatasi';
    }

    try {
        return (new DateTime($tanggal))->format('d M Y');
    } catch (Throwable) {
        return (string) $tanggal;
    }
};

$hariTersisa = static function (?string $tanggal): ?int {
    if ($tanggal === null || trim($tanggal) === '') {
        return null;
    }

    try {
        $today = new DateTimeImmutable(date('Y-m-d'));
        $deadline = new DateTimeImmutable((new DateTime($tanggal))->format('Y-m-d'));
        return max(0, (int) $today->diff($deadline)->format('%r%a'));
    } catch (Throwable) {
        return null;
    }
};

$ringkas = static function (?string $teks, int $limit = 120): string {
    $clean = trim(preg_replace('/\s+/', ' ', strip_tags((string) $teks)) ?? '');
    if ($clean === '') {
        return 'Detail lowongan tersedia di halaman informasi pekerjaan.';
    }

    if (function_exists('mb_strlen') && mb_strlen($clean) > $limit) {
        return mb_substr($clean, 0, $limit - 3) . '...';
    }

    if (strlen($clean) > $limit) {
        return substr($clean, 0, $limit - 3) . '...';
    }

    return $clean;
};

$blankFlyerUrl = base_url('assets/media/svg/files/blank-image.svg');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= esc($title ?? 'Lowongan Kerja BKK') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Daftar lowongan kerja aktif dari DUDI mitra BKK SMK Teratai Putih Global 4.">
    <link rel="shortcut icon" href="<?= base_url('assets/media/logos/favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css">
    <style>
        :root {
            --job-ink: #14213d;
            --job-muted: #667085;
            --job-line: #e4e7ec;
            --job-paper: #fffdf8;
            --job-leaf: #2f8f6b;
            --job-sun: #f6b73c;
            --job-coral: #ef6f61;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            color: var(--job-ink);
            background:
                radial-gradient(circle at 4% 8%, rgba(191, 232, 213, .42), transparent 24rem),
                radial-gradient(circle at 95% 4%, rgba(246, 183, 60, .25), transparent 26rem),
                linear-gradient(180deg, #fffdf8 0%, #f6faf7 48%, #ffffff 100%);
        }

        .job-nav {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255, 253, 248, .86);
            border-bottom: 1px solid rgba(228, 231, 236, .85);
            backdrop-filter: blur(16px);
        }

        .job-brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 16px;
            background:
                radial-gradient(circle at 70% 24%, rgba(255, 255, 255, .75), transparent 1rem),
                linear-gradient(135deg, var(--job-sun), var(--job-coral));
            box-shadow: 0 16px 35px rgba(239, 111, 97, .2);
        }

        .job-hero {
            padding: 4.5rem 0 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .job-hero::after {
            content: "";
            position: absolute;
            right: -14rem;
            top: -8rem;
            width: 34rem;
            height: 34rem;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(47, 143, 107, .22), transparent 68%);
            pointer-events: none;
        }

        .job-title {
            font-size: clamp(2.5rem, 6vw, 5rem);
            line-height: .98;
            letter-spacing: -.055em;
            font-weight: 800;
        }

        .job-title span {
            color: var(--job-leaf);
        }

        .job-kicker {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            padding: .65rem .9rem;
            border: 1px solid rgba(47, 143, 107, .18);
            border-radius: 999px;
            color: var(--job-leaf);
            background: rgba(255, 255, 255, .72);
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            font-size: .75rem;
        }

        .job-search-card,
        .job-card,
        .job-empty-card {
            border: 1px solid var(--job-line);
            border-radius: 1.6rem;
            background: rgba(255, 255, 255, .9);
            box-shadow: 0 20px 55px rgba(20, 33, 61, .07);
        }

        .job-search-card {
            position: relative;
            z-index: 2;
            margin-top: 2rem;
        }

        .job-card {
            overflow: hidden;
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .job-card:hover {
            transform: translateY(-6px);
            border-color: rgba(47, 143, 107, .32);
            box-shadow: 0 28px 70px rgba(20, 33, 61, .13);
        }

        .job-thumb {
            height: 190px;
            background:
                linear-gradient(135deg, rgba(191, 232, 213, .55), rgba(246, 183, 60, .2)),
                #f2f6f3;
            overflow: hidden;
            position: relative;
        }

        .job-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .job-status-pill {
            position: absolute;
            left: 1rem;
            top: 1rem;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem .75rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .92);
            color: var(--job-leaf);
            font-weight: 800;
            font-size: .75rem;
        }

        .job-pill {
            display: inline-flex;
            align-items: center;
            padding: .45rem .72rem;
            border-radius: 999px;
            background: #f3f7f4;
            color: #276b52;
            font-weight: 800;
            font-size: .74rem;
        }

        .job-company-logo {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            object-fit: cover;
            background: #f3f7f4;
        }

        .job-soft-panel {
            border-radius: 1.5rem;
            background:
                radial-gradient(circle at 88% 0%, rgba(246, 183, 60, .32), transparent 11rem),
                linear-gradient(135deg, #17372c 0%, #122c45 100%);
        }

        @media (max-width: 991.98px) {
            .job-hero {
                padding-top: 3.25rem;
            }
        }
    </style>
</head>
<body>
    <header class="job-nav">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between h-80px">
                <a href="<?= site_url('/') ?>" class="d-flex align-items-center gap-3">
                    <span class="job-brand-mark"></span>
                    <span class="d-flex flex-column">
                        <span class="fw-bolder fs-3 text-gray-900 lh-1">BKK TP4</span>
                        <span class="fw-bold text-muted fs-8 text-uppercase">Lowongan Kerja</span>
                    </span>
                </a>
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= site_url('/') ?>" class="btn btn-light fw-bold">Beranda</a>
                    <a href="<?= site_url('login') ?>" class="btn btn-dark fw-bold">Sign In</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="job-hero">
            <div class="container position-relative" style="z-index: 2;">
                <div class="row align-items-end g-8">
                    <div class="col-lg-8">
                        <span class="job-kicker mb-5">
                            <span class="bullet bullet-dot bg-success"></span>
                            Etalase lowongan aktif
                        </span>
                        <h1 class="job-title mb-5">Cari lowongan terbaru dari <span>DUDI mitra</span>.</h1>
                        <p class="text-muted fw-semibold fs-4 mw-lg-700px mb-0">
                            Semua lowongan di halaman ini masih aktif dan bisa dibaca pengunjung sebelum login.
                            Login dibutuhkan saat kamu ingin mengirim lamaran.
                        </p>
                    </div>
                    <div class="col-lg-4">
                        <div class="job-soft-panel p-7 text-white">
                            <div class="text-white-50 fw-bold text-uppercase fs-8 mb-2">Tersedia saat ini</div>
                            <div class="fw-bolder display-5 mb-1"><?= $totalAktif ?></div>
                            <div class="text-white-50 fw-semibold">lowongan aktif siap dilihat</div>
                            <?php if ($keyword !== ''): ?>
                                <div class="separator border-white border-opacity-25 my-5"></div>
                                <div class="fw-bolder fs-4"><?= $totalHasilCari ?> hasil</div>
                                <div class="text-white-50 fw-semibold fs-7">untuk pencarian "<?= esc($keyword) ?>"</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="job-search-card p-5 p-lg-6">
                    <form method="get" action="<?= site_url('lowongan') ?>" class="row g-3 align-items-center">
                        <div class="col-lg">
                            <div class="position-relative">
                                <i class="ki-duotone ki-magnifier fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-5">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" name="q" class="form-control form-control-lg form-control-solid ps-13" placeholder="Cari posisi, perusahaan, atau lokasi" value="<?= esc($keyword) ?>">
                            </div>
                        </div>
                        <div class="col-lg-auto d-flex gap-2">
                            <button class="btn btn-success btn-lg fw-bold px-8" type="submit">Cari</button>
                            <?php if ($keyword !== ''): ?>
                                <a href="<?= site_url('lowongan') ?>" class="btn btn-light btn-lg fw-bold">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="container py-8 py-lg-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-4 mb-7">
                <div>
                    <h2 class="fw-bolder text-gray-900 fs-2hx mb-2">Lowongan aktif</h2>
                    <div class="text-muted fw-semibold">
                        <?= $keyword !== '' ? esc((string) $totalHasilCari) . ' hasil ditemukan.' : 'Peluang terbaru yang bisa dilamar melalui sistem BKK.' ?>
                    </div>
                </div>
                <div class="text-muted fw-bold fs-7">Diurutkan dari deadline terdekat.</div>
            </div>

            <div class="row g-5">
                <?php if ($lowongan === []): ?>
                    <div class="col-12">
                        <div class="job-empty-card p-10 p-lg-15 text-center">
                            <div class="symbol symbol-70px mx-auto mb-6">
                                <span class="symbol-label bg-light-success">
                                    <i class="ki-duotone ki-magnifier fs-2x text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <h3 class="fw-bolder text-gray-900 fs-2 mb-3">Belum ada lowongan yang cocok</h3>
                            <div class="text-muted fw-semibold mb-7">Coba gunakan kata kunci lain, atau kembali ke semua lowongan aktif.</div>
                            <a href="<?= site_url('lowongan') ?>" class="btn btn-success fw-bold">Lihat Semua Lowongan</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($lowongan as $item): ?>
                        <?php
                        $flyerUrl = ! empty($item['flyer_lowongan']) ? base_url((string) $item['flyer_lowongan']) : $blankFlyerUrl;
                        $logoUrl = ! empty($item['logo']) ? base_url((string) $item['logo']) : '';
                        $judulTampil = trim((string) ($item['posisi'] ?? '')) !== '' ? (string) $item['posisi'] : (string) ($item['judul_lowongan'] ?? '-');
                        $lokasi = trim((string) ($item['lokasi_kerja'] ?? '')) !== '' ? (string) $item['lokasi_kerja'] : (string) ($item['kota'] ?? '-');
                        $sisa = $hariTersisa($item['batas_lamaran'] ?? null);
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <article class="job-card h-100">
                                <div class="job-thumb">
                                    <img src="<?= esc($flyerUrl) ?>" alt="<?= esc((string) ($item['judul_lowongan'] ?? 'Lowongan')) ?>">
                                    <span class="job-status-pill">
                                        <span class="bullet bullet-dot bg-success"></span>
                                        Aktif
                                    </span>
                                </div>
                                <div class="p-6">
                                    <div class="d-flex align-items-center gap-3 mb-5">
                                        <?php if ($logoUrl !== ''): ?>
                                            <img class="job-company-logo" src="<?= esc($logoUrl) ?>" alt="<?= esc((string) ($item['nama_perusahaan'] ?? 'Perusahaan')) ?>">
                                        <?php else: ?>
                                            <span class="job-company-logo d-inline-flex align-items-center justify-content-center fw-bolder text-success">
                                                <?= esc(strtoupper(substr((string) ($item['nama_perusahaan'] ?? 'D'), 0, 1))) ?>
                                            </span>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <div class="fw-bolder text-gray-900 text-truncate"><?= esc((string) ($item['nama_perusahaan'] ?? '-')) ?></div>
                                            <div class="text-muted fw-semibold fs-8 text-truncate"><?= esc($lokasi) ?></div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mb-4">
                                        <span class="job-pill"><?= esc(ucfirst((string) ($item['jenis_pekerjaan'] ?? '-'))) ?></span>
                                        <span class="job-pill"><?= esc(ucfirst((string) ($item['sistem_kerja'] ?? '-'))) ?></span>
                                    </div>

                                    <h3 class="fw-bolder text-gray-900 fs-3 mb-3"><?= esc($judulTampil) ?></h3>
                                    <div class="text-muted fw-semibold fs-7 mb-6"><?= esc($ringkas($item['deskripsi_pekerjaan'] ?? null)) ?></div>

                                    <div class="d-flex flex-column gap-3 text-gray-700 fw-semibold fs-7 mb-6">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ki-duotone ki-calendar fs-4 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <span>
                                                Batas <?= esc($formatTanggal($item['batas_lamaran'] ?? null)) ?>
                                                <?= $sisa !== null ? '(' . $sisa . ' hari lagi)' : '' ?>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ki-duotone ki-wallet fs-4 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <span><?= esc((string) (($item['rentang_gaji'] ?? '') !== '' ? $item['rentang_gaji'] : 'Gaji tidak ditampilkan')) ?></span>
                                        </div>
                                    </div>

                                    <a href="<?= site_url('lowongan/' . (string) ($item['slug_lowongan'] ?? '')) ?>" class="btn btn-dark w-100 fw-bold">Lihat Detail Lowongan</a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="container pb-8">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top pt-6">
            <div class="text-muted fw-semibold fs-7">BKK & Tracer Study SMK Teratai Putih Global 4 Kota Bekasi</div>
            <div class="d-flex gap-5 fw-bold fs-7">
                <a href="<?= site_url('/') ?>" class="text-muted text-hover-success">Beranda</a>
                <a href="<?= site_url('login') ?>" class="text-muted text-hover-success">Login</a>
                <a href="<?= site_url('daftar') ?>" class="text-muted text-hover-success">Daftar</a>
            </div>
        </div>
    </footer>

    <script>var hostUrl = "<?= base_url('assets/') ?>";</script>
    <script src="<?= base_url('assets/plugins/global/plugins.bundle.js') ?>"></script>
    <script src="<?= base_url('assets/js/scripts.bundle.js') ?>"></script>
</body>
</html>
