# Tracer Study TP4

Aplikasi Tracer Study untuk mengelola data alumni, master sekolah, isian aktivitas setelah lulus, dan pelaporan tracer alumni.

Project ini dibangun dengan CodeIgniter 4 dan PHP 8.2.

## Fitur Utama

- Landing page publik untuk ringkasan tracer study.
- Autentikasi multi-role: superadmin, admin sekolah, dan alumni.
- Manajemen master data sekolah, kompetensi, angkatan, dan aktivitas alumni.
- Registrasi alumni dan pengisian tracer study mandiri.
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
   cd app-tracer-study-tp4
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
   database.default.database = db_tracerstudy
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
   ```

7. Jalankan aplikasi.

   ```bash
   php spark serve
   ```

   Buka `http://localhost:8080`.

## Akun Awal

Seeder `PenggunaSeeder` membuat akun superadmin awal:

- Email: `superadmin@tracerstudy.local`
- Password: `Admin123`

Ganti password ini setelah login pertama, terutama jika aplikasi akan dipakai di server online.

## Catatan Repository

- File `.env` tidak ikut di-commit karena berisi konfigurasi lokal dan kredensial.
- Folder `vendor/` tidak ikut di-commit; jalankan `composer install` setelah clone.
- Folder `public/uploads/` dipakai untuk file upload runtime dan tidak ikut di-commit.
- Dokumentasi struktur database tersedia di `database_table.md`.
