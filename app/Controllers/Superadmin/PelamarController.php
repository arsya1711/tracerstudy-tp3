<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use App\Models\AlumniModel;
use App\Models\BerkasModel;
use App\Models\LamaranModel;
use App\Models\PelamarModel;
use App\Models\RiwayatKerjaModel;
use App\Models\PenggunaModel;
use App\Models\PeranModel;
use App\Models\TracerAlumniModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use RuntimeException;

/*
|-------------------------------------------------------------------
| CONTROLLER PELAMAR
|-------------------------------------------------------------------
| Controller ini menangani halaman tabel pelamar Super Admin dengan
| DataTables server-side, tambah, edit, hapus lunak, hapus massal,
| dan aktivasi akun pelamar.
| Alur kerja: request non-AJAX me-render view list Metronic, sedangkan
| request AJAX diproses sebagai endpoint JSON untuk DataTables maupun
| aksi modal dan tombol pada tabel.
|
| Tips Debugging:
| - Jika akses ditolak, cek session slug_peran harus superadmin.
| - Jika simpan alumni gagal, cek tabel tb_alumni tersedia dan menerima insert id_pelamar.
*/
class PelamarController extends BaseController
{
    protected PelamarModel $pelamarModel;
    protected PenggunaModel $penggunaModel;
    protected PeranModel $peranModel;
    protected AlumniModel $alumniModel;
    protected RiwayatKerjaModel $riwayatKerjaModel;
    protected BerkasModel $berkasModel;
    protected LamaranModel $lamaranModel;
    protected TracerAlumniModel $tracerAlumniModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->pelamarModel       = new PelamarModel();
        $this->penggunaModel      = new PenggunaModel();
        $this->peranModel         = new PeranModel();
        $this->alumniModel        = new AlumniModel();
        $this->riwayatKerjaModel  = new RiwayatKerjaModel();
        $this->berkasModel        = new BerkasModel();
        $this->lamaranModel       = new LamaranModel();
        $this->tracerAlumniModel  = new TracerAlumniModel();
        $this->db                 = Database::connect();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INDEX
    |-------------------------------------------------------------------
    | Menampilkan halaman daftar pelamar atau response JSON DataTables
    | bila dipanggil dari AJAX.
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
            $result = $this->pelamarModel->getDataTables($this->request);

            $result['data'] = array_map(fn (array $row): array => $this->formatPelamarRow($row), $result['data']);
            $result['csrfHash'] = csrf_hash();

