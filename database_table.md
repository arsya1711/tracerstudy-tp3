# Dokumentasi Tabel Database

Dokumen ini adalah catatan struktur database proyek BKK & Tracer Study.
Semua isi diambil dari database lokal `db_bkk_tracerstudy`.

## Identitas

| Item | Nilai |
| --- | --- |
| Database | `db_bkk_tracerstudy` |
| Dibuat dari | Database live Laragon lokal |
| Tanggal catatan | 2026-05-01 19.03.14 WIB |
| Format karakter | ASCII aman untuk catatan (`-`, `->`, tanpa simbol khusus) |

## Ringkasan Modul

| Modul | Tabel | Fungsi Utama |
| --- | --- | --- |
| Sistem Migration CodeIgniter | `migrations` | Status migration yang sudah dijalankan |
| Autentikasi dan Pengguna | `tb_peran`, `tb_pengguna` | Role dan akun login pengguna |
| Master Data | `tb_angkatan`, `tb_kompetensi`, `tb_aktivitas`, `tb_jenis_berkas`, `tb_kerjasama` | Data referensi aplikasi |
| Pelamar dan Alumni | `tb_pelamar`, `tb_counter_pelamar`, `tb_alumni`, `tb_berkas`, `tb_riwayat_kerja` | Profil, dokumen, dan riwayat pelamar |
| Tracer Study | `tb_tracer_alumni` | Data aktivitas alumni setelah lulus |
| Perusahaan dan Kerjasama | `tb_perusahaan`, `tb_perusahaan_kerjasama` | Data DUDI dan bentuk kerja sama |
| Lowongan dan Lamaran | `tb_lowongan`, `tb_lamaran`, `tb_lamaran_status`, `tb_lamaran_berkas` | Lowongan kerja dan proses lamaran |

## Daftar Tabel

| No | Tabel | Modul | Jumlah Data | Fungsi |
| ---: | --- | --- | ---: | --- |
| 1 | `migrations` | Sistem Migration CodeIgniter | 22 | Menyimpan riwayat migration CodeIgniter yang sudah dijalankan. |
| 2 | `tb_peran` | Autentikasi dan Pengguna | 5 | Master role pengguna sistem. |
| 3 | `tb_pengguna` | Autentikasi dan Pengguna | 7 | Akun login semua aktor sistem. |
| 4 | `tb_angkatan` | Master Data | 4 | Master tahun lulus alumni. |
| 5 | `tb_kompetensi` | Master Data | 4 | Master kompetensi keahlian atau jurusan. |
| 6 | `tb_aktivitas` | Master Data | 4 | Master aktivitas alumni setelah lulus. |
| 7 | `tb_jenis_berkas` | Master Data | 9 | Master jenis dokumen profil dan lamaran. |
| 8 | `tb_kerjasama` | Master Data | 6 | Master jenis kerja sama sekolah dengan DUDI/perusahaan. |
| 9 | `tb_pelamar` | Pelamar dan Alumni | 4 | Profil utama pelamar umum maupun alumni. |
| 10 | `tb_counter_pelamar` | Pelamar dan Alumni | 3 | Counter harian generator account_id pelamar. |
| 11 | `tb_alumni` | Pelamar dan Alumni | 3 | Data akademik khusus pelamar alumni. |
| 12 | `tb_berkas` | Pelamar dan Alumni | 6 | Dokumen profil pelamar yang relatif stabil. |
| 13 | `tb_tracer_alumni` | Tracer Study | 3 | Isian tracer study alumni. |
| 14 | `tb_riwayat_kerja` | Pelamar dan Alumni | 2 | Riwayat pengalaman kerja pelamar. |
| 15 | `tb_perusahaan` | Perusahaan dan Kerjasama | 1 | Data DUDI/perusahaan mitra sekolah. |
| 16 | `tb_perusahaan_kerjasama` | Perusahaan dan Kerjasama | 4 | Pivot relasi perusahaan dengan jenis kerja sama. |
| 17 | `tb_lowongan` | Lowongan dan Lamaran | 1 | Lowongan kerja yang dibuat perusahaan. |
| 18 | `tb_lamaran` | Lowongan dan Lamaran | 1 | Transaksi utama lamaran pelamar ke lowongan. |
| 19 | `tb_lamaran_status` | Lowongan dan Lamaran | 3 | Histori perubahan status lamaran. |
| 20 | `tb_lamaran_berkas` | Lowongan dan Lamaran | 3 | Snapshot dokumen yang dikirim pada satu lamaran. |

