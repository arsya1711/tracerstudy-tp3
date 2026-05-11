<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| CREATE TB ALUMNI
|-------------------------------------------------------------------
| Migration ini membuat tabel tb_alumni untuk menyimpan data lanjutan
| khusus pelamar alumni yang terhubung ke angkatan dan kompetensi.
| Alur kerja: CI4 menjalankan migration ini setelah tb_pelamar,
| tb_angkatan, dan tb_kompetensi tersedia, lalu method up() membentuk
| relasi dan metadata verifikasi alumni.
|
| Tips Debugging:
| - Jika tabel gagal dibuat, cek migration tb_pelamar sudah sukses lebih dulu.
| - Jika foreign key verifikator gagal, cek tb_pengguna sudah ada.
*/
class CreateTbAlumni extends Migration
{
    /*
    |-------------------------------------------------------------------
    | METHOD UP
    |-------------------------------------------------------------------
    | Method ini membangun struktur tabel tb_alumni lengkap dengan
    | relasi ke pelamar, angkatan, kompetensi, dan pencatatan proses
    | verifikasi data alumni.
    | Alur kerja: saat php spark migrate dijalankan, CI4 memanggil
    | method ini untuk membuat tabel bila belum ada.
    |
    | Tips Debugging:
    | - Jika insert alumni duplikat, cek unique key id_pelamar pada tabel ini.
    | - Jika relasi angkatan atau kompetensi kosong, pastikan kolom boleh null.
    */
    public function up()
    {
        $this->forge->addField([
            'id_alumni' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pelamar' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'id_angkatan' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'id_kompetensi' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'nis' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'nisn' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'no_ijazah' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'status_verifikasi' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'catatan_verifikasi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'diverifikasi_oleh' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'diverifikasi_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
            'diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_alumni', true);
        $this->forge->addUniqueKey('id_pelamar', 'uk_tb_alumni_id_pelamar');
        $this->forge->addKey('id_angkatan');
        $this->forge->addKey('id_kompetensi');
        $this->forge->addForeignKey('id_pelamar', 'tb_pelamar', 'id_pelamar', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_angkatan', 'tb_angkatan', 'id_angkatan', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('id_kompetensi', 'tb_kompetensi', 'id_kompetensi', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('diverifikasi_oleh', 'tb_pengguna', 'id_pengguna', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tb_alumni', true);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD DOWN
    |-------------------------------------------------------------------
    | Method ini menghapus tabel tb_alumni saat rollback migration
    | diperlukan.
    | Alur kerja: CI4 memanggil method ini ketika migrate:rollback
    | atau migrate:refresh menurunkan migration ini.
    |
    | Tips Debugging:
    | - Jika rollback gagal, cek apakah ada tabel lain yang bergantung ke tb_alumni.
    | - Jika tabel masih ada, cek nama tb_alumni pada dropTable.
    */
    public function down()
    {
        $this->forge->dropTable('tb_alumni', true);
    }
}
