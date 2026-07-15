<?php

use App\Controllers\Alumni\DashboardController;
use App\Controllers\Alumni\LegalisirController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Superadmin\TracerController;
use App\Models\TracerAlumniModel;
use CodeIgniter\Test\CIUnitTestCase;

final class BusinessWorkflowTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        session()->remove('redirect_setelah_login');
        parent::tearDown();
    }

    public function testLoginRedirectsEveryRoleToItsOwnArea(): void
    {
        $controller = new TestableLoginController();

        $this->assertSame(site_url('dashboard/superadmin'), $controller->redirectUrl('superadmin'));
        $this->assertSame(site_url('admin-sekolah/dashboard'), $controller->redirectUrl('admin_sekolah'));
        $this->assertSame(site_url('alumni/dashboard'), $controller->redirectUrl('alumni'));
        $this->assertSame(site_url('login'), $controller->redirectUrl('tidak_dikenal'));
    }

    public function testAlumniOnboardingRequiresProfileThenTracer(): void
    {
        $controller = new TestableAlumniDashboardController();
        $incomplete = $controller->checklist(['nis' => '', 'id_angkatan' => null, 'id_kompetensi' => null], null);

        $this->assertFalse($incomplete['ready']);
        $this->assertSame('profil', $incomplete['next_step']['key']);
        $this->assertSame(33, $incomplete['progress']['persen']);

        $profileComplete = $controller->checklist(['nis' => '123', 'id_angkatan' => 1, 'id_kompetensi' => 1], null);
        $this->assertSame('tracer', $profileComplete['next_step']['key']);

        $complete = $controller->checklist(['nis' => '123', 'id_angkatan' => 1, 'id_kompetensi' => 1], ['id_tracer' => 1]);
        $this->assertTrue($complete['ready']);
        $this->assertSame(100, $complete['progress']['persen']);
    }

    public function testLegalisirIsBlockedUntilProfileAndTracerAreComplete(): void
    {
        $controller = new TestableLegalisirController(false);
        $missingProfile = $controller->readiness(['id_alumni' => 1, 'nis' => '', 'id_angkatan' => null, 'id_kompetensi' => null]);
        $this->assertFalse($missingProfile['boleh']);
        $this->assertStringContainsString('profil', $missingProfile['alasan']);

        $missingTracer = $controller->readiness(['id_alumni' => 1, 'nis' => '123', 'id_angkatan' => 1, 'id_kompetensi' => 1]);
        $this->assertFalse($missingTracer['boleh']);
        $this->assertStringContainsString('tracer', $missingTracer['alasan']);

        $readyController = new TestableLegalisirController(true);
        $ready = $readyController->readiness(['id_alumni' => 1, 'nis' => '123', 'id_angkatan' => 1, 'id_kompetensi' => 1]);
        $this->assertTrue($ready['boleh']);
        $this->assertSame('', $ready['alasan']);
    }

    public function testExportHelpersEscapeSpreadsheetAndPdfContent(): void
    {
        $controller = new TestableTracerController();

        $this->assertSame('&lt;Alumni&gt; &amp; Sekolah', $controller->excelEscape('<Alumni> & Sekolah'));
        $this->assertSame('Nama \\(QA\\)', $controller->escapePdf('Nama (QA)'));
        $this->assertSame(['satu dua', 'tiga'], $controller->wrapPdf('satu dua tiga', 8, 2));
    }

    public function testLandingRendersActivityChartWithoutManagementButton(): void
    {
        $html = view('landing/index', [
            'title' => 'Tracer Study Alumni',
            'statistik' => [],
            'aktivitas' => [
                ['nama_aktivitas' => 'Bekerja', 'total' => 20],
                ['nama_aktivitas' => 'Wirausaha', 'total' => 10],
            ],
        ]);

        $this->assertStringContainsString('Grafik Aktivitas Alumni', $html);
        $this->assertStringContainsString('activity-chart-bar', $html);
        $this->assertStringContainsString('--chart-value: 100.00%', $html);
        $this->assertStringContainsString('--chart-value: 50.00%', $html);
        $this->assertStringNotContainsString('Kelola Data', $html);
        $this->assertStringContainsString('logo-smk-teratai-putih-3.svg', $html);
    }

    public function testDashboardMobileNavigationKeepsSchoolBrandAndNativeDrawerControls(): void
    {
        $sidebar = file_get_contents(APPPATH . 'Views/partials/sidebar.php');
        $head = file_get_contents(APPPATH . 'Views/partials/head.php');
        $footer = file_get_contents(APPPATH . 'Views/partials/footer.php');
        $responsiveCss = file_get_contents(FCPATH . 'assets/css/custom/dashboard-responsive.css');
        $mobileJs = file_get_contents(FCPATH . 'assets/js/custom/dashboard-mobile.js');

        foreach ([$sidebar, $head, $footer, $responsiveCss, $mobileJs] as $source) {
            $this->assertIsString($source);
        }

        $this->assertStringContainsString('SMK Teratai Putih 3', $sidebar);
        $this->assertStringContainsString('logo-smk-teratai-putih-3.svg', $sidebar);
        $this->assertStringContainsString('id="kt_app_sidebar_mobile_toggle"', $sidebar);
        $this->assertStringContainsString('aria-controls="kt_app_sidebar"', $sidebar);
        $this->assertStringContainsString('data-kt-drawer-overlay="true"', $sidebar);
        $this->assertStringContainsString('data-kt-drawer-close="#kt_app_sidebar_close"', $sidebar);
        $this->assertStringContainsString('data-kt-drawer-dismiss="true"', $sidebar);
        $this->assertStringContainsString('assets/css/custom/dashboard-responsive.css', $head);
        $this->assertStringContainsString('assets/js/custom/dashboard-mobile.js', $footer);
        $this->assertStringNotContainsString('kt-sidebar-manual-closed', $footer);
        $this->assertStringContainsString('#kt_app_sidebar .app-sidebar-logo', $responsiveCss);
        $this->assertStringContainsString('getFocusableElements', $mobileJs);
        $this->assertStringContainsString("event.key === 'Escape'", $mobileJs);
    }
}

class TestableLoginController extends LoginController
{
    public function __construct()
    {
    }

    public function redirectUrl(string $role): string
    {
        return $this->getRedirectUrl($role);
    }
}

class TestableAlumniDashboardController extends DashboardController
{
    public function __construct()
    {
    }

    public function checklist(array $alumni, ?array $tracer): array
    {
        return $this->bangunChecklist($alumni, $tracer);
    }
}

class StubTracerAlumniModel extends TracerAlumniModel
{
    public function __construct(private readonly bool $hasTracer)
    {
    }

    public function ambilTerakhirByAlumni($idAlumni)
    {
        return $this->hasTracer ? ['id_tracer' => 1, 'id_alumni' => $idAlumni] : null;
    }
}

class TestableLegalisirController extends LegalisirController
{
    public function __construct(bool $hasTracer)
    {
        $this->tracerModel = new StubTracerAlumniModel($hasTracer);
    }

    public function readiness(array $alumni): array
    {
        return $this->cekKesiapanLegalisir($alumni);
    }
}

class TestableTracerController extends TracerController
{
    public function __construct()
    {
    }

    public function excelEscape(string $value): string
    {
        return $this->excelXmlEscape($value);
    }

    public function escapePdf(string $value): string
    {
        return $this->pdfEscape($value);
    }

    public function wrapPdf(string $value, int $maxChars, int $maxLines): array
    {
        return $this->pdfWrapText($value, $maxChars, $maxLines);
    }
}
