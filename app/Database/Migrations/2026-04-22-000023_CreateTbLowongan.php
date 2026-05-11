<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| MIGRATION TB_LOWONGAN
|-------------------------------------------------------------------
| Migration ini membuat tabel tb_lowongan sebagai master transaksi
| posting lowongan kerja dari DUDI yang sudah memiliki kerjasama
| rekrutmen dengan sekolah.
*/
class CreateTbLowongan extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tb_lowongan')) {
            return;
        }

        $this->forge->addField([
            'id_lowongan' => [
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
            'dibuat_oleh' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'comment'    => 'id_pengguna Admin DUDI/Sekolah',
            ],
            'judul_lowongan' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'posisi' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'slug_lowongan' => [
                'type'       => 'VARCHAR',
                'constraint' => 170,
            ],
            'flyer_lowongan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Path gambar/flyer lowongan',
            ],
            'deskripsi_pekerjaan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'kualifikasi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'jumlah_kebutuhan' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
                'null'       => true,
            ],
            'jenis_pekerjaan' => [
                'type'       => 'ENUM',
                'constraint' => ['fulltime', 'parttime', 'magang', 'kontrak', 'freelance'],
                'default'    => 'fulltime',
            ],
            'sistem_kerja' => [
                'type'       => 'ENUM',
                'constraint' => ['onsite', 'remote', 'hybrid'],
                'default'    => 'onsite',
            ],
            'pendidikan_min' => [
                'type'       => 'ENUM',
                'constraint' => ['SMP', 'SMA/SMK', 'D3', 'S1', 'S2'],
                'null'       => true,
            ],
            'pengalaman_min' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'rentang_gaji' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'lokasi_kerja' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'batas_lamaran' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'tayang_hingga' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Auto unpublish dari web',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'aktif', 'ditutup', 'kadaluarsa'],
                'default'    => 'draft',
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

        $this->forge->addKey('id_lowongan', true);
        $this->forge->addUniqueKey('slug_lowongan', 'uk_tb_lowongan_slug_lowongan');
        $this->forge->addKey(['status', 'batas_lamaran'], false, false, 'idx_tb_lowongan_status_batas');
        $this->forge->addKey('id_perusahaan');
        $this->forge->addKey(['id_perusahaan', 'status'], false, false, 'idx_tb_lowongan_perusahaan_status');
        $this->forge->addForeignKey('id_perusahaan', 'tb_perusahaan', 'id_perusahaan', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('dibuat_oleh', 'tb_pengguna', 'id_pengguna', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('tb_lowongan', true, [
            'ENGINE'  => 'InnoDB',
            'COMMENT' => 'Data lowongan kerja dari DUDI',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('tb_lowongan', true);
    }
}
