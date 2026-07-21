<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeSchoolCompetencies extends Migration
{
    private const PROGRAMS = [
        'TJK' => [
            'name' => 'Teknik Jaringan Komputer dan Telekomunikasi (TJK) Axioo Class Program (ACP)',
            'legacy' => ['TJK', 'TKJ'],
        ],
        'AKL' => [
            'name' => 'Akuntansi dan Keuangan Lembaga (AKL)',
            'legacy' => ['AKL', 'MM'],
        ],
        'MPLB' => [
            'name' => 'Manajemen Perkantoran dan Layanan Bisnis (MPLB)',
            'legacy' => ['MPLB', 'RPL'],
        ],
    ];

    public function up()
    {
        if (! $this->db->tableExists('tb_kompetensi')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $rows = $this->db->table('tb_kompetensi')
            ->orderBy('id_kompetensi', 'ASC')
            ->get()
            ->getResultArray();
        $usedIds = [];
        $targetIds = [];

        $this->db->transStart();

        foreach (self::PROGRAMS as $akronim => $program) {
            $row = $this->findAvailableRow($rows, $program['legacy'], $usedIds);
            $payload = [
                'nama_kompetensi' => $program['name'],
                'akronim' => $akronim,
                'status_aktif' => 1,
                'diperbarui_pada' => $now,
            ];

            if ($row === null) {
                $payload['dibuat_pada'] = $now;
                $this->db->table('tb_kompetensi')->insert($payload);
                $idKompetensi = (int) $this->db->insertID();
            } else {
                $idKompetensi = (int) $row['id_kompetensi'];
                $this->db->table('tb_kompetensi')
                    ->where('id_kompetensi', $idKompetensi)
                    ->update($payload);
            }

            $usedIds[] = $idKompetensi;
            $targetIds[] = $idKompetensi;
        }

        if ($this->db->tableExists('tb_alumni') && $targetIds !== []) {
            $alumniRows = $this->db->table('tb_alumni')
                ->select('id_alumni')
                ->orderBy('id_alumni', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($alumniRows as $index => $alumni) {
                $this->db->table('tb_alumni')
                    ->where('id_alumni', (int) $alumni['id_alumni'])
                    ->update([
                        'id_kompetensi' => $targetIds[$index % count($targetIds)],
                        'diperbarui_pada' => $now,
                    ]);
            }
        }

        if ($usedIds !== []) {
            $this->db->table('tb_kompetensi')
                ->whereNotIn('id_kompetensi', $usedIds)
                ->delete();
        }

        $this->db->transComplete();
    }

    public function down()
    {
        // Koreksi data sekolah bersifat satu arah agar rollback tidak
        // mengembalikan alumni ke jurusan lama yang sudah dinyatakan salah.
    }

    private function findAvailableRow(array $rows, array $acronyms, array $usedIds): ?array
    {
        foreach ($acronyms as $acronym) {
            foreach ($rows as $row) {
                $id = (int) ($row['id_kompetensi'] ?? 0);
                if ($id > 0 && ! in_array($id, $usedIds, true) && strtoupper((string) ($row['akronim'] ?? '')) === $acronym) {
                    return $row;
                }
            }
        }

        return null;
    }
}
