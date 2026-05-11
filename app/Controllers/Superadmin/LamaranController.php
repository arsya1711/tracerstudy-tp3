<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use App\Models\LamaranBerkasModel;
use App\Models\LamaranModel;
use App\Models\LamaranStatusModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use RuntimeException;

/*
|-------------------------------------------------------------------
| CONTROLLER DATA LAMARAN SUPERADMIN
|-------------------------------------------------------------------
| Controller ini menangani halaman pemantauan seluruh lamaran yang
| masuk dari pelamar ke lowongan aktif di sistem BKK.
|
| Alur kerja:
| 1. Super Admin membuka menu Data Lamaran.
| 2. Controller membaca parameter search dan filter status.
| 3. Data lamaran lengkap dikirim ke view untuk ditampilkan.
|
| Tips Debugging:
| - Jika halaman 404, cek route superadmin/lamaran di Routes.php.
| - Jika tabel kosong, pastikan tb_lamaran sudah termigrasi dan ada
|   data dari modul pelamar.
*/
class LamaranController extends BaseController
{
    protected LamaranModel $lamaranModel;
    protected LamaranBerkasModel $lamaranBerkasModel;
    protected LamaranStatusModel $lamaranStatusModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->lamaranModel       = new LamaranModel();
        $this->lamaranBerkasModel = new LamaranBerkasModel();
        $this->lamaranStatusModel = new LamaranStatusModel();
        $this->db                 = Database::connect();
    }

    /*
    |-------------------------------------------------------------------
    | HALAMAN DATA LAMARAN
    |-------------------------------------------------------------------
    | Menampilkan daftar lamaran dengan filter sederhana agar super
    | admin cepat memantau proses rekrutmen yang berjalan.
    */
    public function index(): string|RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $search       = trim((string) $this->request->getGet('q'));
        $status       = trim((string) $this->request->getGet('status'));
        $idPerusahaan = (int) ($this->request->getGet('id_perusahaan') ?? 0);
        $lamaran      = $this->lamaranModel->ambilDaftarUntukSuperadmin([
            'search'        => $search,
            'status'        => $status,
            'id_perusahaan' => $idPerusahaan,
        ]);

        $detailMap = [];
        foreach ($lamaran as $item) {
            $idLamaran = (int) ($item['id_lamaran'] ?? 0);
            if ($idLamaran <= 0) {
                continue;
            }

            $detailMap[$idLamaran] = [
                'dokumen' => $this->lamaranBerkasModel->ambilByLamaran($idLamaran),
                'riwayat' => $this->lamaranStatusModel->ambilByLamaran($idLamaran),
            ];
        }

        $areaPrefix = $this->ambilAreaPrefix();

        return view('superadmin/lamaran/index', [
            'title'            => ($areaPrefix === 'admin-sekolah' ? 'Monitor Lowongan Kerja' : 'Data Lamaran') . ' - Sistem Tracer Study & BKK',
            'areaPrefix'       => $areaPrefix,
            'dashboardUrl'     => $this->ambilDashboardUrl(),
            'pageHeading'      => $areaPrefix === 'admin-sekolah' ? 'Monitor Lowongan Kerja' : 'Data Lamaran',
            'breadcrumbParent' => $areaPrefix === 'admin-sekolah' ? 'Manajemen DUDI' : 'Manajemen Pengguna',
            'breadcrumbCurrent'=> $areaPrefix === 'admin-sekolah' ? 'Monitor Lowongan Kerja' : 'Data Lamaran',
            'lamaran'          => $lamaran,
            'detailMap'        => $detailMap,
            'ringkasanStatus'  => $this->hitungRingkasanStatus($lamaran),
            'keyword'          => $search,
            'statusFilter'     => $status,
            'perusahaanFilter' => $idPerusahaan,
            'daftarStatus'     => $this->ambilDaftarStatus(),
            'daftarPerusahaan' => $this->lamaranModel->ambilDaftarPerusahaanDenganLamaran(),
            'daftarReview'     => $this->ambilDaftarStatusReview(),
            'bolehUbahStatus'  => session()->get('slug_peran') === 'superadmin',
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | UBAH STATUS UTAMA LAMARAN
    |-------------------------------------------------------------------
    | Aksi ini mengubah status utama proses lamaran dan sekaligus
    | menambahkan histori ke tabel tb_lamaran_status.
    |
    | Tips Debugging:
    | - Jika status berubah tapi histori kosong, cek insert ke
    |   LamaranStatusModel di dalam transaksi.
    */
    public function updateStatus(int $idLamaran): RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        if (session()->get('slug_peran') === 'admin_sekolah') {
            return redirect()->to(site_url('admin-sekolah/lamaran'))->with('error', 'Admin Sekolah/BKK hanya boleh memantau dan meninjau dokumen, tidak mengubah status utama lamaran.');
        }

        $lamaran = $this->lamaranModel->ambilDetailUntukSuperadmin($idLamaran);
        if ($lamaran === null) {
            return redirect()->to(site_url($this->ambilAreaPrefix() . '/lamaran'))->with('error', 'Data lamaran tidak ditemukan.');
        }

        $statusBaru = trim((string) $this->request->getPost('status_baru'));
        $catatan    = trim((string) $this->request->getPost('catatan'));

        if (! array_key_exists($statusBaru, $this->ambilDaftarStatus())) {
            return redirect()->to(site_url('superadmin/lamaran'))->with('error', 'Status lamaran tidak valid.');
        }

        if (in_array($statusBaru, ['perlu_perbaikan_berkas', 'ditolak'], true) && $catatan === '') {
            return redirect()->to(site_url('superadmin/lamaran'))->with('error', 'Catatan wajib diisi untuk status tersebut.');
        }

        $dataUpdate = [
            'status' => $statusBaru,
        ];

        if ($statusBaru === 'diproses') {
            $dataUpdate['tanggal_diproses'] = date('Y-m-d H:i:s');
        }

        if ($statusBaru === 'wawancara') {
            $tanggalWawancara = trim((string) $this->request->getPost('tanggal_wawancara'));
            $dataUpdate['tanggal_wawancara'] = $tanggalWawancara !== '' ? date('Y-m-d H:i:s', strtotime($tanggalWawancara)) : date('Y-m-d H:i:s');
        }

        if (in_array($statusBaru, ['diterima', 'ditolak', 'mengundurkan_diri'], true)) {
            $dataUpdate['tanggal_keputusan'] = date('Y-m-d H:i:s');
        }

        if ($statusBaru === 'perlu_perbaikan_berkas') {
            $batasPerbaikan = trim((string) $this->request->getPost('batas_perbaikan_berkas'));
            $dataUpdate['batas_perbaikan_berkas'] = $batasPerbaikan !== '' ? $batasPerbaikan : null;
        }

        $this->db->transStart();
        $this->lamaranModel->update($idLamaran, $dataUpdate);
        $this->lamaranStatusModel->insert([
            'id_lamaran'  => $idLamaran,
            'status_lama' => (string) ($lamaran['status'] ?? ''),
            'status_baru' => $statusBaru,
            'catatan'     => $catatan !== '' ? $catatan : null,
            'diubah_oleh' => (int) session('id_pengguna'),
            'dibuat_pada' => date('Y-m-d H:i:s'),
        ]);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to(site_url('superadmin/lamaran'))->with('error', 'Status lamaran gagal diperbarui.');
        }

        return redirect()->to(site_url('superadmin/lamaran'))->with('success', 'Status lamaran berhasil diperbarui.');
    }

    /*
    |-------------------------------------------------------------------
    | UBAH REVIEW DOKUMEN SNAPSHOT
    |-------------------------------------------------------------------
    | Review dokumen per file dipisahkan dari status utama lamaran agar
    | reviewer dapat mencatat masalah spesifik pada CV, surat lamaran,
    | atau portofolio tanpa mencampur makna dengan status proses utama.
    */
    public function updateReviewDokumen(int $idLamaran): RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $lamaran = $this->lamaranModel->ambilDetailUntukSuperadmin($idLamaran);
        if ($lamaran === null) {
            return redirect()->to(site_url('superadmin/lamaran'))->with('error', 'Data lamaran tidak ditemukan.');
        }

        $statusReview  = $this->request->getPost('status_review');
        $catatanReview = $this->request->getPost('catatan_review');

        if (! is_array($statusReview) || $statusReview === []) {
            return redirect()->to(site_url($this->ambilAreaPrefix() . '/lamaran'))->with('error', 'Tidak ada dokumen review yang dikirim.');
        }

        $opsiReview = $this->ambilDaftarStatusReview();
        $this->db->transStart();

        foreach ($statusReview as $idLamaranBerkas => $status) {
            $idLamaranBerkas = (int) $idLamaranBerkas;
            $status = trim((string) $status);
            $catatan = is_array($catatanReview) ? trim((string) ($catatanReview[$idLamaranBerkas] ?? '')) : '';

            if ($idLamaranBerkas <= 0 || ! array_key_exists($status, $opsiReview)) {
                continue;
            }

            $baris = $this->lamaranBerkasModel->where('id_lamaran_berkas', $idLamaranBerkas)
                ->where('id_lamaran', $idLamaran)
                ->first();

            if ($baris === null) {
                continue;
            }

            $this->lamaranBerkasModel->update($idLamaranBerkas, [
                'status_review'  => $status,
                'catatan_review' => $catatan !== '' ? $catatan : null,
                'ditinjau_oleh'  => (int) session('id_pengguna'),
                'ditinjau_pada'  => date('Y-m-d H:i:s'),
            ]);
        }

        $this->sinkronStatusDariReviewDokumen($idLamaran, $lamaran);

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to(site_url($this->ambilAreaPrefix() . '/lamaran'))->with('error', 'Review dokumen gagal diperbarui.');
        }

        return redirect()->to(site_url($this->ambilAreaPrefix() . '/lamaran'))->with('success', 'Review dokumen lamaran berhasil diperbarui.');
    }

    protected function ambilDaftarStatus(): array
    {
        return [
            'menunggu_verifikasi'    => 'Menunggu Verifikasi',
            'perlu_perbaikan_berkas' => 'Perlu Perbaikan Berkas',
            'diproses'               => 'Diproses',
            'wawancara'              => 'Wawancara',
            'diterima'               => 'Diterima',
            'ditolak'                => 'Ditolak',
            'mengundurkan_diri'      => 'Mengundurkan Diri',
        ];
    }

    protected function ambilDaftarStatusReview(): array
    {
        return [
            'menunggu'         => 'Menunggu',
            'sesuai'           => 'Sesuai',
            'perlu_perbaikan'  => 'Perlu Perbaikan',
            'ditolak'          => 'Ditolak',
        ];
    }

    protected function hitungRingkasanStatus(array $lamaran): array
    {
        $ringkasan = [
            'total'                 => count($lamaran),
            'menunggu_verifikasi'   => 0,
            'perlu_perbaikan_berkas'=> 0,
            'diproses'              => 0,
            'wawancara'             => 0,
            'diterima'              => 0,
            'ditolak'               => 0,
        ];

        foreach ($lamaran as $item) {
            $status = (string) ($item['status'] ?? '');

            if (array_key_exists($status, $ringkasan)) {
                $ringkasan[$status]++;
            }
        }

        return $ringkasan;
    }

    protected function sinkronStatusDariReviewDokumen(int $idLamaran, array $lamaran): void
    {
        $dokumen = $this->lamaranBerkasModel->ambilByLamaran($idLamaran);
        if ($dokumen === []) {
            return;
        }

        $statusSaatIni = (string) ($lamaran['status'] ?? '');
        if (in_array($statusSaatIni, ['diterima', 'ditolak', 'mengundurkan_diri'], true)) {
            return;
        }

        $statusDokumen = array_map(static fn (array $item): string => (string) ($item['status_review'] ?? 'menunggu'), $dokumen);
        $adaBermasalah = array_intersect($statusDokumen, ['perlu_perbaikan', 'ditolak']) !== [];
        $semuaSesuai = ! in_array('menunggu', $statusDokumen, true)
            && ! in_array('perlu_perbaikan', $statusDokumen, true)
            && ! in_array('ditolak', $statusDokumen, true);

        $statusBaru = null;
        $catatan = null;

        if ($adaBermasalah && $statusSaatIni !== 'perlu_perbaikan_berkas') {
            $statusBaru = 'perlu_perbaikan_berkas';
            $catatan = 'Status otomatis berubah karena ada dokumen lamaran yang perlu diperbaiki atau ditolak reviewer.';
        } elseif ($semuaSesuai && in_array($statusSaatIni, ['menunggu_verifikasi', 'perlu_perbaikan_berkas'], true)) {
            $statusBaru = 'diproses';
            $catatan = 'Status otomatis berubah karena seluruh dokumen lamaran sudah sesuai.';
        }

        if ($statusBaru === null) {
            return;
        }

        $dataUpdate = [
            'status' => $statusBaru,
        ];

        if ($statusBaru === 'diproses') {
            $dataUpdate['tanggal_diproses'] = date('Y-m-d H:i:s');
        }

        $this->lamaranModel->update($idLamaran, $dataUpdate);

        $this->lamaranStatusModel->insert([
            'id_lamaran'  => $idLamaran,
            'status_lama' => $statusSaatIni,
            'status_baru' => $statusBaru,
            'catatan'     => $catatan,
            'diubah_oleh' => (int) session('id_pengguna'),
            'dibuat_pada' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function isSuperadmin(): bool
    {
        return in_array((string) session()->get('slug_peran'), ['superadmin', 'admin_sekolah'], true);
    }

    /*
    |-------------------------------------------------------------------
    | KONTEKS AREA BACKOFFICE
    |-------------------------------------------------------------------
    | Menentukan prefix dan dashboard untuk halaman lamaran yang dipakai
    | Super Admin sebagai Data Lamaran dan Admin Sekolah sebagai monitor
    | lowongan kerja.
    |
    | Tips Debugging:
    | - Jika tombol reset/filter mengarah ke area salah, cek routeBase
    |   di view lamaran.
    */
    protected function ambilAreaPrefix(): string
    {
        return session()->get('slug_peran') === 'admin_sekolah' ? 'admin-sekolah' : 'superadmin';
    }

    protected function ambilDashboardUrl(): string
    {
        return base_url($this->ambilAreaPrefix() === 'admin-sekolah' ? 'admin-sekolah/dashboard' : 'dashboard/superadmin');
    }
}
