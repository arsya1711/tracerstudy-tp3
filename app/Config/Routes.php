<?php

namespace Config;

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes = Services::routes();

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

$routes->get('/', 'Home::index');

$routes->get('login', 'Auth\LoginController::index');
$routes->post('login', 'Auth\LoginController::authenticate');
$routes->get('daftar', 'Auth\RegisterController::index');
$routes->post('daftar', 'Auth\RegisterController::store');
$routes->get('logout', 'Auth\LoginController::logout');

$routes->get('notifikasi/buka/(:num)', 'NotifikasiController::buka/$1', ['filter' => 'auth']);
$routes->get('alumni', 'Alumni\DashboardController::index', ['filter' => 'auth:alumni']);

$routes->group('dashboard', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('superadmin', 'Superadmin\DashboardController::index', ['filter' => 'auth:superadmin']);
    $routes->get('super-admin', 'Superadmin\DashboardController::index', ['filter' => 'auth:superadmin']);
    $routes->get('admin-sekolah', 'AdminSekolah\DashboardController::index', ['filter' => 'auth:admin_sekolah']);
    $routes->get('alumni', 'Alumni\DashboardController::index', ['filter' => 'auth:alumni']);
});

$routes->group('admin-sekolah', ['filter' => 'auth:admin_sekolah'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'AdminSekolah\DashboardController::index');
    $routes->get('legalisir', 'AdminSekolah\LegalisirController::index');
    $routes->post('legalisir/update-status/(:num)', 'AdminSekolah\LegalisirController::updateStatus/$1');
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
    $routes->get('tracer/export', 'AdminSekolah\TracerController::export');
    $routes->get('tracer/export-pdf', 'AdminSekolah\TracerController::exportPdf');
    $routes->post('tracer/update/(:num)', 'AdminSekolah\TracerController::update/$1');
    $routes->post('tracer/hapus-tracer/(:num)', 'AdminSekolah\TracerController::hapusTracer/$1');
    $routes->post('tracer/hapus-alumni/(:num)', 'AdminSekolah\TracerController::hapusAlumni/$1');
});

$routes->group('superadmin', ['filter' => 'auth:superadmin'], static function (RouteCollection $routes) {
    $routes->get('legalisir', 'Superadmin\LegalisirController::index');
    $routes->post('legalisir/update-status/(:num)', 'Superadmin\LegalisirController::updateStatus/$1');
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
    $routes->get('tracer/export', 'Superadmin\TracerController::export');
    $routes->get('tracer/export-pdf', 'Superadmin\TracerController::exportPdf');
    $routes->post('tracer/update/(:num)', 'Superadmin\TracerController::update/$1');
    $routes->post('tracer/hapus-tracer/(:num)', 'Superadmin\TracerController::hapusTracer/$1');
    $routes->post('tracer/hapus-alumni/(:num)', 'Superadmin\TracerController::hapusAlumni/$1');
    $routes->match(['GET', 'POST'], 'admin', 'Superadmin\AdminController::index');
    $routes->post('admin/simpan', 'Superadmin\AdminController::simpan');
    $routes->post('admin/update/(:num)', 'Superadmin\AdminController::update/$1');
    $routes->get('admin/hapus/(:num)', 'Superadmin\AdminController::hapus/$1');
    $routes->post('admin/hapus-massal', 'Superadmin\AdminController::hapusMassal');
    $routes->get('admin/aktivasi/(:num)', 'Superadmin\AdminController::aktivasi/$1');
});

$routes->group('alumni', ['filter' => 'auth:alumni'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Alumni\DashboardController::index');
    $routes->get('profil', 'Alumni\DashboardController::profil');
    $routes->get('tracer', 'Alumni\DashboardController::tracer');
    $routes->get('legalisir', 'Alumni\LegalisirController::index');
    $routes->post('legalisir/simpan', 'Alumni\LegalisirController::simpan');
    $routes->post('profil/update/(:num)', 'Alumni\DashboardController::updateDetail/$1');
    $routes->post('profil/update-email', 'Alumni\DashboardController::updateEmail');
    $routes->post('profil/update-password', 'Alumni\DashboardController::updatePassword');
    $routes->post('tracer/simpan', 'Alumni\DashboardController::simpanTracer');
    $routes->post('tracer/hapus', 'Alumni\DashboardController::hapusTracer');
    $routes->post('profil/simpan-tracer', 'Alumni\DashboardController::simpanTracer');
});
