<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ProductCatalog;
use App\Service\ProductRepository;
use App\Service\StrapiContentClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ProductRepositoryTest extends TestCase
{
    public function testFallsBackToProductCatalogWhenStrapiTokenIsEmpty(): void
    {
        $strapi = new StrapiContentClient(
            http: new MockHttpClient([]),
            cache: new ArrayAdapter(),
            baseUrl: 'http://strapi.invalid',
            apiToken: '__stub__',
        );
        $repo = new ProductRepository($strapi, new ProductCatalog());

        $products = $repo->all();
        self::assertNotEmpty($products);
        // Hardcoded catalogue ships 3 ASIC products.
        self::assertCount(3, $products);
        self::assertSame('antminer-s21-pro', $products[0]['slug']);
    }

    public function testNormalisesStrapiPayloadIntoLegacyShape(): void
    {
        $payload = [
            'data' => [
                [
                    'slug' => 'antminer-s21-pro',
                    'name' => 'Antminer S21 Pro',
                    'manufacturer' => 'Bitmain',
                    'algorithm' => 'SHA-256',
                    'currency_type' => 'Bitcoin',
                    'delivery' => 'Fast Track',
                    'hashrate' => '234 TH/s',
                    'power_w' => 3531,
                    'machine_price_czk' => 169900,
                    'accessories_czk' => 4500,
                    'electricity_yearly_czk' => 154650,
                    'short_description' => 'Hello',
                    'service_lines' => [
                        ['name' => 'Letecká doprava (DDP)', 'price_czk' => 9500, 'is_optional' => false],
                        ['name' => 'Hosting',                  'price_czk' => 3500, 'is_optional' => true],
                    ],
                    'order' => 1,
                ],
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

        $repo = new ProductRepository($strapi, new ProductCatalog());
        $products = $repo->all();

        self::assertCount(1, $products);
        self::assertSame('antminer-s21-pro', $products[0]['slug']);
        self::assertSame('Bitmain', $products[0]['manufacturer']);
        self::assertSame(3531, $products[0]['power_w']);
        self::assertCount(2, $products[0]['service_lines']);
        self::assertFalse($products[0]['service_lines'][0]['is_optional']);
        self::assertTrue($products[0]['service_lines'][1]['is_optional']);
    }

    public function testBySlugReturnsNullForUnknown(): void
    {
        $strapi = new StrapiContentClient(
            http: new MockHttpClient([]),
            cache: new ArrayAdapter(),
            baseUrl: 'http://strapi.invalid',
            apiToken: '__stub__',
        );
        $repo = new ProductRepository($strapi, new ProductCatalog());

        self::assertNull($repo->bySlug('does-not-exist'));
        self::assertNotNull($repo->bySlug('antminer-s21-pro'));
    }
}