            return $this->response->setJSON($result);
        }

        $areaPrefix = $this->ambilAreaPrefix();

        return view('superadmin/pelamar/index', [
            'title'             => 'Data Pelamar - Sistem Tracer Study & BKK',
            'areaPrefix'        => $areaPrefix,
            'dashboardUrl'      => $this->ambilDashboardUrl(),
            'pageHeading'       => $areaPrefix === 'admin-sekolah' ? 'Data Pelamar' : 'Daftar Pelamar',
            'breadcrumbParent'  => 'Manajemen Pengguna',
            'breadcrumbCurrent' => 'Data Pelamar',
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD SIMPAN
    |-------------------------------------------------------------------
    | Menyimpan akun pelamar baru beserta akun pengguna dan baris alumni
    | kosong bila jenis pelamar adalah alumni.
    */
    public function simpan(): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        if (! $this->request->isAJAX()) {
            return $this->jsonResponse('error', 'Request tidak valid.', [], 400);
        }

        $payload = $this->ambilPayloadPelamar();

        if (! $this->validateData($payload, [
            'nama_lengkap'   => 'required',
            'email'          => 'required|valid_email|is_unique[tb_pengguna.email]',
            'kata_sandi'     => 'required|min_length[8]',
            'jenis_pelamar'  => 'required|in_list[alumni,umum]',
            'status_pendaftaran' => 'permit_empty|in_list[menunggu_aktivasi,aktif,terdaftar]',
        ])) {
            return $this->jsonResponse('error', 'Data pelamar belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $errorFoto = $this->validateFotoUpload();
        if ($errorFoto !== null) {
            return $this->jsonResponse('error', $errorFoto, [], 422);
        }

        $slugPeran = $this->normalisasiJenisPelamar($payload['jenis_pelamar']);
        $peran     = $this->peranModel->cariBySlug($slugPeran);

        if ($peran === null) {
            return $this->jsonResponse('error', 'Peran pelamar tidak ditemukan.', [], 404);
        }

        $uploadedFoto = null;

        try {
            $uploadedFoto = $this->simpanFotoPelamar();

            $this->db->transStart();

            $idPengguna = $this->penggunaModel->insert([
                'id_peran'       => $peran['id_peran'],
                'nama_lengkap'   => $payload['nama_lengkap'],
                'email'          => $payload['email'],
                'kata_sandi'     => password_hash($payload['kata_sandi'], PASSWORD_DEFAULT),
                'nomor_telepon'  => $payload['nomor_telepon'],
                'foto_profil'    => $uploadedFoto,
                'status_aktif'   => 1,
            ], true);

            $idPelamar = $this->pelamarModel->insert([
                'id_pengguna'         => $idPengguna,
                'account_id'          => $this->pelamarModel->generateAccountId(),
                'foto'                => $uploadedFoto,
                'jenis_kelamin'       => $payload['jenis_kelamin'],
                'tempat_lahir'        => $payload['tempat_lahir'],
                'tanggal_lahir'       => $payload['tanggal_lahir'],
                'alamat'              => $payload['alamat'],
                'nomer_nik'           => $payload['nomer_nik'],
                'status_pendaftaran'  => 'menunggu_aktivasi',
                'terdaftar_pada'      => date('Y-m-d H:i:s'),
                'diaktivasi_oleh'     => null,
                'diaktivasi_pada'     => null,
            ], true);

            if ($slugPeran === 'pelamar_alumni') {
                $this->db->table('tb_alumni')->insert([
                    'id_pelamar' => $idPelamar,
                ]);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Data pelamar gagal disimpan.');
            }

            return $this->jsonResponse('success', 'Pelamar berhasil ditambahkan.', [
                'id_pelamar' => $idPelamar,
            ]);
        } catch (\Throwable $th) {
            if ($uploadedFoto !== null) {
                $this->hapusFileLokal($uploadedFoto);
            }

            return $this->jsonResponse('error', $th->getMessage(), [], 500);
        }
    }

    /*
    |-------------------------------------------------------------------
    | METHOD UPDATE
    |-------------------------------------------------------------------
    | Memperbarui data akun pengguna dan data pelamar. Bila role diubah
    | menjadi alumni dan baris alumni belum ada, controller akan
    | membuatkan data tb_alumni secara otomatis.
    */
    public function update(int $idPelamar): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        if (! $this->request->isAJAX()) {
            return $this->jsonResponse('error', 'Request tidak valid.', [], 400);
        }

        $pelamar = $this->pelamarModel->ambilDetailById($idPelamar);
        if ($pelamar === null) {
            return $this->jsonResponse('error', 'Data pelamar tidak ditemukan.', [], 404);
        }

        $payload = $this->ambilPayloadPelamar();
        $payload['id_pengguna'] = (int) $pelamar['id_pengguna'];

        if (! $this->validateData($payload, [
            'id_pengguna'         => 'required|integer',
            'nama_lengkap'        => 'required',
            'email'               => 'required|valid_email|is_unique[tb_pengguna.email,id_pengguna,' . $payload['id_pengguna'] . ']',
            'jenis_pelamar'       => 'required|in_list[pelamar_alumni,pelamar_umum,alumni,umum]',
            'status_pendaftaran'  => 'permit_empty|in_list[menunggu_aktivasi,aktif,terdaftar]',
        ])) {
            return $this->jsonResponse('error', 'Data pelamar belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $errorFoto = $this->validateFotoUpload();
        if ($errorFoto !== null) {
            return $this->jsonResponse('error', $errorFoto, [], 422);
        }

        $slugPeran = $this->normalisasiJenisPelamar($payload['jenis_pelamar']);
        $peran     = $this->peranModel->cariBySlug($slugPeran);

        if ($peran === null) {
            return $this->jsonResponse('error', 'Peran pelamar tidak ditemukan.', [], 404);
        }

        $uploadedFoto = null;
        $fotoLama     = $pelamar['foto'] ?: $pelamar['foto_profil'];

        try {
            $uploadedFoto = $this->simpanFotoPelamar();
            $fotoFinal    = $uploadedFoto ?: $fotoLama;

            $this->db->transStart();

            $this->penggunaModel->update((int) $pelamar['id_pengguna'], [
                'id_peran'      => $peran['id_peran'],
                'nama_lengkap'  => $payload['nama_lengkap'],
                'email'         => $payload['email'],
                'nomor_telepon' => $payload['nomor_telepon'],
                'foto_profil'   => $fotoFinal,
            ]);

            $this->pelamarModel->update($idPelamar, [
                'foto'               => $fotoFinal,
                'jenis_kelamin'      => $payload['jenis_kelamin'],
                'tempat_lahir'       => $payload['tempat_lahir'],
                'tanggal_lahir'      => $payload['tanggal_lahir'],
                'alamat'             => $payload['alamat'],
                'nomer_nik'          => $payload['nomer_nik'],
                'status_pendaftaran' => $payload['status_pendaftaran'] !== '' ? $payload['status_pendaftaran'] : (string) $pelamar['status_pendaftaran'],
            ]);

            if ($slugPeran === 'pelamar_alumni' && empty($pelamar['id_alumni'])) {
                $this->db->table('tb_alumni')->insert([
                    'id_pelamar' => $idPelamar,
                ]);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Data pelamar gagal diperbarui.');
            }

            if ($uploadedFoto !== null && $fotoLama !== null && $fotoLama !== $uploadedFoto) {
                $this->hapusFileLokal($fotoLama);
            }

            return $this->jsonResponse('success', 'Pelamar berhasil diperbarui.', [
                'id_pelamar' => $idPelamar,
            ]);
        } catch (\Throwable $th) {
            if ($uploadedFoto !== null) {
                $this->hapusFileLokal($uploadedFoto);
            }

            return $this->jsonResponse('error', $th->getMessage(), [], 500);
        }
    }

    /*
    |-------------------------------------------------------------------
    | METHOD HAPUS
    |-------------------------------------------------------------------
    | Soft delete akun dilakukan dengan menonaktifkan status_aktif pada
    | tabel tb_pengguna. Untuk akun yang masih menunggu aktivasi, aksi
    | ini dipakai sebagai penolakan akses oleh admin BKK.
    */
    public function hapus(int $idPelamar): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        if (! $this->request->isAJAX()) {
            return $this->jsonResponse('error', 'Request tidak valid.', [], 400);
        }

        $pelamar = $this->pelamarModel->ambilDetailById($idPelamar);
        if ($pelamar === null) {
            return $this->jsonResponse('error', 'Data pelamar tidak ditemukan.', [], 404);
        }

        $this->db->table('tb_pengguna')
            ->whereIn('id_pengguna', static function ($builder) use ($idPelamar): void {
                $builder->select('id_pengguna')
                    ->from('tb_pelamar')
                    ->where('id_pelamar', $idPelamar);
            })
            ->update([
                'status_aktif' => 0,
            ]);

        $message = (string) ($pelamar['status_pendaftaran'] ?? '') === 'menunggu_aktivasi'
            ? 'Akses pelamar berhasil ditolak.'
            : 'Pelamar berhasil dinonaktifkan.';

        return $this->jsonResponse('success', $message, [
            'id_pelamar' => $idPelamar,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD HAPUS MASSAL
    |-------------------------------------------------------------------
    | Menghapus permanen beberapa akun pelamar sekaligus berdasarkan
    | array id_pelamar yang dikirim dari checkbox tabel.
    */
    public function hapusMassal(): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        if (! $this->request->isAJAX()) {
            return $this->jsonResponse('error', 'Request tidak valid.', [], 400);
        }

        $ids = $this->request->getPost('ids');
        if (! is_array($ids) || $ids === []) {
            return $this->jsonResponse('error', 'Tidak ada pelamar yang dipilih.', [], 422);
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

        if ($ids === []) {
            return $this->jsonResponse('error', 'ID pelamar tidak valid.', [], 422);
        }

        $idPenggunaList = $this->db->table('tb_pelamar')
            ->select('id_pengguna')
            ->whereIn('id_pelamar', $ids)
            ->get()
            ->getResultArray();

        $idPenggunaList = array_values(array_filter(array_map(static function (array $row): int {
            return (int) ($row['id_pengguna'] ?? 0);
        }, $idPenggunaList)));

        if ($idPenggunaList === []) {
            return $this->jsonResponse('error', 'Data akun pelamar tidak ditemukan.', [], 404);
        }

        $this->db->table('tb_pengguna')
            ->whereIn('id_pengguna', $idPenggunaList)
            ->delete();

        return $this->jsonResponse('success', 'Pelamar terpilih berhasil dihapus permanen.', [
            'ids' => $ids,
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AKTIVASI
    |-------------------------------------------------------------------
    | Mengaktifkan status pendaftaran pelamar dan mencatat siapa yang
    | melakukan aktivasi beserta waktu aktivasi.
    */
    public function aktivasi(int $idPelamar): ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
        }

        if (! $this->request->isAJAX()) {
            return $this->jsonResponse('error', 'Request tidak valid.', [], 400);
        }

        $pelamar = $this->pelamarModel->ambilDetailById($idPelamar);
        if ($pelamar === null) {
            return $this->jsonResponse('error', 'Data pelamar tidak ditemukan.', [], 404);
        }

        $this->penggunaModel->update((int) $pelamar['id_pengguna'], [
            'status_aktif' => 1,
        ]);

        $this->pelamarModel->update($idPelamar, [
            'status_pendaftaran' => 'aktif',
            'diaktivasi_oleh'    => (int) (session('id_pengguna') ?? 0),
            'diaktivasi_pada'    => date('Y-m-d H:i:s'),
        ]);

        return $this->jsonResponse('success', 'Pelamar berhasil diaktivasi.', [
            'id_pelamar' => $idPelamar,
        ]);
    }

    public function detail(int $idPelamar): string|RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $pelamar = $this->db->table('tb_pelamar p')
            ->select('p.*, u.id_pengguna, u.nama_lengkap, u.email, u.nomor_telepon, u.foto_profil, u.status_aktif, u.terakhir_login, u.dibuat_pada, r.slug_peran, r.nama_peran')
            ->join('tb_pengguna u', 'u.id_pengguna = p.id_pengguna')
            ->join('tb_peran r', 'r.id_peran = u.id_peran')
            ->where('p.id_pelamar', $idPelamar)
            ->get()
            ->getRowArray();

        if (! $pelamar) {
            throw PageNotFoundException::forPageNotFound();
        }

        $isAlumni = $pelamar['slug_peran'] === 'pelamar_alumni';

        $data = [
            'title'           => 'Detail Pelamar - Sistem Tracer Study & BKK',
            'detail_mode'     => session()->get('slug_peran') === 'admin_sekolah' ? 'admin_sekolah' : 'superadmin',
            'pelamar'         => $pelamar,
            'isAlumni'        => $isAlumni,
            'alumni'          => null,
            'tracer_terakhir' => null,
            'tracer_fields'   => [],
            'riwayat_kerja'   => $this->riwayatKerjaModel->ambilByPelamar($idPelamar),
            'berkas'          => $this->berkasModel->ambilByPelamar($idPelamar, 'profil'),
            'lamaran'         => $this->lamaranModel->ambilByPelamar($idPelamar),
            'jenis_berkas'    => $this->ambilJenisBerkas($isAlumni, 'profil'),
            'id_pelamar'    => $idPelamar,
            'aktivitas'      => $this->ambilAktivitas(),
            'daftar_angkatan' => $this->ambilAngkatan(),
            'daftar_kompetensi' => $this->ambilKompetensi(),
        ];

        if ($data['isAlumni']) {
            $data['alumni'] = $this->alumniModel->ambilLengkapByPelamar($idPelamar);

            if (! empty($data['alumni']['id_alumni'])) {
                $data['tracer_terakhir'] = $this->tracerAlumniModel->ambilTerakhirByAlumni((int) $data['alumni']['id_alumni']);
                $data['tracer_fields']   = $this->bangunTracerFields($data['tracer_terakhir']);
            }
        }

        return view('superadmin/pelamar/detail', $data);
    }

    public function simpanRiwayatKerja(): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        if (! $this->db->tableExists('tb_riwayat_kerja')) {
            return $this->jsonResponse('error', 'Tabel riwayat kerja belum tersedia di database.', [], 500);
        }

        $payload = $this->ambilPayloadRiwayatKerja();

        if (! $this->validateData($payload, [
            'id_pelamar'       => 'required|integer',
            'nama_perusahaan'  => 'required|max_length[150]',
            'posisi_jabatan'   => 'required|max_length[150]',
            'tanggal_mulai'    => 'required|valid_date[Y-m-d]',
            'tanggal_selesai'  => 'permit_empty|valid_date[Y-m-d]',
            'keterangan'       => 'permit_empty|max_length[500]',
        ])) {
            return $this->jsonResponse('error', 'Data riwayat kerja belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        if ($payload['tanggal_selesai'] !== null && $payload['tanggal_selesai'] < $payload['tanggal_mulai']) {
            return $this->jsonResponse('error', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.', [], 422);
        }

        if ($this->pelamarModel->find($payload['id_pelamar']) === null) {
            return $this->jsonResponse('error', 'Pelamar tidak ditemukan.', [], 404);
        }

        $idRiwayat = $this->riwayatKerjaModel->insert($payload, true);

        return $this->jsonResponse('success', 'Riwayat kerja berhasil ditambahkan.', [
            'id_riwayat_kerja' => $idRiwayat,
        ]);
    }

    public function updateRiwayatKerja(int $id): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        if (! $this->db->tableExists('tb_riwayat_kerja')) {
            return $this->jsonResponse('error', 'Tabel riwayat kerja belum tersedia di database.', [], 500);
        }

        $riwayat = $this->riwayatKerjaModel->find($id);
        if ($riwayat === null) {
            return $this->jsonResponse('error', 'Riwayat kerja tidak ditemukan.', [], 404);
        }

        $payload = $this->ambilPayloadRiwayatKerja();
        $payload['id_pelamar'] = (int) $riwayat['id_pelamar'];

        if (! $this->validateData($payload, [
            'id_pelamar'       => 'required|integer',
            'nama_perusahaan'  => 'required|max_length[150]',
            'posisi_jabatan'   => 'required|max_length[150]',
            'tanggal_mulai'    => 'required|valid_date[Y-m-d]',
            'tanggal_selesai'  => 'permit_empty|valid_date[Y-m-d]',
            'keterangan'       => 'permit_empty|max_length[500]',
        ])) {
            return $this->jsonResponse('error', 'Data riwayat kerja belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        if ($payload['tanggal_selesai'] !== null && $payload['tanggal_selesai'] < $payload['tanggal_mulai']) {
            return $this->jsonResponse('error', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.', [], 422);
        }

        $this->riwayatKerjaModel->update($id, $payload);

        return $this->jsonResponse('success', 'Riwayat kerja berhasil diperbarui.', [
            'id_riwayat_kerja' => $id,
        ]);
    }

    public function hapusRiwayatKerja(int $id): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        if (! $this->db->tableExists('tb_riwayat_kerja')) {
            return $this->jsonResponse('error', 'Tabel riwayat kerja belum tersedia di database.', [], 500);
        }

        $riwayat = $this->riwayatKerjaModel->find($id);
        if ($riwayat === null) {
            return $this->jsonResponse('error', 'Riwayat kerja tidak ditemukan.', [], 404);
        }

        $this->riwayatKerjaModel->delete($id);

        return $this->jsonResponse('success', 'Riwayat kerja berhasil dihapus.', [
            'id_riwayat_kerja' => $id,
        ]);
    }

    public function uploadBerkas(): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        if (! $this->db->tableExists('tb_berkas')) {
            return $this->jsonResponse('error', 'Tabel berkas belum tersedia di database.', [], 500);
        }

        $idBerkas = (int) ($this->request->getPost('id_berkas') ?? 0);
        $berkasLama = null;

        if ($idBerkas > 0) {
            $berkasLama = $this->berkasModel->find($idBerkas);

            if ($berkasLama === null) {
                return $this->jsonResponse('error', 'Data berkas tidak ditemukan.', [], 404);
            }
        }

        $payload = [
            'id_pelamar'      => (int) ($this->request->getPost('id_pelamar') ?? ($berkasLama['id_pelamar'] ?? 0)),
            'id_jenis_berkas' => (int) ($this->request->getPost('id_jenis_berkas') ?? ($berkasLama['id_jenis_berkas'] ?? 0)),
        ];

        if (! $this->validateData($payload, [
            'id_pelamar'      => 'required|integer',
            'id_jenis_berkas' => 'required|integer',
        ])) {
            return $this->jsonResponse('error', 'Data berkas belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        if ($this->pelamarModel->find($payload['id_pelamar']) === null) {
            return $this->jsonResponse('error', 'Pelamar tidak ditemukan.', [], 404);
        }

        $jenisBerkas = $this->berkasModel->cariJenisBerkas($payload['id_jenis_berkas'], 'profil');
        if ($jenisBerkas === null) {
            return $this->jsonResponse('error', 'Jenis berkas profil tidak ditemukan atau tidak dapat diunggah dari halaman ini.', [], 422);
        }

        if ($idBerkas === 0) {
            $berkasLama = $this->berkasModel
                ->where('id_pelamar', $payload['id_pelamar'])
                ->where('id_jenis_berkas', $payload['id_jenis_berkas'])
                ->orderBy('id_berkas', 'DESC')
                ->first();

            if ($berkasLama !== null) {
                $idBerkas = (int) $berkasLama['id_berkas'];
            }
        }

        $file = $this->request->getFile('file_berkas');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            $file = $this->request->getFile('file');
        }

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $this->jsonResponse('error', 'File berkas wajib dipilih.', [], 422);
        }

        if (! $file->isValid() || $file->hasMoved()) {
            return $this->jsonResponse('error', 'File berkas tidak valid.', [], 422);
        }

        if ($file->getSizeByUnit('kb') > 5120) {
            return $this->jsonResponse('error', 'Ukuran berkas maksimal 5 MB.', [], 422);
        }

        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
        $extension  = strtolower((string) $file->getExtension());

        if (! in_array($extension, $allowedExt, true)) {
            return $this->jsonResponse('error', 'Format berkas harus pdf, jpg, jpeg, atau png.', [], 422);
        }

        $relativeDirectory = 'uploads/berkas/' . $payload['id_pelamar'];
        $targetDirectory   = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            return $this->jsonResponse('error', 'Direktori upload berkas tidak dapat dibuat.', [], 500);
        }

        $randomName = $file->getRandomName();
        $file->move($targetDirectory, $randomName);
        $relativePath = $relativeDirectory . '/' . $randomName;

        $dataBerkas = [
            'id_pelamar'      => $payload['id_pelamar'],
            'id_jenis_berkas' => $payload['id_jenis_berkas'],
            'nama_file'       => $file->getClientName(),
            'path_file'       => $relativePath,
            'status_unggah'   => 'sudah_diunggah',
        ];

        if ($this->db->fieldExists('ukuran_file', 'tb_berkas')) {
            $dataBerkas['ukuran_file'] = $file->getSize();
        }

        if ($this->db->fieldExists('tipe_mime', 'tb_berkas')) {
            $dataBerkas['tipe_mime'] = method_exists($file, 'getClientMimeType')
                ? $file->getClientMimeType()
                : $file->getMimeType();
        }

        if ($this->db->fieldExists('catatan', 'tb_berkas')) {
            $dataBerkas['catatan'] = null;
        }

        if ($idBerkas > 0) {
            $this->berkasModel->update($idBerkas, $dataBerkas);

            if (! empty($berkasLama['path_file']) && $berkasLama['path_file'] !== $relativePath) {
                $this->hapusFileLokal($berkasLama['path_file']);
            }

            return $this->jsonResponse('success', 'Berkas berhasil diperbarui.', [
                'id_berkas' => $idBerkas,
            ]);
        }

        $idBerkasBaru = $this->berkasModel->insert($dataBerkas, true);

        return $this->jsonResponse('success', 'Berkas berhasil diunggah.', [
            'id_berkas' => $idBerkasBaru,
        ]);
    }

    public function hapusBerkas(int $id): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        if (! $this->db->tableExists('tb_berkas')) {
            return $this->jsonResponse('error', 'Tabel berkas belum tersedia di database.', [], 500);
        }

        $berkas = $this->berkasModel->find($id);
        if ($berkas === null) {
            return $this->jsonResponse('error', 'Berkas tidak ditemukan.', [], 404);
        }

        $this->berkasModel->delete($id);
        $this->hapusFileLokal($berkas['path_file'] ?? null);

        return $this->jsonResponse('success', 'Berkas berhasil dihapus.', [
            'id_berkas' => $id,
        ]);
    }

    public function updateEmail(): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $idPelamar = (int) ($this->request->getPost('id_pelamar') ?? 0);
        $emailBaru = trim((string) $this->request->getPost('email_baru'));
        $konfirmasiPassword = $this->request->getPost('konfirmasi_password');

        if ($emailBaru === '') {
            return $this->jsonResponse('error', 'Email baru wajib diisi.', [], 422);
        }

        if (! filter_var($emailBaru, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse('error', 'Format email tidak valid.', [], 422);
        }

        if ($konfirmasiPassword === '') {
            return $this->jsonResponse('error', 'Konfirmasi password wajib diisi.', [], 422);
        }

        $pelamar = $this->pelamarModel->ambilDetailById($idPelamar);
        if ($pelamar === null) {
            return $this->jsonResponse('error', 'Data pelamar tidak ditemukan.', [], 404);
        }

        $pengguna = $this->penggunaModel->find((int) $pelamar['id_pengguna']);
        if ($pengguna === null) {
            return $this->jsonResponse('error', 'Data pengguna tidak ditemukan.', [], 404);
        }

        if (! password_verify($konfirmasiPassword, $pengguna['kata_sandi'])) {
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

    public function updatePassword(): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $idPelamar = (int) ($this->request->getPost('id_pelamar') ?? 0);
        $passwordSaatIni = $this->request->getPost('password_saat_ini');
        $passwordBaru = $this->request->getPost('password_baru');
        $konfirmasiPasswordBaru = $this->request->getPost('konfirmasi_password_baru');

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

        $pelamar = $this->pelamarModel->ambilDetailById($idPelamar);
        if ($pelamar === null) {
            return $this->jsonResponse('error', 'Data pelamar tidak ditemukan.', [], 404);
        }

        $pengguna = $this->penggunaModel->find((int) $pelamar['id_pengguna']);
        if ($pengguna === null) {
            return $this->jsonResponse('error', 'Data pengguna tidak ditemukan.', [], 404);
        }

        if (! password_verify($passwordSaatIni, $pengguna['kata_sandi'])) {
            return $this->jsonResponse('error', 'Password saat ini salah.', [], 422);
        }

        $this->penggunaModel->update((int) $pelamar['id_pengguna'], [
            'kata_sandi' => password_hash($passwordBaru, PASSWORD_DEFAULT),
        ]);

        return $this->jsonResponse('success', 'Password berhasil diperbarui.', []);
    }

    public function simpanTracer(): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        if (! $this->db->tableExists('tb_tracer_alumni')) {
            return $this->jsonResponse('error', 'Tabel tracer alumni belum tersedia.', [], 500);
        }

        $idPelamar = (int) ($this->request->getPost('id_pelamar') ?? 0);
        $idAktivitas = (int) ($this->request->getPost('id_aktivitas') ?? 0);

        if ($idPelamar === 0 || $idAktivitas === 0) {
            return $this->jsonResponse('error', 'Data tidak valid.', [], 422);
        }

        $pelamar = $this->pelamarModel->ambilDetailById($idPelamar);
        if ($pelamar === null) {
            return $this->jsonResponse('error', 'Pelamar tidak ditemukan.', [], 404);
        }

        $alumni = $this->alumniModel->where('id_pelamar', $idPelamar)->first();
        if ($alumni === null) {
            return $this->jsonResponse('error', 'Data alumni tidak ditemukan.', [], 404);
        }

        $idAlumni = (int) $alumni['id_alumni'];

        $statusTracer = $this->isSuperadmin() ? 'terkirim' : 'draft';
        $idVerifier = $this->isSuperadmin() ? (int) session('id_pengguna') : null;

        $data = [
            'id_alumni'       => $idAlumni,
            'id_aktivitas'   => $idAktivitas,
            'status'         => $statusTracer,
            'diverifikasi_oleh' => $idVerifier,
            'diverifikasi_pada' => $idVerifier ? date('Y-m-d H:i:s') : null,
            'relevan_jurusan' => $this->request->getPost('relevan_jurusan') ? (int) $this->request->getPost('relevan_jurusan') : null,
            'posisi_kerja'    => trim((string) $this->request->getPost('posisi_kerja')) ?: null,
            'nama_dudi'       => trim((string) $this->request->getPost('nama_dudi')) ?: null,
            'bidang_dudi'     => trim((string) $this->request->getPost('bidang_dudi')) ?: null,
            'alamat_dudi'    => trim((string) $this->request->getPost('alamat_dudi')) ?: null,
            'tahun_mulai_kerja' => $this->request->getPost('tahun_mulai_kerja') ? (int) $this->request->getPost('tahun_mulai_kerja') : null,
            'penghasilan_range' => trim((string) $this->request->getPost('penghasilan_range')) ?: null,
            'universitas'    => trim((string) $this->request->getPost('universitas')) ?: null,
            'program_studi' => trim((string) $this->request->getPost('program_studi')) ?: null,
            'status_kuliah' => trim((string) $this->request->getPost('status_kuliah')) ?: null,
            'nama_usaha'   => trim((string) $this->request->getPost('nama_usaha')) ?: null,
            'bidang_usaha' => trim((string) $this->request->getPost('bidang_usaha')) ?: null,
            'modal_awal'    => $this->request->getPost('modal_awal') ? (float) $this->request->getPost('modal_awal') : null,
            'penghasilan_usaha' => trim((string) $this->request->getPost('penghasilan_usaha')) ?: null,
            'rencana_kedepan' => trim((string) $this->request->getPost('rencana_kedepan')) ?: null,
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
            return $this->jsonResponse('success', 'Tracer study berhasil diperbarui.', [
                'id_tracer' => $tracerLama['id_tracer'],
            ]);
        }

        $idTracer = $this->tracerAlumniModel->insert($data, true);

        return $this->jsonResponse('success', 'Tracer study berhasil disimpan.', [
            'id_tracer' => $idTracer,
        ]);
    }

    protected function ambilPayloadPelamar(): array
    {
        return [
            'nama_lengkap'       => trim((string) $this->request->getPost('nama_lengkap')),
            'email'              => trim((string) $this->request->getPost('email')),
            'kata_sandi'         => (string) $this->request->getPost('kata_sandi'),
            'jenis_pelamar'      => trim((string) $this->request->getPost('jenis_pelamar')),
            'nomor_telepon'      => trim((string) $this->request->getPost('nomor_telepon')) ?: null,
            'jenis_kelamin'      => trim((string) $this->request->getPost('jenis_kelamin')) ?: null,
            'tempat_lahir'       => trim((string) $this->request->getPost('tempat_lahir')) ?: null,
            'tanggal_lahir'      => trim((string) $this->request->getPost('tanggal_lahir')) ?: null,
            'alamat'             => trim((string) $this->request->getPost('alamat')) ?: null,
            'nomer_nik'          => trim((string) $this->request->getPost('nomer_nik')) ?: null,
            'status_pendaftaran' => trim((string) $this->request->getPost('status_pendaftaran')),
        ];
    }

    protected function ambilPayloadRiwayatKerja(): array
    {
        $tanggalSelesai = trim((string) $this->request->getPost('tanggal_selesai'));

        return [
            'id_pelamar'      => (int) ($this->request->getPost('id_pelamar') ?? 0),
            'nama_perusahaan' => trim((string) $this->request->getPost('nama_perusahaan')),
            'posisi_jabatan'  => trim((string) $this->request->getPost('posisi_jabatan')),
            'tanggal_mulai'   => trim((string) $this->request->getPost('tanggal_mulai')),
            'tanggal_selesai' => $tanggalSelesai !== '' ? $tanggalSelesai : null,
            'keterangan'      => trim((string) $this->request->getPost('keterangan')) ?: null,
        ];
    }

    protected function normalisasiJenisPelamar(string $jenisPelamar): string
    {
        return match ($jenisPelamar) {
            'alumni', 'pelamar_alumni' => 'pelamar_alumni',
            default => 'pelamar_umum',
        };
    }

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

    protected function formatPelamarRow(array $row): array
    {
        $foto = $row['foto'] ?: $row['foto_profil'];

        $row['foto_url'] = $foto
            ? base_url($foto)
            : base_url('assets/media/avatars/blank.png');

        return $row;
    }

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
    | Helper ini menentukan apakah view sedang dibuka dari Super Admin
    | atau Admin Sekolah/BKK. Nilainya dipakai untuk breadcrumb dan
    | endpoint AJAX agar satu controller bisa melayani dua area tanpa
    | menggandakan logika CRUD.
    |
    | Tips Debugging:
    | - Jika link Admin Sekolah masih menuju superadmin, cek session
    |   slug_peran harus bernilai admin_sekolah.
    */
    protected function ambilAreaPrefix(): string
    {
        return session()->get('slug_peran') === 'admin_sekolah' ? 'admin-sekolah' : 'superadmin';
    }

    protected function ambilDashboardUrl(): string
    {
        return base_url($this->ambilAreaPrefix() === 'admin-sekolah' ? 'admin-sekolah/dashboard' : 'dashboard/superadmin');
    }

    protected function ambilJenisBerkas(bool $isAlumni = false, ?string $scopePenggunaan = null): array
    {
        if (! $this->db->tableExists('tb_jenis_berkas')) {
            return [];
        }

        $selects = ['id_jenis_berkas', 'nama_berkas', 'wajib'];

        if ($this->db->fieldExists('slug_berkas', 'tb_jenis_berkas')) {
            $selects[] = 'slug_berkas';
        }

        $builder = $this->db->table('tb_jenis_berkas')
            ->select(implode(', ', $selects));

        if ($this->db->fieldExists('status_aktif', 'tb_jenis_berkas')) {
            $builder->where('status_aktif', 1);
        }

        if ($this->db->fieldExists('berlaku_untuk', 'tb_jenis_berkas')) {
            $builder->whereIn('berlaku_untuk', ['semua', $isAlumni ? 'alumni' : 'umum']);
        }

        if ($scopePenggunaan !== null && $this->db->fieldExists('scope_penggunaan', 'tb_jenis_berkas')) {
            $builder->whereIn('scope_penggunaan', [$scopePenggunaan, 'keduanya']);
        }

        if ($scopePenggunaan === 'profil' && $this->db->fieldExists('slug_berkas', 'tb_jenis_berkas')) {
            $builder->whereNotIn('slug_berkas', ['cv', 'surat_lamaran', 'portofolio']);
        }

        if ($this->db->fieldExists('wajib', 'tb_jenis_berkas')) {
            $builder->orderBy('wajib', 'DESC');
        }

        return $builder
            ->orderBy('id_jenis_berkas', 'ASC')
            ->get()
            ->getResultArray();
    }

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

    protected function bangunTracerFields(?array $tracer): array
    {
        if ($tracer === null) {
            return [];
        }

        $aktivitas = strtolower((string) ($tracer['nama_aktivitas'] ?? ''));
        $mapFields = [];

        if (str_contains($aktivitas, 'kerja')) {
            $mapFields = [
                'nama_perusahaan' => 'Perusahaan',
                'posisi_jabatan'  => 'Posisi / Jabatan',
                'gaji'            => 'Gaji',
                'lokasi_kerja'    => 'Lokasi Kerja',
            ];
        } elseif (str_contains($aktivitas, 'kuliah')) {
            $mapFields = [
                'nama_kampus'     => 'Perguruan Tinggi',
                'program_studi'   => 'Program Studi',
                'jenjang'         => 'Jenjang',
                'lokasi_kampus'   => 'Lokasi Kampus',
            ];
        } elseif (str_contains($aktivitas, 'wirausaha')) {
            $mapFields = [
                'nama_usaha'      => 'Nama Usaha',
                'bidang_usaha'    => 'Bidang Usaha',
                'jabatan'         => 'Peran',
                'lokasi_usaha'    => 'Lokasi Usaha',
            ];
        } elseif (str_contains($aktivitas, 'belum')) {
            $mapFields = [
                'alasan'          => 'Alasan',
                'rencana'         => 'Rencana',
                'catatan'         => 'Catatan',
            ];
        }

        $fields = [];

        foreach ($mapFields as $key => $label) {
            if (! empty($tracer[$key])) {
                $fields[] = [
                    'label' => $label,
                    'value' => (string) $tracer[$key],
                ];
            }
        }

        if ($fields !== []) {
            return $fields;
        }

        $excluded = [
            'id_tracer',
            'id_alumni',
            'id_aktivitas',
            'nama_aktivitas',
            'dibuat_pada',
            'diperbarui_pada',
        ];

        foreach ($tracer as $key => $value) {
            if (in_array($key, $excluded, true) || $value === null || $value === '') {
                continue;
            }

            $fields[] = [
                'label' => ucwords(str_replace('_', ' ', $key)),
                'value' => (string) $value,
            ];
        }

        return $fields;
    }

    protected function jsonResponse(string $status, string $message, array $data = [], int $httpCode = 200): ResponseInterface
    {
        return $this->response->setStatusCode($httpCode)->setJSON(array_merge([
            'status'   => $status,
            'message'  => $message,
            'csrfHash' => csrf_hash(),
        ], $data));
    }
}
