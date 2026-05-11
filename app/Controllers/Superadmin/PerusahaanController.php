<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use App\Models\KerjasamaModel;
use App\Models\PerusahaanModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use RuntimeException;

class PerusahaanController extends BaseController
{
    protected PerusahaanModel $perusahaanModel;
    protected KerjasamaModel $kerjasamaModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        helper('url');
        $this->perusahaanModel = new PerusahaanModel();
        $this->kerjasamaModel  = new KerjasamaModel();
        $this->db              = Database::connect();
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
            $result = $this->perusahaanModel->getDataTables($this->request);
            $relasiKerjasama = $this->perusahaanModel->ambilMapKerjasamaUntukPerusahaan(array_column($result['data'], 'id_perusahaan'));
            $result['data'] = array_map(function (array $row) use ($relasiKerjasama): array {
                $idPerusahaan = (int) ($row['id_perusahaan'] ?? 0);
                $row['kerjasama_ids'] = $relasiKerjasama[$idPerusahaan]['kerjasama_ids'] ?? [];
                $row['kerjasama_nama'] = $relasiKerjasama[$idPerusahaan]['kerjasama_nama'] ?? [];
                $row['kerjasama_slug'] = $relasiKerjasama[$idPerusahaan]['kerjasama_slug'] ?? [];

                return $this->formatPerusahaanRow($row);
            }, $result['data']);
            $result['csrfHash'] = csrf_hash();

