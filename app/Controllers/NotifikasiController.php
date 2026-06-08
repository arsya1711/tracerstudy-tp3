<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NotifikasiModel;
use CodeIgniter\HTTP\RedirectResponse;

/*
|-------------------------------------------------------------------
| CONTROLLER NOTIFIKASI
|-------------------------------------------------------------------
| Controller ini menangani klik notifikasi dari bell header dashboard.
| Notifikasi akan ditandai dibaca lebih dulu, lalu user diarahkan ke
| target_url yang tersimpan pada notifikasi tersebut.
|
| Alur kerja:
| 1. User menekan salah satu item notifikasi.
| 2. Controller memastikan notifikasi milik user login.
| 3. Sistem menandai notifikasi sebagai dibaca.
| 4. User diarahkan ke target halaman terkait.
|
| Tips Debugging:
| - Jika notifikasi tidak berubah dibaca, cek id_pengguna session.
| - Jika redirect salah, cek nilai target_url pada tb_notifikasi.
*/
class NotifikasiController extends BaseController
{
    protected NotifikasiModel $notifikasiModel;

    public function __construct()
    {
        $this->notifikasiModel = new NotifikasiModel();
    }

    public function buka(int $idNotifikasi): RedirectResponse
    {
        $idPengguna = (int) session()->get('id_pengguna');
        $notifikasi = $this->notifikasiModel->tandaiDibaca($idNotifikasi, $idPengguna);

        if ($notifikasi === null) {
            return redirect()->to($this->dashboardUrl())->with('error', 'Notifikasi tidak ditemukan.');
        }

        $targetUrl = trim((string) ($notifikasi['target_url'] ?? ''));

        if ($targetUrl === '' || preg_match('#^https?://#i', $targetUrl) === 1) {
            return redirect()->to($this->dashboardUrl());
        }

        return redirect()->to(site_url(ltrim($targetUrl, '/')));
    }

    protected function dashboardUrl(): string
    {
        return match ((string) session()->get('slug_peran')) {
            'superadmin' => site_url('dashboard/superadmin'),
            'admin_sekolah' => site_url('admin-sekolah/dashboard'),
            'alumni' => site_url('alumni/dashboard'),
            default => site_url('login'),
        };
    }
}
