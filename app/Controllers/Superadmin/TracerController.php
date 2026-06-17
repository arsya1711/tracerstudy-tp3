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

    public function update(int $idAlumni): RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $alumni = $this->ambilAlumniDasar($idAlumni);
        if ($alumni === null) {
            return redirect()->to($this->getTracerBaseUrl())->with('error', 'Data alumni tidak ditemukan.');
        }

        $rules = [
            'nama_lengkap'  => 'required|max_length[150]',
            'email'         => 'required|valid_email|max_length[150]',
            'nomor_telepon' => 'permit_empty|max_length[30]',
            'nis'           => 'permit_empty|max_length[30]',
            'nisn'          => 'permit_empty|max_length[30]',
            'no_ijazah'     => 'permit_empty|max_length[60]',
            'id_angkatan'   => 'permit_empty|integer',
            'id_kompetensi' => 'permit_empty|integer',
            'id_aktivitas'  => 'permit_empty|integer',
            'tanggal_lahir' => 'permit_empty|valid_date[Y-m-d]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Data alumni atau tracer belum valid.');
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $emailSudahDipakai = $this->db->table('tb_pengguna')
            ->where('email', $email)
            ->where('id_pengguna !=', (int) $alumni['id_pengguna'])
            ->countAllResults() > 0;

        if ($emailSudahDipakai) {
            return redirect()->back()->withInput()->with('error', 'Email sudah digunakan oleh pengguna lain.');
        }

        $idAktivitas = (int) ($this->request->getPost('id_aktivitas') ?? 0);

        $this->db->transStart();

        $this->db->table('tb_pengguna')
            ->where('id_pengguna', (int) $alumni['id_pengguna'])
            ->update([
                'nama_lengkap'  => trim((string) $this->request->getPost('nama_lengkap')),
                'email'         => $email,
                'nomor_telepon' => $this->ambilStringKosongJadiNull('nomor_telepon'),
            ]);

        $this->db->table('tb_alumni')
            ->where('id_alumni', $idAlumni)
            ->update([
                'nis'           => $this->ambilStringKosongJadiNull('nis'),
                'nisn'          => $this->ambilStringKosongJadiNull('nisn'),
                'no_ijazah'     => $this->ambilStringKosongJadiNull('no_ijazah'),
                'jenis_kelamin' => $this->ambilStringKosongJadiNull('jenis_kelamin'),
                'tempat_lahir'  => $this->ambilStringKosongJadiNull('tempat_lahir'),
                'tanggal_lahir' => $this->ambilStringKosongJadiNull('tanggal_lahir'),
                'id_angkatan'   => $this->ambilIntegerKosongJadiNull('id_angkatan'),
                'id_kompetensi' => $this->ambilIntegerKosongJadiNull('id_kompetensi'),
                'alamat'        => $this->ambilStringKosongJadiNull('alamat'),
            ]);

        if ($idAktivitas > 0 && $this->db->tableExists('tb_tracer_alumni')) {
            $payloadTracer = $this->bangunPayloadTracer($idAlumni, $idAktivitas);
            $tracerLama = $this->db->table('tb_tracer_alumni')
                ->select('id_tracer')
                ->where('id_alumni', $idAlumni)
                ->get()
                ->getRowArray();

            if ($tracerLama !== null) {
                $this->db->table('tb_tracer_alumni')
                    ->where('id_tracer', (int) $tracerLama['id_tracer'])
                    ->update($payloadTracer);
            } else {
                $this->db->table('tb_tracer_alumni')->insert($payloadTracer);
            }
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data alumni.');
        }

        return redirect()->to($this->getTracerBaseUrl())->with('success', 'Data alumni dan tracer berhasil diperbarui.');
    }

    public function hapusTracer(int $idAlumni): RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        if (! $this->db->tableExists('tb_tracer_alumni')) {
            return redirect()->to($this->getTracerBaseUrl())->with('error', 'Tabel tracer belum tersedia.');
        }

        $this->db->table('tb_tracer_alumni')->where('id_alumni', $idAlumni)->delete();

        return redirect()->to($this->getTracerBaseUrl())->with('success', 'Data tracer alumni berhasil dihapus.');
    }

    public function hapusAlumni(int $idAlumni): RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $alumni = $this->ambilAlumniDasar($idAlumni);
        if ($alumni === null) {
            return redirect()->to($this->getTracerBaseUrl())->with('error', 'Data alumni tidak ditemukan.');
        }

        $this->db->transStart();

        if ($this->db->tableExists('tb_tracer_alumni')) {
            $this->db->table('tb_tracer_alumni')->where('id_alumni', $idAlumni)->delete();
        }

        $this->db->table('tb_alumni')->where('id_alumni', $idAlumni)->delete();
        $this->db->table('tb_pengguna')->where('id_pengguna', (int) $alumni['id_pengguna'])->delete();

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to($this->getTracerBaseUrl())->with('error', 'Gagal menghapus data alumni.');
        }

        return redirect()->to($this->getTracerBaseUrl())->with('success', 'Data profil, akun, dan tracer alumni berhasil dihapus.');
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

        $builder = $this->db->table('tb_alumni al')
            ->select([
                't.*',
                'al.id_alumni',
                'al.id_pengguna',
                'al.id_angkatan',
                'al.id_kompetensi',
                'al.nis',
                'al.nisn',
                'al.no_ijazah',
                'al.jenis_kelamin',
                'al.tempat_lahir',
                'al.tanggal_lahir',
                'al.alamat',
                'al.status_verifikasi',
                'CONCAT("ALM-", LPAD(al.id_alumni, 5, "0")) AS account_id',
                'u.nama_lengkap',
                'u.email',
                'u.nomor_telepon',
                'ang.tahun_lulus',
                'k.nama_kompetensi',
                'k.akronim',
                'a.nama_aktivitas',
            ])
            ->join('tb_pengguna u', 'u.id_pengguna = al.id_pengguna', 'inner')
            ->join('tb_tracer_alumni t', 't.id_alumni = al.id_alumni', 'left')
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

        if (($filters['status'] ?? '') === 'sudah') {
            $builder->where('t.id_tracer IS NOT NULL', null, false);
        } elseif (($filters['status'] ?? '') === 'belum') {
            $builder->where('t.id_tracer IS NULL', null, false);
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

        $aktivitas = $builder->orderBy('nama_aktivitas', 'ASC')->get()->getResultArray();

        return array_values(array_filter($aktivitas, static function (array $item): bool {
            $nama = strtolower((string) ($item['nama_aktivitas'] ?? ''));

            return ! str_contains($nama, 'kuliah') && ! str_contains($nama, 'studi');
        }));
    }

    protected function ambilDaftarStatusTracer(): array
    {
        return [
            'sudah' => 'Sudah Mengisi Tracer',
            'belum' => 'Belum Mengisi Tracer',
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

    protected function ambilAlumniDasar(int $idAlumni): ?array
    {
        if (! $this->db->tableExists('tb_alumni') || ! $this->db->tableExists('tb_pengguna')) {
            return null;
        }

        $row = $this->db->table('tb_alumni al')
            ->select('al.id_alumni, al.id_pengguna, u.email')
            ->join('tb_pengguna u', 'u.id_pengguna = al.id_pengguna', 'inner')
            ->where('al.id_alumni', $idAlumni)
            ->get()
            ->getRowArray();

        return $row !== null ? $row : null;
    }

    protected function bangunPayloadTracer(int $idAlumni, int $idAktivitas): array
    {
        return [
            'id_alumni'          => $idAlumni,
            'id_aktivitas'       => $idAktivitas,
            'posisi_kerja'       => $this->ambilStringKosongJadiNull('posisi_kerja'),
            'nama_instansi'      => $this->ambilStringKosongJadiNull('nama_instansi'),
            'bidang_instansi'    => $this->ambilStringKosongJadiNull('bidang_instansi'),
            'alamat_instansi'    => $this->ambilStringKosongJadiNull('alamat_instansi'),
            'tahun_mulai_kerja'  => $this->ambilStringKosongJadiNull('tahun_mulai_kerja'),
            'relevan_jurusan'    => $this->ambilIntegerKosongJadiNull('relevan_jurusan'),
            'penghasilan_range'  => $this->ambilStringKosongJadiNull('penghasilan_range'),
            'universitas'        => $this->ambilStringKosongJadiNull('universitas'),
            'program_studi'      => $this->ambilStringKosongJadiNull('program_studi'),
            'status_kuliah'      => $this->ambilStringKosongJadiNull('status_kuliah'),
            'nama_usaha'         => $this->ambilStringKosongJadiNull('nama_usaha'),
            'bidang_usaha'       => $this->ambilStringKosongJadiNull('bidang_usaha'),
            'modal_awal'         => $this->ambilDecimalKosongJadiNull('modal_awal'),
            'penghasilan_usaha'  => $this->ambilStringKosongJadiNull('penghasilan_usaha'),
            'rencana_kedepan'    => $this->ambilStringKosongJadiNull('rencana_kedepan'),
            'status'             => 'terkirim',
            'diperbarui_pada'    => date('Y-m-d H:i:s'),
        ];
    }

    protected function ambilStringKosongJadiNull(string $field): ?string
    {
        $value = trim((string) ($this->request->getPost($field) ?? ''));

        return $value !== '' ? $value : null;
    }

    protected function ambilIntegerKosongJadiNull(string $field): ?int
    {
        $value = trim((string) ($this->request->getPost($field) ?? ''));

        return $value !== '' ? (int) $value : null;
    }

    protected function ambilDecimalKosongJadiNull(string $field): ?float
    {
        $value = trim((string) ($this->request->getPost($field) ?? ''));

        if ($value === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9.,]/', '', $value) ?? '';
        if (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', str_replace('.', '', $normalized));
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        return $normalized !== '' ? (float) $normalized : null;
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
