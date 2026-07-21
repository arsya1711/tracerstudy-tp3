<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Menyiapkan data demo lengkap untuk presentasi/sidang.
 *
 * Seeder ini tidak menghapus data yang sudah ada. Data demo dikenali dari
 * alamat email yang didefinisikan di dataAlumniDemo() dan diperbarui saat
 * seeder diulang. Pola email demo lama tetap dideteksi untuk proses migrasi.
 */
class SidangSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'demo1234';

    private const GURU_EMAIL = 'rina.wulandari.skom@gmail.com';

    private const GURU_LEGACY_EMAIL = 'guru.demo@demo.tracer.test';

    public function run()
    {
        if (ENVIRONMENT === 'production') {
            throw new RuntimeException('SidangSeeder hanya boleh dijalankan pada environment non-production.');
        }

        $this->call(PeranSeeder::class);
        $this->call(PenggunaSeeder::class);
        $this->call(AktivitasSeeder::class);

        $now = date('Y-m-d H:i:s');
        $angkatan = $this->siapkanAngkatan($now);
        $kompetensi = $this->siapkanKompetensi($now);
        $aktivitas = $this->ambilIdBerdasarkanNama('tb_aktivitas', 'nama_aktivitas', 'id_aktivitas');
        $peran = $this->ambilIdBerdasarkanNama('tb_peran', 'slug_peran', 'id_peran');

        foreach (['alumni', 'admin_sekolah'] as $slug) {
            if (! isset($peran[$slug])) {
                throw new RuntimeException("Peran {$slug} tidak ditemukan.");
            }
        }

        foreach (['Bekerja', 'Kuliah', 'Wirausaha', 'Mencari Kerja'] as $namaAktivitas) {
            if (! isset($aktivitas[$namaAktivitas])) {
                throw new RuntimeException("Aktivitas {$namaAktivitas} tidak ditemukan.");
            }
        }

        // Role aplikasi yang paling dekat dengan guru/petugas tracer adalah Admin Sekolah.
        $idAdmin = $this->simpanGuruDemo((int) $peran['admin_sekolah'], $now);

        $alumniDemo = $this->dataAlumniDemo();
        $idAlumniByNomor = [];
        $this->db->transStart();

        foreach ($alumniDemo as $nomor => $demo) {
            $idPengguna = $this->simpanPenggunaDemo($nomor, $demo, (int) $peran['alumni'], $now);
            $idAlumni = $this->simpanAlumniDemo(
                $nomor,
                $demo,
                $idPengguna,
                (int) $angkatan[$demo['angkatan']],
                (int) $kompetensi[$demo['kompetensi']],
                $idAdmin,
                $now
            );

            $idAlumniByNomor[$nomor] = $idAlumni;

            if ($demo['aktivitas'] !== null) {
                $this->simpanTracerDemo(
                    $nomor,
                    $demo,
                    $idAlumni,
                    (int) $aktivitas[$demo['aktivitas']],
                    $idAdmin
                );
            }
        }

        $this->simpanLegalisirDemo($idAlumniByNomor, $idAdmin);
        $this->simpanNotifikasiDemo($idAlumniByNomor, $idAdmin, $now);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new RuntimeException('Transaksi pembuatan data demo sidang gagal.');
        }
    }

    private function siapkanAngkatan(string $now): array
    {
        foreach (['2022', '2023', '2024', '2025'] as $tahun) {
            $existing = $this->db->table('tb_angkatan')
                ->where('tahun_lulus', $tahun)
                ->get()
                ->getRowArray();

            if ($existing === null) {
                $this->db->table('tb_angkatan')->insert([
                    'tahun_lulus' => $tahun,
                    'status_aktif' => 1,
                    'dibuat_pada' => $now,
                    'diperbarui_pada' => $now,
                ]);
            } elseif ((int) $existing['status_aktif'] !== 1) {
                $this->db->table('tb_angkatan')
                    ->where('id_angkatan', (int) $existing['id_angkatan'])
                    ->update(['status_aktif' => 1, 'diperbarui_pada' => $now]);
            }
        }

        return $this->ambilIdBerdasarkanNama('tb_angkatan', 'tahun_lulus', 'id_angkatan');
    }

    private function siapkanKompetensi(string $now): array
    {
        $items = [
            'TJK' => 'Teknik Jaringan Komputer dan Telekomunikasi (TJK) Axioo Class Program (ACP)',
            'AKL' => 'Akuntansi dan Keuangan Lembaga (AKL)',
            'MPLB' => 'Manajemen Perkantoran dan Layanan Bisnis (MPLB)',
        ];

        foreach ($items as $akronim => $nama) {
            $existing = $this->db->table('tb_kompetensi')
                ->where('akronim', $akronim)
                ->get()
                ->getRowArray();

            $payload = [
                'nama_kompetensi' => $nama,
                'akronim' => $akronim,
                'status_aktif' => 1,
                'diperbarui_pada' => $now,
            ];

            if ($existing === null) {
                $payload['dibuat_pada'] = $now;
                $this->db->table('tb_kompetensi')->insert($payload);
            } else {
                $this->db->table('tb_kompetensi')
                    ->where('id_kompetensi', (int) $existing['id_kompetensi'])
                    ->update($payload);
            }
        }

        return $this->ambilIdBerdasarkanNama('tb_kompetensi', 'akronim', 'id_kompetensi');
    }

    private function simpanPenggunaDemo(int $nomor, array $demo, int $idPeran, string $now): int
    {
        $email = $demo['email'];
        $legacyEmail = sprintf('alumni%02d@demo.tracer.test', $nomor);
        $table = $this->db->table('tb_pengguna');
        $existing = $table->where('email', $email)->get()->getRowArray();
        if ($existing === null) {
            $existing = $table->where('email', $legacyEmail)->get()->getRowArray();
        }
        $payload = [
            'id_peran' => $idPeran,
            'nama_lengkap' => $demo['nama'],
            'email' => $email,
            'kata_sandi' => password_hash(self::DEMO_PASSWORD, PASSWORD_BCRYPT),
            'nomor_telepon' => sprintf('0812%08d', 45000000 + $nomor),
            'status_aktif' => 1,
            'terakhir_login' => $nomor <= 3 ? sprintf('2026-07-%02d 08:15:00', 10 + $nomor) : null,
            'diperbarui_pada' => $now,
        ];

        if ($existing === null) {
            $payload['dibuat_pada'] = sprintf('2026-05-%02d 09:00:00', min(28, $nomor));
            $table->insert($payload);

            return (int) $this->db->insertID();
        }

        $table->where('id_pengguna', (int) $existing['id_pengguna'])->update($payload);

        return (int) $existing['id_pengguna'];
    }

    private function simpanGuruDemo(int $idPeran, string $now): int
    {
        $table = $this->db->table('tb_pengguna');
        $existing = $table->where('email', self::GURU_EMAIL)->get()->getRowArray();
        if ($existing === null) {
            $existing = $table->where('email', self::GURU_LEGACY_EMAIL)->get()->getRowArray();
        }
        $payload = [
            'id_peran' => $idPeran,
            'nama_lengkap' => 'Ibu Rina Wulandari, S.Kom.',
            'email' => self::GURU_EMAIL,
            'kata_sandi' => password_hash(self::DEMO_PASSWORD, PASSWORD_BCRYPT),
            'nomor_telepon' => '081298765432',
            'status_aktif' => 1,
            'terakhir_login' => '2026-07-18 08:00:00',
            'diperbarui_pada' => $now,
        ];

        if ($existing === null) {
            $payload['dibuat_pada'] = '2026-05-01 08:00:00';
            $table->insert($payload);

            return (int) $this->db->insertID();
        }

        $table->where('id_pengguna', (int) $existing['id_pengguna'])->update($payload);

        return (int) $existing['id_pengguna'];
    }

    private function simpanAlumniDemo(
        int $nomor,
        array $demo,
        int $idPengguna,
        int $idAngkatan,
        int $idKompetensi,
        int $idAdmin,
        string $now
    ): int {
        $table = $this->db->table('tb_alumni');
        $existing = $table->where('id_pengguna', $idPengguna)->get()->getRowArray();
        $tahunLahir = 2002 + (($nomor - 1) % 4);
        $payload = [
            'id_pengguna' => $idPengguna,
            'id_angkatan' => $idAngkatan,
            'id_kompetensi' => $idKompetensi,
            'nis' => sprintf('1920%04d', $nomor),
            'nisn' => sprintf('00%08d', 31000000 + $nomor),
            'no_ijazah' => sprintf('DN-02/M-SMK/13/%04d/%d', $nomor, $demo['angkatan']),
            'jenis_kelamin' => $demo['jk'],
            'tempat_lahir' => $demo['tempat_lahir'],
            'tanggal_lahir' => sprintf('%d-%02d-%02d', $tahunLahir, (($nomor - 1) % 12) + 1, (($nomor * 3) % 27) + 1),
            'alamat' => $demo['alamat'],
            'status_verifikasi' => 'aktif',
            'status_pendaftaran' => 'aktif',
            'catatan_verifikasi' => 'Data alumni demo telah diverifikasi untuk simulasi sidang.',
            'terdaftar_pada' => sprintf('2026-05-%02d 09:05:00', min(28, $nomor)),
            'diverifikasi_oleh' => $idAdmin,
            'diverifikasi_pada' => sprintf('2026-05-%02d 10:00:00', min(28, $nomor)),
            'diperbarui_pada' => $now,
        ];

        if ($existing === null) {
            $payload['dibuat_pada'] = sprintf('2026-05-%02d 09:05:00', min(28, $nomor));
            $table->insert($payload);

            return (int) $this->db->insertID();
        }

        $table->where('id_alumni', (int) $existing['id_alumni'])->update($payload);

        return (int) $existing['id_alumni'];
    }

    private function simpanTracerDemo(int $nomor, array $demo, int $idAlumni, int $idAktivitas, int $idAdmin): void
    {
        $table = $this->db->table('tb_tracer_alumni');
        $existing = $table->where('id_alumni', $idAlumni)->get()->getRowArray();
        $status = ['terkirim', 'terverifikasi', 'disetujui'][$nomor % 3];
        $tanggal = sprintf('2026-06-%02d %02d:30:00', (($nomor - 1) % 28) + 1, 8 + ($nomor % 8));
        $payload = array_merge([
            'id_alumni' => $idAlumni,
            'id_aktivitas' => $idAktivitas,
            'status' => $status,
            'diverifikasi_oleh' => $status !== 'terkirim' ? $idAdmin : null,
            'diverifikasi_pada' => $status !== 'terkirim' ? $tanggal : null,
            'disetujui_oleh' => $status === 'disetujui' ? $idAdmin : null,
            'disetujui_pada' => $status === 'disetujui' ? $tanggal : null,
            'rencana_kedepan' => $demo['rencana'],
            'dibuat_pada' => $tanggal,
            'diperbarui_pada' => $tanggal,
        ], $this->detailTracer($nomor, $demo));

        if ($existing === null) {
            $table->insert($payload);
        } else {
            $table->where('id_tracer', (int) $existing['id_tracer'])->update($payload);
        }
    }

    private function detailTracer(int $nomor, array $demo): array
    {
        $perusahaan = ['PT Telkom Indonesia', 'PT Astra International', 'PT Denso Indonesia', 'PT Shopee International', 'PT Mitra Informatika', 'CV Kreasi Digital', 'PT Data Solusi Nusantara', 'PT Sumber Alfaria Trijaya'];
        $posisi = ['Junior Web Developer', 'Teknisi Jaringan', 'Desainer Grafis', 'IT Support', 'Teknisi Otomotif', 'Digital Marketing', 'Quality Assurance', 'Staf Administrasi'];
        $kampus = ['Universitas Bina Sarana Informatika', 'Universitas Bhayangkara Jakarta Raya', 'Universitas Gunadarma', 'Politeknik Negeri Jakarta'];
        $prodi = ['Sistem Informasi', 'Teknik Informatika', 'Desain Komunikasi Visual', 'Teknik Mesin'];
        $usaha = ['Kreasi Visual Bekasi', 'ByteCare Komputer', 'Teratai Auto Service', 'Dapur Alumni', 'Nusa Digital Printing'];
        $detail = [
            'posisi_kerja' => null,
            'nama_instansi' => null,
            'bidang_instansi' => null,
            'alamat_instansi' => null,
            'tahun_mulai_kerja' => null,
            'relevan_jurusan' => null,
            'penghasilan_range' => null,
            'universitas' => null,
            'program_studi' => null,
            'status_kuliah' => null,
            'nama_usaha' => null,
            'bidang_usaha' => null,
            'modal_awal' => null,
            'penghasilan_usaha' => null,
        ];

        if ($demo['aktivitas'] === 'Bekerja') {
            $detail['posisi_kerja'] = $posisi[($nomor - 1) % count($posisi)];
            $detail['nama_instansi'] = $perusahaan[($nomor - 1) % count($perusahaan)];
            $detail['bidang_instansi'] = ['Teknologi Informasi', 'Manufaktur', 'Industri Kreatif', 'Perdagangan'][($nomor - 1) % 4];
            $detail['alamat_instansi'] = 'Kota Bekasi, Jawa Barat';
            $detail['tahun_mulai_kerja'] = (string) $demo['angkatan'];
            $detail['relevan_jurusan'] = $nomor % 4 === 0 ? 0 : 1;
            $detail['penghasilan_range'] = ['< Rp2.000.000', 'Rp2.000.000 - Rp4.000.000', 'Rp4.000.000 - Rp6.000.000'][($nomor - 1) % 3];
        } elseif ($demo['aktivitas'] === 'Kuliah') {
            $detail['universitas'] = $kampus[($nomor - 1) % count($kampus)];
            $detail['program_studi'] = $prodi[($nomor - 1) % count($prodi)];
            $detail['status_kuliah'] = 'Aktif';
            $detail['relevan_jurusan'] = $nomor % 2;
        } elseif ($demo['aktivitas'] === 'Wirausaha') {
            $detail['nama_usaha'] = $usaha[($nomor - 1) % count($usaha)];
            $detail['bidang_usaha'] = ['Jasa Desain', 'Servis Komputer', 'Servis Kendaraan', 'Kuliner'][($nomor - 1) % 4];
            $detail['modal_awal'] = 2500000 + (($nomor % 5) * 1500000);
            $detail['penghasilan_usaha'] = ['< Rp2.000.000', 'Rp2.000.000 - Rp4.000.000', 'Rp4.000.000 - Rp6.000.000'][($nomor - 1) % 3];
            $detail['relevan_jurusan'] = $nomor % 3 === 0 ? 0 : 1;
        } else {
            $detail['rencana_kedepan'] = 'Aktif mengikuti pelatihan dan mencari pekerjaan yang sesuai kompetensi.';
        }

        return $detail;
    }

    private function simpanLegalisirDemo(array $idAlumniByNomor, int $idAdmin): void
    {
        $statuses = ['diajukan', 'diproses', 'selesai', 'ditolak'];
        $dokumen = ['Ijazah', 'SKHUN', 'Rapor'];

        for ($nomor = 1; $nomor <= 12; $nomor++) {
            $status = $statuses[($nomor - 1) % count($statuses)];
            $tanggal = sprintf('2026-07-%02d 09:30:00', $nomor);
            $keperluan = 'Data demo sidang #' . sprintf('%02d', $nomor) . ' untuk persyaratan ' . ($nomor % 2 === 0 ? 'kuliah.' : 'melamar pekerjaan.');
            $table = $this->db->table('tb_pengajuan_legalisir');
            $existing = $table
                ->where('id_alumni', $idAlumniByNomor[$nomor])
                ->where('keperluan', $keperluan)
                ->get()
                ->getRowArray();
            $payload = [
                'id_alumni' => $idAlumniByNomor[$nomor],
                'jenis_dokumen' => $dokumen[($nomor - 1) % count($dokumen)],
                'jumlah_lembar' => (($nomor - 1) % 3) + 1,
                'keperluan' => $keperluan,
                'status' => $status,
                'catatan_admin' => $this->catatanLegalisir($status),
                'diproses_oleh' => $status === 'diajukan' ? null : $idAdmin,
                'diproses_pada' => $status === 'diajukan' ? null : $tanggal,
                'selesai_pada' => $status === 'selesai' ? $tanggal : null,
                'dibuat_pada' => $tanggal,
                'diperbarui_pada' => $tanggal,
            ];

            if ($existing === null) {
                $table->insert($payload);
            } else {
                $table->where('id_pengajuan_legalisir', (int) $existing['id_pengajuan_legalisir'])->update($payload);
            }
        }
    }

    private function catatanLegalisir(string $status): ?string
    {
        return match ($status) {
            'diproses' => 'Dokumen sedang diperiksa dan diproses oleh tata usaha.',
            'selesai' => 'Dokumen sudah selesai dan dapat diambil di tata usaha.',
            'ditolak' => 'Mohon lengkapi salinan dokumen yang lebih jelas.',
            default => null,
        };
    }

    private function simpanNotifikasiDemo(array $idAlumniByNomor, int $idAdmin, string $now): void
    {
        $idPenggunaAlumni = [];
        foreach ([1, 2, 3] as $nomor) {
            $row = $this->db->table('tb_alumni')
                ->select('id_pengguna')
                ->where('id_alumni', $idAlumniByNomor[$nomor])
                ->get()
                ->getRowArray();
            $idPenggunaAlumni[$nomor] = (int) $row['id_pengguna'];
        }

        $items = [
            [$idAdmin, 'tracer_baru', 'Tracer alumni demo masuk', 'Siti Rahmawati telah mengisi tracer study.', 'superadmin/tracer', 0],
            [$idAdmin, 'legalisir_baru', 'Pengajuan legalisir baru', 'Alumni demo mengajukan legalisir ijazah.', 'superadmin/legalisir', 0],
            [$idPenggunaAlumni[1], 'legalisir_status', 'Legalisir sedang diproses', 'Pengajuan legalisir sedang diperiksa oleh tata usaha.', 'alumni/legalisir', 0],
            [$idPenggunaAlumni[2], 'umum', 'Data tracer tersimpan', 'Terima kasih telah berpartisipasi dalam tracer study.', 'alumni/tracer', 1],
            [$idPenggunaAlumni[3], 'legalisir_status', 'Legalisir selesai', 'Dokumen legalisir sudah dapat diambil di tata usaha.', 'alumni/legalisir', 0],
        ];

        foreach ($items as [$idPengguna, $tipe, $judul, $pesan, $targetUrl, $dibaca]) {
            $table = $this->db->table('tb_notifikasi');
            $existing = $table
                ->where('id_pengguna', $idPengguna)
                ->where('judul', $judul)
                ->where('pesan', $pesan)
                ->get()
                ->getRowArray();
            $payload = [
                'id_pengguna' => $idPengguna,
                'tipe' => $tipe,
                'judul' => $judul,
                'pesan' => $pesan,
                'target_url' => $targetUrl,
                'dibaca' => $dibaca,
                'dibaca_pada' => $dibaca ? '2026-07-15 10:00:00' : null,
                'dibuat_pada' => '2026-07-15 09:00:00',
                'diperbarui_pada' => $now,
            ];

            if ($existing === null) {
                $table->insert($payload);
            } else {
                $table->where('id_notifikasi', (int) $existing['id_notifikasi'])->update($payload);
            }
        }
    }

    private function ambilIdBerdasarkanNama(string $table, string $nameField, string $idField): array
    {
        $map = [];
        foreach ($this->db->table($table)->select([$idField, $nameField])->get()->getResultArray() as $row) {
            $map[(string) $row[$nameField]] = (int) $row[$idField];
        }

        return $map;
    }

    private function dataAlumniDemo(): array
    {
        $names = [
            ['Siti Rahmawati', 'P', 'Bekasi'], ['Muhammad Rizky Pratama', 'L', 'Jakarta'],
            ['Aulia Putri Maharani', 'P', 'Bekasi'], ['Fajar Ramadhan', 'L', 'Karawang'],
            ['Nabila Zahra', 'P', 'Depok'], ['Dimas Saputra', 'L', 'Bekasi'],
            ['Citra Lestari', 'P', 'Jakarta'], ['Raka Aditya', 'L', 'Bogor'],
            ['Intan Permata Sari', 'P', 'Bekasi'], ['Bagas Maulana', 'L', 'Cikarang'],
            ['Nadya Oktaviani', 'P', 'Bekasi'], ['Reza Alfarizi', 'L', 'Jakarta'],
            ['Dewi Anggraini', 'P', 'Karawang'], ['Andika Setiawan', 'L', 'Bekasi'],
            ['Putri Amelia', 'P', 'Depok'], ['Yoga Prasetyo', 'L', 'Bekasi'],
            ['Annisa Nur Haliza', 'P', 'Jakarta'], ['Gilang Ramadhan', 'L', 'Cikarang'],
            ['Maya Safitri', 'P', 'Bekasi'], ['Rendi Kurniawan', 'L', 'Bogor'],
            ['Laila Nuraini', 'P', 'Bekasi'], ['Ilham Akbar', 'L', 'Jakarta'],
            ['Vina Aprilia', 'P', 'Karawang'], ['Farhan Hidayat', 'L', 'Bekasi'],
        ];
        $activities = [
            'Bekerja', 'Bekerja', 'Kuliah', 'Wirausaha', 'Mencari Kerja', null,
            'Bekerja', 'Kuliah', 'Bekerja', 'Wirausaha', 'Mencari Kerja', null,
            'Bekerja', 'Bekerja', 'Kuliah', 'Wirausaha', 'Mencari Kerja', null,
            'Bekerja', 'Kuliah', 'Bekerja', 'Wirausaha', 'Mencari Kerja', null,
        ];
        $emails = [
            'siti.rahmawati22@gmail.com', 'rizky.pratama02@gmail.com',
            'aulia.maharani@gmail.com', 'fajarramadhan04@gmail.com',
            'nabila.zahra05@gmail.com', 'dimassaputra06@gmail.com',
            'citra.lestari07@gmail.com', 'rakaaditya08@gmail.com',
            'intan.permata09@gmail.com', 'bagasmaulana10@gmail.com',
            'nadya.oktaviani11@gmail.com', 'reza.alfarizi12@gmail.com',
            'dewianggraini13@gmail.com', 'andika.setiawan14@gmail.com',
            'putriamelia15@gmail.com', 'yogaprasetyo16@gmail.com',
            'annisa.haliza17@gmail.com', 'gilangramadhan18@gmail.com',
            'maya.safitri19@gmail.com', 'rendi.kurniawan20@gmail.com',
            'laila.nuraini21@gmail.com', 'ilhamakbar22@gmail.com',
            'vina.aprilia23@gmail.com', 'farhanhidayat24@gmail.com',
        ];
        $competencies = ['TJK', 'AKL', 'MPLB'];
        $cohorts = ['2022', '2023', '2024', '2025'];
        $result = [];

        foreach ($names as $index => [$nama, $jk, $tempatLahir]) {
            $nomor = $index + 1;
            $result[$nomor] = [
                'nama' => $nama,
                'email' => $emails[$index],
                'jk' => $jk,
                'tempat_lahir' => $tempatLahir,
                'alamat' => 'Kecamatan ' . ['Bekasi Timur', 'Rawalumbu', 'Mustika Jaya', 'Bantargebang'][$index % 4] . ', Kota Bekasi, Jawa Barat',
                'angkatan' => $cohorts[intdiv($index, 6)],
                'kompetensi' => $competencies[$index % 4],
                'aktivitas' => $activities[$index],
                'rencana' => 'Mengembangkan kompetensi, pengalaman profesional, dan jejaring karier.',
            ];
        }

        return $result;
    }
}
