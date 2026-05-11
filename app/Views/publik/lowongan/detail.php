<?php
/*
|-------------------------------------------------------------------
| VIEW DETAIL LOWONGAN PUBLIK
|-------------------------------------------------------------------
| Detail lowongan untuk pengunjung umum. Tombol melamar mengantar
| user ke login atau area pelamar agar validasi tetap terpusat.
*/

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

$slug = (string) ($lowongan['slug_lowongan'] ?? '');
$judulTampil = trim((string) ($lowongan['posisi'] ?? '')) !== '' ? (string) $lowongan['posisi'] : (string) ($lowongan['judul_lowongan'] ?? '-');
$lokasi = trim((string) ($lowongan['lokasi_kerja'] ?? '')) !== '' ? (string) $lowongan['lokasi_kerja'] : (string) ($lowongan['kota'] ?? '-');
$flyerUrl = ! empty($lowongan['flyer_lowongan']) ? base_url((string) $lowongan['flyer_lowongan']) : base_url('assets/media/svg/files/blank-image.svg');
$logoUrl = ! empty($lowongan['logo']) ? base_url((string) $lowongan['logo']) : '';
$isLogin = session()->get('pengguna_login') === true;
$isPelamar = in_array((string) session()->get('slug_peran'), ['pelamar_umum', 'pelamar_alumni'], true);
$slugPeran = (string) session()->get('slug_peran');
$urlMelamar = site_url('login?redirect=' . rawurlencode('pelamar/lowongan/' . $slug));
$labelMelamar = 'Melamar Sekarang';