            return $this->response->setJSON($result);
        }

        $areaPrefix = $this->ambilAreaPrefix();

        return view('superadmin/perusahaan/index', [
            'title'            => 'Data DUDI - Sistem Tracer Study & BKK',
            'areaPrefix'       => $areaPrefix,
            'dashboardUrl'     => $this->ambilDashboardUrl(),
            'pageHeading'      => 'Data DUDI',
            'breadcrumbParent' => 'Manajemen DUDI',
            'breadcrumbCurrent'=> 'Data DUDI',
            'daftar_kota'      => $this->perusahaanModel->ambilDaftarKota(),
            'daftar_kerjasama' => $this->ambilDaftarKerjasama(),
        ]);
    }

    public function simpan(): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $payload = $this->ambilPayloadPerusahaan();
        $payload['slug_perusahaan'] = $this->buildSlug($payload['nama_perusahaan']);

        if (! $this->validateData($payload, [
            'nama_perusahaan' => 'required|max_length[150]|is_unique[tb_perusahaan.nama_perusahaan]',
            'slug_perusahaan' => 'required|max_length[150]|is_unique[tb_perusahaan.slug_perusahaan]',
            'email'           => 'permit_empty|valid_email|is_unique[tb_perusahaan.email]',
            'no_telepon'      => 'permit_empty|max_length[20]',
            'kota'            => 'permit_empty|max_length[100]',
        ])) {
            return $this->jsonResponse('error', 'Data DUDI belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $errorLogo = $this->validateLogoUpload();
        if ($errorLogo !== null) {
            return $this->jsonResponse('error', $errorLogo, [], 422);
        }

        $errorKerjasama = $this->validateKerjasamaSelection($payload['id_kerjasama']);
        if ($errorKerjasama !== null) {
            return $this->jsonResponse('error', $errorKerjasama, [], 422);
        }

        $uploadedLogo = null;

        try {
            $uploadedLogo = $this->simpanLogo();

            $this->db->transStart();

            $idPerusahaan = $this->perusahaanModel->insert([
                'nama_perusahaan'   => $payload['nama_perusahaan'],
                'slug_perusahaan'   => $payload['slug_perusahaan'],
                'alamat'            => $payload['alamat'],
                'kota'              => $payload['kota'],
                'no_telepon'        => $payload['no_telepon'],
                'email'             => $payload['email'],
                'penanggung_jawab'  => $payload['penanggung_jawab'],
                'bidang_usaha'      => $payload['bidang_usaha'],
                'website'           => $payload['website'],
                'deskripsi'         => $payload['deskripsi'],
                'logo'              => $uploadedLogo,
                'status_verifikasi' => 'menunggu',
                'status_aktif'      => 1,
            ], true);

            if (! $idPerusahaan) {
                throw new RuntimeException('Data DUDI gagal disimpan.');
            }

            $this->sinkronkanKerjasamaPerusahaan((int) $idPerusahaan, $payload['id_kerjasama']);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Relasi kerjasama DUDI gagal disimpan.');
            }

            return $this->jsonResponse('success', 'Data DUDI berhasil ditambahkan.', [
                'id_perusahaan' => $idPerusahaan,
            ]);
        } catch (\Throwable $th) {
            if ($uploadedLogo !== null) {
                $this->hapusFileLokal($uploadedLogo);
            }

            return $this->jsonResponse('error', $th->getMessage(), [], 500);
        }
    }

    public function update(int $idPerusahaan): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $perusahaan = $this->perusahaanModel->ambilDetailById($idPerusahaan);
        if ($perusahaan === null) {
            return $this->jsonResponse('error', 'Data DUDI tidak ditemukan.', [], 404);
        }

        $payload = $this->ambilPayloadPerusahaan();
        $payload['slug_perusahaan'] = $this->buildSlug($payload['nama_perusahaan']);

        if (! $this->validateData($payload + ['id' => $idPerusahaan], [
            'id'              => 'permit_empty|integer',
            'nama_perusahaan' => 'required|max_length[150]|is_unique[tb_perusahaan.nama_perusahaan,id_perusahaan,{id}]',
            'slug_perusahaan' => 'required|max_length[150]|is_unique[tb_perusahaan.slug_perusahaan,id_perusahaan,{id}]',
            'email'           => 'permit_empty|valid_email|is_unique[tb_perusahaan.email,id_perusahaan,{id}]',
            'no_telepon'      => 'permit_empty|max_length[20]',
            'kota'            => 'permit_empty|max_length[100]',
        ])) {
            return $this->jsonResponse('error', 'Data DUDI belum valid.', [
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $errorLogo = $this->validateLogoUpload();
        if ($errorLogo !== null) {
            return $this->jsonResponse('error', $errorLogo, [], 422);
        }

        $errorKerjasama = $this->validateKerjasamaSelection($payload['id_kerjasama']);
        if ($errorKerjasama !== null) {
            return $this->jsonResponse('error', $errorKerjasama, [], 422);
        }

        $uploadedLogo = null;
        $logoLama = $perusahaan['logo'] ?? null;

        try {
            $uploadedLogo = $this->simpanLogo();
            $logoFinal = $logoLama;

            if ($payload['logo_remove']) {
                $logoFinal = null;
            }

            if ($uploadedLogo !== null) {
                $logoFinal = $uploadedLogo;
            }

            $this->db->transStart();

            $sukses = $this->perusahaanModel->update($idPerusahaan, [
                'nama_perusahaan'  => $payload['nama_perusahaan'],
                'slug_perusahaan'  => $payload['slug_perusahaan'],
                'alamat'           => $payload['alamat'],
                'kota'             => $payload['kota'],
                'no_telepon'       => $payload['no_telepon'],
                'email'            => $payload['email'],
                'penanggung_jawab' => $payload['penanggung_jawab'],
                'bidang_usaha'     => $payload['bidang_usaha'],
                'website'          => $payload['website'],
                'deskripsi'        => $payload['deskripsi'],
                'logo'             => $logoFinal,
            ]);

            if (! $sukses) {
                throw new RuntimeException('Data DUDI gagal diperbarui.');
            }

            $this->sinkronkanKerjasamaPerusahaan($idPerusahaan, $payload['id_kerjasama']);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Relasi kerjasama DUDI gagal diperbarui.');
            }

            if (($uploadedLogo !== null || $payload['logo_remove']) && $logoLama !== null && $logoLama !== $uploadedLogo) {
                $this->hapusFileLokal($logoLama);
            }

            return $this->jsonResponse('success', 'Data DUDI berhasil diperbarui.', [
                'id_perusahaan' => $idPerusahaan,
            ]);
        } catch (\Throwable $th) {
            if ($uploadedLogo !== null) {
                $this->hapusFileLokal($uploadedLogo);
            }

            return $this->jsonResponse('error', $th->getMessage(), [], 500);
        }
    }

    public function hapus(int $idPerusahaan): ResponseInterface
    {
        $guard = $this->guardAjaxSuperadmin();
        if ($guard !== null) {
            return $guard;
        }

        $perusahaan = $this->perusahaanModel->ambilDetailById($idPerusahaan);
        if ($perusahaan === null) {
            return $this->jsonResponse('error', 'Data DUDI tidak ditemukan.', [], 404);
        }

        $sukses = $this->perusahaanModel->update($idPerusahaan, ['status_aktif' => 0]);

        if (! $sukses) {
            return $this->jsonResponse('error', 'Data DUDI gagal dihapus.', [], 500);
        }

        return $this->jsonResponse('success', 'Data DUDI berhasil dihapus.', [
            'id_perusahaan' => $idPerusahaan,
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
            return $this->jsonResponse('error', 'Tidak ada DUDI yang dipilih.', [], 422);
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

        if ($ids === []) {
            return $this->jsonResponse('error', 'ID DUDI tidak valid.', [], 422);
        }

        $sukses = $this->perusahaanModel
            ->whereIn('id_perusahaan', $ids)
            ->set(['status_aktif' => 0])
            ->update();

        if (! $sukses) {
            return $this->jsonResponse('error', 'Data DUDI gagal dihapus.', [], 500);
        }

        return $this->jsonResponse('success', 'Data DUDI terpilih berhasil dihapus.', [
            'ids' => $ids,
        ]);
    }

    protected function ambilPayloadPerusahaan(): array
    {
        return [
            'nama_perusahaan'  => trim((string) $this->request->getPost('nama_perusahaan')),
            'alamat'           => trim((string) $this->request->getPost('alamat')) ?: null,
            'kota'             => trim((string) $this->request->getPost('kota')) ?: null,
            'no_telepon'       => trim((string) $this->request->getPost('no_telepon')) ?: null,
            'email'            => trim((string) $this->request->getPost('email')) ?: null,
            'penanggung_jawab' => trim((string) $this->request->getPost('penanggung_jawab')) ?: null,
            'bidang_usaha'     => trim((string) $this->request->getPost('bidang_usaha')) ?: null,
            'website'          => trim((string) $this->request->getPost('website')) ?: null,
            'deskripsi'        => trim((string) $this->request->getPost('deskripsi')) ?: null,
            'id_kerjasama'     => array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('id_kerjasama')), static fn (int $id): bool => $id > 0))),
            'logo_remove'      => (string) $this->request->getPost('logo_remove') === '1',
        ];
    }

    protected function buildSlug(string $namaPerusahaan): string
    {
        return url_title($namaPerusahaan, '-', true);
    }

    protected function validateLogoUpload(): ?string
    {
        $file = $this->request->getFile('logo');

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (! $file->isValid()) {
            return 'File logo tidak valid.';
        }

        if ($file->getSizeByUnit('kb') > 2048) {
            return 'Ukuran logo maksimal 2 MB.';
        }

        $ext = strtolower((string) $file->getExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return 'Format logo harus jpg, jpeg, atau png.';
        }

        return null;
    }

    protected function simpanLogo(): ?string
    {
        $file = $this->request->getFile('logo');

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (! $file->isValid() || $file->hasMoved()) {
            throw new RuntimeException('Upload logo gagal diproses.');
        }

        $targetDirectory = FCPATH . 'uploads/perusahaan';
        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            throw new RuntimeException('Direktori upload logo perusahaan tidak dapat dibuat.');
        }

        $randomName = $file->getRandomName();
        $file->move($targetDirectory, $randomName);

        return 'uploads/perusahaan/' . $randomName;
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

    protected function formatPerusahaanRow(array $row): array
    {
        $row['logo_url'] = ! empty($row['logo'])
            ? base_url((string) $row['logo'])
            : base_url('assets/media/svg/files/blank-image.svg');

        $row['kota'] = trim((string) ($row['kota'] ?? '')) ?: '-';
        $row['alamat'] = trim((string) ($row['alamat'] ?? '')) ?: '-';
        $row['no_telepon'] = trim((string) ($row['no_telepon'] ?? '')) ?: '-';
        $row['kerjasama_ids'] = array_values(array_map('intval', $row['kerjasama_ids'] ?? []));
        $row['kerjasama_nama'] = array_values(array_filter(array_map('strval', $row['kerjasama_nama'] ?? [])));
        $row['kerjasama_slug'] = array_values(array_filter(array_map('strval', $row['kerjasama_slug'] ?? [])));

        return $row;
    }

    protected function ambilDaftarKerjasama(): array
    {
        return $this->kerjasamaModel
            ->where('status_aktif', 1)
            ->orderBy('nama_kerjasama', 'ASC')
            ->findAll();
    }

    protected function validateKerjasamaSelection(array $idsKerjasama): ?string
    {
        if ($idsKerjasama === []) {
            return 'Minimal satu jenis kerjasama wajib dipilih.';
        }

        $jumlahValid = $this->db->table('tb_kerjasama')
            ->whereIn('id_kerjasama', $idsKerjasama)
            ->where('status_aktif', 1)
            ->countAllResults();

        if ($jumlahValid !== count($idsKerjasama)) {
            return 'Pilihan kerjasama tidak valid.';
        }

        return null;
    }

    protected function sinkronkanKerjasamaPerusahaan(int $idPerusahaan, array $idsKerjasama): void
    {
        if (! $this->db->tableExists('tb_perusahaan_kerjasama')) {
            return;
        }

        $this->db->table('tb_perusahaan_kerjasama')
            ->where('id_perusahaan', $idPerusahaan)
            ->delete();

        foreach ($idsKerjasama as $idKerjasama) {
            $this->db->table('tb_perusahaan_kerjasama')->insert([
                'id_perusahaan' => $idPerusahaan,
                'id_kerjasama'  => $idKerjasama,
            ]);
        }
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
    | Membuat halaman Data DUDI bisa tampil rapi di Super Admin maupun
    | Admin Sekolah/BKK dengan prefix route yang sesuai.
    |
    | Tips Debugging:
    | - Jika tombol tambah/edit DUDI 404, cek window.ktPerusahaanConfig
    |   pada view dan pastikan prefix route sesuai session.
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
