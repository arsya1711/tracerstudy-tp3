<?php

namespace Config;

use CodeIgniter\Router\RouteCollection;

/*
|-------------------------------------------------------------------
| KONFIGURASI ROUTES
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: file ini mendaftarkan semua endpoint
| utama yang dipakai modul autentikasi dan dashboard.
| Alur kerja: ketika request masuk, CI4 mencari route yang cocok,
| lalu menjalankan controller terkait dan filter jika ada.
|
| Tips Debugging:
| - Jika route 404, periksa path dan method HTTP yang didaftarkan di file ini.
| - Jika filter auth tidak jalan, periksa alias auth di app/Config/Filters.php.
*/

/**
 * @var RouteCollection $routes
 */
$routes = Services::routes();

/*
|-------------------------------------------------------------------
| PENGATURAN DEFAULT ROUTE
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: mengatur namespace default, method
| bawaan, dan mematikan auto routing demi keamanan.
| Alur kerja: CI4 membaca pengaturan ini sebelum memetakan route
| request ke controller yang sesuai.
|
| Tips Debugging:
| - Jika controller tidak ditemukan, periksa namespace default dan nama class controller.
| - Jika route manual tidak jalan, pastikan auto route memang dimatikan dan path sudah didaftarkan.
*/
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Auth\LoginController');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

/*
|-------------------------------------------------------------------
| ROUTE HALAMAN UTAMA
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: menampilkan landing page publik sebagai
| halaman pertama aplikasi saat user membuka http://localhost:8080.
| Alur kerja: saat user membuka domain utama, Home::index() mengambil
| statistik ringkas dan lowongan terbaru untuk dirender di landing.
|
| Tips Debugging:
| - Jika halaman utama masih login, pastikan route / mengarah ke Home::index().
*/
$routes->get('/', 'Home::index');

/*
|-------------------------------------------------------------------
| ROUTE LOWONGAN PUBLIK
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: menyediakan etalase lowongan yang bisa
| dibuka tanpa login. Halaman ini hanya membaca lowongan aktif, lalu
| tombol melamar mengarahkan user ke login/pelamar.
| Alur kerja: GET /lowongan menampilkan daftar, sedangkan
| GET /lowongan/<slug> menampilkan detail lowongan publik.
|
| Tips Debugging:
| - Jika halaman publik 404, cek route ini berada di luar group auth.
| - Jika detail tidak ditemukan, cek slug_lowongan dan status aktif.
*/
$routes->get('lowongan', 'Publik\LowonganController::index');
$routes->get('lowongan/(:segment)', 'Publik\LowonganController::detail/$1');

/*
|-------------------------------------------------------------------
| ROUTE AUTENTIKASI
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: menyediakan endpoint login dan logout
| untuk modul autentikasi.
| Alur kerja: GET /login menampilkan form, POST /login memproses
| autentikasi, dan GET /logout menghapus session login.
|
| Tips Debugging:
| - Jika form submit 404, periksa action form mengarah ke /login dengan method POST.
| - Jika logout tidak bekerja, periksa route GET /logout dan method logout controller.
*/
$routes->get('login', 'Auth\LoginController::index');
$routes->post('login', 'Auth\LoginController::authenticate');
$routes->get('daftar', 'Auth\RegisterController::index');
$routes->post('daftar', 'Auth\RegisterController::store');
$routes->get('logout', 'Auth\LoginController::logout');

/*
|-------------------------------------------------------------------
| ROUTE DASHBOARD TERLINDUNGI
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: mendaftarkan route dashboard yang
| hanya boleh diakses setelah login.
| Alur kerja: setiap request ke group dashboard akan melewati filter
| auth lebih dulu sebelum controller dashboard dijalankan.
|
| Tips Debugging:
| - Jika selalu dilempar ke /login, periksa session pengguna_login.
| - Jika dashboard superadmin 404, periksa controller Superadmin\DashboardController.
*/
$routes->group('dashboard', ['filter' => 'auth'], static function ($routes) {
    $routes->get('superadmin', 'Superadmin\DashboardController::index', ['filter' => 'auth:superadmin']);
    $routes->get('super-admin', 'Superadmin\DashboardController::index', ['filter' => 'auth:superadmin']);
    $routes->get('admin-sekolah', 'AdminSekolah\DashboardController::index', ['filter' => 'auth:admin_sekolah']);
    $routes->get('admin-perusahaan', 'AdminDudi\DashboardController::index', ['filter' => 'auth:admin_dudi,admin_perusahaan']);
    $routes->get('pelamar-umum', 'Pelamar\DashboardController::index', ['filter' => 'auth:pelamar_umum']);
    $routes->get('pelamar-alumni', 'Pelamar\DashboardController::index', ['filter' => 'auth:pelamar_alumni']);
});

