<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use App\Models\NotifikasiModel;
use App\Models\PengajuanLegalisirModel;
use CodeIgniter\HTTP\RedirectResponse;

class LegalisirController extends BaseController
{
    protected PengajuanLegalisirModel $legalisirModel;

    public function __construct()
    {
        $this->legalisirModel = new PengajuanLegalisirModel();
    }

    public function index(): string
    {
        return view('superadmin/legalisir/index', [
            'title' => 'Pengajuan Legalisir - Sistem Tracer Study',
            'pengajuan' => $this->legalisirModel->ambilLengkap(),
            'rekapStatus' => $this->legalisirModel->hitungByStatus(),
            'statusOptions' => $this->statusOptions(),
            'updateUrl' => $this->updateUrl(),
        ]);
    }

    public function updateStatus(int $id): RedirectResponse
    {
        $pengajuan = $this->legalisirModel->find($id);
        if ($pengajuan === null) {
            return redirect()->back()->with('error', 'Pengajuan legalisir tidak ditemukan.');
        }

        $status = (string) $this->request->getPost('status');
        if (! array_key_exists($status, $this->statusOptions())) {
            return redirect()->back()->with('error', 'Status pengajuan tidak valid.');
        }

        $payload = [
            'status' => $status,
            'catatan_admin' => trim((string) $this->request->getPost('catatan_admin')) ?: null,
            'diproses_oleh' => (int) session()->get('id_pengguna'),
            'diproses_pada' => date('Y-m-d H:i:s'),
            'selesai_pada' => $status === 'selesai' ? date('Y-m-d H:i:s') : null,
        ];

        $this->legalisirModel->update($id, $payload);
        $this->kirimNotifikasiAlumni((int) $pengajuan['id_alumni'], $status, (string) ($payload['catatan_admin'] ?? ''));

        return redirect()->back()->with('sukses', 'Status pengajuan legalisir berhasil diperbarui.');
    }

    /*
    | Setelah admin mengubah status legalisir, alumni menerima notifikasi.
    | Catatan admin ikut dikirim agar alasan diproses/ditolak/selesai
    | tetap terbaca dari bell notifikasi maupun halaman legalisir alumni.
    */
    protected function kirimNotifikasiAlumni(int $idAlumni, string $status, string $catatan = ''): void
    {
        $db = db_connect();
        if (! $db->tableExists('tb_alumni')) {
            return;
        }

        $alumni = $db->table('tb_alumni')
            ->select('id_pengguna')
            ->where('id_alumni', $idAlumni)
            ->get()
            ->getRowArray();

        if ($alumni === null) {
            return;
        }

        $pesan = 'Pengajuan legalisir kamu sekarang berstatus ' . ($this->statusOptions()[$status] ?? $status) . '.';
        if (trim($catatan) !== '') {
            $pesan .= ' Catatan admin: ' . trim($catatan);
        }

        (new NotifikasiModel())->buatUntukPengguna(
            [(int) $alumni['id_pengguna']],
            'legalisir_status',
            'Status legalisir diperbarui',
            $pesan,
            site_url('alumni/legalisir')
        );
    }

    protected function updateUrl(): string
    {
        return site_url('superadmin/legalisir/update-status');
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
