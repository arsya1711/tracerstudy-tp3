<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use App\Models\AktivitasModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/*
|-------------------------------------------------------------------
| CONTROLLER AKTIVITAS
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: controller ini menangani halaman
| master Aktivitas alumni untuk Super Admin, termasuk daftar data,
| simpan, update, dan hapus non permanen berbasis AJAX JSON.
| Alur kerja: user membuka halaman index untuk melihat view modul,
| lalu JavaScript memanggil endpoint JSON index/simpan/update/hapus
| melalui fetch tanpa reload halaman penuh.
|
| Tips Debugging:
| - Jika endpoint AJAX 403, cek session slug_peran harus superadmin.
| - Jika validasi gagal, cek payload nama_aktivitas dan keterangan dari form.
*/
class AktivitasController extends BaseController
{
    protected AktivitasModel $aktivitasModel;

    /*
    |-------------------------------------------------------------------
    | CONSTRUCTOR
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menyiapkan model
    | AktivitasModel agar bisa dipakai oleh semua aksi controller.
    | Alur kerja: CI4 memanggil constructor saat controller dibuat,
    | lalu instance model disimpan untuk dipakai method lain.
    |
    | Tips Debugging:
    | - Jika model tidak terbaca, cek namespace App\Models\AktivitasModel.
    */
    public function __construct()
    {
        $this->aktivitasModel = new AktivitasModel();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INDEX
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan halaman utama
    | modul Aktivitas atau mengembalikan daftar data JSON saat
    | dipanggil dari AJAX frontend.
    | Alur kerja: method memeriksa role superadmin, lalu jika request
    | AJAX mengirim data aktif dari model, sedangkan request biasa
    | akan me-render view superadmin/aktivitas/index.
    |
    | Tips Debugging:
    | - Jika selalu redirect ke login, cek session slug_peran harus superadmin.
    | - Jika tabel AJAX kosong, cek hasil ambilSemuaDenganJumlahAlumni() pada model.
    */
    public function index(): string|RedirectResponse|ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            if ($this->request->isAJAX()) {
                return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
            }

            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        if ($this->request->isAJAX()) {
            return $this->jsonResponse('success', 'Data aktivitas berhasil dimuat.', [
                'data' => $this->aktivitasModel->ambilSemuaDenganJumlahAlumni(),
            ]);
        }

        return view('superadmin/aktivitas/index', [
            'title' => 'Aktivitas - Sistem Tracer Study',
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD SIMPAN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memvalidasi lalu
    | menyimpan data aktivitas baru dari modal tambah berbasis AJAX.
    | Alur kerja: frontend mengirim POST, controller memeriksa akses,
    | memvalidasi nama dan keterangan aktivitas, menyimpan data, lalu
    | mengirim JSON hasil operasi beserta token CSRF baru.
    |
    | Tips Debugging:
    | - Jika request ditolak, cek token CSRF pada payload form AJAX.
    | - Jika validasi unik gagal, cek nama_aktivitas sudah ada di tabel tb_aktivitas.
    */
    public function simpan(): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $payload = $this->siapkanPayloadValidasi();

        if (! $this->validateData($payload, $this->ambilAturanValidasi())) {
            return $this->jsonResponse('error', 'Data aktivitas belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $data = [
            'nama_aktivitas'  => $payload['nama_aktivitas'],
            'keterangan'      => $payload['keterangan'],
            'status_aktif'    => 1,
        ];

        $insertId = $this->aktivitasModel->insert($data, true);

        if (! $insertId) {
            return $this->jsonResponse('error', 'Data aktivitas gagal disimpan.', [], 500);
        }

        $baris = $this->ambilBarisAktivitas((int) $insertId);

        return $this->jsonResponse('success', 'Aktivitas berhasil ditambahkan.', [
            'data' => $baris,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD UPDATE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memvalidasi lalu
    | memperbarui data aktivitas dari modal edit berbasis AJAX.
    | Alur kerja: frontend mengirim POST ke endpoint update, controller
    | memeriksa akses, memvalidasi input dengan pengecualian id yang
    | sedang diedit, melakukan update, lalu mengirim JSON hasilnya.
    |
    | Tips Debugging:
    | - Jika data tidak berubah, cek id_aktivitas pada URL update sesuai baris yang diedit.
    | - Jika response 404, cek data aktivitas dengan id tersebut masih aktif.
    */
    public function update(int $id): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $baris = $this->aktivitasModel->find($id);
        if ($baris === null || (int) ($baris['status_aktif'] ?? 0) !== 1) {
            return $this->jsonResponse('error', 'Data aktivitas tidak ditemukan.', [], 404);
        }

        $payload = $this->siapkanPayloadValidasi($id);

        if (! $this->validateData($payload, $this->ambilAturanValidasi($id))) {
            return $this->jsonResponse('error', 'Data aktivitas belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $sukses = $this->aktivitasModel->update($id, [
            'nama_aktivitas' => $payload['nama_aktivitas'],
            'keterangan'     => $payload['keterangan'],
        ]);

        if (! $sukses) {
            return $this->jsonResponse('error', 'Data aktivitas gagal diperbarui.', [], 500);
        }

        return $this->jsonResponse('success', 'Aktivitas berhasil diperbarui.', [
            'data' => $this->ambilBarisAktivitas($id),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD HAPUS
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini melakukan hapus lunak
    | pada data aktivitas dengan cara mengubah status_aktif menjadi 0.
    | Alur kerja: frontend mengirim GET ke endpoint hapus, controller
    | memeriksa akses dan id, lalu menonaktifkan data dan mengirim
    | response JSON hasil operasi.
    |
    | Tips Debugging:
    | - Jika data masih muncul setelah hapus, cek query model hanya mengambil status_aktif = 1.
    | - Jika response 404, cek id_aktivitas pada tombol hapus.
    */
    public function hapus(int $id): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $baris = $this->aktivitasModel->find($id);
        if ($baris === null || (int) ($baris['status_aktif'] ?? 0) !== 1) {
            return $this->jsonResponse('error', 'Data aktivitas tidak ditemukan.', [], 404);
        }

        $sukses = $this->aktivitasModel->update($id, [
            'status_aktif' => 0,
        ]);

        if (! $sukses) {
            return $this->jsonResponse('error', 'Data aktivitas gagal dihapus.', [], 500);
        }

        return $this->jsonResponse('success', 'Aktivitas berhasil dihapus.', [
            'id_aktivitas' => $id,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD SIAPKAN PAYLOAD VALIDASI
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menormalkan payload dari
    | request form agar siap dipakai oleh validasi controller.
    | Alur kerja: controller membaca POST, memangkas spasi di awal dan
    | akhir, lalu menyisipkan id placeholder untuk aturan unique update.
    |
    | Tips Debugging:
    | - Jika placeholder {id} tidak terbaca validasi, cek key id ikut dikirim dari method ini.
    */
    protected function siapkanPayloadValidasi(?int $id = null): array
    {
        $keterangan = trim((string) $this->request->getPost('keterangan'));

        return [
            'id'             => $id,
            'nama_aktivitas' => trim((string) $this->request->getPost('nama_aktivitas')),
            'keterangan'     => $keterangan !== '' ? $keterangan : null,
        ];
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL ATURAN VALIDASI
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membentuk aturan validasi
    | tambah atau edit sesuai kondisi id aktivitas.
    | Alur kerja: saat create rules memakai is_unique standar, sedangkan
    | saat update rules menambahkan pengecualian pada id yang sedang diedit.
    |
    | Tips Debugging:
    | - Jika nama yang sama saat edit tetap ditolak, cek nilai id yang dipakai placeholder {id}.
    */
    protected function ambilAturanValidasi(?int $id = null): array
    {
        $aturanNama = 'required|max_length[100]|is_unique[tb_aktivitas.nama_aktivitas]';

        if ($id !== null) {
            $aturanNama = 'required|max_length[100]|is_unique[tb_aktivitas.nama_aktivitas,id_aktivitas,{id}]';
        }

        return [
            'id' => 'permit_empty|integer',
            'nama_aktivitas' => [
                'rules' => $aturanNama,
            ],
            'keterangan' => [
                'rules' => 'permit_empty',
            ],
        ];
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL BARIS AKTIVITAS
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengambil satu data
    | aktivitas aktif lengkap dengan jumlah alumni untuk response AJAX.
    | Alur kerja: controller membaca daftar aktivitas dari model,
    | lalu mencari baris yang id-nya sesuai dengan kebutuhan response.
    |
    | Tips Debugging:
    | - Jika data response null, cek id_aktivitas memang masih aktif setelah simpan atau update.
    */
    protected function ambilBarisAktivitas(int $id): ?array
    {
        foreach ($this->aktivitasModel->ambilSemuaDenganJumlahAlumni() as $item) {
            if ((int) $item['id_aktivitas'] === $id) {
                return $item;
            }
        }

        return null;
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
    | yang konsisten untuk seluruh aksi AJAX modul Aktivitas, termasuk
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
