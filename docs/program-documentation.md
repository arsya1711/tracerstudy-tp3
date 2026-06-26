# Dokumentasi Program - Sistem Informasi Tracer Study

Dokumentasi ini menjelaskan modul utama aplikasi, alur data, dan file penting yang perlu diperhatikan saat pengembangan.

## Struktur Umum

- `app/Controllers`: mengatur alur request, validasi, proses simpan, dan pengambilan data untuk view.
- `app/Models`: membungkus akses tabel database agar query tidak tersebar di banyak controller.
- `app/Views`: tampilan halaman dashboard, alumni, admin, legalisir, tracer, dan layout.
- `app/Config/Routes.php`: pendaftaran URL aplikasi.
- `docs`: dokumentasi diagram ERD, LRS, sequence diagram, dan catatan program.

## Role Pengguna

- `superadmin`: mengelola master data, tracer alumni, admin, legalisir, laporan, dan export.
- `admin_sekolah`: mengelola data sekolah seperti tracer, legalisir, angkatan, kompetensi, dan aktivitas.
- `alumni`: mengisi profil, tracer study, dan pengajuan legalisir.

## Modul Tracer

File utama:

- `app/Controllers/Superadmin/TracerController.php`
- `app/Controllers/AdminSekolah/TracerController.php`
- `app/Views/superadmin/tracer/index.php`
- `app/Views/alumni/tracer/index.php`

Alur:

1. Alumni mengisi tracer melalui menu `Alumni > Tracer`.
2. Admin melihat data tracer melalui menu `Tracer Alumni`.
3. Data tracer dapat difilter, dicetak, diexport Excel, dan diexport PDF.
4. Export Excel memakai format HTML `.xls` agar Excel langsung menampilkan tabel rapi.
5. Export PDF dibuat langsung dari controller tanpa dependency tambahan.

## Modul Legalisir

File utama:

- `app/Controllers/Alumni/LegalisirController.php`
- `app/Controllers/Superadmin/LegalisirController.php`
- `app/Controllers/AdminSekolah/LegalisirController.php`
- `app/Models/PengajuanLegalisirModel.php`
- `app/Views/alumni/legalisir/index.php`
- `app/Views/superadmin/legalisir/index.php`

Alur:

1. Alumni mengajukan legalisir setelah profil dan tracer lengkap.
2. Sistem menyimpan pengajuan dengan status awal `diajukan`.
3. Sistem mengirim notifikasi ke superadmin dan admin sekolah.
4. Admin mengubah status menjadi `diproses`, `selesai`, atau `ditolak`.
5. Catatan admin ikut ditampilkan di halaman legalisir alumni dan notifikasi alumni.

## Notifikasi Legalisir

Notifikasi visual ditampilkan di:

- Dashboard admin/superadmin melalui card `Pengajuan Legalisir`.
- Sidebar menu `Legalisir` melalui badge angka.
- Dashboard alumni melalui alert status pengajuan terbaru.
- Halaman legalisir alumni melalui ringkasan status dan catatan admin.

Status yang dianggap perlu perhatian:

- Admin/superadmin: `diajukan`.
- Alumni: `diajukan`, `diproses`, dan `ditolak`.

## Dashboard

File utama:

- `app/Controllers/Superadmin/DashboardController.php`
- `app/Controllers/AdminSekolah/DashboardController.php`
- `app/Controllers/Alumni/DashboardController.php`
- `app/Views/dashboard/super-admin/index.php`
- `app/Views/admin_sekolah/dashboard.php`
- `app/Views/alumni/dashboard.php`

Dashboard admin menampilkan ringkasan tracer, alumni, dan legalisir. Dashboard alumni menampilkan status akun, kelengkapan data, profil kuliah, dan status pengajuan legalisir terbaru.

## Sidebar

File utama:

- `app/Views/partials/sidebar.php`

Sidebar menentukan menu berdasarkan role login. Badge legalisir dihitung langsung di partial karena sidebar dipakai lintas role.

## Catatan Pengembangan

- Gunakan model untuk query yang dipakai ulang, misalnya `PengajuanLegalisirModel`.
- Tambahkan komentar hanya pada bagian yang menjelaskan alur bisnis atau logika yang tidak langsung terlihat.
- Hindari komentar per baris yang hanya mengulang nama variabel atau fungsi.
- Setelah mengubah controller/view, jalankan `php -l` pada file terkait.
