<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL KOMPETENSI
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: model ini menangani akses data ke tabel
| tb_kompetensi termasuk pengambilan daftar kompetensi beserta jumlah
| keterserapan tracer alumni.
| Alur kerja: controller memanggil model ini untuk membaca, menambah,
| memperbarui, dan menonaktifkan data kompetensi pada database.
|
| Tips Debugging:
| - Jika field tidak tersimpan, cek allowedFields pada model ini.
| - Jika timestamp tidak terisi, cek useTimestamps dan nama field tanggal pada model.
*/
class KompetensiModel extends Model
{
    protected $table            = 'tb_kompetensi';
    protected $primaryKey       = 'id_kompetensi';
    protected $returnType       = 'array';
    protected $allowedFields    = ['nama_kompetensi', 'akronim', 'status_aktif'];
    protected $useTimestamps    = true;
    protected $createdField     = 'dibuat_pada';
    protected $updatedField     = 'diperbarui_pada';

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL SEMUA DENGAN KETERSERAPAN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengambil seluruh
    | kompetensi aktif dan menghitung jumlah data tracer alumni yang
    | terkait sebagai nilai keterserapan.
    | Alur kerja: controller memanggil method ini, model membangun
    | query LEFT JOIN antara tb_kompetensi dan tb_tracer_alumni, lalu
    | mengembalikan hasil dalam bentuk array.
    |
    | Tips Debugging:
    | - Jika kolom keterserapan selalu 0, cek relasi id_kompetensi di tb_tracer_alumni.
    | - Jika query error tabel tracer tidak ditemukan, cek apakah tb_tracer_alumni sudah tersedia di database.
    */
    public function ambilSemuaDenganKeterserapan(): array
    {
        $db = $this->db;

        if (! $db->tableExists('tb_tracer_alumni')) {
            return $this->select('tb_kompetensi.*, 0 AS keterserapan', false)
                ->where('status_aktif', 1)
                ->orderBy('nama_kompetensi', 'ASC')
                ->findAll();
        }

        return $db->table('tb_kompetensi k')
            ->select('k.*, COUNT(t.id_alumni) AS keterserapan', false)
            ->join('tb_alumni t', 't.id_kompetensi = k.id_kompetensi', 'left')
            ->where('k.status_aktif', 1)
            ->groupBy('k.id_kompetensi')
            ->orderBy('k.nama_kompetensi', 'ASC')
            ->get()
            ->getResultArray();
    }
}
