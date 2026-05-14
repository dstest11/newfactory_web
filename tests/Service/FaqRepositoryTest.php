<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\FaqRepository;
use App\Service\StrapiContentClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;

final class FaqRepositoryTest extends TestCase
{
    public function testFallbackProvidesListFaqs(): void
    {
        $strapi = new StrapiContentClient(
            http: new MockHttpClient([]),
            cache: new ArrayAdapter(),
            baseUrl: 'http://strapi.invalid',
            apiToken: '__stub__',
        );
        $repo = new FaqRepository($strapi);

        $faqs = $repo->forLocation('list');
        self::assertNotEmpty($faqs);
        // Hardcoded fallback ships 5 list-faqs.
        self::assertCount(5, $faqs);
        self::assertArrayHasKey('question', $faqs[0]);
        self::assertArrayHasKey('answer', $faqs[0]);
    }

    public function testFallbackProvidesDetailFaqs(): void
    {
        $strapi = new StrapiContentClient(
            http: new MockHttpClient([]),
            cache: new ArrayAdapter(),
            baseUrl: 'http://strapi.invalid',
            apiToken: '__stub__',
        );
        $repo = new FaqRepository($strapi);

        $faqs = $repo->forLocation('detail');
        self::assertNotEmpty($faqs);
        // Hardcoded fallback ships 4 detail-faqs.
        self::assertCount(4, $faqs);
    }
}
