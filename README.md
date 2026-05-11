# BKK & Tracer Study TP4

Aplikasi Bursa Kerja Khusus dan Tracer Study untuk mengelola data pelamar, alumni, perusahaan/DUDI, lowongan kerja, lamaran, serta pelaporan tracer alumni.

Project ini dibangun dengan CodeIgniter 4 dan PHP 8.2.

## Fitur Utama

- Landing page publik untuk menampilkan lowongan aktif.
- Autentikasi multi-role: superadmin, admin sekolah/BKK, admin DUDI, pelamar umum, dan pelamar alumni.
- Manajemen master data sekolah, kompetensi, angkatan, aktivitas alumni, dan kerja sama DUDI.
- Manajemen pelamar, berkas profil, riwayat kerja, lowongan, lamaran, dan status proses lamaran.
- Modul tracer study alumni dengan rekap dan data analisis.

## Kebutuhan Sistem

- PHP 8.2 atau lebih baru
- Composer
- MySQL atau MariaDB
- Ekstensi PHP yang umum dipakai CodeIgniter 4: `intl`, `mbstring`, `json`, `mysqlnd`, dan `curl`

## Instalasi Lokal

1. Clone repository.

   ```bash
   git clone <url-repository>
   cd app-tracer-bkk-tp4
   ```

2. Install dependency PHP.

   ```bash
   composer install
   ```

3. Salin file environment.

   ```bash
   cp env .env
   ```

4. Atur konfigurasi `.env`.

   ```ini
   CI_ENVIRONMENT = development
   app.baseURL = 'http://localhost:8080/'

   database.default.hostname = localhost
   database.default.database = db_bkk_tracerstudy
   database.default.username = root
   database.default.password =
   database.default.DBDriver = MySQLi
   database.default.port = 3306
   ```

5. Buat database sesuai nama pada `.env`, lalu jalankan migrasi.

   ```bash
   php spark migrate
   ```

6. Jalankan seeder awal sesuai kebutuhan.

   ```bash
   php spark db:seed PeranSeeder
   php spark db:seed PenggunaSeeder
   php spark db:seed KompetensiSeeder
   php spark db:seed AngkatanSeeder
   php spark db:seed AktivitasSeeder
   php spark db:seed KerjasamaSeeder
   php spark db:seed JenisBerkasSeeder
   ```

7. Jalankan aplikasi.

   ```bash
   php spark serve
   ```

   Buka `http://localhost:8080`.

## Akun Awal

Seeder `PenggunaSeeder` membuat akun superadmin awal:

- Email: `superadmin@bkk.com`
- Password: `Admin123`

Ganti password ini setelah login pertama, terutama jika aplikasi akan dipakai di server online.

## Catatan Repository

- File `.env` tidak ikut di-commit karena berisi konfigurasi lokal dan kredensial.
- Folder `vendor/` tidak ikut di-commit; jalankan `composer install` setelah clone.
- Folder `public/uploads/` dipakai untuk file upload runtime dan tidak ikut di-commit.
- Dokumentasi struktur database tersedia di `database_table.md`.
