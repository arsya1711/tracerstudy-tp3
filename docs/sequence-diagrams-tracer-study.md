# Sequence Diagram - Sistem Informasi Tracer Study

Dokumen ini adalah ringkasan dari file `sequence-diagrams-tracer-study.drawio`.

## Diagram Yang Dibuat

| No | Diagram | File/Page |
| --- | --- | --- |
| 1 | Sequence Diagram Login | `Sequence Login` |
| 2 | Sequence Diagram Pengisian Tracer Study | `Sequence Pengisian Tracer` |
| 3 | Sequence Diagram Pengajuan Legalisir | `Sequence Pengajuan Legalisir` |
| 4 | Sequence Diagram Registrasi Alumni | `Sequence Registrasi Alumni` |
| 5 | Sequence Diagram Laporan/Export | `Sequence Laporan Export` |

## Alur Singkat

### Login

Pengguna membuka halaman login, mengisi email dan password, lalu `LoginController` memvalidasi akun melalui `PenggunaModel` dan database. Jika valid, sistem membuat session dan mengarahkan pengguna ke dashboard sesuai role.

### Pengisian Tracer Study

Alumni membuka halaman tracer, sistem mengambil profil alumni, data tracer lama, dan daftar aktivitas. Alumni mengisi form kuliah serta aktivitas utama, lalu sistem menyimpan atau memperbarui `tb_tracer_alumni`.

### Pengajuan Legalisir

Alumni membuka halaman legalisir, mengisi jenis dokumen, jumlah, dan keperluan. `LegalisirController` memvalidasi data, menyimpan pengajuan ke `tb_pengajuan_legalisir`, lalu mengirim notifikasi ke admin.

### Registrasi Alumni

Calon alumni membuka halaman daftar, mengisi data akun, akademik, dan data pribadi. Sistem membuat akun di `tb_pengguna`, profil di `tb_alumni`, lalu mengirim notifikasi pendaftaran baru ke admin.

### Laporan/Export

Admin membuka laporan tracer, sistem mengambil data sesuai filter dan menampilkan tabel/grafik. Saat admin memilih export, sistem mengambil ulang data berdasarkan filter lalu mengirim file Excel `.xls` atau PDF sebagai attachment.