/*
|-------------------------------------------------------------------
| ROUTE MODUL ADMIN SEKOLAH / BKK
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: mendaftarkan area kerja Admin Sekolah
| untuk operasional BKK, mulai dari dashboard, master sekolah,
| pelamar, admin, DUDI, lowongan, sampai monitoring lamaran.
| Alur kerja: semua route admin-sekolah melewati filter auth, lalu
| controller memastikan session role adalah admin_sekolah. Beberapa
| modul memakai ulang controller Super Admin agar aturan CRUD tidak
| digandakan, namun route status utama lamaran sengaja tidak dibuat
| agar Admin Sekolah hanya memantau dan meninjau dokumen.
|
| Tips Debugging:
| - Jika Admin Sekolah tidak bisa membuka modul, cek slug_peran pada
|   session dan pastikan route admin-sekolah/<modul> sudah tersedia.
| - Jika tombol ubah status lamaran muncul untuk Admin Sekolah, cek
|   variabel bolehUbahStatus pada LamaranController.
*/
$routes->group('admin-sekolah', ['filter' => 'auth:admin_sekolah'], static function ($routes) {
    $routes->get('dashboard', 'AdminSekolah\DashboardController::index');
    $routes->get('kompetensi', 'AdminSekolah\KompetensiController::index');
    $routes->post('kompetensi/simpan', 'AdminSekolah\KompetensiController::simpan');
    $routes->post('kompetensi/update/(:num)', 'AdminSekolah\KompetensiController::update/$1');
    $routes->get('kompetensi/hapus/(:num)', 'AdminSekolah\KompetensiController::hapus/$1');
    $routes->get('angkatan', 'AdminSekolah\AngkatanController::index');
    $routes->post('angkatan/simpan', 'AdminSekolah\AngkatanController::simpan');
    $routes->post('angkatan/update/(:num)', 'AdminSekolah\AngkatanController::update/$1');
    $routes->get('angkatan/hapus/(:num)', 'AdminSekolah\AngkatanController::hapus/$1');
    $routes->get('aktivitas', 'AdminSekolah\AktivitasController::index');
    $routes->post('aktivitas/simpan', 'AdminSekolah\AktivitasController::simpan');
    $routes->post('aktivitas/update/(:num)', 'AdminSekolah\AktivitasController::update/$1');
    $routes->get('aktivitas/hapus/(:num)', 'AdminSekolah\AktivitasController::hapus/$1');
    $routes->get('tracer', 'AdminSekolah\TracerController::index');
    $routes->match(['GET', 'POST'], 'pelamar', 'Superadmin\PelamarController::index');
    $routes->post('pelamar/simpan', 'Superadmin\PelamarController::simpan');
    $routes->post('pelamar/update/(:num)', 'Superadmin\PelamarController::update/$1');
    $routes->get('pelamar/hapus/(:num)', 'Superadmin\PelamarController::hapus/$1');
    $routes->post('pelamar/hapus-massal', 'Superadmin\PelamarController::hapusMassal');
    $routes->get('pelamar/aktivasi/(:num)', 'Superadmin\PelamarController::aktivasi/$1');
    $routes->get('pelamar/detail/(:num)', 'Superadmin\PelamarController::detail/$1');
    $routes->post('pelamar/simpan-riwayat-kerja', 'Superadmin\PelamarController::simpanRiwayatKerja');
    $routes->post('pelamar/update-riwayat-kerja/(:num)', 'Superadmin\PelamarController::updateRiwayatKerja/$1');
    $routes->get('pelamar/hapus-riwayat-kerja/(:num)', 'Superadmin\PelamarController::hapusRiwayatKerja/$1');
    $routes->post('pelamar/upload-berkas', 'Superadmin\PelamarController::uploadBerkas');
    $routes->get('pelamar/hapus-berkas/(:num)', 'Superadmin\PelamarController::hapusBerkas/$1');
    $routes->post('pelamar/update-email', 'Superadmin\PelamarController::updateEmail');
    $routes->post('pelamar/update-password', 'Superadmin\PelamarController::updatePassword');
    $routes->post('pelamar/simpan-tracer', 'Superadmin\PelamarController::simpanTracer');
    $routes->match(['GET', 'POST'], 'admin', 'Superadmin\AdminController::index');
    $routes->post('admin/simpan', 'Superadmin\AdminController::simpan');
    $routes->post('admin/update/(:num)', 'Superadmin\AdminController::update/$1');
    $routes->get('admin/hapus/(:num)', 'Superadmin\AdminController::hapus/$1');
    $routes->post('admin/hapus-massal', 'Superadmin\AdminController::hapusMassal');
    $routes->get('admin/aktivasi/(:num)', 'Superadmin\AdminController::aktivasi/$1');
    $routes->match(['GET', 'POST'], 'perusahaan', 'Superadmin\PerusahaanController::index');
    $routes->post('perusahaan/simpan', 'Superadmin\PerusahaanController::simpan');
    $routes->post('perusahaan/update/(:num)', 'Superadmin\PerusahaanController::update/$1');
    $routes->get('perusahaan/hapus/(:num)', 'Superadmin\PerusahaanController::hapus/$1');
    $routes->post('perusahaan/hapus-massal', 'Superadmin\PerusahaanController::hapusMassal');
    $routes->match(['GET', 'POST'], 'lowongan', 'Superadmin\LowonganController::index');
    $routes->post('lowongan/simpan', 'Superadmin\LowonganController::simpan');
    $routes->post('lowongan/update/(:num)', 'Superadmin\LowonganController::update/$1');
    $routes->get('lowongan/hapus/(:num)', 'Superadmin\LowonganController::hapus/$1');
    $routes->post('lowongan/hapus-massal', 'Superadmin\LowonganController::hapusMassal');
    $routes->get('lamaran', 'Superadmin\LamaranController::index');
    $routes->post('lamaran/update-review/(:num)', 'Superadmin\LamaranController::updateReviewDokumen/$1');
});

