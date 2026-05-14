<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    public function testHomepageRenders200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        // Victor template's loader text — proves the SPA shell + branding is in place.
        self::assertStringContainsString('NEW FACTORY', $body);
        self::assertStringContainsString('lang="cs"', $body);
        // Czech section headings prove the rebrand (not the original Victor copy).
        // Section 1 has STROJE NA TĚŽBU heading + Section 4 has NÁŠ TÝM.
        self::assertStringContainsString('STROJE', $body);
        self::assertStringContainsString('SLUŽBY', $body);
        self::assertStringContainsString('KONTAKT', $body);
        // Sanity: NO mention of 2bminer/2bminers on the site (compliance).
        self::assertStringNotContainsString('2bminer', $body);
        // Victor 3D assets must be referenced (CSS + JS bundle paths).
        self::assertStringContainsString('/victor/styles/main.css', $body);
        self::assertStringContainsString('/victor/main.js', $body);

        // v0.4.3 regression guard — Section 1 must show ALL THREE machines
        // at-a-glance (3-up triptych grid). Visitors can't be left thinking
        // the offer is only one ASIC.
        self::assertStringContainsString('nf-machines--triptych', $body);
        self::assertStringContainsString('data-product-slug="antminer-s21-pro"', $body);
        self::assertStringContainsString('data-product-slug="antminer-s21-hyd"', $body);
        self::assertStringContainsString('data-product-slug="whatsminer-m60s"', $body);
    }
}
