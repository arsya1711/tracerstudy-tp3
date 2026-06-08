<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| MIGRATION TB_NOTIFIKASI
|-------------------------------------------------------------------
| Migration ini membuat tabel notifikasi per pengguna agar sistem
| dapat menampilkan badge/bell saat ada kejadian penting, seperti
| aktivitas penting seperti pendaftaran alumni baru.
|
| Alur kerja:
| 1. Event aplikasi membuat baris notifikasi untuk target pengguna.
| 2. Header dashboard membaca notifikasi yang belum dibaca.
| 3. Saat notifikasi diklik, sistem menandai notifikasi sebagai dibaca.
|
| Tips Debugging:
| - Jika badge tidak muncul, cek data tb_notifikasi.id_pengguna.
| - Jika klik notifikasi 404, cek target_url dan route NotifikasiController.
*/
class CreateTbNotifikasi extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tb_notifikasi')) {
            return;
        }

        $this->forge->addField([
            'id_notifikasi' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pengguna' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'tipe' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'umum',
            ],
            'judul' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'pesan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'target_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'dibaca' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'dibaca_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
            'diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_notifikasi', true);
        $this->forge->addKey(['id_pengguna', 'dibaca'], false, false, 'idx_notifikasi_pengguna_dibaca');
        $this->forge->addKey('tipe');
        $this->forge->addForeignKey('id_pengguna', 'tb_pengguna', 'id_pengguna', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_notifikasi', true, [
            'ENGINE'  => 'InnoDB',
            'COMMENT' => 'Notifikasi per pengguna untuk badge/bell dashboard',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('tb_notifikasi', true);
    }
}
