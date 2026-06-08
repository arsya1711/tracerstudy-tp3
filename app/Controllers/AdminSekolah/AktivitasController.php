<?php

namespace App\Controllers\AdminSekolah;

use App\Controllers\Superadmin\AktivitasController as SuperadminAktivitasController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/*
|-------------------------------------------------------------------
| CONTROLLER AKTIVITAS ADMIN SEKOLAH
|-------------------------------------------------------------------
| Controller ini membuka master kegiatan/aktivitas alumni untuk Admin
| Sekolah. Data ini dipakai langsung oleh tracer study.
|
| Alur kerja:
| 1. Admin Sekolah membuka admin-sekolah/aktivitas.
| 2. Request biasa menampilkan view, request AJAX mengambil data.
| 3. Endpoint tambah/edit/hapus tetap memakai logika parent controller.
|
| Tips Debugging:
| - Jika tabel aktivitas kosong, cek response AJAX admin-sekolah/aktivitas.
| - Jika akses ditolak, pastikan session slug_peran adalah admin_sekolah.
*/
class AktivitasController extends SuperadminAktivitasController
{
    public function index(): string|RedirectResponse|ResponseInterface
    {
        if (! $this->isSuperadmin()) {
            if ($this->request->isAJAX()) {
                return $this->jsonResponse('error', 'Akses ditolak.', [], 403);
            }

            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        if ($this->request->isAJAX()) {
            return $this->jsonResponse('success', 'Data aktivitas berhasil dimuat.', [
                'data' => $this->aktivitasModel->ambilSemuaDenganJumlahAlumni(),
            ]);
        }

        return view('superadmin/aktivitas/index', [
            'title' => 'Aktivitas Alumni - Admin Sekolah',
            'dashboardUrl' => base_url('admin-sekolah/dashboard'),
            'indexUrl' => site_url('admin-sekolah/aktivitas'),
            'simpanUrl' => site_url('admin-sekolah/aktivitas/simpan'),
            'updateUrl' => site_url('admin-sekolah/aktivitas/update'),
            'hapusUrl' => site_url('admin-sekolah/aktivitas/hapus'),
        ]);
    }

    protected function isSuperadmin(): bool
    {
        return session()->get('slug_peran') === 'admin_sekolah';
    }
}
