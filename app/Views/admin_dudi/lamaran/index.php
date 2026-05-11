<?php
/*
|-------------------------------------------------------------------
| VIEW LAMARAN MASUK ADMIN DUDI / HRD
|-------------------------------------------------------------------
| View ini menjadi meja kerja HRD untuk memeriksa lamaran masuk,
| melihat dokumen snapshot, memberi review dokumen, dan mengubah
| status proses lamaran.
|
| Alur kerja:
| 1. Search dan filter status membantu HRD menemukan lamaran.
| 2. Tombol mata membuka detail lamaran dan review dokumen.
| 3. Tombol setting membuka modal perubahan status utama lamaran.
|
| Tips Debugging:
| - Jika tombol modal tidak terbuka, cek id modal dan data-bs-target.
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
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('extra_css') ?>
<style>
    .kt-hrd-profile-box {
        border: 1px dashed var(--bs-gray-300);
        border-radius: 1rem;
        padding: 1rem;
        background: var(--bs-light);
    }

    .kt-hrd-print-meta {
        display: none;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #kt_hrd_lamaran_print_area,
        #kt_hrd_lamaran_print_area * {
            visibility: visible;
        }

        #kt_hrd_lamaran_print_area {
            position: absolute;
            inset: 0;
            width: 100%;
            background: #fff;
        }

        .app-sidebar,
        .app-header,
        .app-toolbar,
        .card-header,
        .kt-hrd-actions,
        .modal,
        .btn {
            display: none !important;
        }

        .kt-hrd-print-meta {
            display: block;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Lamaran Masuk</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="<?= site_url('admin-dudi/dashboard') ?>" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted"><?= esc((string) ($perusahaan['nama_perusahaan'] ?? 'DUDI')) ?></li>
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

        <div class="card">
            <div class="card-header border-0 pt-6">
                <form method="get" action="<?= site_url('admin-dudi/lamaran') ?>" class="d-flex flex-stack flex-wrap gap-4 w-100">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" name="q" class="form-control form-control-solid w-250px ps-13" placeholder="Cari pelamar/posisi" value="<?= esc($keyword) ?>" />
                        </div>
                    </div>

                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end gap-3">
                            <select name="status" class="form-select form-select-solid w-225px">
                                <option value="">Semua Status</option>
                                <?php foreach ($daftarStatus as $value => $label): ?>
                                    <option value="<?= esc($value, 'attr') ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-light-primary">Filter</button>
                            <button type="button" class="btn btn-primary" id="kt_hrd_lamaran_print_button">
                                <i class="ki-duotone ki-printer fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>Cetak
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body py-4" id="kt_hrd_lamaran_print_area">
                <div class="kt-hrd-print-meta mb-8">
                    <h2 class="fw-bold mb-2">Laporan Lamaran Masuk</h2>
                    <div class="text-muted"><?= esc((string) ($perusahaan['nama_perusahaan'] ?? '-')) ?> · Dicetak <?= esc($formatTanggal(date('Y-m-d H:i:s'), true)) ?></div>
                </div>

                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target=".kt-hrd-lamaran-check" value="1" />
                                </div>
                            </th>
                            <th class="min-w-250px">Pelamar</th>
                            <th class="min-w-220px">Lowongan</th>
                            <th class="min-w-150px">Tanggal Melamar</th>
                            <th class="min-w-160px">Status</th>
                            <th class="text-end min-w-120px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 fw-semibold">
                        <?php if ($lamaran === []): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-10">Belum ada lamaran masuk.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lamaran as $item): ?>
                                <?php
                                $idLamaran = (int) ($item['id_lamaran'] ?? 0);
                                $modalDetailId = 'kt_modal_hrd_lamaran_' . $idLamaran;
                                $modalStatusId = 'kt_modal_hrd_status_' . $idLamaran;
                                [$badgeClass, $badgeLabel] = $statusBadge($item['status'] ?? null);
                                ?>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input kt-hrd-lamaran-check" type="checkbox" value="<?= $idLamaran ?>" />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-gray-900"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?></div>
                                        <div class="text-muted fs-7"><?= esc((string) ($item['email'] ?? '-')) ?></div>
                                        <div class="text-muted fs-8"><?= esc((string) ($item['account_id'] ?? '-')) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-gray-900"><?= esc((string) ($item['posisi'] ?? '-')) ?></div>
                                        <div class="text-muted fs-7"><?= esc((string) ($item['judul_lowongan'] ?? '-')) ?></div>
                                    </td>
                                    <td><?= esc($formatTanggal($item['tanggal_melamar'] ?? null, true)) ?></td>
                                    <td><span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span></td>
                                    <td class="text-end kt-hrd-actions">
                                        <button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px me-2" data-bs-toggle="modal" data-bs-target="#<?= esc($modalDetailId) ?>" title="Detail Lamaran">
                                            <i class="ki-duotone ki-eye fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                        </button>
                                        <button type="button" class="btn btn-icon btn-active-light-warning w-30px h-30px" data-bs-toggle="modal" data-bs-target="#<?= esc($modalStatusId) ?>" title="Ubah Status">
                                            <i class="ki-duotone ki-setting-2 fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
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
</div>

<?php foreach ($lamaran as $item): ?>
    <?php
    $idLamaran = (int) ($item['id_lamaran'] ?? 0);
    $modalDetailId = 'kt_modal_hrd_lamaran_' . $idLamaran;
    $modalStatusId = 'kt_modal_hrd_status_' . $idLamaran;
    $dokumen = $detailMap[$idLamaran]['dokumen'] ?? [];
    $riwayat = $detailMap[$idLamaran]['riwayat'] ?? [];
    [$badgeClass, $badgeLabel] = $statusBadge($item['status'] ?? null);
    ?>

    <div class="modal fade" id="<?= esc($modalDetailId) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-1000px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Detail Lamaran</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body px-5 py-7">
                    <div class="row g-6 mb-8">
                        <div class="col-lg-4">
                            <div class="kt-hrd-profile-box h-100">
                                <div class="fs-5 fw-bold text-gray-900 mb-4">Profil Pelamar</div>
                                <div class="mb-4">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Nama</div>
                                    <div class="fw-semibold text-gray-800"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?></div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Email</div>
                                    <div class="fw-semibold text-gray-800"><?= esc((string) ($item['email'] ?? '-')) ?></div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Telepon</div>
                                    <div class="fw-semibold text-gray-800"><?= esc((string) (($item['nomor_telepon'] ?? '') !== '' ? $item['nomor_telepon'] : '-')) ?></div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Alamat</div>
                                    <div class="fw-semibold text-gray-800"><?= nl2br(esc((string) (($item['alamat'] ?? '') !== '' ? $item['alamat'] : '-'))) ?></div>
                                </div>
                                <div>
                                    <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Status</div>
                                    <span class="<?= esc($badgeClass) ?>"><?= esc($badgeLabel) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="kt-hrd-profile-box h-100">
                                <div class="fs-5 fw-bold text-gray-900 mb-4">Lowongan & Riwayat Status</div>
                                <div class="row g-4 mb-5">
                                    <div class="col-md-6">
                                        <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Posisi</div>
                                        <div class="fw-semibold text-gray-800"><?= esc((string) ($item['posisi'] ?? '-')) ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted fs-7 text-uppercase fw-bold mb-1">Tanggal Melamar</div>
                                        <div class="fw-semibold text-gray-800"><?= esc($formatTanggal($item['tanggal_melamar'] ?? null, true)) ?></div>
                                    </div>
                                </div>

                                <?php if ($riwayat === []): ?>
                                    <div class="text-muted fs-7">Belum ada riwayat status.</div>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-3">
                                        <?php foreach ($riwayat as $riwayatItem): ?>
                                            <?php [$historyBadgeClass, $historyBadgeLabel] = $statusBadge($riwayatItem['status_baru'] ?? null); ?>
                                            <div class="border border-dashed rounded p-4">
                                                <div class="d-flex flex-wrap justify-content-between gap-3 mb-2">
                                                    <span class="<?= esc($historyBadgeClass) ?>"><?= esc($historyBadgeLabel) ?></span>
                                                    <span class="text-muted fs-8"><?= esc($formatTanggal($riwayatItem['dibuat_pada'] ?? null, true)) ?></span>
                                                </div>
                                                <div class="text-gray-700 fs-7"><?= esc((string) (($riwayatItem['catatan'] ?? '') !== '' ? $riwayatItem['catatan'] : 'Tanpa catatan tambahan.')) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <form action="<?= site_url('admin-dudi/lamaran/update-review/' . $idLamaran) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="d-flex flex-stack mb-4">
                            <div>
                                <div class="fs-5 fw-bold text-gray-900">Review Dokumen Snapshot</div>
                                <div class="text-muted fs-7">Catatan ini khusus untuk dokumen yang dikirim pada lamaran ini.</div>
                            </div>
                        </div>

                        <?php if ($dokumen === []): ?>
                            <div class="alert alert-light-warning">Belum ada dokumen snapshot pada lamaran ini.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th>Dokumen</th>
                                            <th>Status Review</th>
                                            <th>Catatan Reviewer</th>
                                            <th class="text-end">File</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-700 fw-semibold">
                                        <?php foreach ($dokumen as $dokumenItem): ?>
                                            <?php
                                            [$reviewBadgeClass, $reviewBadgeLabel] = $reviewBadge($dokumenItem['status_review'] ?? null);
                                            $pathFile = trim((string) ($dokumenItem['path_file_snapshot'] ?? ''));
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-45px me-4">
                                                            <img src="<?= base_url('assets/media/svg/files/pdf.svg') ?>" class="theme-light-show" alt="" />
                                                            <img src="<?= base_url('assets/media/svg/files/pdf-dark.svg') ?>" class="theme-dark-show" alt="" />
                                                        </div>
                                                        <div>
                                                            <div class="text-gray-900 fw-bold"><?= esc((string) ($dokumenItem['nama_berkas'] ?? '-')) ?></div>
                                                            <div class="text-muted fs-7"><?= esc((string) ($dokumenItem['nama_file_snapshot'] ?? '-')) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="min-w-200px">
                                                    <div class="mb-2"><span class="<?= esc($reviewBadgeClass) ?>"><?= esc($reviewBadgeLabel) ?></span></div>
                                                    <select name="status_review[<?= (int) $dokumenItem['id_lamaran_berkas'] ?>]" class="form-select form-select-solid">
                                                        <?php foreach ($daftarReview as $value => $label): ?>
                                                            <option value="<?= esc($value, 'attr') ?>" <?= ($dokumenItem['status_review'] ?? '') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td class="min-w-250px">
                                                    <textarea name="catatan_review[<?= (int) $dokumenItem['id_lamaran_berkas'] ?>]" class="form-control form-control-solid" rows="3" placeholder="Catatan review dokumen"><?= esc((string) ($dokumenItem['catatan_review'] ?? '')) ?></textarea>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($pathFile !== ''): ?>
                                                        <a href="<?= base_url($pathFile) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light-primary">Lihat File</a>
                                                    <?php else: ?>
                                                        <span class="text-muted fs-7">Tidak tersedia</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-end pt-4">
                                <button type="submit" class="btn btn-primary">Simpan Review Dokumen</button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="<?= esc($modalStatusId) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Ubah Status Lamaran</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body px-5 py-7">
                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5 mb-6">
                        <div class="fw-semibold text-gray-700 fs-7">
                            Perubahan status disimpan sebagai histori pada <code>tb_lamaran_status</code>, jadi proses rekrutmen tetap bisa diaudit.
                        </div>
                    </div>

                    <form action="<?= site_url('admin-dudi/lamaran/update-status/' . $idLamaran) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="mb-5">
                            <label class="form-label fw-semibold">Pelamar</label>
                            <div class="form-control form-control-solid"><?= esc((string) ($item['nama_lengkap'] ?? '-')) ?> - <?= esc((string) ($item['posisi'] ?? '-')) ?></div>
                        </div>

                        <div class="mb-5">
                            <label class="required form-label fw-semibold">Status Baru</label>
                            <select name="status_baru" class="form-select form-select-solid" required>
                                <?php foreach ($daftarStatus as $value => $label): ?>
                                    <option value="<?= esc($value, 'attr') ?>" <?= ($item['status'] ?? '') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-semibold">Batas Perbaikan Berkas</label>
                            <input type="date" name="batas_perbaikan_berkas" class="form-control form-control-solid" value="<?= esc((string) ($item['batas_perbaikan_berkas'] ?? ''), 'attr') ?>">
                            <div class="form-text">Isi jika status diarahkan ke perbaikan berkas.</div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-semibold">Tanggal Wawancara</label>
                            <?php
                            $tanggalWawancaraValue = '';
                            if (! empty($item['tanggal_wawancara'])) {
                                try {
                                    $tanggalWawancaraValue = (new DateTime((string) $item['tanggal_wawancara']))->format('Y-m-d\TH:i');
                                } catch (Throwable) {
                                    $tanggalWawancaraValue = '';
                                }
                            }
                            ?>
                            <input type="datetime-local" name="tanggal_wawancara" class="form-control form-control-solid" value="<?= esc($tanggalWawancaraValue, 'attr') ?>">
                        </div>

                        <div class="mb-8">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea name="catatan" class="form-control form-control-solid" rows="4" placeholder="Contoh: Surat lamaran masih ditujukan ke perusahaan lain, mohon upload ulang sebelum tanggal ..."></textarea>
                            <div class="form-text">Wajib diisi jika status Perlu Perbaikan Berkas atau Ditolak.</div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-warning">Simpan Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?= $this->endSection() ?>

<?= $this->section('extra_js') ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var printButton = document.getElementById('kt_hrd_lamaran_print_button');
        if (printButton) {
            printButton.addEventListener('click', function () {
                window.print();
            });
        }
    });
</script>
<?= $this->endSection() ?>
