<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| PENGGUNA MODEL
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: model ini mengelola data pengguna dari
| tabel tb_pengguna untuk kebutuhan autentikasi.
| Alur kerja: controller login memanggil model ini untuk mencari data
| akun berdasarkan email, lalu model melakukan join ke tb_peran agar
| slug_peran bisa dipakai untuk proses redirect.
|
| Tips Debugging:
| - Jika data login tidak ditemukan, periksa nama tabel tb_pengguna dan isi email di database.
| - Jika slug_peran kosong, periksa join ke tabel tb_peran dan relasi id_peran.
*/
class PenggunaModel extends Model
{
    protected $table            = 'tb_pengguna';
    protected $primaryKey       = 'id_pengguna';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_peran',
        'nama_lengkap',
        'email',
        'kata_sandi',
        'nomor_telepon',
        'foto_profil',
        'status_aktif',
        'token_reset',
        'token_reset_expired',
        'terakhir_login',
        'dibuat_pada',
        'diperbarui_pada',
    ];

    /*
    |-------------------------------------------------------------------
    | METHOD CARI BY EMAIL
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: mengambil satu data pengguna
    | berdasarkan email beserta data peran yang terhubung.
    | Alur kerja: model mem-filter kolom email, melakukan join dengan
    | tb_peran, lalu mengembalikan satu baris pertama yang cocok.
    |
    | Tips Debugging:
    | - Jika hasil null padahal akun ada, periksa nilai email yang dikirim dari form login.
    | - Jika nama_peran atau slug_peran tidak ikut terbawa, periksa kolom join id_peran.
    */
    public function cariByEmail(string $email): ?array
    {
        return $this->select('tb_pengguna.*, tb_peran.nama_peran, tb_peran.slug_peran, tb_pelamar.status_pendaftaran')
            ->join('tb_peran', 'tb_peran.id_peran = tb_pengguna.id_peran', 'left')
            ->join('tb_pelamar', 'tb_pelamar.id_pengguna = tb_pengguna.id_pengguna', 'left')
            ->where('tb_pengguna.email', $email)
            ->first();
    }
}
