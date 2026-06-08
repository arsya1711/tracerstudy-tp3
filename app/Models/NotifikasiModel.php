<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL NOTIFIKASI
|-------------------------------------------------------------------
| Model ini mengelola notifikasi per pengguna, termasuk pembuatan
| notifikasi sistem dan pengambilan data untuk badge header.
|
| Alur kerja:
| 1. Controller membuat notifikasi untuk target id_pengguna.
| 2. Partial header membaca jumlah belum dibaca dan daftar terbaru.
| 3. Notifikasi ditandai dibaca saat user membukanya.
|
| Tips Debugging:
| - Jika notifikasi tidak tersimpan, cek allowedFields model ini.
| - Jika badge kosong, pastikan migration tb_notifikasi sudah dijalankan.
*/
class NotifikasiModel extends Model
{
    protected $table         = 'tb_notifikasi';
    protected $primaryKey    = 'id_notifikasi';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'id_pengguna',
        'tipe',
        'judul',
        'pesan',
        'target_url',
        'dibaca',
        'dibaca_pada',
        'dibuat_pada',
        'diperbarui_pada',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';

    public function buatUntukPengguna(array $idPenggunaList, string $tipe, string $judul, string $pesan, string $targetUrl): void
    {
        if (! $this->db->tableExists($this->table)) {
            return;
        }

        $idPenggunaList = array_values(array_unique(array_filter(
            array_map(static fn($id): int => (int) $id, $idPenggunaList),
            static fn(int $id): bool => $id > 0
        )));

        if ($idPenggunaList === []) {
            return;
        }

        $rows = [];
        $now = date('Y-m-d H:i:s');

        foreach ($idPenggunaList as $idPengguna) {
            $rows[] = [
                'id_pengguna' => $idPengguna,
                'tipe'        => $tipe,
                'judul'       => $judul,
                'pesan'       => $pesan,
                'target_url'  => $targetUrl,
                'dibaca'      => 0,
                'dibaca_pada' => null,
                'dibuat_pada' => $now,
                'diperbarui_pada' => $now,
            ];
        }

        $this->insertBatch($rows);
    }

    public function ambilUntukHeader(int $idPengguna, int $limit = 5): array
    {
        if (! $this->db->tableExists($this->table) || $idPengguna <= 0) {
            return [
                'jumlah_belum_dibaca' => 0,
                'items' => [],
            ];
        }

        return [
            'jumlah_belum_dibaca' => $this->hitungBelumDibaca($idPengguna),
            'items' => $this->where('id_pengguna', $idPengguna)
                ->orderBy('dibaca', 'ASC')
                ->orderBy('dibuat_pada', 'DESC')
                ->limit($limit)
                ->findAll(),
        ];
    }

    public function hitungBelumDibaca(int $idPengguna): int
    {
        if (! $this->db->tableExists($this->table) || $idPengguna <= 0) {
            return 0;
        }

        return (int) $this->where('id_pengguna', $idPengguna)
            ->where('dibaca', 0)
            ->countAllResults();
    }

    public function tandaiDibaca(int $idNotifikasi, int $idPengguna): ?array
    {
        if (! $this->db->tableExists($this->table) || $idNotifikasi <= 0 || $idPengguna <= 0) {
            return null;
        }

        $notifikasi = $this->where('id_notifikasi', $idNotifikasi)
            ->where('id_pengguna', $idPengguna)
            ->first();

        if ($notifikasi === null) {
            return null;
        }

        if ((int) ($notifikasi['dibaca'] ?? 0) === 0) {
            $this->update($idNotifikasi, [
                'dibaca' => 1,
                'dibaca_pada' => date('Y-m-d H:i:s'),
            ]);
        }

        return $notifikasi;
    }
}
