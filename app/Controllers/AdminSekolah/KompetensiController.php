<?php

namespace App\Controllers\AdminSekolah;

use App\Controllers\Superadmin\KompetensiController as SuperadminKompetensiController;
use CodeIgniter\HTTP\RedirectResponse;

/*
|-------------------------------------------------------------------
| CONTROLLER KOMPETENSI ADMIN SEKOLAH
|-------------------------------------------------------------------
| Controller ini membuka pengelolaan kompetensi keahlian untuk Admin
| Sekolah. Data kompetensi termasuk master sekolah, sehingga role
| admin sekolah mengelolanya.
|
| Alur kerja:
| 1. Admin Sekolah membuka admin-sekolah/kompetensi.
| 2. View yang sama dengan Super Admin dipakai ulang.
| 3. URL AJAX diarahkan ke prefix admin-sekolah.
|
| Tips Debugging:
| - Jika data tidak tersimpan, cek route admin-sekolah/kompetensi/simpan.
| - Jika modal edit gagal, cek updateUrl pada window.ktKompetensiConfig.
*/
class KompetensiController extends SuperadminKompetensiController
{
    public function index(): string|RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        return view('superadmin/kompetensi/index', [
            'title' => 'Kompetensi Keahlian - Admin Sekolah',
            'kompetensi' => $this->kompetensiModel->ambilSemuaDenganKeterserapan(),
            'dashboardUrl' => base_url('admin-sekolah/dashboard'),
            'indexUrl' => site_url('admin-sekolah/kompetensi'),
            'simpanUrl' => site_url('admin-sekolah/kompetensi/simpan'),
            'updateUrl' => site_url('admin-sekolah/kompetensi/update'),
            'hapusUrl' => site_url('admin-sekolah/kompetensi/hapus'),
        ]);
    }

    protected function isSuperadmin(): bool
    {
        return session()->get('slug_peran') === 'admin_sekolah';
    }
}
