<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use App\Models\KerjasamaModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/*
|-------------------------------------------------------------------
| CONTROLLER KERJASAMA
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: controller ini menangani halaman master
| jenis Kerjasama untuk Super Admin, termasuk daftar data, simpan,
| update, dan hapus non permanen berbasis AJAX JSON.
| Alur kerja: user membuka halaman index untuk melihat view modul,
| lalu JavaScript memanggil endpoint JSON index/simpan/update/hapus
| melalui fetch tanpa reload halaman penuh.
|
| Tips Debugging:
| - Jika endpoint AJAX 403, cek session slug_peran harus superadmin.
| - Jika validasi gagal, cek payload nama_kerjasama, slug_kerjasama, dan deskripsi dari form.
*/
class KerjasamaController extends BaseController
{
    protected KerjasamaModel $kerjasamaModel;

    /*
    |-------------------------------------------------------------------
    | CONSTRUCTOR
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menyiapkan model
    | KerjasamaModel dan helper URL agar bisa dipakai oleh seluruh
    | aksi controller termasuk pembentukan slug otomatis.
    | Alur kerja: CI4 memanggil constructor saat controller dibuat,
    | lalu instance model disimpan untuk dipakai method lain.
    |
    | Tips Debugging:
    | - Jika url_title tidak dikenali, cek helper url sudah dimuat di constructor ini.
    */
    public function __construct()
    {
        helper('url');
        $this->kerjasamaModel = new KerjasamaModel();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INDEX
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan halaman utama
    | modul Kerjasama atau mengembalikan daftar data JSON saat
    | dipanggil dari AJAX frontend.
    | Alur kerja: method memeriksa role superadmin, lalu jika request
    | AJAX mengirim data aktif dari model, sedangkan request biasa
    | akan me-render view superadmin/kerjasama/index.
    |
    | Tips Debugging:
    | - Jika selalu redirect ke login, cek session slug_peran harus superadmin.
    | - Jika tabel AJAX kosong, cek hasil ambilSemuaDenganJumlahMou() pada model.
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
            return $this->jsonResponse('success', 'Data kerjasama berhasil dimuat.', [
                'data' => $this->kerjasamaModel->ambilSemuaDenganJumlahMou(),
            ]);
        }

