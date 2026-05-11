<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| MIGRATION TB_LAMARAN
|-------------------------------------------------------------------
| Migration ini membuat tabel transaksi lamaran kerja yang menjadi
| inti proses bisnis modul BKK di sisi pelamar.
|
| Alur kerja:
| 1. Pelamar memilih lowongan aktif.
| 2. Sistem membuat satu baris transaksi di tb_lamaran.
| 3. Status terkini lamaran disimpan di tabel ini dan histori detail
|    akan dicatat di tb_lamaran_status.
|
| Tips Debugging:
| - Jika pelamar bisa melamar dua kali ke lowongan yang sama, cek
|   unique key kombinasi id_pelamar dan id_lowongan.
*/
class CreateTbLamaran extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tb_lamaran')) {
            return;
        }

        $this->forge->addField([
            'id_lamaran' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pelamar' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'id_lowongan' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'dibuat_oleh' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'comment'    => 'id_pengguna pelamar yang membuat lamaran',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'menunggu_verifikasi',
                    'perlu_perbaikan_berkas',
                    'diproses',
                    'wawancara',
                    'diterima',
                    'ditolak',
                    'mengundurkan_diri',
                ],
                'default' => 'menunggu_verifikasi',
            ],
            'tanggal_melamar DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'batas_perbaikan_berkas' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'tanggal_diproses' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'tanggal_wawancara' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'tanggal_keputusan' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
            'diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_lamaran', true);
        $this->forge->addUniqueKey(['id_pelamar', 'id_lowongan'], 'uk_tb_lamaran_pelamar_lowongan');
        $this->forge->addKey('status');
        $this->forge->addKey('id_pelamar');
        $this->forge->addKey('id_lowongan');
        $this->forge->addForeignKey('id_pelamar', 'tb_pelamar', 'id_pelamar', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_lowongan', 'tb_lowongan', 'id_lowongan', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('dibuat_oleh', 'tb_pengguna', 'id_pengguna', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('tb_lamaran', true, [
            'ENGINE'  => 'InnoDB',
            'COMMENT' => 'Transaksi lamaran kerja pelamar',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('tb_lamaran', true);
    }
}
