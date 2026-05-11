<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTbJenisBerkas extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tb_jenis_berkas')) {
            return;
        }

        $this->forge->addField([
            'id_jenis_berkas' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_berkas' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'slug_berkas' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'wajib' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
            ],
            'berlaku_untuk' => [
                'type'       => 'ENUM',
                'constraint' => ['semua', 'alumni', 'umum'],
                'default'    => 'semua',
                'null'       => false,
            ],
            'scope_penggunaan' => [
                'type'       => 'ENUM',
                'constraint' => ['profil', 'lamaran', 'keduanya'],
                'default'    => 'profil',
                'null'       => false,
            ],
            'boleh_multi_upload' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status_aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
            'diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_jenis_berkas', true);
        $this->forge->addUniqueKey('slug_berkas', 'uk_tb_jenis_berkas_slug');
        $this->forge->createTable('tb_jenis_berkas', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);
    }

    public function down()
    {
        $this->forge->dropTable('tb_jenis_berkas', true);
    }
}
