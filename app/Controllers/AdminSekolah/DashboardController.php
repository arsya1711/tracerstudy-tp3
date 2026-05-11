<?php

namespace App\Controllers\AdminSekolah;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;

/*
|-------------------------------------------------------------------
| DASHBOARD CONTROLLER ADMIN SEKOLAH/BKK
|-------------------------------------------------------------------
| Controller ini menyiapkan command center untuk Admin Sekolah/BKK.
| Fokusnya bukan seluruh sistem seperti Super Admin, tetapi pekerjaan
| BKK: tracer alumni, verifikasi alumni, lowongan aktif, dan lamaran.
|
| Alur kerja:
| 1. Method index() memastikan role yang masuk adalah admin_sekolah.
| 2. Controller membaca ringkasan dari tabel alumni, tracer, lowongan,
|    dan lamaran.
| 3. Data dikirim ke view admin_sekolah/dashboard.php untuk dirender
|    sebagai kartu statistik dan grafik kecil.
|
| Tips Debugging:
| - Jika dashboard kosong, pastikan tabel tb_alumni dan tb_tracer_alumni
|   sudah terisi.
| - Jika setelah login admin sekolah masih ke placeholder, cek route
|   dashboard/admin-sekolah di app/Config/Routes.php.
*/
class DashboardController extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function index(): string|RedirectResponse
    {
        if (session()->get('slug_peran') !== 'admin_sekolah') {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $aktivitas = $this->ambilGrafikAktivitas();
        $angkatan = $this->ambilGrafikAngkatan();

        return view('admin_sekolah/dashboard', [
            'title'                  => 'Dashboard Admin Sekolah/BKK - Sistem Tracer Study & BKK',
            'total_alumni'           => $this->hitungTabel('tb_alumni'),
            'total_tracer'           => $this->hitungTabel('tb_tracer_alumni'),
            'alumni_menunggu'        => $this->hitungAlumniByStatus('menunggu'),
            'antrean_review'         => $this->hitungPelamarByStatus('menunggu_aktivasi'),
            'pelamar_aktif'          => $this->hitungPelamarByStatus('aktif'),
            'tracer_terkirim'        => $this->hitungTracerTerkirim(),
            'tracer_belum_lengkap'   => $this->hitungTracerBelumLengkap(),
            'lowongan_aktif'         => $this->hitungLowonganAktif(),
            'lamaran_masuk'          => $this->hitungLamaranByStatus('menunggu_verifikasi'),
            'lamaran_perbaikan'      => $this->hitungLamaranByStatus('perlu_perbaikan_berkas'),
            'grafik_aktivitas'       => $aktivitas,
            'grafik_angkatan'        => $angkatan,
            'tracer_terbaru'         => $this->ambilTracerTerbaru(),
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
            ->where('status_verifikasi', $status)
            ->countAllResults();
    }

    protected function hitungPelamarByStatus(string $status): int
    {
        if (! $this->db->tableExists('tb_pelamar') || ! $this->db->fieldExists('status_pendaftaran', 'tb_pelamar')) {
            return 0;
        }

        return (int) $this->db->table('tb_pelamar')
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

        $builder = $this->db->table('tb_alumni al')
            ->join('tb_tracer_alumni t', 't.id_alumni = al.id_alumni', 'left');

        $builder->groupStart()
            ->where('t.id_tracer IS NULL', null, false);

        if ($this->db->fieldExists('status', 'tb_tracer_alumni')) {
            $builder->orWhere('t.status', 'draft');
        }

        $builder->groupEnd();

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

    protected function hitungLamaranByStatus(string $status): int
    {
        if (! $this->db->tableExists('tb_lamaran')) {
            return 0;
        }

        return (int) $this->db->table('tb_lamaran')
            ->where('status', $status)
            ->countAllResults();
    }

    /*
    |-------------------------------------------------------------------
    | GRAFIK AKTIVITAS DAN ANGKATAN
    |-------------------------------------------------------------------
    | Dua helper berikut menyediakan data grafik ringkas. Grafik detail
    | tetap ditempatkan di halaman Data Tracer Alumni agar dashboard
    | tetap ringan.
    */
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
        if (
            ! $this->db->tableExists('tb_tracer_alumni')
            || ! $this->db->tableExists('tb_alumni')
            || ! $this->db->tableExists('tb_angkatan')
        ) {
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
            'labels' => array_map(static fn(array $row): string => (string) ($row['tahun_lulus'] ?? '-'), $rows),
            'series' => array_map(static fn(array $row): int => (int) ($row['total'] ?? 0), $rows),
        ];
    }

    protected function ambilTracerTerbaru(): array
    {
        foreach (['tb_tracer_alumni', 'tb_alumni', 'tb_pelamar', 'tb_pengguna', 'tb_aktivitas'] as $table) {
            if (! $this->db->tableExists($table)) {
                return [];
            }
        }

        return $this->db->table('tb_tracer_alumni t')
            ->select('t.status, t.diperbarui_pada, u.nama_lengkap, p.account_id, a.nama_aktivitas')
            ->join('tb_alumni al', 'al.id_alumni = t.id_alumni', 'inner')
            ->join('tb_pelamar p', 'p.id_pelamar = al.id_pelamar', 'inner')
            ->join('tb_pengguna u', 'u.id_pengguna = p.id_pengguna', 'inner')
            ->join('tb_aktivitas a', 'a.id_aktivitas = t.id_aktivitas', 'left')
            ->orderBy('t.diperbarui_pada', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
    }
}