## Detail Tabel

### migrations

- Modul: Sistem Migration CodeIgniter
- Fungsi: Menyimpan riwayat migration CodeIgniter yang sudah dijalankan.
- Catatan: Wajib ikut dicatat agar status migration aplikasi dan database tetap sinkron.
- Jumlah data saat catatan dibuat: 22

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | NO | PRI | `NULL` | auto_increment |
| `version` | `varchar(255)` | NO | - | `NULL` | - |
| `class` | `varchar(255)` | NO | - | `NULL` | - |
| `group` | `varchar(255)` | NO | - | `NULL` | - |
| `namespace` | `varchar(255)` | NO | - | `NULL` | - |
| `time` | `int` | NO | - | `NULL` | - |
| `batch` | `int unsigned` | NO | - | `NULL` | - |

### tb_peran

- Modul: Autentikasi dan Pengguna
- Fungsi: Master role pengguna sistem.
- Catatan: slug_peran dipakai untuk routing akses seperti superadmin, admin_sekolah, admin_dudi, pelamar_umum, dan pelamar_alumni.
- Jumlah data saat catatan dibuat: 5

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_peran` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `nama_peran` | `varchar(50)` | NO | - | `NULL` | - |
| `slug_peran` | `varchar(50)` | NO | UNI | `NULL` | - |
| `keterangan` | `text` | YES | - | `NULL` | - |
| `dibuat_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `diperbarui_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

### tb_pengguna

- Modul: Autentikasi dan Pengguna
- Fungsi: Akun login semua aktor sistem.
- Catatan: Jenis pengguna tidak memakai kolom terpisah karena sudah diwakili oleh id_peran.
- Jumlah data saat catatan dibuat: 7

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_pengguna` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_peran` | `int unsigned` | NO | MUL | `NULL` | - |
| `nama_lengkap` | `varchar(150)` | NO | - | `NULL` | - |
| `email` | `varchar(150)` | NO | UNI | `NULL` | - |
| `kata_sandi` | `varchar(255)` | NO | - | `NULL` | - |
| `nomor_telepon` | `varchar(20)` | YES | - | `NULL` | - |
| `foto_profil` | `varchar(255)` | YES | - | `NULL` | - |
| `status_aktif` | `tinyint(1)` | NO | - | `1` | - |
| `token_reset` | `varchar(255)` | YES | - | `NULL` | - |
| `token_reset_expired` | `datetime` | YES | - | `NULL` | - |
| `terakhir_login` | `datetime` | YES | - | `NULL` | - |
| `dibuat_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `diperbarui_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

### tb_angkatan

- Modul: Master Data
- Fungsi: Master tahun lulus alumni.
- Catatan: Dipakai untuk filter alumni dan statistik tracer study.
- Jumlah data saat catatan dibuat: 4

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_angkatan` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `tahun_lulus` | `year` | NO | UNI | `NULL` | - |
| `status_aktif` | `tinyint(1)` | NO | - | `1` | - |
| `dibuat_pada` | `datetime` | YES | - | `NULL` | - |
| `diperbarui_pada` | `datetime` | YES | - | `NULL` | - |

### tb_kompetensi

