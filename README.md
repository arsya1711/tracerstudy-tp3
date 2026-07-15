# Sistem Informasi Tracer Study SMK Teratai Putih Global 3 Bekasi

Implementasi Sistem Informasi Tracer Study Berbasis Web untuk Pemetaan Karier Lulusan pada SMK Teratai Putih Global 3 Bekasi.

Aplikasi ini mengelola data alumni, master sekolah, isian aktivitas setelah lulus, pengajuan legalisir, dan pelaporan tracer alumni.

Project ini dibangun dengan CodeIgniter 4 dan PHP 8.2.

## Fitur Utama

- Landing page publik untuk ringkasan tracer study.
- Autentikasi multi-role: superadmin, admin sekolah, dan alumni.
- Manajemen master data sekolah, kompetensi, angkatan, dan aktivitas alumni.
- Registrasi alumni dan pengisian tracer study mandiri.
- Modul tracer study alumni dengan rekap dan data analisis.
- Modul pengajuan legalisir dokumen alumni.

## Batasan Fitur Skripsi

- Fitur lowongan kerja, lamaran, perusahaan, dan rekrutmen BKK tidak dijadikan fitur utama skripsi ini.
- Modul tersebut sudah dipisahkan dari aplikasi agar fokus sistem tetap pada tracer study, pemetaan karier lulusan, data alumni, legalisir, dan pelaporan.

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

Pada database development yang masih kosong, seeder `PenggunaSeeder` membuat dua akun admin awal:

- Email: `superadmin@tracerstudy.local`
- Password: `Admin123`
- Email: `adminsekolah@tracerstudy.local`
- Password: `AdminSekolah123`

Password di atas hanya fallback untuk development lokal. Seeder bersifat idempoten: akun yang sudah ada tidak akan diubah password, email, status aktif, nama, maupun perannya.

Pada environment production, akun baru hanya dapat dibuat jika password bootstrap disediakan melalui environment:

```ini
seed.superadminPassword = 'gunakan-password-kuat'
seed.adminSekolahPassword = 'gunakan-password-kuat-lain'
```

Ganti password bootstrap setelah login pertama dan jangan commit nilainya ke repository.

## Catatan Repository

- File `.env` tidak ikut di-commit karena berisi konfigurasi lokal dan kredensial.
- Folder `vendor/` tidak ikut di-commit; jalankan `composer install` setelah clone.
- Folder `public/uploads/` dipakai untuk file upload runtime dan tidak ikut di-commit.
- Dokumentasi struktur database tersedia di `database_table.md`.
