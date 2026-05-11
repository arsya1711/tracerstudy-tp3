<?php

namespace App\Controllers\Pelamar;

use App\Controllers\BaseController;
use App\Models\LamaranBerkasModel;
use App\Models\LamaranModel;
use App\Models\LamaranStatusModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;
use RuntimeException;

/*
|-------------------------------------------------------------------
| CONTROLLER RIWAYAT LAMARAN PELAMAR
|-------------------------------------------------------------------
| Controller ini menangani halaman riwayat dan detail lamaran dari
| sudut pandang pelamar. Pelamar dapat melihat status proses, catatan
| admin/HRD, dokumen snapshot, dan mengunggah ulang dokumen yang
| diminta perbaikan.
|
| Alur kerja:
| 1. Pelamar membuka menu Riwayat Lamaran.
| 2. Pelamar memilih salah satu lamaran untuk melihat detail.
| 3. Jika status perlu perbaikan, pelamar dapat upload ulang dokumen.
| 4. Ketika semua dokumen bermasalah sudah diganti, status kembali
|    ke menunggu_verifikasi agar bisa ditinjau ulang.
|
| Tips Debugging:
| - Jika detail 404, pastikan id_lamaran milik pelamar yang login.
| - Jika upload ulang ditolak, cek status lamaran dan status review
|   dokumen pada tabel tb_lamaran_berkas.
*/
class LamaranController extends BaseController
{
    protected LamaranModel $lamaranModel;
    protected LamaranBerkasModel $lamaranBerkasModel;
    protected LamaranStatusModel $lamaranStatusModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->lamaranModel       = new LamaranModel();
        $this->lamaranBerkasModel = new LamaranBerkasModel();
        $this->lamaranStatusModel = new LamaranStatusModel();
        $this->db                 = Database::connect();
    }

    /*
    |-------------------------------------------------------------------
    | DAFTAR RIWAYAT LAMARAN
    |-------------------------------------------------------------------
    | Halaman ini menampilkan semua lamaran milik pelamar yang sedang
    | login dengan filter status dan pencarian sederhana.
    */
    public function index(): string|RedirectResponse
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        $keyword = strtolower(trim((string) $this->request->getGet('q')));
        $status  = trim((string) $this->request->getGet('status'));
        $lamaran = $this->lamaranModel->ambilByPelamar((int) $pelamar['id_pelamar']);

        if ($status !== '') {
            $lamaran = array_values(array_filter(
                $lamaran,
                static fn(array $item): bool => (string) ($item['status'] ?? '') === $status
            ));
        }

        if ($keyword !== '') {
            $lamaran = array_values(array_filter($lamaran, static function (array $item) use ($keyword): bool {
                $haystack = strtolower(implode(' ', [
                    (string) ($item['judul_lowongan'] ?? ''),
                    (string) ($item['posisi'] ?? ''),
                    (string) ($item['nama_perusahaan'] ?? ''),
                ]));

                return str_contains($haystack, $keyword);
            }));
        }

        return view('pelamar/lamaran/index', [
            'title'        => 'Riwayat Lamaran - Sistem Tracer Study & BKK',
            'pelamar'      => $pelamar,
            'lamaran'      => $lamaran,
            'keyword'      => (string) $this->request->getGet('q'),
            'statusFilter' => $status,
            'daftarStatus' => $this->ambilDaftarStatus(),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | DETAIL LAMARAN PELAMAR
    |-------------------------------------------------------------------
    | Menampilkan status utama, histori status, dokumen snapshot, dan
    | catatan review dokumen khusus satu lamaran milik pelamar login.
    */
    public function detail(int $idLamaran): string|RedirectResponse
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        $lamaran = $this->lamaranModel->ambilDetailMilikPelamar($idLamaran, (int) $pelamar['id_pelamar']);

        if ($lamaran === null) {
            throw PageNotFoundException::forPageNotFound('Lamaran tidak ditemukan.');
        }

        return view('pelamar/lamaran/detail', [
            'title'         => 'Detail Lamaran - Sistem Tracer Study & BKK',
            'pelamar'       => $pelamar,
            'lamaran'       => $lamaran,
            'dokumen'       => $this->lamaranBerkasModel->ambilByLamaran($idLamaran),
            'riwayatStatus' => $this->lamaranStatusModel->ambilByLamaran($idLamaran),
            'daftarStatus'  => $this->ambilDaftarStatus(),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | UPLOAD ULANG DOKUMEN LAMARAN
    |-------------------------------------------------------------------
    | Method ini mengganti snapshot dokumen tertentu ketika reviewer
    | memberi status perlu_perbaikan atau ditolak pada file tersebut.
    |
    | Tips Debugging:
    | - Jika status tidak berubah ke menunggu_verifikasi, cek apakah
    |   masih ada dokumen lain berstatus perlu_perbaikan/ditolak.
    */
    public function uploadUlangDokumen(int $idLamaranBerkas): RedirectResponse
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        $dokumen = $this->ambilDokumenMilikPelamar($idLamaranBerkas, (int) $pelamar['id_pelamar']);

        if ($dokumen === null) {
            return redirect()->to(site_url('pelamar/lamaran'))->with('error', 'Dokumen lamaran tidak ditemukan.');
        }

        $idLamaran = (int) $dokumen['id_lamaran'];

        if ((string) ($dokumen['status_lamaran'] ?? '') !== 'perlu_perbaikan_berkas') {
            return redirect()->to(site_url('pelamar/lamaran/' . $idLamaran))
                ->with('error', 'Upload ulang hanya tersedia saat status lamaran meminta perbaikan berkas.');
        }

        if (! in_array((string) ($dokumen['status_review'] ?? ''), ['perlu_perbaikan', 'ditolak'], true)) {
            return redirect()->to(site_url('pelamar/lamaran/' . $idLamaran))
                ->with('error', 'Dokumen ini belum ditandai perlu diperbaiki.');
        }

        $file = $this->request->getFile('file_dokumen');
        $error = $this->validasiFileDokumen($file, (string) ($dokumen['nama_berkas'] ?? 'Dokumen'));

        if ($error !== null) {
            return redirect()->to(site_url('pelamar/lamaran/' . $idLamaran))->with('error', $error);
        }

        $pathsBaru = [];

        try {
            $direktori = $this->siapkanDirektoriSnapshot($idLamaran);
            $randomName = $file->getRandomName();
            $file->move($direktori['absolute'], $randomName);
            $relativePath = $direktori['relative'] . '/' . $randomName;
            $pathsBaru[] = $relativePath;

            $this->db->transException(true)->transStart();

            $this->lamaranBerkasModel->update($idLamaranBerkas, [
                'nama_file_snapshot'   => $file->getClientName(),
                'path_file_snapshot'   => $relativePath,
                'ukuran_file_snapshot' => $file->getSize(),
                'tipe_mime_snapshot'   => method_exists($file, 'getClientMimeType') ? $file->getClientMimeType() : $file->getMimeType(),
                'status_review'        => 'menunggu',
                'catatan_review'       => null,
                'ditinjau_oleh'        => null,
                'ditinjau_pada'        => null,
            ]);

            if (! $this->masihAdaDokumenBermasalah($idLamaran)) {
                $this->lamaranModel->update($idLamaran, [
                    'status' => 'menunggu_verifikasi',
                ]);

                $this->lamaranStatusModel->insert([
                    'id_lamaran'  => $idLamaran,
                    'status_lama' => 'perlu_perbaikan_berkas',
                    'status_baru' => 'menunggu_verifikasi',
                    'catatan'     => 'Pelamar telah mengunggah ulang dokumen yang diminta perbaikan.',
                    'diubah_oleh' => (int) $pelamar['id_pengguna'],
                    'dibuat_pada' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->db->transComplete();
        } catch (\Throwable $e) {
            $this->db->transRollback();

            foreach ($pathsBaru as $pathBaru) {
                $this->hapusFileSnapshot($pathBaru);
            }

            return redirect()->to(site_url('pelamar/lamaran/' . $idLamaran))
                ->with('error', 'Dokumen gagal diunggah ulang. ' . $e->getMessage());
        }

        $this->hapusFileSnapshot((string) ($dokumen['path_file_snapshot'] ?? ''));

        return redirect()->to(site_url('pelamar/lamaran/' . $idLamaran))
            ->with('success', 'Dokumen lamaran berhasil diunggah ulang.');
    }

    protected function ambilPelamarLogin(): array
    {
        if (! in_array((string) session()->get('slug_peran'), ['pelamar_umum', 'pelamar_alumni'], true)) {
            throw new RuntimeException('Akses riwayat lamaran ditolak.');
        }

        $pelamar = $this->db->table('tb_pelamar p')
            ->select('p.*, u.id_pengguna, u.nama_lengkap, u.email, u.nomor_telepon, u.status_aktif, r.slug_peran')
            ->join('tb_pengguna u', 'u.id_pengguna = p.id_pengguna', 'inner')
            ->join('tb_peran r', 'r.id_peran = u.id_peran', 'inner')
            ->where('p.id_pengguna', (int) session()->get('id_pengguna'))
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
                ->with('error', 'Akun kamu sedang nonaktif. Riwayat lamaran belum bisa diakses.');
        }

        if ((string) ($pelamar['status_pendaftaran'] ?? '') === 'aktif') {
            return null;
        }

        return redirect()->to(site_url('pelamar/dashboard'))
            ->with('error', 'Akun kamu masih menunggu persetujuan admin BKK. Riwayat lamaran baru bisa diakses setelah akun disetujui.');
    }

    protected function ambilDokumenMilikPelamar(int $idLamaranBerkas, int $idPelamar): ?array
    {
        return $this->db->table('tb_lamaran_berkas lb')
            ->select([
                'lb.*',
                'l.id_pelamar',
                'l.status AS status_lamaran',
                'jb.nama_berkas',
                'jb.slug_berkas',
            ])
            ->join('tb_lamaran l', 'l.id_lamaran = lb.id_lamaran', 'inner')
            ->join('tb_jenis_berkas jb', 'jb.id_jenis_berkas = lb.id_jenis_berkas', 'left')
            ->where('lb.id_lamaran_berkas', $idLamaranBerkas)
            ->where('l.id_pelamar', $idPelamar)
            ->get()
            ->getRowArray();
    }

    protected function validasiFileDokumen($file, string $namaDokumen): ?string
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $namaDokumen . ' wajib dipilih.';
        }

        if (! $file->isValid() || $file->hasMoved()) {
            return 'File ' . $namaDokumen . ' tidak valid.';
        }

        if ($file->getSizeByUnit('kb') > 5120) {
            return 'Ukuran file ' . $namaDokumen . ' maksimal 5 MB.';
        }

        $extension = strtolower((string) $file->getExtension());
        if (! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            return 'Format file ' . $namaDokumen . ' harus pdf, jpg, jpeg, atau png.';
        }

        return null;
    }

    protected function masihAdaDokumenBermasalah(int $idLamaran): bool
    {
        return $this->db->table('tb_lamaran_berkas')
            ->where('id_lamaran', $idLamaran)
            ->whereIn('status_review', ['perlu_perbaikan', 'ditolak'])
            ->countAllResults() > 0;
    }

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

    protected function ambilDaftarStatus(): array
    {
        return [
            'menunggu_verifikasi'    => 'Menunggu Verifikasi',
            'perlu_perbaikan_berkas' => 'Perlu Perbaikan Berkas',
            'diproses'               => 'Diproses',
            'wawancara'              => 'Wawancara',
            'diterima'               => 'Diterima',
            'ditolak'                => 'Ditolak',
            'mengundurkan_diri'      => 'Mengundurkan Diri',
        ];
    }
}
