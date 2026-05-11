<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL PERUSAHAAN / DUDI
|-------------------------------------------------------------------
| Model ini menangani data perusahaan mitra, termasuk kebutuhan tabel
| Data DUDI, relasi kerjasama, dan pencarian perusahaan berdasarkan
| akun Admin DUDI.
|
| Alur kerja:
| 1. Super Admin memakai model ini untuk CRUD Data DUDI.
| 2. Admin DUDI memakai ambilByPengguna() sebagai pagar akses.
| 3. Relasi kerjasama dibaca untuk badge dan validasi lowongan.
|
| Tips Debugging:
| - Jika Admin DUDI tidak punya dashboard, cek tb_perusahaan.id_pengguna.
| - Jika badge kerjasama kosong, cek tb_perusahaan_kerjasama.
*/
class PerusahaanModel extends Model
{
    protected $table            = 'tb_perusahaan';
    protected $primaryKey       = 'id_perusahaan';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_pengguna',
        'nama_perusahaan',
        'slug_perusahaan',
        'bidang_usaha',
        'deskripsi',
        'alamat',
        'kota',
        'penanggung_jawab',
        'no_telepon',
        'email',
        'website',
        'logo',
        'status_verifikasi',
        'status_aktif',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    public function getDataTables(object $request): array
    {
        $draw   = (int) ($request->getVar('draw') ?? 0);
        $start  = max(0, (int) ($request->getVar('start') ?? 0));
        $length = (int) ($request->getVar('length') ?? 10);

        if ($length < 1) {
            $length = 10;
        }

        $filters = [
            'kota'        => trim((string) ($request->getVar('kota') ?? '')),
            'search'      => trim((string) (($request->getVar('search')['value'] ?? '') ?: '')),
            'orderColumn' => (int) ($request->getVar('order')[0]['column'] ?? -1),
            'orderDir'    => strtolower((string) ($request->getVar('order')[0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC',
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

    public function ambilDetailById(int $idPerusahaan): ?array
    {
        return $this->baseDataTablesQuery()
            ->where('p.id_perusahaan', $idPerusahaan)
            ->get()
            ->getRowArray();
    }

    /*
    |-------------------------------------------------------------------
    | AMBIL PERUSAHAAN BERDASARKAN AKUN ADMIN DUDI
    |-------------------------------------------------------------------
    | Relasi id_pengguna pada tb_perusahaan menjadi pagar akses agar
    | satu admin DUDI hanya bekerja pada perusahaan yang ditugaskan.
    |
    | Tips Debugging:
    | - Jika admin DUDI selalu ditolak, cek tb_perusahaan.id_pengguna.
    */
    public function ambilByPengguna(int $idPengguna): ?array
    {
        if ($idPengguna <= 0) {
            return null;
        }

        return $this->baseDataTablesQuery()
            ->where('p.id_pengguna', $idPengguna)
            ->get()
            ->getRowArray();
    }

    public function ambilDaftarKota(): array
    {
        return $this->db->table($this->table)
            ->select('kota')
            ->where('status_aktif', 1)
            ->where('kota IS NOT NULL', null, false)
            ->where('kota !=', '')
            ->groupBy('kota')
            ->orderBy('kota', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function ambilMapKerjasamaUntukPerusahaan(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

        if (
            $ids === []
            || ! $this->db->tableExists('tb_perusahaan_kerjasama')
            || ! $this->db->tableExists('tb_kerjasama')
        ) {
            return [];
        }

        $rows = $this->db->table('tb_perusahaan_kerjasama pk')
            ->select('pk.id_perusahaan, pk.id_kerjasama, k.nama_kerjasama, k.slug_kerjasama')
            ->join('tb_kerjasama k', 'k.id_kerjasama = pk.id_kerjasama', 'inner')
            ->whereIn('pk.id_perusahaan', $ids)
            ->where('k.status_aktif', 1)
            ->orderBy('k.nama_kerjasama', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];

        foreach ($rows as $row) {
            $idPerusahaan = (int) ($row['id_perusahaan'] ?? 0);

            if (! isset($map[$idPerusahaan])) {
                $map[$idPerusahaan] = [
                    'kerjasama_ids'   => [],
                    'kerjasama_nama'  => [],
                    'kerjasama_slug'  => [],
                ];
            }

            $map[$idPerusahaan]['kerjasama_ids'][] = (int) ($row['id_kerjasama'] ?? 0);
            $map[$idPerusahaan]['kerjasama_nama'][] = (string) ($row['nama_kerjasama'] ?? '');
            $map[$idPerusahaan]['kerjasama_slug'][] = (string) ($row['slug_kerjasama'] ?? '');
        }

        return $map;
    }

    protected function baseDataTablesQuery(): BaseBuilder
    {
        return $this->db->table($this->table . ' p')
            ->select([
                'p.id_perusahaan',
                'p.id_pengguna',
                'p.nama_perusahaan',
                'p.slug_perusahaan',
                'p.bidang_usaha',
                'p.deskripsi',
                'p.alamat',
                'p.kota',
                'p.penanggung_jawab',
                'p.no_telepon',
                'p.email',
                'p.website',
                'p.logo',
                'p.status_verifikasi',
                'p.status_aktif',
                'p.dibuat_pada',
                'p.diperbarui_pada',
            ])
            ->where('p.status_aktif', 1);
    }

    protected function applyDataTablesFilters(BaseBuilder $builder, array $filters): void
    {
        if ($filters['kota'] !== '') {
            $builder->where('p.kota', $filters['kota']);
        }

        if ($filters['search'] !== '') {
            $keyword = $filters['search'];

            $builder->groupStart()
                ->like('p.nama_perusahaan', $keyword)
                ->orLike('p.email', $keyword)
                ->orLike('p.no_telepon', $keyword)
                ->orLike('p.kota', $keyword)
                ->orLike('p.alamat', $keyword)
                ->groupEnd();
        }
    }

    protected function applyDataTablesOrdering(BaseBuilder $builder, int $orderColumn, string $orderDir): void
    {
        $mapOrder = [
            1 => 'p.nama_perusahaan',
            2 => 'p.no_telepon',
            3 => 'p.kota',
            4 => 'p.alamat',
        ];

        if (isset($mapOrder[$orderColumn])) {
            $builder->orderBy($mapOrder[$orderColumn], $orderDir);
            return;
        }

        $builder->orderBy('p.id_perusahaan', 'DESC');
    }
}
