<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL RIWAYAT KERJA
|-------------------------------------------------------------------
| Model ini menangani operasi data riwayat pengalaman kerja pelamar
| untuk keperluan:
| 1. CV otomatis yang bisa di-generate dari sistem
| 2. Screening awal oleh Admin BKK/HRD
| 3. Analisis kesesuaian kandidat dengan lowongan
|
| Alur kerja: controller memanggil model ini untuk CRUD data riwayat
| kerja, termasuk validasi durasi kerja & query analisis.
|
| Tips Debugging:
| - Jika data tidak tersimpan, cek allowedFields pada model ini.
| - Jika query durasi gagal, cek tanggal_mulai & tanggal_selesai.
*/

class RiwayatKerjaModel extends Model
{
    protected $table            = 'tb_riwayat_kerja';
    protected $primaryKey       = 'id_riwayat';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_pelamar',
        'nama_perusahaan',
        'bidang_usaha',
        'lokasi',
        'posisi_jabatan',
        'tanggal_mulai',
        'tanggal_selesai',
        'masih_bekerja',
        'keterangan',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL SEMUA BY PELAMAR
    |-------------------------------------------------------------------
    | Method ini mengambil semua riwayat kerja milik seorang pelamar,
    | diurutkan dari yang paling baru berdasarkan tanggal mulai.
    | Alur kerja: controller memanggil method ini dengan id_pelamar,
    | lalu model mengembalikan array riwayat kerja.
    |
    | Tips Debugging:
    | - Jika data kosong padahal sudah ada, cek id_pelamar yang benar.
    */
    public function ambilByPelamar(int $idPelamar): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        return $this->where('id_pelamar', $idPelamar)
            ->orderBy('tanggal_mulai', 'DESC')
            ->findAll();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL SEMUA BY PELAMAR (ALIAS)
    |-------------------------------------------------------------------
    | Alias dari ambilByPelamar untuk keselarasan naming convention.
    */
    public function ambilSemuaByPelamar(int $idPelamar): array
    {
        return $this->ambilByPelamar($idPelamar);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD HITUNG DURASI KERJA
    |-------------------------------------------------------------------
    | Method ini menghitung total durasi pengalaman kerja pelamar
    | dalam satuan bulan. Digunakan untuk HR screening/matching.
    | Alur kerja: method menyeleksi semua riwayat kerja, hitung
    | interval tiap baris (tanggal_mulai ke tanggal_selesai atau
    | sekarang jika masih_bekerja=1), lalu jumlahkan.
    |
    | Tips Debugging:
    | - Jika durasi salah, cek apakah ada tanggal_selesai yang NULL.
    */
    public function hitungDurasiKerja(int $idPelamar): int
    {
        $riwayat = $this->ambilByPelamar($idPelamar);

        if (empty($riwayat)) {
            return 0;
        }

        $totalBulan = 0;

        foreach ($riwayat as $r) {
            try {
                $tanggalMulai = new \DateTime((string) $r['tanggal_mulai']);
                $tanggalSelesai = $r['tanggal_selesai']
                    ? new \DateTime((string) $r['tanggal_selesai'])
                    : new \DateTime('now');

                // Hitung interval dalam satuan bulan
                $interval = $tanggalMulai->diff($tanggalSelesai);
                $bulan = ($interval->y * 12) + $interval->m;

                $totalBulan += max(1, $bulan); // Minimum 1 bulan jika kurang dari 1 bulan
            } catch (\Throwable $th) {
                // Skip jika ada error parsing tanggal
                continue;
            }
        }

        return $totalBulan;
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL POSISI TERBARU
    |-------------------------------------------------------------------
    | Method ini mengambil riwayat kerja terbaru (yang paling baru
    | berdasarkan tanggal_mulai) dari seorang pelamar.
    | Alur kerja: method query 1 row pertama dengan ordering DESC.
    |
    | Tips Debugging:
    | - Jika hasilnya NULL padahal ada data, periksa tanggal_mulai.
    */
    public function ambilPosisiTerbaru(int $idPelamar): ?array
    {
        return $this->where('id_pelamar', $idPelamar)
            ->orderBy('tanggal_mulai', 'DESC')
            ->first();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL POSISI AKTIF
    |-------------------------------------------------------------------
    | Method ini mengambil posisi yang sedang aktif (masih_bekerja=1)
    | dari seorang pelamar. Biasanya hanya 1 record.
    | Alur kerja: method query dengan kondisi masih_bekerja=1.
    |
    | Tips Debugging:
    | - Jika hasilnya NULL, periksa apakah ada record dengan masih_bekerja=1.
    */
    public function ambilPosisiAktif(int $idPelamar): ?array
    {
        return $this->where('id_pelamar', $idPelamar)
            ->where('masih_bekerja', 1)
            ->first();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD CARI BY BIDANG USAHA
    |-------------------------------------------------------------------
    | Method ini mencari riwayat kerja pelamar berdasarkan bidang usaha.
    | Digunakan untuk matching dengan lowongan kerja yang mencari
    | kandidat dari bidang industri tertentu.
    | Alur kerja: controller memanggil method dengan bidang_usaha,
    | lalu model mengembalikan array riwayat yang cocok.
    |
    | Tips Debugging:
    | - Jika data kosong, periksa apakah bidang_usaha di database match.
    */
    public function cariByBidangUsaha(int $idPelamar, string $bidangUsaha): array
    {
        return $this->where('id_pelamar', $idPelamar)
            ->like('bidang_usaha', $bidangUsaha)
            ->orderBy('tanggal_mulai', 'DESC')
            ->findAll();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL DETAIL DENGAN DURASI
    |-------------------------------------------------------------------
    | Method helper ini mengambil 1 row riwayat kerja dan menambahkan
    | field durasi_bulan yang dihitung secara real-time. Berguna untuk
    | menampilkan durasi di UI tanpa perlu query terpisah.
    | Alur kerja: method query row, hitung durasi interval, merge field.
    |
    | Tips Debugging:
    | - Jika durasi_bulan tidak muncul, periksa keberadaan row terlebih dulu.
    */
    public function ambilDetailDenganDurasi(int $idRiwayat): ?array
    {
        $riwayat = $this->find($idRiwayat);

        if ($riwayat === null) {
            return null;
        }

        try {
            $tanggalMulai = new \DateTime((string) $riwayat['tanggal_mulai']);
            $tanggalSelesai = $riwayat['tanggal_selesai']
                ? new \DateTime((string) $riwayat['tanggal_selesai'])
                : new \DateTime('now');

            $interval = $tanggalMulai->diff($tanggalSelesai);
            $durasibulan = ($interval->y * 12) + $interval->m;

            $riwayat['durasi_bulan'] = max(1, $durasibulan);
        } catch (\Throwable $th) {
            $riwayat['durasi_bulan'] = 0;
        }

        return $riwayat;
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL DENGAN JOIN PELAMAR
    |-------------------------------------------------------------------
    | Method ini mengambil riwayat kerja dengan informasi pelamar
    | (join ke tb_pelamar dan tb_pengguna). Berguna untuk laporan
    | atau analisis yang membutuhkan data pelamar juga.
    | Alur kerja: method build query custom dengan multi-join.
    |
    | Tips Debugging:
    | - Jika kolom tidak ada, cek field yang di-select sudah sesuai.
    */
    public function ambilDenganJoinPelamar(int $idRiwayat): ?array
    {
        return $this->db->table($this->table . ' rk')
            ->select([
                'rk.id_riwayat',
                'rk.id_pelamar',
                'rk.nama_perusahaan',
                'rk.bidang_usaha',
                'rk.lokasi',
                'rk.posisi_jabatan',
                'rk.tanggal_mulai',
                'rk.tanggal_selesai',
                'rk.masih_bekerja',
                'rk.keterangan',
                'rk.dibuat_pada',
                'rk.diperbarui_pada',
                'p.id_pengguna',
                'p.account_id',
                'p.foto',
                'u.nama_lengkap',
                'u.email',
            ])
            ->join('tb_pelamar p', 'p.id_pelamar = rk.id_pelamar', 'inner')
            ->join('tb_pengguna u', 'u.id_pengguna = p.id_pengguna', 'inner')
            ->where('rk.id_riwayat', $idRiwayat)
            ->get()
            ->getRowArray();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL LAPORAN RIWAYAT KERJA
    |-------------------------------------------------------------------
    | Method ini mengambil laporan riwayat kerja semua pelamar dengan
    | informasi agregat (jumlah riwayat, durasi total, posisi terbaru).
    | Berguna untuk HR dashboard atau analisis statistik.
    | Alur kerja: method build query GROUP BY dengan aggregate functions.
    |
    | Tips Debugging:
    | - Jika hasil kosong, periksa apakah ada data di tb_riwayat_kerja.
    */
    public function ambilLaporanRiwayatKerja(): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        return $this->db->table($this->table . ' rk')
            ->select([
                'rk.id_pelamar',
                'COUNT(rk.id_riwayat) as jumlah_riwayat',
                'MAX(rk.tanggal_mulai) as tanggal_kerja_terakhir',
            ])
            ->groupBy('rk.id_pelamar')
            ->get()
            ->getResultArray();
    }
}
