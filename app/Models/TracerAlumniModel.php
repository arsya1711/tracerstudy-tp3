<?php

namespace App\Models;

use CodeIgniter\Model;

class TracerAlumniModel extends Model
{
    protected $table         = 'tb_tracer_alumni';
    protected $primaryKey    = 'id_tracer';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = false;
    protected $allowedFields = [
        'id_alumni',
        'id_aktivitas',
        'status',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'disetujui_oleh',
        'disetujui_pada',
        'posisi_kerja',
        'nama_dudi',
        'bidang_dudi',
        'alamat_dudi',
        'tahun_mulai_kerja',
        'relevan_jurusan',
        'penghasilan_range',
        'universitas',
        'program_studi',
        'status_kuliah',
        'nama_usaha',
        'bidang_usaha',
        'modal_awal',
        'penghasilan_usaha',
        'rencana_kedepan',
    ];

    public function ambilTerakhirByAlumni($id_alumni)
    {
        if (! $this->db->tableExists($this->table)) {
            return null;
        }

        $builder = $this->db->table('tb_tracer_alumni t');
        $selects = ['t.*'];

        if ($this->db->tableExists('tb_aktivitas')) {
            $builder->join('tb_aktivitas a', 'a.id_aktivitas = t.id_aktivitas', 'left');
            $selects[] = $this->db->fieldExists('nama_aktivitas', 'tb_aktivitas') ? 'a.nama_aktivitas' : 'NULL AS nama_aktivitas';
        } else {
            $selects[] = 'NULL AS nama_aktivitas';
        }

        $orderBy = $this->db->fieldExists('dibuat_pada', 'tb_tracer_alumni') ? 't.dibuat_pada' : 't.id_tracer';

        return $builder
            ->select(implode(', ', $selects), false)
            ->where('t.id_alumni', $id_alumni)
            ->orderBy($orderBy, 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();
    }
}
