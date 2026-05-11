<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/*
|-------------------------------------------------------------------
| KOMPETENSI SEEDER
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: seeder ini mengisi data awal kompetensi
| keahlian agar modul Kompetensi langsung memiliki data contoh yang
| siap ditampilkan di tabel.
| Alur kerja: CI4 menjalankan class ini saat php spark db:seed
| memanggil KompetensiSeeder, lalu method run() memasukkan data batch.
|
| Tips Debugging:
| - Jika data dobel, cek apakah seeder dijalankan berulang tanpa reset tabel.
| - Jika insert gagal, cek tabel tb_kompetensi sudah dibuat oleh migration.
*/
class KompetensiSeeder extends Seeder
{
    /*
    |-------------------------------------------------------------------
    | METHOD RUN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memasukkan tiga data awal
    | kompetensi keahlian produksi untuk kebutuhan awal modul.
    | Alur kerja: CI4 memanggil method ini saat seeder dijalankan,
    | lalu insertBatch menulis semua baris sekaligus ke database.
    |
    | Tips Debugging:
    | - Jika seeder tidak menambah data, cek koneksi database aktif.
    | - Jika timestamp kosong, cek field dibuat_pada dan diperbarui_pada ada di tabel.
    */
    public function run()
    {
        $waktuSekarang = date('Y-m-d H:i:s');

        $data = [
            [
                'nama_kompetensi' => 'Multimedia',
                'akronim'         => 'MM',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
            [
                'nama_kompetensi' => 'Teknik Komputer Jaringan',
                'akronim'         => 'TKJ',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
            [
                'nama_kompetensi' => 'Rekayasa Perangkat Lunak',
                'akronim'         => 'RPL',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
        ];

        $this->db->table('tb_kompetensi')->insertBatch($data);
    }
}