/*
|-------------------------------------------------------------------
| ROUTE MODUL ADMIN DUDI / HRD
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: mendaftarkan meja kerja perusahaan
| untuk melihat lowongan sendiri, memeriksa lamaran masuk, memberi
| review dokumen, dan mengubah status proses rekrutmen.
| Alur kerja: semua route admin-dudi melewati filter auth, lalu
| controller memastikan lagi akun hanya mengakses perusahaan yang
| terhubung melalui tb_perusahaan.id_pengguna.
|
| Tips Debugging:
| - Jika setelah login admin_dudi 404, cek route admin-dudi/dashboard.
| - Jika akses perusahaan salah, cek relasi tb_perusahaan.id_pengguna.
*/
$routes->group('admin-dudi', ['filter' => 'auth:admin_dudi,admin_perusahaan'], static function ($routes) {
    $routes->get('dashboard', 'AdminDudi\DashboardController::index');
    $routes->get('lowongan', 'AdminDudi\LowonganController::index');
    $routes->post('lowongan/simpan', 'AdminDudi\LowonganController::simpan');
    $routes->post('lowongan/update/(:num)', 'AdminDudi\LowonganController::update/$1');
    $routes->get('lamaran', 'AdminDudi\LamaranController::index');
    $routes->post('lamaran/update-status/(:num)', 'AdminDudi\LamaranController::updateStatus/$1');
    $routes->post('lamaran/update-review/(:num)', 'AdminDudi\LamaranController::updateReviewDokumen/$1');
});

