<?php

use CodeIgniter\Test\CIUnitTestCase;

final class AccountProfileWorkflowTest extends CIUnitTestCase
{
    public function testProfileRoutesAreAuthenticatedAndMutationsUsePost(): void
    {
        $routes = file_get_contents(APPPATH . 'Config/Routes.php');
        $this->assertIsString($routes);
        $this->assertStringContainsString("group('profil-akun', ['filter' => 'auth:superadmin,admin_sekolah']", $routes);
        $this->assertStringContainsString("->get('/', 'ProfilAkunController::index')", $routes);
        $this->assertStringContainsString("->post('update', 'ProfilAkunController::update')", $routes);
        $this->assertStringContainsString("->post('update-password', 'ProfilAkunController::updatePassword')", $routes);
        $this->assertStringNotContainsString("->get('update', 'ProfilAkunController::update')", $routes);
    }

    public function testAccountCardsLinkToTheCorrectProfile(): void
    {
        $sidebar = file_get_contents(APPPATH . 'Views/partials/sidebar.php');
        $this->assertIsString($sidebar);
        $this->assertStringContainsString("\$profileUrl = \$isAlumni ? base_url('alumni/profil') : base_url('profil-akun');", $sidebar);
        $this->assertSame(2, substr_count($sidebar, 'href="<?= esc($profileUrl) ?>"'));
    }

    public function testSensitiveChangesRequireTheCurrentPassword(): void
    {
        $controller = file_get_contents(APPPATH . 'Controllers/ProfilAkunController.php');
        $this->assertIsString($controller);
        $this->assertStringContainsString('$emailBerubah', $controller);
        $this->assertGreaterThanOrEqual(3, substr_count($controller, 'password_verify('));
        $this->assertStringContainsString('password_hash(', $controller);
        $this->assertStringContainsString('is_unique[tb_pengguna.email,id_pengguna,', $controller);
    }
}
