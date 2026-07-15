<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use App\Models\KompetensiModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/*
|-------------------------------------------------------------------
| CONTROLLER KOMPETENSI
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: controller ini menangani halaman
| manajemen Kompetensi Keahlian untuk Super Admin, termasuk daftar
| data, simpan, update, dan hapus non permanen berbasis AJAX JSON.
| Alur kerja: user membuka halaman index untuk melihat tabel, lalu
| aksi tambah, ubah, dan hapus memanggil endpoint JSON pada controller
| ini melalui fetch di sisi frontend.
|
| Tips Debugging:
| - Jika endpoint AJAX selalu 403, cek session slug_peran harus superadmin.
| - Jika data tidak tersimpan, cek payload POST dan validasi field pada controller ini.
*/
class KompetensiController extends BaseController
{
    protected KompetensiModel $kompetensiModel;

    /*
    |-------------------------------------------------------------------
    | CONSTRUCTOR
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menyiapkan model
    | KompetensiModel agar bisa dipakai oleh seluruh aksi controller.
    | Alur kerja: CI4 memanggil constructor saat controller dibuat,
    | lalu instance model disimpan untuk dipakai method lain.
    |
    | Tips Debugging:
    | - Jika model tidak terbaca, cek namespace App\Models\KompetensiModel.
    */
    public function __construct()
    {
        $this->kompetensiModel = new KompetensiModel();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INDEX
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan halaman utama
    | modul Kompetensi Keahlian beserta daftar data aktif yang sudah
    | dihitung nilai keterserapannya.
    | Alur kerja: method mengecek role superadmin, mengambil data dari
    | model, lalu me-render view superadmin/kompetensi/index.
    |
    | Tips Debugging:
    | - Jika selalu redirect ke login, cek session slug_peran harus superadmin.
    | - Jika tabel kosong, cek hasil query ambilSemuaDenganKeterserapan() pada model.
    */
    public function index(): string|RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        return view('superadmin/kompetensi/index', [
            'title'      => 'Kompetensi Keahlian - Sistem Tracer Study',
            'kompetensi' => $this->kompetensiModel->ambilSemuaDenganKeterserapan(),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD SIMPAN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memvalidasi lalu menyimpan
    | data kompetensi baru dari form tambah berbasis AJAX.
    | Alur kerja: frontend mengirim request POST, controller memeriksa
    | akses superadmin, memvalidasi field wajib, menyimpan data ke
    | database, lalu mengirim response JSON hasil operasi.
    |
    | Tips Debugging:
    | - Jika request ditolak, cek token CSRF yang dikirim bersama form AJAX.
    | - Jika validasi gagal terus, cek field nama_kompetensi dan akronim dikirim dari form.
    */
    public function simpan(): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $validasi = $this->validate([
            'nama_kompetensi' => 'required',
            'akronim'         => 'required',
        ]);

        if (! $validasi) {
            return $this->jsonResponse('error', 'Data kompetensi belum lengkap.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $data = [
            'nama_kompetensi' => (string) $this->request->getPost('nama_kompetensi'),
            'akronim'         => (string) $this->request->getPost('akronim'),
            'status_aktif'    => 1,
        ];

        $insertId = $this->kompetensiModel->insert($data, true);

        if (! $insertId) {
            return $this->jsonResponse('error', 'Data kompetensi gagal disimpan.', [], 500);
        }

        $baris = $this->kompetensiModel->find($insertId);
        $baris['keterserapan'] = 0;

        return $this->jsonResponse('success', 'Kompetensi berhasil ditambahkan.', [
            'data' => $baris,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD UPDATE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memvalidasi lalu
    | memperbarui data kompetensi yang dipilih dari modal edit AJAX.
    | Alur kerja: frontend mengirim POST ke endpoint update dengan id,
    | controller memeriksa akses, memvalidasi input, melakukan update,
    | lalu mengirim response JSON hasil perubahan data.
    |
    | Tips Debugging:
    | - Jika data tidak berubah, cek id_kompetensi yang dikirim dari tombol edit.
    | - Jika response error 404, cek data kompetensi dengan id tersebut masih aktif.
    */
    public function update(int $id): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $baris = $this->kompetensiModel->find($id);
        if ($baris === null || (int) ($baris['status_aktif'] ?? 0) !== 1) {
            return $this->jsonResponse('error', 'Data kompetensi tidak ditemukan.', [], 404);
        }

        $validasi = $this->validate([
            'nama_kompetensi' => 'required',
            'akronim'         => 'required',
        ]);

        if (! $validasi) {
            return $this->jsonResponse('error', 'Data kompetensi belum lengkap.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $sukses = $this->kompetensiModel->update($id, [
            'nama_kompetensi' => (string) $this->request->getPost('nama_kompetensi'),
            'akronim'         => (string) $this->request->getPost('akronim'),
        ]);

        if (! $sukses) {
            return $this->jsonResponse('error', 'Data kompetensi gagal diperbarui.', [], 500);
        }

        $dataKompetensi = $this->kompetensiModel->ambilSemuaDenganKeterserapan();
        $barisTerbaru = null;

        foreach ($dataKompetensi as $item) {
            if ((int) $item['id_kompetensi'] === $id) {
                $barisTerbaru = $item;
                break;
            }
        }

        return $this->jsonResponse('success', 'Kompetensi berhasil diperbarui.', [
            'data' => $barisTerbaru,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD HAPUS
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini melakukan hapus lunak
    | dengan cara mengubah status_aktif menjadi 0 pada data kompetensi.
    | Alur kerja: frontend mengirim request POST ber-CSRF ke endpoint hapus,
    | controller memeriksa akses dan id, lalu menonaktifkan data serta
    | mengirim response JSON hasil aksi.
    |
    | Tips Debugging:
    | - Jika data masih muncul setelah hapus, cek query model hanya mengambil status_aktif = 1.
    | - Jika response 404, cek id pada tombol hapus sesuai data tabel.
    */
    public function hapus(int $id): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $baris = $this->kompetensiModel->find($id);
        if ($baris === null || (int) ($baris['status_aktif'] ?? 0) !== 1) {
            return $this->jsonResponse('error', 'Data kompetensi tidak ditemukan.', [], 404);
        }

        $sukses = $this->kompetensiModel->update($id, [
            'status_aktif' => 0,
        ]);

        if (! $sukses) {
            return $this->jsonResponse('error', 'Data kompetensi gagal dihapus.', [], 500);
        }

        return $this->jsonResponse('success', 'Kompetensi berhasil dihapus.', [
            'id_kompetensi' => $id,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD IS SUPERADMIN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memeriksa apakah session
    | pengguna yang sedang aktif memiliki role superadmin.
    | Alur kerja: controller memanggil helper ini sebelum memproses
    | halaman atau endpoint AJAX yang khusus untuk Super Admin.
    |
    | Tips Debugging:
    | - Jika akses superadmin selalu gagal, cek session slug_peran saat login.
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
    | yang konsisten untuk seluruh aksi AJAX modul Kompetensi,
    | termasuk token CSRF terbaru untuk request berikutnya.
    | Alur kerja: method menerima status, pesan, data tambahan, dan
    | kode HTTP, lalu menggabungkannya ke response JSON CI4.
    |
    | Tips Debugging:
    | - Jika token CSRF berikutnya tidak terbarui, cek field csrfHash di response JSON.
    | - Jika frontend tidak membaca pesan, cek struktur status dan message tetap konsisten.
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
