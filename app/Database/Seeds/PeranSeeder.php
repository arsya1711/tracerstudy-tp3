<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/*
|-------------------------------------------------------------------
| PERAN SEEDER
|-------------------------------------------------------------------
| Seeder ini mengisi data master peran awal untuk modul autentikasi
| sesuai role final production. Nilai slug_peran dipakai di
| LoginController untuk menentukan redirect berdasarkan role pengguna
| setelah proses autentikasi berhasil.
| Alur kerja: CI4 menjalankan class ini saat php spark db:seed
| memanggil PeranSeeder, lalu method run() menambahkan data awal.
|
| Tips Debugging:
| - Jika migrasi gagal karena tabel belum ada, cek urutan migrate sebelum seed.
| - Jika seeder duplikat, cek data lama dan constraint unique slug_peran.
*/
class PeranSeeder extends Seeder
{
    /*
    |-------------------------------------------------------------------
    | METHOD RUN
    |-------------------------------------------------------------------
    | Method ini memasukkan lima data role utama ke tabel tb_peran
    | sebagai referensi hak akses aplikasi.
    | Alur kerja: CI4 memanggil method ini saat seeder dijalankan
    | manual atau dirangkai dari seeder lain.
    |
    | Tips Debugging:
    | - Jika migrasi gagal karena tabel tidak ditemukan, cek apakah migrate sudah sukses.
    | - Jika seeder duplikat, cek apakah slug_peran atau nama_peran sudah ada.
    */
    public function run()
    {
        $data = [
            [
                'nama_peran' => 'Super Admin',
                'slug_peran' => 'superadmin',
                'keterangan' => 'Akses penuh ke seluruh sistem',
            ],
            [
                'nama_peran' => 'Admin Sekolah/BKK',
                'slug_peran' => 'admin_sekolah',
                'keterangan' => 'Mengelola data sekolah dan BKK',
            ],
            [
                'nama_peran' => 'Admin DUDI',
                'slug_peran' => 'admin_dudi',
                'keterangan' => 'Mengelola data perusahaan dan lowongan',
            ],
            [
                'nama_peran' => 'Pelamar Umum',
                'slug_peran' => 'pelamar_umum',
                'keterangan' => 'Pelamar dari masyarakat umum',
            ],
            [
                'nama_peran' => 'Pelamar Alumni',
                'slug_peran' => 'pelamar_alumni',
                'keterangan' => 'Pelamar dari alumni sekolah',
            ],
        ];

        $this->db->table('tb_peran')->insertBatch($data);
    }
}
