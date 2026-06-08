<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropTbJurusan extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tb_jurusan')) {
            return;
        }

        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('tb_jurusan', true);
        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        if ($this->db->tableExists('tb_jurusan')) {
            return;
        }

        $this->forge->addField([
            'id_jurusan' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_jurusan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'akronim' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'status_aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
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

        $this->forge->addKey('id_jurusan', true);
        $this->forge->createTable('tb_jurusan', true);
    }
}
