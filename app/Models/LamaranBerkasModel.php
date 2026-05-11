<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL LAMARAN BERKAS
|-------------------------------------------------------------------
| Model ini menyimpan snapshot berkas yang benar-benar dipakai saat
| pelamar submit satu lamaran tertentu.
|
| Tips Debugging:
| - Jika file lamaran lama ikut berubah, cek apakah data snapshot
|   benar-benar disimpan ke tb_lamaran_berkas, bukan ke tb_berkas.
*/
class LamaranBerkasModel extends Model
{
    protected $table         = 'tb_lamaran_berkas';
    protected $primaryKey    = 'id_lamaran_berkas';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'id_lamaran',
        'id_berkas',
        'id_jenis_berkas',
        'nama_file_snapshot',
        'path_file_snapshot',
        'ukuran_file_snapshot',
        'tipe_mime_snapshot',
        'wajib_saat_submit',
        'status_review',
        'catatan_review',
        'ditinjau_oleh',
        'ditinjau_pada',
        'dibuat_pada',
    ];
    protected $useTimestamps = false;

    /*
    |-------------------------------------------------------------------
    | DOKUMEN SNAPSHOT BERDASARKAN LAMARAN
    |-------------------------------------------------------------------
    | Method ini mengembalikan semua dokumen snapshot yang dilampirkan
    | pada satu lamaran lengkap dengan nama jenis berkasnya.
    |
    | Tips Debugging:
    | - Jika nama berkas kosong, cek join ke tb_jenis_berkas.
    */
    public function ambilByLamaran(int $idLamaran): array
    {
        return $this->db->table('tb_lamaran_berkas lb')
            ->select([
                'lb.*',
                'jb.nama_berkas',
                'jb.slug_berkas',
            ])
            ->join('tb_jenis_berkas jb', 'jb.id_jenis_berkas = lb.id_jenis_berkas', 'left')
            ->where('lb.id_lamaran', $idLamaran)
            ->orderBy('jb.wajib', 'DESC')
            ->orderBy('lb.id_lamaran_berkas', 'ASC')
            ->get()
            ->getResultArray();
    }
}
