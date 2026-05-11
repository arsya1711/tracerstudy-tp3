<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RiwayatKerjaSeeder extends Seeder
{
    public function run()
    {
        /**
         * ============================================================
         * SEEDER RIWAYAT KERJA
         * ============================================================
         * Seeder ini menyiapkan data contoh riwayat kerja untuk
         * keperluan testing dan development. Data ini adalah contoh
         * data dummy yang realistis untuk memudahkan testing fitur.
         *
         * Catatan:
         * - Seeder ini hanya menjalankan jika tabel tb_riwayat_kerja
         *   dan tb_pelamar sudah ada di database.
         * - Gunakan: php spark db:seed RiwayatKerjaSeeder
         * ============================================================
         */

        $data = [
            // Riwayat kerja untuk pelamar dengan id_pelamar = 1
            [
                'id_pelamar'      => 1,
                'nama_perusahaan' => 'PT Telkom Indonesia',
                'bidang_usaha'    => 'Telekomunikasi',
                'lokasi'          => 'Jakarta',
                'posisi_jabatan'  => 'IT Support',
                'tanggal_mulai'   => '2023-01-15',
                'tanggal_selesai' => '2024-06-30',
                'masih_bekerja'   => 0,
                'keterangan'      => 'Memberikan support teknis untuk infrastruktur jaringan perusahaan. Menangani ticket hingga level 2.',
                'dibuat_pada'     => date('Y-m-d H:i:s'),
                'diperbarui_pada' => date('Y-m-d H:i:s'),
            ],
            [
                'id_pelamar'      => 1,
                'nama_perusahaan' => 'CV Maju Jaya Tech',
                'bidang_usaha'    => 'IT Services',
                'lokasi'          => 'Bekasi',
                'posisi_jabatan'  => 'Programmer',
                'tanggal_mulai'   => '2024-07-01',
                'tanggal_selesai' => null,
                'masih_bekerja'   => 1,
                'keterangan'      => 'Mengembangkan web application menggunakan PHP dan CodeIgniter. Tim size 5 orang.',
                'dibuat_pada'     => date('Y-m-d H:i:s'),
                'diperbarui_pada' => date('Y-m-d H:i:s'),
            ],

            // Riwayat kerja untuk pelamar dengan id_pelamar = 2
            [
                'id_pelamar'      => 2,
                'nama_perusahaan' => 'PT Bank Rakyat Indonesia',
                'bidang_usaha'    => 'Perbankan',
                'lokasi'          => 'Jakarta Pusat',
                'posisi_jabatan'  => 'Customer Service',
                'tanggal_mulai'   => '2022-06-01',
                'tanggal_selesai' => '2024-02-28',
                'masih_bekerja'   => 0,
                'keterangan'      => 'Melayani nasabah di front office. Menangani pembukaan rekening, setoran, dan transaksi lainnya.',
                'dibuat_pada'     => date('Y-m-d H:i:s'),
                'diperbarui_pada' => date('Y-m-d H:i:s'),
            ],

            // Riwayat kerja untuk pelamar dengan id_pelamar = 3
            [
                'id_pelamar'      => 3,
                'nama_perusahaan' => 'PT Garuda Indonesia',
                'bidang_usaha'    => 'Penerbangan',
                'lokasi'          => 'Tangerang',
                'posisi_jabatan'  => 'Ground Crew',
                'tanggal_mulai'   => '2023-03-10',
                'tanggal_selesai' => null,
                'masih_bekerja'   => 1,
                'keterangan'      => 'Bertanggung jawab atas penanganan bagasi dan loading penerbangan. Shift 3 sistem.',
                'dibuat_pada'     => date('Y-m-d H:i:s'),
                'diperbarui_pada' => date('Y-m-d H:i:s'),
            ],
        ];

        // Only insert jika tabel dan pelamar ada
        if (! $this->db->tableExists('tb_riwayat_kerja')) {
            return;
        }

        // Check if data pelamar sudah ada sebelum insert
        $builder = $this->db->table('tb_pelamar');
        foreach ($data as &$row) {
            $pelamarCheck = $builder->where('id_pelamar', $row['id_pelamar'])->get()->getRow();
            if ($pelamarCheck === null) {
                unset($row); // Skip jika pelamar tidak ditemukan
            }
        }

        if (! empty($data)) {
            $this->db->table('tb_riwayat_kerja')->insertBatch($data);
        }
    }
}
