<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePimpinanSekolahRole extends Migration
{
    private array $obsoleteSlugs = [
        'pimpinan',
        'pimpinan_sekolah',
        'kepala_sekolah',
    ];

    public function up()
    {
        if (! $this->db->tableExists('tb_peran') || ! $this->db->tableExists('tb_pengguna')) {
            return;
        }

        $roles = $this->db->table('tb_peran')
            ->select('id_peran')
            ->whereIn('slug_peran', $this->obsoleteSlugs)
            ->get()
            ->getResultArray();

        if ($roles === []) {
            return;
        }

        $roleIds = array_map(static fn (array $row): int => (int) $row['id_peran'], $roles);

        $users = $this->db->table('tb_pengguna')
            ->select('id_pengguna')
            ->whereIn('id_peran', $roleIds)
            ->get()
            ->getResultArray();

        $userIds = array_map(static fn (array $row): int => (int) $row['id_pengguna'], $users);

        if ($userIds !== []) {
            $this->kosongkanReferensiPengguna($userIds);

            $this->db->table('tb_pengguna')
                ->whereIn('id_pengguna', $userIds)
                ->delete();
        }

        $this->db->table('tb_peran')
            ->whereIn('id_peran', $roleIds)
            ->delete();
    }

    public function down()
    {
        if (! $this->db->tableExists('tb_peran')) {
            return;
        }

        $data = [
            [
                'nama_peran' => 'Pimpinan Sekolah',
                'slug_peran' => 'pimpinan_sekolah',
                'keterangan' => 'Role lama yang sudah tidak digunakan aplikasi',
            ],
        ];

        foreach ($data as $role) {
            $exists = $this->db->table('tb_peran')
                ->where('slug_peran', $role['slug_peran'])
                ->get()
                ->getRowArray();

            if ($exists === null) {
                $this->db->table('tb_peran')->insert($role);
            }
        }
    }

    private function kosongkanReferensiPengguna(array $userIds): void
    {
        $references = [
            ['table' => 'tb_alumni', 'column' => 'diverifikasi_oleh'],
            ['table' => 'tb_tracer_alumni', 'column' => 'diverifikasi_oleh'],
            ['table' => 'tb_tracer_alumni', 'column' => 'disetujui_oleh'],
            ['table' => 'tb_pengajuan_legalisir', 'column' => 'diproses_oleh'],
        ];

        foreach ($references as $reference) {
            if (! $this->db->tableExists($reference['table']) || ! $this->db->fieldExists($reference['column'], $reference['table'])) {
                continue;
            }

            $this->db->table($reference['table'])
                ->whereIn($reference['column'], $userIds)
                ->update([$reference['column'] => null]);
        }

        if ($this->db->tableExists('tb_notifikasi') && $this->db->fieldExists('id_pengguna', 'tb_notifikasi')) {
            $this->db->table('tb_notifikasi')
                ->whereIn('id_pengguna', $userIds)
                ->delete();
        }
    }
}
