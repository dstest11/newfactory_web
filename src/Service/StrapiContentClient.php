<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface as ContractsHttpClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Thin Strapi v5 read client filtered to the `newfactory` tenant.
 *
 * Strapi instance is shared across all dosmart apps — schemas use a `tenant`
 * field for soft isolation. This client encapsulates the tenant filter plus
 * a Symfony cache wrap so repeated controller hits don't hammer Strapi.
 */
final class StrapiContentClient
{
    public function __construct(
        private readonly ContractsHttpClient $http,
        private readonly CacheInterface $cache,
        private readonly string $baseUrl,
        private readonly string $apiToken,
        private readonly string $tenant = 'newfactory',
        private readonly int $cacheTtl = 300,
    ) {}

    /**
     * Fetch a Strapi singleType (e.g. `homepage`, `kontakt-page`) for the
     * current tenant. Returns the attributes payload or null when Strapi
     * has no entry yet (HTTP 404, which is the v5 response for empty single
     * types).
     *
     * @return array<string, mixed>|null
     */
    public function singleType(string $singularName, array $populate = []): ?array
    {
        return $this->fetchCached(
            cacheKey: sprintf('strapi.%s.%s', $singularName, $this->tenant),
            url: sprintf('%s/api/%s', $this->baseUrl, $singularName),
            query: $this->buildQuery($populate),
        );
    }

    /**
     * Fetch a Strapi collectionType filtered to the current tenant.
     *
     * @return list<array<string, mixed>>
     */
    public function collection(string $pluralName, array $populate = [], int $limit = 100): array
    {
        $payload = $this->fetchCached(
            cacheKey: sprintf('strapi.%s.%s.collection', $pluralName, $this->tenant),
            url: sprintf('%s/api/%s', $this->baseUrl, $pluralName),
            query: array_merge(
                $this->buildQuery($populate),
                ['pagination[limit]' => $limit],
            ),
        );

        if ($payload === null) {
            return [];
        }
        return $payload['data'] ?? [];
    }

    /**
     * Invalidate cached reads for a content-type after a write so the next
     * page render fetches fresh data from Strapi. Called by CmsApiController
     * after a successful PATCH.
     *
     * Invalidates BOTH the singleType key (`strapi.<slug>.<tenant>`) and the
     * collection key (`strapi.<slug>.<tenant>.collection`) since callers
     * don't know which shape backs the entity.
     */
    public function invalidate(string $slug): void
    {
        $this->cache->delete(sprintf('strapi.%s.%s', $slug, $this->tenant));
        $this->cache->delete(sprintf('strapi.%s.%s.collection', $slug, $this->tenant));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchCached(string $cacheKey, string $url, array $query): ?array
    {
        if ($this->apiToken === '' || str_starts_with($this->apiToken, '__')) {
            return null;
        }

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($url, $query): ?array {
            $item->expiresAfter($this->cacheTtl);
            try {
                $response = $this->http->request('GET', $url, [
                    'query' => $query,
                    'headers' => ['Authorization' => 'Bearer '.$this->apiToken],
                    'timeout' => 5,
                ]);
                if ($response->getStatusCode() === 404) {
                    return null;
                }
                return $response->toArray();
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * @return array<string, string|int>
     */
    private function buildQuery(array $populate): array
    {
        $query = ['filters[tenant][$eq]' => $this->tenant];
        foreach ($populate as $i => $field) {
            $query[sprintf('populate[%d]', $i)] = $field;
        }
        return $query;
    }
}
