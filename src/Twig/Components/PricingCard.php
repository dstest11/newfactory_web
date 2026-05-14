<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Renders 2bminer-style pricing breakdown for one mining product:
 *   Machine price + accessories (line items, optional/required) + yearly electricity.
 *
 * Used on /produkty and /produkty/{slug}.
 */
#[AsTwigComponent('PricingCard')]
final class PricingCard
{
    /** @var array<string, mixed> */
    public array $product;

    public bool $compact = false;

    public function totalCzk(): int
    {
        $sum = (int) $this->product['machine_price_czk'];
        foreach ($this->product['service_lines'] ?? [] as $line) {
            if (!($line['is_optional'] ?? false)) {
                $sum += (int) $line['price_czk'];
            }
        }
        return $sum;
    }

    public function totalWithOptionalCzk(): int
    {
        $sum = (int) $this->product['machine_price_czk'];
        foreach ($this->product['service_lines'] ?? [] as $line) {
            $sum += (int) $line['price_czk'];
        }
        return $sum;
    }

    public function electricityMonthlyCzk(): int
    {
        return (int) round(((int) $this->product['electricity_yearly_czk']) / 12);
    }
}
