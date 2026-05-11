<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use App\Models\LowonganModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use RuntimeException;

/*
|-------------------------------------------------------------------
| CONTROLLER DATA LOWONGAN
|-------------------------------------------------------------------
| Controller ini menangani seluruh kebutuhan modul lowongan untuk
| superadmin: menampilkan tabel, menyimpan data, mengubah data,
| menghapus data, serta mengelola upload flyer lowongan.
|
| Alur kerja:
| 1. index() merender view atau memasok data AJAX untuk DataTables.
| 2. simpan()/update() memvalidasi input lalu menyimpan ke database.
| 3. hapus()/hapusMassal() menjaga agar lowongan tidak terhapus jika
|    sudah memiliki lamaran.
|
| Tips Debugging:
| - Jika AJAX gagal, periksa guardAjaxSuperadmin() dan respons JSON.
| - Jika upload flyer bermasalah, cek folder public/uploads/lowongan.
*/
class LowonganController extends BaseController
{
    protected LowonganModel $lowonganModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    /*
    |-------------------------------------------------------------------
    | KONSTRUKTOR MODUL
    |-------------------------------------------------------------------
    | Menyiapkan helper URL, model lowongan, dan koneksi database yang
    | dipakai beberapa helper pengecekan seperti relasi tb_lamaran.
    */
    public function __construct()
    {
        helper('url');
        $this->lowonganModel = new LowonganModel();
        $this->db            = Database::connect();
    }

    /*
    |-------------------------------------------------------------------
    | HALAMAN INDEX DAN SUPLAI DATATABLES
    |-------------------------------------------------------------------
    | Method ini melayani dua kebutuhan sekaligus:
    | - request biasa untuk merender halaman Data Lowongan
    | - request AJAX untuk mengirim data tabel dalam format JSON
    |
    | Tips Debugging:
    | - Jika tabel kosong padahal data ada, cek formatLowonganRow()
    |   dan payload yang dikirim LowonganModel::getDataTables().
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
            $result = $this->lowonganModel->getDataTables($this->request);
            $result['data'] = array_map(fn(array $row): array => $this->formatLowonganRow($row), $result['data']);
            $result['csrfHash'] = csrf_hash();

            return $this->response->setJSON($result);
        }

        $daftarPerusahaan = $this->lowonganModel->ambilDaftarPerusahaanRekrutmen();

        $areaPrefix = $this->ambilAreaPrefix();

        return view('superadmin/lowongan/index', [
            'title'             => 'Data Lowongan - Sistem Tracer Study & BKK',
            'areaPrefix'        => $areaPrefix,
            'dashboardUrl'      => $this->ambilDashboardUrl(),
            'pageHeading'       => $areaPrefix === 'admin-sekolah' ? 'Lowongan Kerja' : 'Data Lowongan',
            'breadcrumbParent'  => 'Manajemen DUDI',
            'breadcrumbCurrent' => 'Lowongan Kerja',
            'daftar_perusahaan' => $daftarPerusahaan,
            'daftar_status'     => $this->ambilDaftarStatus(),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | SIMPAN LOWONGAN BARU
    |-------------------------------------------------------------------
    | Method ini membaca input form, memvalidasi data, menyimpan flyer
    | jika ada, lalu membuat record lowongan baru di database.
    |
    | Tips Debugging:
    | - Jika simpan gagal setelah upload, cek proses rollback file pada
    |   blok catch agar tidak ada file yatim di folder uploads.
    */
    public function simpan(): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $payload = $this->ambilPayloadLowongan();
        $payload['slug_lowongan'] = $this->generateUniqueSlug($payload['judul_lowongan'], $payload['posisi']);

        $validation = $this->validasiLowongan($payload);
        if ($validation !== null) {
            return $validation;
        }

        $errorFlyer = $this->validateFlyerUpload();
        if ($errorFlyer !== null) {
            return $this->jsonResponse('error', $errorFlyer, [], 422);
        }

        $uploadedFlyer = null;