- Modul: Master Data
- Fungsi: Master kompetensi keahlian atau jurusan.
- Catatan: Dipakai pada data akademik alumni dan rekap berdasarkan jurusan.
- Jumlah data saat catatan dibuat: 4

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_kompetensi` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `nama_kompetensi` | `varchar(100)` | NO | - | `NULL` | - |
| `akronim` | `varchar(20)` | NO | - | `NULL` | - |
| `status_aktif` | `tinyint(1)` | NO | - | `1` | - |
| `dibuat_pada` | `datetime` | YES | - | `NULL` | - |
| `diperbarui_pada` | `datetime` | YES | - | `NULL` | - |

### tb_aktivitas

- Modul: Master Data
- Fungsi: Master aktivitas alumni setelah lulus.
- Catatan: Contoh aktivitas: bekerja, kuliah, wirausaha, atau belum bekerja.
- Jumlah data saat catatan dibuat: 4

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_aktivitas` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `nama_aktivitas` | `varchar(100)` | NO | UNI | `NULL` | - |
| `keterangan` | `text` | YES | - | `NULL` | - |
| `status_aktif` | `tinyint(1)` | NO | - | `1` | - |
| `dibuat_pada` | `datetime` | NO | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `diperbarui_pada` | `datetime` | NO | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

### tb_jenis_berkas

- Modul: Master Data
- Fungsi: Master jenis dokumen profil dan lamaran.
- Catatan: scope_penggunaan membedakan profil, lamaran, atau keduanya. boleh_multi_upload mengatur apakah satu jenis dokumen boleh banyak file.
- Jumlah data saat catatan dibuat: 9

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_jenis_berkas` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `nama_berkas` | `varchar(100)` | NO | - | `NULL` | - |
| `slug_berkas` | `varchar(50)` | NO | UNI | `NULL` | - |
| `wajib` | `tinyint(1)` | NO | - | `1` | - |
| `berlaku_untuk` | `enum('semua','alumni','umum')` | NO | - | `semua` | - |
| `scope_penggunaan` | `enum('profil','lamaran','keduanya')` | NO | - | `profil` | - |
| `boleh_multi_upload` | `tinyint(1)` | NO | - | `0` | - |
| `keterangan` | `text` | YES | - | `NULL` | - |
| `status_aktif` | `tinyint(1)` | NO | - | `1` | - |
| `dibuat_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `diperbarui_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

### tb_kerjasama

- Modul: Master Data
- Fungsi: Master jenis kerja sama sekolah dengan DUDI/perusahaan.
- Catatan: Relasi perusahaan ke jenis kerja sama disimpan di tb_perusahaan_kerjasama.
- Jumlah data saat catatan dibuat: 6

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_kerjasama` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `nama_kerjasama` | `varchar(150)` | NO | UNI | `NULL` | - |
| `slug_kerjasama` | `varchar(150)` | NO | UNI | `NULL` | - |
| `deskripsi` | `text` | YES | - | `NULL` | - |
| `status_aktif` | `tinyint(1)` | NO | - | `1` | - |
| `dibuat_pada` | `datetime` | NO | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `diperbarui_pada` | `datetime` | NO | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

### tb_pelamar

- Modul: Pelamar dan Alumni
- Fungsi: Profil utama pelamar umum maupun alumni.
- Catatan: account_id menjadi nomor identitas pelamar dengan format PLM-YYYYMMDD0001.
- Jumlah data saat catatan dibuat: 4

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_pelamar` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_pengguna` | `int unsigned` | NO | UNI | `NULL` | - |
| `account_id` | `varchar(30)` | NO | UNI | `NULL` | - |
| `foto` | `varchar(255)` | YES | - | `NULL` | - |
| `jenis_kelamin` | `varchar(20)` | YES | - | `NULL` | - |
| `tempat_lahir` | `varchar(100)` | YES | - | `NULL` | - |
| `tanggal_lahir` | `date` | YES | - | `NULL` | - |
| `alamat` | `text` | YES | - | `NULL` | - |
| `nomer_nik` | `varchar(30)` | YES | - | `NULL` | - |
| `status_pendaftaran` | `enum('menunggu_aktivasi','aktif','terdaftar')` | NO | MUL | `menunggu_aktivasi` | - |
| `terdaftar_pada` | `datetime` | YES | - | `NULL` | - |
| `diaktivasi_oleh` | `int unsigned` | YES | MUL | `NULL` | - |
| `diaktivasi_pada` | `datetime` | YES | - | `NULL` | - |
| `dibuat_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `diperbarui_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

