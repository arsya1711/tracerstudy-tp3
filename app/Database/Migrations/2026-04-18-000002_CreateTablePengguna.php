<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| CREATE TABLE PENGGUNA
|-------------------------------------------------------------------
| Migration ini membuat tabel tb_pengguna untuk menyimpan akun login,
| profil dasar pengguna, status aktif, token reset password, dan relasi
| ke tabel peran. Kolom kata_sandi menggunakan VARCHAR(255) agar aman
| menampung hash bcrypt yang umumnya 60 karakter dan tetap kompatibel
| jika algoritma hash berubah. Kolom token_reset disiapkan untuk alur
| fitur lupa password dan validasi masa berlaku reset.
| Alur kerja: CI4 menjalankan class ini setelah migration peran sukses,
| lalu method up() membuat tabel, unique key, dan foreign key.
|
| Tips Debugging:
| - Jika migrasi gagal karena foreign key error, cek urutan migration dan engine tabel.
| - Jika seeder duplikat, cek apakah email unique sudah terisi pada data lama.
*/
class CreateTablePengguna extends Migration
{
    /*
    |-------------------------------------------------------------------
    | METHOD UP
    |-------------------------------------------------------------------
    | Method ini membangun struktur tabel tb_pengguna lengkap dengan
    | relasi ke tb_peran, unique email, status akun, dan metadata login.
    | Alur kerja: CI4 memanggil method ini saat migrate maju setelah
    | migration dengan timestamp lebih kecil berhasil dijalankan.
    |
    | Tips Debugging:
    | - Jika migrasi gagal saat membuat foreign key, cek nama tabel dan kolom referensi.
    | - Jika seeder duplikat, cek apakah email super admin sudah pernah dimasukkan.
    */
    public function up()
    {
        $this->forge->addField([
            'id_pengguna' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_peran' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'nama_lengkap' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'kata_sandi' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'nomor_telepon' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'foto_profil' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status_aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 1,
            ],
            'token_reset' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'token_reset_expired' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'terakhir_login' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
            'diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_pengguna', true);
        $this->forge->addUniqueKey('email', 'uk_tb_pengguna_email');
        $this->forge->addForeignKey('id_peran', 'tb_peran', 'id_peran', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('tb_pengguna', true);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD DOWN
    |-------------------------------------------------------------------
    | Method ini menghapus tabel tb_pengguna saat rollback migration
    | diperlukan untuk reset struktur autentikasi.
    | Alur kerja: CI4 memanggil method ini saat migrate mundur, biasanya
    | sebelum migration tabel referensi dihapus.
    |
    | Tips Debugging:
    | - Jika migrasi gagal saat drop table, cek apakah ada lock atau transaksi terbuka.
    | - Jika seeder duplikat, cek apakah rollback tidak dijalankan penuh sebelum seed ulang.
    */
    public function down()
    {
        $this->forge->dropTable('tb_pengguna', true);
    }
}
