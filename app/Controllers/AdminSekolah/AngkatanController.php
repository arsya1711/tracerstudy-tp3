<?php

namespace App\Controllers\AdminSekolah;

use App\Controllers\Superadmin\AngkatanController as SuperadminAngkatanController;
use CodeIgniter\HTTP\RedirectResponse;

/*
|-------------------------------------------------------------------
| CONTROLLER ANGKATAN ADMIN SEKOLAH
|-------------------------------------------------------------------
| Controller ini membuka modul Angkatan untuk Admin Sekolah dengan
| memakai logika simpan, update, dan hapus dari controller Super Admin.
|
| Alur kerja:
| 1. Admin Sekolah membuka admin-sekolah/angkatan.
| 2. Data angkatan diambil dari model yang sama.
| 3. Endpoint AJAX diarahkan ke prefix admin-sekolah agar sidebar dan
|    URL tetap sesuai konteks admin sekolah.
|
| Tips Debugging:
| - Jika AJAX 403, pastikan session slug_peran bernilai admin_sekolah.
| - Jika tombol simpan mengarah ke superadmin, cek config URL di view.
*/
class AngkatanController extends SuperadminAngkatanController
{
    public function index(): string|RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        return view('superadmin/angkatan/index', [
            'title' => 'Angkatan - Admin Sekolah',
            'angkatan' => $this->angkatanModel->ambilSemuaDenganJumlahSiswa(),
            'dashboardUrl' => base_url('admin-sekolah/dashboard'),
            'indexUrl' => site_url('admin-sekolah/angkatan'),
            'simpanUrl' => site_url('admin-sekolah/angkatan/simpan'),
            'updateUrl' => site_url('admin-sekolah/angkatan/update'),
            'hapusUrl' => site_url('admin-sekolah/angkatan/hapus'),
        ]);
    }

    protected function isSuperadmin(): bool
    {
        return session()->get('slug_peran') === 'admin_sekolah';
    }
}
