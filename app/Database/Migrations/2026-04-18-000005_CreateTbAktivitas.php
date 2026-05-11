<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| CREATE TB AKTIVITAS
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: migration ini membuat tabel
| tb_aktivitas untuk menyimpan master status aktivitas alumni
| setelah lulus yang dipakai form dinamis tracer study.
| Alur kerja: CI4 menjalankan class ini saat php spark migrate
| menemukan migration baru, lalu method up() membentuk tabel dan
| method down() menghapus tabel saat rollback dibutuhkan.
|
| Tips Debugging:
| - Jika tabel gagal dibuat, cek nama tabel dan sintaks default timestamp pada query SQL.
| - Jika migration tidak terbaca, cek timestamp file lebih besar dari migration sebelumnya.
*/
class CreateTbAktivitas extends Migration
{
    /*
    |-------------------------------------------------------------------
    | METHOD UP
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membangun struktur tabel
    | tb_aktivitas lengkap dengan primary key, keterangan, status
    | aktif, dan kolom timestamp pencatatan data.
    | Alur kerja: saat php spark migrate dijalankan, CI4 memanggil
    | method ini untuk membuat tabel jika belum ada.
    |
    | Tips Debugging:
    | - Jika nama aktivitas tidak bisa duplikat, cek unique key nama_aktivitas pada tabel ini.
    | - Jika kolom waktu tidak update otomatis, cek definisi CURRENT_TIMESTAMP dan ON UPDATE.
    */
    public function up()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `tb_aktivitas` (
                `id_aktivitas` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `nama_aktivitas` VARCHAR(100) NOT NULL,
                `keterangan` TEXT NULL,
                `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
                `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `diperbarui_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_aktivitas`),
                UNIQUE KEY `uk_tb_aktivitas_nama_aktivitas` (`nama_aktivitas`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    /*
    |-------------------------------------------------------------------
    | METHOD DOWN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghapus tabel
    | tb_aktivitas saat rollback migration diperlukan.
    | Alur kerja: CI4 memanggil method ini ketika migrate:rollback
    | atau migrate:refresh menurunkan migration ini.
    |
    | Tips Debugging:
    | - Jika rollback gagal, cek apakah tabel sedang dipakai oleh query aktif.
    | - Jika tabel masih tersisa, cek nama tb_aktivitas pada query DROP TABLE.
    */
    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `tb_aktivitas`');
    }
}
