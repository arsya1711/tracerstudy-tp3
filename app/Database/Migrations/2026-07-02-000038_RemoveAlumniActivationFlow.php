<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveAlumniActivationFlow extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tb_alumni')) {
            return;
        }

        $this->db->table('tb_alumni')
            ->where('status_pendaftaran', 'menunggu_aktivasi')
            ->update([
                'status_pendaftaran' => 'aktif',
                'status_verifikasi'  => 'aktif',
            ]);

        if ($this->db->fieldExists('status_pendaftaran', 'tb_alumni')) {
            $this->db->query(
                "ALTER TABLE `tb_alumni` MODIFY `status_pendaftaran` ENUM('menunggu_aktivasi','aktif','terdaftar') NOT NULL DEFAULT 'aktif'"
            );
        }

        if ($this->db->fieldExists('status_verifikasi', 'tb_alumni')) {
            $this->db->query(
                "ALTER TABLE `tb_alumni` MODIFY `status_verifikasi` VARCHAR(30) NOT NULL DEFAULT 'aktif'"
            );
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('tb_alumni')) {
            return;
        }

        if ($this->db->fieldExists('status_pendaftaran', 'tb_alumni')) {
            $this->db->query(
                "ALTER TABLE `tb_alumni` MODIFY `status_pendaftaran` ENUM('menunggu_aktivasi','aktif','terdaftar') NOT NULL DEFAULT 'menunggu_aktivasi'"
            );
        }

        if ($this->db->fieldExists('status_verifikasi', 'tb_alumni')) {
            $this->db->query(
                "ALTER TABLE `tb_alumni` MODIFY `status_verifikasi` VARCHAR(30) NOT NULL DEFAULT 'menunggu_aktivasi'"
            );
        }
    }
}
