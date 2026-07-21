<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menyimpan penanda migration data presentasi tanpa memuat data alumni otomatis.
 * Data demo kini bersifat opsional dan hanya boleh dijalankan manual melalui
 * `php spark db:seed SidangSeeder` pada environment non-production.
 */
class LoadSidangDemoData extends Migration
{
    public function up()
    {
        // Sengaja dikosongkan agar fresh migration menghasilkan database
        // tanpa akun alumni, tracer, legalisir, dan notifikasi data demo.
    }

    public function down()
    {
        if (ENVIRONMENT === 'production' || ! $this->db->tableExists('tb_pengguna')) {
            return;
        }

        $nisDemo = array_map(
            static fn (int $nomor): string => sprintf('1920%04d', $nomor),
            range(1, 24)
        );
        $idPenggunaAlumni = [];

        if ($this->db->tableExists('tb_alumni')) {
            $rows = $this->db->table('tb_alumni')
                ->select('id_pengguna')
                ->whereIn('nis', $nisDemo)
                ->get()
                ->getResultArray();
            $idPenggunaAlumni = array_map(
                static fn (array $row): int => (int) $row['id_pengguna'],
                $rows
            );
        }

        $guru = $this->db->table('tb_pengguna')
            ->select('id_pengguna')
            ->whereIn('email', [
                'rina.wulandari.skom@gmail.com',
                'guru.demo@demo.tracer.test',
            ])
            ->get()
            ->getRowArray();

        $this->db->transStart();

        if ($idPenggunaAlumni !== []) {
            // Relasi alumni, tracer, legalisir, dan notifikasi ikut terhapus melalui FK cascade.
            $this->db->table('tb_pengguna')
                ->whereIn('id_pengguna', $idPenggunaAlumni)
                ->delete();
        }

        if ($guru !== null && ! $this->guruDipakaiDataLain((int) $guru['id_pengguna'])) {
            $this->db->table('tb_pengguna')
                ->where('id_pengguna', (int) $guru['id_pengguna'])
                ->delete();
        }

        $this->db->transComplete();
    }

    /**
     * Akun guru dipertahankan saat rollback bila sudah dipakai untuk memproses
     * data non-demo, sehingga rollback tidak merusak jejak data pengguna.
     */
    private function guruDipakaiDataLain(int $idPengguna): bool
    {
        $references = [
            ['tb_alumni', 'diverifikasi_oleh'],
            ['tb_tracer_alumni', 'diverifikasi_oleh'],
            ['tb_tracer_alumni', 'disetujui_oleh'],
            ['tb_pengajuan_legalisir', 'diproses_oleh'],
        ];

        foreach ($references as [$table, $column]) {
            if (
                $this->db->tableExists($table)
                && $this->db->fieldExists($column, $table)
                && $this->db->table($table)->where($column, $idPengguna)->countAllResults() > 0
            ) {
                return true;
            }
        }

        return false;
    }
}
