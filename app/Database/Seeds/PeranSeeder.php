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
    | Method ini memasukkan tiga data role utama ke tabel tb_peran
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
                'keterangan' => 'Akses penuh ke aplikasi tracer study',
            ],
            [
                'nama_peran' => 'Admin Sekolah',
                'slug_peran' => 'admin_sekolah',
                'keterangan' => 'Mengelola master data sekolah dan tracer alumni',
            ],
            [
                'nama_peran' => 'Alumni',
                'slug_peran' => 'alumni',
                'keterangan' => 'Akun alumni untuk mengisi profil dan tracer study',
            ],
        ];

        $table = $this->db->table('tb_peran');

        foreach ($data as $peran) {
            $existing = $table
                ->where('slug_peran', $peran['slug_peran'])
                ->get()
                ->getRowArray();

            if ($existing) {
                $table
                    ->where('id_peran', $existing['id_peran'])
                    ->update($peran);

                continue;
            }

            $table->insert($peran);
        }
    }
}
