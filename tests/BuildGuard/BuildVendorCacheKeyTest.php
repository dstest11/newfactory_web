<?php

declare(strict_types=1);

namespace App\Tests\BuildGuard;

use PHPUnit\Framework\TestCase;

/**
 * The vendor artifact may only come from the Spaces cache when nothing baked into it has changed.
 * `composer install` writes the app's class list (src/) into vendor/composer/autoload_classmap.php, so a
 * cache key without src/ hands out a classmap from an older release. With `--classmap-authoritative` the
 * autoloader then refuses the PSR-4 fallback and the kernel boot dies on the first new class — appcontrol
 * release v2026.10.30 (2026-10-30) died exactly so and answered 500 everywhere until rolled back.
 * Same fix as stavsys #255 / appcontrol #26.
 */
final class BuildVendorCacheKeyTest extends TestCase
{
    private const string WORKFLOW = __DIR__ . '/../../.github/workflows/build-vendor.yml';

    public function testCacheKeyHashesSrcAndConfig(): void
    {
        $step = $this->step('Compute artifact cache key');

        self::assertMatchesRegularExpression('/for dir in [^\n;]*\bsrc\b/', $step, 'The cache key must hash src/ — it is baked into the classmap.');
        self::assertMatchesRegularExpression('/for dir in [^\n;]*\bconfig\b/', $step, 'The cache key must hash config/ — it shapes compiled output.');
    }

    public function testCacheKeyHashesTheWorkflowItself(): void
    {
        $step = $this->step('Compute artifact cache key');

        self::assertStringContainsString('FILES+=(.github/workflows/build-vendor.yml)', $step, 'A change to build flags must invalidate tarballs built with the old ones.');
    }

    public function testComposerInstallKeepsThePsr4Fallback(): void
    {
        $step = $this->step('Composer install');

        self::assertStringContainsString('composer install', $step);
        self::assertStringNotContainsString('--classmap-authoritative', $step, 'An authoritative classmap turns any stale cache into a boot failure.');
    }

    private function step(string $name): string
    {
        $yaml = (string) file_get_contents(self::WORKFLOW);
        $start = strpos($yaml, '- name: ' . $name);
        self::assertNotFalse($start, sprintf('Step "%s" not found in build-vendor.yml.', $name));
        $next = strpos($yaml, "\n      - name: ", $start + 1);

        return substr($yaml, $start, false === $next ? null : $next - $start);
    }
}
