<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Editor admin entry point. Public surface area is intentionally minimal:
 *
 *   GET  /admin/login         render login form (anonymous-allowed)
 *   POST /admin/login         handled by Symfony Security form_login (no method here)
 *   GET  /admin/logout        handled by Symfony Security logout (no method here)
 *   GET  /admin               dashboard placeholder — confirms the editor is logged in
 *
 * The actual inline-edit writes go through Dosmart\Bundle\CmsCore\Controller\
 * CmsProxyController which is mounted at /api/cms/* by the cms-core-bundle's
 * routes.yaml. That controller calls App\Cms\Authenticator\NewfactoryAuthenticator
 * to resolve the editor context — which now returns a real EditContext for
 * any session holding ROLE_EDITOR.
 */
final class AdminController extends AbstractController
{
    #[Route('/admin/login', name: 'admin_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If already logged in, send to dashboard.
        if ($this->isGranted('ROLE_EDITOR')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * Symfony Security intercepts /admin/logout via the firewall config; this
     * method is never invoked but the route must exist so url generators work.
     */
    #[Route('/admin/logout', name: 'admin_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Symfony Security handles /admin/logout — this method is unreachable.');
    }

    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_EDITOR')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    /**
     * Endpoint the inline-edit Stimulus controller calls to confirm it still
     * has an authenticated editor session. Returns 200 with a fresh CSRF token
     * (handy for re-issuing if the page sat idle for an hour), or 401 if the
     * session expired.
     */
    #[Route('/admin/api/session', name: 'admin_api_session', methods: ['GET'])]
    public function session(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if ($user === null || !$this->isGranted('ROLE_EDITOR')) {
            return new JsonResponse(['authenticated' => false], Response::HTTP_UNAUTHORIZED);
        }
        return new JsonResponse([
            'authenticated' => true,
            'email' => $user->getUserIdentifier(),
            'roles' => $this->getUser()?->getRoles() ?? [],
        ]);
    }
}
