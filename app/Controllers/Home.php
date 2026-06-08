<?php

namespace App\Controllers;

use Config\Database;

class Home extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function index(): string
    {
        return view('landing/index', [
            'title' => 'Tracer Study SMK Teratai Putih Global 4',
            'statistik' => [
                'alumni' => $this->hitungTabel('tb_alumni'),
                'pengguna' => $this->hitungTabel('tb_pengguna'),
                'tracer' => $this->hitungTabel('tb_tracer_alumni'),
                'belum_tracer' => $this->hitungTracerBelumLengkap(),
            ],
            'aktivitas' => $this->ambilRingkasanAktivitas(),
        ]);
    }

    protected function hitungTabel(string $table): int
    {
        if (! $this->db->tableExists($table)) {
            return 0;
        }

        return (int) $this->db->table($table)->countAllResults();
    }

    protected function hitungTracerBelumLengkap(): int
    {
        if (! $this->db->tableExists('tb_alumni') || ! $this->db->tableExists('tb_tracer_alumni')) {
            return 0;
        }

        return (int) $this->db->table('tb_alumni al')
            ->join('tb_tracer_alumni t', 't.id_alumni = al.id_alumni', 'left')
            ->where('t.id_tracer IS NULL', null, false)
            ->countAllResults();
    }

    protected function ambilRingkasanAktivitas(): array
    {
        if (! $this->db->tableExists('tb_tracer_alumni') || ! $this->db->tableExists('tb_aktivitas')) {
            return [];
        }

        return $this->db->table('tb_tracer_alumni t')
            ->select('a.nama_aktivitas, COUNT(*) AS total')
            ->join('tb_aktivitas a', 'a.id_aktivitas = t.id_aktivitas', 'left')
            ->groupBy('a.nama_aktivitas')
            ->orderBy('a.nama_aktivitas', 'ASC')
            ->get()
            ->getResultArray();
    }
}