/*
|-------------------------------------------------------------------
| ROUTE SUPERADMIN MASTER DATA
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: mendaftarkan seluruh endpoint modul
| master data milik Super Admin seperti Kompetensi Keahlian,
| Angkatan, Aktivitas, dan Kerjasama, baik halaman utama maupun
| endpoint AJAX.
| Alur kerja: request yang masuk ke prefix superadmin akan lebih dulu
| melewati filter auth, lalu diteruskan ke controller terkait.
|
| Tips Debugging:
| - Jika endpoint AJAX 404, cek URL superadmin/<modul> pada JavaScript tiap halaman.
| - Jika halaman bisa dibuka tanpa login, cek group ini tetap memakai filter auth.
*/
$routes->group('superadmin', ['filter' => 'auth:superadmin'], static function ($routes) {
    $routes->get('kompetensi', 'Superadmin\KompetensiController::index');
    $routes->post('kompetensi/simpan', 'Superadmin\KompetensiController::simpan');
    $routes->post('kompetensi/update/(:num)', 'Superadmin\KompetensiController::update/$1');
    $routes->get('kompetensi/hapus/(:num)', 'Superadmin\KompetensiController::hapus/$1');
    $routes->get('angkatan', 'Superadmin\AngkatanController::index');
    $routes->post('angkatan/simpan', 'Superadmin\AngkatanController::simpan');
    $routes->post('angkatan/update/(:num)', 'Superadmin\AngkatanController::update/$1');
    $routes->get('angkatan/hapus/(:num)', 'Superadmin\AngkatanController::hapus/$1');
    $routes->get('aktivitas', 'Superadmin\AktivitasController::index');
    $routes->post('aktivitas/simpan', 'Superadmin\AktivitasController::simpan');
    $routes->post('aktivitas/update/(:num)', 'Superadmin\AktivitasController::update/$1');
    $routes->get('aktivitas/hapus/(:num)', 'Superadmin\AktivitasController::hapus/$1');
    $routes->get('tracer', 'Superadmin\TracerController::index');
    $routes->get('kerjasama', 'Superadmin\KerjasamaController::index');
    $routes->post('kerjasama/simpan', 'Superadmin\KerjasamaController::simpan');
    $routes->post('kerjasama/update/(:num)', 'Superadmin\KerjasamaController::update/$1');
    $routes->get('kerjasama/hapus/(:num)', 'Superadmin\KerjasamaController::hapus/$1');
    $routes->match(['GET', 'POST'], 'perusahaan', 'Superadmin\PerusahaanController::index');
    $routes->post('perusahaan/simpan', 'Superadmin\PerusahaanController::simpan');
    $routes->post('perusahaan/update/(:num)', 'Superadmin\PerusahaanController::update/$1');
    $routes->get('perusahaan/hapus/(:num)', 'Superadmin\PerusahaanController::hapus/$1');
    $routes->post('perusahaan/hapus-massal', 'Superadmin\PerusahaanController::hapusMassal');
    $routes->match(['GET', 'POST'], 'lowongan', 'Superadmin\LowonganController::index');
    $routes->post('lowongan/simpan', 'Superadmin\LowonganController::simpan');
    $routes->post('lowongan/update/(:num)', 'Superadmin\LowonganController::update/$1');
    $routes->get('lowongan/hapus/(:num)', 'Superadmin\LowonganController::hapus/$1');
    $routes->post('lowongan/hapus-massal', 'Superadmin\LowonganController::hapusMassal');
    $routes->match(['GET', 'POST'], 'pelamar', 'Superadmin\PelamarController::index');
    $routes->post('pelamar/simpan', 'Superadmin\PelamarController::simpan');
    $routes->post('pelamar/update/(:num)', 'Superadmin\PelamarController::update/$1');
    $routes->get('pelamar/hapus/(:num)', 'Superadmin\PelamarController::hapus/$1');
    $routes->post('pelamar/hapus-massal', 'Superadmin\PelamarController::hapusMassal');
    $routes->get('pelamar/aktivasi/(:num)', 'Superadmin\PelamarController::aktivasi/$1');
    $routes->get('pelamar/detail/(:num)', 'Superadmin\PelamarController::detail/$1');
    $routes->get('lamaran', 'Superadmin\LamaranController::index');
    $routes->post('lamaran/update-status/(:num)', 'Superadmin\LamaranController::updateStatus/$1');
    $routes->post('lamaran/update-review/(:num)', 'Superadmin\LamaranController::updateReviewDokumen/$1');
    $routes->post('pelamar/simpan-riwayat-kerja', 'Superadmin\PelamarController::simpanRiwayatKerja');
    $routes->post('pelamar/update-riwayat-kerja/(:num)', 'Superadmin\PelamarController::updateRiwayatKerja/$1');
    $routes->get('pelamar/hapus-riwayat-kerja/(:num)', 'Superadmin\PelamarController::hapusRiwayatKerja/$1');
    $routes->post('pelamar/upload-berkas', 'Superadmin\PelamarController::uploadBerkas');
    $routes->get('pelamar/hapus-berkas/(:num)', 'Superadmin\PelamarController::hapusBerkas/$1');
    $routes->post('pelamar/update-email', 'Superadmin\PelamarController::updateEmail');
    $routes->post('pelamar/update-password', 'Superadmin\PelamarController::updatePassword');
    $routes->post('pelamar/simpan-tracer', 'Superadmin\PelamarController::simpanTracer');
    $routes->match(['GET', 'POST'], 'admin', 'Superadmin\AdminController::index');
    $routes->post('admin/simpan', 'Superadmin\AdminController::simpan');
    $routes->post('admin/update/(:num)', 'Superadmin\AdminController::update/$1');
    $routes->get('admin/hapus/(:num)', 'Superadmin\AdminController::hapus/$1');
    $routes->post('admin/hapus-massal', 'Superadmin\AdminController::hapusMassal');
    $routes->get('admin/aktivasi/(:num)', 'Superadmin\AdminController::aktivasi/$1');
});

