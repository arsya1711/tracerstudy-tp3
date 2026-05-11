<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use App\Models\PenggunaModel;
use App\Models\PeranModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use RuntimeException;

class AdminController extends BaseController
{
    protected AdminModel $adminModel;
    protected PenggunaModel $penggunaModel;
    protected PeranModel $peranModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->adminModel    = new AdminModel();
        $this->penggunaModel = new PenggunaModel();
        $this->peranModel    = new PeranModel();
        $this->db            = Database::connect();
    }

    public function index(): string|RedirectResponse|ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            if ($this->request->isAJAX()) {
                return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
            }

            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        if ($this->request->isAJAX()) {
            $result = $this->adminModel->getDataTables($this->request);
            $result['data'] = array_map(fn (array $row): array => $this->formatAdminRow($row), $result['data']);
            $result['csrfHash'] = csrf_hash();

            return $this->response->setJSON($result);
        }

        $areaPrefix = $this->ambilAreaPrefix();

        return view('superadmin/admin/index', [
            'title'               => 'Manajemen Admin - Sistem Tracer Study & BKK',
            'areaPrefix'          => $areaPrefix,
            'dashboardUrl'        => $this->ambilDashboardUrl(),
            'pageHeading'         => $areaPrefix === 'admin-sekolah' ? 'Data Admin' : 'Manajemen Admin',
            'breadcrumbParent'    => 'Manajemen Pengguna',
            'breadcrumbCurrent'   => 'Data Admin',
            'jenis_admin'         => $this->ambilJenisAdmin(),
            'daftar_perusahaan'   => $this->ambilDaftarPerusahaan(),
            'perusahaan_tersedia' => $this->perusahaanTersedia(),
        ]);
    }

    public function simpan(): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $payload = $this->ambilPayloadAdmin();
        $allowedJenis = implode(',', array_column($this->ambilJenisAdmin(), 'slug_peran'));

        if (! $this->validateData($payload, [
            'nama_lengkap' => 'required',
            'email'        => 'required|valid_email|is_unique[tb_pengguna.email]',
            'kata_sandi'   => 'required|min_length[8]',
            'jenis_admin'  => 'required|in_list[' . $allowedJenis . ']',
        ])) {
            return $this->jsonResponse('error', 'Data admin belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $errorFoto = $this->validateFotoUpload();
        if ($errorFoto !== null) {
            return $this->jsonResponse('error', $errorFoto, [], 422);
        }

        $errorPerusahaan = $this->validatePenugasanPerusahaan($payload['jenis_admin'], $payload['id_perusahaan']);
        if ($errorPerusahaan !== null) {
            return $this->jsonResponse('error', $errorPerusahaan, [], 422);
        }

        $peran = $this->peranModel->cariBySlug($payload['jenis_admin']);
        if ($peran === null) {
            return $this->jsonResponse('error', 'Peran admin tidak ditemukan.', [], 404);
        }

        $uploadedFoto = null;

        try {
            $uploadedFoto = $this->simpanFotoAdmin();

            $this->db->transStart();

            $idPengguna = $this->penggunaModel->insert([
                'id_peran'      => $peran['id_peran'],
                'nama_lengkap'  => $payload['nama_lengkap'],
                'email'         => $payload['email'],
                'kata_sandi'    => password_hash($payload['kata_sandi'], PASSWORD_DEFAULT),
                'nomor_telepon' => $payload['nomor_telepon'],
                'foto_profil'   => $uploadedFoto,
                'status_aktif'  => 1,
            ], true);

            $this->sinkronkanPerusahaanAdmin((int) $idPengguna, $payload['jenis_admin'], $payload['id_perusahaan']);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Data admin gagal disimpan.');
            }

            return $this->jsonResponse('success', 'Admin berhasil ditambahkan.', [
                'id_pengguna' => $idPengguna,
            ]);
        } catch (\Throwable $th) {
            if ($uploadedFoto !== null) {
                $this->hapusFileLokal($uploadedFoto);
            }

            return $this->jsonResponse('error', $th->getMessage(), [], 500);
        }
    }

    public function update(int $idPengguna): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $admin = $this->adminModel->ambilDetailById($idPengguna);
        if ($admin === null) {
            return $this->jsonResponse('error', 'Data admin tidak ditemukan.', [], 404);
        }

        $payload = $this->ambilPayloadAdmin();
        $allowedJenis = implode(',', array_column($this->ambilJenisAdmin(), 'slug_peran'));

        if (! $this->validateData($payload, [
            'nama_lengkap' => 'required',
            'email'        => 'required|valid_email|is_unique[tb_pengguna.email,id_pengguna,' . $idPengguna . ']',
            'jenis_admin'  => 'required|in_list[' . $allowedJenis . ']',
            'kata_sandi'   => 'permit_empty|min_length[8]',
        ])) {
            return $this->jsonResponse('error', 'Data admin belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $errorFoto = $this->validateFotoUpload();
        if ($errorFoto !== null) {
            return $this->jsonResponse('error', $errorFoto, [], 422);
        }

        $errorPerusahaan = $this->validatePenugasanPerusahaan($payload['jenis_admin'], $payload['id_perusahaan'], $idPengguna);
        if ($errorPerusahaan !== null) {
            return $this->jsonResponse('error', $errorPerusahaan, [], 422);
        }

        $peran = $this->peranModel->cariBySlug($payload['jenis_admin']);
        if ($peran === null) {
            return $this->jsonResponse('error', 'Peran admin tidak ditemukan.', [], 404);
        }

        $uploadedFoto = null;
        $fotoLama = $admin['foto_profil'] ?? null;

        try {
            $uploadedFoto = $this->simpanFotoAdmin();
            $fotoFinal = $fotoLama;

            if ($payload['foto_remove']) {
                $fotoFinal = null;
            }

            if ($uploadedFoto !== null) {
                $fotoFinal = $uploadedFoto;
            }

            $dataPengguna = [
                'id_peran'      => $peran['id_peran'],
                'nama_lengkap'  => $payload['nama_lengkap'],
                'email'         => $payload['email'],
                'nomor_telepon' => $payload['nomor_telepon'],
                'foto_profil'   => $fotoFinal,
            ];

            if ($payload['kata_sandi'] !== '') {
                $dataPengguna['kata_sandi'] = password_hash($payload['kata_sandi'], PASSWORD_DEFAULT);
            }

            $this->db->transStart();

            $this->penggunaModel->update($idPengguna, $dataPengguna);
            $this->sinkronkanPerusahaanAdmin($idPengguna, $payload['jenis_admin'], $payload['id_perusahaan']);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Data admin gagal diperbarui.');
            }

            if (($uploadedFoto !== null || $payload['foto_remove']) && $fotoLama !== null && $fotoLama !== $uploadedFoto) {
                $this->hapusFileLokal($fotoLama);
            }

            return $this->jsonResponse('success', 'Admin berhasil diperbarui.', [
                'id_pengguna' => $idPengguna,
            ]);
        } catch (\Throwable $th) {
            if ($uploadedFoto !== null) {
                $this->hapusFileLokal($uploadedFoto);
            }

            return $this->jsonResponse('error', $th->getMessage(), [], 500);
        }
    }

    public function aktivasi(int $idPengguna): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $admin = $this->adminModel->ambilDetailById($idPengguna);
        if ($admin === null) {
            return $this->jsonResponse('error', 'Data admin tidak ditemukan.', [], 404);
        }

        $this->penggunaModel->update($idPengguna, ['status_aktif' => 1]);

        return $this->jsonResponse('success', 'Admin berhasil diaktifkan.', [
            'id_pengguna' => $idPengguna,
        ]);
    }

    public function hapus(int $idPengguna): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $admin = $this->adminModel->ambilDetailById($idPengguna);
        if ($admin === null) {
            return $this->jsonResponse('error', 'Data admin tidak ditemukan.', [], 404);
        }

        $this->penggunaModel->update($idPengguna, ['status_aktif' => 0]);

        return $this->jsonResponse('success', 'Admin berhasil dinonaktifkan.', [
            'id_pengguna' => $idPengguna,
        ]);
    }

    public function hapusMassal(): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $ids = $this->request->getPost('ids');
        if (! is_array($ids) || $ids === []) {
            return $this->jsonResponse('error', 'Tidak ada admin yang dipilih.', [], 422);
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

        if ($ids === []) {
            return $this->jsonResponse('error', 'ID admin tidak valid.', [], 422);
        }

        $admins = $this->db->table('tb_pengguna u')
            ->select('u.id_pengguna, u.foto_profil')
            ->join('tb_peran r', 'r.id_peran = u.id_peran', 'inner')
            ->whereIn('u.id_pengguna', $ids)
            ->whereIn('r.slug_peran', ['admin_sekolah', 'admin_dudi', 'admin_perusahaan'])
            ->get()
            ->getResultArray();

        if ($admins === []) {
            return $this->jsonResponse('error', 'Data admin tidak ditemukan.', [], 404);
        }

        $this->lepaskanSemuaPerusahaanDariAdmin($ids);
        $this->penggunaModel->whereIn('id_pengguna', $ids)->delete();

        foreach ($admins as $admin) {
            $this->hapusFileLokal($admin['foto_profil'] ?? null);
        }

        return $this->jsonResponse('success', 'Admin terpilih berhasil dihapus permanen.', [
            'ids' => $ids,
        ]);
    }

    protected function ambilPayloadAdmin(): array
    {
        return [
            'nama_lengkap'  => trim((string) $this->request->getPost('nama_lengkap')),
            'email'         => trim((string) $this->request->getPost('email')),
            'kata_sandi'    => (string) $this->request->getPost('kata_sandi'),
            'jenis_admin'   => trim((string) $this->request->getPost('jenis_admin')),
            'nomor_telepon' => trim((string) $this->request->getPost('nomor_telepon')) ?: null,
            'id_perusahaan' => (int) ($this->request->getPost('id_perusahaan') ?? 0),
            'foto_remove'   => (string) $this->request->getPost('foto_remove') === '1',
        ];
    }

    protected function ambilJenisAdmin(): array
    {
        return $this->db->table('tb_peran')
            ->select('id_peran, nama_peran, slug_peran')
            ->whereIn('slug_peran', ['admin_sekolah', 'admin_dudi', 'admin_perusahaan'])
            ->orderBy('id_peran', 'ASC')
            ->get()
            ->getResultArray();
    }

    protected function perusahaanTersedia(): bool
    {
        return $this->db->tableExists('tb_perusahaan')
            && $this->db->fieldExists('id_perusahaan', 'tb_perusahaan')
            && $this->db->fieldExists('nama_perusahaan', 'tb_perusahaan')
            && $this->db->fieldExists('id_pengguna', 'tb_perusahaan');
    }

    protected function ambilDaftarPerusahaan(): array
    {
        if (! $this->perusahaanTersedia()) {
            return [];
        }

        return $this->db->table('tb_perusahaan')
            ->select('id_perusahaan, nama_perusahaan, id_pengguna')
            ->orderBy('nama_perusahaan', 'ASC')
            ->get()
            ->getResultArray();
    }

    protected function validatePenugasanPerusahaan(string $jenisAdmin, int $idPerusahaan, ?int $idPengguna = null): ?string
    {
        if (! $this->perusahaanTersedia()) {
            return null;
        }

        if ($jenisAdmin !== 'admin_dudi' && $jenisAdmin !== 'admin_perusahaan') {
            return null;
        }

        if ($idPerusahaan <= 0) {
            return 'Pilih perusahaan untuk admin DUDI.';
        }

        $perusahaan = $this->db->table('tb_perusahaan')
            ->select('id_perusahaan, id_pengguna')
            ->where('id_perusahaan', $idPerusahaan)
            ->get()
            ->getRowArray();

        if ($perusahaan === null) {
            return 'Perusahaan tidak ditemukan.';
        }

        $pemilikSaatIni = (int) ($perusahaan['id_pengguna'] ?? 0);
        if ($pemilikSaatIni > 0 && $pemilikSaatIni !== (int) ($idPengguna ?? 0)) {
            return 'Perusahaan tersebut sudah terhubung ke admin lain.';
        }

        return null;
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

    protected function simpanFotoAdmin(): ?string
    {
        $file = $this->request->getFile('foto');

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (! $file->isValid() || $file->hasMoved()) {
            throw new RuntimeException('Upload foto gagal diproses.');
        }

        $targetDirectory = FCPATH . 'uploads/admin';
        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            throw new RuntimeException('Direktori upload foto admin tidak dapat dibuat.');
        }

        $randomName = $file->getRandomName();
        $file->move($targetDirectory, $randomName);

        return 'uploads/admin/' . $randomName;
    }

    protected function sinkronkanPerusahaanAdmin(int $idPengguna, string $jenisAdmin, int $idPerusahaan): void
    {
        if (! $this->perusahaanTersedia()) {
            return;
        }

        $this->db->table('tb_perusahaan')
            ->where('id_pengguna', $idPengguna)
            ->update(['id_pengguna' => null]);

        if (($jenisAdmin === 'admin_dudi' || $jenisAdmin === 'admin_perusahaan') && $idPerusahaan > 0) {
            $this->db->table('tb_perusahaan')
                ->where('id_perusahaan', $idPerusahaan)
                ->update(['id_pengguna' => $idPengguna]);
        }
    }

    protected function lepaskanSemuaPerusahaanDariAdmin(array $ids): void
    {
        if (! $this->perusahaanTersedia() || $ids === []) {
            return;
        }

        $this->db->table('tb_perusahaan')
            ->whereIn('id_pengguna', $ids)
            ->update(['id_pengguna' => null]);
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

    protected function formatAdminRow(array $row): array
    {
        $row['foto_url'] = ! empty($row['foto_profil'])
            ? base_url((string) $row['foto_profil'])
            : base_url('assets/media/avatars/blank.png');

        $row['nama_perusahaan'] = trim((string) ($row['nama_perusahaan'] ?? '')) ?: '-';

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
    | Menentukan prefix route dan dashboard untuk view Data Admin yang
    | dipakai ulang oleh Super Admin dan Admin Sekolah/BKK.
    |
    | Tips Debugging:
    | - Jika AJAX Data Admin mengarah ke prefix yang salah, cek nilai
    |   areaPrefix yang dikirim dari method index().
    */
    protected function ambilAreaPrefix(): string
    {
        return session()->get('slug_peran') === 'admin_sekolah' ? 'admin-sekolah' : 'superadmin';
    }

    protected function ambilDashboardUrl(): string
    {
        return base_url($this->ambilAreaPrefix() === 'admin-sekolah' ? 'admin-sekolah/dashboard' : 'dashboard/superadmin');
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
