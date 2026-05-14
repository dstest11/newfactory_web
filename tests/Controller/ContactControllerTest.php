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
        self::assertStringContainsString('Pošlete nám poptávku', $body);
        self::assertStringContainsString('mining@new-factory.cz', $body);
        // Honeypot field is present but visually hidden.
        self::assertStringContainsString('contact[website]', $body);
    }

    public function testHoneypotBlocksSubmission(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/kontakt');
        $form = $crawler->selectButton('Odeslat poptávku')->form();
        $form['contact[name]'] = 'Spam Bot';
        $form['contact[email]'] = 'bot@example.com';
        $form['contact[message]'] = 'I am a bot filling every field';
        $form['contact[website]'] = 'http://spam.example.com';  // bot trap
        $client->submit($form);

        // Honeypot rejection paths: either form re-render (200, "Spam detected")
        // OR rate-limiter (429, "Příliš mnoho pokusů") — both block spam,
        // which is what matters. The thing that MUST NOT happen is a 302
        // redirect to success state with "Děkujeme!" flash.
        $status = $client->getResponse()->getStatusCode();
        self::assertContains($status, [200, 429], "expected anti-spam status, got $status");
        $body = (string) $client->getResponse()->getContent();
        self::assertStringNotContainsString('Děkujeme!', $body, 'honeypot must not yield success flash');
    }
}
