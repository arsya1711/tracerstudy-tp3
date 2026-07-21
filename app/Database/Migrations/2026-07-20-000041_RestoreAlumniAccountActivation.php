<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Mengembalikan status awal alumni ke menunggu aktivasi.
 * Data alumni lama tidak diubah; aturan ini berlaku untuk pendaftaran baru.
 */
class RestoreAlumniAccountActivation extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tb_alumni')) {
            return;
        }

        if ($this->db->fieldExists('status_pendaftaran', 'tb_alumni')) {
            $this->db->query(
                "ALTER TABLE `tb_alumni` MODIFY `status_pendaftaran` "
                . "ENUM('menunggu_aktivasi','aktif','terdaftar') NOT NULL DEFAULT 'menunggu_aktivasi'"
            );
        }

        if ($this->db->fieldExists('status_verifikasi', 'tb_alumni')) {
            $this->db->query(
                "ALTER TABLE `tb_alumni` MODIFY `status_verifikasi` "
                . "VARCHAR(30) NOT NULL DEFAULT 'menunggu_aktivasi'"
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
                "ALTER TABLE `tb_alumni` MODIFY `status_pendaftaran` "
                . "ENUM('menunggu_aktivasi','aktif','terdaftar') NOT NULL DEFAULT 'aktif'"
            );
        }

        if ($this->db->fieldExists('status_verifikasi', 'tb_alumni')) {
            $this->db->query(
                "ALTER TABLE `tb_alumni` MODIFY `status_verifikasi` VARCHAR(30) NOT NULL DEFAULT 'aktif'"
            );
        }
    }
}
