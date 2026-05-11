<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| MIGRATION TB_PERUSAHAAN
|-------------------------------------------------------------------
| Migration ini membuat tabel tb_perusahaan sebagai master data DUDI
| mitra sekolah. Tabel ini dipakai untuk modul Data DUDI, relasi akun
| admin DUDI, dan referensi modul kerjasama/lowongan di tahap berikut.
*/
class CreateTbPerusahaan extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tb_perusahaan')) {
            return;
        }

        $this->forge->addField([
            'id_perusahaan' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pengguna' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK ke akun admin_dudi di tb_pengguna',
            ],
            'nama_perusahaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'slug_perusahaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'bidang_usaha' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'deskripsi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'alamat' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'kota' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'penanggung_jawab' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'no_telepon' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'website' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'logo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status_verifikasi' => [
                'type'       => 'ENUM',
                'constraint' => ['menunggu', 'terverifikasi', 'ditolak'],
                'default'    => 'menunggu',
            ],
            'status_aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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

        $this->forge->addKey('id_perusahaan', true);
        $this->forge->addUniqueKey('slug_perusahaan', 'uk_tb_perusahaan_slug_perusahaan');
        $this->forge->addUniqueKey('nama_perusahaan', 'uk_tb_perusahaan_nama_perusahaan');
        $this->forge->addUniqueKey('email', 'uk_tb_perusahaan_email');
        $this->forge->addUniqueKey('id_pengguna', 'uk_tb_perusahaan_id_pengguna');
        $this->forge->addKey('kota');
        $this->forge->addForeignKey('id_pengguna', 'tb_pengguna', 'id_pengguna', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tb_perusahaan', true, [
            'ENGINE'  => 'InnoDB',
            'COMMENT' => 'Data perusahaan/DUDI mitra sekolah',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('tb_perusahaan', true);
    }
}
