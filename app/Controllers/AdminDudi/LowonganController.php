<?php

namespace App\Controllers\AdminDudi;

use App\Controllers\BaseController;
use App\Models\LowonganModel;
use App\Models\PerusahaanModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;
use RuntimeException;

/*
|-------------------------------------------------------------------
| CONTROLLER LOWONGAN ADMIN DUDI / HRD
|-------------------------------------------------------------------
| Controller ini menampilkan daftar lowongan yang dimiliki oleh
| perusahaan tempat akun Admin DUDI/HRD terhubung.
|
| Alur kerja:
| 1. Sistem memastikan pengguna login memiliki role admin_dudi.
| 2. Perusahaan dicari melalui tb_perusahaan.id_pengguna.
| 3. Lowongan difilter berdasarkan id_perusahaan agar HRD tidak bisa
|    melihat lowongan perusahaan lain.
|
| Tips Debugging:
| - Jika halaman kosong, cek tb_perusahaan.id_pengguna sudah terisi.
| - Jika lowongan perusahaan lain muncul, cek where id_perusahaan pada
|   LowonganModel::ambilByPerusahaan().
*/
class LowonganController extends BaseController
{
    protected PerusahaanModel $perusahaanModel;
    protected LowonganModel $lowonganModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        helper('url');
        $this->perusahaanModel = new PerusahaanModel();
        $this->lowonganModel   = new LowonganModel();
        $this->db              = Database::connect();
    }

    /*
    |-------------------------------------------------------------------
    | HALAMAN LOWONGAN SAYA
    |-------------------------------------------------------------------
    | Menampilkan lowongan milik perusahaan login dengan search dan
    | filter status sederhana seperti pola tabel modul lain.
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
        $lowongan = $this->lowonganModel->ambilByPerusahaan((int) $perusahaan['id_perusahaan'], [
            'search' => $search,
            'status' => $status,
        ]);

        return view('admin_dudi/lowongan/index', [
            'title'        => 'Lowongan Saya - Sistem Tracer Study & BKK',
            'perusahaan'   => $perusahaan,
            'lowongan'     => $lowongan,
            'jumlahLamaran'=> $this->hitungJumlahLamaranPerLowongan(array_column($lowongan, 'id_lowongan')),
            'keyword'      => $search,
            'statusFilter' => $status,
            'daftarStatus' => $this->ambilDaftarStatusLowongan(),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | SIMPAN LOWONGAN BARU ADMIN DUDI
    |-------------------------------------------------------------------
    | Admin DUDI boleh membuat lowongan, tetapi id_perusahaan tidak
    | diambil dari form. Sistem otomatis memakai perusahaan yang
    | terhubung dengan akun login agar tidak bisa membuat lowongan
    | atas nama DUDI lain.
    |
    | Tips Debugging:
    | - Jika selalu gagal simpan, cek perusahaan login memiliki
    |   kerjasama dengan slug `rekrutmen`.
    */
    public function simpan(): RedirectResponse
    {
        $perusahaan = $this->validasiAksesPerusahaan();
        if ($perusahaan instanceof RedirectResponse) {
            return $perusahaan;
        }

        $payload = $this->ambilPayloadLowongan((int) $perusahaan['id_perusahaan']);
        $payload['slug_lowongan'] = $this->generateUniqueSlug($payload['judul_lowongan'], $payload['posisi']);

        $redirect = redirect()->to(site_url('admin-dudi/lowongan'));
        $validasi = $this->validasiLowongan($payload, (int) $perusahaan['id_perusahaan']);
        if ($validasi !== null) {
            return $redirect->withInput()->with('error', $validasi);
        }

        $errorFlyer = $this->validateFlyerUpload();
        if ($errorFlyer !== null) {
            return $redirect->withInput()->with('error', $errorFlyer);
        }

        $uploadedFlyer = null;

        try {
            $uploadedFlyer = $this->simpanFlyer();
            $idLowongan = $this->lowonganModel->insert([
                'id_perusahaan'       => $payload['id_perusahaan'],
                'dibuat_oleh'         => $this->ambilIdPenggunaLogin(),
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

            return $redirect->with('success', 'Lowongan berhasil ditambahkan.');
        } catch (\Throwable $th) {
            if ($uploadedFlyer !== null) {
                $this->hapusFileLokal($uploadedFlyer);
            }

            return $redirect->withInput()->with('error', $th->getMessage());
        }
    }

    /*
    |-------------------------------------------------------------------
    | UPDATE LOWONGAN ADMIN DUDI
    |-------------------------------------------------------------------
    | Admin DUDI hanya boleh mengubah lowongan milik perusahaannya.
    | Pengecekan dilakukan dengan membandingkan id_perusahaan lowongan
    | dan id_perusahaan akun login.
    */
    public function update(int $idLowongan): RedirectResponse
    {
        $perusahaan = $this->validasiAksesPerusahaan();
        if ($perusahaan instanceof RedirectResponse) {
            return $perusahaan;
        }

        $redirect = redirect()->to(site_url('admin-dudi/lowongan'));
        $lowongan = $this->lowonganModel->ambilDetailById($idLowongan);

        if ($lowongan === null || (int) ($lowongan['id_perusahaan'] ?? 0) !== (int) $perusahaan['id_perusahaan']) {
            return $redirect->with('error', 'Data lowongan tidak ditemukan atau bukan milik perusahaan Anda.');
        }

        $payload = $this->ambilPayloadLowongan((int) $perusahaan['id_perusahaan']);
        $payload['slug_lowongan'] = $this->generateUniqueSlug($payload['judul_lowongan'], $payload['posisi'], $idLowongan);

        $validasi = $this->validasiLowongan($payload, (int) $perusahaan['id_perusahaan'], $idLowongan);
        if ($validasi !== null) {
            return $redirect->withInput()->with('error', $validasi);
        }

        $errorFlyer = $this->validateFlyerUpload();
        if ($errorFlyer !== null) {
            return $redirect->withInput()->with('error', $errorFlyer);
        }

        $uploadedFlyer = null;
        $flyerLama = $lowongan['flyer_lowongan'] ?? null;

        try {
            $uploadedFlyer = $this->simpanFlyer();
            $flyerFinal = $payload['flyer_remove'] ? null : $flyerLama;

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

            return $redirect->with('success', 'Lowongan berhasil diperbarui.');
        } catch (\Throwable $th) {
            if ($uploadedFlyer !== null) {
                $this->hapusFileLokal($uploadedFlyer);
            }

            return $redirect->withInput()->with('error', $th->getMessage());
        }
    }

    /*
    |-------------------------------------------------------------------
    | JUMLAH LAMARAN PER LOWONGAN
    |-------------------------------------------------------------------
    | Helper ini menambahkan konteks jumlah pelamar pada setiap lowongan
    | tanpa perlu membuat query berulang di view.
    */
    protected function hitungJumlahLamaranPerLowongan(array $idsLowongan): array
    {
        $idsLowongan = array_values(array_filter(array_map('intval', $idsLowongan), static fn (int $id): bool => $id > 0));

        if ($idsLowongan === [] || ! $this->db->tableExists('tb_lamaran')) {
            return [];
        }

        $rows = $this->db->table('tb_lamaran')
            ->select('id_lowongan, COUNT(*) AS total')
            ->whereIn('id_lowongan', $idsLowongan)
            ->groupBy('id_lowongan')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id_lowongan']] = (int) $row['total'];
        }

        return $map;
    }

    protected function ambilDaftarStatusLowongan(): array
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
    | NORMALISASI PAYLOAD LOWONGAN ADMIN DUDI
    |-------------------------------------------------------------------
    | Field dari form dirapikan sebelum divalidasi. id_perusahaan selalu
    | dipaksa dari perusahaan login, bukan dari input tersembunyi.
    */
    protected function ambilPayloadLowongan(int $idPerusahaan): array
    {
        $tayangHingga = trim((string) $this->request->getPost('tayang_hingga'));
        if ($tayangHingga !== '') {
            $tayangHingga = str_replace('T', ' ', $tayangHingga);

            if (strlen($tayangHingga) === 16) {
                $tayangHingga .= ':00';
            }
        }

        return [
            'id_perusahaan'       => $idPerusahaan,
            'judul_lowongan'      => trim((string) $this->request->getPost('judul_lowongan')),
            'posisi'              => trim((string) $this->request->getPost('posisi')),
            'deskripsi_pekerjaan' => trim((string) $this->request->getPost('deskripsi_pekerjaan')) ?: null,
            'kualifikasi'         => trim((string) $this->request->getPost('kualifikasi')) ?: null,
            'jumlah_kebutuhan'    => max(1, (int) ($this->request->getPost('jumlah_kebutuhan') ?? 1)),
            'jenis_pekerjaan'     => trim((string) $this->request->getPost('jenis_pekerjaan')) ?: 'fulltime',
            'sistem_kerja'        => trim((string) $this->request->getPost('sistem_kerja')) ?: 'onsite',
            'pendidikan_min'      => trim((string) $this->request->getPost('pendidikan_min')) ?: null,
            'pengalaman_min'      => trim((string) $this->request->getPost('pengalaman_min')) ?: null,
            'rentang_gaji'        => trim((string) $this->request->getPost('rentang_gaji')) ?: null,
            'lokasi_kerja'        => trim((string) $this->request->getPost('lokasi_kerja')) ?: null,
            'batas_lamaran'       => trim((string) $this->request->getPost('batas_lamaran')) ?: null,
            'tayang_hingga'       => $tayangHingga !== '' ? $tayangHingga : null,
            'status'              => trim((string) $this->request->getPost('status')) ?: 'draft',
            'flyer_remove'        => (string) $this->request->getPost('flyer_remove') === '1',
        ];
    }

    /*
    |-------------------------------------------------------------------
    | VALIDASI LOWONGAN ADMIN DUDI
    |-------------------------------------------------------------------
    | Validasi ini memastikan field inti benar, perusahaan punya hak
    | rekrutmen, dan slug lowongan tetap unik.
    */
    protected function validasiLowongan(array $payload, int $idPerusahaan, ?int $idLowongan = null): ?string
    {
        if (! $this->validateData($payload, [
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
            return 'Data lowongan belum valid. Pastikan judul, posisi, jenis pekerjaan, sistem kerja, dan status sudah benar.';
        }

        if (! $this->lowonganModel->perusahaanMemilikiKerjasamaRekrutmen($idPerusahaan)) {
            return 'Perusahaan Anda belum memiliki kerjasama rekrutmen aktif sehingga belum bisa membuat lowongan.';
        }

        if (
            $payload['batas_lamaran'] !== null
            && $payload['tayang_hingga'] !== null
            && strtotime($payload['tayang_hingga']) < strtotime($payload['batas_lamaran'])
        ) {
            return 'Tayang hingga tidak boleh lebih awal dari batas lamaran.';
        }

        if ($this->lowonganModel->slugDipakai($payload['slug_lowongan'], $idLowongan)) {
            return 'Slug lowongan sudah digunakan. Coba ubah judul atau posisi.';
        }

        return null;
    }

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
    | VALIDASI DAN PENYIMPANAN FLYER
    |-------------------------------------------------------------------
    | Helper ini menjaga agar flyer lowongan hanya menerima file gambar
    | yang aman dan ukurannya tidak terlalu besar.
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

    protected function ambilPerusahaanLogin(): ?array
    {
        $idPengguna = (int) session()->get('id_pengguna');
        $penggunaSession = session()->get('pengguna');

        if ($idPengguna <= 0 && is_array($penggunaSession)) {
            $idPengguna = (int) ($penggunaSession['id_pengguna'] ?? 0);
        }

        return $this->perusahaanModel->ambilByPengguna($idPengguna);
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
