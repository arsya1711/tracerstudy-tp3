<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| CREATE TB ANGKATAN
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: migration ini membuat tabel
| tb_angkatan untuk menyimpan data tahun lulus angkatan alumni yang
| dipakai pada modul manajemen Angkatan.
| Alur kerja: CI4 menjalankan class ini saat php spark migrate
| menemukan migration baru, lalu method up() membentuk tabel dan
| method down() menghapus tabel saat rollback dibutuhkan.
|
| Tips Debugging:
| - Jika tabel gagal dibuat, cek tipe kolom YEAR dan unique key tahun_lulus.
| - Jika migration tidak terbaca, cek timestamp file lebih besar dari migration sebelumnya.
*/
class CreateTbAngkatan extends Migration
{
    /*
    |-------------------------------------------------------------------
    | METHOD UP
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membangun struktur tabel
    | tb_angkatan lengkap dengan primary key, unique tahun_lulus,
    | status aktif, dan kolom timestamp pencatatan data.
    | Alur kerja: saat php spark migrate dijalankan, CI4 memanggil
    | method ini untuk membuat tabel jika belum ada.
    |
    | Tips Debugging:
    | - Jika insert model gagal karena duplikat, cek unique key tahun_lulus pada tabel ini.
    | - Jika kolom waktu kosong, cek konfigurasi timestamps pada model Angkatan.
    */
    public function up()
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS `tb_angkatan` (
                `id_angkatan` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `tahun_lulus` YEAR NOT NULL,
                `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
                `dibuat_pada` DATETIME NULL,
                `diperbarui_pada` DATETIME NULL,
                PRIMARY KEY (`id_angkatan`),
                UNIQUE KEY `uk_tb_angkatan_tahun_lulus` (`tahun_lulus`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
        );
    }

    /*
    |-------------------------------------------------------------------
    | METHOD DOWN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghapus tabel
    | tb_angkatan saat rollback migration diperlukan.
    | Alur kerja: CI4 memanggil method ini ketika migrate:rollback
    | atau migrate:refresh menurunkan migration ini.
    |
    | Tips Debugging:
    | - Jika rollback gagal, cek apakah tabel sedang dipakai oleh query aktif.
    | - Jika tabel masih tersisa, cek nama tb_angkatan pada dropTable.
    */
    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `tb_angkatan`');
    }
}
