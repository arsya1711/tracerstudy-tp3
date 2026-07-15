<?php

use App\Models\AktivitasModel;
use CodeIgniter\Test\CIUnitTestCase;

final class AktivitasSchemaRegressionTest extends CIUnitTestCase
{
    public function testActivityModelOnlyWritesCanonicalColumns(): void
    {
        $reflection = new ReflectionClass(AktivitasModel::class);
        $model = $reflection->newInstanceWithoutConstructor();
        $allowedFields = $reflection->getProperty('allowedFields')->getValue($model);

        $this->assertSame(
            ['nama_aktivitas', 'keterangan', 'status_aktif'],
            $allowedFields,
        );
        $this->assertNotContains('slug_aktivitas', $allowedFields);
    }

    public function testForwardMigrationRemovesTheLegacySlugColumn(): void
    {
        $migration = file_get_contents(
            APPPATH . 'Database/Migrations/2026-07-16-000039_DropLegacySlugAktivitas.php',
        );

        $this->assertIsString($migration);
        $this->assertStringContainsString("fieldExists('slug_aktivitas', 'tb_aktivitas')", $migration);
        $this->assertStringContainsString(
            'ALTER TABLE `tb_aktivitas` DROP COLUMN `slug_aktivitas`',
            $migration,
        );
    }

    public function testCanonicalSchemaAndSeedDataDoNotRequireAnActivitySlug(): void
    {
        $createMigration = file_get_contents(
            APPPATH . 'Database/Migrations/2026-04-18-000005_CreateTbAktivitas.php',
        );
        $databaseDump = file_get_contents(ROOTPATH . 'database/tracerstudy.sql');
        $seeder = file_get_contents(APPPATH . 'Database/Seeds/AktivitasSeeder.php');
        $controller = file_get_contents(
            APPPATH . 'Controllers/Superadmin/AktivitasController.php',
        );
        $logicalSchema = file_get_contents(ROOTPATH . 'docs/lrs-tracer-study.md');
        $diagramSchema = file_get_contents(ROOTPATH . 'docs/lrs-tracer-study.drawio');

        $this->assertIsString($createMigration);
        $this->assertIsString($databaseDump);
        $this->assertIsString($seeder);
        $this->assertIsString($controller);
        $this->assertIsString($logicalSchema);
        $this->assertIsString($diagramSchema);
        $this->assertStringNotContainsString('slug_aktivitas', $createMigration);
        $this->assertStringNotContainsString('slug_aktivitas', $databaseDump);
        $this->assertStringNotContainsString('slug_aktivitas', $seeder);
        $this->assertStringNotContainsString('slug_aktivitas', $controller);
        $this->assertStringNotContainsString('slug_aktivitas', $logicalSchema);
        $this->assertStringNotContainsString('slug_aktivitas', $diagramSchema);
    }
}
