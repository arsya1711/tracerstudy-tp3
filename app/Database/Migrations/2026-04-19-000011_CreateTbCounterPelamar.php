<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| CREATE TB COUNTER PELAMAR
|-------------------------------------------------------------------
| Migration ini membuat tabel counter harian untuk generator
| account_id pelamar agar nomor urut tidak pernah mundur walau
| data pelamar sebelumnya dihapus.
| Alur kerja: CI4 menjalankan migration ini lalu tabel menyimpan
| nomor_terakhir per tanggal generate dalam format YYYYMMDD.
|
| Tips Debugging:
| - Jika nomor urut tetap duplikat, cek unique key tanggal_generate.
| - Jika generator gagal, cek migration ini sudah termigrate.
*/
class CreateTbCounterPelamar extends Migration
{
    /*
    |-------------------------------------------------------------------
    | METHOD UP
    |-------------------------------------------------------------------
    | Method ini membangun tabel tb_counter_pelamar untuk menyimpan
    | counter account ID per hari.
    | Alur kerja: saat php spark migrate dijalankan, CI4 memanggil
    | method ini dan membuat tabel bila belum ada.
    */
    public function up()
    {
        $this->forge->addField([
            'id_counter_pelamar' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tanggal_generate' => [
                'type'       => 'VARCHAR',
                'constraint' => 8,
                'null'       => false,
            ],
            'nomor_terakhir' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'null'       => false,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
            'diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_counter_pelamar', true);
        $this->forge->addUniqueKey('tanggal_generate', 'uk_tb_counter_pelamar_tanggal_generate');
        $this->forge->createTable('tb_counter_pelamar', true);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD DOWN
    |-------------------------------------------------------------------
    | Method ini menghapus tabel tb_counter_pelamar saat rollback
    | migration diperlukan.
    */
    public function down()
    {
        $this->forge->dropTable('tb_counter_pelamar', true);
    }
}
