<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| ENSURE TB KERJASAMA EXISTS
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: migration ini memastikan tabel
| tb_kerjasama benar-benar ada pada database yang sudah sempat
| mencatat migration create tetapi belum memiliki tabel fisiknya.
| Alur kerja: saat migration dijalankan, class ini mengecek tabel
| lebih dulu lalu membuatnya bila belum tersedia.
|
| Tips Debugging:
| - Jika migration ini tetap tidak membuat tabel, cek koneksi database default mengarah ke schema yang benar.
| - Jika tabel sudah ada, migration ini memang akan dilewati tanpa perubahan.
*/
class EnsureTbKerjasamaExists extends Migration
{
    /*
    |-------------------------------------------------------------------
    | METHOD UP
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membentuk tabel
    | tb_kerjasama hanya jika tabel tersebut belum tersedia.
    | Alur kerja: migration mengecek keberadaan tabel, menyiapkan
    | field dan unique key, lalu membuat tabel dengan DB Forge.
    |
    | Tips Debugging:
    | - Jika nama atau slug tidak unik, cek addUniqueKey pada migration ini.
    | - Jika tabel tetap tidak muncul, cek tableExists() membaca database aktif yang benar.
    */
    public function up()
    {
        if ($this->db->tableExists('tb_kerjasama')) {
            return;
        }

        $this->forge->addField([
            'id_kerjasama' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_kerjasama' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'slug_kerjasama' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'deskripsi' => [
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

        $this->forge->addKey('id_kerjasama', true);
        $this->forge->addUniqueKey('nama_kerjasama', 'uk_tb_kerjasama_nama_kerjasama');
        $this->forge->addUniqueKey('slug_kerjasama', 'uk_tb_kerjasama_slug_kerjasama');
        $this->forge->createTable('tb_kerjasama', true);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD DOWN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghapus tabel
    | tb_kerjasama saat rollback migration diperlukan.
    | Alur kerja: CI4 memanggil method ini ketika migrate:rollback
    | menurunkan migration ini dan tabel target masih ada.
    |
    | Tips Debugging:
    | - Jika rollback gagal, cek apakah tabel sedang dipakai oleh query aktif.
    | - Jika tabel tidak ikut terhapus, cek nama tabel pada dropTable sesuai target.
    */
    public function down()
    {
        $this->forge->dropTable('tb_kerjasama', true);
    }
}
