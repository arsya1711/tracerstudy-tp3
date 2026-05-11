<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $table            = 'tb_pengguna';
    protected $primaryKey       = 'id_pengguna';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_peran',
        'nama_lengkap',
        'email',
        'kata_sandi',
        'nomor_telepon',
        'foto_profil',
        'status_aktif',
        'token_reset',
        'token_reset_expired',
        'terakhir_login',
        'dibuat_pada',
        'diperbarui_pada',
    ];

    public function getDataTables(object $request): array
    {
        $draw   = (int) ($request->getVar('draw') ?? 0);
        $start  = max(0, (int) ($request->getVar('start') ?? 0));
        $length = (int) ($request->getVar('length') ?? 10);

        if ($length < 1) {
            $length = 10;
        }

        $filters = [
            'jenis_admin' => trim((string) ($request->getVar('jenis_admin') ?? '')),
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

    public function ambilDetailById(int $idPengguna): ?array
    {
        return $this->baseDataTablesQuery()
            ->where('u.id_pengguna', $idPengguna)
            ->get()
            ->getRowArray();
    }

    protected function baseDataTablesQuery(): BaseBuilder
    {
        $builder = $this->db->table($this->table . ' u')
            ->select([
                'u.id_pengguna',
                'u.id_peran',
                'u.nama_lengkap',
                'u.email',
                'u.nomor_telepon',
                'u.foto_profil',
                'u.status_aktif',
                'u.terakhir_login',
                'u.dibuat_pada',
                'u.diperbarui_pada',
                'r.nama_peran',
                'r.slug_peran',
            ])
            ->join('tb_peran r', 'r.id_peran = u.id_peran', 'inner')
            ->whereIn('r.slug_peran', ['admin_sekolah', 'admin_dudi', 'admin_perusahaan']);

        if (
            $this->db->tableExists('tb_perusahaan')
            && $this->db->fieldExists('id_pengguna', 'tb_perusahaan')
            && $this->db->fieldExists('nama_perusahaan', 'tb_perusahaan')
        ) {
            $builder->select('pr.id_perusahaan, pr.nama_perusahaan')
                ->join('tb_perusahaan pr', 'pr.id_pengguna = u.id_pengguna', 'left');
        } else {
            $builder->select('NULL AS id_perusahaan, NULL AS nama_perusahaan', false);
        }

        return $builder;
    }

    protected function applyDataTablesFilters(BaseBuilder $builder, array $filters): void
    {
        if ($filters['jenis_admin'] !== '') {
            $builder->where('r.slug_peran', $filters['jenis_admin']);
        }

        if ($filters['search'] !== '') {
            $keyword = $filters['search'];

            $builder->groupStart()
                ->like('u.nama_lengkap', $keyword)
                ->orLike('u.email', $keyword);

            if (
                $this->db->tableExists('tb_perusahaan')
                && $this->db->fieldExists('nama_perusahaan', 'tb_perusahaan')
            ) {
                $builder->orLike('pr.nama_perusahaan', $keyword);
            }

            $builder->groupEnd();
        }
    }

    protected function applyDataTablesOrdering(BaseBuilder $builder, int $orderColumn, string $orderDir): void
    {
        $mapOrder = [
            1 => 'u.nama_lengkap',
            2 => 'r.slug_peran',
            3 => 'pr.nama_perusahaan',
            4 => 'u.status_aktif',
            5 => 'u.terakhir_login',
        ];

        if (isset($mapOrder[$orderColumn])) {
            if ($mapOrder[$orderColumn] === 'pr.nama_perusahaan' && ! $this->db->tableExists('tb_perusahaan')) {
                $builder->orderBy('u.nama_lengkap', 'ASC');
                return;
            }

            $builder->orderBy($mapOrder[$orderColumn], $orderDir);
            return;
        }

        $builder->orderBy('u.id_pengguna', 'DESC');
    }
}
