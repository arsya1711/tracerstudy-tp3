<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/*
|-------------------------------------------------------------------
| ANGKATAN SEEDER
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: seeder ini mengisi data awal angkatan
| agar modul Angkatan langsung memiliki tahun lulus dasar yang siap
| dipakai saat pengembangan awal.
| Alur kerja: CI4 menjalankan class ini saat php spark db:seed
| memanggil AngkatanSeeder, lalu method run() melakukan insert batch.
|
| Tips Debugging:
| - Jika data tidak masuk, cek tabel tb_angkatan sudah berhasil dibuat.
| - Jika data dobel, cek seeder dijalankan berulang tanpa membersihkan tabel.
*/
class AngkatanSeeder extends Seeder
{
    /*
    |-------------------------------------------------------------------
    | METHOD RUN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memasukkan tiga data
    | tahun lulus awal ke tabel tb_angkatan.
    | Alur kerja: CI4 memanggil method ini saat seeder dijalankan,
    | lalu insertBatch menyimpan semua data sekaligus.
    |
    | Tips Debugging:
    | - Jika gagal karena unique, cek apakah tahun 2022, 2023, atau 2024 sudah ada.
    | - Jika timestamp kosong, cek field dibuat_pada dan diperbarui_pada pada tabel.
    */
    public function run()
    {
        $waktuSekarang = date('Y-m-d H:i:s');

        $data = [
            [
                'tahun_lulus'    => '2022',
                'status_aktif'   => 1,
                'dibuat_pada'    => $waktuSekarang,
                'diperbarui_pada'=> $waktuSekarang,
            ],
            [
                'tahun_lulus'    => '2023',
                'status_aktif'   => 1,
                'dibuat_pada'    => $waktuSekarang,
                'diperbarui_pada'=> $waktuSekarang,
            ],
            [
                'tahun_lulus'    => '2024',
                'status_aktif'   => 1,
                'dibuat_pada'    => $waktuSekarang,
                'diperbarui_pada'=> $waktuSekarang,
            ],
        ];

        $this->db->table('tb_angkatan')->insertBatch($data);
    }
}
