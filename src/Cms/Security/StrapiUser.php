<?php

declare(strict_types=1);

namespace App\Cms\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * In-memory editor identity. Backed by the env allowlist
 * (NEWFACTORY_EDITOR_EMAILS + NEWFACTORY_EDITOR_PASSWORD_HASH) — there is no
 * local user database. The class is also designed to accommodate a future
 * Strapi Users-Permissions backend without changing the security.yaml wiring.
 */
final class StrapiUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param list<string> $roles Symfony roles (must include ROLE_EDITOR for inline edits)
     */
    public function __construct(
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly array $roles = ['ROLE_EDITOR'],
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        // Guarantee ROLE_USER is present so isAuthenticated() checks work.
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }
        return array_values(array_unique($roles));
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    public function eraseCredentials(): void
    {
        // password hash is the only credential we hold; nothing transient to wipe
    }
}
