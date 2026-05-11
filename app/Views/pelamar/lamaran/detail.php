<?php
/*
|-------------------------------------------------------------------
| VIEW DETAIL LAMARAN PELAMAR
|-------------------------------------------------------------------
| Halaman ini membantu pelamar memahami status lamaran, melihat
| dokumen snapshot yang dikirim, membaca catatan reviewer, dan upload
| ulang dokumen yang diminta perbaikan.
|
| Tips Debugging:
| - Jika tombol upload ulang tidak muncul, cek status lamaran dan
|   status_review dokumen di tb_lamaran_berkas.
| - Jika file tidak bisa dibuka, cek path_file_snapshot di database.
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
        'menunggu_verifikasi'    => ['badge badge-light-warning', 'Menunggu Verifikasi'],
        'perlu_perbaikan_berkas' => ['badge badge-light-danger', 'Perlu Perbaikan'],
        'diproses'               => ['badge badge-light-info', 'Diproses'],
        'wawancara'              => ['badge badge-light-primary', 'Wawancara'],
        'diterima'               => ['badge badge-light-success', 'Diterima'],
        'ditolak'                => ['badge badge-light-danger', 'Ditolak'],
        'mengundurkan_diri'      => ['badge badge-light-dark', 'Mengundurkan Diri'],
        default                  => ['badge badge-light-secondary', '-'],
    };
};

$reviewBadge = static function (?string $status): array {
    return match ((string) $status) {
        'sesuai'          => ['badge badge-light-success', 'Sesuai'],
        'perlu_perbaikan' => ['badge badge-light-warning', 'Perlu Perbaikan'],
        'ditolak'         => ['badge badge-light-danger', 'Ditolak'],
        default           => ['badge badge-light-secondary', 'Menunggu'],
    };
};

[$statusClass, $statusLabel] = $statusBadge($lamaran['status'] ?? null);
$flyerUrl = ! empty($lamaran['flyer_lowongan']) ? base_url((string) $lamaran['flyer_lowongan']) : base_url('assets/media/svg/files/blank-image.svg');
$bolehUploadUlang = (string) ($lamaran['status'] ?? '') === 'perlu_perbaikan_berkas';
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('extra_css') ?>
<style>
    .kt-lamaran-detail-flyer {
        border-radius: 1rem;
        overflow: hidden;
        background: #f8fafc;
    }

    .kt-lamaran-detail-flyer img {
        width: 100%;
        max-height: 260px;
        object-fit: cover;
    }

    .kt-lamaran-doc {
        border: 1px dashed var(--bs-gray-300);
        border-radius: 1rem;
        padding: 1.25rem;
        height: 100%;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Detail Lamaran</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= base_url('pelamar/dashboard') ?>" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">
                    <a href="<?= base_url('pelamar/lamaran') ?>" class="text-muted text-hover-primary">Riwayat Lamaran</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Detail</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success mb-8"><?= esc((string) session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger mb-8"><?= esc((string) session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <div class="row g-5 g-xl-8 mb-8">
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="kt-lamaran-detail-flyer mb-6">
                            <img src="<?= esc($flyerUrl) ?>" alt="<?= esc((string) ($lamaran['judul_lowongan'] ?? 'Lowongan')) ?>">
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <span class="badge badge-light-primary"><?= esc((string) ($lamaran['posisi'] ?? '-')) ?></span>
                            <span class="badge badge-light-info"><?= esc(ucfirst((string) ($lamaran['jenis_pekerjaan'] ?? '-'))) ?></span>
                            <span class="badge badge-light-success"><?= esc(ucfirst((string) ($lamaran['sistem_kerja'] ?? '-'))) ?></span>
                            <?php if (! empty($lamaran['pendidikan_min'])): ?>
                                <span class="badge badge-light-warning">Min. <?= esc((string) $lamaran['pendidikan_min']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="fs-2 fw-bold text-gray-900 mb-2"><?= esc((string) ($lamaran['judul_lowongan'] ?? '-')) ?></div>
                        <div class="text-muted fs-6 mb-6"><?= esc((string) ($lamaran['nama_perusahaan'] ?? '-')) ?> - <?= esc((string) (($lamaran['lokasi_kerja'] ?? '') !== '' ? $lamaran['lokasi_kerja'] : ($lamaran['kota'] ?? '-'))) ?></div>

                        <div class="row g-5">
                            <div class="col-md-6">
                                <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Tanggal Melamar</div>
                                <div class="fw-semibold text-gray-800"><?= esc($formatTanggal($lamaran['tanggal_melamar'] ?? null, true)) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Status Lamaran</div>
                                <span class="<?= esc($statusClass) ?>"><?= esc($statusLabel) ?></span>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Batas Lamaran</div>
                                <div class="fw-semibold text-gray-800"><?= esc($formatTanggal($lamaran['batas_lamaran'] ?? null)) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Jadwal Wawancara</div>
                                <div class="fw-semibold text-gray-800"><?= esc($formatTanggal($lamaran['tanggal_wawancara'] ?? null, true)) ?></div>
                            </div>
                        </div>

                        <?php if ($bolehUploadUlang && ! empty($lamaran['batas_perbaikan_berkas'])): ?>
                            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-5 mt-6">
                                <div class="fw-semibold text-gray-700 fs-7">
                                    Batas perbaikan dokumen: <span class="fw-bold"><?= esc($formatTanggal($lamaran['batas_perbaikan_berkas'])) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h2>Riwayat Status</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <?php if ($riwayatStatus === []): ?>
                            <div class="text-muted py-5">Belum ada riwayat status.</div>
                        <?php else: ?>
                            <div class="timeline timeline-border-dashed">
                                <?php foreach ($riwayatStatus as $riwayat): ?>
                                    <?php [$historyClass, $historyLabel] = $statusBadge($riwayat['status_baru'] ?? null); ?>
                                    <div class="timeline-item">
                                        <div class="timeline-line"></div>
                                        <div class="timeline-icon">
                                            <i class="ki-duotone ki-abstract-8 fs-2 text-primary">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <div class="timeline-content mb-8 mt-n2">
                                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                                <span class="<?= esc($historyClass) ?>"><?= esc($historyLabel) ?></span>
                                                <span class="text-muted fs-8"><?= esc($formatTanggal($riwayat['dibuat_pada'] ?? null, true)) ?></span>
                                            </div>
                                            <div class="text-gray-700 fs-7 mb-1">Oleh: <?= esc((string) (($riwayat['diubah_oleh_nama'] ?? '') !== '' ? $riwayat['diubah_oleh_nama'] : 'Sistem')) ?></div>
                                            <div class="text-gray-600 fs-7"><?= esc((string) (($riwayat['catatan'] ?? '') !== '' ? $riwayat['catatan'] : 'Tanpa catatan tambahan.')) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column">
                    <h2 class="mb-1">Dokumen Lamaran</h2>
                    <div class="text-muted fw-semibold fs-6">Dokumen di bawah ini adalah snapshot yang dikirim khusus untuk lamaran ini.</div>
                </div>
            </div>
            <div class="card-body pt-0">
                <?php if ($dokumen === []): ?>
                    <div class="text-center text-muted py-10">Belum ada dokumen snapshot untuk lamaran ini.</div>
                <?php else: ?>
                    <div class="row g-5">
                        <?php foreach ($dokumen as $item): ?>
                            <?php
                            [$reviewClass, $reviewLabel] = $reviewBadge($item['status_review'] ?? null);
                            $perluUploadUlang = $bolehUploadUlang && in_array((string) ($item['status_review'] ?? ''), ['perlu_perbaikan', 'ditolak'], true);
                            ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="kt-lamaran-doc">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-5">
                                        <div class="symbol symbol-60px">
                                            <img src="<?= base_url('assets/media/svg/files/pdf.svg') ?>" class="theme-light-show" alt="<?= esc((string) ($item['nama_berkas'] ?? 'Dokumen'), 'attr') ?>" />
                                            <img src="<?= base_url('assets/media/svg/files/pdf-dark.svg') ?>" class="theme-dark-show" alt="<?= esc((string) ($item['nama_berkas'] ?? 'Dokumen'), 'attr') ?>" />
                                        </div>
                                        <div class="text-end">
                                            <span class="<?= esc($reviewClass) ?>"><?= esc($reviewLabel) ?></span>
                                            <?php if (! empty($item['wajib_saat_submit'])): ?>
                                                <div class="badge badge-light-danger mt-2">Wajib</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="fw-bold fs-5 text-gray-900 mb-1"><?= esc((string) ($item['nama_berkas'] ?? '-')) ?></div>
                                    <div class="text-muted fs-7 mb-4"><?= esc((string) ($item['nama_file_snapshot'] ?? '-')) ?></div>

                                    <?php if (! empty($item['catatan_review'])): ?>
                                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mb-4">
                                            <div class="fw-semibold text-gray-700 fs-7">
                                                <span class="d-block fw-bold mb-1">Catatan Reviewer</span>
                                                <?= esc((string) $item['catatan_review']) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="<?= base_url((string) ($item['path_file_snapshot'] ?? '')) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light-primary">Lihat File</a>
                                        <?php if ($perluUploadUlang): ?>
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#kt_modal_upload_ulang_<?= (int) $item['id_lamaran_berkas'] ?>">
                                                Upload Ulang
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php foreach ($dokumen as $item): ?>
    <?php
    $perluUploadUlang = $bolehUploadUlang && in_array((string) ($item['status_review'] ?? ''), ['perlu_perbaikan', 'ditolak'], true);
    if (! $perluUploadUlang) {
        continue;
    }
    ?>
    <div class="modal fade" id="kt_modal_upload_ulang_<?= (int) $item['id_lamaran_berkas'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Upload Ulang <?= esc((string) ($item['nama_berkas'] ?? 'Dokumen')) ?></h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body px-5 py-7">
                    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-5 mb-6">
                        <div class="fw-semibold text-gray-700 fs-7">
                            File baru akan menggantikan snapshot lama untuk lamaran ini. Setelah semua dokumen bermasalah diperbaiki, status lamaran kembali ke Menunggu Verifikasi.
                        </div>
                    </div>

                    <form action="<?= site_url('pelamar/lamaran/upload-ulang/' . (int) $item['id_lamaran_berkas']) ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-6">
                            <label class="required form-label fw-semibold">File Baru</label>
                            <input type="file" name="file_dokumen" class="form-control form-control-solid" accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="form-text">Format: pdf, jpg, jpeg, png. Maksimal 5 MB.</div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning">Upload Ulang</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?= $this->endSection() ?>
