<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use App\Models\AngkatanModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/*
|-------------------------------------------------------------------
| CONTROLLER ANGKATAN
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: controller ini menangani halaman
| manajemen Angkatan untuk Super Admin, termasuk daftar data, simpan,
| update, dan hapus non permanen berbasis AJAX JSON.
| Alur kerja: user membuka halaman index untuk melihat tabel angkatan,
| lalu semua aksi tambah, edit, dan hapus diproses oleh endpoint JSON
| melalui fetch di sisi frontend.
|
| Tips Debugging:
| - Jika endpoint AJAX 403, cek session slug_peran harus superadmin.
| - Jika validasi tahun gagal, cek payload tahun_lulus yang dikirim form.
*/
class AngkatanController extends BaseController
{
    protected AngkatanModel $angkatanModel;

    /*
    |-------------------------------------------------------------------
    | CONSTRUCTOR
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menyiapkan model
    | AngkatanModel agar bisa dipakai oleh semua aksi controller.
    | Alur kerja: CI4 memanggil constructor saat controller dibuat,
    | lalu instance model disimpan untuk dipakai method lain.
    |
    | Tips Debugging:
    | - Jika model tidak terbaca, cek namespace App\Models\AngkatanModel.
    */
    public function __construct()
    {
        $this->angkatanModel = new AngkatanModel();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INDEX
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan halaman utama
    | modul Angkatan beserta daftar data aktif dan jumlah siswa yang
    | terhubung ke setiap tahun lulus.
    | Alur kerja: method memeriksa role superadmin, mengambil data
    | dari model, lalu me-render view superadmin/angkatan/index.
    |
    | Tips Debugging:
    | - Jika selalu redirect ke login, cek session slug_peran harus superadmin.
    | - Jika tabel kosong, cek hasil ambilSemuaDenganJumlahSiswa() pada model.
    */
    public function index(): string|RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        return view('superadmin/angkatan/index', [
            'title'    => 'Angkatan - Sistem Tracer Study',
            'angkatan' => $this->angkatanModel->ambilSemuaDenganJumlahSiswa(),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD SIMPAN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memvalidasi lalu
    | menyimpan data angkatan baru dari modal tambah berbasis AJAX.
    | Alur kerja: frontend mengirim POST, controller memeriksa akses,
    | memvalidasi tahun_lulus, menyimpan data, lalu mengirim JSON hasil
    | operasi beserta token CSRF baru.
    |
    | Tips Debugging:
    | - Jika request ditolak, cek token CSRF pada payload form AJAX.
    | - Jika tahun dianggap duplikat, cek data tahun_lulus sudah ada di tabel tb_angkatan.
    */
    public function simpan(): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $validasi = $this->validate([
            'tahun_lulus' => 'required|exact_length[4]|is_unique[tb_angkatan.tahun_lulus]',
        ]);

        if (! $validasi) {
            return $this->jsonResponse('error', 'Data angkatan belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $data = [
            'tahun_lulus'  => (string) $this->request->getPost('tahun_lulus'),
            'status_aktif' => 1,
        ];

        $insertId = $this->angkatanModel->insert($data, true);

        if (! $insertId) {
            return $this->jsonResponse('error', 'Data angkatan gagal disimpan.', [], 500);
        }

        $baris = $this->angkatanModel->find($insertId);
        $baris['jumlah_siswa'] = 0;

        return $this->jsonResponse('success', 'Angkatan berhasil ditambahkan.', [
            'data' => $baris,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD UPDATE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memvalidasi lalu
    | memperbarui data angkatan dari modal edit berbasis AJAX.
    | Alur kerja: frontend mengirim POST ke endpoint update, controller
    | memeriksa akses, memvalidasi tahun_lulus dengan pengecualian id
    | yang sedang diedit, melakukan update, lalu mengirim JSON hasilnya.
    |
    | Tips Debugging:
    | - Jika validasi unik gagal padahal data sama, cek id pada URL update sesuai baris yang diedit.
    | - Jika response 404, cek data angkatan dengan id tersebut masih aktif.
    */
    public function update(int $id): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $baris = $this->angkatanModel->find($id);
        if ($baris === null || (int) ($baris['status_aktif'] ?? 0) !== 1) {
            return $this->jsonResponse('error', 'Data angkatan tidak ditemukan.', [], 404);
        }

        $validasi = $this->validate([
            'tahun_lulus' => 'required|exact_length[4]|is_unique[tb_angkatan.tahun_lulus,id_angkatan,' . $id . ']',
        ]);

        if (! $validasi) {
            return $this->jsonResponse('error', 'Data angkatan belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $sukses = $this->angkatanModel->update($id, [
            'tahun_lulus' => (string) $this->request->getPost('tahun_lulus'),
        ]);

        if (! $sukses) {
            return $this->jsonResponse('error', 'Data angkatan gagal diperbarui.', [], 500);
        }

        $dataAngkatan = $this->angkatanModel->ambilSemuaDenganJumlahSiswa();
        $barisTerbaru = null;

        foreach ($dataAngkatan as $item) {
            if ((int) $item['id_angkatan'] === $id) {
                $barisTerbaru = $item;
                break;
            }
        }

        return $this->jsonResponse('success', 'Angkatan berhasil diperbarui.', [
            'data' => $barisTerbaru,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD HAPUS
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini melakukan hapus lunak
    | pada data angkatan dengan cara mengubah status_aktif menjadi 0.
    | Alur kerja: frontend mengirim POST ber-CSRF ke endpoint hapus, controller
    | memeriksa akses dan id, lalu menonaktifkan data dan mengirim
    | response JSON hasil operasi.
    |
    | Tips Debugging:
    | - Jika data masih muncul setelah hapus, cek query model hanya mengambil status_aktif = 1.
    | - Jika response 404, cek id_angkatan pada tombol hapus.
    */
    public function hapus(int $id): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $baris = $this->angkatanModel->find($id);
        if ($baris === null || (int) ($baris['status_aktif'] ?? 0) !== 1) {
            return $this->jsonResponse('error', 'Data angkatan tidak ditemukan.', [], 404);
        }

        $sukses = $this->angkatanModel->update($id, [
            'status_aktif' => 0,
        ]);

        if (! $sukses) {
            return $this->jsonResponse('error', 'Data angkatan gagal dihapus.', [], 500);
        }

        return $this->jsonResponse('success', 'Angkatan berhasil dihapus.', [
            'id_angkatan' => $id,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD IS SUPERADMIN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memeriksa apakah session
    | pengguna aktif memiliki role superadmin.
    | Alur kerja: controller memanggil method ini sebelum memproses
    | halaman atau endpoint AJAX khusus Super Admin.
    |
    | Tips Debugging:
    | - Jika akses selalu gagal, cek session slug_peran saat login.
    */
    protected function isSuperadmin(): bool
    {
        return session()->get('slug_peran') === 'superadmin';
    }

    /*
    |-------------------------------------------------------------------
    | METHOD JSON RESPONSE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membentuk response JSON
    | yang konsisten untuk seluruh aksi AJAX modul Angkatan, termasuk
    | token CSRF baru untuk request selanjutnya.
    | Alur kerja: method menerima status, pesan, data tambahan, dan
    | kode HTTP, lalu menggabungkannya ke response JSON CI4.
    |
    | Tips Debugging:
    | - Jika frontend gagal membaca response, cek key status dan message tetap konsisten.
    | - Jika request berikutnya 403, cek csrfHash ikut dikirim di JSON.
    */
    protected function jsonResponse(string $status, string $message, array $data = [], int $httpCode = 200): ResponseInterface
    {
        return $this->response->setStatusCode($httpCode)->setJSON(array_merge([
            'status'   => $status,
            'message'  => $message,
            'csrfHash' => csrf_hash(),
        ], $data));
    }
}
