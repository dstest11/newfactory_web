<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\BenefitRepository;
use App\Service\StrapiContentClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;

final class BenefitRepositoryTest extends TestCase
{
    public function testFallbackProvidesSixBenefits(): void
    {
        $strapi = new StrapiContentClient(
            http: new MockHttpClient([]),
            cache: new ArrayAdapter(),
            baseUrl: 'http://strapi.invalid',
            apiToken: '__stub__',
        );
        $repo = new BenefitRepository($strapi);

        $benefits = $repo->all();
        self::assertCount(6, $benefits);
        self::assertSame('Cena elektřiny 5 Kč/kWh', $benefits[0]['title']);
    }
}
