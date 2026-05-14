<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\StrapiContentClient;
use Dosmart\Bundle\CmsCore\EditorScope\EditableFieldRegistry;
use Dosmart\Bundle\CmsCore\Strapi\StrapiClientInterface;
use Dosmart\Bundle\CmsCore\Strapi\StrapiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Slim inline-edit write API in front of Strapi.
 *
 * Why we don't reuse the bundle's CmsProxyController directly:
 * Symfony Config's `useAttributeAsKey()` (used in
 * Dosmart\Bundle\CmsCore\DependencyInjection\Configuration) silently rewrites
 * hyphenated YAML keys to underscored array keys when the parameter is
 * compiled. That means a YAML map keyed by `newfactory-homepage` is stored
 * inside the EditableFieldRegistry under `newfactory_homepage` — but the
 * Strapi REST URL uses the hyphenated slug (`/api/newfactory-homepage`).
 *
 * The bundle's controller uses the SAME slug for both the registry lookup
 * AND the Strapi call, so either path fails: dashed → registry miss (403),
 * underscored → wrong Strapi URL.
 *
 * This controller decouples the two: it translates the incoming slug to
 * underscored form ONLY for the registry whitelist check, and keeps the
 * original dashed form for the Strapi REST call.
 */
final class CmsApiController extends AbstractController
{
    public function __construct(
        private readonly EditableFieldRegistry $editableFields,
        private readonly StrapiClientInterface $strapi,
        private readonly StrapiContentClient $appCache,
    ) {}

    /**
     * PATCH /api/cms/single/{type}
     * Body: { "path": "hero_eyebrow", "value": "..." }
     */
    #[Route(
        path: '/api/cms/single/{type}',
        name: 'cms_api_patch_single',
        requirements: ['type' => '[a-z0-9-]+'],
        methods: ['PATCH'],
    )]
    #[IsGranted('ROLE_EDITOR')]
    public function patchSingle(string $type, Request $request): JsonResponse
    {
        return $this->write($type, documentId: '', request: $request);
    }

    /**
     * PATCH /api/cms/collection/{type}/{documentId}
     * Body: { "path": "title", "value": "..." }
     */
    #[Route(
        path: '/api/cms/collection/{type}/{documentId}',
        name: 'cms_api_patch_collection_item',
        requirements: ['type' => '[a-z0-9-]+', 'documentId' => '[a-zA-Z0-9]+'],
        methods: ['PATCH'],
    )]
    #[IsGranted('ROLE_EDITOR')]
    public function patchCollectionItem(string $type, string $documentId, Request $request): JsonResponse
    {
        return $this->write($type, documentId: $documentId, request: $request);
    }

    private function write(string $type, string $documentId, Request $request): JsonResponse
    {
        $payload = $this->decodeJson($request);
        $path = (string) ($payload['path'] ?? '');
        $value = $payload['value'] ?? null;

        if ($path === '') {
            return $this->jsonError('Missing field "path"', Response::HTTP_BAD_REQUEST);
        }

        // Registry stores hyphenated yaml keys as underscored array keys
        // (Symfony Config quirk). Translate for the whitelist lookup only —
        // Strapi still receives the original dashed slug below.
        $registryKey = str_replace('-', '_', $type);
        if (!$this->editableFields->isAllowed($registryKey, $path)
            && !$this->editableFields->isAllowed($type, $path)
        ) {
            return $this->jsonError(
                sprintf('Field "%s" on "%s" is not editable', $path, $type),
                Response::HTTP_FORBIDDEN,
            );
        }

        $data = $this->buildNestedData($path, $value);

        try {
            // updateItem(type, '', data) is the bundle's singleType convention;
            // pass the canonical dashed slug so the Strapi URL is correct.
            $response = $this->strapi->updateItem($type, $documentId, $data);
            // Bundle's own cache (TaggedCacheKey) AND our app-level Strapi
            // read cache must both be busted — they use different key shapes.
            $this->strapi->invalidateCache($type);
            $this->appCache->invalidate($type);
            return new JsonResponse($response);
        } catch (StrapiException $e) {
            return $this->jsonError(
                'Strapi error: ' . $e->getMessage(),
                $e->statusCode > 0 ? $e->statusCode : Response::HTTP_BAD_GATEWAY,
            );
        }
    }

    private function decodeJson(Request $request): array
    {
        $body = $request->getContent();
        if ($body === '') {
            return [];
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Convert "hero_eyebrow" or "hero.title" into a nested data array Strapi
     * expects under `data`. The bundle's StrapiClient wraps the outer
     * `{ data: ... }` envelope automatically.
     */
    private function buildNestedData(string $path, mixed $value): array
    {
        $parts = explode('.', $path);
        $result = $value;
        foreach (array_reverse($parts) as $segment) {
            $result = [$segment => $result];
        }
        return $result;
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}
