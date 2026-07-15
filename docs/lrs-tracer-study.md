# LRS - Sistem Informasi Tracer Study

LRS ini dibuat berdasarkan file `ERD_Revisi_Final_TracerStudy.drawio`. Atribut yang dicantumkan mengikuti atribut pada ERD final, yaitu atribut penting, primary key, foreign key, dan atribut inti yang menjelaskan relasi.

## Struktur Relasi

### 1. tb_peran

`tb_peran`(`id_peran`, `nama_peran`, `slug_peran`)

| Atribut | Keterangan |
| --- | --- |
| `id_peran` | Primary Key |
| `nama_peran` | Nama role pengguna |
| `slug_peran` | Kode/slug role |

### 2. tb_pengguna

`tb_pengguna`(`id_pengguna`, `id_peran`, `nama_lengkap`, `email`)

| Atribut | Keterangan |
| --- | --- |
| `id_pengguna` | Primary Key |
| `id_peran` | Foreign Key ke `tb_peran.id_peran` |
| `nama_lengkap` | Nama pengguna |
| `email` | Email akun login |

### 3. tb_notifikasi

`tb_notifikasi`(`id_notifikasi`, `id_pengguna`, `judul`)

| Atribut | Keterangan |
| --- | --- |
| `id_notifikasi` | Primary Key |
| `id_pengguna` | Foreign Key ke `tb_pengguna.id_pengguna` |
| `judul` | Judul notifikasi |

### 4. tb_alumni

`tb_alumni`(`id_alumni`, `id_pengguna`, `id_angkatan`, `id_kompetensi`, `nis`, `nisn`, `status_verifikasi`)

| Atribut | Keterangan |
| --- | --- |
| `id_alumni` | Primary Key |
| `id_pengguna` | Foreign Key ke `tb_pengguna.id_pengguna` |
| `id_angkatan` | Foreign Key ke `tb_angkatan.id_angkatan` |
| `id_kompetensi` | Foreign Key ke `tb_kompetensi.id_kompetensi` |
| `nis` | Nomor induk siswa |
| `nisn` | Nomor induk siswa nasional |
| `status_verifikasi` | Status verifikasi data alumni |

### 5. tb_angkatan

`tb_angkatan`(`id_angkatan`, `tahun_lulus`, `status_aktif`)

| Atribut | Keterangan |
| --- | --- |
| `id_angkatan` | Primary Key |
| `tahun_lulus` | Tahun kelulusan alumni |
| `status_aktif` | Status aktif data angkatan |

### 6. tb_kompetensi

`tb_kompetensi`(`id_kompetensi`, `nama_kompetensi`, `akronim`, `status_aktif`)

| Atribut | Keterangan |
| --- | --- |
| `id_kompetensi` | Primary Key |
| `nama_kompetensi` | Nama kompetensi keahlian/jurusan |
| `akronim` | Singkatan kompetensi |
| `status_aktif` | Status aktif data kompetensi |

### 7. tb_tracer_alumni

`tb_tracer_alumni`(`id_tracer`, `id_alumni`, `id_aktivitas`, `status`, `tanggal_pengisian`)

| Atribut | Keterangan |
| --- | --- |
| `id_tracer` | Primary Key |
| `id_alumni` | Foreign Key ke `tb_alumni.id_alumni` |
| `id_aktivitas` | Foreign Key ke `tb_aktivitas.id_aktivitas` |
| `status` | Status data tracer |
| `tanggal_pengisian` | Tanggal pengisian tracer |

### 8. tb_aktivitas

`tb_aktivitas`(`id_aktivitas`, `nama_aktivitas`, `keterangan`, `status_aktif`, `dibuat_pada`, `diperbarui_pada`)

| Atribut | Keterangan |
| --- | --- |
| `id_aktivitas` | Primary Key |
| `nama_aktivitas` | Nama aktivitas alumni setelah lulus |
| `keterangan` | Penjelasan opsional aktivitas |
| `status_aktif` | Status aktif aktivitas |
| `dibuat_pada` | Waktu data dibuat |
| `diperbarui_pada` | Waktu data terakhir diperbarui |

### 9. tb_pengajuan_legalisir

`tb_pengajuan_legalisir`(`id_pengajuan_legalisir`, `id_alumni`, `diproses_oleh`, `jenis_dokumen`, `status`)

| Atribut | Keterangan |
| --- | --- |
| `id_pengajuan_legalisir` | Primary Key |
| `id_alumni` | Foreign Key ke `tb_alumni.id_alumni` |
| `diproses_oleh` | Foreign Key ke `tb_pengguna.id_pengguna` |
| `jenis_dokumen` | Jenis dokumen yang diajukan |
| `status` | Status pengajuan legalisir |

## Relasi Antar Tabel

| Relasi | Kardinalitas | Keterangan |
| --- | --- | --- |
| `tb_peran` ke `tb_pengguna` | 1 : M | Satu peran dapat dimiliki banyak pengguna |
| `tb_pengguna` ke `tb_notifikasi` | 1 : M | Satu pengguna dapat menerima banyak notifikasi |
| `tb_pengguna` ke `tb_alumni` | 1 : 0..1 | Satu akun pengguna dapat memiliki satu profil alumni |
| `tb_pengguna` ke `tb_pengajuan_legalisir` | 1 : M | Satu pengguna/admin dapat memproses banyak pengajuan legalisir |
| `tb_angkatan` ke `tb_alumni` | 1 : M | Satu angkatan dapat dimiliki banyak alumni |
| `tb_kompetensi` ke `tb_alumni` | 1 : M | Satu kompetensi dapat dimiliki banyak alumni |
| `tb_alumni` ke `tb_tracer_alumni` | 1 : 0..1 | Satu alumni mengisi maksimal satu data tracer |
| `tb_aktivitas` ke `tb_tracer_alumni` | 1 : M | Satu aktivitas dapat dipilih banyak tracer alumni |
| `tb_alumni` ke `tb_pengajuan_legalisir` | 1 : M | Satu alumni dapat membuat banyak pengajuan legalisir |

## Catatan

- `tb_pengguna` berfungsi sebagai tabel akun login semua role.
- Detail alumni tetap disimpan di `tb_alumni`.
- LRS ini mengikuti struktur dan atribut yang terlihat pada ERD revisi final.
