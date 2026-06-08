<?php

namespace App\Controllers\AdminSekolah;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;

class DashboardController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function index()
    {
        if (session()->get('slug_peran') !== 'admin_sekolah') {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        return view('admin_sekolah/dashboard', [
            'title' => 'Dashboard Admin Sekolah - Sistem Tracer Study',
            'total_alumni' => $this->hitungTabel('tb_alumni'),
            'total_tracer' => $this->hitungTabel('tb_tracer_alumni'),
            'alumni_menunggu' => $this->hitungAlumniByStatus('menunggu_aktivasi'),
            'alumni_aktif' => $this->hitungAlumniByStatus('aktif'),
            'tracer_terkirim' => $this->hitungTracerTerkirim(),
            'tracer_belum_lengkap' => $this->hitungTracerBelumLengkap(),
            'grafik_aktivitas' => $this->ambilGrafikAktivitas(),
            'grafik_angkatan' => $this->ambilGrafikAngkatan(),
            'tracer_terbaru' => $this->ambilTracerTerbaru(),
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

    protected function ambilGrafikAktivitas(): array
    {
        if (! $this->db->tableExists('tb_tracer_alumni') || ! $this->db->tableExists('tb_aktivitas')) {
            return ['labels' => [], 'series' => [], 'map' => []];
        }

        $rows = $this->db->table('tb_tracer_alumni t')
            ->select('a.nama_aktivitas, COUNT(*) AS total')
            ->join('tb_aktivitas a', 'a.id_aktivitas = t.id_aktivitas', 'left')
            ->groupBy('a.nama_aktivitas')
            ->orderBy('a.nama_aktivitas', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $label = trim((string) ($row['nama_aktivitas'] ?? 'Belum Diisi')) ?: 'Belum Diisi';
            $map[$label] = (int) ($row['total'] ?? 0);
        }

        return ['labels' => array_keys($map), 'series' => array_values($map), 'map' => $map];
    }

    protected function ambilGrafikAngkatan(): array
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

    protected function ambilTracerTerbaru(): array
    {
        foreach (['tb_tracer_alumni', 'tb_alumni', 'tb_pengguna', 'tb_aktivitas'] as $table) {
            if (! $this->db->tableExists($table)) {
                return [];
            }
        }

        return $this->db->table('tb_tracer_alumni t')
            ->select('t.status, t.diperbarui_pada, u.nama_lengkap, CONCAT("ALM-", LPAD(al.id_alumni, 5, "0")) AS account_id, a.nama_aktivitas', false)
            ->join('tb_alumni al', 'al.id_alumni = t.id_alumni', 'inner')
            ->join('tb_pengguna u', 'u.id_pengguna = al.id_pengguna', 'inner')
            ->join('tb_aktivitas a', 'a.id_aktivitas = t.id_aktivitas', 'left')
            ->orderBy('t.diperbarui_pada', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
    }
}
