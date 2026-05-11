<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTbBerkas extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tb_berkas')) {
            return;
        }

        $this->forge->addField([
            'id_berkas' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pelamar' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'id_jenis_berkas' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'nama_file' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'path_file' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'ukuran_file' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'tipe_mime' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'status_unggah' => [
                'type'       => 'ENUM',
                'constraint' => ['belum_diunggah', 'sudah_diunggah', 'ditolak'],
                'default'    => 'belum_diunggah',
                'null'       => false,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
            'diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_berkas', true);
        $this->forge->addUniqueKey(['id_pelamar', 'id_jenis_berkas'], 'uk_tb_berkas_pelamar_jenis');
        $this->forge->addKey('status_unggah');
        $this->forge->addForeignKey('id_pelamar', 'tb_pelamar', 'id_pelamar', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_jenis_berkas', 'tb_jenis_berkas', 'id_jenis_berkas', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('tb_berkas', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);
    }

    public function down()
    {
        $this->forge->dropTable('tb_berkas', true);
    }
}
