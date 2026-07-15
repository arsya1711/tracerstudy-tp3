<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StoredXssRegressionTest extends TestCase
{
    private const SCRIPT_SAFE_JSON_FLAGS = JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT;

    public function testScriptSafeJsonCannotCloseInlineScriptAndPreservesChartData(): void
    {
        $chartData = [
            'labels' => [
                '</script><script>window.storedXss = true</script>',
                "SMK \"Teratai\" & O'Reilly",
                'Kuliah / Melanjutkan Studi',
            ],
            'series' => [1, 2, 3],
        ];

        $encoded = json_encode($chartData, self::SCRIPT_SAFE_JSON_FLAGS | JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('</script', strtolower($encoded));
        $this->assertStringContainsString('\u003C/script\u003E', $encoded);
        $this->assertStringContainsString('\u0026', $encoded);
        $this->assertStringContainsString('\u0027', $encoded);
        $this->assertStringContainsString('\u0022', $encoded);
        $this->assertSame($chartData, json_decode($encoded, true, 512, JSON_THROW_ON_ERROR));
    }

    public function testDashboardRendersStoredActivityNameWithoutCreatingAnotherScriptTag(): void
    {
        $payload = '</script><script>window.storedXss = true</script>';
        $html = view('dashboard/super-admin/index', [
            'tracer_aktivitas' => [
                'labels' => [$payload],
                'series' => [1],
                'map' => [$payload => 1],
            ],
            'tracer_angkatan' => ['labels' => [], 'series' => []],
        ]);
        $safeHtml = view('dashboard/super-admin/index', [
            'tracer_aktivitas' => [
                'labels' => ['Bekerja'],
                'series' => [1],
                'map' => ['Bekerja' => 1],
            ],
            'tracer_angkatan' => ['labels' => [], 'series' => []],
        ]);

        $this->assertStringNotContainsString($payload, $html);
        $this->assertStringContainsString('\\u003C/script\\u003E', $html);
        $this->assertSame(
            substr_count(strtolower($safeHtml), '<script'),
            substr_count(strtolower($html), '<script'),
        );
    }

    public function testEveryJsonEncodeCallInViewsUsesAllScriptSafeFlags(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(APPPATH . 'Views', FilesystemIterator::SKIP_DOTS)
        );
        $checkedCalls = 0;

        foreach ($iterator as $file) {
            if (! $file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            $this->assertIsString($source);

            foreach ($this->extractJsonEncodeCalls($source) as $call) {
                $checkedCalls++;

                foreach (['JSON_HEX_TAG', 'JSON_HEX_AMP', 'JSON_HEX_APOS', 'JSON_HEX_QUOT'] as $flag) {
                    $this->assertStringContainsString(
                        $flag,
                        $call,
                        sprintf('%s memakai json_encode() tanpa %s.', $file->getPathname(), $flag)
                    );
                }
            }
        }

        $this->assertGreaterThan(0, $checkedCalls, 'Tidak ada pemanggilan json_encode() pada app/Views yang diperiksa.');
    }

    /**
     * @return list<string>
     */
    private function extractJsonEncodeCalls(string $source): array
    {
        $tokens = token_get_all($source);
        $calls = [];
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (! is_array($token) || $token[0] !== T_STRING || strcasecmp($token[1], 'json_encode') !== 0) {
                continue;
            }

            $call = $token[1];
            $depth = 0;
            $started = false;

            for ($cursor = $index + 1; $cursor < $tokenCount; $cursor++) {
                $current = $tokens[$cursor];
                $text = is_array($current) ? $current[1] : $current;
                $call .= $text;

                if (! $started) {
                    if (trim($text) === '') {
                        continue;
                    }

                    if ($text !== '(') {
                        break;
                    }

                    $started = true;
                    $depth = 1;
                    continue;
                }

                if ($text === '(') {
                    $depth++;
                    continue;
                }

                if ($text === ')') {
                    $depth--;

                    if ($depth === 0) {
                        $calls[] = $call;
                        $index = $cursor;
                        break;
                    }
                }
            }
        }

        return $calls;
    }
}
