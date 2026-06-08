<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

class DashboardController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function index()
    {
        if (session()->get('slug_peran') !== 'superadmin') {
            return redirect()->to('/login');
        }

        $tracerAktivitas = $this->ambilGrafikTracerAktivitas();

        return view('dashboard/super-admin/index', [
            'title' => 'Dashboard Super Admin - Sistem Tracer Study',
            'total_pengguna' => $this->hitungTabel('tb_pengguna'),
            'total_alumni' => $this->hitungTabel('tb_alumni'),
            'alumni_menunggu' => $this->hitungAlumniByStatus('menunggu_aktivasi'),
            'alumni_aktif' => $this->hitungAlumniByStatus('aktif'),
            'tracer_terkirim' => $this->hitungTracerTerkirim(),
            'tracer_belum_lengkap' => $this->hitungTracerBelumLengkap(),
            'tracer_aktivitas' => $tracerAktivitas,
            'tracer_angkatan' => $this->ambilGrafikTracerAngkatan(),
        ]);
    }

    protected function hitungTabel(string $table): int
    {
        if (! $this->db->tableExists($table)) {
            return 0;
        }

        return (int) $this->db->table($table)->countAllResults();
    }

    protected function hitungAlumniByStatus(string $status): int
    {
        if (! $this->db->tableExists('tb_alumni')) {
            return 0;
        }

        return (int) $this->db->table('tb_alumni')
            ->where('status_pendaftaran', $status)
            ->countAllResults();
    }

    protected function hitungTracerTerkirim(): int
    {
        if (! $this->db->tableExists('tb_tracer_alumni')) {
            return 0;
        }

        return (int) $this->db->table('tb_tracer_alumni')
            ->whereIn('status', ['terkirim', 'terverifikasi', 'disetujui'])
            ->countAllResults();
    }

    protected function hitungTracerBelumLengkap(): int
    {
        if (! $this->db->tableExists('tb_alumni') || ! $this->db->tableExists('tb_tracer_alumni')) {
            return 0;
        }

        return (int) $this->db->table('tb_alumni al')
            ->join('tb_tracer_alumni t', 't.id_alumni = al.id_alumni', 'left')
            ->where('t.id_tracer IS NULL', null, false)
            ->countAllResults();
    }

    protected function ambilGrafikTracerAktivitas(): array
    {
        $map = ['Bekerja' => 0, 'Kuliah' => 0, 'Wirausaha' => 0, 'Belum Bekerja' => 0];

        if (! $this->db->tableExists('tb_tracer_alumni') || ! $this->db->tableExists('tb_aktivitas')) {
            return ['labels' => array_keys($map), 'series' => array_values($map), 'map' => $map];
        }

        $rows = $this->db->table('tb_tracer_alumni t')
            ->select('a.nama_aktivitas, COUNT(*) AS total')
            ->join('tb_aktivitas a', 'a.id_aktivitas = t.id_aktivitas', 'left')
            ->groupBy('a.nama_aktivitas')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $label = (string) ($row['nama_aktivitas'] ?? 'Lainnya');
            $map[$label] = (int) ($row['total'] ?? 0);
        }

        return ['labels' => array_keys($map), 'series' => array_values($map), 'map' => $map];
    }

    protected function ambilGrafikTracerAngkatan(): array
    {
        if (! $this->db->tableExists('tb_tracer_alumni') || ! $this->db->tableExists('tb_alumni') || ! $this->db->tableExists('tb_angkatan')) {
            return ['labels' => [], 'series' => []];
        }

        $rows = $this->db->table('tb_tracer_alumni t')
            ->select('ang.tahun_lulus, COUNT(*) AS total')
            ->join('tb_alumni al', 'al.id_alumni = t.id_alumni', 'inner')
            ->join('tb_angkatan ang', 'ang.id_angkatan = al.id_angkatan', 'left')
            ->groupBy('ang.tahun_lulus')
            ->orderBy('ang.tahun_lulus', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'labels' => array_map(static function (array $row): string {
                return (string) ($row['tahun_lulus'] ?? '-');
            }, $rows),
            'series' => array_map(static function (array $row): int {
                return (int) ($row['total'] ?? 0);
            }, $rows),
        ];
    }
}
