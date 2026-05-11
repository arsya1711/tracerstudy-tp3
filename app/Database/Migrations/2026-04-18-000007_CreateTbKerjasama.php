<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| CREATE TB KERJASAMA
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: migration ini membuat tabel
| tb_kerjasama untuk menyimpan master jenis kerjasama yang dipakai
| sebagai referensi pada tabel MoU dan modul lowongan.
| Alur kerja: CI4 menjalankan class ini saat php spark migrate
| menemukan migration baru, lalu method up() membentuk tabel dan
| method down() menghapus tabel saat rollback dibutuhkan.
|
| Tips Debugging:
| - Jika tabel gagal dibuat, cek sintaks unique key dan default timestamp pada query SQL.
| - Jika migration tidak terbaca, cek timestamp file lebih besar dari migration sebelumnya.
*/
class CreateTbKerjasama extends Migration
{
    /*
    |-------------------------------------------------------------------
    | METHOD UP
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membangun struktur tabel
    | tb_kerjasama lengkap dengan nama, slug, deskripsi, status aktif,
    | dan kolom timestamp pencatatan data.
    | Alur kerja: saat php spark migrate dijalankan, CI4 memanggil
    | method ini untuk membuat tabel jika belum ada.
    |
    | Tips Debugging:
    | - Jika insert gagal karena nama atau slug duplikat, cek unique key pada tabel ini.
    | - Jika kolom waktu tidak update otomatis, cek definisi CURRENT_TIMESTAMP dan ON UPDATE.
    */
    public function up()
    {
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
    | atau migrate:refresh menurunkan migration ini.
    |
    | Tips Debugging:
    | - Jika rollback gagal, cek apakah tabel sedang dipakai oleh query aktif.
    | - Jika tabel masih tersisa, cek nama tb_kerjasama pada query DROP TABLE.
    */
    public function down()
    {
        $this->forge->dropTable('tb_kerjasama', true);
    }
}
