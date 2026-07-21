<?php

namespace App\Controllers\AdminSekolah;

use App\Controllers\Superadmin\LegalisirController as SuperadminLegalisirController;

class LegalisirController extends SuperadminLegalisirController
{
    protected function updateUrl(): string
    {
        return site_url('admin-sekolah/legalisir/update-status');
    }

    protected function deleteUrl(): string
    {
        return site_url('admin-sekolah/legalisir/hapus');
    }
}
