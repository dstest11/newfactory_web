<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ContactControllerTest extends WebTestCase
{
    public function testGetRendersForm(): void
    {
        $client = static::createClient();
        $client->request('GET', '/kontakt');
        self::assertResponseIsSuccessful();

        $body = (string) $client->getResponse()->getContent();
        // Headline phrase ("Pošlete nám poptávku") is split across markup for editorial layout
        // — check the two halves separately + the action verb on the submit button.
        self::assertStringContainsString('Pošlete nám', $body);
        self::assertStringContainsString('poptávku', $body);
        self::assertStringContainsString('Odeslat poptávku', $body);
        self::assertStringContainsString('mining@new-factory.cz', $body);
        // Honeypot field is present but visually hidden. The form is a plain
        // HTML form posted via fetch() to /kontakt-odeslat (not a Symfony
        // FormType since #23), so the field is `website`, not `contact[website]`.
        self::assertStringContainsString('name="website"', $body);
    }

    public function testHoneypotBlocksSubmission(): void
    {
        $client = static::createClient();
        $client->enableProfiler();
        $client->request('POST', '/kontakt-odeslat', [
            'name' => 'Spam Bot',
            'email' => 'bot@example.com',
            'message' => 'I am a bot filling every field',
            'website' => 'http://spam.example.com', // bot trap
        ]);

        // The honeypot answers with a fake success (so the bot learns nothing)
        // but MUST NOT send a lead e-mail.
        self::assertResponseIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testMissingNameAndEmailAreRejected(): void
    {
        $client = static::createClient();
        $client->request('POST', '/kontakt-odeslat', ['name' => '', 'email' => 'not-an-email']);

        self::assertResponseStatusCodeSame(422);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertFalse($data['ok']);
        self::assertArrayHasKey('name', $data['errors']);
        self::assertArrayHasKey('email', $data['errors']);
    }
}