/*
|-------------------------------------------------------------------
| ROUTE MODUL PELAMAR
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: mendaftarkan dashboard dan profil
| mandiri untuk akun pelamar umum maupun alumni.
| Alur kerja: setelah login, pelamar diarahkan ke /pelamar/dashboard,
| lalu dapat membuka profil, daftar lowongan, mengunggah berkas
| profil, dan mengirim lamaran ke lowongan aktif.
|
| Tips Debugging:
| - Jika setelah login pelamar tetap 404, cek redirect URL pada
|   LoginController::getRedirectUrl().
*/
$routes->group('pelamar', ['filter' => 'auth:pelamar_umum,pelamar_alumni'], static function ($routes) {
    $routes->get('dashboard', 'Pelamar\DashboardController::index');
    $routes->get('profil', 'Pelamar\DashboardController::profil');
    $routes->post('profil/update/(:num)', 'Pelamar\DashboardController::updateDetail/$1');
    $routes->post('profil/upload-berkas', 'Pelamar\DashboardController::uploadBerkasProfil');
    $routes->post('profil/hapus-berkas/(:num)', 'Pelamar\DashboardController::hapusBerkasProfil/$1');
    $routes->post('profil/simpan-riwayat-kerja', 'Pelamar\DashboardController::simpanRiwayatKerja');
    $routes->post('profil/update-riwayat-kerja/(:num)', 'Pelamar\DashboardController::updateRiwayatKerja/$1');
    $routes->post('profil/hapus-riwayat-kerja/(:num)', 'Pelamar\DashboardController::hapusRiwayatKerja/$1');
    $routes->post('profil/update-email', 'Pelamar\DashboardController::updateEmail');
    $routes->post('profil/update-password', 'Pelamar\DashboardController::updatePassword');
    $routes->post('profil/simpan-tracer', 'Pelamar\DashboardController::simpanTracer');
    $routes->get('lamaran', 'Pelamar\LamaranController::index');
    $routes->get('lamaran/(:num)', 'Pelamar\LamaranController::detail/$1');
    $routes->post('lamaran/upload-ulang/(:num)', 'Pelamar\LamaranController::uploadUlangDokumen/$1');
    $routes->get('lowongan', 'Pelamar\LowonganController::index');
    $routes->get('lowongan/(:segment)', 'Pelamar\LowonganController::detail/$1');
    $routes->post('lowongan/lamar/(:num)', 'Pelamar\LowonganController::lamar/$1');
});