### tb_counter_pelamar

- Modul: Pelamar dan Alumni
- Fungsi: Counter harian generator account_id pelamar.
- Catatan: Dipakai oleh PelamarModel::generateAccountId() agar nomor tidak mundur atau dipakai ulang.
- Jumlah data saat catatan dibuat: 3

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_counter_pelamar` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `tanggal_generate` | `varchar(8)` | NO | UNI | `NULL` | - |
| `nomor_terakhir` | `int unsigned` | NO | - | `0` | - |
| `dibuat_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `diperbarui_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

### tb_alumni

- Modul: Pelamar dan Alumni
- Fungsi: Data akademik khusus pelamar alumni.
- Catatan: Verifikasi akademik dipisahkan dari verifikasi isi tracer study.
- Jumlah data saat catatan dibuat: 3

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_alumni` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_pelamar` | `int unsigned` | NO | UNI | `NULL` | - |
| `id_angkatan` | `int unsigned` | YES | MUL | `NULL` | - |
| `id_kompetensi` | `int unsigned` | YES | MUL | `NULL` | - |
| `nis` | `varchar(30)` | YES | - | `NULL` | - |
| `nisn` | `varchar(30)` | YES | - | `NULL` | - |
| `no_ijazah` | `varchar(50)` | YES | - | `NULL` | - |
| `status_verifikasi` | `varchar(30)` | YES | - | `NULL` | - |
| `catatan_verifikasi` | `text` | YES | - | `NULL` | - |
| `diverifikasi_oleh` | `int unsigned` | YES | MUL | `NULL` | - |
| `diverifikasi_pada` | `datetime` | YES | - | `NULL` | - |
| `dibuat_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `diperbarui_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

### tb_berkas

- Modul: Pelamar dan Alumni
- Fungsi: Dokumen profil pelamar yang relatif stabil.
- Catatan: Snapshot dokumen per lamaran disimpan terpisah di tb_lamaran_berkas.
- Jumlah data saat catatan dibuat: 6

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_berkas` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_pelamar` | `int unsigned` | NO | MUL | `NULL` | - |
| `id_jenis_berkas` | `int unsigned` | NO | MUL | `NULL` | - |
| `nama_file` | `varchar(255)` | YES | - | `NULL` | - |
| `path_file` | `varchar(255)` | YES | - | `NULL` | - |
| `ukuran_file` | `int unsigned` | YES | - | `NULL` | - |
| `tipe_mime` | `varchar(100)` | YES | - | `NULL` | - |
| `status_unggah` | `enum('belum_diunggah','sudah_diunggah','ditolak')` | NO | MUL | `belum_diunggah` | - |
| `catatan` | `text` | YES | - | `NULL` | - |
| `dibuat_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `diperbarui_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

### tb_tracer_alumni

- Modul: Tracer Study
- Fungsi: Isian tracer study alumni.
- Catatan: Status draft memungkinkan alumni menyimpan isian sebelum dikirim.
- Jumlah data saat catatan dibuat: 3

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_tracer` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_alumni` | `int unsigned` | NO | UNI | `NULL` | - |
| `id_aktivitas` | `int unsigned` | NO | MUL | `NULL` | - |
| `status` | `enum('draft','terkirim','terverifikasi','disetujui')` | NO | - | `draft` | - |
| `diverifikasi_oleh` | `int unsigned` | YES | MUL | `NULL` | - |
| `diverifikasi_pada` | `datetime` | YES | - | `NULL` | - |
| `disetujui_oleh` | `int unsigned` | YES | MUL | `NULL` | - |
| `disetujui_pada` | `datetime` | YES | - | `NULL` | - |
| `posisi_kerja` | `varchar(100)` | YES | - | `NULL` | - |
| `nama_dudi` | `varchar(150)` | YES | - | `NULL` | - |
| `bidang_dudi` | `varchar(100)` | YES | - | `NULL` | - |
| `alamat_dudi` | `text` | YES | - | `NULL` | - |
| `tahun_mulai_kerja` | `year` | YES | - | `NULL` | - |
| `relevan_jurusan` | `tinyint(1)` | YES | - | `NULL` | - |
| `penghasilan_range` | `varchar(50)` | YES | - | `NULL` | - |
| `universitas` | `varchar(150)` | YES | - | `NULL` | - |
| `program_studi` | `varchar(100)` | YES | - | `NULL` | - |
| `status_kuliah` | `varchar(50)` | YES | - | `NULL` | - |
| `nama_usaha` | `varchar(150)` | YES | - | `NULL` | - |
| `bidang_usaha` | `varchar(100)` | YES | - | `NULL` | - |
| `modal_awal` | `decimal(15,2)` | YES | - | `NULL` | - |
| `penghasilan_usaha` | `varchar(50)` | YES | - | `NULL` | - |
| `rencana_kedepan` | `text` | YES | - | `NULL` | - |
| `dibuat_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `diperbarui_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

### tb_riwayat_kerja

- Modul: Pelamar dan Alumni
- Fungsi: Riwayat pengalaman kerja pelamar.
- Catatan: Dipakai untuk profil dan kebutuhan screening HRD.
- Jumlah data saat catatan dibuat: 2

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_riwayat` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_pelamar` | `int unsigned` | NO | MUL | `NULL` | - |
| `nama_perusahaan` | `varchar(150)` | NO | - | `NULL` | - |
| `bidang_usaha` | `varchar(100)` | YES | - | `NULL` | - |
| `lokasi` | `varchar(100)` | YES | - | `NULL` | - |
| `posisi_jabatan` | `varchar(100)` | YES | - | `NULL` | - |
| `tanggal_mulai` | `date` | NO | MUL | `NULL` | - |
| `tanggal_selesai` | `date` | YES | - | `NULL` | - |
| `masih_bekerja` | `tinyint(1)` | NO | - | `0` | - |
| `keterangan` | `text` | YES | - | `NULL` | - |
| `dibuat_pada` | `datetime` | NO | - | `NULL` | - |
| `diperbarui_pada` | `datetime` | NO | - | `NULL` | - |

### tb_perusahaan

- Modul: Perusahaan dan Kerjasama
- Fungsi: Data DUDI/perusahaan mitra sekolah.
- Catatan: id_pengguna menghubungkan akun admin_dudi ke perusahaan yang dikelolanya.
- Jumlah data saat catatan dibuat: 1

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_perusahaan` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_pengguna` | `int unsigned` | YES | UNI | `NULL` | - |
| `nama_perusahaan` | `varchar(150)` | NO | UNI | `NULL` | - |
| `slug_perusahaan` | `varchar(150)` | NO | UNI | `NULL` | - |
| `bidang_usaha` | `varchar(100)` | YES | - | `NULL` | - |
| `deskripsi` | `text` | YES | - | `NULL` | - |
| `alamat` | `text` | YES | - | `NULL` | - |
| `kota` | `varchar(100)` | YES | MUL | `NULL` | - |
| `penanggung_jawab` | `varchar(150)` | YES | - | `NULL` | - |
| `no_telepon` | `varchar(20)` | YES | - | `NULL` | - |
| `email` | `varchar(100)` | YES | UNI | `NULL` | - |
| `website` | `varchar(150)` | YES | - | `NULL` | - |
| `logo` | `varchar(255)` | YES | - | `NULL` | - |
| `status_verifikasi` | `enum('menunggu','terverifikasi','ditolak')` | NO | - | `menunggu` | - |
| `status_aktif` | `tinyint(1)` | NO | - | `1` | - |
| `dibuat_pada` | `datetime` | YES | - | `NULL` | - |
| `diperbarui_pada` | `datetime` | YES | - | `NULL` | - |

### tb_perusahaan_kerjasama

- Modul: Perusahaan dan Kerjasama
- Fungsi: Pivot relasi perusahaan dengan jenis kerja sama.
- Catatan: Satu perusahaan dapat memiliki banyak bentuk kerja sama.
- Jumlah data saat catatan dibuat: 4

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_perusahaan_kerjasama` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_perusahaan` | `int unsigned` | NO | MUL | `NULL` | - |
| `id_kerjasama` | `int unsigned` | NO | MUL | `NULL` | - |
| `dibuat_pada` | `datetime` | YES | - | `NULL` | - |
| `diperbarui_pada` | `datetime` | YES | - | `NULL` | - |

### tb_lowongan

- Modul: Lowongan dan Lamaran
- Fungsi: Lowongan kerja yang dibuat perusahaan.
- Catatan: status membedakan draft, aktif, ditutup, dan kadaluarsa.
- Jumlah data saat catatan dibuat: 1

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_lowongan` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_perusahaan` | `int unsigned` | NO | MUL | `NULL` | - |
| `dibuat_oleh` | `int unsigned` | NO | MUL | `NULL` | - |
| `judul_lowongan` | `varchar(150)` | NO | - | `NULL` | - |
| `posisi` | `varchar(100)` | NO | - | `NULL` | - |
| `slug_lowongan` | `varchar(170)` | NO | UNI | `NULL` | - |
| `flyer_lowongan` | `varchar(255)` | YES | - | `NULL` | - |
| `deskripsi_pekerjaan` | `text` | YES | - | `NULL` | - |
| `kualifikasi` | `text` | YES | - | `NULL` | - |
| `jumlah_kebutuhan` | `int unsigned` | YES | - | `1` | - |
| `jenis_pekerjaan` | `enum('fulltime','parttime','magang','kontrak','freelance')` | NO | - | `fulltime` | - |
| `sistem_kerja` | `enum('onsite','remote','hybrid')` | NO | - | `onsite` | - |
| `pendidikan_min` | `enum('SMP','SMA/SMK','D3','S1','S2')` | YES | - | `NULL` | - |
| `pengalaman_min` | `varchar(50)` | YES | - | `NULL` | - |
| `rentang_gaji` | `varchar(50)` | YES | - | `NULL` | - |
| `lokasi_kerja` | `varchar(150)` | YES | - | `NULL` | - |
| `batas_lamaran` | `date` | YES | - | `NULL` | - |
| `tayang_hingga` | `datetime` | YES | - | `NULL` | - |
| `status` | `enum('draft','aktif','ditutup','kadaluarsa')` | NO | MUL | `draft` | - |
| `dibuat_pada` | `datetime` | YES | - | `NULL` | - |
| `diperbarui_pada` | `datetime` | YES | - | `NULL` | - |

### tb_lamaran

- Modul: Lowongan dan Lamaran
- Fungsi: Transaksi utama lamaran pelamar ke lowongan.
- Catatan: Status terbaru lamaran disimpan di tabel ini, histori status disimpan di tb_lamaran_status.
- Jumlah data saat catatan dibuat: 1

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_lamaran` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_pelamar` | `int unsigned` | NO | MUL | `NULL` | - |
| `id_lowongan` | `int unsigned` | NO | MUL | `NULL` | - |
| `dibuat_oleh` | `int unsigned` | NO | MUL | `NULL` | - |
| `status` | `enum('menunggu_verifikasi','perlu_perbaikan_berkas','diproses','wawancara','diterima','ditolak','mengundurkan_diri')` | NO | MUL | `menunggu_verifikasi` | - |
| `tanggal_melamar` | `datetime` | NO | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `batas_perbaikan_berkas` | `date` | YES | - | `NULL` | - |
| `tanggal_diproses` | `datetime` | YES | - | `NULL` | - |
| `tanggal_wawancara` | `datetime` | YES | - | `NULL` | - |
| `tanggal_keputusan` | `datetime` | YES | - | `NULL` | - |
| `dibuat_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |
| `diperbarui_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

### tb_lamaran_status

- Modul: Lowongan dan Lamaran
- Fungsi: Histori perubahan status lamaran.
- Catatan: Setiap perubahan status dicatat sebagai audit trail.
- Jumlah data saat catatan dibuat: 3

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_status` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_lamaran` | `int unsigned` | NO | MUL | `NULL` | - |
| `status_lama` | `enum('menunggu_verifikasi','perlu_perbaikan_berkas','diproses','wawancara','diterima','ditolak','mengundurkan_diri')` | YES | - | `NULL` | - |
| `status_baru` | `enum('menunggu_verifikasi','perlu_perbaikan_berkas','diproses','wawancara','diterima','ditolak','mengundurkan_diri')` | NO | - | `NULL` | - |
| `catatan` | `text` | YES | - | `NULL` | - |
| `diubah_oleh` | `int unsigned` | NO | MUL | `NULL` | - |
| `dibuat_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |

