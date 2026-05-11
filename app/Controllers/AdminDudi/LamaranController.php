<?php

namespace App\Controllers\AdminDudi;

use App\Controllers\BaseController;
use App\Models\LamaranBerkasModel;
use App\Models\LamaranModel;
use App\Models\LamaranStatusModel;
use App\Models\PerusahaanModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;

/*
|-------------------------------------------------------------------
| CONTROLLER LAMARAN MASUK ADMIN DUDI / HRD
|-------------------------------------------------------------------
| Controller ini menjadi meja kerja HRD untuk meninjau pelamar yang
| masuk ke lowongan perusahaan mereka.
|
| Alur kerja:
| 1. Admin DUDI login dan terhubung ke satu perusahaan.
| 2. Controller mengambil lamaran berdasarkan lowongan perusahaan itu.
| 3. HRD dapat review dokumen snapshot dan mengubah status lamaran.
|
| Tips Debugging:
| - Jika HRD bisa membuka lamaran perusahaan lain, cek method
|   LamaranModel::ambilDetailUntukPerusahaan().
| - Jika review dokumen tidak berubah, cek id_lamaran_berkas dan
|   id_lamaran pada form modal.
*/
class LamaranController extends BaseController
{
    protected PerusahaanModel $perusahaanModel;
    protected LamaranModel $lamaranModel;
    protected LamaranBerkasModel $lamaranBerkasModel;
    protected LamaranStatusModel $lamaranStatusModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->perusahaanModel    = new PerusahaanModel();
        $this->lamaranModel       = new LamaranModel();
        $this->lamaranBerkasModel = new LamaranBerkasModel();
        $this->lamaranStatusModel = new LamaranStatusModel();
        $this->db                 = Database::connect();
    }

    /*
    |-------------------------------------------------------------------
    | HALAMAN LAMARAN MASUK
    |-------------------------------------------------------------------
    | Menampilkan lamaran yang masuk ke lowongan milik perusahaan login,
    | lengkap dengan modal detail, review dokumen, dan ubah status.
    */
    public function index(): string|RedirectResponse
    {
        if (! $this->isAdminDudi()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $perusahaan = $this->ambilPerusahaanLogin();
        if ($perusahaan === null) {
            return redirect()->to('/logout')->with('error', 'Akun admin DUDI belum terhubung ke perusahaan.');
        }

        $search = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));
        $lamaran = $this->lamaranModel->ambilDaftarUntukPerusahaan((int) $perusahaan['id_perusahaan'], [
            'search' => $search,
            'status' => $status,
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

        return view('admin_dudi/lamaran/index', [
            'title'        => 'Lamaran Masuk - Sistem Tracer Study & BKK',
            'perusahaan'   => $perusahaan,
            'lamaran'      => $lamaran,
            'detailMap'    => $detailMap,
            'keyword'      => $search,
            'statusFilter' => $status,
            'daftarStatus' => $this->ambilDaftarStatusLamaran(),
            'daftarReview' => $this->ambilDaftarStatusReview(),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | UBAH STATUS LAMARAN OLEH HRD
    |-------------------------------------------------------------------
    | Status utama lamaran diperbarui oleh HRD dan setiap perubahan
    | disimpan ke tb_lamaran_status sebagai audit trail.
    */
    public function updateStatus(int $idLamaran): RedirectResponse
    {
        $perusahaan = $this->validasiAksesPerusahaan();
        if ($perusahaan instanceof RedirectResponse) {
            return $perusahaan;
        }

        $lamaran = $this->lamaranModel->ambilDetailUntukPerusahaan($idLamaran, (int) $perusahaan['id_perusahaan']);
        if ($lamaran === null) {
            return redirect()->to(site_url('admin-dudi/lamaran'))->with('error', 'Data lamaran tidak ditemukan.');
        }

        $statusBaru = trim((string) $this->request->getPost('status_baru'));
        $catatan    = trim((string) $this->request->getPost('catatan'));

        if (! array_key_exists($statusBaru, $this->ambilDaftarStatusLamaran())) {
            return redirect()->to(site_url('admin-dudi/lamaran'))->with('error', 'Status lamaran tidak valid.');
        }

        if (in_array($statusBaru, ['perlu_perbaikan_berkas', 'ditolak'], true) && $catatan === '') {
            return redirect()->to(site_url('admin-dudi/lamaran'))->with('error', 'Catatan wajib diisi untuk status tersebut.');
        }

        $dataUpdate = ['status' => $statusBaru];

        if ($statusBaru === 'diproses') {
            $dataUpdate['tanggal_diproses'] = date('Y-m-d H:i:s');
        }

        if ($statusBaru === 'wawancara') {
            $tanggalWawancara = trim((string) $this->request->getPost('tanggal_wawancara'));
            $dataUpdate['tanggal_wawancara'] = $tanggalWawancara !== '' ? date('Y-m-d H:i:s', strtotime($tanggalWawancara)) : date('Y-m-d H:i:s');
        }

        if (in_array($statusBaru, ['diterima', 'ditolak'], true)) {
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
            'diubah_oleh' => $this->ambilIdPenggunaLogin(),
            'dibuat_pada' => date('Y-m-d H:i:s'),
        ]);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to(site_url('admin-dudi/lamaran'))->with('error', 'Status lamaran gagal diperbarui.');
        }

        return redirect()->to(site_url('admin-dudi/lamaran'))->with('success', 'Status lamaran berhasil diperbarui.');
    }

    /*
    |-------------------------------------------------------------------
    | REVIEW DOKUMEN SNAPSHOT OLEH HRD
    |-------------------------------------------------------------------
    | HRD dapat memberi status review per dokumen. Catatan ini spesifik
    | ke file snapshot pada lamaran tersebut, bukan berkas profil umum.
    */
    public function updateReviewDokumen(int $idLamaran): RedirectResponse
    {
        $perusahaan = $this->validasiAksesPerusahaan();
        if ($perusahaan instanceof RedirectResponse) {
            return $perusahaan;
        }

        $lamaran = $this->lamaranModel->ambilDetailUntukPerusahaan($idLamaran, (int) $perusahaan['id_perusahaan']);
        if ($lamaran === null) {
            return redirect()->to(site_url('admin-dudi/lamaran'))->with('error', 'Data lamaran tidak ditemukan.');
        }

        $statusReview  = $this->request->getPost('status_review');
        $catatanReview = $this->request->getPost('catatan_review');

        if (! is_array($statusReview) || $statusReview === []) {
            return redirect()->to(site_url('admin-dudi/lamaran'))->with('error', 'Tidak ada dokumen review yang dikirim.');
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
                'ditinjau_oleh'  => $this->ambilIdPenggunaLogin(),
                'ditinjau_pada'  => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to(site_url('admin-dudi/lamaran'))->with('error', 'Review dokumen gagal diperbarui.');
        }

        return redirect()->to(site_url('admin-dudi/lamaran'))->with('success', 'Review dokumen lamaran berhasil diperbarui.');
    }

    protected function validasiAksesPerusahaan(): array|RedirectResponse
    {
        if (! $this->isAdminDudi()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $perusahaan = $this->ambilPerusahaanLogin();
        if ($perusahaan === null) {
            return redirect()->to('/logout')->with('error', 'Akun admin DUDI belum terhubung ke perusahaan.');
        }

        return $perusahaan;
    }

    protected function ambilDaftarStatusLamaran(): array
    {
        return [
            'menunggu_verifikasi'    => 'Menunggu Verifikasi',
            'perlu_perbaikan_berkas' => 'Perlu Perbaikan Berkas',
            'diproses'               => 'Diproses',
            'wawancara'              => 'Wawancara',
            'diterima'               => 'Diterima',
            'ditolak'                => 'Ditolak',
        ];
    }

    protected function ambilDaftarStatusReview(): array
    {
        return [
            'menunggu'        => 'Menunggu',
            'sesuai'          => 'Sesuai',
            'perlu_perbaikan' => 'Perlu Perbaikan',
            'ditolak'         => 'Ditolak',
        ];
    }

    protected function ambilPerusahaanLogin(): ?array
    {
        return $this->perusahaanModel->ambilByPengguna($this->ambilIdPenggunaLogin());
    }

    protected function ambilIdPenggunaLogin(): int
    {
        $idPengguna = (int) session()->get('id_pengguna');
        $penggunaSession = session()->get('pengguna');

        if ($idPengguna <= 0 && is_array($penggunaSession)) {
            $idPengguna = (int) ($penggunaSession['id_pengguna'] ?? 0);
        }

        return $idPengguna;
    }

    protected function isAdminDudi(): bool
    {
        return in_array((string) session()->get('slug_peran'), ['admin_dudi', 'admin_perusahaan'], true);
    }
}
