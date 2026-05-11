<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL PELAMAR
|-------------------------------------------------------------------
| Model ini menangani data utama pelamar untuk modul Super Admin,
| termasuk query server-side DataTables dengan join ke pengguna,
| peran, dan data alumni.
| Alur kerja: controller memanggil getDataTables() saat halaman tabel
| diminta via AJAX, lalu model membentuk query filter, search, order,
| dan pagination yang sesuai dengan payload DataTables.
|
| Tips Debugging:
| - Jika kolom tabel kosong, cek relasi join tb_pelamar ke tb_pengguna dan tb_peran.
| - Jika pencarian tidak bekerja, cek parameter search[value] dari DataTables.
*/
class PelamarModel extends Model
{
    protected $table            = 'tb_pelamar';
    protected $primaryKey       = 'id_pelamar';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_pengguna',
        'account_id',
        'foto',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'nomer_nik',
        'status_pendaftaran',
        'terdaftar_pada',
        'diaktivasi_oleh',
        'diaktivasi_pada',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    /*
    |-------------------------------------------------------------------
    | METHOD GET DATATABLES
    |-------------------------------------------------------------------
    | Method ini menyusun response data DataTables server-side lengkap
    | dengan total data, total setelah filter, dan baris hasil query.
    | Alur kerja:
    | 1. Bangun query dasar join pelamar, pengguna, peran, alumni.
    | 2. Terapkan filter jenis pelamar, status akun, status pendaftaran.
    | 3. Terapkan search, urutan, dan pagination dari payload DataTables.
    |
    | Tips Debugging:
    | - Jika jumlah total salah, cek recordsTotal dan recordsFiltered memakai query berbeda.
    | - Jika filter tidak bereaksi, cek nama field request sesuai data-kt-user-table-filter.
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
            'jenis_pelamar'      => trim((string) ($request->getVar('jenis_pelamar') ?? '')),
            'status_aktif'       => trim((string) ($request->getVar('status_aktif') ?? '')),
            'status_pendaftaran' => trim((string) ($request->getVar('status_pendaftaran') ?? '')),
            'search'             => trim((string) (($request->getVar('search')['value'] ?? '') ?: '')),
            'orderColumn'        => (int) ($request->getVar('order')[0]['column'] ?? -1),
            'orderDir'           => strtolower((string) ($request->getVar('order')[0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC',
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
    | METHOD GENERATE ACCOUNT ID
    |-------------------------------------------------------------------
    | Method ini membentuk account_id pelamar dengan pola harian dan
    | nomor urut empat digit agar identitas akun mudah dilacak.
    | Format akhir: PLM-YYYYMMDD0001
    |
    | Tips Debugging:
    | - Jika nomor urut selalu kembali 0001, cek query LIKE prefix tanggal hari ini.
    */
    public function generateAccountId(): string
    {
        $tanggal = date('Ymd');
        $prefix  = 'PLM-' . $tanggal;
        $this->db->transStart();

        $this->db->query(
            'INSERT INTO tb_counter_pelamar (tanggal_generate, nomor_terakhir)
            VALUES (?, LAST_INSERT_ID(1))
            ON DUPLICATE KEY UPDATE nomor_terakhir = LAST_INSERT_ID(nomor_terakhir + 1), diperbarui_pada = CURRENT_TIMESTAMP',
            [$tanggal]
        );

        $row = $this->db->query('SELECT LAST_INSERT_ID() AS nomor_urut')->getRowArray();

        $this->db->transComplete();

        $nomorUrut = (int) ($row['nomor_urut'] ?? 0);

        return $prefix . str_pad((string) $nomorUrut, 4, '0', STR_PAD_LEFT);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AMBIL DETAIL BY ID
    |-------------------------------------------------------------------
    | Method helper ini mengambil satu baris pelamar lengkap dengan
    | join yang sama seperti tabel agar controller mudah memvalidasi
    | data saat update, hapus, atau aktivasi.
    */
    public function ambilDetailById(int $idPelamar): ?array
    {
        return $this->baseDataTablesQuery()
            ->where('p.id_pelamar', $idPelamar)
            ->get()
            ->getRowArray();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD BASE DATATABLES QUERY
    |-------------------------------------------------------------------
    | Method ini membangun query dasar join seluruh tabel yang dibutuhkan
    | oleh modul pelamar.
    */
    protected function baseDataTablesQuery(): BaseBuilder
    {
        return $this->db->table($this->table . ' p')
            ->select([
                'p.id_pelamar',
                'p.id_pengguna',
                'p.account_id',
                'p.foto',
                'p.jenis_kelamin',
                'p.tempat_lahir',
                'p.tanggal_lahir',
                'p.alamat',
                'p.nomer_nik',
                'p.status_pendaftaran',
                'p.terdaftar_pada',
                'p.diaktivasi_oleh',
                'p.diaktivasi_pada',
                'p.dibuat_pada',
                'p.diperbarui_pada',
                'u.nama_lengkap',
                'u.email',
                'u.nomor_telepon',
                'u.foto_profil',
                'u.status_aktif',
                'u.terakhir_login',
                'r.id_peran',
                'r.nama_peran',
                'r.slug_peran',
                'a.id_alumni',
                'a.id_angkatan',
                'a.id_kompetensi',
                'a.nis',
                'a.nisn',
                'a.no_ijazah',
                'a.status_verifikasi',
            ])
            ->join('tb_pengguna u', 'u.id_pengguna = p.id_pengguna', 'inner')
            ->join('tb_peran r', 'r.id_peran = u.id_peran', 'inner')
            ->join('tb_alumni a', 'a.id_pelamar = p.id_pelamar', 'left');
    }

    /*
    |-------------------------------------------------------------------
    | METHOD APPLY DATATABLES FILTERS
    |-------------------------------------------------------------------
    | Method ini memasang filter dropdown dan pencarian keyword.
    */
    protected function applyDataTablesFilters(BaseBuilder $builder, array $filters): void
    {
        if ($filters['jenis_pelamar'] !== '') {
            $builder->where('r.slug_peran', $filters['jenis_pelamar']);
        }

        if ($filters['status_aktif'] !== '') {
            $builder->where('u.status_aktif', (int) $filters['status_aktif']);
        }

        if ($filters['status_pendaftaran'] !== '') {
            $builder->where('p.status_pendaftaran', $filters['status_pendaftaran']);
        }

        if ($filters['search'] !== '') {
            $keyword = $filters['search'];

            $builder->groupStart()
                ->like('u.nama_lengkap', $keyword)
                ->orLike('u.email', $keyword)
                ->orLike('p.account_id', $keyword)
                ->groupEnd();
        }
    }

    /*
    |-------------------------------------------------------------------
    | METHOD APPLY DATATABLES ORDERING
    |-------------------------------------------------------------------
    | Method ini memetakan index kolom DataTables ke kolom SQL yang
    | aman untuk diurutkan.
    */
    protected function applyDataTablesOrdering(BaseBuilder $builder, int $orderColumn, string $orderDir): void
    {
        $mapOrder = [
            1 => 'p.account_id',
            2 => 'u.nama_lengkap',
            3 => 'r.slug_peran',
            4 => 'p.status_pendaftaran',
            5 => 'u.terakhir_login',
            6 => 'u.status_aktif',
            7 => 'p.terdaftar_pada',
        ];

        if (isset($mapOrder[$orderColumn])) {
            $builder->orderBy($mapOrder[$orderColumn], $orderDir);
            return;
        }

        $builder->orderBy('p.id_pelamar', 'DESC');
    }
}
