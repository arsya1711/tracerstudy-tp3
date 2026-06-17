# ERD Chen - Sistem Informasi Tracer Study

Dokumen ini adalah pendamping dari file `erd-chen-tracer-study.drawio`. ERD dibuat dengan bentuk Chen: entitas sebagai kotak, relasi sebagai belah ketupat, dan atribut sebagai oval.

## Tabel Yang Dimasukkan

| Tabel | Jenis | Keterangan |
| --- | --- | --- |
| `migrations` | Teknis | Riwayat migration CodeIgniter. |
| `tb_peran` | Master | Role pengguna. |
| `tb_pengguna` | Master/Akun | Data akun login pengguna. |
| `tb_alumni` | Master utama | Profil alumni. |
| `tb_angkatan` | Master | Tahun lulus alumni. |
| `tb_kompetensi` | Master | Kompetensi keahlian/jurusan. |
| `tb_aktivitas` | Master | Aktivitas utama setelah lulus. |
| `tb_tracer_alumni` | Transaksi | Data tracer yang diisi alumni. |
| `tb_pengajuan_legalisir` | Transaksi | Pengajuan legalisir dokumen alumni. |
| `tb_notifikasi` | Transaksi pendukung | Notifikasi sistem per pengguna. |

## Foreign Key

| Tabel | Foreign Key | Mengarah Ke |
| --- | --- | --- |
| `tb_pengguna` | `id_peran` | `tb_peran.id_peran` |
| `tb_alumni` | `id_pengguna` | `tb_pengguna.id_pengguna` |
| `tb_alumni` | `id_angkatan` | `tb_angkatan.id_angkatan` |
| `tb_alumni` | `id_kompetensi` | `tb_kompetensi.id_kompetensi` |
| `tb_alumni` | `diverifikasi_oleh` | `tb_pengguna.id_pengguna` |
| `tb_tracer_alumni` | `id_alumni` | `tb_alumni.id_alumni` |
| `tb_tracer_alumni` | `id_aktivitas` | `tb_aktivitas.id_aktivitas` |
| `tb_tracer_alumni` | `diverifikasi_oleh` | `tb_pengguna.id_pengguna` |
| `tb_tracer_alumni` | `disetujui_oleh` | `tb_pengguna.id_pengguna` |
| `tb_pengajuan_legalisir` | `id_alumni` | `tb_alumni.id_alumni` |
| `tb_pengajuan_legalisir` | `diproses_oleh` | `tb_pengguna.id_pengguna` |
| `tb_notifikasi` | `id_pengguna` | `tb_pengguna.id_pengguna` |

## Kardinalitas Utama

| Relasi | Kardinalitas | Makna |
| --- | --- | --- |
| `tb_peran` - `tb_pengguna` | 1 : M | Satu role dapat dimiliki banyak pengguna. |
| `tb_pengguna` - `tb_alumni` | 1 : 0..1 | Satu akun dapat memiliki satu profil alumni. |
| `tb_angkatan` - `tb_alumni` | 1 : M | Satu angkatan dapat dimiliki banyak alumni. |
| `tb_kompetensi` - `tb_alumni` | 1 : M | Satu kompetensi dapat dimiliki banyak alumni. |
| `tb_alumni` - `tb_tracer_alumni` | 1 : 0..1 | Satu alumni mengisi maksimal satu data tracer. |
| `tb_aktivitas` - `tb_tracer_alumni` | 1 : M | Satu aktivitas dapat dipilih banyak tracer alumni. |
| `tb_alumni` - `tb_pengajuan_legalisir` | 1 : M | Satu alumni dapat membuat banyak pengajuan legalisir. |
| `tb_pengguna` - `tb_pengajuan_legalisir` | 1 : M opsional | Satu admin dapat memproses banyak pengajuan legalisir. |
| `tb_pengguna` - `tb_notifikasi` | 1 : M | Satu pengguna dapat menerima banyak notifikasi. |
| `tb_pengguna` - `tb_alumni`/`tb_tracer_alumni` | 1 : M opsional | Satu admin dapat memverifikasi banyak data alumni atau tracer. |

## Catatan

- `migrations` dimasukkan karena permintaan ERD ini mencakup semua tabel, tetapi tabel tersebut tidak punya relasi bisnis.
- Atribut di diagram tidak memuat semua kolom timestamp agar diagram tetap terbaca.
- Semua atribut foreign key pada diagram diberi label `(FK)`.
