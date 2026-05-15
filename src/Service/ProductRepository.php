<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Reads product catalogue from Strapi (`newfactory-product` collection),
 * normalises the v5 attribute shape into the legacy array shape that
 * `home/index.html.twig`, `product/list.html.twig` and `product/detail.html.twig`
 * already consume, and falls back to the hardcoded {@see ProductCatalog} when
 * Strapi is unreachable or has no entries yet.
 *
 * Result shape (intentionally unchanged vs. legacy ProductCatalog::all() so the
 * Strapi rollout is a drop-in swap):
 *
 *  array{
 *      slug:string, name:string, manufacturer:string, algorithm:string,
 *      hashrate:string, power_w:int, dimensions:string, weight_kg:float,
 *      release_date:string, currency_type:string, delivery:string,
 *      annual_btc:string, machine_price_czk:int, accessories_czk:int,
 *      electricity_yearly_czk:int,
 *      service_lines:list<array{name:string,price_czk:int,is_optional:bool}>,
 *      short_description:string, image:string
 *  }
 */
final class ProductRepository
{
    public function __construct(
        private readonly StrapiContentClient $strapi,
        private readonly ProductCatalog $fallback,
    ) {}

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $entries = $this->strapi->collection(
            pluralName: 'newfactory-products',
            populate: ['service_lines'],
            limit: 100,
        );

        if ($entries === []) {
            return $this->fallback->all();
        }

        $normalised = array_map([$this, 'normalise'], $entries);

        // Drop nulls (entries missing a slug etc.) and sort by `order` then by name
        $normalised = array_values(array_filter(
            $normalised,
            static fn (?array $p): bool => $p !== null,
        ));

        if ($normalised === []) {
            return $this->fallback->all();
        }

        usort($normalised, static function (array $a, array $b): int {
            return ($a['_order'] ?? 0) <=> ($b['_order'] ?? 0)
                ?: strcmp($a['name'], $b['name']);
        });

        // Strip internal sort key before returning
        return array_map(
            static function (array $p): array {
                unset($p['_order']);
                return $p;
            },
            $normalised,
        );
    }

    public function bySlug(string $slug): ?array
    {
        foreach ($this->all() as $p) {
            if (($p['slug'] ?? null) === $slug) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Map Strapi v5 entry to the legacy ProductCatalog shape.
     *
     * @param array<string, mixed> $entry
     * @return array<string, mixed>|null
     */
    private function normalise(array $entry): ?array
    {
        // Strapi v5 returns attributes flat (no `.attributes` wrapper for documents API).
        // Defensive: support both shapes.
        $attrs = $entry['attributes'] ?? $entry;
        if (!is_array($attrs) || empty($attrs['slug']) || empty($attrs['name'])) {
            return null;
        }

        $serviceLinesRaw = $attrs['service_lines'] ?? [];
        if (!is_array($serviceLinesRaw)) {
            $serviceLinesRaw = [];
        }
        $serviceLines = [];
        foreach ($serviceLinesRaw as $line) {
            if (!is_array($line) || empty($line['name'])) {
                continue;
            }
            $serviceLines[] = [
                'name' => (string) $line['name'],
                'price_czk' => (int) ($line['price_czk'] ?? 0),
                'is_optional' => (bool) ($line['is_optional'] ?? true),
            ];
        }

        // documentId is the Strapi v5 stable identifier the inline-edit
        // overlay uses to address a specific item in the collection
        // (data-cms-field="newfactory-products:<documentId>:<path>").
        // Fall back to the legacy numeric id only as a last resort —
        // documents API expects the documentId on PUT.
        $documentId = (string) ($attrs['documentId'] ?? $entry['documentId'] ?? $entry['id'] ?? '');

        return [
            'slug' => (string) $attrs['slug'],
            'documentId' => $documentId,
            'name' => (string) $attrs['name'],
            'manufacturer' => (string) ($attrs['manufacturer'] ?? ''),
            'algorithm' => (string) ($attrs['algorithm'] ?? 'SHA-256'),
            'currency_type' => (string) ($attrs['currency_type'] ?? 'Bitcoin'),
            'delivery' => (string) ($attrs['delivery'] ?? 'Standard'),
            'hashrate' => (string) ($attrs['hashrate'] ?? ''),
            'power_w' => (int) ($attrs['power_w'] ?? 0),
            'dimensions' => (string) ($attrs['dimensions'] ?? ''),
            'weight_kg' => (float) ($attrs['weight_kg'] ?? 0),
            'release_date' => (string) ($attrs['release_date'] ?? ''),
            'annual_btc' => (string) ($attrs['annual_btc'] ?? ''),
            'machine_price_czk' => (int) ($attrs['machine_price_czk'] ?? 0),
            'accessories_czk' => (int) ($attrs['accessories_czk'] ?? 0),
            'electricity_yearly_czk' => (int) ($attrs['electricity_yearly_czk'] ?? 0),
            'short_description' => (string) ($attrs['short_description'] ?? ''),
            'image' => (string) ($attrs['image'] ?? ''),
            'service_lines' => $serviceLines,
            '_order' => (int) ($attrs['order'] ?? 0),
        ];
    }
}
