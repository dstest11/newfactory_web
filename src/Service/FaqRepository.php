<?php

declare(strict_types=1);

namespace App\Service;

/**
 * FAQ entries grouped by `location` (`list` for /produkty general FAQ,
 * `detail` for /produkty/{slug} machine-specific FAQ).
 *
 * Falls back to a hardcoded set when Strapi is unreachable so the FAQ section
 * is never blank in production. Hardcoded copy mirrors what was in the Twig
 * templates before the Strapi migration.
 */
final class FaqRepository
{
    public function __construct(
        private readonly StrapiContentClient $strapi,
    ) {}

    /**
     * @return list<array{question:string, answer:string}>
     */
    public function forLocation(string $location): array
    {
        $entries = $this->strapi->collection(
            pluralName: 'newfactory-faqs',
            populate: [],
            limit: 100,
        );

        $filtered = array_values(array_filter(
            $entries,
            static fn (array $e): bool => (($e['attributes']['location'] ?? $e['location'] ?? null) === $location),
        ));

        if ($filtered === []) {
            return $this->fallback($location);
        }

        usort($filtered, static function (array $a, array $b): int {
            $oa = (int) ($a['attributes']['order'] ?? $a['order'] ?? 0);
            $ob = (int) ($b['attributes']['order'] ?? $b['order'] ?? 0);
            return $oa <=> $ob;
        });

        $out = [];
        foreach ($filtered as $e) {
            $attrs = $e['attributes'] ?? $e;
            $question = (string) ($attrs['question'] ?? '');
            $answer = (string) ($attrs['answer'] ?? '');
            if ($question === '' || $answer === '') {
                continue;
            }
            $out[] = ['question' => $question, 'answer' => $answer];
        }

        return $out !== [] ? $out : $this->fallback($location);
    }

    /**
     * @return list<array{question:string, answer:string}>
     */
    private function fallback(string $location): array
    {
        if ($location === 'detail') {
            return [
                [
                    'question' => 'Kdy reálně začne stroj těžit po objednávce?',
                    'answer' => 'Při stavu skladem do 48 hodin od přijetí platby. Při dovozu z výroby 14–21 dní (zahrnuto v Standard delivery). Aktivace v hosting datacentru je vždy stejný den, kdy stroj dorazí.',
                ],
                [
                    'question' => 'Co se stane, pokud kurz BTC propadne a mining přestane být ziskový?',
                    'answer' => 'Provoz lze kdykoliv pozastavit. Během pauzy se neúčtuje hosting ani elektřina. Stroj zůstává ve vlastnictví a fyzicky v datacentru. Při návratu výnosnosti znovu spustíme bez setupu.',
                ],
                [
                    'question' => 'Můžu si stroj odvézt domů?',
                    'answer' => 'Ano, ALL-IN cena zahrnuje dopravu k vám. V tom případě odpadají měsíční hosting + monitoring poplatky. Doporučujeme však profesionální hosting — domácí provoz znamená hluk 70+ dB, vysokou teplotu a 3 kW konstantního příkonu (ASIC = ne pro byt).',
                ],
                [
                    'question' => 'Jaké jsou platební podmínky?',
                    'answer' => '50 % ALL-IN při objednávce, 50 % při fyzickém dovozu stroje do ČR. Platby bankovním převodem v CZK nebo přímo BTC podle aktuálního kurzu.',
                ],
            ];
        }

        // location = 'list' (default)
        return [
            [
                'question' => 'Je všechno v ALL-IN ceně, nebo budou další faktury?',
                'answer' => 'ALL-IN cena zahrnuje stroj, příslušenství, leteckou dopravu (DDP — paid by us), pojištění zásilky a vyřízení cla + DPH. Žádný další jednorázový poplatek z naší strany. Volitelné měsíční služby (hosting, monitoring, údržba) si určujete při poptávce.',
            ],
            [
                'question' => 'Jak vysoký je tarif za elektřinu?',
                'answer' => 'Pevný tarif 5 Kč/kWh pro celou délku 12měsíční smlouvy. Při prodloužení se sazba reviduje na základě aktuálních cen — nikdy ne v průběhu smlouvy.',
            ],
            [
                'question' => 'Co když stroj poruchu nebo přestane těžit?',
                'answer' => 'U strojů s aktivní zárukou výrobce vyřizujeme reklamaci za vás. Po skončení záruky pokračujeme bezplatným doživotním servisem v rámci naší standardní podpory.',
            ],
            [
                'question' => 'Můžu provoz pozastavit, když mining přestane být ziskový?',
                'answer' => 'Ano. Smlouva má 12měsíční délku, ale provoz lze kdykoliv pozastavit — během pauzy se neúčtuje elektřina ani hosting.',
            ],
            [
                'question' => 'Co je v 98% SLA garanci uptime?',
                'answer' => 'Smluvní garance, že stroj poběží minimálně 98 % času každého fakturačního měsíce. Při delším výpadku kompenzujeme zákazníkovi výnos podle aktuální obtížnosti sítě a kurzu BTC.',
            ],
        ];
    }
}
