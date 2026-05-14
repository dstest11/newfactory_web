<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\HomepageRepository;
use App\Service\StrapiContentClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class HomepageRepositoryTest extends TestCase
{
    public function testFallbackShipsHeroCopy(): void
    {
        $strapi = new StrapiContentClient(
            http: new MockHttpClient([]),
            cache: new ArrayAdapter(),
            baseUrl: 'http://strapi.invalid',
            apiToken: '__stub__',
        );
        $repo = new HomepageRepository($strapi);

        $hp = $repo->get();
        self::assertSame('STROJE', $hp['hero_title_top']);
        self::assertSame('NA TĚŽBU', $hp['hero_title_bottom']);
        self::assertArrayHasKey('hero_lead', $hp);
        self::assertArrayHasKey('benefits_title', $hp);
    }

    public function testStrapiPayloadOverridesFallback(): void
    {
        $payload = [
            'data' => [
                'hero_eyebrow' => '[CUSTOM]',
                'hero_title_top' => 'CUSTOM',
                'hero_title_bottom' => 'TITLE',
            ],
        ];
        $http = new MockHttpClient([new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR), [
            'response_headers' => ['Content-Type: application/json'],
        ])]);
        $strapi = new StrapiContentClient(
            http: $http,
            cache: new ArrayAdapter(),
            baseUrl: 'http://strapi.invalid',
            apiToken: 'real-token-1234',
        );
        $repo = new HomepageRepository($strapi);

        $hp = $repo->get();
        self::assertSame('[CUSTOM]', $hp['hero_eyebrow']);
        self::assertSame('CUSTOM', $hp['hero_title_top']);
        self::assertSame('TITLE', $hp['hero_title_bottom']);
        // Fallback fields stay populated for keys not present in payload.
        self::assertNotEmpty($hp['benefits_title']);
    }
}
