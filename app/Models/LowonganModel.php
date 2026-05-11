<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL DATA LOWONGAN
|-------------------------------------------------------------------
| Model ini menangani query utama modul lowongan, khususnya untuk
| kebutuhan DataTables, detail data, dan validasi relasi bisnis
| dengan perusahaan yang memiliki kerjasama rekrutmen.
|
| Tips Debugging:
| - Jika filter tabel tidak bekerja, cek helper query builder di bawah.
| - Jika DUDI rekrutmen kosong, periksa tabel kerjasama dan pivotnya.
*/
class LowonganModel extends Model
{
    protected $table            = 'tb_lowongan';
    protected $primaryKey       = 'id_lowongan';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_perusahaan',
        'dibuat_oleh',
        'judul_lowongan',
        'posisi',
        'slug_lowongan',
        'flyer_lowongan',
        'deskripsi_pekerjaan',
        'kualifikasi',
        'jumlah_kebutuhan',
        'jenis_pekerjaan',
        'sistem_kerja',
        'pendidikan_min',
        'pengalaman_min',
        'rentang_gaji',
        'lokasi_kerja',
        'batas_lamaran',
        'tayang_hingga',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    /*
    |-------------------------------------------------------------------
    | SUPLAI DATA UNTUK DATATABLES
    |-------------------------------------------------------------------
    | Method ini mengatur pagination, pencarian, filter, dan ordering
    | untuk daftar lowongan yang ditampilkan di halaman superadmin.
    |
    | Tips Debugging:
    | - Jika jumlah recordsFiltered aneh, periksa applyDataTablesFilters().
    */
    public function getDataTables(object $request): array
    {
        $draw   = (int) ($request->getVar('draw') ?? 0);
        $start  = max(0, (int) ($request->getVar('start') ?? 0));
        $length = (int) ($request->getVar('length') ?? 10);

        if ($length < 1) {
            $length = 10;
        }

        $filters = [
            'id_perusahaan' => (int) ($request->getVar('id_perusahaan') ?? 0),
            'status'        => trim((string) ($request->getVar('status') ?? '')),
            'search'        => trim((string) (($request->getVar('search')['value'] ?? '') ?: '')),
            'orderColumn'   => (int) ($request->getVar('order')[0]['column'] ?? -1),
            'orderDir'      => strtolower((string) ($request->getVar('order')[0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC',
        ];

        $recordsTotal = (int) $this->baseDataTablesQuery()->countAllResults();

        $filteredBuilder = $this->baseDataTablesQuery();
        $this->applyDataTablesFilters($filteredBuilder, $filters);
        $recordsFiltered = (int) $filteredBuilder->countAllResults();

        $dataBuilder = $this->baseDataTablesQuery();
        $this->applyDataTablesFilters($dataBuilder, $filters);
        $this->applyDataTablesOrdering($dataBuilder, $filters['orderColumn'], $filters['orderDir']);
        $dataBuilder->limit($length, $start);

        return [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $dataBuilder->get()->getResultArray(),
        ];
    }

    /*
    |-------------------------------------------------------------------
    | AMBIL DETAIL LOWONGAN
    |-------------------------------------------------------------------
    | Detail dipakai terutama untuk kebutuhan edit, sehingga query
    | memakai basis join yang sama dengan daftar tabel agar konsisten.
    */
    public function ambilDetailById(int $idLowongan): ?array
    {
        return $this->baseDataTablesQuery()
            ->where('l.id_lowongan', $idLowongan)
            ->get()
            ->getRowArray();
    }

    /*
    |-------------------------------------------------------------------
    | DAFTAR DUDI DENGAN KERJASAMA REKRUTMEN
    |-------------------------------------------------------------------
    | Hanya perusahaan yang memenuhi syarat kerjasama rekrutmen yang
    | boleh dipakai saat membuat lowongan baru.
    |
    | Tips Debugging:
    | - Jika dropdown DUDI kosong, cek tb_kerjasama.slug_kerjasama
    |   apakah benar menggunakan nilai `rekrutmen`.
    */
    public function ambilDaftarPerusahaanRekrutmen(): array
    {
        if (
            ! $this->db->tableExists('tb_perusahaan')
            || ! $this->db->tableExists('tb_perusahaan_kerjasama')
            || ! $this->db->tableExists('tb_kerjasama')
        ) {
            return [];
        }

        return $this->db->table('tb_perusahaan p')
            ->distinct()
            ->select('p.id_perusahaan, p.nama_perusahaan')
            ->join('tb_perusahaan_kerjasama pk', 'pk.id_perusahaan = p.id_perusahaan', 'inner')
            ->join('tb_kerjasama k', 'k.id_kerjasama = pk.id_kerjasama', 'inner')
            ->where('p.status_aktif', 1)
            ->where('k.status_aktif', 1)
            ->where('k.slug_kerjasama', 'rekrutmen')
            ->orderBy('p.nama_perusahaan', 'ASC')
            ->get()
            ->getResultArray();
    }

    /*
    |-------------------------------------------------------------------
    | VALIDASI RELASI DUDI REKRUTMEN
    |-------------------------------------------------------------------
    | Digunakan backend sebelum simpan/update untuk memastikan lowongan
    | hanya dibuat oleh perusahaan yang memang memiliki kerjasama sesuai.
    */
    public function perusahaanMemilikiKerjasamaRekrutmen(int $idPerusahaan): bool
    {
        if (
            $idPerusahaan <= 0
            || ! $this->db->tableExists('tb_perusahaan_kerjasama')
            || ! $this->db->tableExists('tb_kerjasama')
        ) {
            return false;
        }

        return $this->db->table('tb_perusahaan_kerjasama pk')
            ->join('tb_kerjasama k', 'k.id_kerjasama = pk.id_kerjasama', 'inner')
            ->where('pk.id_perusahaan', $idPerusahaan)
            ->where('k.status_aktif', 1)
            ->where('k.slug_kerjasama', 'rekrutmen')
            ->countAllResults() > 0;
    }

    /*
    |-------------------------------------------------------------------
    | CEK SLUG DUPLIKAT
    |-------------------------------------------------------------------
    | Slug lowongan harus unik agar identitas data tetap jelas dan aman
    | dipakai untuk URL atau referensi internal di masa depan.
    */
    public function slugDipakai(string $slug, ?int $idLowongan = null): bool
    {
        $builder = $this->builder()->where('slug_lowongan', $slug);

        if ($idLowongan !== null) {
            $builder->where('id_lowongan !=', $idLowongan);
        }

        return $builder->countAllResults() > 0;
    }

    /*
    |-------------------------------------------------------------------
    | DAFTAR LOWONGAN AKTIF UNTUK PELAMAR
    |-------------------------------------------------------------------
    | Method ini mengambil lowongan yang masih aktif tayang dan masih
    | berada dalam masa pendaftaran agar pelamar hanya melihat peluang
    | yang memang bisa dilamar.
    |
    | Tips Debugging:
    | - Jika lowongan aktif tidak muncul, cek status lowongan, tanggal
    |   batas_lamaran, dan relasi perusahaan masih aktif.
    */
    public function ambilDaftarAktifUntukPelamar(string $keyword = ''): array
    {
        $builder = $this->basePelamarQuery();
        $this->applyActivePelamarFilter($builder);

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('l.judul_lowongan', $keyword)
                ->orLike('l.posisi', $keyword)
                ->orLike('p.nama_perusahaan', $keyword)
                ->orLike('l.lokasi_kerja', $keyword)
                ->groupEnd();
        }

        return $builder
            ->orderBy('l.batas_lamaran', 'ASC')
            ->orderBy('l.id_lowongan', 'DESC')
            ->get()
            ->getResultArray();
    }

    /*
    |-------------------------------------------------------------------
    | DETAIL LOWONGAN AKTIF BERDASARKAN SLUG
    |-------------------------------------------------------------------
    | Dipakai oleh halaman detail lowongan pelamar agar URL bisa lebih
    | rapi dan identitas lowongan tetap konsisten.
    */
    public function ambilDetailAktifBySlug(string $slugLowongan): ?array
    {
        $builder = $this->basePelamarQuery();
        $this->applyActivePelamarFilter($builder);

        return $builder
            ->where('l.slug_lowongan', trim($slugLowongan))
            ->get()
            ->getRowArray();
    }

    /*
    |-------------------------------------------------------------------
    | DETAIL LOWONGAN AKTIF BERDASARKAN ID
    |-------------------------------------------------------------------
    | Dipakai saat submit lamaran karena form POST lebih aman mengirim
    | id_lowongan dibanding slug yang rawan berubah di masa depan.
    */
    public function ambilDetailAktifById(int $idLowongan): ?array
    {
        $builder = $this->basePelamarQuery();
        $this->applyActivePelamarFilter($builder);

        return $builder
            ->where('l.id_lowongan', $idLowongan)
            ->get()
            ->getRowArray();
    }

    /*
    |-------------------------------------------------------------------
    | DAFTAR LOWONGAN BERDASARKAN PERUSAHAAN
    |-------------------------------------------------------------------
    | Dipakai Admin DUDI/HRD untuk melihat lowongan yang dimiliki oleh
    | perusahaan tempat akun tersebut ditugaskan.
    */
    public function ambilByPerusahaan(int $idPerusahaan, array $filters = []): array
    {
        if ($idPerusahaan <= 0) {
            return [];
        }

        $builder = $this->baseDataTablesQuery()
            ->where('l.id_perusahaan', $idPerusahaan);

        $keyword = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($status !== '') {
            $builder->where('l.status', $status);
        }

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('l.judul_lowongan', $keyword)
                ->orLike('l.posisi', $keyword)
                ->orLike('l.kualifikasi', $keyword)
                ->orLike('l.lokasi_kerja', $keyword)
                ->groupEnd();
        }

        return $builder
            ->orderBy('l.dibuat_pada', 'DESC')
            ->orderBy('l.id_lowongan', 'DESC')
            ->get()
            ->getResultArray();
    }

    /*
    |-------------------------------------------------------------------
    | RINGKASAN LOWONGAN PERUSAHAAN
    |-------------------------------------------------------------------
    | Mengembalikan jumlah lowongan per status untuk dashboard Admin
    | DUDI tanpa perlu mengulang query di controller.
    */
    public function hitungRingkasanPerusahaan(int $idPerusahaan): array
    {
        $default = [
            'total' => 0,
            'draft' => 0,
            'aktif' => 0,
            'ditutup' => 0,
            'kadaluarsa' => 0,
        ];

        if ($idPerusahaan <= 0 || ! $this->db->tableExists($this->table)) {
            return $default;
        }

        $rows = $this->db->table($this->table)
            ->select('status, COUNT(*) AS total')
            ->where('id_perusahaan', $idPerusahaan)
            ->groupBy('status')
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
    | QUERY DASAR DATATABLES
    |-------------------------------------------------------------------
    | Query dasar ini menjadi fondasi untuk hitung total data, filter,
    | ambil detail, dan sorting agar struktur select tetap seragam.
    */
    protected function baseDataTablesQuery(): BaseBuilder
    {
        return $this->db->table($this->table . ' l')
            ->select([
                'l.id_lowongan',
                'l.id_perusahaan',
                'l.dibuat_oleh',
                'l.judul_lowongan',
                'l.posisi',
                'l.slug_lowongan',
                'l.flyer_lowongan',
                'l.deskripsi_pekerjaan',
                'l.kualifikasi',
                'l.jumlah_kebutuhan',
                'l.jenis_pekerjaan',
                'l.sistem_kerja',
                'l.pendidikan_min',
                'l.pengalaman_min',
                'l.rentang_gaji',
                'l.lokasi_kerja',
                'l.batas_lamaran',
                'l.tayang_hingga',
                'l.status',
                'l.dibuat_pada',
                'l.diperbarui_pada',
                'p.nama_perusahaan',
                'u.nama_lengkap AS pemosting_nama',
            ])
            ->join('tb_perusahaan p', 'p.id_perusahaan = l.id_perusahaan', 'inner')
            ->join('tb_pengguna u', 'u.id_pengguna = l.dibuat_oleh', 'left');
    }

    /*
    |-------------------------------------------------------------------
    | PENERAPAN FILTER DATATABLES
    |-------------------------------------------------------------------
    | Filter perusahaan, status, dan search umum diterapkan di sini
    | supaya dapat dipakai ulang oleh count dan query data utama.
    */
    protected function applyDataTablesFilters(BaseBuilder $builder, array $filters): void
    {
        if ($filters['id_perusahaan'] > 0) {
            $builder->where('l.id_perusahaan', $filters['id_perusahaan']);
        }

        if ($filters['status'] !== '') {
            $builder->where('l.status', $filters['status']);
        }

        if ($filters['search'] !== '') {
            $keyword = $filters['search'];

            $builder->groupStart()
                ->like('p.nama_perusahaan', $keyword)
                ->orLike('l.judul_lowongan', $keyword)
                ->orLike('l.posisi', $keyword)
                ->orLike('l.kualifikasi', $keyword)
                ->orLike('u.nama_lengkap', $keyword)
                ->groupEnd();
        }
    }

    /*
    |-------------------------------------------------------------------
    | PENERAPAN SORTING DATATABLES
    |-------------------------------------------------------------------
    | Mapping kolom frontend ke kolom database dipusatkan di sini agar
    | perubahan urutan tabel lebih mudah dipelihara.
    |
    | Tips Debugging:
    | - Jika sorting salah kolom, cek indeks mapOrder dengan urutan
    |   kolom pada DataTables di file lowongan.js.
    */
    protected function applyDataTablesOrdering(BaseBuilder $builder, int $orderColumn, string $orderDir): void
    {
        $mapOrder = [
            1 => 'p.nama_perusahaan',
            2 => 'l.judul_lowongan',
            3 => 'l.posisi',
            4 => 'l.kualifikasi',
            5 => 'u.nama_lengkap',
            6 => 'l.status',
        ];

        if (isset($mapOrder[$orderColumn])) {
            $builder->orderBy($mapOrder[$orderColumn], $orderDir);
            return;
        }

        $builder->orderBy('l.id_lowongan', 'DESC');
    }

    /*
    |-------------------------------------------------------------------
    | QUERY DASAR LOWONGAN PELAMAR
    |-------------------------------------------------------------------
    | Query ini sengaja dipisah dari query DataTables superadmin karena
    | kebutuhan pelamar lebih fokus pada lowongan aktif yang bisa dilamar.
    */
    protected function basePelamarQuery(): BaseBuilder
    {
        return $this->db->table($this->table . ' l')
            ->select([
                'l.id_lowongan',
                'l.id_perusahaan',
                'l.dibuat_oleh',
                'l.judul_lowongan',
                'l.posisi',
                'l.slug_lowongan',
                'l.flyer_lowongan',
                'l.deskripsi_pekerjaan',
                'l.kualifikasi',
                'l.jumlah_kebutuhan',
                'l.jenis_pekerjaan',
                'l.sistem_kerja',
                'l.pendidikan_min',
                'l.pengalaman_min',
                'l.rentang_gaji',
                'l.lokasi_kerja',
                'l.batas_lamaran',
                'l.tayang_hingga',
                'l.status',
                'l.dibuat_pada',
                'p.nama_perusahaan',
                'p.kota',
                'p.logo',
                'u.nama_lengkap AS pemosting_nama',
            ])
            ->join('tb_perusahaan p', 'p.id_perusahaan = l.id_perusahaan', 'inner')
            ->join('tb_pengguna u', 'u.id_pengguna = l.dibuat_oleh', 'left');
    }

    /*
    |-------------------------------------------------------------------
    | FILTER LOWONGAN YANG MASIH BISA DILAMAR
    |-------------------------------------------------------------------
    | Filter ini memastikan lowongan yang tampil ke pelamar benar-benar
    | aktif, perusahaan masih aktif, dan deadline belum lewat.
    */
    protected function applyActivePelamarFilter(BaseBuilder $builder): void
    {
        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');

        $builder->where('l.status', 'aktif');

        if ($this->db->fieldExists('status_aktif', 'tb_perusahaan')) {
            $builder->where('p.status_aktif', 1);
        }

        $builder->groupStart()
            ->where('l.batas_lamaran IS NULL', null, false)
            ->orWhere('l.batas_lamaran >=', $today)
            ->groupEnd();

        $builder->groupStart()
            ->where('l.tayang_hingga IS NULL', null, false)
            ->orWhere('l.tayang_hingga >=', $now)
            ->groupEnd();
    }
}
