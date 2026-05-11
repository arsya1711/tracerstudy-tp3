<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| CREATE TABLE PERAN
|-------------------------------------------------------------------
| Migration ini membuat tabel master peran pengguna bernama tb_peran
| untuk menyimpan daftar role aplikasi. Kolom slug_peran dipakai
| middleware dan proses routing otorisasi untuk mengenali peran
| secara stabil tanpa bergantung pada teks nama tampilan.
| Alur kerja: CI4 menjalankan class ini saat perintah migrate
| menemukan file migration baru, lalu method up() dieksekusi untuk
| membuat tabel dan index yang dibutuhkan.
|
| Tips Debugging:
| - Jika migrasi gagal karena tabel sudah ada, cek status tabel di database.
| - Jika seeder duplikat, cek apakah slug_peran unique sudah terisi sebelumnya.
*/
class CreateTablePeran extends Migration
{
    /*
    |-------------------------------------------------------------------
    | METHOD UP
    |-------------------------------------------------------------------
    | Method ini membangun struktur tabel tb_peran lengkap dengan primary
    | key, unique key slug_peran, dan kolom timestamp produksi.
    | Alur kerja: CI4 memanggil method ini ketika migration dijalankan
    | ke arah maju melalui php spark migrate.
    |
    | Tips Debugging:
    | - Jika migrasi gagal karena sintaks SQL timestamp, cek versi MySQL/MariaDB.
    | - Jika seeder duplikat, cek apakah data role lama masih ada di tabel.
    */
    public function up()
    {
        $this->forge->addField([
            'id_peran' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_peran' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'slug_peran' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
            'diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_peran', true);
        $this->forge->addUniqueKey('slug_peran', 'uk_tb_peran_slug_peran');
        $this->forge->createTable('tb_peran', true);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD DOWN
    |-------------------------------------------------------------------
    | Method ini menghapus tabel tb_peran saat rollback migration
    | diperlukan pada proses pengembangan atau reset struktur database.
    | Alur kerja: CI4 memanggil method ini ketika perintah migrate:rollback
    | atau migrate:refresh menurunkan migration ini.
    |
    | Tips Debugging:
    | - Jika migrasi gagal karena foreign key masih dipakai, cek tabel turunan.
    | - Jika seeder duplikat, cek apakah rollback tidak benar-benar menghapus tabel.
    */
    public function down()
    {
        $this->forge->dropTable('tb_peran', true);
    }
}
