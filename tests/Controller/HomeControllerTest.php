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
        self::assertStringContainsString('O NÁS', $body);
        self::assertStringContainsString('STROJE', $body);
        // Victor 3D assets must be referenced (CSS + JS bundle paths).
        self::assertStringContainsString('/victor/styles/main.css', $body);
        self::assertStringContainsString('/victor/main.js', $body);
    }
}