### tb_lamaran_berkas

- Modul: Lowongan dan Lamaran
- Fungsi: Snapshot dokumen yang dikirim pada satu lamaran.
- Catatan: Menjaga histori lamaran tetap aman walaupun dokumen profil diganti.
- Jumlah data saat catatan dibuat: 3

| Kolom | Tipe | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| `id_lamaran_berkas` | `int unsigned` | NO | PRI | `NULL` | auto_increment |
| `id_lamaran` | `int unsigned` | NO | MUL | `NULL` | - |
| `id_berkas` | `int unsigned` | YES | MUL | `NULL` | - |
| `id_jenis_berkas` | `int unsigned` | NO | MUL | `NULL` | - |
| `nama_file_snapshot` | `varchar(255)` | NO | - | `NULL` | - |
| `path_file_snapshot` | `varchar(255)` | NO | - | `NULL` | - |
| `ukuran_file_snapshot` | `int unsigned` | YES | - | `NULL` | - |
| `tipe_mime_snapshot` | `varchar(100)` | YES | - | `NULL` | - |
| `wajib_saat_submit` | `tinyint(1)` | NO | - | `0` | - |
| `status_review` | `enum('menunggu','sesuai','perlu_perbaikan','ditolak')` | NO | - | `menunggu` | - |
| `catatan_review` | `text` | YES | - | `NULL` | - |
| `ditinjau_oleh` | `int unsigned` | YES | MUL | `NULL` | - |
| `ditinjau_pada` | `datetime` | YES | - | `NULL` | - |
| `dibuat_pada` | `datetime` | YES | - | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED |

