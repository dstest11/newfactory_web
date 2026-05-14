<?php

declare(strict_types=1);

namespace App\Cms\Authenticator;

use Dosmart\Bundle\CmsCore\Authenticator\AuthenticatorInterface;
use Dosmart\Bundle\CmsCore\Authenticator\EditContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Bridges Symfony Security to the cms-core-bundle's CmsProxyController.
 *
 * Plan 2 Task 5 will replace this minimal impl with a Strapi-backed login
 * authenticator (StrapiUser + StrapiUserProvider + StrapiLoginAuthenticator).
 * For now this just hands the bundle a working `resolve()` that returns null
 * when no Symfony user is authenticated, or constructs an EditContext from
 * the current token when one exists. That's enough to let the app boot and
 * serve the public marketing pages while admin routes are still being built.
 */
final class NewfactoryAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly string $tenant,
    ) {}

    public function resolve(Request $request): ?EditContext
    {
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return null;
        }
        $user = $token->getUser();
        if ($user === null) {
            return null;
        }

        $roles = $token->getRoleNames();
        $hasEditorRole = in_array('ROLE_EDITOR', $roles, true)
            || in_array('ROLE_ADMIN', $roles, true)
            || in_array('ROLE_SUPERADMIN', $roles, true);

        if (!$hasEditorRole) {
            return null;
        }

        return new EditContext(
            userId: $user->getUserIdentifier(),
            tenant: $this->tenant,
            roles: $roles,
        );
    }
}
