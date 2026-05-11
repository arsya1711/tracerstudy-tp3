<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class JenisBerkasSeeder extends Seeder
{
    public function run()
    {
        if (! $this->db->tableExists('tb_jenis_berkas')) {
            return;
        }

        $data = [
            [
                'nama_berkas'   => 'Surat Lamaran',
                'slug_berkas'   => 'surat_lamaran',
                'wajib'         => 1,
                'berlaku_untuk' => 'semua',
                'scope_penggunaan' => 'lamaran',
                'boleh_multi_upload' => 1,
                'keterangan'    => 'Surat lamaran kerja.',
                'status_aktif'  => 1,
            ],
            [
                'nama_berkas'   => 'CV',
                'slug_berkas'   => 'cv',
                'wajib'         => 1,
                'berlaku_untuk' => 'semua',
                'scope_penggunaan' => 'lamaran',
                'boleh_multi_upload' => 1,
                'keterangan'    => 'Curriculum Vitae terbaru.',
                'status_aktif'  => 1,
            ],
            [
                'nama_berkas'   => 'KTP',
                'slug_berkas'   => 'ktp',
                'wajib'         => 1,
                'berlaku_untuk' => 'semua',
                'scope_penggunaan' => 'profil',
                'boleh_multi_upload' => 0,
                'keterangan'    => 'Kartu Tanda Penduduk yang masih berlaku.',
                'status_aktif'  => 1,
            ],
            [
                'nama_berkas'   => 'Ijazah',
                'slug_berkas'   => 'ijazah',
                'wajib'         => 1,
                'berlaku_untuk' => 'alumni',
                'scope_penggunaan' => 'profil',
                'boleh_multi_upload' => 0,
                'keterangan'    => 'Ijazah kelulusan sekolah.',
                'status_aktif'  => 1,
            ],
            [
                'nama_berkas'   => 'SKCK',
                'slug_berkas'   => 'skck',
                'wajib'         => 0,
                'berlaku_untuk' => 'semua',
                'scope_penggunaan' => 'profil',
                'boleh_multi_upload' => 0,
                'keterangan'    => 'Surat Keterangan Catatan Kepolisian.',
                'status_aktif'  => 1,
            ],
            [
                'nama_berkas'   => 'Sertifikat',
                'slug_berkas'   => 'sertifikat',
                'wajib'         => 0,
                'berlaku_untuk' => 'semua',
                'scope_penggunaan' => 'profil',
                'boleh_multi_upload' => 1,
                'keterangan'    => 'Sertifikat keahlian atau pelatihan.',
                'status_aktif'  => 1,
            ],
            [
                'nama_berkas'   => 'Pas Foto',
                'slug_berkas'   => 'pas_foto',
                'wajib'         => 1,
                'berlaku_untuk' => 'semua',
                'scope_penggunaan' => 'profil',
                'boleh_multi_upload' => 0,
                'keterangan'    => 'Pas foto terbaru.',
                'status_aktif'  => 1,
            ],
            [
                'nama_berkas'   => 'Transkrip Nilai',
                'slug_berkas'   => 'transkrip',
                'wajib'         => 0,
                'berlaku_untuk' => 'alumni',
                'scope_penggunaan' => 'profil',
                'boleh_multi_upload' => 0,
                'keterangan'    => 'Transkrip nilai atau rapor.',
                'status_aktif'  => 1,
            ],
            [
                'nama_berkas'   => 'Portofolio',
                'slug_berkas'   => 'portofolio',
                'wajib'         => 0,
                'berlaku_untuk' => 'semua',
                'scope_penggunaan' => 'lamaran',
                'boleh_multi_upload' => 1,
                'keterangan'    => 'Portofolio karya atau project.',
                'status_aktif'  => 1,
            ],
        ];

        foreach ($data as $row) {
            $table = $this->db->table('tb_jenis_berkas');
            $exists = $table->where('slug_berkas', $row['slug_berkas'])->get()->getRowArray();

            if ($exists !== null) {
                $this->db->table('tb_jenis_berkas')
                    ->where('id_jenis_berkas', (int) $exists['id_jenis_berkas'])
                    ->update($row);
                continue;
            }

            $this->db->table('tb_jenis_berkas')->insert($row);
        }
    }
}