## Foreign Key

| Tabel | Kolom | Referensi | Constraint |
| --- | --- | --- | --- |
| `tb_alumni` | `diverifikasi_oleh` | `tb_pengguna.id_pengguna` | `tb_alumni_diverifikasi_oleh_foreign` |
| `tb_alumni` | `id_angkatan` | `tb_angkatan.id_angkatan` | `tb_alumni_id_angkatan_foreign` |
| `tb_alumni` | `id_kompetensi` | `tb_kompetensi.id_kompetensi` | `tb_alumni_id_kompetensi_foreign` |
| `tb_alumni` | `id_pelamar` | `tb_pelamar.id_pelamar` | `tb_alumni_id_pelamar_foreign` |
| `tb_berkas` | `id_jenis_berkas` | `tb_jenis_berkas.id_jenis_berkas` | `tb_berkas_id_jenis_berkas_foreign` |
| `tb_berkas` | `id_pelamar` | `tb_pelamar.id_pelamar` | `tb_berkas_id_pelamar_foreign` |
| `tb_lamaran` | `dibuat_oleh` | `tb_pengguna.id_pengguna` | `tb_lamaran_dibuat_oleh_foreign` |
| `tb_lamaran` | `id_lowongan` | `tb_lowongan.id_lowongan` | `tb_lamaran_id_lowongan_foreign` |
| `tb_lamaran` | `id_pelamar` | `tb_pelamar.id_pelamar` | `tb_lamaran_id_pelamar_foreign` |
| `tb_lamaran_berkas` | `ditinjau_oleh` | `tb_pengguna.id_pengguna` | `tb_lamaran_berkas_ditinjau_oleh_foreign` |
| `tb_lamaran_berkas` | `id_berkas` | `tb_berkas.id_berkas` | `tb_lamaran_berkas_id_berkas_foreign` |
| `tb_lamaran_berkas` | `id_jenis_berkas` | `tb_jenis_berkas.id_jenis_berkas` | `tb_lamaran_berkas_id_jenis_berkas_foreign` |
| `tb_lamaran_berkas` | `id_lamaran` | `tb_lamaran.id_lamaran` | `tb_lamaran_berkas_id_lamaran_foreign` |
| `tb_lamaran_status` | `diubah_oleh` | `tb_pengguna.id_pengguna` | `tb_lamaran_status_diubah_oleh_foreign` |
| `tb_lamaran_status` | `id_lamaran` | `tb_lamaran.id_lamaran` | `tb_lamaran_status_id_lamaran_foreign` |
| `tb_lowongan` | `dibuat_oleh` | `tb_pengguna.id_pengguna` | `tb_lowongan_dibuat_oleh_foreign` |
| `tb_lowongan` | `id_perusahaan` | `tb_perusahaan.id_perusahaan` | `tb_lowongan_id_perusahaan_foreign` |
| `tb_pelamar` | `diaktivasi_oleh` | `tb_pengguna.id_pengguna` | `tb_pelamar_diaktivasi_oleh_foreign` |
| `tb_pelamar` | `id_pengguna` | `tb_pengguna.id_pengguna` | `tb_pelamar_id_pengguna_foreign` |
| `tb_pengguna` | `id_peran` | `tb_peran.id_peran` | `tb_pengguna_id_peran_foreign` |
| `tb_perusahaan` | `id_pengguna` | `tb_pengguna.id_pengguna` | `tb_perusahaan_id_pengguna_foreign` |
| `tb_perusahaan_kerjasama` | `id_kerjasama` | `tb_kerjasama.id_kerjasama` | `tb_perusahaan_kerjasama_id_kerjasama_foreign` |
| `tb_perusahaan_kerjasama` | `id_perusahaan` | `tb_perusahaan.id_perusahaan` | `tb_perusahaan_kerjasama_id_perusahaan_foreign` |
| `tb_riwayat_kerja` | `id_pelamar` | `tb_pelamar.id_pelamar` | `tb_riwayat_kerja_id_pelamar_foreign` |
| `tb_tracer_alumni` | `disetujui_oleh` | `tb_pengguna.id_pengguna` | `tb_tracer_alumni_disetujui_oleh_foreign` |
| `tb_tracer_alumni` | `diverifikasi_oleh` | `tb_pengguna.id_pengguna` | `tb_tracer_alumni_diverifikasi_oleh_foreign` |
| `tb_tracer_alumni` | `id_aktivitas` | `tb_aktivitas.id_aktivitas` | `tb_tracer_alumni_id_aktivitas_foreign` |
| `tb_tracer_alumni` | `id_alumni` | `tb_alumni.id_alumni` | `tb_tracer_alumni_id_alumni_foreign` |

