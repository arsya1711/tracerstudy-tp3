<?php

use App\Filters\AuthFilter;
use App\Filters\SecurityHeadersFilter;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Test\CIUnitTestCase;

final class SecurityRegressionTest extends CIUnitTestCase
{
    public function testGuestIsRedirectedByAuthorizationFilter(): void
    {
        session()->remove('pengguna_login');
        $result = (new AuthFilter())->before(service('request'));

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame(site_url('login'), $result->getHeaderLine('Location'));
    }

    public function testAllStateChangingRoutesUsePost(): void
    {
        $routes = file_get_contents(APPPATH . 'Config/Routes.php');
        $this->assertIsString($routes);

        foreach (['logout', 'notifikasi/buka/(:num)', 'kompetensi/hapus/(:num)', 'angkatan/hapus/(:num)', 'aktivitas/hapus/(:num)', 'admin/hapus/(:num)', 'admin/aktivasi/(:num)', 'tracer/aktivasi-alumni/(:num)'] as $uri) {
            $this->assertStringNotContainsString("->get('{$uri}'", $routes);
            $this->assertStringContainsString("->post('{$uri}'", $routes);
        }
    }

    public function testAjaxMutationClientsUsePostAndCsrfPayloads(): void
    {
        $files = [
            FCPATH . 'assets/js/custom/kompetensi/kompetensi.js',
            FCPATH . 'assets/js/custom/angkatan/angkatan.js',
            FCPATH . 'assets/js/custom/aktivitas/aktivitas.js',
            FCPATH . 'assets/js/custom/admin/admin.js',
        ];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            $this->assertIsString($source);
            $this->assertStringContainsString('csrf', strtolower($source));
        }

        $admin = file_get_contents($files[3]);
        $this->assertStringNotContainsString('row.id_pengguna, "GET"', $admin);
        $this->assertStringContainsString('row.id_pengguna, "POST", new FormData()', $admin);
    }

    public function testSecurityHeadersAreAddedToResponses(): void
    {
        $response = service('response', null, false);
        (new SecurityHeadersFilter())->after(service('request'), $response);

        $this->assertStringContainsString("default-src 'self'", $response->getHeaderLine('Content-Security-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=(), payment=(), usb=()', $response->getHeaderLine('Permissions-Policy'));
    }

    public function testSeederPreservesExistingAdminsAndRequiresProductionSecrets(): void
    {
        $seeder = file_get_contents(APPPATH . 'Database/Seeds/PenggunaSeeder.php');
        $readme = file_get_contents(ROOTPATH . 'README.md');
        $databaseDump = file_get_contents(ROOTPATH . 'database/tracerstudy.sql');

        $this->assertStringContainsString('adminsekolah@tracerstudy.local', $seeder);
        $this->assertStringContainsString('AdminSekolah123', $seeder);
        $this->assertStringContainsString('seed.superadminPassword', $seeder);
        $this->assertStringContainsString('seed.adminSekolahPassword', $seeder);
        $this->assertStringContainsString("ENVIRONMENT === 'production'", $seeder);
        $this->assertStringNotContainsString('->update(', $seeder);
        $this->assertStringContainsString('adminsekolah@tracerstudy.local', $readme);
        $this->assertStringContainsString('AdminSekolah123', $readme);
        $this->assertIsString($databaseDump);
        $this->assertDoesNotMatchRegularExpression(
            '/UPDATE\s+`?tb_pengguna`?.*`?kata_sandi`?/is',
            $databaseDump,
            'Dump database tidak boleh memuat perintah yang mereset password akun lama.',
        );

        $this->assertMatchesRegularExpression('/if\s*\(\$existing\)\s*\{\s*continue;\s*\}/', $seeder);
    }

    public function testEveryApplicationLayoutUsesTheSchoolFavicon(): void
    {
        foreach ([
            APPPATH . 'Views/landing/index.php',
            APPPATH . 'Views/layouts/auth.php',
            APPPATH . 'Views/partials/head.php',
        ] as $view) {
            $source = file_get_contents($view);
            $this->assertIsString($source);
            $this->assertStringContainsString('logo-smk-teratai-putih-3.svg', $source);
            $this->assertStringNotContainsString('logos/favicon.ico', $source);
        }
    }
}
