<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;

/*
|-------------------------------------------------------------------
| CONTROLLER DATA TRACER ALUMNI
|-------------------------------------------------------------------
| Controller ini menangani halaman laporan tracer alumni untuk Super
| Admin. Halaman ini berisi tabel tracer, filter, tombol cetak,
| grafik batang, dan grafik donut.
|
| Alur kerja:
| 1. Admin membuka menu Data Tracer Alumni.
| 2. Controller membaca filter dari query string.
| 3. Data tracer alumni diambil dari relasi alumni, pengguna,
|    angkatan, kompetensi, dan aktivitas.
| 4. View menampilkan tabel serta grafik berdasarkan data yang sama.
|
| Tips Debugging:
| - Jika tabel kosong, cek tb_tracer_alumni dan relasi tb_alumni.
| - Jika nama alumni kosong, cek relasi tb_alumni -> tb_pengguna.
| - Jika grafik tidak tampil, cek ApexCharts pada bundle Metronic.
*/
class TracerController extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /*
    |-------------------------------------------------------------------
    | HALAMAN DATA TRACER ALUMNI
    |-------------------------------------------------------------------
    | Menampilkan data tracer alumni lengkap dengan filter dan grafik
    | analitik. Grafik mengikuti hasil filter agar laporan konsisten.
    */
    public function index(): string|RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $filters = [
            'search'        => trim((string) $this->request->getGet('q')),
            'id_angkatan'   => (int) ($this->request->getGet('id_angkatan') ?? 0),
            'id_kompetensi' => (int) ($this->request->getGet('id_kompetensi') ?? 0),
            'id_aktivitas'  => (int) ($this->request->getGet('id_aktivitas') ?? 0),
            'status'        => trim((string) $this->request->getGet('status')),
        ];

        $tracer = $this->ambilDataTracer($filters);

        return view('superadmin/tracer/index', [
            'title'            => $this->getPageTitle(),
            'tracer'           => $tracer,
            'filters'          => $filters,
            'daftarAngkatan'   => $this->ambilDaftarAngkatan(),
            'daftarKompetensi' => $this->ambilDaftarKompetensi(),
            'daftarAktivitas'  => $this->ambilDaftarAktivitas(),
            'daftarStatus'     => $this->ambilDaftarStatusTracer(),
            'grafikAktivitas'  => $this->bangunGrafikAktivitas($tracer),
            'grafikAngkatan'   => $this->bangunGrafikAngkatan($tracer),
            'dashboardUrl'     => $this->getDashboardUrl(),
            'tracerBaseUrl'    => $this->getTracerBaseUrl(),
            'tracerRoleLabel'  => $this->getTracerRoleLabel(),
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | QUERY DATA TRACER ALUMNI
    |-------------------------------------------------------------------
    | Query ini menggabungkan tabel tracer dengan data identitas alumni
    | agar halaman laporan tidak perlu melakukan query tambahan di view.
    */
    protected function ambilDataTracer(array $filters): array
    {
        foreach (['tb_tracer_alumni', 'tb_alumni', 'tb_pengguna'] as $table) {
            if (! $this->db->tableExists($table)) {
                return [];
            }
        }

        $builder = $this->db->table('tb_tracer_alumni t')
            ->select([
                't.*',
                'al.id_pengguna',
                'al.id_angkatan',
                'al.id_kompetensi',
                'al.nis',
                'al.nisn',
                'al.status_verifikasi',
                'CONCAT("ALM-", LPAD(al.id_alumni, 5, "0")) AS account_id',
                'u.nama_lengkap',
                'u.email',
                'ang.tahun_lulus',
                'k.nama_kompetensi',
                'k.akronim',
                'a.nama_aktivitas',
            ])
            ->join('tb_alumni al', 'al.id_alumni = t.id_alumni', 'inner')
            ->join('tb_pengguna u', 'u.id_pengguna = al.id_pengguna', 'inner')
            ->join('tb_angkatan ang', 'ang.id_angkatan = al.id_angkatan', 'left')
            ->join('tb_kompetensi k', 'k.id_kompetensi = al.id_kompetensi', 'left')
            ->join('tb_aktivitas a', 'a.id_aktivitas = t.id_aktivitas', 'left');

        if (($filters['id_angkatan'] ?? 0) > 0) {
            $builder->where('al.id_angkatan', (int) $filters['id_angkatan']);
        }

        if (($filters['id_kompetensi'] ?? 0) > 0) {
            $builder->where('al.id_kompetensi', (int) $filters['id_kompetensi']);
        }

        if (($filters['id_aktivitas'] ?? 0) > 0) {
            $builder->where('t.id_aktivitas', (int) $filters['id_aktivitas']);
        }

        if (($filters['status'] ?? '') !== '') {
            $builder->where('t.status', (string) $filters['status']);
        }

        $keyword = trim((string) ($filters['search'] ?? ''));
        if ($keyword !== '') {
            $builder->groupStart()
                ->like('u.nama_lengkap', $keyword)
                ->orLike('u.email', $keyword)
                ->orLike('al.nis', $keyword)
                ->orLike('k.nama_kompetensi', $keyword)
                ->orLike('k.akronim', $keyword)
                ->orLike('a.nama_aktivitas', $keyword)
                ->groupEnd();
        }

        return $builder
            ->orderBy('t.diperbarui_pada', 'DESC')
            ->orderBy('t.id_tracer', 'DESC')
            ->get()
            ->getResultArray();
    }

    protected function ambilDaftarAngkatan(): array
    {
        if (! $this->db->tableExists('tb_angkatan')) {
            return [];
        }

        $builder = $this->db->table('tb_angkatan')
            ->select('id_angkatan, tahun_lulus');

        if ($this->db->fieldExists('status_aktif', 'tb_angkatan')) {
            $builder->where('status_aktif', 1);
        }

        return $builder->orderBy('tahun_lulus', 'DESC')->get()->getResultArray();
    }

    protected function ambilDaftarKompetensi(): array
    {
        if (! $this->db->tableExists('tb_kompetensi')) {
            return [];
        }

        $builder = $this->db->table('tb_kompetensi')
            ->select('id_kompetensi, nama_kompetensi, akronim');

        if ($this->db->fieldExists('status_aktif', 'tb_kompetensi')) {
            $builder->where('status_aktif', 1);
        }

        return $builder->orderBy('nama_kompetensi', 'ASC')->get()->getResultArray();
    }

    protected function ambilDaftarAktivitas(): array
    {
        if (! $this->db->tableExists('tb_aktivitas')) {
            return [];
        }

        $builder = $this->db->table('tb_aktivitas')
            ->select('id_aktivitas, nama_aktivitas');

        if ($this->db->fieldExists('status_aktif', 'tb_aktivitas')) {
            $builder->where('status_aktif', 1);
        }

        return $builder->orderBy('nama_aktivitas', 'ASC')->get()->getResultArray();
    }

    protected function ambilDaftarStatusTracer(): array
    {
        return [
            'terkirim'      => 'Terkirim',
            'terverifikasi' => 'Terverifikasi',
            'disetujui'     => 'Disetujui',
        ];
    }

    /*
    |-------------------------------------------------------------------
    | DATA GRAFIK AKTIVITAS
    |-------------------------------------------------------------------
    | Grafik ini menghitung komposisi kegiatan alumni berdasarkan data
    | tracer yang sedang tampil setelah filter diterapkan.
    */
    protected function bangunGrafikAktivitas(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $label = trim((string) ($row['nama_aktivitas'] ?? 'Belum Diisi')) ?: 'Belum Diisi';
            $map[$label] = ($map[$label] ?? 0) + 1;
        }

        return [
            'labels' => array_keys($map),
            'series' => array_values($map),
            'map'    => $map,
        ];
    }

    protected function bangunGrafikAngkatan(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $label = trim((string) ($row['tahun_lulus'] ?? '-')) ?: '-';
            $map[$label] = ($map[$label] ?? 0) + 1;
        }

        ksort($map);

        return [
            'labels' => array_keys($map),
            'series' => array_values($map),
        ];
    }

    protected function isSuperadmin(): bool
    {
        return session()->get('slug_peran') === 'superadmin';
    }

    protected function getPageTitle(): string
    {
        return 'Data Tracer Alumni - Sistem Tracer Study';
    }

    protected function getDashboardUrl(): string
    {
        return site_url('dashboard/superadmin');
    }

    protected function getTracerBaseUrl(): string
    {
        return site_url('superadmin/tracer');
    }

    protected function getTracerRoleLabel(): string
    {
        return 'Manajemen Sekolah';
    }
}
