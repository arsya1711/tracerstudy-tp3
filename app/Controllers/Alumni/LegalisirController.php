<?php

namespace App\Controllers\Alumni;

use App\Controllers\BaseController;
use App\Models\AlumniModel;
use App\Models\NotifikasiModel;
use App\Models\PengajuanLegalisirModel;
use CodeIgniter\HTTP\RedirectResponse;

class LegalisirController extends BaseController
{
    protected AlumniModel $alumniModel;
    protected PengajuanLegalisirModel $legalisirModel;

    public function __construct()
    {
        $this->alumniModel = new AlumniModel();
        $this->legalisirModel = new PengajuanLegalisirModel();
    }

    public function index(): string|RedirectResponse
    {
        $alumni = $this->ambilAlumniLogin();
        if ($alumni === null) {
            return redirect()->to(site_url('logout'))->with('error', 'Profil alumni belum ditemukan.');
        }

        return view('alumni/legalisir/index', [
            'title' => 'Pengajuan Legalisir - Sistem Tracer Study',
            'alumni' => $alumni,
            'pengajuan' => $this->legalisirModel->ambilLengkap((int) $alumni['id_alumni']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function simpan(): RedirectResponse
    {
        $alumni = $this->ambilAlumniLogin();
        if ($alumni === null) {
            return redirect()->to(site_url('logout'))->with('error', 'Profil alumni belum ditemukan.');
        }

        $rules = [
            'jenis_dokumen' => 'required|max_length[100]',
            'jumlah_lembar' => 'required|integer|greater_than[0]',
            'keperluan' => 'permit_empty|max_length[1000]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Data pengajuan legalisir belum lengkap.');
        }

        $this->legalisirModel->insert([
            'id_alumni' => (int) $alumni['id_alumni'],
            'jenis_dokumen' => trim((string) $this->request->getPost('jenis_dokumen')),
            'jumlah_lembar' => (int) $this->request->getPost('jumlah_lembar'),
            'keperluan' => trim((string) $this->request->getPost('keperluan')) ?: null,
            'status' => 'diajukan',
        ]);

        $this->kirimNotifikasiAdmin((string) ($alumni['nama_lengkap'] ?? 'Alumni'));

        return redirect()->to(site_url('alumni/legalisir'))->with('sukses', 'Pengajuan legalisir berhasil dikirim.');
    }

    protected function ambilAlumniLogin(): ?array
    {
        if ((string) session()->get('slug_peran') !== 'alumni') {
            return null;
        }

        return $this->alumniModel->ambilLengkapByPengguna((int) session()->get('id_pengguna'));
    }

    protected function kirimNotifikasiAdmin(string $namaAlumni): void
    {
        $db = db_connect();
        if (! $db->tableExists('tb_pengguna') || ! $db->tableExists('tb_peran')) {
            return;
        }

        $adminRows = $db->table('tb_pengguna u')
            ->select('u.id_pengguna, p.slug_peran')
            ->join('tb_peran p', 'p.id_peran = u.id_peran', 'inner')
            ->whereIn('p.slug_peran', ['superadmin', 'admin_sekolah'])
            ->where('u.status_aktif', 1)
            ->get()
            ->getResultArray();

        $superadminIds = [];
        $adminSekolahIds = [];

        foreach ($adminRows as $row) {
            if (($row['slug_peran'] ?? '') === 'superadmin') {
                $superadminIds[] = (int) $row['id_pengguna'];
            } elseif (($row['slug_peran'] ?? '') === 'admin_sekolah') {
                $adminSekolahIds[] = (int) $row['id_pengguna'];
            }
        }

        $notifikasi = new NotifikasiModel();
        $notifikasi->buatUntukPengguna(
            $superadminIds,
            'legalisir_baru',
            'Pengajuan legalisir baru',
            $namaAlumni . ' mengajukan legalisir dokumen.',
            site_url('superadmin/legalisir')
        );
        $notifikasi->buatUntukPengguna(
            $adminSekolahIds,
            'legalisir_baru',
            'Pengajuan legalisir baru',
            $namaAlumni . ' mengajukan legalisir dokumen.',
            site_url('admin-sekolah/legalisir')
        );
    }

    protected function statusOptions(): array
    {
        return [
            'diajukan' => 'Diajukan',
            'diproses' => 'Diproses',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ];
    }
}
