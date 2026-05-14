<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Static catalogue of mining hardware available at New Factory.
 *
 * Lives in-app for v1 — keeps tight delivery without requiring a new Strapi
 * content type or a CMS schema migration. Once the operator has a sense of
 * what specs to expose for editorial control, this graduates to Strapi.
 *
 * @return list<array{slug:string, name:string, manufacturer:string, algorithm:string, hashrate:string, power_w:int, dimensions:string, machine_price_czk:int, electricity_yearly_czk:int, service_lines:list<array{name:string,price_czk:int,is_optional:bool}>, short_description:string, image:string}>
 */
final class ProductCatalog
{
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        // Prices in Kč. service_lines are per-purchase add-ons; electricity is yearly @ ~5 Kč/kWh,
        // 24/7 uptime = power_w * 24 * 365 / 1000 * 5.
        return [
            [
                'slug' => 'antminer-s21-pro',
                'name' => 'Antminer S21 Pro',
                'manufacturer' => 'Bitmain',
                'algorithm' => 'SHA-256 (Bitcoin)',
                'hashrate' => '234 TH/s',
                'power_w' => 3531,
                'dimensions' => '400 × 195 × 290 mm',
                'machine_price_czk' => 169_900,
                'electricity_yearly_czk' => 154_650, // 3531W * 24h * 365d * 5 Kč / 1000
                'service_lines' => [
                    ['name' => 'Doprava ČR', 'price_czk' => 1_500, 'is_optional' => false],
                    ['name' => 'Instalace + provoz 24 měs.', 'price_czk' => 12_000, 'is_optional' => true],
                    ['name' => 'Rozšířená záruka 24 měs.', 'price_czk' => 8_000, 'is_optional' => true],
                ],
                'short_description' => 'Top-tier Bitcoin ASIC s nejlepším poměrem cena/výkon (74 J/TH).',
                'image' => 'antminer-s21-pro.webp',
            ],
            [
                'slug' => 'antminer-s21-hyd',
                'name' => 'Antminer S21 Hydro',
                'manufacturer' => 'Bitmain',
                'algorithm' => 'SHA-256 (Bitcoin)',
                'hashrate' => '335 TH/s',
                'power_w' => 5360,
                'dimensions' => '400 × 195 × 290 mm',
                'machine_price_czk' => 249_900,
                'electricity_yearly_czk' => 234_800,
                'service_lines' => [
                    ['name' => 'Doprava ČR', 'price_czk' => 2_000, 'is_optional' => false],
                    ['name' => 'Vodní okruh + instalace', 'price_czk' => 35_000, 'is_optional' => false],
                    ['name' => 'Rozšířená záruka 24 měs.', 'price_czk' => 12_000, 'is_optional' => true],
                ],
                'short_description' => 'Vodní chlazení — 36 % vyšší hashrate než air-cooled S21 při tichém provozu.',
                'image' => 'antminer-s21-hyd.webp',
            ],
            [
                'slug' => 'whatsminer-m60s',
                'name' => 'Whatsminer M60S',
                'manufacturer' => 'MicroBT',
                'algorithm' => 'SHA-256 (Bitcoin)',
                'hashrate' => '186 TH/s',
                'power_w' => 3441,
                'dimensions' => '430 × 200 × 290 mm',
                'machine_price_czk' => 139_900,
                'electricity_yearly_czk' => 150_700,
                'service_lines' => [
                    ['name' => 'Doprava ČR', 'price_czk' => 1_500, 'is_optional' => false],
                    ['name' => 'Instalace + provoz 24 měs.', 'price_czk' => 12_000, 'is_optional' => true],
                ],
                'short_description' => 'Alternativa k Antmineru — solidní 85 J/TH, dostupnější vstupní cena.',
                'image' => 'whatsminer-m60s.webp',
            ],
        ];
    }

    public function bySlug(string $slug): ?array
    {
        foreach ($this->all() as $p) {
            if ($p['slug'] === $slug) {
                return $p;
            }
        }
        return null;
    }
}
