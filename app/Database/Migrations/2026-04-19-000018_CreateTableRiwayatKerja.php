<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableRiwayatKerja extends Migration
{
    public function up()
    {
        /**
         * ============================================================
         * MODUL 6: RIWAYAT KERJA PELAMAR
         * ============================================================
         * Tabel ini menyimpan data riwayat pengalaman kerja pelamar.
         * Data ini digunakan untuk:
         *   1. Generate CV otomatis
         *   2. Screening awal oleh Admin BKK/HRD
         *   3. Analisis kesesuaian kandidat dengan posisi
         *
         * CATATAN PENTING:
         *   • masih_bekerja = 1 artinya tanggal_selesai = NULL
         *   • INDEX compound (tanggal_mulai, tanggal_selesai) untuk
         *     query durasi pengalaman kerja
         *   • bidang_usaha untuk matching dengan lowongan
         *   • Relasi: tb_pelamar (1) ──→ (N) tb_riwayat_kerja
         * ============================================================
         */
        $this->forge->addField([
            'id_riwayat'      => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_pelamar'      => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'FK ke tb_pelamar',
            ],
            'nama_perusahaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
                'comment'    => 'Nama perusahaan/institusi tempat bekerja',
            ],
            'bidang_usaha'    => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Contoh: Otomotif, Retail, IT, Banking, dll',
            ],
            'lokasi'          => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Kota/Kabupaten tempat bekerja',
            ],
            'posisi_jabatan'  => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Posisi/jabatan yang dijabat (contoh: Staff IT, Manager, dll)',
            ],
            'tanggal_mulai'   => [
                'type'    => 'DATE',
                'null'    => false,
                'comment' => 'Tanggal mulai bekerja (Wajib diisi untuk hitung durasi)',
            ],
            'tanggal_selesai' => [
                'type'    => 'DATE',
                'null'    => true,
                'comment' => 'Tanggal selesai bekerja (NULL jika masih_bekerja = 1)',
            ],
            'masih_bekerja'   => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'comment'    => '1 = Masih bekerja di tempat ini, 0 = Sudah berhenti',
            ],
            'keterangan'      => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Deskripsi tugas, tanggung jawab, & pencapaian di posisi ini',
            ],
            'dibuat_pada'     => [
                'type'    => 'DATETIME',
                'null'    => false,
            ],
            'diperbarui_pada' => [
                'type'    => 'DATETIME',
                'null'    => false,
            ],
        ]);

        $this->forge->addKey('id_riwayat', true);
        $this->forge->addKey(['id_pelamar'], false, false, 'idx_pelamar');
        $this->forge->addKey(['tanggal_mulai', 'tanggal_selesai'], false, false, 'idx_tanggal');

        $this->forge->addForeignKey('id_pelamar', 'tb_pelamar', 'id_pelamar', 'CASCADE', 'CASCADE');

        $this->forge->createTable('tb_riwayat_kerja', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);

        // Add comment ke table
        $this->db->query("ALTER TABLE `tb_riwayat_kerja` COMMENT='Riwayat pengalaman kerja pelamar untuk CV & screening HRD'");
    }

    public function down()
    {
        $this->forge->dropTable('tb_riwayat_kerja', true);
    }
}
