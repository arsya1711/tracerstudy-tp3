<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/*
|-------------------------------------------------------------------
| KERJASAMA SEEDER
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: seeder ini mengisi data awal master
| jenis kerjasama agar modul MoU dan lowongan langsung memiliki
| referensi dasar saat pertama kali dipakai.
| Alur kerja: CI4 menjalankan class ini saat php spark db:seed
| memanggil KerjasamaSeeder, lalu method run() menyisipkan data awal
| satu per satu bila nama atau slug-nya belum tersedia.
|
| Tips Debugging:
| - Jika data tidak masuk, cek tabel tb_kerjasama sudah berhasil dibuat.
| - Jika data dobel, cek seeder dijalankan ulang dan kondisi pengecekan uniknya.
*/
class KerjasamaSeeder extends Seeder
{
    /*
    |-------------------------------------------------------------------
    | METHOD RUN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memasukkan enam data
    | jenis kerjasama awal ke tabel tb_kerjasama.
    | Alur kerja: CI4 memanggil method ini saat seeder dijalankan,
    | lalu setiap baris dicek dulu agar insert aman untuk dijalankan ulang.
    |
    | Tips Debugging:
    | - Jika insert gagal, cek nama kolom pada tabel dan data seed.
    | - Jika jenis rekrutmen tidak ada, cek baris slug rekrutmen ikut tersimpan.
    */
    public function run()
    {
        $waktuSekarang = date('Y-m-d H:i:s');

        $data = [
            [
                'nama_kerjasama'  => 'PKL',
                'slug_kerjasama'  => 'pkl',
                'deskripsi'       => 'Kerjasama praktik kerja lapangan atau magang industri bagi siswa.',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
            [
                'nama_kerjasama'  => 'Kunjungan Industri',
                'slug_kerjasama'  => 'kunjungan-industri',
                'deskripsi'       => 'Kerjasama kunjungan industri untuk penguatan wawasan dunia kerja.',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
            [
                'nama_kerjasama'  => 'Penguji UKK',
                'slug_kerjasama'  => 'penguji-ukk',
                'deskripsi'       => 'Kerjasama pelibatan industri sebagai penguji ujian kompetensi keahlian.',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
            [
                'nama_kerjasama'  => 'Sinkronisasi Kurikulum',
                'slug_kerjasama'  => 'sinkronisasi',
                'deskripsi'       => 'Kerjasama penyelarasan kurikulum sekolah dengan kebutuhan industri.',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
            [
                'nama_kerjasama'  => 'Rekrutmen Tenaga Kerja',
                'slug_kerjasama'  => 'rekrutmen',
                'deskripsi'       => 'Kerjasama rekrutmen tenaga kerja untuk mendukung modul lowongan dan penempatan alumni.',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
            [
                'nama_kerjasama'  => 'Pelatihan & Sertifikasi',
                'slug_kerjasama'  => 'pelatihan',
                'deskripsi'       => 'Kerjasama pelatihan dan sertifikasi untuk peningkatan kompetensi siswa dan alumni.',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
        ];

        foreach ($data as $baris) {
            $sudahAda = $this->db->table('tb_kerjasama')
                ->groupStart()
                ->where('nama_kerjasama', $baris['nama_kerjasama'])
                ->orWhere('slug_kerjasama', $baris['slug_kerjasama'])
                ->groupEnd()
                ->get()
                ->getRowArray();

            if ($sudahAda !== null) {
                continue;
            }

            $this->db->table('tb_kerjasama')->insert($baris);
        }
    }
}
