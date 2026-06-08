<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropRemainingRecruitmentTables extends Migration
{
    public function up(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        foreach ([
            'tb_lamaran_berkas',
            'tb_lamaran_status',
            'tb_lamaran',
            'tb_lowongan',
            'tb_perusahaan_kerjasama',
            'tb_perusahaan',
            'tb_kerjasama',
            'tb_jenis_berkas',
            'tb_berkas',
            'tb_riwayat_kerja',
            'tb_counter_pelamar',
            'tb_pelamar',
        ] as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        if ($this->db->tableExists('tb_peran')) {
            $this->db->table('tb_peran')
                ->whereIn('slug_peran', ['admin_dudi', 'admin_perusahaan', 'pelamar_umum'])
                ->delete();

            $this->db->table('tb_peran')
                ->where('slug_peran', 'pelamar_alumni')
                ->update([
                    'nama_peran' => 'Alumni',
                    'slug_peran' => 'alumni',
                    'keterangan' => 'Akun alumni untuk mengisi profil dan tracer study',
                ]);
        }
    }

    public function down(): void
    {
        // Tabel rekrutmen lama tidak dibuat ulang.
    }
}
