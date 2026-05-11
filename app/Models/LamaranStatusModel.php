<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL LAMARAN STATUS
|-------------------------------------------------------------------
| Model ini menyimpan histori perpindahan status lamaran sebagai audit
| trail yang terpisah dari status utama di tabel tb_lamaran.
|
| Tips Debugging:
| - Jika status utama berubah tetapi histori kosong, cek controller
|   update status sudah memanggil insert ke model ini.
*/
class LamaranStatusModel extends Model
{
    protected $table         = 'tb_lamaran_status';
    protected $primaryKey    = 'id_status';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'id_lamaran',
        'status_lama',
        'status_baru',
        'catatan',
        'diubah_oleh',
        'dibuat_pada',
    ];
    protected $useTimestamps = false;

    /*
    |-------------------------------------------------------------------
    | RIWAYAT STATUS BERDASARKAN LAMARAN
    |-------------------------------------------------------------------
    | Super Admin memakai method ini untuk melihat histori perubahan
    | status lamaran dari awal submit sampai keputusan terbaru.
    */
    public function ambilByLamaran(int $idLamaran): array
    {
        return $this->db->table('tb_lamaran_status ls')
            ->select([
                'ls.*',
                'u.nama_lengkap AS diubah_oleh_nama',
            ])
            ->join('tb_pengguna u', 'u.id_pengguna = ls.diubah_oleh', 'left')
            ->where('ls.id_lamaran', $idLamaran)
            ->orderBy('ls.dibuat_pada', 'DESC')
            ->orderBy('ls.id_status', 'DESC')
            ->get()
            ->getResultArray();
    }
}
