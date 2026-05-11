<?php

namespace App\Controllers\Pelamar;

use App\Controllers\BaseController;
use App\Models\BerkasModel;
use App\Models\LamaranBerkasModel;
use App\Models\LamaranModel;
use App\Models\LamaranStatusModel;
use App\Models\LowonganModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;
use RuntimeException;

/*
|-------------------------------------------------------------------
| CONTROLLER LOWONGAN PELAMAR
|-------------------------------------------------------------------
| Controller ini menghubungkan pelamar dengan modul lamaran. Di sini
| pelamar dapat melihat lowongan aktif, membuka detail, lalu submit
| lamaran lengkap dengan dokumen khusus per perusahaan.
|
| Alur kerja:
| 1. Pelamar membuka daftar lowongan aktif.
| 2. Pelamar masuk ke halaman detail lowongan.
| 3. Sistem mengecek kelengkapan berkas profil umum.
| 4. Pelamar mengunggah CV, surat lamaran, dan portofolio opsional.
| 5. Sistem menyimpan transaksi ke tb_lamaran, snapshot dokumen ke
|    tb_lamaran_berkas, dan histori awal ke tb_lamaran_status.
|
| Tips Debugging:
| - Jika lowongan tidak tampil, cek status lowongan harus `aktif`.
| - Jika submit gagal karena profil belum lengkap, cek data wajib
|   ber-scope `profil` pada tabel tb_jenis_berkas.
*/
class LowonganController extends BaseController
{
    protected LowonganModel $lowonganModel;
    protected LamaranModel $lamaranModel;
    protected LamaranStatusModel $lamaranStatusModel;
    protected LamaranBerkasModel $lamaranBerkasModel;
    protected BerkasModel $berkasModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->lowonganModel      = new LowonganModel();
        $this->lamaranModel       = new LamaranModel();
        $this->lamaranStatusModel = new LamaranStatusModel();
        $this->lamaranBerkasModel = new LamaranBerkasModel();
        $this->berkasModel        = new BerkasModel();
        $this->db                 = Database::connect();
    }

    /*
    |-------------------------------------------------------------------
    | DAFTAR LOWONGAN AKTIF
    |-------------------------------------------------------------------
    | Halaman ini menjadi pintu masuk utama pelamar untuk melihat
    | lowongan yang masih aktif dan masih bisa dilamar.
    */
    public function index(): string|RedirectResponse
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        $keyword = trim((string) $this->request->getGet('q'));
        $lowongan = $this->lowonganModel->ambilDaftarAktifUntukPelamar($keyword);
        $lamaranMap = [];

        foreach ($this->lamaranModel->ambilByPelamar((int) $pelamar['id_pelamar']) as $item) {
            $lamaranMap[(int) ($item['id_lowongan'] ?? 0)] = $item;
        }

        return view('pelamar/lowongan/index', [
            'title'            => 'Lowongan Kerja - Sistem Tracer Study & BKK',
            'pelamar'          => $pelamar,
            'keyword'          => $keyword,
            'lowongan'         => $lowongan,
            'lamaranMap'       => $lamaranMap,
            'berkasProfilInfo' => $this->ambilStatusKelengkapanProfil((int) $pelamar['id_pelamar']),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | DETAIL LOWONGAN
    |-------------------------------------------------------------------
    | Menampilkan informasi lengkap lowongan sekaligus form lampiran
    | dokumen untuk submit lamaran.
    */
    public function detail(string $slugLowongan): string|RedirectResponse
    {
        $pelamar  = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        $lowongan = $this->lowonganModel->ambilDetailAktifBySlug($slugLowongan);

        if ($lowongan === null) {
            throw PageNotFoundException::forPageNotFound('Lowongan tidak ditemukan atau sudah tidak aktif.');
        }

        $berkasProfilInfo = $this->ambilStatusKelengkapanProfil((int) $pelamar['id_pelamar']);
        $lamaranSaya = $this->lamaranModel
            ->where('id_pelamar', (int) $pelamar['id_pelamar'])
            ->where('id_lowongan', (int) $lowongan['id_lowongan'])
            ->first();

        return view('pelamar/lowongan/detail', [
            'title'            => 'Detail Lowongan - Sistem Tracer Study & BKK',
            'pelamar'          => $pelamar,
            'lowongan'         => $lowongan,
            'lamaranSaya'      => $lamaranSaya,
            'berkasProfilInfo' => $berkasProfilInfo,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | SUBMIT LAMARAN
    |-------------------------------------------------------------------
    | Method ini menjadi inti proses bisnis modul lamaran. Validasi
    | kelengkapan profil dilakukan lebih dulu agar dokumen umum tidak
    | tercampur dengan dokumen spesifik perusahaan.
    |
    | Tips Debugging:
    | - Jika data masuk ke tb_lamaran tetapi snapshot kosong, cek error
    |   pada proses upload dan insert tb_lamaran_berkas.
    */
    public function lamar(int $idLowongan)
    {
        $pelamar  = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        $lowongan = $this->lowonganModel->ambilDetailAktifById($idLowongan);

        if ($lowongan === null) {
            return redirect()->to(site_url('pelamar/lowongan'))->with('error', 'Lowongan tidak ditemukan atau sudah tidak aktif.');
        }

        if ($this->lamaranModel->sudahPernahMelamar((int) $pelamar['id_pelamar'], $idLowongan)) {
            return redirect()->to(site_url('pelamar/lowongan/' . $lowongan['slug_lowongan']))->with('error', 'Kamu sudah pernah melamar pada lowongan ini.');
        }

        $berkasProfilInfo = $this->ambilStatusKelengkapanProfil((int) $pelamar['id_pelamar']);
        if ($berkasProfilInfo['belumLengkap'] !== []) {
            return redirect()->to(site_url('pelamar/lowongan/' . $lowongan['slug_lowongan']))
                ->with('error', 'Lengkapi dulu berkas profil wajib sebelum melamar.')
                ->with('belum_lengkap', implode(', ', array_map(static fn(array $item): string => (string) ($item['nama_berkas'] ?? 'Berkas'), $berkasProfilInfo['belumLengkap'])));
        }

        $dokumenLamaran = $this->ambilDokumenLamaranDariRequest();
        if ($dokumenLamaran['error'] !== null) {
            return redirect()->back()->withInput()->with('error', $dokumenLamaran['error']);
        }

        $idLamaran = 0;
        $pathsSnapshot = [];

        try {
            $this->db->transException(true)->transStart();

            $idLamaran = (int) $this->lamaranModel->insert([
                'id_pelamar'      => (int) $pelamar['id_pelamar'],
                'id_lowongan'     => $idLowongan,
                'dibuat_oleh'     => (int) $pelamar['id_pengguna'],
                'status'          => 'menunggu_verifikasi',
                'tanggal_melamar' => date('Y-m-d H:i:s'),
            ], true);

            if ($idLamaran <= 0) {
                throw new RuntimeException('Lamaran gagal dibuat.');
            }

            $this->lamaranStatusModel->insert([
                'id_lamaran'  => $idLamaran,
                'status_lama' => null,
                'status_baru' => 'menunggu_verifikasi',
                'catatan'     => 'Lamaran diajukan oleh pelamar.',
                'diubah_oleh' => (int) $pelamar['id_pengguna'],
                'dibuat_pada' => date('Y-m-d H:i:s'),
            ]);

            $snapshotDirectory = $this->siapkanDirektoriSnapshot($idLamaran);

            foreach ($dokumenLamaran['files'] as $dokumen) {
                $file = $dokumen['file'];
                $randomName = $file->getRandomName();
                $file->move($snapshotDirectory['absolute'], $randomName);
                $relativePath = $snapshotDirectory['relative'] . '/' . $randomName;
                $pathsSnapshot[] = $relativePath;

                $this->lamaranBerkasModel->insert([
                    'id_lamaran'            => $idLamaran,
                    'id_berkas'             => null,
                    'id_jenis_berkas'       => (int) $dokumen['jenis']['id_jenis_berkas'],
                    'nama_file_snapshot'    => $file->getClientName(),
                    'path_file_snapshot'    => $relativePath,
                    'ukuran_file_snapshot'  => $file->getSize(),
                    'tipe_mime_snapshot'    => method_exists($file, 'getClientMimeType') ? $file->getClientMimeType() : $file->getMimeType(),
                    'wajib_saat_submit'     => (int) $dokumen['wajib'],
                    'status_review'         => 'menunggu',
                    'catatan_review'        => null,
                    'ditinjau_oleh'         => null,
                    'ditinjau_pada'         => null,
                    'dibuat_pada'           => date('Y-m-d H:i:s'),
                ]);
            }

            $this->db->transComplete();
        } catch (\Throwable $e) {
            $this->db->transRollback();

            foreach ($pathsSnapshot as $relativePath) {
                $this->hapusFileSnapshot($relativePath);
            }

            if ($idLamaran > 0) {
                $this->hapusDirektoriSnapshotKosong($idLamaran);
            }

            return redirect()->back()->withInput()->with('error', 'Lamaran gagal dikirim. ' . $e->getMessage());
        }

        return redirect()->to(site_url('pelamar/lamaran/' . $idLamaran))
            ->with('success', 'Lamaran ke ' . (string) ($lowongan['nama_perusahaan'] ?? 'perusahaan') . ' berhasil dikirim.');
    }

    /*
    |-------------------------------------------------------------------
    | AMBIL PELAMAR LOGIN
    |-------------------------------------------------------------------
    | Helper ini memastikan modul hanya dipakai role pelamar dan akun
    | yang login benar-benar punya profil pelamar.
    */
    protected function ambilPelamarLogin(): array
    {
        if (! in_array((string) session()->get('slug_peran'), ['pelamar_umum', 'pelamar_alumni'], true)) {
            throw new RuntimeException('Akses modul lamaran ditolak.');
        }

        $idPengguna = (int) session()->get('id_pengguna');
        $pelamar = $this->db->table('tb_pelamar p')
            ->select('p.*, u.nama_lengkap, u.email, u.nomor_telepon, u.status_aktif, r.slug_peran')
            ->join('tb_pengguna u', 'u.id_pengguna = p.id_pengguna', 'inner')
            ->join('tb_peran r', 'r.id_peran = u.id_peran', 'inner')
            ->where('p.id_pengguna', $idPengguna)
            ->get()
            ->getRowArray();

        if ($pelamar === null) {
            throw new RuntimeException('Profil pelamar tidak ditemukan.');
        }

        session()->set('status_pendaftaran', $pelamar['status_pendaftaran'] ?? null);

        return $pelamar;
    }

    protected function pastikanPelamarSudahDisetujui(array $pelamar): ?RedirectResponse
    {
        if ((int) ($pelamar['status_aktif'] ?? 0) !== 1) {
            return redirect()->to(site_url('pelamar/dashboard'))
                ->with('error', 'Akun kamu sedang nonaktif. Kamu belum bisa membuka lowongan atau mengirim lamaran.');
        }

        if ((string) ($pelamar['status_pendaftaran'] ?? '') === 'aktif') {
            return null;
        }

        return redirect()->to(site_url('pelamar/dashboard'))
            ->with('error', 'Akun kamu masih menunggu persetujuan admin BKK. Lowongan baru bisa diakses setelah akun disetujui.');
    }

    /*
    |-------------------------------------------------------------------
    | STATUS KELENGKAPAN PROFIL
    |-------------------------------------------------------------------
    | Profil wajib dicek lebih dulu agar pelamar tidak bisa submit
    | lamaran ketika dokumen umum seperti KTP atau ijazah belum siap.
    */
    protected function ambilStatusKelengkapanProfil(int $idPelamar): array
    {
        $berkasProfil = $this->berkasModel->ambilByPelamar($idPelamar, 'profil');
        $belumLengkap = array_values(array_filter(
            $berkasProfil,
            static fn(array $item): bool => ! empty($item['wajib']) && (($item['status_unggah'] ?? '') !== 'sudah_diunggah')
        ));

        return [
            'semua'        => $berkasProfil,
            'belumLengkap' => $belumLengkap,
            'siapMelamar'  => $belumLengkap === [],
        ];
    }

    /*
    |-------------------------------------------------------------------
    | AMBIL DOKUMEN LAMARAN DARI REQUEST
    |-------------------------------------------------------------------
    | Method ini memusatkan validasi CV, surat lamaran, dan portofolio
    | agar aturan format dan ukuran file konsisten.
    */
    protected function ambilDokumenLamaranDariRequest(): array
    {
        $konfigurasi = [
            'cv' => [
                'field' => 'cv_file',
                'label' => 'CV',
                'wajib' => true,
            ],
            'surat_lamaran' => [
                'field' => 'surat_lamaran_file',
                'label' => 'Surat Lamaran',
                'wajib' => true,
            ],
            'portofolio' => [
                'field' => 'portofolio_file',
                'label' => 'Portofolio',
                'wajib' => false,
            ],
        ];

        $hasil = [];

        foreach ($konfigurasi as $slug => $meta) {
            $jenis = $this->berkasModel->cariJenisBerkasBySlug($slug, 'lamaran');
            if ($jenis === null) {
                return ['error' => 'Master jenis berkas `' . $meta['label'] . '` belum tersedia.', 'files' => []];
            }

            $file = $this->request->getFile($meta['field']);
            $tidakAdaFile = $file === null || $file->getError() === UPLOAD_ERR_NO_FILE;

            if ($meta['wajib'] && $tidakAdaFile) {
                return ['error' => $meta['label'] . ' wajib diunggah saat melamar.', 'files' => []];
            }

            if ($tidakAdaFile) {
                continue;
            }

            if (! $file->isValid() || $file->hasMoved()) {
                return ['error' => 'File ' . $meta['label'] . ' tidak valid.', 'files' => []];
            }

            if ($file->getSizeByUnit('kb') > 5120) {
                return ['error' => 'Ukuran file ' . $meta['label'] . ' maksimal 5 MB.', 'files' => []];
            }

            $extension = strtolower((string) $file->getExtension());
            if (! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                return ['error' => 'Format file ' . $meta['label'] . ' harus pdf, jpg, jpeg, atau png.', 'files' => []];
            }

            $hasil[] = [
                'slug'  => $slug,
                'jenis' => $jenis,
                'file'  => $file,
                'wajib' => $meta['wajib'],
            ];
        }

        return ['error' => null, 'files' => $hasil];
    }

    /*
    |-------------------------------------------------------------------
    | SIAPKAN DIREKTORI SNAPSHOT LAMARAN
    |-------------------------------------------------------------------
    | Direktori arsip dibuat setelah id_lamaran tersedia agar setiap
    | lamaran punya folder snapshot yang terpisah dan mudah dilacak.
    */
    protected function siapkanDirektoriSnapshot(int $idLamaran): array
    {
        $relativeDirectory = 'uploads/lamaran/' . $idLamaran;
        $absoluteDirectory = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

        if (! is_dir($absoluteDirectory) && ! mkdir($absoluteDirectory, 0775, true) && ! is_dir($absoluteDirectory)) {
            throw new RuntimeException('Direktori arsip lamaran tidak dapat dibuat.');
        }

        return [
            'relative' => $relativeDirectory,
            'absolute' => $absoluteDirectory,
        ];
    }

    protected function hapusFileSnapshot(string $relativePath): void
    {
        if (trim($relativePath) === '') {
            return;
        }

        $absolutePath = FCPATH . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    protected function hapusDirektoriSnapshotKosong(int $idLamaran): void
    {
        $absoluteDirectory = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'lamaran' . DIRECTORY_SEPARATOR . $idLamaran;

        if (! is_dir($absoluteDirectory)) {
            return;
        }

        $items = array_diff(scandir($absoluteDirectory) ?: [], ['.', '..']);
        if ($items === []) {
            @rmdir($absoluteDirectory);
        }
    }
}
