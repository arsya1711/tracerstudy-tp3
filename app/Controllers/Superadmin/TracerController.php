<?php

namespace App\Controllers\Superadmin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
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

        $filters = $this->ambilFilterDariRequest();

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

    public function export(): ResponseInterface|RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $filters = $this->ambilFilterDariRequest();
        $rows = $this->ambilDataTracer($filters);
        $filename = 'laporan-tracer-alumni-' . date('Ymd-His') . '.xls';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0, no-cache, no-store, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody($this->bangunExcelTracer($rows, $filters));
    }

    /*
    | Export PDF laporan tracer.
    | Dibuat tanpa dependency tambahan karena Composer tidak tersedia di
    | environment lokal. PDF digambar langsung dari data tracer yang sama
    | dengan tabel dan export Excel.
    */
    public function exportPdf(): ResponseInterface|RedirectResponse
    {
        if (! $this->isSuperadmin()) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $filters = $this->ambilFilterDariRequest();
        $rows = $this->ambilDataTracer($filters);
        $filename = 'laporan-tracer-alumni-' . date('Ymd-His') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0, no-cache, no-store, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody($this->bangunPdfTracer($rows, $filters));
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

        $payloadAlumni = [
            'nis'                 => $this->ambilStringKosongJadiNull('nis'),
            'nisn'                => $this->ambilStringKosongJadiNull('nisn'),
            'jenis_kelamin'       => $this->ambilStringKosongJadiNull('jenis_kelamin'),
            'tempat_lahir'        => $this->ambilStringKosongJadiNull('tempat_lahir'),
            'tanggal_lahir'       => $this->ambilStringKosongJadiNull('tanggal_lahir'),
            'id_angkatan'         => $this->ambilIntegerKosongJadiNull('id_angkatan'),
            'id_kompetensi'       => $this->ambilIntegerKosongJadiNull('id_kompetensi'),
            'alamat'              => $this->ambilStringKosongJadiNull('alamat'),
            'status_pendaftaran'  => 'aktif',
            'status_verifikasi'   => 'aktif',
        ];

        $this->db->table('tb_alumni')
            ->where('id_alumni', $idAlumni)
            ->update($payloadAlumni);

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
                't.dibuat_pada AS tracer_dibuat_pada',
                't.diperbarui_pada AS tracer_diperbarui_pada',
                'al.id_alumni',
                'al.id_pengguna',
                'al.id_angkatan',
                'al.id_kompetensi',
                'al.nis',
                'al.nisn',
                'al.jenis_kelamin',
                'al.tempat_lahir',
                'al.tanggal_lahir',
                'al.alamat',
                'al.status_verifikasi',
                'al.status_pendaftaran',
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

        if (($filters['tanggal_mulai'] ?? '') !== '') {
            $builder->where('DATE(COALESCE(t.diperbarui_pada, t.dibuat_pada)) >=', $filters['tanggal_mulai']);
        }

        if (($filters['tanggal_selesai'] ?? '') !== '') {
            $builder->where('DATE(COALESCE(t.diperbarui_pada, t.dibuat_pada)) <=', $filters['tanggal_selesai']);
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
            ->orderBy('u.nama_lengkap', 'ASC')
            ->get()
            ->getResultArray();
    }

    protected function ambilFilterDariRequest(): array
    {
        $tanggalMulai = $this->validDateFilter((string) $this->request->getGet('tanggal_mulai'));
        $tanggalSelesai = $this->validDateFilter((string) $this->request->getGet('tanggal_selesai'));

        if ($tanggalMulai !== '' && $tanggalSelesai !== '' && $tanggalMulai > $tanggalSelesai) {
            [$tanggalMulai, $tanggalSelesai] = [$tanggalSelesai, $tanggalMulai];
        }

        return [
            'search'           => trim((string) $this->request->getGet('q')),
            'id_angkatan'      => max(0, (int) ($this->request->getGet('id_angkatan') ?? 0)),
            'id_kompetensi'    => max(0, (int) ($this->request->getGet('id_kompetensi') ?? 0)),
            'id_aktivitas'     => max(0, (int) ($this->request->getGet('id_aktivitas') ?? 0)),
            'status'           => in_array((string) $this->request->getGet('status'), ['sudah', 'belum'], true) ? (string) $this->request->getGet('status') : '',
            'tanggal_mulai'    => $tanggalMulai,
            'tanggal_selesai'  => $tanggalSelesai,
        ];
    }

    protected function validDateFilter(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }

    protected function bangunExcelTracer(array $rows, array $filters): string
    {
        $rekap = $this->bangunRekapAkreditasi($rows);
        $filterText = $this->formatFilterExcel($filters);
        $dibuatPada = date('d/m/Y H:i');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?mso-application progid="Excel.Sheet"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
            . 'xmlns:o="urn:schemas-microsoft-com:office:office" '
            . 'xmlns:x="urn:schemas-microsoft-com:office:excel" '
            . 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Styles>';
        $xml .= '<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="16" ss:Color="#0B1F4D"/></Style>';
        $xml .= '<Style ss:ID="Meta"><Font ss:Color="#64748B"/></Style>';
        $xml .= '<Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B1F4D" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        $xml .= '<Style ss:ID="Cell"><Alignment ss:Vertical="Top" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9E2EC"/></Borders></Style>';
        $xml .= '<Style ss:ID="Number"><Alignment ss:Horizontal="Center" ss:Vertical="Top"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9E2EC"/></Borders></Style>';
        $xml .= '<Style ss:ID="Percent"><NumberFormat ss:Format="0.00%"/><Alignment ss:Horizontal="Center" ss:Vertical="Top"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9E2EC"/></Borders></Style>';
        $xml .= '</Styles>';

        $xml .= '<Worksheet ss:Name="Rekap Akreditasi"><Table>';
        $xml .= '<Column ss:Width="90"/><Column ss:Width="190"/><Column ss:Width="90"/><Column ss:Width="125"/><Column ss:Width="125"/><Column ss:Width="75"/><Column ss:Width="145"/><Column ss:Width="80"/><Column ss:Width="95"/><Column ss:Width="135"/><Column ss:Width="155"/>';
        $xml .= $this->excelXmlRow([['Laporan Tracer Alumni - Rekap Akreditasi', 'String', 'Title']], 11);
        $xml .= $this->excelXmlRow([['Sistem Informasi Tracer Study SMK Teratai Putih Global 3 Bekasi', 'String', 'Meta']], 11);
        $xml .= $this->excelXmlRow([['Dibuat pada: ' . $dibuatPada, 'String', 'Meta']], 11);
        $xml .= $this->excelXmlRow([['Filter: ' . $filterText, 'String', 'Meta']], 11);
        $xml .= '<Row/>';
        $xml .= $this->excelXmlHeader([
            'Angkatan',
            'Kompetensi Keahlian',
            'Total Alumni',
            'Sudah Mengisi Tracer Study',
            'Belum Mengisi Tracer Study',
            'Bekerja',
            'Kuliah / Melanjutkan Studi',
            'Wirausaha',
            'Mencari Kerja',
            'Persentase Keterserapan',
            'Persentase Pengisian Tracer Study',
        ]);

        if ($rekap === []) {
            $xml .= $this->excelXmlRow([['Data tidak tersedia', 'String', 'Cell']], 11);
        } else {
            foreach ($rekap as $item) {
                $xml .= $this->excelXmlRow([
                    [$item['angkatan'], 'String', 'Cell'],
                    [$item['kompetensi'], 'String', 'Cell'],
                    [$item['total_alumni'], 'Number', 'Number'],
                    [$item['sudah_tracer'], 'Number', 'Number'],
                    [$item['belum_tracer'], 'Number', 'Number'],
                    [$item['bekerja'], 'Number', 'Number'],
                    [$item['kuliah'], 'Number', 'Number'],
                    [$item['wirausaha'], 'Number', 'Number'],
                    [$item['mencari_kerja'], 'Number', 'Number'],
                    [$item['persentase_keterserapan'] / 100, 'Number', 'Percent'],
                    [$item['persentase_pengisian'] / 100, 'Number', 'Percent'],
                ]);
            }
        }

        $xml .= '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>6</SplitHorizontal><TopRowBottomPane>6</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions></Worksheet>';

        $xml .= '<Worksheet ss:Name="Detail Data Alumni"><Table>';
        $xml .= '<Column ss:Width="40"/><Column ss:Width="190"/><Column ss:Width="120"/><Column ss:Width="190"/><Column ss:Width="120"/><Column ss:Width="105"/><Column ss:Width="200"/><Column ss:Width="130"/><Column ss:Width="210"/><Column ss:Width="220"/><Column ss:Width="150"/><Column ss:Width="240"/><Column ss:Width="155"/><Column ss:Width="125"/><Column ss:Width="160"/>';
        $xml .= $this->excelXmlRow([['Laporan Tracer Alumni - Detail Data Alumni', 'String', 'Title']], 15);
        $xml .= $this->excelXmlRow([['Dibuat pada: ' . $dibuatPada, 'String', 'Meta']], 15);
        $xml .= $this->excelXmlRow([['Filter: ' . $filterText, 'String', 'Meta']], 15);
        $xml .= '<Row/>';
        $xml .= $this->excelXmlHeader([
            'No',
            'Nama Alumni',
            'NIS/NISN',
            'Email',
            'No. Telepon',
            'Angkatan / Tahun Lulus',
            'Kompetensi Keahlian',
            'Status Aktivitas',
            'Nama Instansi / Kampus / Usaha',
            'Bidang Pekerjaan / Program Studi / Bidang Usaha',
            'Jabatan / Posisi',
            'Alamat Instansi / Kampus / Usaha',
            'Kesesuaian Bidang dengan Kompetensi',
            'Tanggal Pengisian',
            'Status Pendaftaran Alumni',
        ]);

        if ($rows === []) {
            $xml .= $this->excelXmlRow([['Data tidak tersedia', 'String', 'Cell']], 15);
        } else {
            foreach ($rows as $index => $row) {
                $xml .= $this->excelXmlRow([
                    [$index + 1, 'Number', 'Number'],
                    [$this->excelValue($row['nama_lengkap'] ?? ''), 'String', 'Cell'],
                    [$this->formatNisNisn($row), 'String', 'Cell'],
                    [$this->excelValue($row['email'] ?? ''), 'String', 'Cell'],
                    [$this->excelValue($row['nomor_telepon'] ?? ''), 'String', 'Cell'],
                    [$this->excelValue($row['tahun_lulus'] ?? ''), 'String', 'Cell'],
                    [$this->formatKompetensiExcel($row), 'String', 'Cell'],
                    [$this->formatStatusAktivitas($row), 'String', 'Cell'],
                    [$this->formatNamaTempatTracer($row), 'String', 'Cell'],
                    [$this->formatBidangTracer($row), 'String', 'Cell'],
                    [$this->formatJabatanTracer($row), 'String', 'Cell'],
                    [$this->formatAlamatTracer($row), 'String', 'Cell'],
                    [$this->formatRelevansiTracer($row), 'String', 'Cell'],
                    [$this->formatTanggalPengisian($row), 'String', 'Cell'],
                    [$this->formatStatusPendaftaran($row), 'String', 'Cell'],
                ]);
            }
        }

        $xml .= '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>5</SplitHorizontal><TopRowBottomPane>5</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions></Worksheet>';
        $xml .= '</Workbook>';

        return "\xEF\xBB\xBF" . $xml;
    }

    protected function bangunRekapAkreditasi(array $rows): array
    {
        $rekap = [];

        foreach ($rows as $row) {
            $angkatan = $this->excelValue($row['tahun_lulus'] ?? '');
            $kompetensi = $this->formatKompetensiExcel($row);
            $key = $angkatan . '|' . $kompetensi;

            if (! isset($rekap[$key])) {
                $rekap[$key] = [
                    'angkatan' => $angkatan,
                    'kompetensi' => $kompetensi,
                    'total_alumni' => 0,
                    'sudah_tracer' => 0,
                    'belum_tracer' => 0,
                    'bekerja' => 0,
                    'kuliah' => 0,
                    'wirausaha' => 0,
                    'mencari_kerja' => 0,
                    'persentase_keterserapan' => 0.0,
                    'persentase_pengisian' => 0.0,
                ];
            }

            $rekap[$key]['total_alumni']++;

            $sudahMengisi = (int) ($row['id_tracer'] ?? 0) > 0;
            if ($sudahMengisi) {
                $rekap[$key]['sudah_tracer']++;
            } else {
                $rekap[$key]['belum_tracer']++;
            }

            $kategori = $this->kategoriAktivitas($row);
            if (isset($rekap[$key][$kategori])) {
                $rekap[$key][$kategori]++;
            }
        }

        foreach ($rekap as &$item) {
            $total = (int) $item['total_alumni'];
            $terserap = (int) $item['bekerja'] + (int) $item['kuliah'] + (int) $item['wirausaha'];
            $item['persentase_keterserapan'] = $total > 0 ? round(($terserap / $total) * 100, 2) : 0.0;
            $item['persentase_pengisian'] = $total > 0 ? round(((int) $item['sudah_tracer'] / $total) * 100, 2) : 0.0;
        }
        unset($item);

        uasort($rekap, static function (array $a, array $b): int {
            return [$a['angkatan'], $a['kompetensi']] <=> [$b['angkatan'], $b['kompetensi']];
        });

        return array_values($rekap);
    }

    protected function bangunRingkasanAkreditasi(array $rows): array
    {
        $ringkasan = [
            'total_alumni' => count($rows),
            'sudah_tracer' => 0,
            'belum_tracer' => 0,
            'bekerja' => 0,
            'kuliah' => 0,
            'wirausaha' => 0,
            'mencari_kerja' => 0,
            'persentase_keterserapan' => 0.0,
            'persentase_pengisian' => 0.0,
        ];

        foreach ($rows as $row) {
            if ((int) ($row['id_tracer'] ?? 0) > 0) {
                $ringkasan['sudah_tracer']++;
            } else {
                $ringkasan['belum_tracer']++;
            }

            $kategori = $this->kategoriAktivitas($row);
            if (isset($ringkasan[$kategori])) {
                $ringkasan[$kategori]++;
            }
        }

        $total = (int) $ringkasan['total_alumni'];
        if ($total > 0) {
            $terserap = (int) $ringkasan['bekerja'] + (int) $ringkasan['kuliah'] + (int) $ringkasan['wirausaha'];
            $ringkasan['persentase_keterserapan'] = round(($terserap / $total) * 100, 2);
            $ringkasan['persentase_pengisian'] = round(((int) $ringkasan['sudah_tracer'] / $total) * 100, 2);
        }

        return $ringkasan;
    }

    protected function excelXmlHeader(array $headers): string
    {
        $cells = [];
        foreach ($headers as $header) {
            $cells[] = [$header, 'String', 'Header'];
        }

        return $this->excelXmlRow($cells);
    }

    protected function excelXmlRow(array $cells, ?int $mergeAcross = null): string
    {
        $xml = '<Row>';
        foreach ($cells as $index => $cell) {
            [$value, $type, $style] = $cell + ['', 'String', 'Cell'];
            $merge = $index === 0 && $mergeAcross !== null ? ' ss:MergeAcross="' . max(0, $mergeAcross - 1) . '"' : '';
            $xml .= '<Cell ss:StyleID="' . $this->excelXmlEscape((string) $style) . '"' . $merge . '><Data ss:Type="' . $this->excelXmlEscape((string) $type) . '">' . $this->excelXmlEscape((string) $value) . '</Data></Cell>';
        }
        $xml .= '</Row>';

        return $xml;
    }

    protected function excelXmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected function excelValue(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : '-';
    }

    protected function kategoriAktivitas(array $row): string
    {
        if ((int) ($row['id_tracer'] ?? 0) <= 0) {
            return 'belum_tracer';
        }

        $nama = strtolower(trim((string) ($row['nama_aktivitas'] ?? '')));

        if (str_contains($nama, 'kuliah') || str_contains($nama, 'studi')) {
            return 'kuliah';
        }

        if (str_contains($nama, 'wirausaha') || str_contains($nama, 'usaha')) {
            return 'wirausaha';
        }

        if (str_contains($nama, 'mencari') || str_contains($nama, 'belum')) {
            return 'mencari_kerja';
        }

        if (str_contains($nama, 'bekerja') || str_contains($nama, 'kerja')) {
            return 'bekerja';
        }

        return 'mencari_kerja';
    }

    protected function formatNisNisn(array $row): string
    {
        $nis = trim((string) ($row['nis'] ?? ''));
        $nisn = trim((string) ($row['nisn'] ?? ''));

        if ($nis !== '' && $nisn !== '') {
            return $nis . ' / ' . $nisn;
        }

        return $nis !== '' ? $nis : ($nisn !== '' ? $nisn : '-');
    }

    protected function formatStatusAktivitas(array $row): string
    {
        if ((int) ($row['id_tracer'] ?? 0) <= 0) {
            return 'Belum Mengisi Tracer Study';
        }

        return $this->excelValue($row['nama_aktivitas'] ?? '');
    }

    protected function formatNamaTempatTracer(array $row): string
    {
        return match ($this->kategoriAktivitas($row)) {
            'kuliah' => $this->excelValue($row['universitas'] ?? ''),
            'wirausaha' => $this->excelValue($row['nama_usaha'] ?? ''),
            'bekerja' => $this->excelValue($row['nama_instansi'] ?? ''),
            default => '-',
        };
    }

    protected function formatBidangTracer(array $row): string
    {
        return match ($this->kategoriAktivitas($row)) {
            'kuliah' => $this->excelValue($row['program_studi'] ?? ''),
            'wirausaha' => $this->excelValue($row['bidang_usaha'] ?? ''),
            'bekerja' => $this->excelValue($row['bidang_instansi'] ?? ''),
            default => $this->excelValue($row['rencana_kedepan'] ?? ''),
        };
    }

    protected function formatJabatanTracer(array $row): string
    {
        return $this->kategoriAktivitas($row) === 'bekerja'
            ? $this->excelValue($row['posisi_kerja'] ?? '')
            : '-';
    }

    protected function formatAlamatTracer(array $row): string
    {
        return $this->kategoriAktivitas($row) === 'bekerja'
            ? $this->excelValue($row['alamat_instansi'] ?? '')
            : '-';
    }

    protected function formatRelevansiTracer(array $row): string
    {
        if ((int) ($row['id_tracer'] ?? 0) <= 0 || ($row['relevan_jurusan'] ?? null) === null || $row['relevan_jurusan'] === '') {
            return '-';
        }

        return (int) $row['relevan_jurusan'] === 1 ? 'Sesuai' : 'Tidak Sesuai';
    }

    protected function formatTanggalPengisian(array $row): string
    {
        if ((int) ($row['id_tracer'] ?? 0) <= 0) {
            return '-';
        }

        $tanggal = trim((string) ($row['tracer_diperbarui_pada'] ?? ''));
        if ($tanggal === '') {
            $tanggal = trim((string) ($row['tracer_dibuat_pada'] ?? ''));
        }

        if ($tanggal === '') {
            return '-';
        }

        try {
            return (new \DateTime($tanggal))->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $tanggal;
        }
    }

    protected function formatStatusPendaftaran(array $row): string
    {
        $status = trim((string) ($row['status_pendaftaran'] ?? ''));

        return $status !== '' ? ucwords(str_replace('_', ' ', $status)) : '-';
    }

    protected function formatKompetensiExcel(array $row): string
    {
        $nama = trim((string) ($row['nama_kompetensi'] ?? ''));
        $akronim = trim((string) ($row['akronim'] ?? ''));

        if ($nama !== '' && $akronim !== '') {
            return $nama . ' (' . $akronim . ')';
        }

        return $nama !== '' ? $nama : ($akronim !== '' ? $akronim : '-');
    }

    protected function formatFilterExcel(array $filters): string
    {
        $items = [];

        if (($filters['search'] ?? '') !== '') {
            $items[] = 'Pencarian: ' . $filters['search'];
        }

        if (($filters['id_angkatan'] ?? 0) > 0) {
            $items[] = 'ID Angkatan: ' . (int) $filters['id_angkatan'];
        }

        if (($filters['id_kompetensi'] ?? 0) > 0) {
            $items[] = 'ID Kompetensi: ' . (int) $filters['id_kompetensi'];
        }

        if (($filters['id_aktivitas'] ?? 0) > 0) {
            $items[] = 'ID Aktivitas: ' . (int) $filters['id_aktivitas'];
        }

        if (($filters['status'] ?? '') !== '') {
            $items[] = 'Status: ' . $filters['status'];
        }

        if (($filters['tanggal_mulai'] ?? '') !== '') {
            $items[] = 'Tanggal Mulai: ' . $filters['tanggal_mulai'];
        }

        if (($filters['tanggal_selesai'] ?? '') !== '') {
            $items[] = 'Tanggal Selesai: ' . $filters['tanggal_selesai'];
        }

        return $items !== [] ? implode(', ', $items) : 'Semua data';
    }

    protected function excelCell(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /*
    | Generator PDF ringan untuk laporan tracer.
    | Bagian ini menyusun halaman landscape, header, ringkasan, tabel,
    | dan pagination memakai sintaks PDF dasar.
    */
    protected function bangunPdfTracer(array $rows, array $filters): string
    {
        $pageWidth = 842.0;
        $pageHeight = 595.0;
        $margin = 24.0;
        $contentWidth = $pageWidth - ($margin * 2);
        $columns = [
            ['label' => 'No', 'width' => 24],
            ['label' => 'ID', 'width' => 55],
            ['label' => 'Nama Alumni', 'width' => 90],
            ['label' => 'Email', 'width' => 115],
            ['label' => 'Angk.', 'width' => 45],
            ['label' => 'Kompetensi', 'width' => 100],
            ['label' => 'Aktivitas', 'width' => 75],
            ['label' => 'Status', 'width' => 80],
            ['label' => 'Kuliah', 'width' => 90],
            ['label' => 'Instansi / Usaha', 'width' => 120],
        ];

        $ringkasan = $this->bangunRingkasanAkreditasi($rows);

        $pages = [];
        $current = [];
        $y = $pageHeight - $margin;
        $pageNumber = 1;

        $addPageHeader = function () use (&$current, &$y, $pageHeight, $margin, $contentWidth, $filters, $ringkasan): void {
            $current[] = '0.043 0.122 0.302 rg';
            $current[] = $this->pdfText($margin, $y - 4, 'Laporan Tracer Alumni', 18, 'F2');
            $y -= 26;
            $current[] = '0.392 0.455 0.545 rg';
            $current[] = $this->pdfText($margin, $y, 'Sistem Informasi Tracer Study', 9, 'F1');
            $y -= 14;
            $current[] = $this->pdfText($margin, $y, 'Dibuat pada: ' . date('d/m/Y H:i') . '   |   Filter: ' . $this->formatFilterExcel($filters), 8, 'F1');
            $y -= 20;

            $boxWidth = 87;
            $gap = 10;
            $summary = [
                ['Total', (string) $ringkasan['total_alumni']],
                ['Sudah Tracer', (string) $ringkasan['sudah_tracer']],
                ['Belum Tracer', (string) $ringkasan['belum_tracer']],
                ['Bekerja', (string) $ringkasan['bekerja']],
                ['Kuliah', (string) $ringkasan['kuliah']],
                ['Wirausaha', (string) $ringkasan['wirausaha']],
                ['Mencari Kerja', (string) $ringkasan['mencari_kerja']],
                ['Keterserapan', number_format((float) $ringkasan['persentase_keterserapan'], 2) . '%'],
            ];

            foreach ($summary as $index => $item) {
                $x = $margin + (($boxWidth + $gap) * $index);
                $current[] = '0.933 0.957 1 rg';
                $current[] = $this->pdfRect($x, $y - 28, $boxWidth, 32, true);
                $current[] = '0.780 0.843 0.996 RG';
                $current[] = $this->pdfRect($x, $y - 28, $boxWidth, 32);
                $current[] = '0.043 0.122 0.302 rg';
                $current[] = $this->pdfText($x + 8, $y - 9, $item[0], 8, 'F2');
                $current[] = $this->pdfText($x + 8, $y - 23, $item[1], 13, 'F2');
            }

            $current[] = '0.859 0.886 0.925 RG';
            $current[] = $this->pdfLine($margin, $y - 42, $margin + $contentWidth, $y - 42);
            $y -= 60;
        };

        $addTableHeader = function () use (&$current, &$y, $margin, $columns): void {
            $x = $margin;
            $height = 24;
            foreach ($columns as $column) {
                $current[] = '0.043 0.122 0.302 rg';
                $current[] = $this->pdfRect($x, $y - $height, (float) $column['width'], $height, true);
                $current[] = '1 1 1 rg';
                $current[] = $this->pdfText($x + 4, $y - 15, (string) $column['label'], 7, 'F2');
                $x += (float) $column['width'];
            }
            $y -= $height;
        };

        $finishPage = function () use (&$pages, &$current, &$y, $pageHeight, $margin, &$pageNumber): void {
            $current[] = '0.392 0.455 0.545 rg';
            $current[] = $this->pdfText($margin, 18, 'Halaman ' . $pageNumber, 8, 'F1');
            $pages[] = implode("\n", $current);
            $current = [];
            $y = $pageHeight - $margin;
            $pageNumber++;
        };

        $addPageHeader();
        $addTableHeader();

        foreach ($rows as $index => $row) {
            $sudahMengisi = (int) ($row['id_tracer'] ?? 0) > 0;
            $kuliah = trim((string) ($row['universitas'] ?? ''));
            $programStudi = trim((string) ($row['program_studi'] ?? ''));
            $instansi = trim((string) ($row['nama_instansi'] ?? ''));
            $usaha = trim((string) ($row['nama_usaha'] ?? ''));

            $values = [
                (string) ($index + 1),
                $this->pdfValue($row['account_id'] ?? ''),
                $this->pdfValue($row['nama_lengkap'] ?? ''),
                $this->pdfValue($row['email'] ?? ''),
                $this->pdfValue($row['tahun_lulus'] ?? ''),
                $this->formatKompetensiExcel($row),
                $this->pdfValue($row['nama_aktivitas'] ?? 'Belum Mengisi Tracer'),
                $sudahMengisi ? 'Sudah' : 'Belum',
                $kuliah !== '' ? trim($kuliah . ' ' . $programStudi) : '-',
                $instansi !== '' ? $instansi : ($usaha !== '' ? $usaha : '-'),
            ];

            $wrapped = [];
            $maxLines = 1;
            foreach ($values as $columnIndex => $value) {
                $maxChars = max(4, (int) floor(((float) $columns[$columnIndex]['width']) / 4.2));
                $lines = $this->pdfWrapText((string) $value, $maxChars, 3);
                $wrapped[] = $lines;
                $maxLines = max($maxLines, count($lines));
            }

            $rowHeight = max(22, 10 + ($maxLines * 9));
            if ($y - $rowHeight < 42) {
                $finishPage();
                $addPageHeader();
                $addTableHeader();
            }

            $x = $margin;
            $fill = $index % 2 === 1 ? '0.973 0.980 0.988 rg' : '1 1 1 rg';
            foreach ($columns as $columnIndex => $column) {
                $current[] = $fill;
                $current[] = $this->pdfRect($x, $y - $rowHeight, (float) $column['width'], $rowHeight, true);
                $current[] = '0.851 0.886 0.925 RG';
                $current[] = $this->pdfRect($x, $y - $rowHeight, (float) $column['width'], $rowHeight);
                $current[] = '0.122 0.161 0.216 rg';
                foreach ($wrapped[$columnIndex] as $lineIndex => $line) {
                    $current[] = $this->pdfText($x + 4, $y - 12 - ($lineIndex * 9), $line, 7, 'F1');
                }
                $x += (float) $column['width'];
            }

            $y -= $rowHeight;
        }

        if ($rows === []) {
            $current[] = '0.392 0.455 0.545 rg';
            $current[] = $this->pdfText($margin + 6, $y - 20, 'Tidak ada data tracer sesuai filter.', 9, 'F1');
        }

        $finishPage();

        return $this->composePdf($pages, $pageWidth, $pageHeight);
    }

    protected function composePdf(array $pageStreams, float $pageWidth, float $pageHeight): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];

        $pageObjectIds = [];
        foreach ($pageStreams as $stream) {
            $contentObjectId = count($objects) + 1;
            $streamObject = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
            $objects[] = $streamObject;

            $pageObjectId = count($objects) + 1;
            $pageObjectIds[] = $pageObjectId;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $pageWidth . ' ' . $pageHeight . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentObjectId . ' 0 R >>';
        }

        $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $pageObjectIds)) . '] /Count ' . count($pageObjectIds) . ' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    protected function pdfText(float $x, float $y, string $text, int $size = 8, string $font = 'F1'): string
    {
        return 'BT /' . $font . ' ' . $size . ' Tf 1 0 0 1 ' . $this->pdfNumber($x) . ' ' . $this->pdfNumber($y) . ' Tm (' . $this->pdfEscape($text) . ') Tj ET';
    }

    protected function pdfRect(float $x, float $y, float $width, float $height, bool $fill = false): string
    {
        return $this->pdfNumber($x) . ' ' . $this->pdfNumber($y) . ' ' . $this->pdfNumber($width) . ' ' . $this->pdfNumber($height) . ' re ' . ($fill ? 'f' : 'S');
    }

    protected function pdfLine(float $x1, float $y1, float $x2, float $y2): string
    {
        return $this->pdfNumber($x1) . ' ' . $this->pdfNumber($y1) . ' m ' . $this->pdfNumber($x2) . ' ' . $this->pdfNumber($y2) . ' l S';
    }

    protected function pdfNumber(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    protected function pdfEscape(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = $converted !== false ? $converted : $text;
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    protected function pdfValue(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : '-';
    }

    protected function pdfWrapText(string $text, int $maxChars, int $maxLines): array
    {
        $text = $this->pdfValue($text);
        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            while (strlen($word) > $maxChars) {
                $chunk = substr($word, 0, $maxChars);
                $word = substr($word, $maxChars);
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }
                $lines[] = $chunk;
            }

            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if (strlen($candidate) <= $maxChars) {
                $line = $candidate;
            } else {
                if ($line !== '') {
                    $lines[] = $line;
                }
                $line = $word;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        $lines = array_slice($lines !== [] ? $lines : ['-'], 0, $maxLines);
        if (count($lines) === $maxLines && strlen(implode(' ', $words)) > strlen(implode(' ', $lines))) {
            $last = $lines[$maxLines - 1];
            $lines[$maxLines - 1] = strlen($last) > 3 ? substr($last, 0, -3) . '...' : $last;
        }

        return $lines;
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
