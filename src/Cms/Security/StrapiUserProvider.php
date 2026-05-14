<?php

declare(strict_types=1);

namespace App\Cms\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Loads editor users from the env allowlist.
 *
 * Configuration (.env / GH Secrets):
 *   - NEWFACTORY_EDITOR_EMAILS         "admin@dosmart.world,jan@new-factory.cz"
 *   - NEWFACTORY_EDITOR_PASSWORD_HASH  bcrypt hash of the shared editor password
 *
 * The same hash is used for every email in the allowlist — simple shared
 * password for the small editor team. Future iteration can split into a
 * per-email map without breaking the UserInterface contract.
 *
 * @implements UserProviderInterface<StrapiUser>
 */
final class StrapiUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    /**
     * @param list<string> $editorEmails
     */
    public function __construct(
        private readonly array $editorEmails,
        private readonly string $passwordHash,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $normalized = strtolower(trim($identifier));
        $allowed = array_map(static fn (string $e): string => strtolower(trim($e)), $this->editorEmails);

        if (!in_array($normalized, $allowed, true)) {
            throw new UserNotFoundException(sprintf('Editor "%s" is not in the allowlist.', $identifier));
        }

        if ($this->passwordHash === '' || str_starts_with($this->passwordHash, '__')) {
            // Hash placeholder (e.g. __PENDING_PLAN3_TASKX__) — refuse to authenticate.
            throw new UserNotFoundException('Editor password hash is not configured.');
        }

        return new StrapiUser(email: $normalized, passwordHash: $this->passwordHash);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof StrapiUser) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === StrapiUser::class || is_subclass_of($class, StrapiUser::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // env-backed; no upgrade path
    }
}
