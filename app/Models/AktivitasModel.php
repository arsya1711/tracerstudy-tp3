<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL AKTIVITAS
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: model ini menangani operasi data pada
| tabel tb_aktivitas termasuk pengambilan daftar aktivitas aktif
| beserta jumlah alumni yang terhubung.
| Alur kerja: controller memanggil model ini untuk membaca, menambah,
| memperbarui, dan menonaktifkan data aktivitas di database.
|
| Tips Debugging:
| - Jika data tidak tersimpan, cek allowedFields pada model ini.
| - Jika timestamp kosong, cek useTimestamps dan nama field tanggalnya.
*/
class AktivitasModel extends Model
{
    protected $table         = 'tb_aktivitas';
    protected $primaryKey    = 'id_aktivitas';
    protected $returnType    = 'array';
    protected $allowedFields = ['nama_aktivitas', 'keterangan', 'status_aktif'];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL SEMUA DENGAN JUMLAH ALUMNI
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengambil seluruh
    | aktivitas aktif dan menghitung jumlah alumni dari relasi
    | id_aktivitas pada tb_tracer_alumni.
    | Alur kerja: controller memanggil method ini, model mengecek
    | keberadaan tabel tracer, lalu menjalankan query LEFT JOIN
    | dan GROUP BY atau mengembalikan jumlah 0 bila tabel belum ada.
    |
    | Tips Debugging:
    | - Jika jumlah_alumni selalu 0, cek kolom id_aktivitas dan id_tracer pada tb_tracer_alumni.
    | - Jika query gagal, cek tabel tb_aktivitas dan tb_tracer_alumni tersedia di database.
    */
    public function ambilSemuaDenganJumlahAlumni(): array
    {
        $db = $this->db;

        if (
            ! $db->tableExists('tb_tracer_alumni')
            || ! $db->fieldExists('id_aktivitas', 'tb_tracer_alumni')
            || ! $db->fieldExists('id_tracer', 'tb_tracer_alumni')
        ) {
            return $this->select('tb_aktivitas.*, 0 AS jumlah_alumni', false)
                ->where('status_aktif', 1)
                ->orderBy('nama_aktivitas', 'ASC')
                ->findAll();
        }

        return $db->table('tb_aktivitas a')
            ->select('a.*, COUNT(t.id_tracer) AS jumlah_alumni', false)
            ->join('tb_tracer_alumni t', 't.id_aktivitas = a.id_aktivitas', 'left')
            ->where('a.status_aktif', 1)
            ->groupBy('a.id_aktivitas')
            ->orderBy('a.nama_aktivitas', 'ASC')
            ->get()
            ->getResultArray();
    }
}
