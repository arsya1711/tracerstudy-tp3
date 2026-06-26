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
            'no_ijazah'     => 'permit_empty|max_length[60]',
            'id_angkatan'   => 'permit_empty|integer',
            'id_kompetensi' => 'permit_empty|integer',
            'id_aktivitas'  => 'permit_empty|integer',
            'tanggal_lahir' => 'permit_empty|valid_date[Y-m-d]',
            'status_pendaftaran' => 'required|in_list[menunggu_aktivasi,aktif]',
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

        $statusPendaftaran = (string) $this->request->getPost('status_pendaftaran');
        $statusVerifikasi = $statusPendaftaran === 'aktif' ? 'aktif' : 'menunggu_aktivasi';

        $payloadAlumni = [
            'nis'                 => $this->ambilStringKosongJadiNull('nis'),
            'nisn'                => $this->ambilStringKosongJadiNull('nisn'),
            'no_ijazah'           => $this->ambilStringKosongJadiNull('no_ijazah'),
            'jenis_kelamin'       => $this->ambilStringKosongJadiNull('jenis_kelamin'),
            'tempat_lahir'        => $this->ambilStringKosongJadiNull('tempat_lahir'),
            'tanggal_lahir'       => $this->ambilStringKosongJadiNull('tanggal_lahir'),
            'id_angkatan'         => $this->ambilIntegerKosongJadiNull('id_angkatan'),
            'id_kompetensi'       => $this->ambilIntegerKosongJadiNull('id_kompetensi'),
            'alamat'              => $this->ambilStringKosongJadiNull('alamat'),
            'status_pendaftaran'  => $statusPendaftaran,
            'status_verifikasi'   => $statusVerifikasi,
        ];

        if ($statusPendaftaran === 'aktif') {
            $payloadAlumni['diverifikasi_oleh'] = (int) session()->get('id_pengguna') ?: null;
            $payloadAlumni['diverifikasi_pada'] = date('Y-m-d H:i:s');
        }

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

    protected function ambilFilterDariRequest(): array
    {
        return [
            'search'        => trim((string) $this->request->getGet('q')),
            'id_angkatan'   => (int) ($this->request->getGet('id_angkatan') ?? 0),
            'id_kompetensi' => (int) ($this->request->getGet('id_kompetensi') ?? 0),
            'id_aktivitas'  => (int) ($this->request->getGet('id_aktivitas') ?? 0),
            'status'        => trim((string) $this->request->getGet('status')),
        ];
    }

    protected function bangunExcelTracer(array $rows, array $filters): string
    {
        $total = count($rows);
        $sudahTracer = 0;
        $belumTracer = 0;

        foreach ($rows as $index => $row) {
            if ((int) ($row['id_tracer'] ?? 0) > 0) {
                $sudahTracer++;
            } else {
                $belumTracer++;
            }
        }

        $filterText = $this->formatFilterExcel($filters);
        $dibuatPada = date('d/m/Y H:i');

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta charset="UTF-8">';
        $html .= '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Laporan Tracer</x:Name><x:WorksheetOptions><x:FreezePanes/><x:FrozenNoSplit/><x:SplitHorizontal>7</x:SplitHorizontal><x:TopRowBottomPane>7</x:TopRowBottomPane><x:ActivePane>2</x:ActivePane></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        $html .= '<style>';
        $html .= 'body{font-family:Calibri,Arial,sans-serif;font-size:11pt;color:#1f2937;}';
        $html .= 'table{border-collapse:collapse;}';
        $html .= '.title{font-size:18pt;font-weight:700;color:#0b1f4d;}';
        $html .= '.subtitle{font-size:10pt;color:#64748b;}';
        $html .= '.summary-label{background:#eef4ff;font-weight:700;color:#0b1f4d;border:1px solid #c7d7fe;padding:8px;}';
        $html .= '.summary-value{background:#f8fbff;border:1px solid #c7d7fe;padding:8px;text-align:center;font-weight:700;}';
        $html .= '.header{background:#0b1f4d;color:#ffffff;font-weight:700;border:1px solid #243b6b;padding:8px;text-align:center;}';
        $html .= '.cell{border:1px solid #d9e2ec;padding:7px;vertical-align:top;}';
        $html .= '.alt{background:#f8fafc;}';
        $html .= '.status-ok{background:#dcfce7;color:#166534;font-weight:700;text-align:center;}';
        $html .= '.status-warn{background:#fef3c7;color:#92400e;font-weight:700;text-align:center;}';
        $html .= '.text{mso-number-format:"\\@";}';
        $html .= '.center{text-align:center;}';
        $html .= 'td{white-space:normal;}';
        $html .= '</style></head><body>';
        $html .= '<table>';
        $html .= '<col width="45"><col width="115"><col width="190"><col width="220"><col width="100"><col width="115"><col width="90"><col width="220"><col width="150"><col width="170"><col width="130"><col width="160"><col width="220"><col width="220"><col width="150"><col width="130"><col width="170"><col width="240">';
        $html .= '<tr><td colspan="18" class="title">Laporan Tracer Alumni</td></tr>';
        $html .= '<tr><td colspan="18" class="subtitle">Sistem Informasi Tracer Study</td></tr>';
        $html .= '<tr><td colspan="18" class="subtitle">Dibuat pada: ' . $this->excelCell($dibuatPada) . '</td></tr>';
        $html .= '<tr><td colspan="18" class="subtitle">Filter: ' . $this->excelCell($filterText) . '</td></tr>';
        $html .= '<tr><td colspan="18">&nbsp;</td></tr>';
        $html .= '<tr>';
        $html .= '<td class="summary-label" colspan="3">Total Alumni</td><td class="summary-value">' . $total . '</td>';
        $html .= '<td class="summary-label" colspan="3">Sudah Mengisi Tracer</td><td class="summary-value">' . $sudahTracer . '</td>';
        $html .= '<td class="summary-label" colspan="3">Belum Mengisi Tracer</td><td class="summary-value">' . $belumTracer . '</td>';
        $html .= '</tr>';
        $html .= '<tr><td colspan="18">&nbsp;</td></tr>';
        $html .= '<tr>';

        $headers = [
            'No',
            'Account ID',
            'Nama Alumni',
            'Email',
            'NIS',
            'NISN',
            'Angkatan',
            'Kompetensi',
            'Aktivitas',
            'Status Tracer',
            'Tanggal Pengisian',
            'Posisi Kerja',
            'Nama Instansi',
            'Universitas',
            'Program Studi',
            'Status Kuliah',
            'Nama Usaha',
            'Rencana Kedepan',
        ];

        foreach ($headers as $header) {
            $html .= '<td class="header">' . $this->excelCell($header) . '</td>';
        }

        $html .= '</tr>';

        foreach ($rows as $index => $row) {
            $sudahMengisi = (int) ($row['id_tracer'] ?? 0) > 0;
            $rowClass = $index % 2 === 1 ? ' alt' : '';
            $statusClass = $sudahMengisi ? 'status-ok' : 'status-warn';

            $values = [
                $index + 1,
                $this->excelValue($row['account_id'] ?? ''),
                $this->excelValue($row['nama_lengkap'] ?? ''),
                $this->excelValue($row['email'] ?? ''),
                $this->excelValue($row['nis'] ?? ''),
                $this->excelValue($row['nisn'] ?? ''),
                $this->excelValue($row['tahun_lulus'] ?? ''),
                $this->formatKompetensiExcel($row),
                $this->excelValue($row['nama_aktivitas'] ?? 'Belum Mengisi Tracer'),
                $sudahMengisi ? 'Sudah Mengisi Tracer' : 'Belum Mengisi Tracer',
                $this->excelValue($row['tanggal_pengisian'] ?? ''),
                $this->excelValue($row['posisi_kerja'] ?? ''),
                $this->excelValue($row['nama_instansi'] ?? ''),
                $this->excelValue($row['universitas'] ?? ''),
                $this->excelValue($row['program_studi'] ?? ''),
                $this->excelValue($row['status_kuliah'] ?? ''),
                $this->excelValue($row['nama_usaha'] ?? ''),
                $this->excelValue($row['rencana_kedepan'] ?? ''),
            ];

            $html .= '<tr>';

            foreach ($values as $columnIndex => $value) {
                $class = $columnIndex === 9 ? 'cell ' . $statusClass : 'cell text' . $rowClass;
                $html .= '<td class="' . $class . '">' . $this->excelCell((string) $value) . '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        return "\xEF\xBB\xBF" . $html;
    }

    protected function excelValue(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : '-';
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

        $total = count($rows);
        $sudahTracer = 0;
        foreach ($rows as $row) {
            if ((int) ($row['id_tracer'] ?? 0) > 0) {
                $sudahTracer++;
            }
        }

        $pages = [];
        $current = [];
        $y = $pageHeight - $margin;
        $pageNumber = 1;

        $addPageHeader = function () use (&$current, &$y, $pageHeight, $margin, $contentWidth, $filters, $total, $sudahTracer): void {
            $belumTracer = $total - $sudahTracer;
            $current[] = '0.043 0.122 0.302 rg';
            $current[] = $this->pdfText($margin, $y - 4, 'Laporan Tracer Alumni', 18, 'F2');
            $y -= 26;
            $current[] = '0.392 0.455 0.545 rg';
            $current[] = $this->pdfText($margin, $y, 'Sistem Informasi Tracer Study', 9, 'F1');
            $y -= 14;
            $current[] = $this->pdfText($margin, $y, 'Dibuat pada: ' . date('d/m/Y H:i') . '   |   Filter: ' . $this->formatFilterExcel($filters), 8, 'F1');
            $y -= 20;

            $boxWidth = 155;
            $gap = 10;
            $summary = [
                ['Total Alumni', (string) $total],
                ['Sudah Tracer', (string) $sudahTracer],
                ['Belum Tracer', (string) $belumTracer],
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
