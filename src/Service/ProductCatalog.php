<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Static catalogue of mining hardware available at New Factory.
 *
 * Service-line breakdown follows the standard Czech mining-hosting industry
 * pattern: stroj + doprava + pojištění + clo + hosting + kabeláž + instalace
 * + monitoring + údržba + firmware optimalizace + servis na životnost + garance
 * provozuschopnosti. Each line item is optional vs. required per the
 * `is_optional` flag — used by PricingCard to compute base vs. all-in price.
 *
 * @return list<array{slug:string, name:string, manufacturer:string, algorithm:string, hashrate:string, power_w:int, dimensions:string, weight_kg:float, release_date:string, currency_type:string, delivery:string, annual_btc:string, machine_price_czk:int, accessories_czk:int, electricity_yearly_czk:int, service_lines:list<array{name:string,price_czk:int,is_optional:bool}>, short_description:string, image:string}>
 */
final class ProductCatalog
{
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return [
            [
                'slug' => 'antminer-s21-pro',
                'name' => 'Antminer S21 Pro',
                'manufacturer' => 'Bitmain',
                'algorithm' => 'SHA-256',
                'currency_type' => 'Bitcoin',
                'delivery' => 'Fast Track',
                'hashrate' => '234 TH/s',
                'power_w' => 3531,
                'dimensions' => '400 × 195 × 290 mm',
                'weight_kg' => 14.2,
                'release_date' => '2026-Q1',
                'annual_btc' => '≈ 0,21 BTC / rok',
                'machine_price_czk' => 169_900,
                'accessories_czk' => 4_500,
                'electricity_yearly_czk' => 154_650,
                'service_lines' => [
                    ['name' => 'Letecká doprava (DDP)',          'price_czk' => 9_500,  'is_optional' => false],
                    ['name' => 'Pojištění zásilky',                'price_czk' => 1_200,  'is_optional' => false],
                    ['name' => 'Clo + DPH (vyřízení)',             'price_czk' => 3_900,  'is_optional' => false],
                    ['name' => 'Rezervace hosting slotu',          'price_czk' => 3_500,  'is_optional' => true],
                    ['name' => 'Kabeláž a zapojení',                'price_czk' => 1_500,  'is_optional' => true],
                    ['name' => 'Instalace a setup',                 'price_czk' => 5_000,  'is_optional' => true],
                    ['name' => 'Monitoring 24/7 (12 měs.)',         'price_czk' => 5_900,  'is_optional' => true],
                    ['name' => 'Pravidelná údržba (12 měs.)',       'price_czk' => 3_600,  'is_optional' => true],
                    ['name' => 'Firmware optimalizace',             'price_czk' => 3_500,  'is_optional' => true],
                    ['name' => 'Servis na životnost stroje',        'price_czk' => 11_900, 'is_optional' => true],
                    ['name' => 'Garance provozuschopnosti 98 %',    'price_czk' => 8_400,  'is_optional' => true],
                ],
                'short_description' => 'Top-tier Bitcoin ASIC s nejlepším poměrem cena/výkon (74 J/TH). Nasazení do datacentra do 14 dnů.',
                'image' => 'antminer-s21-pro.webp',
            ],
            [
                'slug' => 'antminer-s21-hyd',
                'name' => 'Antminer S21 Hydro',
                'manufacturer' => 'Bitmain',
                'algorithm' => 'SHA-256',
                'currency_type' => 'Bitcoin',
                'delivery' => 'Standard',
                'hashrate' => '335 TH/s',
                'power_w' => 5360,
                'dimensions' => '400 × 195 × 290 mm',
                'weight_kg' => 15.5,
                'release_date' => '2026-Q2',
                'annual_btc' => '≈ 0,31 BTC / rok',
                'machine_price_czk' => 249_900,
                'accessories_czk' => 8_500,
                'electricity_yearly_czk' => 234_800,
                'service_lines' => [
                    ['name' => 'Letecká doprava (DDP)',          'price_czk' => 12_500, 'is_optional' => false],
                    ['name' => 'Pojištění zásilky',                'price_czk' => 1_800,  'is_optional' => false],
                    ['name' => 'Clo + DPH (vyřízení)',             'price_czk' => 5_400,  'is_optional' => false],
                    ['name' => 'Vodní okruh + radiátor',           'price_czk' => 22_900, 'is_optional' => false],
                    ['name' => 'Rezervace hosting slotu',          'price_czk' => 4_500,  'is_optional' => true],
                    ['name' => 'Kabeláž + hydraulika',              'price_czk' => 3_900,  'is_optional' => true],
                    ['name' => 'Instalace a setup',                 'price_czk' => 12_000, 'is_optional' => true],
                    ['name' => 'Monitoring 24/7 (12 měs.)',         'price_czk' => 7_900,  'is_optional' => true],
                    ['name' => 'Pravidelná údržba (12 měs.)',       'price_czk' => 4_800,  'is_optional' => true],
                    ['name' => 'Firmware optimalizace',             'price_czk' => 3_500,  'is_optional' => true],
                    ['name' => 'Servis na životnost stroje',        'price_czk' => 14_900, 'is_optional' => true],
                    ['name' => 'Garance provozuschopnosti 99 %',    'price_czk' => 12_900, 'is_optional' => true],
                ],
                'short_description' => 'Vodní chlazení — 36 % vyšší hashrate než air-cooled S21 při tichém provozu. Vhodné pro vlastní serverovnu.',
                'image' => 'antminer-s21-hyd.webp',
            ],
            [
                'slug' => 'whatsminer-m60s',
                'name' => 'Whatsminer M60S',
                'manufacturer' => 'MicroBT',
                'algorithm' => 'SHA-256',
                'currency_type' => 'Bitcoin',
                'delivery' => 'Fast Track',
                'hashrate' => '186 TH/s',
                'power_w' => 3441,
                'dimensions' => '430 × 200 × 290 mm',
                'weight_kg' => 13.8,
                'release_date' => '2026-Q1',
                'annual_btc' => '≈ 0,17 BTC / rok',
                'machine_price_czk' => 139_900,
                'accessories_czk' => 3_900,
                'electricity_yearly_czk' => 150_700,
                'service_lines' => [
                    ['name' => 'Letecká doprava (DDP)',          'price_czk' => 8_500,  'is_optional' => false],
                    ['name' => 'Pojištění zásilky',                'price_czk' => 1_100,  'is_optional' => false],
                    ['name' => 'Clo + DPH (vyřízení)',             'price_czk' => 3_200,  'is_optional' => false],
                    ['name' => 'Rezervace hosting slotu',          'price_czk' => 3_200,  'is_optional' => true],
                    ['name' => 'Kabeláž a zapojení',                'price_czk' => 1_500,  'is_optional' => true],
                    ['name' => 'Instalace a setup',                 'price_czk' => 4_800,  'is_optional' => true],
                    ['name' => 'Monitoring 24/7 (12 měs.)',         'price_czk' => 5_900,  'is_optional' => true],
                    ['name' => 'Pravidelná údržba (12 měs.)',       'price_czk' => 3_600,  'is_optional' => true],
                    ['name' => 'Firmware optimalizace',             'price_czk' => 3_500,  'is_optional' => true],
                    ['name' => 'Servis na životnost stroje',        'price_czk' => 9_900,  'is_optional' => true],
                    ['name' => 'Garance provozuschopnosti 97 %',    'price_czk' => 6_900,  'is_optional' => true],
                ],
                'short_description' => 'Alternativa k Antmineru — solidní 85 J/TH, dostupnější vstupní cena, totožný hosting + servisní rámec.',
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
