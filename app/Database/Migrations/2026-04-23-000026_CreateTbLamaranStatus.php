<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| MIGRATION TB_LAMARAN_STATUS
|-------------------------------------------------------------------
| Tabel ini menyimpan audit trail perpindahan status lamaran agar
| pelamar, BKK, dan HRD bisa melacak proses seleksi dengan rapi.
|
| Tips Debugging:
| - Jika histori status tidak tercatat, cek controller submit/update
|   status sudah menambahkan baris ke tabel ini.
*/
class CreateTbLamaranStatus extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tb_lamaran_status')) {
            return;
        }

        $statusEnum = [
            'menunggu_verifikasi',
            'perlu_perbaikan_berkas',
            'diproses',
            'wawancara',
            'diterima',
            'ditolak',
            'mengundurkan_diri',
        ];

        $this->forge->addField([
            'id_status' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_lamaran' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'status_lama' => [
                'type'       => 'ENUM',
                'constraint' => $statusEnum,
                'null'       => true,
            ],
            'status_baru' => [
                'type'       => 'ENUM',
                'constraint' => $statusEnum,
                'null'       => false,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'diubah_oleh' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_status', true);
        $this->forge->addKey('id_lamaran');
        $this->forge->addForeignKey('id_lamaran', 'tb_lamaran', 'id_lamaran', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('diubah_oleh', 'tb_pengguna', 'id_pengguna', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('tb_lamaran_status', true, [
            'ENGINE'  => 'InnoDB',
            'COMMENT' => 'Riwayat perubahan status lamaran',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('tb_lamaran_status', true);
    }
}
