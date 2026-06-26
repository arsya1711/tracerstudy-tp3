<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeTracerStudyTermsAndAlumniStatus extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tb_alumni')) {
            $this->db->query(
                "ALTER TABLE `tb_alumni` MODIFY `status_pendaftaran` ENUM('menunggu_aktivasi','aktif','terdaftar') NOT NULL DEFAULT 'menunggu_aktivasi'"
            );

            $this->db->table('tb_alumni')
                ->where('status_verifikasi', 'menunggu_aktivasi')
                ->where('status_pendaftaran', 'aktif')
                ->update(['status_pendaftaran' => 'menunggu_aktivasi']);
        }

        if ($this->db->tableExists('tb_aktivitas')) {
            $waktuSekarang = date('Y-m-d H:i:s');
            $aktivitasMencariKerja = $this->db->table('tb_aktivitas')
                ->select('id_aktivitas')
                ->where('nama_aktivitas', 'Mencari Kerja')
                ->get()
                ->getRowArray();
            $aktivitasBelumBekerja = $this->db->table('tb_aktivitas')
                ->select('id_aktivitas')
                ->where('nama_aktivitas', 'Belum Bekerja')
                ->get()
                ->getRowArray();

            if ($aktivitasBelumBekerja !== null && $aktivitasMencariKerja === null) {
                $this->db->table('tb_aktivitas')
                    ->where('id_aktivitas', (int) $aktivitasBelumBekerja['id_aktivitas'])
                    ->update([
                    'nama_aktivitas'  => 'Mencari Kerja',
                    'keterangan'      => 'Alumni sedang mencari peluang kerja setelah lulus.',
                    'diperbarui_pada' => $waktuSekarang,
                    ]);
            } elseif ($aktivitasBelumBekerja !== null && $aktivitasMencariKerja !== null) {
                if ($this->db->tableExists('tb_tracer_alumni')) {
                    $this->db->table('tb_tracer_alumni')
                        ->where('id_aktivitas', (int) $aktivitasBelumBekerja['id_aktivitas'])
                        ->update(['id_aktivitas' => (int) $aktivitasMencariKerja['id_aktivitas']]);
                }

                $this->db->table('tb_aktivitas')
                    ->where('id_aktivitas', (int) $aktivitasBelumBekerja['id_aktivitas'])
                    ->update([
                        'status_aktif'    => 0,
                        'diperbarui_pada' => $waktuSekarang,
                    ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tb_alumni')) {
            $this->db->query(
                "ALTER TABLE `tb_alumni` MODIFY `status_pendaftaran` ENUM('menunggu_aktivasi','aktif','terdaftar') NOT NULL DEFAULT 'aktif'"
            );
        }
    }
}
