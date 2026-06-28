<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CleanupLegacyJurusanFromAlumni extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tb_alumni')) {
            return;
        }

        $this->db->disableForeignKeyChecks();

        if ($this->foreignKeyExists('tb_alumni', 'fk_tb_alumni_jurusan')) {
            $this->db->query('ALTER TABLE `tb_alumni` DROP FOREIGN KEY `fk_tb_alumni_jurusan`');
        }

        if ($this->indexExists('tb_alumni', 'idx_tb_alumni_id_jurusan')) {
            $this->db->query('ALTER TABLE `tb_alumni` DROP INDEX `idx_tb_alumni_id_jurusan`');
        }

        if ($this->db->fieldExists('id_jurusan', 'tb_alumni')) {
            $this->forge->dropColumn('tb_alumni', 'id_jurusan');
        }

        if ($this->db->fieldExists('id_kompetensi', 'tb_alumni')) {
            if (! $this->indexOnColumnExists('tb_alumni', 'id_kompetensi')) {
                $this->db->query('ALTER TABLE `tb_alumni` ADD INDEX `idx_tb_alumni_id_kompetensi` (`id_kompetensi`)');
            }

            if (
                $this->db->tableExists('tb_kompetensi')
                && ! $this->foreignKeyOnColumnExists('tb_alumni', 'id_kompetensi')
            ) {
                $this->db->query(
                    'ALTER TABLE `tb_alumni` ADD CONSTRAINT `fk_tb_alumni_kompetensi` '
                    . 'FOREIGN KEY (`id_kompetensi`) REFERENCES `tb_kompetensi` (`id_kompetensi`) '
                    . 'ON DELETE SET NULL ON UPDATE CASCADE'
                );
            }
        }

        if ($this->db->tableExists('tb_jurusan')) {
            $this->forge->dropTable('tb_jurusan', true);
        }

        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        if (! $this->db->tableExists('tb_alumni') || $this->db->fieldExists('id_jurusan', 'tb_alumni')) {
            return;
        }

        $this->forge->addColumn('tb_alumni', [
            'id_jurusan' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'id_kompetensi',
            ],
        ]);

        if (! $this->indexExists('tb_alumni', 'idx_tb_alumni_id_jurusan')) {
            $this->db->query('ALTER TABLE `tb_alumni` ADD INDEX `idx_tb_alumni_id_jurusan` (`id_jurusan`)');
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS total
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$table, $constraint, 'FOREIGN KEY']
        )->getRowArray();

        return (int) ($row['total'] ?? 0) > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS total
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?',
            [$table, $index]
        )->getRowArray();

        return (int) ($row['total'] ?? 0) > 0;
    }

    private function indexOnColumnExists(string $table, string $column): bool
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS total
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            [$table, $column]
        )->getRowArray();

        return (int) ($row['total'] ?? 0) > 0;
    }

    private function foreignKeyOnColumnExists(string $table, string $column): bool
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS total
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $column]
        )->getRowArray();

        return (int) ($row['total'] ?? 0) > 0;
    }
}
