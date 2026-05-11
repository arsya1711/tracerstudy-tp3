<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| CREATE TB KOMPETENSI
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: migration ini membuat tabel
| tb_kompetensi untuk menyimpan daftar kompetensi keahlian sekolah
| yang dipakai oleh modul manajemen kompetensi.
| Alur kerja: CI4 menjalankan class ini saat perintah migrate
| menemukan file migration baru, lalu method up() membuat struktur
| tabel dan method down() menghapusnya saat rollback.
|
| Tips Debugging:
| - Jika tabel gagal dibuat, cek nama kolom dan tipe data di migration ini.
| - Jika migrate tidak jalan, cek timestamp file migration lebih besar dari migration sebelumnya.
*/
class CreateTbKompetensi extends Migration
{
    /*
    |-------------------------------------------------------------------
    | METHOD UP
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membangun tabel
    | tb_kompetensi lengkap dengan primary key, status aktif, dan
    | kolom timestamp untuk pencatatan data.
    | Alur kerja: saat php spark migrate dijalankan, CI4 memanggil
    | method ini untuk membuat tabel jika belum ada.
    |
    | Tips Debugging:
    | - Jika kolom timestamp kosong saat insert model, cek nama createdField dan updatedField pada model.
    | - Jika struktur tabel berbeda dari harapan, cek cache schema database dan jalankan migrate:refresh bila perlu.
    */
    public function up()
    {
        $this->forge->addField([
            'id_kompetensi' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_kompetensi' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'akronim' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'status_aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
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

        $this->forge->addKey('id_kompetensi', true);
        $this->forge->createTable('tb_kompetensi', true);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD DOWN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghapus tabel
    | tb_kompetensi saat rollback migration dibutuhkan.
    | Alur kerja: CI4 memanggil method ini ketika migrate:rollback
    | atau migrate:refresh menurunkan migration ini.
    |
    | Tips Debugging:
    | - Jika rollback gagal, cek apakah tabel sedang dipakai oleh query aktif.
    | - Jika tabel masih ada setelah rollback, cek nama tabel di dropTable sama dengan tabel yang dibuat.
    */
    public function down()
    {
        $this->forge->dropTable('tb_kompetensi', true);
    }
}
