# Dokumentasi Tabel Database

Dokumen ini mencatat arah struktur database setelah aplikasi difokuskan ke Tracer Study.

## Tabel Inti

| Tabel | Fungsi |
| --- | --- |
| `migrations` | Riwayat migration CodeIgniter. |
| `tb_peran` | Role pengguna: superadmin, admin sekolah, alumni. |
| `tb_pengguna` | Akun login pengguna. |
| `tb_angkatan` | Master tahun lulus alumni. |
| `tb_kompetensi` | Master kompetensi keahlian/jurusan. |
| `tb_aktivitas` | Master aktivitas alumni setelah lulus. |
| `tb_alumni` | Profil alumni dan data akademik, langsung terhubung ke `tb_pengguna`. |
| `tb_tracer_alumni` | Isian tracer study alumni. |
| `tb_notifikasi` | Notifikasi sistem untuk pengguna. |

## Catatan Perubahan

- Modul rekrutmen sudah dipisahkan dari aplikasi dan tabel-tabel lamanya dihapus oleh migration `2026-06-03-000030_PurgeBkkModule`.
- `tb_alumni` langsung terhubung ke akun pengguna. Relasi utama alumni sekarang adalah `tb_alumni.id_pengguna -> tb_pengguna.id_pengguna`.
- Kolom tempat kerja tracer memakai nama `nama_instansi`, `bidang_instansi`, dan `alamat_instansi`.
