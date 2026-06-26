<?php

namespace App\Models;

use CodeIgniter\Model;

class PengajuanLegalisirModel extends Model
{
    protected $table         = 'tb_pengajuan_legalisir';
    protected $primaryKey    = 'id_pengajuan_legalisir';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'id_alumni',
        'jenis_dokumen',
        'jumlah_lembar',
        'keperluan',
        'status',
        'catatan_admin',
        'diproses_oleh',
        'diproses_pada',
        'selesai_pada',
        'dibuat_pada',
        'diperbarui_pada',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    /*
    | Mengambil data pengajuan legalisir beserta profil alumni,
    | kompetensi, angkatan, dan admin pemroses. Dipakai oleh halaman admin
    | maupun halaman alumni agar format data konsisten.
    */
    public function ambilLengkap(?int $idAlumni = null): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        $builder = $this->db->table($this->table . ' l')
            ->select([
                'l.*',
                'al.nis',
                'al.nisn',
                'u.nama_lengkap',
                'u.email',
                'k.nama_kompetensi',
                'k.akronim',
                'ang.tahun_lulus',
                'admin.nama_lengkap AS nama_admin',
            ])
            ->join('tb_alumni al', 'al.id_alumni = l.id_alumni', 'inner')
            ->join('tb_pengguna u', 'u.id_pengguna = al.id_pengguna', 'inner')
            ->join('tb_kompetensi k', 'k.id_kompetensi = al.id_kompetensi', 'left')
            ->join('tb_angkatan ang', 'ang.id_angkatan = al.id_angkatan', 'left')
            ->join('tb_pengguna admin', 'admin.id_pengguna = l.diproses_oleh', 'left');

        if ($idAlumni !== null && $idAlumni > 0) {
            $builder->where('l.id_alumni', $idAlumni);
        }

        return $builder
            ->orderBy('l.dibuat_pada', 'DESC')
            ->orderBy('l.id_pengajuan_legalisir', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function hitungByStatus(): array
    {
        $hasil = [
            'diajukan' => 0,
            'diproses' => 0,
            'selesai' => 0,
            'ditolak' => 0,
        ];

        if (! $this->db->tableExists($this->table)) {
            return $hasil;
        }

        foreach ($this->select('status, COUNT(*) AS total', false)->groupBy('status')->findAll() as $row) {
            $status = (string) ($row['status'] ?? '');
            if (array_key_exists($status, $hasil)) {
                $hasil[$status] = (int) ($row['total'] ?? 0);
            }
        }

        return $hasil;
    }

    /*
    | Menghitung status tertentu untuk badge dan card notifikasi.
    | Contoh: admin hanya perlu melihat status "diajukan", sedangkan
    | alumni perlu melihat status "diajukan", "diproses", dan "ditolak".
    */
    public function hitungByStatusList(array $statusList, ?int $idAlumni = null): int
    {
        if (! $this->db->tableExists($this->table) || $statusList === []) {
            return 0;
        }

        $builder = $this->builder()->whereIn('status', $statusList);

        if ($idAlumni !== null && $idAlumni > 0) {
            $builder->where('id_alumni', $idAlumni);
        }

        return (int) $builder->countAllResults();
    }

    /*
    | Mengambil pengajuan terbaru milik alumni untuk alert status di
    | dashboard alumni dan ringkasan halaman legalisir.
    */
    public function ambilTerbaruByAlumni(int $idAlumni): ?array
    {
        $rows = $this->ambilLengkap($idAlumni);

        return $rows[0] ?? null;
    }
}
