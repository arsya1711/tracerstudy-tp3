<?php

namespace App\Controllers\Publik;

use App\Controllers\BaseController;
use App\Models\LowonganModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/*
|-------------------------------------------------------------------
| CONTROLLER LOWONGAN PUBLIK
|-------------------------------------------------------------------
| Controller ini menampilkan lowongan aktif untuk pengunjung umum.
| Halaman publik tidak menyimpan lamaran, tetapi mengarahkan user ke
| login/pelamar agar proses melamar tetap memakai modul pelamar yang
| sudah memiliki validasi berkas dan snapshot dokumen.
|
| Alur kerja:
| 1. Pengunjung membuka /lowongan untuk melihat lowongan aktif.
| 2. Pengunjung membuka /lowongan/<slug> untuk membaca detail.
| 3. Tombol melamar mengarah ke login dengan redirect internal.
| 4. Setelah login sebagai pelamar, user kembali ke detail lowongan
|    di area pelamar dan dapat submit lamaran.
|
| Tips Debugging:
| - Jika lowongan publik kosong, cek tb_lowongan.status harus aktif.
| - Jika setelah login tidak kembali ke detail lowongan, cek parameter
|   redirect pada URL login dan LoginController.
*/
class LowonganController extends BaseController
{
    protected LowonganModel $lowonganModel;

    public function __construct()
    {
        $this->lowonganModel = new LowonganModel();
    }

    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $lowongan = $this->lowonganModel->ambilDaftarAktifUntukPelamar($keyword);

        return view('publik/lowongan/index', [
            'title'          => 'Lowongan Kerja BKK - Sistem Tracer Study & BKK',
            'keyword'        => $keyword,
            'lowongan'       => $lowongan,
            'totalAktif'     => count($this->lowonganModel->ambilDaftarAktifUntukPelamar('')),
            'totalHasilCari' => count($lowongan),
        ]);
    }

    public function detail(string $slugLowongan): string
    {
        $lowongan = $this->lowonganModel->ambilDetailAktifBySlug($slugLowongan);

        if ($lowongan === null) {
            throw PageNotFoundException::forPageNotFound('Lowongan tidak ditemukan atau sudah tidak aktif.');
        }

        return view('publik/lowongan/detail', [
            'title'    => 'Detail Lowongan BKK - Sistem Tracer Study & BKK',
            'lowongan' => $lowongan,
        ]);
    }
}