        try {
            $uploadedFlyer = $this->simpanFlyer();

            $idLowongan = $this->lowonganModel->insert([
                'id_perusahaan'       => $payload['id_perusahaan'],
                'dibuat_oleh'         => (int) (session('id_pengguna') ?? 0),
                'judul_lowongan'      => $payload['judul_lowongan'],
                'posisi'              => $payload['posisi'],
                'slug_lowongan'       => $payload['slug_lowongan'],
                'flyer_lowongan'      => $uploadedFlyer,
                'deskripsi_pekerjaan' => $payload['deskripsi_pekerjaan'],
                'kualifikasi'         => $payload['kualifikasi'],
                'jumlah_kebutuhan'    => $payload['jumlah_kebutuhan'],
                'jenis_pekerjaan'     => $payload['jenis_pekerjaan'],
                'sistem_kerja'        => $payload['sistem_kerja'],
                'pendidikan_min'      => $payload['pendidikan_min'],
                'pengalaman_min'      => $payload['pengalaman_min'],
                'rentang_gaji'        => $payload['rentang_gaji'],
                'lokasi_kerja'        => $payload['lokasi_kerja'],
                'batas_lamaran'       => $payload['batas_lamaran'],
                'tayang_hingga'       => $payload['tayang_hingga'],
                'status'              => $payload['status'],
            ], true);

            if (! $idLowongan) {
                throw new RuntimeException('Data lowongan gagal disimpan.');
            }

            return $this->jsonResponse('success', 'Lowongan berhasil ditambahkan.', [
                'id_lowongan' => $idLowongan,
            ]);
        } catch (\Throwable $th) {
            if ($uploadedFlyer !== null) {
                $this->hapusFileLokal($uploadedFlyer);
            }

            return $this->jsonResponse('error', $th->getMessage(), [], 500);
        }
    }

    /*
    |-------------------------------------------------------------------
    | UPDATE DATA LOWONGAN
    |-------------------------------------------------------------------
    | Memperbarui record lowongan yang sudah ada, termasuk pergantian
    | flyer lama, penghapusan flyer, dan regenerasi slug jika perlu.
    |
    | Tips Debugging:
    | - Jika flyer lama tidak terhapus, cek kondisi $flyerLama dan
    |   path relatif yang diterima hapusFileLokal().
    */
    public function update(int $idLowongan): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $lowongan = $this->lowonganModel->ambilDetailById($idLowongan);
        if ($lowongan === null) {
            return $this->jsonResponse('error', 'Data lowongan tidak ditemukan.', [], 404);
        }

        $payload = $this->ambilPayloadLowongan();
        $payload['slug_lowongan'] = $this->generateUniqueSlug($payload['judul_lowongan'], $payload['posisi'], $idLowongan);

        $validation = $this->validasiLowongan($payload, $idLowongan);
        if ($validation !== null) {
            return $validation;
        }

        $errorFlyer = $this->validateFlyerUpload();
        if ($errorFlyer !== null) {
            return $this->jsonResponse('error', $errorFlyer, [], 422);
        }

        $uploadedFlyer = null;
        $flyerLama = $lowongan['flyer_lowongan'] ?? null;

        try {
            $uploadedFlyer = $this->simpanFlyer();
            $flyerFinal = $flyerLama;

            if ($payload['flyer_remove']) {
                $flyerFinal = null;
            }

            if ($uploadedFlyer !== null) {
                $flyerFinal = $uploadedFlyer;
            }

            $sukses = $this->lowonganModel->update($idLowongan, [
                'id_perusahaan'       => $payload['id_perusahaan'],
                'judul_lowongan'      => $payload['judul_lowongan'],
                'posisi'              => $payload['posisi'],
                'slug_lowongan'       => $payload['slug_lowongan'],
                'flyer_lowongan'      => $flyerFinal,
                'deskripsi_pekerjaan' => $payload['deskripsi_pekerjaan'],
                'kualifikasi'         => $payload['kualifikasi'],
                'jumlah_kebutuhan'    => $payload['jumlah_kebutuhan'],
                'jenis_pekerjaan'     => $payload['jenis_pekerjaan'],
                'sistem_kerja'        => $payload['sistem_kerja'],
                'pendidikan_min'      => $payload['pendidikan_min'],
                'pengalaman_min'      => $payload['pengalaman_min'],
                'rentang_gaji'        => $payload['rentang_gaji'],
                'lokasi_kerja'        => $payload['lokasi_kerja'],
                'batas_lamaran'       => $payload['batas_lamaran'],
                'tayang_hingga'       => $payload['tayang_hingga'],
                'status'              => $payload['status'],
            ]);

            if (! $sukses) {
                throw new RuntimeException('Data lowongan gagal diperbarui.');
            }

            if (($uploadedFlyer !== null || $payload['flyer_remove']) && $flyerLama !== null && $flyerLama !== $uploadedFlyer) {
                $this->hapusFileLokal($flyerLama);
            }

            return $this->jsonResponse('success', 'Lowongan berhasil diperbarui.', [
                'id_lowongan' => $idLowongan,
            ]);
        } catch (\Throwable $th) {
            if ($uploadedFlyer !== null) {
                $this->hapusFileLokal($uploadedFlyer);
            }

            return $this->jsonResponse('error', $th->getMessage(), [], 500);
        }
    }

    /*
    |-------------------------------------------------------------------
    | HAPUS SATU LOWONGAN
    |-------------------------------------------------------------------
    | Menghapus data lowongan tunggal jika belum memiliki lamaran, lalu
    | membersihkan file flyer terkait agar storage tetap rapi.
    */
    public function hapus(int $idLowongan): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $lowongan = $this->lowonganModel->ambilDetailById($idLowongan);
        if ($lowongan === null) {
            return $this->jsonResponse('error', 'Data lowongan tidak ditemukan.', [], 404);
        }

        if ($this->punyaLamaran($idLowongan)) {
            return $this->jsonResponse('error', 'Lowongan tidak dapat dihapus karena sudah memiliki lamaran.', [], 422);
        }

        $flyer = $lowongan['flyer_lowongan'] ?? null;
        $sukses = $this->lowonganModel->delete($idLowongan);

        if (! $sukses) {
            return $this->jsonResponse('error', 'Data lowongan gagal dihapus.', [], 500);
        }

        $this->hapusFileLokal($flyer);

        return $this->jsonResponse('success', 'Lowongan berhasil dihapus.', [
            'id_lowongan' => $idLowongan,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | HAPUS MASSAL LOWONGAN
    |-------------------------------------------------------------------
    | Dipakai saat admin memilih beberapa baris sekaligus. Method ini
    | memvalidasi seluruh ID, memastikan tidak ada relasi lamaran, lalu
    | menghapus data dan flyer yang terkait.
    |
    | Tips Debugging:
    | - Jika hapus massal tertolak, periksa apakah salah satu lowongan
    |   sudah memiliki data di tb_lamaran.
    */
    public function hapusMassal(): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $ids = $this->request->getPost('ids');
        if (! is_array($ids) || $ids === []) {
            return $this->jsonResponse('error', 'Tidak ada lowongan yang dipilih.', [], 422);
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, static fn(int $id): bool => $id > 0);

        if ($ids === []) {
            return $this->jsonResponse('error', 'ID lowongan tidak valid.', [], 422);
        }

        foreach ($ids as $idLowongan) {
            if ($this->punyaLamaran($idLowongan)) {
                return $this->jsonResponse('error', 'Salah satu lowongan sudah memiliki lamaran sehingga tidak bisa dihapus massal.', [], 422);
            }
        }

        $rows = $this->lowonganModel->whereIn('id_lowongan', $ids)->findAll();
        $flyers = array_filter(array_map(static fn(array $row): ?string => $row['flyer_lowongan'] ?? null, $rows));

        $sukses = $this->lowonganModel->whereIn('id_lowongan', $ids)->delete();

        if (! $sukses) {
            return $this->jsonResponse('error', 'Lowongan terpilih gagal dihapus.', [], 500);
        }

        foreach ($flyers as $flyer) {
            $this->hapusFileLokal($flyer);
        }

        return $this->jsonResponse('success', 'Lowongan terpilih berhasil dihapus.', [
            'ids' => $ids,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | NORMALISASI PAYLOAD FORM
    |-------------------------------------------------------------------
    | Input mentah dari request diubah ke format yang lebih siap dipakai
    | backend, termasuk trim string, nilai default, dan datetime-local
    | menjadi format MySQL.
    |
    | Tips Debugging:
    | - Jika tayang_hingga tidak tersimpan, cek konversi huruf T menjadi
    |   spasi pada input datetime-local dari browser.
    */
    protected function ambilPayloadLowongan(): array
    {
        $tayangHingga = trim((string) $this->request->getPost('tayang_hingga'));
        if ($tayangHingga !== '') {
            $tayangHingga = str_replace('T', ' ', $tayangHingga);

            if (strlen($tayangHingga) === 16) {
                $tayangHingga .= ':00';
            }
        }

        return [
            'id_perusahaan'       => (int) ($this->request->getPost('id_perusahaan') ?? 0),
            'judul_lowongan'      => trim((string) $this->request->getPost('judul_lowongan')),
            'posisi'              => trim((string) $this->request->getPost('posisi')),
            'deskripsi_pekerjaan' => trim((string) $this->request->getPost('deskripsi_pekerjaan')) ?: null,
            'kualifikasi'         => trim((string) $this->request->getPost('kualifikasi')) ?: null,
            'jumlah_kebutuhan'    => max(1, (int) ($this->request->getPost('jumlah_kebutuhan') ?? 1)),
            'jenis_pekerjaan'     => trim((string) $this->request->getPost('jenis_pekerjaan')),
            'sistem_kerja'        => trim((string) $this->request->getPost('sistem_kerja')),
            'pendidikan_min'      => trim((string) $this->request->getPost('pendidikan_min')) ?: null,
            'pengalaman_min'      => trim((string) $this->request->getPost('pengalaman_min')) ?: null,
            'rentang_gaji'        => trim((string) $this->request->getPost('rentang_gaji')) ?: null,
            'lokasi_kerja'        => trim((string) $this->request->getPost('lokasi_kerja')) ?: null,
            'batas_lamaran'       => trim((string) $this->request->getPost('batas_lamaran')) ?: null,
            'tayang_hingga'       => $tayangHingga !== '' ? $tayangHingga : null,
            'status'              => trim((string) $this->request->getPost('status')),
            'flyer_remove'        => (string) $this->request->getPost('flyer_remove') === '1',
        ];
    }

    /*
    |-------------------------------------------------------------------
    | VALIDASI DATA LOWONGAN
    |-------------------------------------------------------------------
    | Method ini menggabungkan validasi field, validasi relasi bisnis
    | DUDI rekrutmen, serta aturan tanggal dan slug unik.
    |
    | Tips Debugging:
    | - Jika DUDI valid tapi tetap ditolak, cek relasi kerjasama slug
    |   `rekrutmen` pada tabel pivot perusahaan.
    */
    protected function validasiLowongan(array $payload, ?int $idLowongan = null): ?ResponseInterface
    {
        if (! $this->validateData($payload, [
            'id_perusahaan'    => 'required|integer',
            'judul_lowongan'   => 'required|max_length[150]',
            'posisi'           => 'required|max_length[100]',
            'jumlah_kebutuhan' => 'permit_empty|integer|greater_than_equal_to[1]',
            'jenis_pekerjaan'  => 'required|in_list[fulltime,parttime,magang,kontrak,freelance]',
            'sistem_kerja'     => 'required|in_list[onsite,remote,hybrid]',
            'pendidikan_min'   => 'permit_empty|in_list[SMP,SMA/SMK,D3,S1,S2]',
            'pengalaman_min'   => 'permit_empty|max_length[50]',
            'rentang_gaji'     => 'permit_empty|max_length[50]',
            'lokasi_kerja'     => 'permit_empty|max_length[150]',
            'batas_lamaran'    => 'permit_empty|valid_date[Y-m-d]',
            'tayang_hingga'    => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'status'           => 'required|in_list[draft,aktif,ditutup,kadaluarsa]',
        ])) {
            return $this->jsonResponse('error', 'Data lowongan belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        if (! $this->lowonganModel->perusahaanMemilikiKerjasamaRekrutmen($payload['id_perusahaan'])) {
            return $this->jsonResponse('error', 'DUDI yang dipilih belum memiliki kerjasama rekrutmen aktif.', [], 422);
        }

        if (
            $payload['batas_lamaran'] !== null
            && $payload['tayang_hingga'] !== null
            && strtotime($payload['tayang_hingga']) < strtotime($payload['batas_lamaran'])
        ) {
            return $this->jsonResponse('error', 'Tayang hingga tidak boleh lebih awal dari batas lamaran.', [], 422);
        }

        if ($this->lowonganModel->slugDipakai($payload['slug_lowongan'], $idLowongan)) {
            return $this->jsonResponse('error', 'Slug lowongan sudah digunakan. Coba ubah judul atau posisi.', [], 422);
        }

        return null;
    }

    /*
    |-------------------------------------------------------------------
    | GENERATE SLUG LOWONGAN
    |-------------------------------------------------------------------
    | Slug dibentuk dari kombinasi judul dan posisi agar URL atau
    | identitas internal lowongan tetap mudah dibaca dan unik.
    */
    protected function generateUniqueSlug(string $judulLowongan, string $posisi, ?int $idLowongan = null): string
    {
        $base = url_title(trim($judulLowongan . ' ' . $posisi), '-', true);
        $base = $base !== '' ? $base : 'lowongan';
        $slug = $base;
        $counter = 1;

        while ($this->lowonganModel->slugDipakai($slug, $idLowongan)) {
            $counter++;
            $slug = $base . '-' . $counter;
        }

        return $slug;
    }

    /*
    |-------------------------------------------------------------------
    | VALIDASI FILE FLYER
    |-------------------------------------------------------------------
    | Mengecek validitas file upload, ukuran maksimum, dan ekstensi yang
    | diizinkan sebelum file benar-benar dipindahkan ke storage publik.
    */
    protected function validateFlyerUpload(): ?string
    {
        $file = $this->request->getFile('flyer_lowongan');

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (! $file->isValid()) {
            return 'File flyer tidak valid.';
        }

        if ($file->getSizeByUnit('kb') > 4096) {
            return 'Ukuran flyer maksimal 4 MB.';
        }

        $ext = strtolower((string) $file->getExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return 'Format flyer harus jpg, jpeg, atau png.';
        }

        return null;
    }

    /*
    |-------------------------------------------------------------------
    | SIMPAN FILE FLYER
    |-------------------------------------------------------------------
    | File flyer yang lolos validasi dipindahkan ke folder upload publik
    | dengan nama acak agar aman dari bentrok nama file.
    |
    | Tips Debugging:
    | - Jika upload gagal, pastikan folder public/uploads/lowongan bisa
    |   dibuat dan memiliki permission tulis.
    */
    protected function simpanFlyer(): ?string
    {
        $file = $this->request->getFile('flyer_lowongan');

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (! $file->isValid() || $file->hasMoved()) {
            throw new RuntimeException('Upload flyer gagal diproses.');
        }

        $targetDirectory = FCPATH . 'uploads/lowongan';
        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            throw new RuntimeException('Direktori upload flyer lowongan tidak dapat dibuat.');
        }

        $randomName = $file->getRandomName();
        $file->move($targetDirectory, $randomName);

        return 'uploads/lowongan/' . $randomName;
    }

    /*
    |-------------------------------------------------------------------
    | HAPUS FILE LOKAL
    |-------------------------------------------------------------------
    | Utilitas kecil ini dipakai untuk membersihkan file flyer lama atau
    | file yang gagal tersimpan ke database agar storage tetap bersih.
    */
    protected function hapusFileLokal(?string $relativePath): void
    {
        if ($relativePath === null || trim($relativePath) === '') {
            return;
        }

        $fullPath = FCPATH . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    /*
    |-------------------------------------------------------------------
    | CEK RELASI LAMARAN
    |-------------------------------------------------------------------
    | Lowongan yang sudah memiliki lamaran tidak boleh dihapus agar
    | histori transaksi pelamar tetap terjaga.
    */
    protected function punyaLamaran(int $idLowongan): bool
    {
        if (! $this->db->tableExists('tb_lamaran')) {
            return false;
        }

        return $this->db->table('tb_lamaran')
            ->where('id_lowongan', $idLowongan)
            ->countAllResults() > 0;
    }

    /*
    |-------------------------------------------------------------------
    | FORMAT DATA UNTUK FRONTEND
    |-------------------------------------------------------------------
    | Baris dari database dilengkapi URL flyer, fallback tampilan, dan
    | pembersihan string sebelum dikirim ke DataTables.
    */
    protected function formatLowonganRow(array $row): array
    {
        $row['flyer_url'] = ! empty($row['flyer_lowongan'])
            ? base_url((string) $row['flyer_lowongan'])
            : base_url('assets/media/svg/files/blank-image.svg');

        $row['pemosting_nama'] = trim((string) ($row['pemosting_nama'] ?? '')) ?: 'System';
        $row['kualifikasi'] = trim((string) ($row['kualifikasi'] ?? ''));
        $row['judul_lowongan'] = trim((string) ($row['judul_lowongan'] ?? ''));
        $row['posisi'] = trim((string) ($row['posisi'] ?? ''));
        $row['batas_lamaran'] = trim((string) ($row['batas_lamaran'] ?? ''));
        $row['tayang_hingga'] = trim((string) ($row['tayang_hingga'] ?? ''));
        $row['lokasi_kerja'] = trim((string) ($row['lokasi_kerja'] ?? ''));
        $row['pengalaman_min'] = trim((string) ($row['pengalaman_min'] ?? ''));
        $row['rentang_gaji'] = trim((string) ($row['rentang_gaji'] ?? ''));
        $row['deskripsi_pekerjaan'] = trim((string) ($row['deskripsi_pekerjaan'] ?? ''));

        return $row;
    }

    /*
    |-------------------------------------------------------------------
    | MASTER STATUS LOWONGAN
    |-------------------------------------------------------------------
    | Daftar status dipusatkan di sini agar opsi filter dan form tetap
    | konsisten di seluruh modul backend.
    */
    protected function ambilDaftarStatus(): array
    {
        return [
            'draft'      => 'Draft',
            'aktif'      => 'Aktif',
            'ditutup'    => 'Ditutup',
            'kadaluarsa' => 'Kadaluarsa',
        ];
    }

    /*
    |-------------------------------------------------------------------
    | GUARD HAK AKSES SUPERADMIN
    |-------------------------------------------------------------------
    | Helper ini memisahkan logika otorisasi agar method utama lebih
    | ringkas dan mudah dibaca.
    */
    protected function isSuperadmin(): bool
    {
        return in_array((string) session()->get('slug_peran'), ['superadmin', 'admin_sekolah'], true);
    }

    protected function guardAjaxSuperadmin(): ?ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        if (! $this->request->isAJAX()) {
            return $this->jsonResponse('error', 'Request tidak valid.', [], 400);
        }

        return null;
    }

    /*
    |-------------------------------------------------------------------
    | KONTEKS AREA BACKOFFICE
    |-------------------------------------------------------------------
    | Menentukan prefix route halaman lowongan ketika view yang sama
    | dipakai oleh Super Admin dan Admin Sekolah/BKK.
    |
    | Tips Debugging:
    | - Jika DataTables lowongan tidak memuat, cek indexUrl di
    |   window.ktLowonganConfig.
    */
    protected function ambilAreaPrefix(): string
    {
        return session()->get('slug_peran') === 'admin_sekolah' ? 'admin-sekolah' : 'superadmin';
    }

    protected function ambilDashboardUrl(): string
    {
        return base_url($this->ambilAreaPrefix() === 'admin-sekolah' ? 'admin-sekolah/dashboard' : 'dashboard/superadmin');
    }

    /*
    |-------------------------------------------------------------------
    | HELPER RESPONS JSON STANDAR
    |-------------------------------------------------------------------
    | Semua respons AJAX dikirim melalui format yang sama agar frontend
    | dapat membaca status, pesan, dan csrfHash secara konsisten.
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
