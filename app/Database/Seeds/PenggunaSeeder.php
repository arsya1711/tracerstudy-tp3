<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/*
|-------------------------------------------------------------------
| PENGGUNA SEEDER
|-------------------------------------------------------------------
| Seeder ini membuat akun Super Admin awal untuk kebutuhan login
| pertama kali pada modul autentikasi. Password wajib disimpan dalam
| bentuk hash, bukan plain text, sehingga seeder ini memakai
| password_hash() dengan PASSWORD_BCRYPT.
| Alur kerja: CI4 menjalankan class ini saat php spark db:seed
| memanggil PenggunaSeeder, lalu method run() menyimpan akun awal.
|
| Tips Debugging:
| - Jika migrasi gagal karena tabel pengguna belum ada, cek migrate modul autentikasi.
| - Jika seeder duplikat, cek apakah email superadmin@tracerstudy.local sudah ada.
*/
class PenggunaSeeder extends Seeder
{
    /*
    |-------------------------------------------------------------------
    | METHOD RUN
    |-------------------------------------------------------------------
    | Method ini memasukkan satu akun Super Administrator aktif
    | dengan relasi ke peran id 1 dan password yang sudah di-hash.
    | Alur kerja: CI4 memanggil method ini saat seeder dijalankan
    | langsung atau setelah PeranSeeder selesai.
    |
    | Tips Debugging:
    | - Jika migrasi gagal karena foreign key id_peran tidak valid, cek data tb_peran.
    | - Jika seeder duplikat, cek apakah email akun super admin sudah pernah dibuat.
    */
    public function run()
    {
        $peranSuperadmin = $this->db->table('tb_peran')
            ->where('slug_peran', 'superadmin')
            ->get()
            ->getRowArray();

        if (! $peranSuperadmin) {
            $this->call(PeranSeeder::class);

            $peranSuperadmin = $this->db->table('tb_peran')
                ->where('slug_peran', 'superadmin')
                ->get()
                ->getRowArray();
        }

        if (! $peranSuperadmin) {
            return;
        }

        $data = [
            'id_peran'      => $peranSuperadmin['id_peran'],
            'nama_lengkap'  => 'Super Administrator',
            'email'         => 'superadmin@tracerstudy.local',
            'kata_sandi'    => password_hash('Admin123', PASSWORD_BCRYPT),
            'status_aktif'  => 1,
        ];

        $table = $this->db->table('tb_pengguna');
        $existing = $table
            ->where('email', $data['email'])
            ->get()
            ->getRowArray();

        if ($existing) {
            $table
                ->where('id_pengguna', $existing['id_pengguna'])
                ->update($data);

            return;
        }

        $table->insert($data);
    }
}
