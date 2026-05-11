<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| PERAN MODEL
|-------------------------------------------------------------------
| Model ini mengelola akses data tabel tb_peran sebagai master role
| untuk kebutuhan autentikasi dan otorisasi aplikasi.
| Alur kerja: CI4 memanggil model ini saat controller atau service
| membutuhkan data peran berdasarkan id atau slug_peran.
|
| Tips Debugging:
| - Jika migrasi gagal X, cek apakah tabel tb_peran sudah berhasil dibuat.
| - Jika seeder duplikat, cek nilai unique slug_peran pada data role.
*/
class PeranModel extends Model
{
    protected $table            = 'tb_peran';
    protected $primaryKey       = 'id_peran';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama_peran',
        'slug_peran',
        'keterangan',
        'dibuat_pada',
        'diperbarui_pada',
    ];
    protected $useTimestamps = false;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    /*
    |-------------------------------------------------------------------
    | METHOD CARI BY SLUG
    |-------------------------------------------------------------------
    | Method ini mengambil satu data peran berdasarkan slug_peran agar
    | controller bisa memetakan role dengan stabil.
    | Alur kerja: CI4 menjalankan query ke tb_peran saat method ini
    | dipanggil oleh modul login atau otorisasi berbasis slug.
    |
    | Tips Debugging:
    | - Jika migrasi gagal X, cek apakah kolom slug_peran ada di tabel.
    | - Jika seeder duplikat, cek apakah slug_peran yang sama tersimpan lebih dari sekali.
    */
    public function cariBySlug(string $slugPeran): ?array
    {
        return $this->where('slug_peran', $slugPeran)->first();
    }
}
