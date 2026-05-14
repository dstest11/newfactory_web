<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Hero / section copy for the homepage. Reads the `newfactory-homepage`
 * Strapi singleType. Falls back to a hardcoded copy snapshot (matching the
 * pre-Strapi Twig template) when Strapi is unreachable or the entry is empty.
 *
 * Returns a flat array<string,string> with all the editorial fields the
 * template references. Templates use `{{ homepage.hero_title_top|default('STROJE') }}`
 * so every key has a built-in template-side guard too.
 */
final class HomepageRepository
{
    public function __construct(
        private readonly StrapiContentClient $strapi,
    ) {}

    /** @return array<string, string> */
    public function get(): array
    {
        $payload = $this->strapi->singleType('newfactory-homepage');
        $data = $payload['data'] ?? null;
        if (!is_array($data) || $data === []) {
            return $this->fallback();
        }

        // Strapi v5 singletype response: data.<field> (flat) — defensive against legacy `.attributes`
        $attrs = $data['attributes'] ?? $data;
        $merged = $this->fallback();
        foreach ($merged as $k => $_) {
            if (isset($attrs[$k]) && is_string($attrs[$k]) && $attrs[$k] !== '') {
                $merged[$k] = (string) $attrs[$k];
            }
        }
        return $merged;
    }

    /** @return array<string, string> */
    private function fallback(): array
    {
        return [
            'hero_eyebrow' => '[NABÍDKA / 03]',
            'hero_title_top' => 'STROJE',
            'hero_title_bottom' => 'NA TĚŽBU',
            'hero_lead' => 'Tři ASIC mineři. Plně transparentní all-in cena — stroj + doprava + clo + pojištění v jednom čísle. Hosting, monitoring a servis si vybíráte sami.',
            'benefits_title' => 'PROČ PRÁVĚ NEW FACTORY',
            'benefits_lead' => 'Šest věcí, které dělají rozdíl mezi solidním a špatným hosting providerem.',
            'team_title' => 'NÁŠ TÝM',
            'team_lead' => 'Firma stojí na lidech, ne na strojích. Náš tým má za sebou roky praxe v mining, hardware servisu i obchodě. Stroj prodáme i tomu, kdo nikdy nemíchal kryptoměnu — provedeme vás od první kalkulace po první vytěžený Bitcoin.',
            'contact_title' => 'KONTAKT',
            'contact_lead' => 'Máme rádi vaše otázky — ozveme se do 24 hodin. Konzultace zdarma.',
        ];
    }
}
