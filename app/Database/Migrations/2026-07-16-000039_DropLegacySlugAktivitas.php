<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Removes the unused legacy activity slug and its implicit unique constraint.
 */
class DropLegacySlugAktivitas extends Migration
{
    public function up()
    {
        if (
            ! $this->db->tableExists('tb_aktivitas')
            || ! $this->db->fieldExists('slug_aktivitas', 'tb_aktivitas')
        ) {
            return;
        }

        // MySQL removes indexes that only reference a dropped column.
        $this->db->query('ALTER TABLE `tb_aktivitas` DROP COLUMN `slug_aktivitas`');
        $this->db->resetDataCache();
    }

    public function down()
    {
        if (
            ! $this->db->tableExists('tb_aktivitas')
            || $this->db->fieldExists('slug_aktivitas', 'tb_aktivitas')
        ) {
            return;
        }

        $this->db->query(
            'ALTER TABLE `tb_aktivitas` '
            . 'ADD COLUMN `slug_aktivitas` VARCHAR(100) NULL AFTER `nama_aktivitas`'
        );
        $this->db->query(
            "UPDATE `tb_aktivitas` SET `slug_aktivitas` = CONCAT('aktivitas-', `id_aktivitas`)"
        );
        $this->db->query(
            'ALTER TABLE `tb_aktivitas` MODIFY `slug_aktivitas` VARCHAR(100) NOT NULL'
        );
        $this->db->query(
            'ALTER TABLE `tb_aktivitas` '
            . 'ADD UNIQUE KEY `uk_tb_aktivitas_slug_aktivitas` (`slug_aktivitas`)'
        );
        $this->db->resetDataCache();
    }
}
