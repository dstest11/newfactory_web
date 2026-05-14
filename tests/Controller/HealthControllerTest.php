<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthControllerTest extends WebTestCase
{
    public function testHealthReturnsJsonOk(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_health');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');

        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('ok', $payload['status']);
        self::assertSame('newfactory_web', $payload['app']);
    }
}