## Relasi Utama

- `tb_peran` (1) -> (N) `tb_pengguna`
- `tb_pengguna` (1) -> (1) `tb_pelamar`
- `tb_pelamar` (1) -> (0..1) `tb_alumni`
- `tb_pelamar` (1) -> (N) `tb_berkas`
- `tb_pelamar` (1) -> (N) `tb_riwayat_kerja`
- `tb_alumni` (1) -> (0..1) `tb_tracer_alumni`
- `tb_pengguna` (1) -> (0..1) `tb_perusahaan`
- `tb_perusahaan` (1) -> (N) `tb_lowongan`
- `tb_lowongan` (1) -> (N) `tb_lamaran`
- `tb_lamaran` (1) -> (N) `tb_lamaran_status`
- `tb_lamaran` (1) -> (N) `tb_lamaran_berkas`

## Catatan Penting

- `tb_counter_pelamar` wajib ada karena generator `account_id` pelamar memakai tabel ini.
- `tb_jenis_berkas.scope_penggunaan` memisahkan dokumen profil, lamaran, dan keduanya.
- `tb_lamaran_berkas` menyimpan snapshot dokumen supaya histori lamaran tidak berubah saat dokumen profil diganti.
- `migrations` ikut didokumentasikan supaya status migration dan database tetap sinkron.