if ($isPelamar) {
    $urlMelamar = site_url('pelamar/lowongan/' . $slug);
} elseif ($isLogin) {
    $urlMelamar = match ($slugPeran) {
        'superadmin' => site_url('dashboard/superadmin'),
        'admin_sekolah' => site_url('admin-sekolah/dashboard'),
        'admin_dudi', 'admin_perusahaan' => site_url('admin-dudi/dashboard'),
        default => site_url('login'),
    };
    $labelMelamar = 'Buka Dashboard';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= esc($title ?? 'Detail Lowongan BKK') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?= base_url('assets/media/logos/favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css">
    <style>
        :root {
            --detail-ink: #14213d;
            --detail-muted: #667085;
            --detail-line: #e4e7ec;
            --detail-leaf: #2f8f6b;
            --detail-sun: #f6b73c;
            --detail-coral: #ef6f61;
        }

        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            color: var(--detail-ink);
            background:
                radial-gradient(circle at 7% 8%, rgba(191, 232, 213, .42), transparent 24rem),
                linear-gradient(180deg, #fffdf8 0%, #f6faf7 48%, #ffffff 100%);
        }

        .detail-nav {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255, 253, 248, .86);
            border-bottom: 1px solid rgba(228, 231, 236, .85);
            backdrop-filter: blur(16px);
        }

        .detail-brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 16px;
            background:
                radial-gradient(circle at 70% 24%, rgba(255, 255, 255, .75), transparent 1rem),
                linear-gradient(135deg, var(--detail-sun), var(--detail-coral));
            box-shadow: 0 16px 35px rgba(239, 111, 97, .2);
        }

        .detail-hero {
            padding: 4.5rem 0 2rem;
        }

        .detail-title {
            font-size: clamp(2.35rem, 5vw, 4.3rem);
            line-height: 1;
            letter-spacing: -.05em;
            font-weight: 800;
        }

        .detail-kicker,
        .detail-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-weight: 800;
        }

        .detail-kicker {
            gap: .65rem;
            padding: .65rem .9rem;
            border: 1px solid rgba(47, 143, 107, .18);
            color: var(--detail-leaf);
            background: rgba(255, 255, 255, .72);
            letter-spacing: .04em;
            text-transform: uppercase;
            font-size: .75rem;
        }

        .detail-pill {
            padding: .45rem .72rem;
            background: #f3f7f4;
            color: #276b52;
            font-size: .74rem;
        }

        .detail-card,
        .detail-flyer,
        .detail-sidebar {
            border: 1px solid var(--detail-line);
            border-radius: 1.6rem;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 20px 55px rgba(20, 33, 61, .07);
        }

        .detail-flyer {
            overflow: hidden;
        }

        .detail-flyer img {
            width: 100%;
            max-height: 560px;
            object-fit: cover;
            display: block;
        }

        .detail-company-logo {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            object-fit: cover;
            background: #f3f7f4;
        }

        .detail-sidebar {
            position: sticky;
            top: 105px;
        }

        .detail-summary-item {
            padding-bottom: 1.15rem;
            border-bottom: 1px dashed var(--detail-line);
        }

        .detail-summary-item:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .detail-apply-box {
            border-radius: 1.35rem;
            background:
                radial-gradient(circle at 88% 0%, rgba(246, 183, 60, .32), transparent 11rem),
                linear-gradient(135deg, #17372c 0%, #122c45 100%);
        }
    </style>
</head>
<body>
    <header class="detail-nav">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between h-80px">
                <a href="<?= site_url('/') ?>" class="d-flex align-items-center gap-3">
                    <span class="detail-brand-mark"></span>
                    <span class="d-flex flex-column">
                        <span class="fw-bolder fs-3 text-gray-900 lh-1">BKK TP4</span>
                        <span class="fw-bold text-muted fs-8 text-uppercase">Detail Lowongan</span>
                    </span>
                </a>
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= site_url('lowongan') ?>" class="btn btn-light fw-bold">Semua Lowongan</a>
                    <?php if (! $isLogin): ?>
                        <a href="<?= site_url('login') ?>" class="btn btn-dark fw-bold">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="detail-hero">
            <div class="container">
                <div class="row align-items-end g-7">
                    <div class="col-lg-8">
                        <span class="detail-kicker mb-5">
                            <span class="bullet bullet-dot bg-success"></span>
                            Lowongan aktif
                        </span>
                        <h1 class="detail-title mb-5"><?= esc($judulTampil) ?></h1>
                        <div class="d-flex align-items-center gap-3 mb-5">
                            <?php if ($logoUrl !== ''): ?>
                                <img class="detail-company-logo" src="<?= esc($logoUrl) ?>" alt="<?= esc((string) ($lowongan['nama_perusahaan'] ?? 'Perusahaan')) ?>">
                            <?php else: ?>
                                <span class="detail-company-logo d-inline-flex align-items-center justify-content-center fw-bolder text-success">
                                    <?= esc(strtoupper(substr((string) ($lowongan['nama_perusahaan'] ?? 'D'), 0, 1))) ?>
                                </span>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bolder text-gray-900 fs-4"><?= esc((string) ($lowongan['nama_perusahaan'] ?? '-')) ?></div>
                                <div class="text-muted fw-semibold"><?= esc($lokasi) ?></div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="detail-pill"><?= esc(ucfirst((string) ($lowongan['jenis_pekerjaan'] ?? '-'))) ?></span>
                            <span class="detail-pill"><?= esc(ucfirst((string) ($lowongan['sistem_kerja'] ?? '-'))) ?></span>
                            <?php if (! empty($lowongan['pendidikan_min'])): ?>
                                <span class="detail-pill">Min. <?= esc((string) $lowongan['pendidikan_min']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="detail-apply-box p-7 text-white">
                            <div class="text-white-50 fw-bold text-uppercase fs-8 mb-2">Siap melamar?</div>
                            <div class="fw-bolder fs-2 mb-3">Masuk sebagai pelamar</div>
                            <div class="text-white-50 fw-semibold fs-7 mb-6">Setelah login, kamu bisa mengirim dokumen lamaran khusus untuk perusahaan ini.</div>
                            <a href="<?= esc($urlMelamar) ?>" class="btn btn-success w-100 fw-bold"><?= esc($labelMelamar) ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="container py-8 py-lg-12">
            <div class="row g-8">
                <div class="col-xl-8">
                    <div class="detail-flyer mb-8">
                        <img src="<?= esc($flyerUrl) ?>" alt="<?= esc((string) ($lowongan['judul_lowongan'] ?? 'Lowongan')) ?>">
                    </div>

                    <div class="detail-card p-7 p-lg-8 mb-8">
                        <h2 class="fw-bolder text-gray-900 fs-2 mb-4">Deskripsi Pekerjaan</h2>
                        <div class="text-gray-700 fw-semibold fs-6 lh-lg">
                            <?= nl2br(esc((string) (($lowongan['deskripsi_pekerjaan'] ?? '') !== '' ? $lowongan['deskripsi_pekerjaan'] : '-'))) ?>
                        </div>
                    </div>

                    <div class="detail-card p-7 p-lg-8">
                        <h2 class="fw-bolder text-gray-900 fs-2 mb-4">Kualifikasi</h2>
                        <div class="text-gray-700 fw-semibold fs-6 lh-lg">
                            <?= nl2br(esc((string) (($lowongan['kualifikasi'] ?? '') !== '' ? $lowongan['kualifikasi'] : '-'))) ?>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <aside class="detail-sidebar p-7">
                        <h2 class="fw-bolder text-gray-900 fs-2 mb-6">Ringkasan</h2>
                        <div class="d-flex flex-column gap-5 mb-7">
                            <div class="detail-summary-item">
                                <div class="text-muted fs-8 text-uppercase fw-bold mb-1">DUDI</div>
                                <div class="fw-bolder text-gray-900"><?= esc((string) ($lowongan['nama_perusahaan'] ?? '-')) ?></div>
                            </div>
                            <div class="detail-summary-item">
                                <div class="text-muted fs-8 text-uppercase fw-bold mb-1">Lokasi</div>
                                <div class="fw-bolder text-gray-900"><?= esc($lokasi) ?></div>
                            </div>
                            <div class="detail-summary-item">
                                <div class="text-muted fs-8 text-uppercase fw-bold mb-1">Batas Lamaran</div>
                                <div class="fw-bolder text-gray-900"><?= esc($formatTanggal($lowongan['batas_lamaran'] ?? null)) ?></div>
                            </div>
                            <div class="detail-summary-item">
                                <div class="text-muted fs-8 text-uppercase fw-bold mb-1">Kebutuhan</div>
                                <div class="fw-bolder text-gray-900"><?= esc((string) (($lowongan['jumlah_kebutuhan'] ?? '') !== '' ? $lowongan['jumlah_kebutuhan'] : '-')) ?> orang</div>
                            </div>
                            <div class="detail-summary-item">
                                <div class="text-muted fs-8 text-uppercase fw-bold mb-1">Gaji</div>
                                <div class="fw-bolder text-gray-900"><?= esc((string) (($lowongan['rentang_gaji'] ?? '') !== '' ? $lowongan['rentang_gaji'] : '-')) ?></div>
                            </div>
                            <div class="detail-summary-item">
                                <div class="text-muted fs-8 text-uppercase fw-bold mb-1">Pengalaman</div>
                                <div class="fw-bolder text-gray-900"><?= esc((string) (($lowongan['pengalaman_min'] ?? '') !== '' ? $lowongan['pengalaman_min'] : 'Tidak ditentukan')) ?></div>
                            </div>
                        </div>

                        <div class="notice d-flex bg-light-success rounded border-success border border-dashed p-5 mb-6">
                            <div class="fw-semibold text-gray-700 fs-7">
                                CV dan surat lamaran akan diunggah dari dashboard pelamar agar dokumen tercatat rapi.
                            </div>
                        </div>

                        <a href="<?= esc($urlMelamar) ?>" class="btn btn-dark w-100 fw-bold mb-3"><?= esc($labelMelamar) ?></a>
                        <a href="<?= site_url('lowongan') ?>" class="btn btn-light w-100 fw-bold">Kembali ke Daftar</a>
                    </aside>
                </div>
            </div>
        </section>
    </main>

    <footer class="container pb-8">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top pt-6">
            <div class="text-muted fw-semibold fs-7">BKK & Tracer Study SMK Teratai Putih Global 4 Kota Bekasi</div>
            <div class="d-flex gap-5 fw-bold fs-7">
                <a href="<?= site_url('/') ?>" class="text-muted text-hover-success">Beranda</a>
                <a href="<?= site_url('lowongan') ?>" class="text-muted text-hover-success">Lowongan</a>
                <a href="<?= site_url('login') ?>" class="text-muted text-hover-success">Login</a>
            </div>
        </div>
    </footer>

    <script>var hostUrl = "<?= base_url('assets/') ?>";</script>
    <script src="<?= base_url('assets/plugins/global/plugins.bundle.js') ?>"></script>
    <script src="<?= base_url('assets/js/scripts.bundle.js') ?>"></script>
</body>
</html>
