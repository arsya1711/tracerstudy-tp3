<?php

namespace App\Controllers\AdminSekolah;

use App\Controllers\Superadmin\TracerController as SuperadminTracerController;

/*
|-------------------------------------------------------------------
| CONTROLLER DATA TRACER ADMIN SEKOLAH
|-------------------------------------------------------------------
| Controller ini memakai ulang logika laporan tracer Super Admin,
| tetapi membatasi aksesnya untuk role Admin Sekolah.
|
| Alur kerja:
| 1. Admin Sekolah membuka menu Data Tracer Alumni.
| 2. Method index() dari parent controller menjalankan query tracer.
| 3. Method helper di bawah menyesuaikan URL dashboard dan URL filter
|    agar halaman tetap berada di area admin-sekolah.
|
| Tips Debugging:
| - Jika admin sekolah dilempar ke login, periksa session slug_peran
|   harus bernilai admin_sekolah.
| - Jika filter kembali ke URL superadmin, periksa getTracerBaseUrl().
*/
class TracerController extends SuperadminTracerController
{
    protected function isSuperadmin(): bool
    {
        return session()->get('slug_peran') === 'admin_sekolah';
    }

    protected function getPageTitle(): string
    {
        return 'Data Tracer Alumni - Sistem Tracer Study';
    }

    protected function getDashboardUrl(): string
    {
        return site_url('admin-sekolah/dashboard');
    }

    protected function getTracerBaseUrl(): string
    {
        return site_url('admin-sekolah/tracer');
    }

    protected function getTracerRoleLabel(): string
    {
        return 'Admin Sekolah';
    }
}
