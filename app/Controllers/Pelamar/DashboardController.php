<?php

namespace App\Controllers\Pelamar;

use App\Controllers\BaseController;
use App\Models\AlumniModel;
use App\Models\BerkasModel;
use App\Models\LamaranModel;
use App\Models\PelamarModel;
use App\Models\PenggunaModel;
use App\Models\RiwayatKerjaModel;
use App\Models\TracerAlumniModel;
use Config\Database;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/*
|-------------------------------------------------------------------
| CONTROLLER MODUL PELAMAR
|-------------------------------------------------------------------
| Controller ini menjadi entry point dashboard dan profil mandiri
| untuk akun pelamar umum maupun alumni.
|
| Alur kerja:
| 1. Pelamar login lalu diarahkan ke dashboard.
| 2. Dashboard menampilkan ringkasan akun, berkas profil, dan lamaran.
| 3. Halaman profil dipakai untuk melengkapi berkas profil umum.
|
| Tips Debugging:
| - Jika pelamar diarahkan kembali ke login, cek slug_peran session.
| - Jika data profil kosong, pastikan akun pengguna sudah punya baris
|   tb_pelamar yang terhubung lewat id_pengguna.
*/
class DashboardController extends BaseController
{
    protected PelamarModel $pelamarModel;
    protected AlumniModel $alumniModel;
    protected BerkasModel $berkasModel;
    protected LamaranModel $lamaranModel;
    protected PenggunaModel $penggunaModel;
    protected RiwayatKerjaModel $riwayatKerjaModel;
    protected TracerAlumniModel $tracerAlumniModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->pelamarModel = new PelamarModel();
        $this->alumniModel  = new AlumniModel();
        $this->berkasModel  = new BerkasModel();
        $this->lamaranModel = new LamaranModel();
        $this->penggunaModel = new PenggunaModel();
        $this->riwayatKerjaModel = new RiwayatKerjaModel();
        $this->tracerAlumniModel = new TracerAlumniModel();
        $this->db           = Database::connect();
    }

    /*
    |-------------------------------------------------------------------
    | DASHBOARD PELAMAR
    |-------------------------------------------------------------------
    | Menampilkan ringkasan cepat agar pelamar tahu status akun,
    | kelengkapan berkas profil, dan jumlah lamaran yang sudah dibuat.
    */
    public function index(): string
    {
        $pelamar = $this->ambilPelamarLogin();
        $berkasProfil = $this->berkasModel->ambilByPelamar((int) $pelamar['id_pelamar'], 'profil');
        $lamaran = $this->lamaranModel->ambilByPelamar((int) $pelamar['id_pelamar']);
        $ringkasanBerkas = $this->hitungRingkasanBerkas($berkasProfil);

        return view('pelamar/dashboard', [
            'title'           => 'Dashboard Pelamar - Sistem Tracer Study & BKK',
            'pelamar'         => $pelamar,
            'berkas'          => $berkasProfil,
            'lamaran'         => $lamaran,
            'ringkasanBerkas' => $ringkasanBerkas,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | HALAMAN PROFIL PELAMAR
    |-------------------------------------------------------------------
    | Halaman ini menjadi pusat pengelolaan dokumen profil umum serta
    | tempat pelamar melihat riwayat lamaran yang pernah diajukan.
    */
    public function profil(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        $idPelamar = (int) $pelamar['id_pelamar'];
        $isAlumni = ($pelamar['slug_peran'] ?? '') === 'pelamar_alumni';
        $alumni = $isAlumni ? $this->alumniModel->ambilLengkapByPelamar($idPelamar) : null;
        $tracerTerakhir = null;

        if ($isAlumni && ! empty($alumni['id_alumni'])) {
            $tracerTerakhir = $this->tracerAlumniModel->ambilTerakhirByAlumni((int) $alumni['id_alumni']);
        }

        return view('superadmin/pelamar/detail', [
            'title'              => 'Profil Pelamar - Sistem Tracer Study & BKK',
            'detail_mode'        => 'pelamar',
            'pelamar'            => $pelamar,
            'isAlumni'           => $isAlumni,
            'alumni'             => $alumni,
            'tracer_terakhir'    => $tracerTerakhir,
            'tracer_fields'      => $this->bangunTracerFields($tracerTerakhir),
            'riwayat_kerja'      => $this->riwayatKerjaModel->ambilByPelamar($idPelamar),
            'berkas'             => $this->berkasModel->ambilByPelamar($idPelamar, 'profil'),
            'lamaran'            => $this->lamaranModel->ambilByPelamar($idPelamar),
            'jenis_berkas'       => $this->berkasModel->ambilJenisBerkasByScope('profil', $idPelamar),
            'id_pelamar'         => $idPelamar,
            'aktivitas'          => $this->ambilAktivitas(),
            'daftar_angkatan'    => $this->ambilAngkatan(),
            'daftar_kompetensi'  => $this->ambilKompetensi(),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | UPLOAD BERKAS PROFIL
    |-------------------------------------------------------------------
    | Pelamar hanya boleh mengunggah dokumen dengan scope profil dari
    | halaman ini agar CV/surat lamaran tidak tercampur dengan lamaran.
    |
    | Tips Debugging:
    | - Jika jenis berkas ditolak, cek master tb_jenis_berkas apakah
    |   scope_penggunaan benar-benar bernilai profil.
    */
    public function uploadBerkasProfil()
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        $idPelamar = (int) $pelamar['id_pelamar'];
        $idJenisBerkas = (int) ($this->request->getPost('id_jenis_berkas') ?? 0);

        if ($idJenisBerkas <= 0) {
            return $this->responseProfil('error', 'Jenis berkas profil wajib dipilih.', [], 422);
        }

        $jenisBerkas = $this->berkasModel->cariJenisBerkas($idJenisBerkas, 'profil');
        if ($jenisBerkas === null) {
            return $this->responseProfil('error', 'Jenis berkas profil tidak valid.', [], 422);
        }

        $file = $this->request->getFile('file_berkas');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $this->responseProfil('error', 'File berkas profil wajib dipilih.', [], 422);
        }

        if (! $file->isValid() || $file->hasMoved()) {
            return $this->responseProfil('error', 'File berkas profil tidak valid.', [], 422);
        }

        if ($file->getSizeByUnit('kb') > 5120) {
            return $this->responseProfil('error', 'Ukuran berkas maksimal 5 MB.', [], 422);
        }

        $extension = strtolower((string) $file->getExtension());
        if (! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            return $this->responseProfil('error', 'Format berkas harus pdf, jpg, jpeg, atau png.', [], 422);
        }

        $berkasLama = $this->berkasModel
            ->where('id_pelamar', $idPelamar)
            ->where('id_jenis_berkas', $idJenisBerkas)
            ->orderBy('id_berkas', 'DESC')
            ->first();

        $relativeDirectory = 'uploads/berkas/' . $idPelamar;
        $targetDirectory   = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            return $this->responseProfil('error', 'Direktori upload berkas tidak dapat dibuat.', [], 500);
        }

        $randomName = $file->getRandomName();
        $file->move($targetDirectory, $randomName);
        $relativePath = $relativeDirectory . '/' . $randomName;

        $dataBerkas = [
            'id_pelamar'      => $idPelamar,
            'id_jenis_berkas' => $idJenisBerkas,
            'nama_file'       => $file->getClientName(),
            'path_file'       => $relativePath,
            'status_unggah'   => 'sudah_diunggah',
            'catatan'         => null,
        ];

        if ($this->db->fieldExists('ukuran_file', 'tb_berkas')) {
            $dataBerkas['ukuran_file'] = $file->getSize();
        }

        if ($this->db->fieldExists('tipe_mime', 'tb_berkas')) {
            $dataBerkas['tipe_mime'] = method_exists($file, 'getClientMimeType')
                ? $file->getClientMimeType()
                : $file->getMimeType();
        }

        if ($berkasLama !== null) {
            $this->berkasModel->update((int) $berkasLama['id_berkas'], $dataBerkas);

            if (! empty($berkasLama['path_file']) && $berkasLama['path_file'] !== $relativePath) {
                $this->hapusFileLokal((string) $berkasLama['path_file']);
            }

            return $this->responseProfil('success', 'Berkas profil berhasil diperbarui.');
        }

        $this->berkasModel->insert($dataBerkas);

        return $this->responseProfil('success', 'Berkas profil berhasil diunggah.');
    }

    /*
    |-------------------------------------------------------------------
    | HAPUS BERKAS PROFIL
    |-------------------------------------------------------------------
    | Penghapusan hanya boleh dilakukan pada dokumen milik pelamar yang
    | sedang login agar tidak terjadi akses silang antar akun.
    */
    public function hapusBerkasProfil(int $idBerkas)
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        $berkas = $this->berkasModel->find($idBerkas);

        if ($berkas === null || (int) ($berkas['id_pelamar'] ?? 0) !== (int) $pelamar['id_pelamar']) {
            return $this->responseProfil('error', 'Berkas profil tidak ditemukan.', [], 404);
        }

        $this->berkasModel->delete($idBerkas);
        $this->hapusFileLokal((string) ($berkas['path_file'] ?? ''));

        return $this->responseProfil('success', 'Berkas profil berhasil dihapus.');
    }

    /*
    |-------------------------------------------------------------------
    | SIMPAN RIWAYAT KERJA PELAMAR
    |-------------------------------------------------------------------
    | Method ini memungkinkan pelamar menambahkan riwayat kerja milik
    | sendiri dari halaman profil dengan pola AJAX yang sama seperti
    | modul detail pelamar super admin.
    |
    | Tips Debugging:
    | - Jika modal submit tetapi tidak tersimpan, cek simpanRiwayatUrl.
    | - Jika validasi gagal, cek payload FormData pada tab Network.
    */
    public function simpanRiwayatKerja(): ResponseInterface
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        if (! $this->request->isAJAX()) {
            return $this->jsonResponse('error', 'Request tidak valid.', [], 400);
        }

        $payload = $this->ambilPayloadRiwayatKerja();
        $payload['id_pelamar'] = (int) $pelamar['id_pelamar'];

        return $this->simpanAtauPerbaruiRiwayatKerja($payload);
    }

    /*
    |-------------------------------------------------------------------
    | UPDATE RIWAYAT KERJA PELAMAR
    |-------------------------------------------------------------------
    | Method ini memastikan pelamar hanya dapat memperbarui riwayat
    | kerja yang memang dimiliki oleh akun yang sedang login.
    */
    public function updateRiwayatKerja(int $id): ResponseInterface
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        if (! $this->request->isAJAX()) {
            return $this->jsonResponse('error', 'Request tidak valid.', [], 400);
        }

        $riwayat = $this->riwayatKerjaModel->find($id);
        if ($riwayat === null || (int) ($riwayat['id_pelamar'] ?? 0) !== (int) $pelamar['id_pelamar']) {
            return $this->jsonResponse('error', 'Riwayat kerja tidak ditemukan.', [], 404);
        }

        $payload = $this->ambilPayloadRiwayatKerja();
        $payload['id_pelamar'] = (int) $pelamar['id_pelamar'];

        return $this->simpanAtauPerbaruiRiwayatKerja($payload, $id);
    }

    /*
    |-------------------------------------------------------------------
    | HAPUS RIWAYAT KERJA PELAMAR
    |-------------------------------------------------------------------
    | Method ini menghapus riwayat kerja milik pelamar sendiri agar
    | tidak terjadi akses silang antar akun.
    */
    public function hapusRiwayatKerja(int $id): ResponseInterface
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        if (! $this->request->isAJAX()) {
            return $this->jsonResponse('error', 'Request tidak valid.', [], 400);
        }

        $riwayat = $this->riwayatKerjaModel->find($id);
        if ($riwayat === null || (int) ($riwayat['id_pelamar'] ?? 0) !== (int) $pelamar['id_pelamar']) {
            return $this->jsonResponse('error', 'Riwayat kerja tidak ditemukan.', [], 404);
        }

        $this->riwayatKerjaModel->delete($id);

        return $this->jsonResponse('success', 'Riwayat kerja berhasil dihapus.', [
            'id_riwayat_kerja' => $id,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | UPDATE DETAIL PELAMAR
    |-------------------------------------------------------------------
    | Method ini memberi akses self-service untuk pelamar memperbarui
    | identitas dasarnya sendiri tanpa harus masuk dari sisi admin.
    | Data yang diperbolehkan hanya data profil akun sendiri.
    |
    | Tips Debugging:
    | - Jika upload foto gagal, cek ukuran dan format file.
    | - Jika respons 403 muncul, pastikan id_pelamar milik session aktif.
    */
    public function updateDetail(int $idPelamar): ResponseInterface
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        if ((int) $pelamar['id_pelamar'] !== $idPelamar || ! $this->request->isAJAX()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $payload = [
            'nama_lengkap'   => trim((string) $this->request->getPost('nama_lengkap')),
            'nomor_telepon'  => trim((string) $this->request->getPost('nomor_telepon')) ?: null,
            'jenis_kelamin'  => trim((string) $this->request->getPost('jenis_kelamin')) ?: null,
            'tempat_lahir'   => trim((string) $this->request->getPost('tempat_lahir')) ?: null,
            'tanggal_lahir'  => trim((string) $this->request->getPost('tanggal_lahir')) ?: null,
            'alamat'         => trim((string) $this->request->getPost('alamat')) ?: null,
            'nomer_nik'      => trim((string) $this->request->getPost('nomer_nik')) ?: null,
        ];

        if (! $this->validateData($payload, [
            'nama_lengkap'  => 'required',
            'jenis_kelamin' => 'permit_empty|in_list[L,P]',
            'tanggal_lahir' => 'permit_empty|valid_date[Y-m-d]',
        ])) {
            return $this->jsonResponse('error', 'Data profil belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $errorFoto = $this->validateFotoUpload();
        if ($errorFoto !== null) {
            return $this->jsonResponse('error', $errorFoto, [], 422);
        }

        $fotoBaru = null;

        try {
            $fotoBaru = $this->simpanFotoPelamar();

            $dataPengguna = [
                'nama_lengkap'  => $payload['nama_lengkap'],
                'nomor_telepon' => $payload['nomor_telepon'],
            ];
            $dataPelamar = [
                'jenis_kelamin' => $payload['jenis_kelamin'],
                'tempat_lahir'  => $payload['tempat_lahir'],
                'tanggal_lahir' => $payload['tanggal_lahir'],
                'alamat'        => $payload['alamat'],
                'nomer_nik'     => $payload['nomer_nik'],
            ];

            if ($fotoBaru !== null) {
                $dataPengguna['foto_profil'] = $fotoBaru;
                $dataPelamar['foto'] = $fotoBaru;
            }

            $this->penggunaModel->update((int) $pelamar['id_pengguna'], $dataPengguna);
            $this->pelamarModel->update($idPelamar, $dataPelamar);

            if ($fotoBaru !== null) {
                $this->hapusFileLokal((string) ($pelamar['foto'] ?? ''));
                $this->hapusFileLokal((string) ($pelamar['foto_profil'] ?? ''));
            }

            session()->set([
                'nama_lengkap' => $payload['nama_lengkap'],
            ]);

            return $this->jsonResponse('success', 'Detail profil berhasil diperbarui.');
        } catch (\Throwable $th) {
            if ($fotoBaru !== null) {
                $this->hapusFileLokal($fotoBaru);
            }

            return $this->jsonResponse('error', $th->getMessage(), [], 500);
        }
    }

    /*
    |-------------------------------------------------------------------
    | UPDATE EMAIL PELAMAR
    |-------------------------------------------------------------------
    | Pelamar dapat mengganti email akun sendiri dengan konfirmasi
    | password aktif agar perubahan sensitif tetap aman.
    */
    public function updateEmail(): ResponseInterface
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        if (! $this->request->isAJAX() || (int) ($this->request->getPost('id_pelamar') ?? 0) !== (int) $pelamar['id_pelamar']) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $emailBaru = trim((string) $this->request->getPost('email_baru'));
        $konfirmasiPassword = (string) $this->request->getPost('konfirmasi_password');

        if ($emailBaru === '') {
            return $this->jsonResponse('error', 'Email baru wajib diisi.', [], 422);
        }

        if (! filter_var($emailBaru, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse('error', 'Format email tidak valid.', [], 422);
        }

        if ($konfirmasiPassword === '') {
            return $this->jsonResponse('error', 'Konfirmasi password wajib diisi.', [], 422);
        }

        $pengguna = $this->penggunaModel->find((int) $pelamar['id_pengguna']);
        if ($pengguna === null) {
            return $this->jsonResponse('error', 'Data pengguna tidak ditemukan.', [], 404);
        }

        if (! password_verify($konfirmasiPassword, (string) $pengguna['kata_sandi'])) {
            return $this->jsonResponse('error', 'Password salah.', [], 422);
        }

        $cekEmail = $this->penggunaModel->where('email', $emailBaru)
            ->where('id_pengguna !=', (int) $pelamar['id_pengguna'])
            ->first();
        if ($cekEmail !== null) {
            return $this->jsonResponse('error', 'Email sudah digunakan.', [], 422);
        }

        $this->penggunaModel->update((int) $pelamar['id_pengguna'], [
            'email' => $emailBaru,
        ]);

        return $this->jsonResponse('success', 'Email berhasil diperbarui.', [
            'email_baru' => $emailBaru,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | UPDATE PASSWORD PELAMAR
    |-------------------------------------------------------------------
    | Method ini menangani perubahan password milik pelamar sendiri.
    | Validasi dilakukan pada password saat ini dan kecocokan konfirmasi.
    */
    public function updatePassword(): ResponseInterface
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        if (! $this->request->isAJAX() || (int) ($this->request->getPost('id_pelamar') ?? 0) !== (int) $pelamar['id_pelamar']) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        $passwordSaatIni = (string) $this->request->getPost('password_saat_ini');
        $passwordBaru = (string) $this->request->getPost('password_baru');
        $konfirmasiPasswordBaru = (string) $this->request->getPost('konfirmasi_password_baru');

        if ($passwordSaatIni === '') {
            return $this->jsonResponse('error', 'Password saat ini wajib diisi.', [], 422);
        }

        if ($passwordBaru === '') {
            return $this->jsonResponse('error', 'Password baru wajib diisi.', [], 422);
        }

        if (strlen($passwordBaru) < 8) {
            return $this->jsonResponse('error', 'Password minimal 8 karakter.', [], 422);
        }

        if ($passwordBaru !== $konfirmasiPasswordBaru) {
            return $this->jsonResponse('error', 'Password baru dan konfirmasi tidak cocok.', [], 422);
        }

        $pengguna = $this->penggunaModel->find((int) $pelamar['id_pengguna']);
        if ($pengguna === null) {
            return $this->jsonResponse('error', 'Data pengguna tidak ditemukan.', [], 404);
        }

        if (! password_verify($passwordSaatIni, (string) $pengguna['kata_sandi'])) {
            return $this->jsonResponse('error', 'Password saat ini salah.', [], 422);
        }

        $this->penggunaModel->update((int) $pelamar['id_pengguna'], [
            'kata_sandi' => password_hash($passwordBaru, PASSWORD_DEFAULT),
        ]);

        return $this->jsonResponse('success', 'Password berhasil diperbarui.');
    }

    /*
    |-------------------------------------------------------------------
    | SIMPAN TRACER PELAMAR
    |-------------------------------------------------------------------
    | Method ini memungkinkan pelamar alumni mengisi atau memperbarui
    | tracer study miliknya sendiri dari halaman profil.
    | Alur kerja: pelamar boleh menyimpan sebagai draft terlebih dahulu,
    | lalu mengirimnya ketika data sudah siap ditinjau admin sekolah.
    |
    | Tips Debugging:
    | - Jika alumni tidak bisa menyimpan tracer, cek role pelamar_alumni.
    | - Jika data tidak berubah, cek id_aktivitas terkirim dari form modal.
    */
    public function simpanTracer(): ResponseInterface
    {
        $pelamar = $this->ambilPelamarLogin();
        $aksesDitolak = $this->pastikanPelamarSudahDisetujui($pelamar);
        if ($aksesDitolak !== null) {
            return $aksesDitolak;
        }

        if (! $this->request->isAJAX() || (int) ($this->request->getPost('id_pelamar') ?? 0) !== (int) $pelamar['id_pelamar']) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        if (($pelamar['slug_peran'] ?? '') !== 'pelamar_alumni') {
            return $this->jsonResponse('error', 'Tracer study hanya untuk akun alumni.', [], 403);
        }

        if (! $this->db->tableExists('tb_tracer_alumni')) {
            return $this->jsonResponse('error', 'Tabel tracer alumni belum tersedia.', [], 500);
        }

        $idAktivitas = (int) ($this->request->getPost('id_aktivitas') ?? 0);
        if ($idAktivitas === 0) {
            return $this->jsonResponse('error', 'Aktivitas tracer wajib dipilih.', [], 422);
        }

        $alumni = $this->alumniModel->where('id_pelamar', (int) $pelamar['id_pelamar'])->first();
        if ($alumni === null) {
            return $this->jsonResponse('error', 'Data alumni tidak ditemukan.', [], 404);
        }

        $idAlumni = (int) $alumni['id_alumni'];
        $statusTracer = strtolower(trim((string) ($this->request->getPost('status_tracer') ?? 'draft')));
        $statusTracer = in_array($statusTracer, ['draft', 'terkirim'], true) ? $statusTracer : 'draft';
        $data = [
            'id_alumni'          => $idAlumni,
            'id_aktivitas'       => $idAktivitas,
            'status'             => $statusTracer,
            'diverifikasi_oleh'  => null,
            'diverifikasi_pada'  => null,
            'relevan_jurusan'    => $this->request->getPost('relevan_jurusan') !== null && $this->request->getPost('relevan_jurusan') !== ''
                ? (int) $this->request->getPost('relevan_jurusan')
                : null,
            'posisi_kerja'       => trim((string) $this->request->getPost('posisi_kerja')) ?: null,
            'nama_dudi'          => trim((string) $this->request->getPost('nama_dudi')) ?: null,
            'bidang_dudi'        => trim((string) $this->request->getPost('bidang_dudi')) ?: null,
            'alamat_dudi'        => trim((string) $this->request->getPost('alamat_dudi')) ?: null,
            'tahun_mulai_kerja'  => $this->request->getPost('tahun_mulai_kerja') ? (int) $this->request->getPost('tahun_mulai_kerja') : null,
            'penghasilan_range'  => trim((string) $this->request->getPost('penghasilan_range')) ?: null,
            'universitas'        => trim((string) $this->request->getPost('universitas')) ?: null,
            'program_studi'      => trim((string) $this->request->getPost('program_studi')) ?: null,
            'status_kuliah'      => trim((string) $this->request->getPost('status_kuliah')) ?: null,
            'nama_usaha'         => trim((string) $this->request->getPost('nama_usaha')) ?: null,
            'bidang_usaha'       => trim((string) $this->request->getPost('bidang_usaha')) ?: null,
            'modal_awal'         => $this->request->getPost('modal_awal') ? (float) $this->request->getPost('modal_awal') : null,
            'penghasilan_usaha'  => trim((string) $this->request->getPost('penghasilan_usaha')) ?: null,
            'rencana_kedepan'    => trim((string) $this->request->getPost('rencana_kedepan')) ?: null,
        ];

        $tracerLama = $this->tracerAlumniModel->where('id_alumni', $idAlumni)->first();
        $idAngkatan = (int) ($this->request->getPost('id_angkatan') ?? 0);
        $idKompetensi = (int) ($this->request->getPost('id_kompetensi') ?? 0);

        if ($idAngkatan > 0 || $idKompetensi > 0) {
            $updateAlumni = [];

            if ($idAngkatan > 0) {
                $updateAlumni['id_angkatan'] = $idAngkatan;
            }

            if ($idKompetensi > 0) {
                $updateAlumni['id_kompetensi'] = $idKompetensi;
            }

            $this->alumniModel->update($idAlumni, $updateAlumni);
        }

        if ($tracerLama !== null) {
            $this->tracerAlumniModel->update((int) $tracerLama['id_tracer'], $data);

            return $this->jsonResponse('success', $statusTracer === 'terkirim' ? 'Tracer study berhasil dikirim.' : 'Draft tracer study berhasil disimpan.', [
                'id_tracer' => $tracerLama['id_tracer'],
            ]);
        }

        $idTracer = $this->tracerAlumniModel->insert($data, true);

        return $this->jsonResponse('success', $statusTracer === 'terkirim' ? 'Tracer study berhasil dikirim.' : 'Draft tracer study berhasil disimpan.', [
            'id_tracer' => $idTracer,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | AMBIL PELAMAR LOGIN
    |-------------------------------------------------------------------
    | Helper ini memastikan akun yang masuk benar-benar role pelamar
    | dan sudah punya profil pelamar yang lengkap di database.
    */
    protected function ambilPelamarLogin(): array
    {
        if (! $this->isPelamar()) {
            throw new RuntimeException('Akses modul pelamar ditolak.');
        }

        $idPengguna = (int) session()->get('id_pengguna');
        $pelamar = $this->db->table('tb_pelamar p')
            ->select('p.*, u.nama_lengkap, u.email, u.nomor_telepon, u.foto_profil, u.status_aktif, u.terakhir_login, r.slug_peran, r.nama_peran')
            ->join('tb_pengguna u', 'u.id_pengguna = p.id_pengguna', 'inner')
            ->join('tb_peran r', 'r.id_peran = u.id_peran', 'inner')
            ->where('p.id_pengguna', $idPengguna)
            ->get()
            ->getRowArray();

        if ($pelamar === null) {
            throw new RuntimeException('Profil pelamar belum ditemukan.');
        }

        session()->set('status_pendaftaran', $pelamar['status_pendaftaran'] ?? null);

        return $pelamar;
    }

    protected function pastikanPelamarSudahDisetujui(array $pelamar)
    {
        if ((int) ($pelamar['status_aktif'] ?? 0) !== 1) {
            $message = 'Akun kamu sedang nonaktif. Silakan hubungi admin BKK untuk membuka akses kembali.';

            if ($this->request->isAJAX()) {
                return $this->jsonResponse('error', $message, [], 403);
            }

            return redirect()->to(site_url('pelamar/dashboard'))->with('error', $message);
        }

        if ((string) ($pelamar['status_pendaftaran'] ?? '') === 'aktif') {
            return null;
        }

        $message = 'Akun kamu masih menunggu persetujuan admin BKK. Saat ini hanya dashboard yang dapat diakses.';

        if ($this->request->isAJAX()) {
            return $this->jsonResponse('error', $message, [], 403);
        }

        return redirect()->to(site_url('pelamar/dashboard'))->with('error', $message);
    }

    /*
    |-------------------------------------------------------------------
    | HITUNG RINGKASAN BERKAS
    |-------------------------------------------------------------------
    | Helper ini dipakai dashboard pelamar untuk menghitung progres
    | kelengkapan dokumen profil tanpa perlu menghitung ulang di view.
    |
    | Tips Debugging:
    | - Jika angka dashboard terasa salah, cek struktur array berkas
    |   yang dikembalikan oleh BerkasModel::ambilByPelamar().
    */
    protected function hitungRingkasanBerkas(array $berkas): array
    {
        $total = count($berkas);
        $uploaded = count(array_filter($berkas, static fn(array $item): bool => ($item['status_unggah'] ?? '') === 'sudah_diunggah'));
        $wajib = count(array_filter($berkas, static fn(array $item): bool => ! empty($item['wajib'])));
        $wajibUploaded = count(array_filter($berkas, static fn(array $item): bool => ! empty($item['wajib']) && ($item['status_unggah'] ?? '') === 'sudah_diunggah'));

        return [
            'total'          => $total,
            'uploaded'       => $uploaded,
            'wajib'          => $wajib,
            'wajib_uploaded' => $wajibUploaded,
        ];
    }

    /*
    |-------------------------------------------------------------------
    | AMBIL PAYLOAD RIWAYAT KERJA
    |-------------------------------------------------------------------
    | Helper ini menyusun payload riwayat kerja dari form modal agar
    | proses tambah dan edit memakai format data yang sama.
    */
    protected function ambilPayloadRiwayatKerja(): array
    {
        $tanggalSelesai = trim((string) $this->request->getPost('tanggal_selesai'));

        return [
            'id_pelamar'      => (int) ($this->request->getPost('id_pelamar') ?? 0),
            'nama_perusahaan' => trim((string) $this->request->getPost('nama_perusahaan')),
            'bidang_usaha'    => trim((string) $this->request->getPost('bidang_usaha')) ?: null,
            'lokasi'          => trim((string) $this->request->getPost('lokasi')) ?: null,
            'posisi_jabatan'  => trim((string) $this->request->getPost('posisi_jabatan')),
            'tanggal_mulai'   => trim((string) $this->request->getPost('tanggal_mulai')),
            'tanggal_selesai' => $tanggalSelesai !== '' ? $tanggalSelesai : null,
            'masih_bekerja'   => (int) ($this->request->getPost('masih_bekerja') ? 1 : 0),
            'keterangan'      => trim((string) $this->request->getPost('keterangan')) ?: null,
        ];
    }

    /*
    |-------------------------------------------------------------------
    | SIMPAN ATAU PERBARUI RIWAYAT KERJA
    |-------------------------------------------------------------------
    | Helper ini dipakai method simpan dan update agar validasi bisnis
    | riwayat kerja tetap satu pintu dan mudah dirawat.
    |
    | Tips Debugging:
    | - Jika tanggal selesai selalu kosong, cek checkbox masih_bekerja.
    | - Jika data tidak lolos validasi, cek rule posisi_jabatan dan nama_perusahaan.
    */
    protected function simpanAtauPerbaruiRiwayatKerja(array $payload, ?int $idRiwayat = null): ResponseInterface
    {
        if ($payload['masih_bekerja'] === 1) {
            $payload['tanggal_selesai'] = null;
        }

        if (! $this->validateData($payload, [
            'id_pelamar'      => 'required|integer',
            'nama_perusahaan' => 'required|max_length[150]',
            'posisi_jabatan'  => 'required|max_length[150]',
            'tanggal_mulai'   => 'required|valid_date[Y-m-d]',
            'tanggal_selesai' => 'permit_empty|valid_date[Y-m-d]',
            'keterangan'      => 'permit_empty|max_length[500]',
        ])) {
            return $this->jsonResponse('error', 'Data riwayat kerja belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        if ($payload['tanggal_selesai'] !== null && $payload['tanggal_selesai'] < $payload['tanggal_mulai']) {
            return $this->jsonResponse('error', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.', [], 422);
        }

        if ($idRiwayat === null) {
            $idRiwayat = (int) $this->riwayatKerjaModel->insert($payload, true);

            return $this->jsonResponse('success', 'Riwayat kerja berhasil ditambahkan.', [
                'id_riwayat_kerja' => $idRiwayat,
            ]);
        }

        $this->riwayatKerjaModel->update($idRiwayat, $payload);

        return $this->jsonResponse('success', 'Riwayat kerja berhasil diperbarui.', [
            'id_riwayat_kerja' => $idRiwayat,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | BANGUN FIELD TRACER
    |-------------------------------------------------------------------
    | Helper ini menyusun field tracer study menjadi pasangan label dan
    | nilai agar tab tracer pada halaman detail bisa dirender dengan
    | format yang seragam, baik untuk super admin maupun pelamar.
    |
    | Tips Debugging:
    | - Jika data tracer ada tetapi detail tidak tampil, cek nama field
    |   yang dipetakan di method ini dengan kolom tabel tracer aktual.
    */
    protected function bangunTracerFields(?array $tracer): array
    {
        if ($tracer === null) {
            return [];
        }

        $aktivitas = strtolower((string) ($tracer['nama_aktivitas'] ?? ''));
        $mapFields = [];

        if (str_contains($aktivitas, 'kerja')) {
            $mapFields = [
                'posisi_kerja'      => 'Posisi / Jabatan',
                'nama_dudi'         => 'Nama DUDI',
                'bidang_dudi'       => 'Bidang DUDI',
                'alamat_dudi'       => 'Alamat DUDI',
                'tahun_mulai_kerja' => 'Tahun Mulai Kerja',
                'penghasilan_range' => 'Penghasilan',
            ];
        } elseif (str_contains($aktivitas, 'kuliah')) {
            $mapFields = [
                'universitas'   => 'Universitas',
                'program_studi' => 'Program Studi',
                'status_kuliah' => 'Status Kuliah',
            ];
        } elseif (str_contains($aktivitas, 'wirausaha')) {
            $mapFields = [
                'nama_usaha'        => 'Nama Usaha',
                'bidang_usaha'      => 'Bidang Usaha',
                'modal_awal'        => 'Modal Awal',
                'penghasilan_usaha' => 'Penghasilan Usaha',
            ];
        } elseif (str_contains($aktivitas, 'belum')) {
            $mapFields = [
                'rencana_kedepan' => 'Rencana Kedepan',
            ];
        }

        $fields = [];

        foreach ($mapFields as $key => $label) {
            $value = $tracer[$key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $fields[] = [
                'label' => $label,
                'value' => (string) $value,
            ];
        }

        return $fields;
    }

    /*
    |-------------------------------------------------------------------
    | AMBIL MASTER AKTIVITAS
    |-------------------------------------------------------------------
    | Helper ini menyiapkan daftar aktivitas tracer study agar modal
    | edit tracer milik pelamar memiliki pilihan kegiatan yang lengkap.
    |
    | Tips Debugging:
    | - Jika radio kegiatan kosong, cek isi tabel tb_aktivitas.
    */
    protected function ambilAktivitas(): array
    {
        if (! $this->db->tableExists('tb_aktivitas')) {
            return [];
        }

        return $this->db->table('tb_aktivitas')
            ->select('id_aktivitas, nama_aktivitas')
            ->where('status_aktif', 1)
            ->orderBy('id_aktivitas', 'ASC')
            ->get()
            ->getResultArray();
    }

    /*
    |-------------------------------------------------------------------
    | AMBIL MASTER ANGKATAN
    |-------------------------------------------------------------------
    | Helper ini mengisi pilihan tahun lulus pada modal tracer study
    | agar alumni dapat melengkapi profil akademiknya sendiri.
    |
    | Tips Debugging:
    | - Jika pilihan tahun kosong, periksa isi tb_angkatan.
    */
    protected function ambilAngkatan(): array
    {
        if (! $this->db->tableExists('tb_angkatan')) {
            return [];
        }

        return $this->db->table('tb_angkatan')
            ->select('id_angkatan, tahun_lulus')
            ->orderBy('tahun_lulus', 'DESC')
            ->get()
            ->getResultArray();
    }

    /*
    |-------------------------------------------------------------------
    | AMBIL MASTER KOMPETENSI
    |-------------------------------------------------------------------
    | Helper ini menyiapkan daftar jurusan atau kompetensi keahlian
    | untuk modal tracer study pada akun pelamar alumni.
    |
    | Tips Debugging:
    | - Jika jurusan tidak muncul, cek data aktif di tb_kompetensi.
    */
    protected function ambilKompetensi(): array
    {
        if (! $this->db->tableExists('tb_kompetensi')) {
            return [];
        }

        return $this->db->table('tb_kompetensi')
            ->select('id_kompetensi, nama_kompetensi, akronim')
            ->orderBy('nama_kompetensi', 'ASC')
            ->get()
            ->getResultArray();
    }

    /*
    |-------------------------------------------------------------------
    | RESPONSE PROFIL PELAMAR
    |-------------------------------------------------------------------
    | Helper ini menyatukan pola respons untuk halaman profil pelamar.
    | Jika request datang dari fetch AJAX, controller mengembalikan JSON.
    | Jika request datang dari submit biasa, controller mengembalikan
    | redirect lengkap dengan flash message seperti sebelumnya.
    |
    | Tips Debugging:
    | - Jika fetch menerima HTML, cek header X-Requested-With pada request.
    | - Jika redirect tidak membawa pesan, cek session flashdata aktif.
    */
    protected function responseProfil(string $status, string $message, array $data = [], int $httpCode = 200)
    {
        if ($this->request->isAJAX()) {
            return $this->jsonResponse($status, $message, $data, $httpCode);
        }

        if ($status === 'success') {
            return redirect()->to(site_url('pelamar/profil'))->with('success', $message);
        }

        return redirect()->back()->with('error', $message);
    }

    protected function hapusFileLokal(string $relativePath): void
    {
        if (trim($relativePath) === '') {
            return;
        }

        $fullPath = FCPATH . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    protected function isPelamar(): bool
    {
        return in_array((string) session()->get('slug_peran'), ['pelamar_umum', 'pelamar_alumni'], true);
    }

    /*
    |-------------------------------------------------------------------
    | VALIDASI FOTO PELAMAR
    |-------------------------------------------------------------------
    | Helper ini memeriksa file foto sebelum disimpan agar ukuran dan
    | formatnya konsisten dengan kebutuhan profil pelamar.
    */
    protected function validateFotoUpload(): ?string
    {
        $file = $this->request->getFile('foto');

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (! $file->isValid()) {
            return 'File foto tidak valid.';
        }

        if ($file->getSizeByUnit('kb') > 2048) {
            return 'Ukuran foto maksimal 2 MB.';
        }

        $ext = strtolower((string) $file->getExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return 'Format foto harus jpg, jpeg, atau png.';
        }

        return null;
    }

    /*
    |-------------------------------------------------------------------
    | SIMPAN FOTO PELAMAR
    |-------------------------------------------------------------------
    | Helper ini memindahkan file foto baru ke folder uploads/pelamar
    | dan mengembalikan relative path yang disimpan di database.
    */
    protected function simpanFotoPelamar(): ?string
    {
        $file = $this->request->getFile('foto');

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (! $file->isValid() || $file->hasMoved()) {
            throw new RuntimeException('Upload foto gagal diproses.');
        }

        $targetDirectory = FCPATH . 'uploads/pelamar';
        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            throw new RuntimeException('Direktori upload foto tidak dapat dibuat.');
        }

        $randomName = $file->getRandomName();
        $file->move($targetDirectory, $randomName);

        return 'uploads/pelamar/' . $randomName;
    }

    /*
    |-------------------------------------------------------------------
    | JSON RESPONSE
    |-------------------------------------------------------------------
    | Helper ini menyamakan format respons AJAX pelamar dengan modul
    | lain, termasuk pembaruan CSRF hash agar fetch berikutnya tetap aman.
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
