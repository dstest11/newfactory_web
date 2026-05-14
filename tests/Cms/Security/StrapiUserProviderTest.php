<?php

declare(strict_types=1);

namespace App\Tests\Cms\Security;

use App\Cms\Security\StrapiUser;
use App\Cms\Security\StrapiUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class StrapiUserProviderTest extends TestCase
{
    private const HASH = '$2y$04$placeholderHashUsedForTests....................';

    public function testLoadUserByIdentifierReturnsStrapiUserForAllowlistedEmail(): void
    {
        $provider = new StrapiUserProvider(
            editorEmails: ['editor@example.com', 'admin@dosmart.world'],
            passwordHash: self::HASH,
        );

        $user = $provider->loadUserByIdentifier('editor@example.com');

        self::assertInstanceOf(StrapiUser::class, $user);
        self::assertSame('editor@example.com', $user->getUserIdentifier());
        self::assertSame(self::HASH, $user->getPassword());
        self::assertContains('ROLE_EDITOR', $user->getRoles());
    }

    public function testLoadUserByIdentifierIsCaseAndWhitespaceInsensitive(): void
    {
        $provider = new StrapiUserProvider(
            editorEmails: [' Editor@Example.com '],
            passwordHash: self::HASH,
        );

        $user = $provider->loadUserByIdentifier('EDITOR@example.com');
        self::assertSame('editor@example.com', $user->getUserIdentifier());
    }

    public function testLoadUserByIdentifierThrowsForUnknownEmail(): void
    {
        $provider = new StrapiUserProvider(
            editorEmails: ['allowed@example.com'],
            passwordHash: self::HASH,
        );

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('unknown@example.com');
    }

    public function testLoadUserByIdentifierThrowsWhenPasswordHashIsPlaceholder(): void
    {
        $provider = new StrapiUserProvider(
            editorEmails: ['allowed@example.com'],
            passwordHash: '__PENDING__',
        );

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('allowed@example.com');
    }

    public function testLoadUserByIdentifierThrowsWhenPasswordHashIsEmpty(): void
    {
        $provider = new StrapiUserProvider(
            editorEmails: ['allowed@example.com'],
            passwordHash: '',
        );

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('allowed@example.com');
    }

    public function testSupportsClass(): void
    {
        $provider = new StrapiUserProvider([], self::HASH);
        self::assertTrue($provider->supportsClass(StrapiUser::class));
        self::assertFalse($provider->supportsClass(\stdClass::class));
    }
}
