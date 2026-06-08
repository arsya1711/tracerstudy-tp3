<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| MIGRATION HAPUS STATUS DRAFT TRACER
|-------------------------------------------------------------------
| Migration ini merapikan alur tracer study agar data alumni tidak
| lagi memiliki status draft. Setiap isian tracer dianggap langsung
| terkirim untuk ditinjau oleh Admin Sekolah.
|
| Alur kerja:
| 1. Data lama dengan status draft dikonversi menjadi terkirim.
| 2. Struktur ENUM status di tb_tracer_alumni diubah tanpa draft.
| 3. Default status baru menjadi terkirim.
|
| Tips Debugging:
| - Jika ALTER TABLE gagal, cek apakah tabel tb_tracer_alumni sudah ada.
| - Jika masih ada status draft, pastikan migration ini sudah dijalankan.
*/
class RemoveDraftFromTracerStatus extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('tb_tracer_alumni') || ! $this->db->fieldExists('status', 'tb_tracer_alumni')) {
            return;
        }

        $this->db->table('tb_tracer_alumni')
            ->where('status', 'draft')
            ->update(['status' => 'terkirim']);

        $this->db->query(
            "ALTER TABLE `tb_tracer_alumni`
             MODIFY `status` ENUM('terkirim','terverifikasi','disetujui')
             NOT NULL DEFAULT 'terkirim'"
        );
    }

    public function down(): void
    {
        if (! $this->db->tableExists('tb_tracer_alumni') || ! $this->db->fieldExists('status', 'tb_tracer_alumni')) {
            return;
        }

        $this->db->query(
            "ALTER TABLE `tb_tracer_alumni`
             MODIFY `status` ENUM('draft','terkirim','terverifikasi','disetujui')
             NOT NULL DEFAULT 'draft'"
        );
    }
}
