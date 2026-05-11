<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL LAMARAN
|-------------------------------------------------------------------
| Model ini menangani transaksi utama lamaran kerja pelamar, mulai
| dari pengecekan duplikasi lamaran sampai riwayat lamaran milik akun
| pelamar yang sedang login.
|
| Alur kerja:
| 1. Controller membuat record baru saat pelamar submit lamaran.
| 2. Riwayat lamaran pelamar dibaca lagi dari model ini.
| 3. Status terkini disimpan di tb_lamaran, sedangkan histori detail
|    disimpan terpisah di tb_lamaran_status.
|
| Tips Debugging:
| - Jika pelamar bisa melamar dua kali, cek unique key kombinasi
|   id_pelamar dan id_lowongan di migration tb_lamaran.
| - Jika riwayat lamaran kosong, cek join ke tb_lowongan dan
|   tb_perusahaan serta pastikan migration sudah dijalankan.
*/
class LamaranModel extends Model
{
    protected $table         = 'tb_lamaran';
    protected $primaryKey    = 'id_lamaran';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'id_pelamar',
        'id_lowongan',
        'dibuat_oleh',
        'status',
        'tanggal_melamar',
        'batas_perbaikan_berkas',
        'tanggal_diproses',
        'tanggal_wawancara',
        'tanggal_keputusan',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    /*
    |-------------------------------------------------------------------
    | RIWAYAT LAMARAN BERDASARKAN PELAMAR
    |-------------------------------------------------------------------
    | Method ini dipakai di dashboard/profil pelamar untuk menampilkan
    | daftar lamaran lengkap dengan lowongan dan nama perusahaan.
    */
    public function ambilByPelamar(int $idPelamar): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        return $this->db->table('tb_lamaran l')
            ->select([
                'l.id_lamaran',
                'l.id_pelamar',
                'l.id_lowongan',
                'l.status',
                'l.tanggal_melamar',
                'l.batas_perbaikan_berkas',
                'l.tanggal_wawancara',
                'lw.judul_lowongan',
                'lw.posisi',
                'lw.slug_lowongan',
                'p.nama_perusahaan',
            ])
            ->join('tb_lowongan lw', 'lw.id_lowongan = l.id_lowongan', 'left')
            ->join('tb_perusahaan p', 'p.id_perusahaan = lw.id_perusahaan', 'left')
            ->where('l.id_pelamar', $idPelamar)
            ->orderBy('l.tanggal_melamar', 'DESC')
            ->orderBy('l.id_lamaran', 'DESC')
            ->get()
            ->getResultArray();
    }

    /*
    |-------------------------------------------------------------------
    | CEK DUPLIKAT LAMARAN
    |-------------------------------------------------------------------
    | Pelamar hanya boleh memiliki satu lamaran untuk satu lowongan.
    */
    public function sudahPernahMelamar(int $idPelamar, int $idLowongan): bool
    {
        if (! $this->db->tableExists($this->table)) {
            return false;
        }

        return $this->where('id_pelamar', $idPelamar)
            ->where('id_lowongan', $idLowongan)
            ->countAllResults() > 0;
    }

    /*
    |-------------------------------------------------------------------
    | AMBIL DETAIL LAMARAN MILIK PELAMAR
    |-------------------------------------------------------------------
    | Helper ini disiapkan agar modul berikutnya bisa membuka detail
    | lamaran tanpa risiko akses silang antar pelamar.
    */
    public function ambilDetailMilikPelamar(int $idLamaran, int $idPelamar): ?array
    {
        if (! $this->db->tableExists($this->table)) {
            return null;
        }

        return $this->db->table('tb_lamaran l')
            ->select([
                'l.*',
                'lw.judul_lowongan',
                'lw.posisi',
                'lw.slug_lowongan',
                'lw.flyer_lowongan',
                'lw.deskripsi_pekerjaan',
                'lw.kualifikasi',
                'lw.jenis_pekerjaan',
                'lw.sistem_kerja',
                'lw.pendidikan_min',
                'lw.pengalaman_min',
                'lw.rentang_gaji',
                'lw.lokasi_kerja',
                'lw.batas_lamaran',
                'p.nama_perusahaan',
                'p.kota',
                'p.no_telepon',
                'p.email AS email_perusahaan',
            ])
            ->join('tb_lowongan lw', 'lw.id_lowongan = l.id_lowongan', 'left')
            ->join('tb_perusahaan p', 'p.id_perusahaan = lw.id_perusahaan', 'left')
            ->where('l.id_lamaran', $idLamaran)
            ->where('l.id_pelamar', $idPelamar)
            ->get()
            ->getRowArray();
    }

    /*
    |-------------------------------------------------------------------
    | DAFTAR LAMARAN UNTUK SUPERADMIN
    |-------------------------------------------------------------------
    | Method ini dipakai halaman Data Lamaran agar Super Admin bisa
    | memantau siapa melamar ke lowongan mana, kapan submit, dan status
    | proses terkininya.
    |
    | Tips Debugging:
    | - Jika nama pelamar kosong, cek join tb_pelamar ke tb_pengguna.
    | - Jika data perusahaan tidak muncul, cek join tb_lowongan ke
    |   tb_perusahaan serta foreign key id_perusahaan.
    */
    public function ambilDaftarUntukSuperadmin(array $filters = []): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        $builder = $this->db->table('tb_lamaran l')
            ->select([
                'l.id_lamaran',
                'l.id_pelamar',
                'l.id_lowongan',
                'l.status',
                'l.tanggal_melamar',
                'l.batas_perbaikan_berkas',
                'l.tanggal_wawancara',
                'p.account_id',
                'u.nama_lengkap',
                'u.email',
                'lw.judul_lowongan',
                'lw.posisi',
                'lw.slug_lowongan',
                'lw.flyer_lowongan',
                'lw.deskripsi_pekerjaan',
                'lw.kualifikasi',
                'lw.jenis_pekerjaan',
                'lw.sistem_kerja',
                'lw.pendidikan_min',
                'lw.pengalaman_min',
                'lw.rentang_gaji',
                'lw.lokasi_kerja',
                'lw.batas_lamaran',
                'lw.tayang_hingga',
                'pr.nama_perusahaan',
                'pr.id_perusahaan',
            ])
            ->join('tb_pelamar p', 'p.id_pelamar = l.id_pelamar', 'inner')
            ->join('tb_pengguna u', 'u.id_pengguna = p.id_pengguna', 'inner')
            ->join('tb_lowongan lw', 'lw.id_lowongan = l.id_lowongan', 'left')
            ->join('tb_perusahaan pr', 'pr.id_perusahaan = lw.id_perusahaan', 'left');

        $keyword = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $idPerusahaan = (int) ($filters['id_perusahaan'] ?? 0);

        if ($status !== '') {
            $builder->where('l.status', $status);
        }

        if ($idPerusahaan > 0) {
            $builder->where('pr.id_perusahaan', $idPerusahaan);
        }

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('u.nama_lengkap', $keyword)
                ->orLike('u.email', $keyword)
                ->orLike('p.account_id', $keyword)
                ->orLike('lw.judul_lowongan', $keyword)
                ->orLike('lw.posisi', $keyword)
                ->orLike('pr.nama_perusahaan', $keyword)
                ->groupEnd();
        }

        return $builder
            ->orderBy('l.tanggal_melamar', 'DESC')
            ->orderBy('l.id_lamaran', 'DESC')
            ->get()
            ->getResultArray();
    }

    /*
    |-------------------------------------------------------------------
    | DAFTAR LAMARAN BERDASARKAN PERUSAHAAN
    |-------------------------------------------------------------------
    | Method ini dipakai Admin DUDI/HRD untuk melihat lamaran yang
    | masuk hanya pada lowongan milik perusahaannya sendiri.
    |
    | Tips Debugging:
    | - Jika data kosong, cek tb_lowongan.id_perusahaan dan relasi akun
    |   admin DUDI pada tb_perusahaan.id_pengguna.
    */
    public function ambilDaftarUntukPerusahaan(int $idPerusahaan, array $filters = []): array
    {
        if (! $this->db->tableExists($this->table) || $idPerusahaan <= 0) {
            return [];
        }

        $builder = $this->baseQueryLamaranLengkap()
            ->where('pr.id_perusahaan', $idPerusahaan);

        $keyword = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($status !== '') {
            $builder->where('l.status', $status);
        }

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('u.nama_lengkap', $keyword)
                ->orLike('u.email', $keyword)
                ->orLike('p.account_id', $keyword)
                ->orLike('lw.judul_lowongan', $keyword)
                ->orLike('lw.posisi', $keyword)
                ->groupEnd();
        }

        return $builder
            ->orderBy('l.tanggal_melamar', 'DESC')
            ->orderBy('l.id_lamaran', 'DESC')
            ->get()
            ->getResultArray();
    }

    /*
    |-------------------------------------------------------------------
    | DETAIL LAMARAN BERDASARKAN PERUSAHAAN
    |-------------------------------------------------------------------
    | Helper ini memastikan Admin DUDI hanya bisa membuka detail lamaran
    | dari lowongan yang memang dimiliki perusahaannya.
    */
    public function ambilDetailUntukPerusahaan(int $idLamaran, int $idPerusahaan): ?array
    {
        if (! $this->db->tableExists($this->table) || $idLamaran <= 0 || $idPerusahaan <= 0) {
            return null;
        }

        return $this->baseQueryLamaranLengkap()
            ->where('l.id_lamaran', $idLamaran)
            ->where('pr.id_perusahaan', $idPerusahaan)
            ->get()
            ->getRowArray();
    }

    /*
    |-------------------------------------------------------------------
    | RINGKASAN LAMARAN PERUSAHAAN
    |-------------------------------------------------------------------
    | Dipakai dashboard Admin DUDI untuk menampilkan angka ringkas
    | tanpa perlu melakukan banyak query berulang di controller.
    */
    public function hitungRingkasanPerusahaan(int $idPerusahaan): array
    {
        $default = [
            'total' => 0,
            'menunggu_verifikasi' => 0,
            'perlu_perbaikan_berkas' => 0,
            'diproses' => 0,
            'wawancara' => 0,
            'diterima' => 0,
            'ditolak' => 0,
        ];

        if (! $this->db->tableExists($this->table) || $idPerusahaan <= 0) {
            return $default;
        }

        $rows = $this->db->table('tb_lamaran l')
            ->select('l.status, COUNT(*) AS total')
            ->join('tb_lowongan lw', 'lw.id_lowongan = l.id_lowongan', 'inner')
            ->where('lw.id_perusahaan', $idPerusahaan)
            ->groupBy('l.status')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $jumlah = (int) ($row['total'] ?? 0);
            $default['total'] += $jumlah;

            if (array_key_exists($status, $default)) {
                $default[$status] = $jumlah;
            }
        }

        return $default;
    }

    /*
    |-------------------------------------------------------------------
    | DETAIL LAMARAN UNTUK SUPERADMIN
    |-------------------------------------------------------------------
    | Mengambil satu lamaran lengkap dengan data pelamar, lowongan,
    | dan perusahaan untuk kebutuhan modal detail/review.
    */
    public function ambilDetailUntukSuperadmin(int $idLamaran): ?array
    {
        if (! $this->db->tableExists($this->table)) {
            return null;
        }

        return $this->db->table('tb_lamaran l')
            ->select([
                'l.*',
                'p.account_id',
                'u.nama_lengkap',
                'u.email',
                'u.nomor_telepon',
                'lw.judul_lowongan',
                'lw.posisi',
                'lw.slug_lowongan',
                'lw.flyer_lowongan',
                'lw.deskripsi_pekerjaan',
                'lw.kualifikasi',
                'lw.jenis_pekerjaan',
                'lw.sistem_kerja',
                'lw.pendidikan_min',
                'lw.pengalaman_min',
                'lw.rentang_gaji',
                'lw.lokasi_kerja',
                'lw.batas_lamaran',
                'lw.tayang_hingga',
                'pr.id_perusahaan',
                'pr.nama_perusahaan',
            ])
            ->join('tb_pelamar p', 'p.id_pelamar = l.id_pelamar', 'inner')
            ->join('tb_pengguna u', 'u.id_pengguna = p.id_pengguna', 'inner')
            ->join('tb_lowongan lw', 'lw.id_lowongan = l.id_lowongan', 'left')
            ->join('tb_perusahaan pr', 'pr.id_perusahaan = lw.id_perusahaan', 'left')
            ->where('l.id_lamaran', $idLamaran)
            ->get()
            ->getRowArray();
    }

    protected function baseQueryLamaranLengkap(): \CodeIgniter\Database\BaseBuilder
    {
        return $this->db->table('tb_lamaran l')
            ->select([
                'l.id_lamaran',
                'l.id_pelamar',
                'l.id_lowongan',
                'l.status',
                'l.tanggal_melamar',
                'l.batas_perbaikan_berkas',
                'l.tanggal_diproses',
                'l.tanggal_wawancara',
                'l.tanggal_keputusan',
                'p.account_id',
                'p.foto',
                'p.jenis_kelamin',
                'p.tempat_lahir',
                'p.tanggal_lahir',
                'p.alamat',
                'p.nomer_nik',
                'u.nama_lengkap',
                'u.email',
                'u.nomor_telepon',
                'lw.judul_lowongan',
                'lw.posisi',
                'lw.slug_lowongan',
                'lw.flyer_lowongan',
                'lw.deskripsi_pekerjaan',
                'lw.kualifikasi',
                'lw.jenis_pekerjaan',
                'lw.sistem_kerja',
                'lw.pendidikan_min',
                'lw.pengalaman_min',
                'lw.rentang_gaji',
                'lw.lokasi_kerja',
                'lw.batas_lamaran',
                'lw.tayang_hingga',
                'pr.id_perusahaan',
                'pr.nama_perusahaan',
            ])
            ->join('tb_pelamar p', 'p.id_pelamar = l.id_pelamar', 'inner')
            ->join('tb_pengguna u', 'u.id_pengguna = p.id_pengguna', 'inner')
            ->join('tb_lowongan lw', 'lw.id_lowongan = l.id_lowongan', 'left')
            ->join('tb_perusahaan pr', 'pr.id_perusahaan = lw.id_perusahaan', 'left');
    }

    /*
    |-------------------------------------------------------------------
    | DAFTAR DUDI PADA DATA LAMARAN
    |-------------------------------------------------------------------
    | Filter DUDI di halaman Data Lamaran memakai helper ini agar opsi
    | yang muncul benar-benar hanya perusahaan yang sudah punya lamaran.
    */
    public function ambilDaftarPerusahaanDenganLamaran(): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        return $this->db->table('tb_lamaran l')
            ->distinct()
            ->select('pr.id_perusahaan, pr.nama_perusahaan')
            ->join('tb_lowongan lw', 'lw.id_lowongan = l.id_lowongan', 'inner')
            ->join('tb_perusahaan pr', 'pr.id_perusahaan = lw.id_perusahaan', 'inner')
            ->orderBy('pr.nama_perusahaan', 'ASC')
            ->get()
            ->getResultArray();
    }
}
