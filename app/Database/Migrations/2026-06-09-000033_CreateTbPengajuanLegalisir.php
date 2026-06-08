<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTbPengajuanLegalisir extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tb_pengajuan_legalisir')) {
            return;
        }

        $this->forge->addField([
            'id_pengajuan_legalisir' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_alumni' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'jenis_dokumen' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'jumlah_lembar' => [
                'type'       => 'INT',
                'constraint' => 4,
                'unsigned'   => true,
                'default'    => 1,
                'null'       => false,
            ],
            'keperluan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['diajukan', 'diproses', 'selesai', 'ditolak'],
                'default'    => 'diajukan',
                'null'       => false,
            ],
            'catatan_admin' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'diproses_oleh' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'diproses_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'selesai_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
            'diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_pengajuan_legalisir', true);
        $this->forge->addKey(['id_alumni', 'status'], false, false, 'idx_legalisir_alumni_status');
        $this->forge->addKey('diproses_oleh');
        $this->forge->addForeignKey('id_alumni', 'tb_alumni', 'id_alumni', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('diproses_oleh', 'tb_pengguna', 'id_pengguna', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tb_pengajuan_legalisir', true);
    }

    public function down()
    {
        $this->forge->dropTable('tb_pengajuan_legalisir', true);
    }
}
