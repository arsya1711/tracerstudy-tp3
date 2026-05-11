<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
|-------------------------------------------------------------------
| MIGRATION TB_LAMARAN_BERKAS
|-------------------------------------------------------------------
| Tabel ini menyimpan snapshot dokumen yang benar-benar dipakai saat
| pelamar men-submit lamaran ke satu lowongan tertentu.
|
| Alur kerja:
| 1. Pelamar upload dokumen per lamaran seperti CV dan surat lamaran.
| 2. File dipindahkan ke folder arsip lamaran.
| 3. Metadata file snapshot disimpan ke tabel ini.
|
| Tips Debugging:
| - Jika file lamaran berubah padahal lamaran lama sudah submit, cek
|   apakah aplikasi benar-benar menyimpan snapshot di tabel ini.
*/
class CreateTbLamaranBerkas extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tb_lamaran_berkas')) {
            return;
        }

        $this->forge->addField([
            'id_lamaran_berkas' => [
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
            'id_berkas' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'id_jenis_berkas' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'nama_file_snapshot' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'path_file_snapshot' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'ukuran_file_snapshot' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'tipe_mime_snapshot' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'wajib_saat_submit' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'status_review' => [
                'type'       => 'ENUM',
                'constraint' => ['menunggu', 'sesuai', 'perlu_perbaikan', 'ditolak'],
                'default'    => 'menunggu',
            ],
            'catatan_review' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ditinjau_oleh' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'ditinjau_pada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id_lamaran_berkas', true);
        $this->forge->addUniqueKey(['id_lamaran', 'id_jenis_berkas'], 'uk_tb_lamaran_jenis_berkas');
        $this->forge->addKey('id_lamaran');
        $this->forge->addKey('id_berkas');
        $this->forge->addKey('id_jenis_berkas');
        $this->forge->addForeignKey('id_lamaran', 'tb_lamaran', 'id_lamaran', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_berkas', 'tb_berkas', 'id_berkas', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('id_jenis_berkas', 'tb_jenis_berkas', 'id_jenis_berkas', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('ditinjau_oleh', 'tb_pengguna', 'id_pengguna', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tb_lamaran_berkas', true, [
            'ENGINE'  => 'InnoDB',
            'COMMENT' => 'Lampiran snapshot dokumen per lamaran',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('tb_lamaran_berkas', true);
    }
}
