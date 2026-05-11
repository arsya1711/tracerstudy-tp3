<?php

namespace App\Models;

use CodeIgniter\Model;

/*
|-------------------------------------------------------------------
| MODEL BERKAS PELAMAR
|-------------------------------------------------------------------
| Model ini mengelola gudang berkas pelamar sekaligus master jenis
| berkas yang berlaku untuk profil maupun untuk proses lamaran.
|
| Alur kerja:
| 1. Controller meminta daftar berkas berdasarkan pelamar.
| 2. Model menggabungkan master jenis berkas dengan upload terbaru.
| 3. Hasil akhir tetap menampilkan card master meski file belum ada.
|
| Tips Debugging:
| - Jika card jenis berkas hilang, cek isi tabel tb_jenis_berkas.
| - Jika upload terbaru tidak muncul, cek unique key id_pelamar dan
|   id_jenis_berkas pada tabel tb_berkas.
*/
class BerkasModel extends Model
{
    protected $table         = 'tb_berkas';
    protected $primaryKey    = 'id_berkas';
    protected $returnType    = 'array';
    protected $allowedFields = ['id_pelamar', 'id_jenis_berkas', 'nama_file', 'path_file', 'ukuran_file', 'tipe_mime', 'status_unggah', 'catatan'];
    protected $useTimestamps = true;
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diperbarui_pada';
    protected array $slugBerkasKhususLamaran = ['cv', 'surat_lamaran', 'portofolio'];

    /*
    |-------------------------------------------------------------------
    | AMBIL BERKAS BERDASARKAN PELAMAR
    |-------------------------------------------------------------------
    | Method ini dipakai untuk menampilkan card berkas profil lengkap
    | maupun mengambil master berkas yang relevan untuk validasi.
    */
    public function ambilByPelamar($id_pelamar, ?string $scopePenggunaan = null): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        $idPelamar = (int) $id_pelamar;
        $jenisBerkas = $this->ambilJenisBerkasBerlaku($idPelamar, $scopePenggunaan);
        $uploadMap = $this->ambilUploadTerbaruByPelamar($idPelamar, $scopePenggunaan);

        if ($jenisBerkas === []) {
            return array_values($uploadMap);
        }

        $hasil = [];

        foreach ($jenisBerkas as $jenis) {
            $idJenisBerkas = (int) ($jenis['id_jenis_berkas'] ?? 0);
            $upload = $uploadMap[$idJenisBerkas] ?? [];

            $hasil[] = array_merge([
                'id_berkas'       => (int) ($upload['id_berkas'] ?? 0),
                'id_pelamar'      => $idPelamar,
                'id_jenis_berkas' => $idJenisBerkas,
                'nama_file'       => null,
                'path_file'       => null,
                'ukuran_file'     => null,
                'tipe_mime'       => null,
                'status_unggah'   => 'belum_diunggah',
                'catatan'         => null,
                'dibuat_pada'     => null,
                'diperbarui_pada' => null,
                'nama_berkas'     => (string) ($jenis['nama_berkas'] ?? 'Berkas'),
                'slug_berkas'     => (string) ($jenis['slug_berkas'] ?? ''),
                'wajib'           => (int) ($jenis['wajib'] ?? 0),
                'berlaku_untuk'   => (string) ($jenis['berlaku_untuk'] ?? 'semua'),
                'scope_penggunaan' => (string) ($jenis['scope_penggunaan'] ?? 'profil'),
                'boleh_multi_upload' => (int) ($jenis['boleh_multi_upload'] ?? 0),
                'keterangan'      => (string) ($jenis['keterangan'] ?? ''),
            ], $upload);

            unset($uploadMap[$idJenisBerkas]);
        }

        foreach ($uploadMap as $uploadTersisa) {
            $hasil[] = $uploadTersisa;
        }

