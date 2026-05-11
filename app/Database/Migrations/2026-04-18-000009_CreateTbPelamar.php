<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| CREATE TB PELAMAR
|-------------------------------------------------------------------
| Migration ini membuat tabel tb_pelamar sebagai detail lanjutan dari
| akun pengguna untuk role pelamar umum maupun pelamar alumni.
| Alur kerja: CI4 menjalankan migration ini setelah tb_pengguna dan
| tb_peran tersedia, lalu method up() membentuk struktur tabel beserta
| relasi ke akun pengguna dan pencatat aktivasi.
|
| Tips Debugging:
| - Jika tabel gagal dibuat, cek foreign key ke tb_pengguna sudah ada.
| - Jika insert account_id duplikat, cek unique key account_id pada tabel ini.
*/
class CreateTbPelamar extends Migration
{
    /*
    |-------------------------------------------------------------------
    | METHOD UP
    |-------------------------------------------------------------------
    | Method ini membangun struktur tabel tb_pelamar lengkap dengan
    | relasi akun pengguna, data biodata dasar, status pendaftaran,
    | dan metadata aktivasi akun pelamar.
    | Alur kerja: saat php spark migrate dijalankan, CI4 memanggil
    | method ini untuk membuat tabel bila belum ada.
    |
    | Tips Debugging:
    | - Jika foreign key gagal, cek tipe kolom id_pengguna konsisten UNSIGNED.
    | - Jika kolom enum error, cek engine database mendukung ENUM MySQL/MariaDB.
    */
    public function up()
    {
        $this->forge->addField([
            'id_pelamar' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pengguna' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'account_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
            ],
            'foto' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'jenis_kelamin' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'tempat_lahir' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'tanggal_lahir' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'alamat' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'nomer_nik' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'status_pendaftaran' => [
                'type'       => 'ENUM',
                'constraint' => ['menunggu_aktivasi', 'aktif', 'terdaftar'],
                'default'    => 'menunggu_aktivasi',
                'null'       => false,
            ],
            'terdaftar_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'diaktivasi_oleh' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'diaktivasi_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
            'diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_pelamar', true);
        $this->forge->addUniqueKey('id_pengguna', 'uk_tb_pelamar_id_pengguna');
        $this->forge->addUniqueKey('account_id', 'uk_tb_pelamar_account_id');
        $this->forge->addKey('status_pendaftaran');
        $this->forge->addForeignKey('id_pengguna', 'tb_pengguna', 'id_pengguna', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('diaktivasi_oleh', 'tb_pengguna', 'id_pengguna', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tb_pelamar', true);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD DOWN
    |-------------------------------------------------------------------
    | Method ini menghapus tabel tb_pelamar saat rollback migration
    | diperlukan.
    | Alur kerja: CI4 memanggil method ini ketika migrate:rollback
    | atau migrate:refresh menurunkan migration ini.
    |
    | Tips Debugging:
    | - Jika rollback gagal, cek apakah ada foreign key dari tb_alumni.
    | - Jika tabel masih tersisa, cek nama tb_pelamar pada dropTable.
    */
    public function down()
    {
        $this->forge->dropTable('tb_pelamar', true);
    }
}
