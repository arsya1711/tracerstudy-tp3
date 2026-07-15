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
| Akun yang sudah ada tidak pernah diubah password, email, status, nama,
| atau perannya. Seeder hanya membuat akun bootstrap yang belum ada.
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
        $this->call(PeranSeeder::class);

        $akunAwal = [
            [
                'slug_peran'     => 'superadmin',
                'nama_lengkap'   => 'Super Administrator',
                'email'          => 'superadmin@tracerstudy.local',
                'password_env'   => 'seed.superadminPassword',
                'password_dev'   => 'Admin123',
                'legacy_emails'  => ['superadmin@tracer.com'],
            ],
            [
                'slug_peran'     => 'admin_sekolah',
                'nama_lengkap'   => 'Admin Sekolah',
                'email'          => 'adminsekolah@tracerstudy.local',
                'password_env'   => 'seed.adminSekolahPassword',
                'password_dev'   => 'AdminSekolah123',
                'legacy_emails'  => ['adminsekolah@tracer.com'],
            ],
        ];

        $table = $this->db->table('tb_pengguna');

        foreach ($akunAwal as $akun) {
            $peran = $this->db->table('tb_peran')
                ->where('slug_peran', $akun['slug_peran'])
                ->get()
                ->getRowArray();

            if (! $peran) {
                continue;
            }

            $existing = $table
                ->where('email', $akun['email'])
                ->get()
                ->getRowArray();

            if (! $existing && $akun['legacy_emails'] !== []) {
                $existing = $table
                    ->whereIn('email', $akun['legacy_emails'])
                    ->get()
                    ->getRowArray();
            }

            if (! $existing) {
                $existing = $table
                    ->where('id_peran', $peran['id_peran'])
                    ->where('nama_lengkap', $akun['nama_lengkap'])
                    ->get()
                    ->getRowArray();
            }

            if ($existing) {
                continue;
            }

            $password = $this->resolveBootstrapPassword($akun);
            $data = [
                'id_peran'      => $peran['id_peran'],
                'nama_lengkap'  => $akun['nama_lengkap'],
                'email'         => $akun['email'],
                'kata_sandi'    => password_hash($password, PASSWORD_BCRYPT),
                'status_aktif'  => 1,
            ];

            $table->insert($data);
        }
    }

    /**
     * Production bootstrap credentials must come from environment secrets.
     * The documented fallback remains available only for a fresh local/dev DB.
     */
    protected function resolveBootstrapPassword(array $akun): string
    {
        $environmentKey = (string) $akun['password_env'];
        $password = trim((string) env($environmentKey, ''));

        if ($password === '') {
            if (ENVIRONMENT === 'production') {
                throw new \RuntimeException(
                    "Environment {$environmentKey} wajib diisi sebelum membuat akun admin awal."
                );
            }

            $password = (string) $akun['password_dev'];
        }

        if (strlen($password) < 8) {
            throw new \RuntimeException(
                "Password bootstrap {$environmentKey} minimal 8 karakter."
            );
        }

        return $password;
    }
}
