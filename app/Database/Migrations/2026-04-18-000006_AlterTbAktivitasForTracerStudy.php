<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| ALTER TB AKTIVITAS FOR TRACER STUDY
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: migration ini menyelaraskan struktur
| tb_aktivitas lama ke kebutuhan master aktivitas alumni tracer study.
| Alur kerja: saat migration dijalankan pada database lama, class ini
| menghapus kolom lama, menambah kolom baru, dan memperbarui index
| serta timestamp agar sesuai dengan modul Aktivitas terbaru.
|
| Tips Debugging:
| - Jika ALTER gagal, cek apakah MySQL sedang memakai tabel tb_aktivitas di query lain.
| - Jika migration terasa tidak berpengaruh, cek schema lama memang sudah ada sebelum file ini dijalankan.
*/
class AlterTbAktivitasForTracerStudy extends Migration
{
    /*
    |-------------------------------------------------------------------
    | METHOD UP
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengubah schema
    | tb_aktivitas lama menjadi schema master aktivitas alumni.
    | Alur kerja: migration mengecek kolom dan index yang ada dulu,
    | lalu hanya menjalankan ALTER yang memang diperlukan.
    |
    | Tips Debugging:
    | - Jika kolom keterangan belum muncul, cek fieldExists() membaca tabel yang benar.
    | - Jika unique key gagal dibuat, cek ada data nama_aktivitas yang duplikat di database lama.
    */
    public function up()
    {
        if (! $this->db->tableExists('tb_aktivitas')) {
            return;
        }

        if ($this->db->fieldExists('jenis_aktivitas', 'tb_aktivitas')) {
            $this->db->query('ALTER TABLE `tb_aktivitas` DROP COLUMN `jenis_aktivitas`');
        }

        if (! $this->db->fieldExists('keterangan', 'tb_aktivitas')) {
            $this->db->query('ALTER TABLE `tb_aktivitas` ADD COLUMN `keterangan` TEXT NULL AFTER `nama_aktivitas`');
        }

        $this->db->query('ALTER TABLE `tb_aktivitas` MODIFY `nama_aktivitas` VARCHAR(100) NOT NULL');
        $this->db->query('ALTER TABLE `tb_aktivitas` MODIFY `status_aktif` TINYINT(1) NOT NULL DEFAULT 1');

        if ($this->db->fieldExists('dibuat_pada', 'tb_aktivitas')) {
            $this->db->query('ALTER TABLE `tb_aktivitas` MODIFY `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        } else {
            $this->db->query('ALTER TABLE `tb_aktivitas` ADD COLUMN `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }

        if ($this->db->fieldExists('diperbarui_pada', 'tb_aktivitas')) {
            $this->db->query('ALTER TABLE `tb_aktivitas` MODIFY `diperbarui_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        } else {
            $this->db->query('ALTER TABLE `tb_aktivitas` ADD COLUMN `diperbarui_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }

        if (! $this->punyaIndex('tb_aktivitas', 'uk_tb_aktivitas_nama_aktivitas')) {
            $this->db->query('ALTER TABLE `tb_aktivitas` ADD UNIQUE KEY `uk_tb_aktivitas_nama_aktivitas` (`nama_aktivitas`)');
        }
    }

    /*
    |-------------------------------------------------------------------
    | METHOD DOWN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengembalikan schema
    | tb_aktivitas ke bentuk lama jika rollback diperlukan.
    | Alur kerja: migration melepas unique key baru, menghapus
    | keterangan, lalu mengembalikan kolom lama seperlunya.
    |
    | Tips Debugging:
    | - Jika rollback gagal, cek apakah index atau kolom target masih ada.
    | - Jika jenis_aktivitas tidak kembali, cek method down() benar-benar terpanggil saat rollback.
    */
    public function down()
    {
        if (! $this->db->tableExists('tb_aktivitas')) {
            return;
        }

        if ($this->punyaIndex('tb_aktivitas', 'uk_tb_aktivitas_nama_aktivitas')) {
            $this->db->query('ALTER TABLE `tb_aktivitas` DROP INDEX `uk_tb_aktivitas_nama_aktivitas`');
        }

        if ($this->db->fieldExists('keterangan', 'tb_aktivitas')) {
            $this->db->query('ALTER TABLE `tb_aktivitas` DROP COLUMN `keterangan`');
        }

        if (! $this->db->fieldExists('jenis_aktivitas', 'tb_aktivitas')) {
            $this->db->query("ALTER TABLE `tb_aktivitas` ADD COLUMN `jenis_aktivitas` ENUM('Internal','Eksternal') NOT NULL DEFAULT 'Internal' AFTER `nama_aktivitas`");
        }

        $this->db->query('ALTER TABLE `tb_aktivitas` MODIFY `nama_aktivitas` VARCHAR(150) NOT NULL');
        $this->db->query('ALTER TABLE `tb_aktivitas` MODIFY `dibuat_pada` DATETIME NULL');
        $this->db->query('ALTER TABLE `tb_aktivitas` MODIFY `diperbarui_pada` DATETIME NULL');
    }

    /*
    |-------------------------------------------------------------------
    | METHOD PUNYA INDEX
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memeriksa apakah sebuah
    | tabel sudah memiliki index dengan nama tertentu.
    | Alur kerja: migration menjalankan SHOW INDEX, lalu mengembalikan
    | true jika nama index ditemukan pada hasil query.
    |
    | Tips Debugging:
    | - Jika hasil selalu false, cek nama index dan nama tabel yang dikirim ke method ini.
    */
    protected function punyaIndex(string $namaTabel, string $namaIndex): bool
    {
        $hasil = $this->db->query(
            'SHOW INDEX FROM `' . $namaTabel . '` WHERE Key_name = ?',
            [$namaIndex]
        )->getResultArray();

        return $hasil !== [];
    }
}
