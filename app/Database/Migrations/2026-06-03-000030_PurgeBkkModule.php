<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PurgeBkkModule extends Migration
{
    public function up(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        $this->migrasikanAlumniDariPelamar();
        $this->renameKolomInstansiTracer();

        foreach ([
            'tb_lamaran_berkas',
            'tb_lamaran_status',
            'tb_lamaran',
            'tb_lowongan',
            'tb_perusahaan_kerjasama',
            'tb_perusahaan',
            'tb_kerjasama',
            'tb_berkas',
            'tb_riwayat_kerja',
            'tb_counter_pelamar',
            'tb_pelamar',
        ] as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        if ($this->db->tableExists('tb_peran')) {
            $this->db->table('tb_peran')
                ->whereIn('slug_peran', ['admin_dudi', 'admin_perusahaan', 'pelamar_umum'])
                ->delete();

            $this->db->table('tb_peran')
                ->where('slug_peran', 'admin_sekolah')
                ->update([
                    'nama_peran' => 'Admin Sekolah',
                    'keterangan' => 'Mengelola master data sekolah dan tracer alumni',
                ]);

            $this->db->table('tb_peran')
                ->where('slug_peran', 'pelamar_alumni')
                ->update([
                    'nama_peran' => 'Alumni',
                    'slug_peran' => 'alumni',
                    'keterangan' => 'Akun alumni untuk mengisi profil dan tracer study',
                ]);
        }

        if ($this->db->tableExists('tb_jenis_berkas')) {
            $this->db->table('tb_jenis_berkas')
                ->whereIn('slug_berkas', ['surat_lamaran', 'cv', 'portofolio'])
                ->delete();
        }
    }

    public function down(): void
    {
        // Modul rekrutmen sengaja tidak dibuat ulang oleh migration ini.
    }

    private function migrasikanAlumniDariPelamar(): void
    {
        if (! $this->db->tableExists('tb_alumni')) {
            return;
        }

        if (! $this->db->fieldExists('id_pengguna', 'tb_alumni')) {
            $this->forge->addColumn('tb_alumni', [
                'id_pengguna' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'id_alumni',
                ],
            ]);
        }

        foreach ([
            'jenis_kelamin' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'tempat_lahir' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tanggal_lahir' => ['type' => 'DATE', 'null' => true],
            'alamat' => ['type' => 'TEXT', 'null' => true],
            'status_pendaftaran' => [
                'type'       => 'ENUM',
                'constraint' => ['menunggu_aktivasi', 'aktif', 'terdaftar'],
                'default'    => 'aktif',
                'null'       => false,
            ],
            'terdaftar_pada' => ['type' => 'DATETIME', 'null' => true],
        ] as $field => $definition) {
            if (! $this->db->fieldExists($field, 'tb_alumni')) {
                $this->forge->addColumn('tb_alumni', [$field => $definition]);
            }
        }

        if ($this->db->tableExists('tb_pelamar') && $this->db->fieldExists('id_pelamar', 'tb_alumni')) {
            $this->db->query(
                'UPDATE tb_alumni al
                INNER JOIN tb_pelamar p ON p.id_pelamar = al.id_pelamar
                SET al.id_pengguna = p.id_pengguna,
                    al.jenis_kelamin = COALESCE(al.jenis_kelamin, p.jenis_kelamin),
                    al.tempat_lahir = COALESCE(al.tempat_lahir, p.tempat_lahir),
                    al.tanggal_lahir = COALESCE(al.tanggal_lahir, p.tanggal_lahir),
                    al.alamat = COALESCE(al.alamat, p.alamat),
                    al.status_pendaftaran = COALESCE(p.status_pendaftaran, al.status_pendaftaran),
                    al.terdaftar_pada = COALESCE(al.terdaftar_pada, p.terdaftar_pada)'
            );
        }

        $this->db->table('tb_alumni')
            ->where('id_pengguna IS NULL', null, false)
            ->delete();

        if ($this->db->fieldExists('id_pelamar', 'tb_alumni')) {
            $this->forge->dropColumn('tb_alumni', 'id_pelamar');
        }
    }

    private function renameKolomInstansiTracer(): void
    {
        if (! $this->db->tableExists('tb_tracer_alumni')) {
            return;
        }

        $map = [
            'nama_dudi'   => ['nama_instansi', 'VARCHAR', 150],
            'bidang_dudi' => ['bidang_instansi', 'VARCHAR', 100],
            'alamat_dudi' => ['alamat_instansi', 'TEXT', null],
        ];

        foreach ($map as $old => [$new, $type, $constraint]) {
            if (! $this->db->fieldExists($old, 'tb_tracer_alumni') || $this->db->fieldExists($new, 'tb_tracer_alumni')) {
                continue;
            }

            $definition = [
                'name' => $new,
                'type' => $type,
                'null' => true,
            ];

            if ($constraint !== null) {
                $definition['constraint'] = $constraint;
            }

            $this->forge->modifyColumn('tb_tracer_alumni', [
                $old => $definition,
            ]);
        }
    }
}
