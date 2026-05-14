<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductControllerTest extends WebTestCase
{
    public function testListRendersAllProducts(): void
    {
        $client = static::createClient();
        $client->request('GET', '/produkty');

        self::assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Antminer S21 Pro', $body);
        self::assertStringContainsString('Antminer S21 Hydro', $body);
        self::assertStringContainsString('Whatsminer M60S', $body);
    }

    public function testDetailRendersValidSlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/produkty/antminer-s21-pro');

        self::assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Antminer S21 Pro', $body);
        self::assertStringContainsString('234 TH/s', $body);
    }

    public function testDetail404OnUnknownSlug(): void
    {
        $client = static::createClient();
        $client->request('GET', '/produkty/neexistuje');
        self::assertResponseStatusCodeSame(404);
    }
}
