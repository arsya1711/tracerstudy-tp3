<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL ALUMNI
|-------------------------------------------------------------------
| Model ini menangani akses data alumni yang terhubung ke pelamar,
| termasuk pengambilan data lengkap beserta angkatan dan kompetensi.
| Alur kerja: controller detail pelamar memanggil method
| ambilLengkapByPelamar() untuk mengisi sidebar dan kartu anggota.
|
| Tips Debugging:
| - Jika data alumni kosong, cek relasi tb_alumni.id_pelamar.
| - Jika tahun_angkatan tidak muncul, cek apakah kolom itu memang ada.
*/
class AlumniModel extends Model
{
    protected $table         = 'tb_alumni';
    protected $primaryKey    = 'id_alumni';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'id_pelamar',
        'id_angkatan',
        'id_kompetensi',
        'nis',
        'nisn',
        'no_ijazah',
        'status_verifikasi',
        'catatan_verifikasi',
        'diverifikasi_oleh',
        'diverifikasi_pada',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    public function ambilLengkapByPelamar($id_pelamar): ?array
    {
        if (! $this->db->tableExists('tb_alumni')) {
            return null;
        }

        $builder = $this->db->table('tb_alumni al');
        $selects = ['al.*'];

        if ($this->db->tableExists('tb_angkatan')) {
            $builder->join('tb_angkatan ang', 'ang.id_angkatan = al.id_angkatan', 'left');
            $selects[] = $this->db->fieldExists('tahun_lulus', 'tb_angkatan') ? 'ang.tahun_lulus' : 'NULL AS tahun_lulus';
            $selects[] = $this->db->fieldExists('tahun_angkatan', 'tb_angkatan') ? 'ang.tahun_angkatan' : 'NULL AS tahun_angkatan';
        } else {
            $selects[] = 'NULL AS tahun_lulus';
            $selects[] = 'NULL AS tahun_angkatan';
        }

        if ($this->db->tableExists('tb_kompetensi')) {
            $builder->join('tb_kompetensi k', 'k.id_kompetensi = al.id_kompetensi', 'left');
            $selects[] = $this->db->fieldExists('nama_kompetensi', 'tb_kompetensi') ? 'k.nama_kompetensi' : 'NULL AS nama_kompetensi';
            $selects[] = $this->db->fieldExists('akronim', 'tb_kompetensi') ? 'k.akronim' : 'NULL AS akronim';
        } else {
            $selects[] = 'NULL AS nama_kompetensi';
            $selects[] = 'NULL AS akronim';
        }

        return $builder
            ->select(implode(', ', $selects), false)
            ->where('al.id_pelamar', $id_pelamar)
            ->get()
            ->getRowArray();
    }
}
