<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTbJenisBerkasForScopePenggunaan extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tb_jenis_berkas')) {
            return;
        }

        /*
        |-------------------------------------------------------------------
        | KOLOM SCOPE PENGGUNAAN BERKAS
        |-------------------------------------------------------------------
        | Migration ini menambahkan pemisah logika antara berkas profil
        | umum milik pelamar dan berkas yang seharusnya diproses per
        | lamaran seperti CV, surat lamaran, dan portofolio.
        |
        | Tips Debugging:
        | - Jika migration gagal karena kolom sudah ada, cek fieldExists
        |   agar deployment ulang tetap aman.
        */
        if (! $this->db->fieldExists('scope_penggunaan', 'tb_jenis_berkas')) {
            $this->forge->addColumn('tb_jenis_berkas', [
                'scope_penggunaan' => [
                    'type'       => 'ENUM',
                    'constraint' => ['profil', 'lamaran', 'keduanya'],
                    'default'    => 'profil',
                    'null'       => false,
                    'after'      => 'berlaku_untuk',
                ],
            ]);
        }

        if (! $this->db->fieldExists('boleh_multi_upload', 'tb_jenis_berkas')) {
            $this->forge->addColumn('tb_jenis_berkas', [
                'boleh_multi_upload' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'scope_penggunaan',
                ],
            ]);
        }

        /*
        |-------------------------------------------------------------------
        | BACKFILL MASTER JENIS BERKAS
        |-------------------------------------------------------------------
        | Dokumen seperti CV, surat lamaran, dan portofolio diubah jadi
        | ber-scope lamaran agar tab Berkas Profil hanya menampilkan
        | dokumen umum yang relevan untuk akun pelamar.
        */
        $updates = [
            'surat_lamaran' => ['scope_penggunaan' => 'lamaran', 'boleh_multi_upload' => 1],
            'cv'            => ['scope_penggunaan' => 'lamaran', 'boleh_multi_upload' => 1],
            'portofolio'    => ['scope_penggunaan' => 'lamaran', 'boleh_multi_upload' => 1],
            'sertifikat'    => ['scope_penggunaan' => 'profil', 'boleh_multi_upload' => 1],
            'ktp'           => ['scope_penggunaan' => 'profil', 'boleh_multi_upload' => 0],
            'ijazah'        => ['scope_penggunaan' => 'profil', 'boleh_multi_upload' => 0],
            'skck'          => ['scope_penggunaan' => 'profil', 'boleh_multi_upload' => 0],
            'pas_foto'      => ['scope_penggunaan' => 'profil', 'boleh_multi_upload' => 0],
            'transkrip'     => ['scope_penggunaan' => 'profil', 'boleh_multi_upload' => 0],
        ];

        foreach ($updates as $slug => $payload) {
            $this->db->table('tb_jenis_berkas')
                ->where('slug_berkas', $slug)
                ->update($payload);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('tb_jenis_berkas')) {
            return;
        }

        if ($this->db->fieldExists('boleh_multi_upload', 'tb_jenis_berkas')) {
            $this->forge->dropColumn('tb_jenis_berkas', 'boleh_multi_upload');
        }

        if ($this->db->fieldExists('scope_penggunaan', 'tb_jenis_berkas')) {
            $this->forge->dropColumn('tb_jenis_berkas', 'scope_penggunaan');
        }
    }
}
