<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/*
|-------------------------------------------------------------------
| DASHBOARD CONTROLLER SUPERADMIN
|-------------------------------------------------------------------
| Controller ini menyiapkan data ringkasan untuk dashboard utama
| Super Admin/BKK, termasuk statistik pelamar, DUDI, lowongan,
| lamaran, dan mini grafik tracer alumni.
|
| Alur kerja:
| 1. Method index() memastikan pengguna adalah superadmin.
| 2. Controller membaca statistik dari tabel inti.
| 3. Data dikirim ke view dashboard/super-admin/index.php untuk
|    dirender sebagai kartu ringkasan dan grafik.
|
| Tips Debugging:
| - Jika grafik kosong, cek isi tb_tracer_alumni dan tb_aktivitas.
| - Jika angka lamaran nol, pastikan migration tb_lamaran sudah jalan.
*/
class DashboardController extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INDEX DASHBOARD SUPERADMIN
    |-------------------------------------------------------------------
    | Menampilkan command center ringkas untuk Super Admin. Dashboard
    | sengaja tidak menjadi halaman laporan penuh, tetapi tetap memuat
    | insight kecil agar kondisi BKK dan tracer langsung terlihat.
    */
    public function index(): string|RedirectResponse
    {
        if (session()->get('slug_peran') !== 'superadmin') {
            return redirect()->to('/login');
        }

        $totalPengguna = $this->hitungTabel('tb_pengguna');
        $totalPelamar = $this->hitungTabel('tb_pelamar');
        $totalAlumni = $this->hitungTabel('tb_alumni');
        $totalPerusahaan = $this->hitungPerusahaanAktif();
        $totalLowonganAktif = $this->hitungLowonganAktif();
        $totalLamaran = $this->hitungTabel('tb_lamaran');
        $lamaranMasuk = $this->hitungLamaranByStatus('menunggu_verifikasi');
        $antreanReview = $this->hitungPelamarByStatus('menunggu_aktivasi');
        $pelamarAktif = $this->hitungPelamarByStatus('aktif');
        $tracerTerkirim = $this->hitungTracerTerkirim();
        $tracerBelumLengkap = $this->hitungTracerBelumLengkap();
        $tracerAktivitas = $this->ambilGrafikTracerAktivitas();
        $tracerAngkatan = $this->ambilGrafikTracerAngkatan();
        $ringkasanLamaran = $this->ambilRingkasanLamaran();
        $lamaranTerbaru = $this->ambilLamaranTerbaru();
        $totalBekerja = $tracerAktivitas['map']['Bekerja'] ?? 0;
        $totalWirausaha = $tracerAktivitas['map']['Wirausaha'] ?? 0;
        $persenKeterserapan = $totalAlumni > 0 ? round((($totalBekerja + $totalWirausaha) / $totalAlumni) * 100, 1) : 0;

        return view('dashboard/super-admin/index', [
            'title'                => 'Dashboard Super Admin - Sistem Tracer Study & BKK',
            'total_pengguna'       => $totalPengguna,
            'total_pelamar'        => $totalPelamar,
            'total_alumni'         => $totalAlumni,
            'total_perusahaan'     => $totalPerusahaan,
            'total_lowongan'       => $totalLowonganAktif,
            'total_lamaran'        => $totalLamaran,
            'lamaran_masuk'        => $lamaranMasuk,
            'antrean_review'       => $antreanReview,
            'pelamar_aktif'        => $pelamarAktif,
            'tracer_terkirim'      => $tracerTerkirim,
            'tracer_belum_lengkap' => $tracerBelumLengkap,
            'persen_keterserapan'  => $persenKeterserapan,
            'tracer_aktivitas'     => $tracerAktivitas,
            'tracer_angkatan'      => $tracerAngkatan,
            'ringkasan_lamaran'    => $ringkasanLamaran,
            'lamaran_terbaru'      => $lamaranTerbaru,
            'data'                 => [],
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | HELPER HITUNG TABEL AMAN
    |-------------------------------------------------------------------
    | Helper ini mencegah error saat tabel tertentu belum tersedia di
    | database lokal pengembangan.
    */
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

    protected function hitungPelamarByStatus(string $status): int
    {
        if (! $this->db->tableExists('tb_pelamar') || ! $this->db->fieldExists('status_pendaftaran', 'tb_pelamar')) {
            return 0;
        }

        return (int) $this->db->table('tb_pelamar')
            ->where('status_pendaftaran', $status)
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

    /*
    |-------------------------------------------------------------------
    | DATA GRAFIK TRACER BERDASARKAN AKTIVITAS
    |-------------------------------------------------------------------
    | Query ini mengambil komposisi kegiatan alumni untuk grafik donut
    | kecil di dashboard.
    */
    protected function ambilGrafikTracerAktivitas(): array
    {
        $defaultLabels = ['Bekerja', 'Kuliah', 'Wirausaha', 'Belum Bekerja'];
        $map = array_fill_keys($defaultLabels, 0);

        if (! $this->db->tableExists('tb_tracer_alumni') || ! $this->db->tableExists('tb_aktivitas')) {
            return [
                'labels' => array_keys($map),
                'series' => array_values($map),
                'map' => $map,
            ];
        }

        $rows = $this->db->table('tb_tracer_alumni t')
            ->select('a.nama_aktivitas, COUNT(*) AS total')
            ->join('tb_aktivitas a', 'a.id_aktivitas = t.id_aktivitas', 'left')
            ->groupBy('a.nama_aktivitas')
            ->orderBy('a.nama_aktivitas', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $label = (string) ($row['nama_aktivitas'] ?? 'Lainnya');
            $map[$label] = (int) ($row['total'] ?? 0);
        }

        return [
            'labels' => array_keys($map),
            'series' => array_values($map),
            'map' => $map,
        ];
    }

    /*
    |-------------------------------------------------------------------
    | DATA GRAFIK TRACER BERDASARKAN ANGKATAN
    |-------------------------------------------------------------------
    | Query ini mengambil jumlah alumni yang sudah memiliki tracer per
    | tahun lulus agar dashboard punya mini tren batang.
    */
    protected function ambilGrafikTracerAngkatan(): array
    {
        if (
            ! $this->db->tableExists('tb_tracer_alumni')
            || ! $this->db->tableExists('tb_alumni')
            || ! $this->db->tableExists('tb_angkatan')
        ) {
            return [
                'labels' => [],
                'series' => [],
            ];
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
            'labels' => array_map(static fn (array $row): string => (string) ($row['tahun_lulus'] ?? '-'), $rows),
            'series' => array_map(static fn (array $row): int => (int) ($row['total'] ?? 0), $rows),
        ];
    }

    protected function ambilRingkasanLamaran(): array
    {
        $default = [
            'menunggu_verifikasi' => 0,
            'perlu_perbaikan_berkas' => 0,
            'diproses' => 0,
            'wawancara' => 0,
            'diterima' => 0,
            'ditolak' => 0,
        ];

        if (! $this->db->tableExists('tb_lamaran')) {
            return $default;
        }

        $rows = $this->db->table('tb_lamaran')
            ->select('status, COUNT(*) AS total')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            if (array_key_exists($status, $default)) {
                $default[$status] = (int) ($row['total'] ?? 0);
            }
        }

        return $default;
    }

    protected function ambilLamaranTerbaru(): array
    {
        if (
            ! $this->db->tableExists('tb_lamaran')
            || ! $this->db->tableExists('tb_pelamar')
            || ! $this->db->tableExists('tb_pengguna')
            || ! $this->db->tableExists('tb_lowongan')
            || ! $this->db->tableExists('tb_perusahaan')
        ) {
            return [];
        }

        return $this->db->table('tb_lamaran l')
            ->select([
                'l.id_lamaran',
                'l.status',
                'l.tanggal_melamar',
                'u.nama_lengkap',
                'lw.posisi',
                'lw.judul_lowongan',
                'p.nama_perusahaan',
            ])
            ->join('tb_pelamar pl', 'pl.id_pelamar = l.id_pelamar', 'inner')
            ->join('tb_pengguna u', 'u.id_pengguna = pl.id_pengguna', 'inner')
            ->join('tb_lowongan lw', 'lw.id_lowongan = l.id_lowongan', 'left')
            ->join('tb_perusahaan p', 'p.id_perusahaan = lw.id_perusahaan', 'left')
            ->orderBy('l.tanggal_melamar', 'DESC')
            ->orderBy('l.id_lamaran', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
    }
}