        return view('superadmin/kerjasama/index', [
            'title' => 'Kerjasama - Sistem Tracer Study & BKK',
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD SIMPAN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memvalidasi lalu
    | menyimpan data jenis kerjasama baru dari modal tambah berbasis AJAX.
    | Alur kerja: frontend mengirim POST, controller memeriksa akses,
    | menormalkan slug bila kosong, memvalidasi input, menyimpan data,
    | lalu mengirim JSON hasil operasi beserta token CSRF baru.
    |
    | Tips Debugging:
    | - Jika request ditolak, cek token CSRF pada payload form AJAX.
    | - Jika validasi unik gagal, cek nama_kerjasama atau slug_kerjasama sudah ada di tabel tb_kerjasama.
    */
    public function simpan(): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $payload = $this->siapkanPayloadValidasi();

        if (! $this->validateData($payload, $this->ambilAturanValidasi())) {
            return $this->jsonResponse('error', 'Data kerjasama belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $data = [
            'nama_kerjasama' => $payload['nama_kerjasama'],
            'slug_kerjasama' => $payload['slug_kerjasama'],
            'deskripsi'      => $payload['deskripsi'],
            'status_aktif'   => 1,
        ];

        $insertId = $this->kerjasamaModel->insert($data, true);

        if (! $insertId) {
            return $this->jsonResponse('error', 'Data kerjasama gagal disimpan.', [], 500);
        }

        return $this->jsonResponse('success', 'Kerjasama berhasil ditambahkan.', [
            'data' => $this->ambilBarisKerjasama((int) $insertId),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD UPDATE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memvalidasi lalu
    | memperbarui data jenis kerjasama dari modal edit berbasis AJAX.
    | Alur kerja: frontend mengirim POST ke endpoint update, controller
    | memeriksa akses, menormalkan slug bila kosong, memvalidasi input
    | dengan pengecualian id yang sedang diedit, lalu mengirim JSON hasilnya.
    |
    | Tips Debugging:
    | - Jika data tidak berubah, cek id_kerjasama pada URL update sesuai baris yang diedit.
    | - Jika response 404, cek data kerjasama dengan id tersebut masih aktif.
    */
    public function update(int $id): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $baris = $this->kerjasamaModel->find($id);
        if ($baris === null || (int) ($baris['status_aktif'] ?? 0) !== 1) {
            return $this->jsonResponse('error', 'Data kerjasama tidak ditemukan.', [], 404);
        }

        $payload = $this->siapkanPayloadValidasi($id);

        if (! $this->validateData($payload, $this->ambilAturanValidasi($id))) {
            return $this->jsonResponse('error', 'Data kerjasama belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $sukses = $this->kerjasamaModel->update($id, [
            'nama_kerjasama' => $payload['nama_kerjasama'],
            'slug_kerjasama' => $payload['slug_kerjasama'],
            'deskripsi'      => $payload['deskripsi'],
        ]);

        if (! $sukses) {
            return $this->jsonResponse('error', 'Data kerjasama gagal diperbarui.', [], 500);
        }

        return $this->jsonResponse('success', 'Kerjasama berhasil diperbarui.', [
            'data' => $this->ambilBarisKerjasama($id),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD HAPUS
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini melakukan hapus lunak
    | pada data kerjasama dengan cara mengubah status_aktif menjadi 0.
    | Alur kerja: frontend mengirim GET ke endpoint hapus, controller
    | memeriksa akses dan id, lalu menonaktifkan data dan mengirim
    | response JSON hasil operasi.
    |
    | Tips Debugging:
    | - Jika data masih muncul setelah hapus, cek query model hanya mengambil status_aktif = 1.
    | - Jika response 404, cek id_kerjasama pada tombol hapus.
    */
    public function hapus(int $id): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $baris = $this->kerjasamaModel->find($id);
        if ($baris === null || (int) ($baris['status_aktif'] ?? 0) !== 1) {
            return $this->jsonResponse('error', 'Data kerjasama tidak ditemukan.', [], 404);
        }

        $sukses = $this->kerjasamaModel->update($id, [
            'status_aktif' => 0,
        ]);

        if (! $sukses) {
            return $this->jsonResponse('error', 'Data kerjasama gagal dihapus.', [], 500);
        }

        return $this->jsonResponse('success', 'Kerjasama berhasil dihapus.', [
            'id_kerjasama' => $id,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD SIAPKAN PAYLOAD VALIDASI
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menormalkan payload dari
    | request form agar siap dipakai oleh validasi controller.
    | Alur kerja: controller membaca POST, memangkas spasi, membuat
    | slug otomatis dari nama bila slug kosong, lalu menyisipkan id
    | placeholder untuk aturan unique update.
    |
    | Tips Debugging:
    | - Jika slug kosong saat validasi, cek nama_kerjasama terkirim dan helper url_title aktif.
    */
    protected function siapkanPayloadValidasi(?int $id = null): array
    {
        $namaKerjasama = trim((string) $this->request->getPost('nama_kerjasama'));
        $slugKerjasama = strtolower(trim((string) $this->request->getPost('slug_kerjasama')));
        $deskripsi = trim((string) $this->request->getPost('deskripsi'));

        if ($slugKerjasama === '' && $namaKerjasama !== '') {
            $slugKerjasama = url_title($namaKerjasama, '-', true);
        }

        return [
            'id'             => $id,
            'nama_kerjasama' => $namaKerjasama,
            'slug_kerjasama' => $slugKerjasama,
            'deskripsi'      => $deskripsi !== '' ? $deskripsi : null,
        ];
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL ATURAN VALIDASI
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membentuk aturan validasi
    | tambah atau edit sesuai kondisi id kerjasama.
    | Alur kerja: saat create rules memakai is_unique standar, sedangkan
    | saat update rules menambahkan pengecualian pada id yang sedang diedit.
    |
    | Tips Debugging:
    | - Jika nama atau slug yang sama saat edit tetap ditolak, cek nilai id yang dipakai placeholder {id}.
    */
    protected function ambilAturanValidasi(?int $id = null): array
    {
        $aturanNama = 'required|max_length[150]|is_unique[tb_kerjasama.nama_kerjasama]';
        $aturanSlug = 'required|alpha_dash|is_unique[tb_kerjasama.slug_kerjasama]';

        if ($id !== null) {
            $aturanNama = 'required|max_length[150]|is_unique[tb_kerjasama.nama_kerjasama,id_kerjasama,{id}]';
            $aturanSlug = 'required|alpha_dash|is_unique[tb_kerjasama.slug_kerjasama,id_kerjasama,{id}]';
        }

        return [
            'id' => 'permit_empty|integer',
            'nama_kerjasama' => [
                'rules' => $aturanNama,
            ],
            'slug_kerjasama' => [
                'rules' => $aturanSlug,
            ],
            'deskripsi' => [
                'rules' => 'permit_empty',
            ],
        ];
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL BARIS KERJASAMA
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengambil satu data
    | kerjasama aktif lengkap dengan jumlah MoU untuk response AJAX.
    | Alur kerja: controller membaca daftar kerjasama dari model,
    | lalu mencari baris yang id-nya sesuai dengan kebutuhan response.
    |
    | Tips Debugging:
    | - Jika data response null, cek id_kerjasama memang masih aktif setelah simpan atau update.
    */
    protected function ambilBarisKerjasama(int $id): ?array
    {
        foreach ($this->kerjasamaModel->ambilSemuaDenganJumlahMou() as $item) {
            if ((int) $item['id_kerjasama'] === $id) {
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
    | yang konsisten untuk seluruh aksi AJAX modul Kerjasama, termasuk
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
