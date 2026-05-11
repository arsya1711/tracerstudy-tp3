<?php

namespace App\Controllers\AdminDudi;

use App\Controllers\BaseController;
use App\Models\LamaranModel;
use App\Models\LowonganModel;
use App\Models\PerusahaanModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;
use RuntimeException;

/*
|-------------------------------------------------------------------
| CONTROLLER DASHBOARD ADMIN DUDI / HRD
|-------------------------------------------------------------------
| Controller ini menjadi halaman awal admin perusahaan setelah login.
| Data yang tampil dibatasi berdasarkan perusahaan yang terhubung ke
| akun admin melalui tb_perusahaan.id_pengguna.
|
| Alur kerja:
| 1. Sistem membaca id_pengguna dari session login.
| 2. Controller mencari perusahaan milik akun tersebut.
| 3. Dashboard menampilkan ringkasan lowongan dan lamaran masuk.
|
| Tips Debugging:
| - Jika dashboard kosong, cek tb_perusahaan.id_pengguna.
| - Jika akses ditolak, cek slug_peran session harus admin_dudi.
*/
class DashboardController extends BaseController
{
    protected PerusahaanModel $perusahaanModel;
    protected LowonganModel $lowonganModel;
    protected LamaranModel $lamaranModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->perusahaanModel = new PerusahaanModel();
        $this->lowonganModel   = new LowonganModel();
        $this->lamaranModel    = new LamaranModel();
        $this->db              = Database::connect();
    }

    public function index(): string|RedirectResponse
    {
        if (! $this->isAdminDudi()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $perusahaan = $this->ambilPerusahaanLogin();
        if ($perusahaan === null) {
            return redirect()->to('/logout')->with('error', 'Akun admin DUDI belum terhubung ke perusahaan.');
        }

        $lamaranTerbaru = array_slice($this->lamaranModel->ambilDaftarUntukPerusahaan((int) $perusahaan['id_perusahaan']), 0, 5);

        return view('admin_dudi/dashboard', [
            'title'             => 'Dashboard Admin DUDI - Sistem Tracer Study & BKK',
            'perusahaan'        => $perusahaan,
            'ringkasanLowongan' => $this->lowonganModel->hitungRingkasanPerusahaan((int) $perusahaan['id_perusahaan']),
            'ringkasanLamaran'  => $this->lamaranModel->hitungRingkasanPerusahaan((int) $perusahaan['id_perusahaan']),
            'lamaranTerbaru'    => $lamaranTerbaru,
        ]);
    }

    protected function ambilPerusahaanLogin(): ?array
    {
        $idPengguna = (int) session()->get('id_pengguna');
        $penggunaSession = session()->get('pengguna');

        if ($idPengguna <= 0 && is_array($penggunaSession)) {
            $idPengguna = (int) ($penggunaSession['id_pengguna'] ?? 0);
        }

        return $this->perusahaanModel->ambilByPengguna($idPengguna);
    }

    protected function isAdminDudi(): bool
    {
        return in_array((string) session()->get('slug_peran'), ['admin_dudi', 'admin_perusahaan'], true);
    }
}
