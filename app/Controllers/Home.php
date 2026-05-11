<?php

namespace App\Controllers;

use Config\Database;

/*
|-------------------------------------------------------------------
| CONTROLLER LANDING PAGE PUBLIK
|-------------------------------------------------------------------
| Controller ini menampilkan halaman pertama aplikasi saat user
| membuka http://localhost:8080 setelah menjalankan php spark serve.
| Landing page mengambil inspirasi struktur Metronic landing1.html,
| tetapi kontennya disesuaikan untuk BKK dan Tracer Study.
|
| Alur kerja:
| 1. User membuka route /.
| 2. Controller membaca filter pencarian dari query string.
| 3. Controller mengambil statistik ringkas dan lowongan aktif terbaru.
| 4. Data dikirim ke view landing/index.php.
|
| Tips Debugging:
| - Jika halaman pertama masih login, periksa route / di app/Config/Routes.php.
| - Jika lowongan kosong, cek tb_lowongan.status harus aktif dan deadline belum lewat.
*/
class Home extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function index(): string
    {
        $filters = [
            'search' => trim((string) $this->request->getGet('q')),
            'kota' => trim((string) $this->request->getGet('kota')),
            'jenis_pekerjaan' => trim((string) $this->request->getGet('jenis_pekerjaan')),
        ];

        return view('landing/index', [
            'title' => 'BKK & Tracer Study SMK Teratai Putih Global 4',
            'filters' => $filters,
            'lowongan' => $this->ambilLowonganTerbaru($filters),
            'daftarKota' => $this->ambilDaftarKotaLowongan(),
            'statistik' => [
                'pelamar' => $this->hitungTabel('tb_pelamar'),
                'alumni' => $this->hitungTabel('tb_alumni'),
                'dudi' => $this->hitungPerusahaanAktif(),
                'lowongan' => $this->hitungLowonganAktif(),
                'lamaran' => $this->hitungTabel('tb_lamaran'),
                'tracer' => $this->hitungTabel('tb_tracer_alumni'),
            ],
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | QUERY LOWONGAN TERBARU UNTUK LANDING
    |-------------------------------------------------------------------
    | Query ini mengambil lowongan aktif yang masih bisa dilamar. Filter
    | pencarian sengaja dibuat ringan agar landing tetap cepat.
    */
    protected function ambilLowonganTerbaru(array $filters): array
    {
        if (! $this->db->tableExists('tb_lowongan') || ! $this->db->tableExists('tb_perusahaan')) {
            return [];
        }

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $builder = $this->db->table('tb_lowongan l')
            ->select([
                'l.id_lowongan',
                'l.judul_lowongan',
                'l.posisi',
                'l.slug_lowongan',
                'l.flyer_lowongan',
                'l.jenis_pekerjaan',
                'l.sistem_kerja',
                'l.lokasi_kerja',
                'l.batas_lamaran',
                'l.rentang_gaji',
                'p.nama_perusahaan',
                'p.kota',
            ])
            ->join('tb_perusahaan p', 'p.id_perusahaan = l.id_perusahaan', 'inner')
            ->where('l.status', 'aktif')
            ->groupStart()
                ->where('l.batas_lamaran IS NULL', null, false)
                ->orWhere('l.batas_lamaran >=', $today)
            ->groupEnd()
            ->groupStart()
                ->where('l.tayang_hingga IS NULL', null, false)
                ->orWhere('l.tayang_hingga >=', $now)
            ->groupEnd();

        if ($this->db->fieldExists('status_aktif', 'tb_perusahaan')) {
            $builder->where('p.status_aktif', 1);
        }

        $keyword = trim((string) ($filters['search'] ?? ''));
        if ($keyword !== '') {
            $builder->groupStart()
                ->like('l.judul_lowongan', $keyword)
                ->orLike('l.posisi', $keyword)
                ->orLike('p.nama_perusahaan', $keyword)
                ->orLike('l.lokasi_kerja', $keyword)
                ->groupEnd();
        }

        if (($filters['kota'] ?? '') !== '') {
            $builder->where('p.kota', (string) $filters['kota']);
        }

        if (($filters['jenis_pekerjaan'] ?? '') !== '') {
            $builder->where('l.jenis_pekerjaan', (string) $filters['jenis_pekerjaan']);
        }

        return $builder
            ->orderBy('l.batas_lamaran', 'ASC')
            ->orderBy('l.id_lowongan', 'DESC')
            ->limit(6)
            ->get()
            ->getResultArray();
    }

    protected function ambilDaftarKotaLowongan(): array
    {
        if (! $this->db->tableExists('tb_perusahaan')) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn(array $row): string => trim((string) ($row['kota'] ?? '')),
            $this->db->table('tb_perusahaan')
                ->distinct()
                ->select('kota')
                ->where('kota IS NOT NULL', null, false)
                ->orderBy('kota', 'ASC')
                ->get()
                ->getResultArray()
        )));
    }

    protected function hitungTabel(string $table): int
    {
        if (! $this->db->tableExists($table)) {
            return 0;
        }

        return (int) $this->db->table($table)->countAllResults();
    }

    protected function hitungPerusahaanAktif(): int
    {
        if (! $this->db->tableExists('tb_perusahaan')) {
            return 0;
        }

        $builder = $this->db->table('tb_perusahaan');

        if ($this->db->fieldExists('status_aktif', 'tb_perusahaan')) {
            $builder->where('status_aktif', 1);
        }

        return (int) $builder->countAllResults();
    }

    protected function hitungLowonganAktif(): int
    {
        if (! $this->db->tableExists('tb_lowongan')) {
            return 0;
        }

        return (int) $this->db->table('tb_lowongan')
            ->where('status', 'aktif')
            ->countAllResults();
    }
}
