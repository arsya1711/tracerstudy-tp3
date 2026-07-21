<?php

use App\Controllers\Auth\RegisterController;
use App\Controllers\Superadmin\TracerController;
use CodeIgniter\Test\CIUnitTestCase;

final class AlumniActivationWorkflowTest extends CIUnitTestCase
{
    public function testRegistrationCreatesInactivePendingAccount(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers/Auth/RegisterController.php');

        $this->assertIsString($source);
        $this->assertStringContainsString("'status_aktif'  => 0", $source);
        $this->assertStringContainsString("'status_verifikasi'   => 'menunggu_aktivasi'", $source);
        $this->assertStringContainsString("'status_pendaftaran'  => 'menunggu_aktivasi'", $source);
        $this->assertStringContainsString('kirimEmailPendaftaranAlumni', $source);
        $this->assertStringNotContainsString('password' . "\n" . '                .', $source);
    }

    public function testActivationRoutesArePostAndAdminViewUsesCsrf(): void
    {
        $routes = file_get_contents(APPPATH . 'Config/Routes.php');
        $view = file_get_contents(APPPATH . 'Views/superadmin/tracer/index.php');

        $this->assertIsString($routes);
        $this->assertIsString($view);
        $this->assertSame(2, substr_count($routes, "->post('tracer/aktivasi-alumni/(:num)'"));
        $this->assertStringNotContainsString("->get('tracer/aktivasi-alumni/(:num)'", $routes);
        $this->assertStringContainsString("'/aktivasi-alumni/'", $view);
        $this->assertStringContainsString('csrf_field()', $view);
        $this->assertStringContainsString('status_akun', $view);
    }

    public function testLoginChecksPasswordBeforeDisclosingActivationStatus(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers/Auth/LoginController.php');

        $this->assertIsString($source);
        $passwordCheck = strpos($source, 'password_verify');
        $activationCheck = strpos($source, '$menungguAktivasi');

        $this->assertNotFalse($passwordCheck);
        $this->assertNotFalse($activationCheck);
        $this->assertLessThan($activationCheck, $passwordCheck);
        $this->assertStringContainsString("'status'  => 'account_inactive'", $source);
    }

    public function testDemoEmailsAreExcludedFromAutomaticRecipients(): void
    {
        $controller = new TestableActivationRegisterController();
        $admins = [
            ['email' => 'admin@sekolah.sch.id', 'nama_lengkap' => 'Admin Asli', 'slug_peran' => 'admin_sekolah'],
            ['email' => 'rina.wulandari.skom@gmail.com', 'nama_lengkap' => 'Guru Demo', 'slug_peran' => 'admin_sekolah'],
            ['email' => 'superadmin@tracerstudy.local', 'nama_lengkap' => 'Bootstrap', 'slug_peran' => 'superadmin'],
        ];

        $this->assertSame(
            ['admin@sekolah.sch.id'],
            array_column($controller->recipients($admins, ''), 'email')
        );

        $this->assertSame(
            ['sidang.admin@gmail.com'],
            array_column($controller->recipients($admins, 'sidang.admin@gmail.com;invalid'), 'email')
        );
    }

    public function testSuccessfulActivationNotifiesAlumniWithoutSendingDemoEmail(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers/Superadmin/TracerController.php');
        $controller = new TestableActivationTracerController();

        $this->assertIsString($source);
        $this->assertStringContainsString('kirimNotifikasiAktivasiAlumni($alumni)', $source);
        $this->assertStringContainsString("'akun_diaktifkan'", $source);
        $this->assertStringContainsString("'Akun alumni sudah aktif'", $source);
        $this->assertStringContainsString("'Akun alumni kamu sudah aktif'", $source);
        $this->assertStringNotContainsString('kata_sandi', $source);
        $this->assertTrue($controller->isDemo([
            'nis' => '19200001',
            'email' => 'siti.rahmawati22@gmail.com',
        ]));
        $this->assertFalse($controller->isDemo([
            'nis' => '20250001',
            'email' => 'alumni@sekolah.sch.id',
        ]));
    }
}

class TestableActivationRegisterController extends RegisterController
{
    public function __construct()
    {
    }

    public function recipients(array $admins, string $configured): array
    {
        return $this->ambilPenerimaEmailAdmin($admins, $configured);
    }
}

class TestableActivationTracerController extends TracerController
{
    public function __construct()
    {
    }

    public function isDemo(array $alumni): bool
    {
        return $this->alumniDemo($alumni);
    }
}
