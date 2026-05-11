<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL ANGKATAN
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: model ini menangani operasi data pada
| tabel tb_angkatan termasuk pengambilan daftar angkatan aktif beserta
| jumlah siswa atau alumni yang terhubung.
| Alur kerja: controller memanggil model ini untuk membaca, menambah,
| memperbarui, dan menonaktifkan data angkatan di database.
|
| Tips Debugging:
| - Jika data tidak tersimpan, cek allowedFields pada model ini.
| - Jika timestamp kosong, cek useTimestamps dan nama field tanggalnya.
*/

class AngkatanModel extends Model
{
    protected $table         = 'tb_angkatan';
    protected $primaryKey    = 'id_angkatan';
    protected $returnType    = 'array';
    protected $allowedFields = ['tahun_lulus', 'status_aktif'];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL SEMUA DENGAN JUMLAH SISWA
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengambil seluruh data
    | angkatan aktif dan menghitung jumlah alumni yang terhubung melalui
    | relasi id_angkatan pada tb_alumni (sama seperti KompetensiModel).
    | Alur kerja: controller memanggil method ini, model mengecek
    | keberadaan tabel serta kolom relasi, lalu menjalankan query LEFT
    | JOIN ke tb_alumni dan GROUP BY atau mengembalikan jumlah 0 bila 
    | relasi belum ada.
    |
    | Tips Debugging:
    | - Jika jumlah_siswa tetap 0, pastikan tb_alumni punya kolom id_angkatan.
    | - Jika query gagal, cek tabel tb_alumni dan tb_angkatan tersedia di database.
    | - Jika angka tidak sesuai, verifikasi data alumni sudah punya id_angkatan yang benar.
    */
    public function ambilSemuaDenganJumlahSiswa(): array
    {
        $db = $this->db;

        if (! $db->tableExists('tb_alumni') || ! $db->fieldExists('id_angkatan', 'tb_alumni')) {
            return $this->select('tb_angkatan.*, 0 AS jumlah_siswa', false)
                ->where('status_aktif', 1)
                ->orderBy('tahun_lulus', 'DESC')
                ->findAll();
        }

        return $db->table('tb_angkatan a')
            ->select('a.*, COUNT(al.id_alumni) AS jumlah_siswa', false)
            ->join('tb_alumni al', 'al.id_angkatan = a.id_angkatan', 'left')
            ->where('a.status_aktif', 1)
            ->groupBy('a.id_angkatan')
            ->orderBy('a.tahun_lulus', 'DESC')
            ->get()
            ->getResultArray();
    }
}
