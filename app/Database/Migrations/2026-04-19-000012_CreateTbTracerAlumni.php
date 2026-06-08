<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTbTracerAlumni extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_tracer' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'auto_increment' => true,
            ],
            'id_alumni' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'id_aktivitas' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['terkirim', 'terverifikasi', 'disetujui'],
                'null'       => false,
                'default'   => 'terkirim',
            ],
            'diverifikasi_oleh' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'diverifikasi_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'disetujui_oleh' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'disetujui_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'posisi_kerja' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'nama_instansi' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'bidang_instansi' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'alamat_instansi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'tahun_mulai_kerja' => [
                'type'       => 'YEAR',
                'null'       => true,
            ],
            'relevan_jurusan' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
            ],
            'penghasilan_range' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'universitas' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'program_studi' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'status_kuliah' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'nama_usaha' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'bidang_usaha' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'modal_awal' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
            ],
            'penghasilan_usaha' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'rencana_kedepan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
            'diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_tracer', true);
        $this->forge->addUniqueKey('id_alumni', 'uk_tb_tracer_alumni_id_alumni');
        $this->forge->addForeignKey('id_alumni', 'tb_alumni', 'id_alumni', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_aktivitas', 'tb_aktivitas', 'id_aktivitas', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('diverifikasi_oleh', 'tb_pengguna', 'id_pengguna', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('disetujui_oleh', 'tb_pengguna', 'id_pengguna', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_tracer_alumni', true);
    }

    public function down()
    {
        $this->forge->dropTable('tb_tracer_alumni', true);
    }
}
