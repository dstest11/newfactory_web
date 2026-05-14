<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AdminControllerTest extends WebTestCase
{
    public function testLoginPageRendersForAnonymousVisitor(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'PŘIHLÁŠENÍ');
        self::assertSelectorExists('form[action]');
        self::assertSelectorExists('input[name="email"]');
        self::assertSelectorExists('input[name="password"]');
    }

    public function testDashboardRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        // form_login firewall redirects unauthenticated users to login_path
        self::assertResponseRedirects('/admin/login');
    }

    public function testInvalidCredentialsKeepUserAtLoginWithError(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login');
        $token = $this->extractCsrfToken($client);

        $client->request('POST', '/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrong-password',
            '_csrf_token' => $token,
        ]);

        // Symfony redirects back to login_path on failure
        $client->followRedirect();
        self::assertSelectorExists('.nf-admin-alert');
    }

    public function testValidCredentialsRedirectToDashboard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login');
        $token = $this->extractCsrfToken($client);

        $client->request('POST', '/admin/login', [
            'email' => 'editor@example.com',
            'password' => 'edit-test-2026',
            '_csrf_token' => $token,
        ]);

        self::assertResponseRedirects('/admin');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'JSI PŘIHLÁŠEN');
    }

    private function extractCsrfToken(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): string
    {
        $crawler = $client->getCrawler();
        $node = $crawler->filterXPath('//input[@name="_csrf_token"]');
        self::assertGreaterThan(0, $node->count(), 'CSRF input must be present on login form');
        return $node->attr('value') ?? '';
    }

    public function testCmsApiRejectsAnonymous(): void
    {
        $client = static::createClient();
        $client->request('PATCH', '/api/cms/single/newfactory-homepage', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['path' => 'hero_title_top', 'value' => 'X']) ?: '');

        // access_control: ^/api/cms requires ROLE_EDITOR → 302 → login OR 401 from firewall.
        // form_login throws AuthenticationException which entrypoint redirects to /admin/login.
        self::assertContains(
            $client->getResponse()->getStatusCode(),
            [Response::HTTP_FOUND, Response::HTTP_UNAUTHORIZED],
            sprintf('Expected redirect or 401 for anonymous CMS call, got %d', $client->getResponse()->getStatusCode()),
        );
    }

    public function testPublicHomepageStaysReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        // Inline-edit firewall must not break the public site.
        self::assertResponseIsSuccessful();
    }
}
