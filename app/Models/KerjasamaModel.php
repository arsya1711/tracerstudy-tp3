<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL KERJASAMA
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: model ini menangani operasi data pada
| tabel tb_kerjasama termasuk pengambilan daftar jenis kerjasama aktif
| beserta jumlah data MoU yang terhubung.
| Alur kerja: controller memanggil model ini untuk membaca, menambah,
| memperbarui, dan menonaktifkan data kerjasama di database.
|
| Tips Debugging:
| - Jika data tidak tersimpan, cek allowedFields pada model ini.
| - Jika timestamp kosong, cek useTimestamps dan nama field tanggalnya.
*/
class KerjasamaModel extends Model
{
    protected $table         = 'tb_kerjasama';
    protected $primaryKey    = 'id_kerjasama';
    protected $returnType    = 'array';
    protected $allowedFields = ['nama_kerjasama', 'slug_kerjasama', 'deskripsi', 'status_aktif'];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL SEMUA DENGAN JUMLAH MOU
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengambil seluruh jenis
    | kerjasama aktif dan menghitung jumlah MoU dari relasi id_kerjasama
    | pada tb_mou.
    | Alur kerja: controller memanggil method ini, model mengecek
    | keberadaan tabel dan field relasi, lalu menjalankan query LEFT JOIN
    | atau mengembalikan jumlah 0 bila tabel relasi belum ada.
    |
    | Tips Debugging:
    | - Jika jumlah_mou selalu 0, cek kolom id_kerjasama dan id_mou pada tb_mou.
    | - Jika query gagal, cek tabel tb_kerjasama dan tb_mou tersedia di database.
    */
    public function ambilSemuaDenganJumlahMou(): array
    {
        $db = $this->db;

        if (
            ! $db->tableExists('tb_mou')
            || ! $db->fieldExists('id_kerjasama', 'tb_mou')
            || ! $db->fieldExists('id_mou', 'tb_mou')
        ) {
            return $this->select('tb_kerjasama.*, 0 AS jumlah_mou', false)
                ->where('status_aktif', 1)
                ->orderBy('nama_kerjasama', 'ASC')
                ->findAll();
        }

        return $db->table('tb_kerjasama k')
            ->select('k.*, COUNT(m.id_mou) AS jumlah_mou', false)
            ->join('tb_mou m', 'm.id_kerjasama = k.id_kerjasama', 'left')
            ->where('k.status_aktif', 1)
            ->groupBy('k.id_kerjasama')
            ->orderBy('k.nama_kerjasama', 'ASC')
            ->get()
            ->getResultArray();
    }
}
