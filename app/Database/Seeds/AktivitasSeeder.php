<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/*
|-------------------------------------------------------------------
| AKTIVITAS SEEDER
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: seeder ini mengisi data awal master
| aktivitas alumni agar form tracer study langsung memiliki opsi
| standar saat pertama kali dipakai.
| Alur kerja: CI4 menjalankan class ini saat php spark db:seed
| memanggil AktivitasSeeder, lalu method run() menyisipkan data awal
| aktivitas satu per satu bila namanya belum tersedia.
|
| Tips Debugging:
| - Jika data tidak masuk, cek tabel tb_aktivitas sudah berhasil dibuat.
| - Jika data dobel, cek seeder dijalankan berulang tanpa membersihkan tabel.
*/
class AktivitasSeeder extends Seeder
{
    /*
    |-------------------------------------------------------------------
    | METHOD RUN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memasukkan empat data
    | aktivitas awal ke tabel tb_aktivitas.
    | Alur kerja: CI4 memanggil method ini saat seeder dijalankan,
    | lalu method melakukan insert hanya untuk nama aktivitas yang
    | belum tersedia agar aman dijalankan ulang.
    |
    | Tips Debugging:
    | - Jika insert gagal, cek nama kolom pada tabel dan data batch.
    | - Jika data tidak berubah saat seeder diulang, cek nama_aktivitas yang sama memang di-skip.
    */
    public function run()
    {
        $waktuSekarang = date('Y-m-d H:i:s');

        $data = [
            [
                'nama_aktivitas'  => 'Bekerja',
                'keterangan'      => 'Alumni sudah bekerja setelah lulus.',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
            [
                'nama_aktivitas'  => 'Kuliah',
                'keterangan'      => 'Alumni melanjutkan studi ke perguruan tinggi.',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
            [
                'nama_aktivitas'  => 'Wirausaha',
                'keterangan'      => 'Alumni menjalankan usaha mandiri atau bisnis sendiri.',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
            [
                'nama_aktivitas'  => 'Mencari Kerja',
                'keterangan'      => 'Alumni sedang mencari peluang kerja setelah lulus.',
                'status_aktif'    => 1,
                'dibuat_pada'     => $waktuSekarang,
                'diperbarui_pada' => $waktuSekarang,
            ],
        ];

        $aktivitasMencariKerja = $this->db->table('tb_aktivitas')
            ->select('id_aktivitas')
            ->where('nama_aktivitas', 'Mencari Kerja')
            ->get()
            ->getRowArray();
        $aktivitasBelumBekerja = $this->db->table('tb_aktivitas')
            ->select('id_aktivitas')
            ->where('nama_aktivitas', 'Belum Bekerja')
            ->get()
            ->getRowArray();

        if ($aktivitasBelumBekerja !== null && $aktivitasMencariKerja === null) {
            $this->db->table('tb_aktivitas')
                ->where('id_aktivitas', (int) $aktivitasBelumBekerja['id_aktivitas'])
                ->update([
                    'nama_aktivitas'  => 'Mencari Kerja',
                    'keterangan'      => 'Alumni sedang mencari peluang kerja setelah lulus.',
                    'diperbarui_pada' => $waktuSekarang,
                ]);
        } elseif ($aktivitasBelumBekerja !== null && $aktivitasMencariKerja !== null) {
            if ($this->db->tableExists('tb_tracer_alumni')) {
                $this->db->table('tb_tracer_alumni')
                    ->where('id_aktivitas', (int) $aktivitasBelumBekerja['id_aktivitas'])
                    ->update(['id_aktivitas' => (int) $aktivitasMencariKerja['id_aktivitas']]);
            }

            $this->db->table('tb_aktivitas')
                ->where('id_aktivitas', (int) $aktivitasBelumBekerja['id_aktivitas'])
                ->update([
                    'status_aktif'    => 0,
                    'diperbarui_pada' => $waktuSekarang,
                ]);
        }

        foreach ($data as $baris) {
            $sudahAda = $this->db->table('tb_aktivitas')
                ->select('id_aktivitas')
                ->where('nama_aktivitas', $baris['nama_aktivitas'])
                ->get()
                ->getRowArray();

            if ($sudahAda !== null) {
                continue;
            }

            $this->db->table('tb_aktivitas')->insert($baris);
        }
    }
}
