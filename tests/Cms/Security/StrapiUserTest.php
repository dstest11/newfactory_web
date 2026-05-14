<?php

declare(strict_types=1);

namespace App\Tests\Cms\Security;

use App\Cms\Security\StrapiUser;
use PHPUnit\Framework\TestCase;

final class StrapiUserTest extends TestCase
{
    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new StrapiUser('editor@example.com', '$2y$04$dummyHashThatNobodyMatches', ['ROLE_EDITOR']);
        self::assertSame('editor@example.com', $user->getUserIdentifier());
    }

    public function testRolesAlwaysIncludeRoleUser(): void
    {
        $user = new StrapiUser('editor@example.com', '$2y$04$x', ['ROLE_EDITOR']);
        $roles = $user->getRoles();

        self::assertContains('ROLE_EDITOR', $roles);
        self::assertContains('ROLE_USER', $roles);
        self::assertCount(2, $roles, 'Expected exactly ROLE_EDITOR + ROLE_USER');
    }

    public function testRolesAreDeduplicated(): void
    {
        $user = new StrapiUser('editor@example.com', '$2y$04$x', ['ROLE_EDITOR', 'ROLE_USER', 'ROLE_EDITOR']);
        $roles = $user->getRoles();

        self::assertSame(['ROLE_EDITOR', 'ROLE_USER'], array_values($roles));
    }

    public function testPasswordHashIsExposedForHashing(): void
    {
        $user = new StrapiUser('editor@example.com', '$2y$04$secret-hash', ['ROLE_EDITOR']);
        self::assertSame('$2y$04$secret-hash', $user->getPassword());
    }

    public function testEraseCredentialsIsNoOp(): void
    {
        $user = new StrapiUser('editor@example.com', '$2y$04$x', ['ROLE_EDITOR']);
        $user->eraseCredentials();
        self::assertSame('$2y$04$x', $user->getPassword(), 'eraseCredentials must not wipe the hash');
    }
}
