<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| MIGRATION TB_PERUSAHAAN_KERJASAMA
|-------------------------------------------------------------------
| Tabel pivot ini menyimpan relasi banyak-ke-banyak antara DUDI dan
| jenis kerjasama aktif yang sudah disepakati dengan sekolah.
*/
class CreateTbPerusahaanKerjasama extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tb_perusahaan_kerjasama')) {
            return;
        }

        $this->forge->addField([
            'id_perusahaan_kerjasama' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_perusahaan' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'id_kerjasama' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'dibuat_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'diperbarui_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id_perusahaan_kerjasama', true);
        $this->forge->addUniqueKey(['id_perusahaan', 'id_kerjasama'], 'uk_tb_perusahaan_kerjasama_relasi');
        $this->forge->addForeignKey('id_perusahaan', 'tb_perusahaan', 'id_perusahaan', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_kerjasama', 'tb_kerjasama', 'id_kerjasama', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_perusahaan_kerjasama', true, [
            'ENGINE'  => 'InnoDB',
            'COMMENT' => 'Pivot relasi DUDI dengan jenis kerjasama',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('tb_perusahaan_kerjasama', true);
    }
}
