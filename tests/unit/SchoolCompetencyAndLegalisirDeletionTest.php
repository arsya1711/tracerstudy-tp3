<?php

use CodeIgniter\Test\CIUnitTestCase;

final class SchoolCompetencyAndLegalisirDeletionTest extends CIUnitTestCase
{
    public function testSeedersOnlyUseTheThreeCorrectSchoolCompetencies(): void
    {
        $sources = file_get_contents(APPPATH . 'Database/Seeds/KompetensiSeeder.php')
            . file_get_contents(APPPATH . 'Database/Seeds/SidangSeeder.php');

        foreach (['TJK', 'AKL', 'MPLB'] as $acronym) {
            $this->assertStringContainsString("'{$acronym}'", $sources);
        }

        foreach (['Multimedia', 'Teknik Komputer Jaringan', 'Rekayasa Perangkat Lunak', 'Teknik Kendaraan Ringan Otomotif'] as $legacyName) {
            $this->assertStringNotContainsString($legacyName, $sources);
        }
    }

    public function testLegalisirDeletionIsPostOnlyAndChecksOwnershipAndStatus(): void
    {
        $routes = file_get_contents(APPPATH . 'Config/Routes.php');
        $controller = file_get_contents(APPPATH . 'Controllers/Alumni/LegalisirController.php');
        $view = file_get_contents(APPPATH . 'Views/alumni/legalisir/index.php');

        $this->assertStringContainsString("->post('legalisir/hapus/(:num)'", $routes);
        $this->assertStringNotContainsString("->get('legalisir/hapus/(:num)'", $routes);
        $this->assertSame(3, substr_count($routes, "->post('legalisir/hapus/(:num)'"));
        $this->assertStringContainsString("->where('id_alumni', (int) \$alumni['id_alumni'])", $controller);
        $this->assertStringContainsString("!== 'diajukan'", $controller);
        $this->assertStringContainsString('js-hapus-legalisir-form', $view);
        $this->assertStringContainsString('csrf_field()', $view);
    }
}