        return $hasil;
    }

    /*
    |-------------------------------------------------------------------
    | CARI JENIS BERKAS BERDASARKAN ID
    |-------------------------------------------------------------------
    | Dipakai saat upload untuk memastikan jenis berkas yang dikirim
    | memang masih aktif dan cocok dengan scope yang diminta.
    */
    public function cariJenisBerkas(int $idJenisBerkas, ?string $scopePenggunaan = null): ?array
    {
        if (! $this->db->tableExists('tb_jenis_berkas')) {
            return null;
        }

        $builder = $this->db->table('tb_jenis_berkas')
            ->where('id_jenis_berkas', $idJenisBerkas);

        if ($this->db->fieldExists('status_aktif', 'tb_jenis_berkas')) {
            $builder->where('status_aktif', 1);
        }

        if ($scopePenggunaan !== null && $this->db->fieldExists('scope_penggunaan', 'tb_jenis_berkas')) {
            $builder->whereIn('scope_penggunaan', [$scopePenggunaan, 'keduanya']);
        }

        $jenisBerkas = $builder->get()->getRowArray();

        if (
            $jenisBerkas !== null
            && $scopePenggunaan === 'profil'
            && $this->isBerkasKhususLamaran((string) ($jenisBerkas['slug_berkas'] ?? ''))
        ) {
            return null;
        }

        return $jenisBerkas;
    }

    /*
    |-------------------------------------------------------------------
    | CARI JENIS BERKAS BERDASARKAN SLUG
    |-------------------------------------------------------------------
    | Method ini sangat membantu modul lamaran karena CV, surat
    | lamaran, dan portofolio lebih aman direferensikan dengan slug.
    |
    | Tips Debugging:
    | - Jika CV tidak ditemukan saat submit, cek slug master dokumen
    |   di tb_jenis_berkas apakah masih memakai `cv`.
    */
    public function cariJenisBerkasBySlug(string $slugBerkas, ?string $scopePenggunaan = null): ?array
    {
        if (! $this->db->tableExists('tb_jenis_berkas')) {
            return null;
        }

        $builder = $this->db->table('tb_jenis_berkas')
            ->where('slug_berkas', trim($slugBerkas));

        if ($this->db->fieldExists('status_aktif', 'tb_jenis_berkas')) {
            $builder->where('status_aktif', 1);
        }

        if ($scopePenggunaan !== null && $this->db->fieldExists('scope_penggunaan', 'tb_jenis_berkas')) {
            $builder->whereIn('scope_penggunaan', [$scopePenggunaan, 'keduanya']);
        }

        return $builder->get()->getRowArray();
    }

    /*
    |-------------------------------------------------------------------
    | AMBIL MASTER JENIS BERKAS PER SCOPE
    |-------------------------------------------------------------------
    | Modul lamaran memanggil helper ini untuk menyiapkan daftar jenis
    | dokumen yang harus dilampirkan per lowongan.
    */
    public function ambilJenisBerkasByScope(string $scopePenggunaan, ?int $idPelamar = null): array
    {
        if (! $this->db->tableExists('tb_jenis_berkas')) {
            return [];
        }

        return $this->ambilJenisBerkasBerlaku((int) ($idPelamar ?? 0), $scopePenggunaan);
    }

    protected function ambilJenisBerkasBerlaku(int $idPelamar, ?string $scopePenggunaan = null): array
    {
        if (! $this->db->tableExists('tb_jenis_berkas')) {
            return [];
        }

        $jenisPelamar = $this->ambilKategoriPelamar($idPelamar);
        $builder = $this->db->table('tb_jenis_berkas');
        $selects = ['id_jenis_berkas'];

        foreach (['nama_berkas', 'slug_berkas', 'wajib', 'berlaku_untuk', 'scope_penggunaan', 'boleh_multi_upload', 'keterangan'] as $field) {
            if ($this->db->fieldExists($field, 'tb_jenis_berkas')) {
                $selects[] = $field;
            }
        }

        if ($this->db->fieldExists('status_aktif', 'tb_jenis_berkas')) {
            $builder->where('status_aktif', 1);
        }

        if ($this->db->fieldExists('berlaku_untuk', 'tb_jenis_berkas')) {
            $builder->whereIn('berlaku_untuk', ['semua', $jenisPelamar]);
        }

        if ($scopePenggunaan !== null && $this->db->fieldExists('scope_penggunaan', 'tb_jenis_berkas')) {
            $builder->whereIn('scope_penggunaan', [$scopePenggunaan, 'keduanya']);
        }

        if ($scopePenggunaan === 'profil' && $this->db->fieldExists('slug_berkas', 'tb_jenis_berkas')) {
            $builder->whereNotIn('slug_berkas', $this->slugBerkasKhususLamaran);
        }

        if ($this->db->fieldExists('wajib', 'tb_jenis_berkas')) {
            $builder->orderBy('wajib', 'DESC');
        }

        return $builder
            ->select(implode(', ', $selects), false)
            ->orderBy('id_jenis_berkas', 'ASC')
            ->get()
            ->getResultArray();
    }

    protected function ambilUploadTerbaruByPelamar(int $idPelamar, ?string $scopePenggunaan = null): array
    {
        $builder = $this->db->table('tb_berkas b');
        $selects = ['b.*'];

        if ($this->db->tableExists('tb_jenis_berkas')) {
            $builder->join('tb_jenis_berkas jb', 'jb.id_jenis_berkas = b.id_jenis_berkas', 'left');

            foreach (['nama_berkas', 'slug_berkas', 'wajib', 'berlaku_untuk', 'scope_penggunaan', 'boleh_multi_upload', 'keterangan'] as $field) {
                $selects[] = $this->db->fieldExists($field, 'tb_jenis_berkas')
                    ? 'jb.' . $field
                    : 'NULL AS ' . $field;
            }

            if ($scopePenggunaan !== null && $this->db->fieldExists('scope_penggunaan', 'tb_jenis_berkas')) {
                $builder->whereIn('jb.scope_penggunaan', [$scopePenggunaan, 'keduanya']);
            }

            if ($scopePenggunaan === 'profil' && $this->db->fieldExists('slug_berkas', 'tb_jenis_berkas')) {
                $builder->whereNotIn('jb.slug_berkas', $this->slugBerkasKhususLamaran);
            }
        } else {
            $selects[] = 'NULL AS nama_berkas';
            $selects[] = 'NULL AS slug_berkas';
            $selects[] = '0 AS wajib';
            $selects[] = 'NULL AS berlaku_untuk';
            $selects[] = 'NULL AS scope_penggunaan';
            $selects[] = '0 AS boleh_multi_upload';
            $selects[] = 'NULL AS keterangan';
        }

        $rows = $builder
            ->select(implode(', ', $selects), false)
            ->where('b.id_pelamar', $idPelamar)
            ->orderBy('b.id_jenis_berkas', 'ASC')
            ->orderBy('b.id_berkas', 'DESC')
            ->get()
            ->getResultArray();

        $hasil = [];

        foreach ($rows as $row) {
            $idJenisBerkas = (int) ($row['id_jenis_berkas'] ?? 0);
            $index = $idJenisBerkas > 0 ? $idJenisBerkas : 'legacy_' . (int) ($row['id_berkas'] ?? 0);

            if (array_key_exists($index, $hasil)) {
                continue;
            }

            $hasil[$index] = $row;
        }

        return $hasil;
    }

    protected function ambilKategoriPelamar(int $idPelamar): string
    {
        if ($idPelamar <= 0) {
            return 'umum';
        }

        if (
            ! $this->db->tableExists('tb_pelamar')
            || ! $this->db->tableExists('tb_pengguna')
            || ! $this->db->tableExists('tb_peran')
        ) {
            return 'umum';
        }

        $row = $this->db->table('tb_pelamar p')
            ->select('r.slug_peran')
            ->join('tb_pengguna u', 'u.id_pengguna = p.id_pengguna', 'left')
            ->join('tb_peran r', 'r.id_peran = u.id_peran', 'left')
            ->where('p.id_pelamar', $idPelamar)
            ->get()
            ->getRowArray();

        return (($row['slug_peran'] ?? '') === 'pelamar_alumni') ? 'alumni' : 'umum';
    }

    /*
    |-------------------------------------------------------------------
    | PENJAGA DOKUMEN KHUSUS LAMARAN
    |-------------------------------------------------------------------
    | CV, surat lamaran, dan portofolio lebih aman diunggah pada saat
    | pelamar melamar lowongan tertentu karena isi dokumen sering
    | menyesuaikan nama perusahaan atau posisi.
    |
    | Tips Debugging:
    | - Jika CV masih muncul di profil, cek slug_berkas pada master data.
    */
    protected function isBerkasKhususLamaran(string $slugBerkas): bool
    {
        return in_array(strtolower(trim($slugBerkas)), $this->slugBerkasKhususLamaran, true);
    }
}
