<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Benefit cards for the 'PROČ NÁS' grid on the homepage.
 *
 * Falls back to a hardcoded set of 6 benefits matching the Twig original
 * when Strapi is unreachable or empty.
 */
final class BenefitRepository
{
    public function __construct(
        private readonly StrapiContentClient $strapi,
    ) {}

    /**
     * @return list<array{title:string, body:string}>
     */
    public function all(): array
    {
        $entries = $this->strapi->collection(
            pluralName: 'newfactory-benefits',
            populate: [],
            limit: 100,
        );

        if ($entries === []) {
            return $this->fallback();
        }

        usort($entries, static function (array $a, array $b): int {
            $oa = (int) ($a['attributes']['order'] ?? $a['order'] ?? 0);
            $ob = (int) ($b['attributes']['order'] ?? $b['order'] ?? 0);
            return $oa <=> $ob;
        });

        $out = [];
        foreach ($entries as $e) {
            $attrs = $e['attributes'] ?? $e;
            $title = (string) ($attrs['title'] ?? '');
            $body = (string) ($attrs['body'] ?? '');
            if ($title === '' || $body === '') {
                continue;
            }
            $out[] = ['title' => $title, 'body' => $body];
        }

        return $out !== [] ? $out : $this->fallback();
    }

    /**
     * @return list<array{title:string, body:string}>
     */
    private function fallback(): array
    {
        return [
            [
                'title' => 'Cena elektřiny 5 Kč/kWh',
                'body' => 'Pevný tarif po celé délce smlouvy. Přesouvání zátěže do nočních špiček zdarma. Cena za reálnou spotřebu, ne paušál.',
            ],
            [
                'title' => '98 % garance uptime',
                'body' => 'Smluvní SLA. Při výpadku delším než 24 hodin kompenzace v BTC podle aktuální obtížnosti sítě.',
            ],
            [
                'title' => 'Bezplatný doživotní servis',
                'body' => 'Po skončení záruky výrobce přebíráme servis my. Bez dodatečných poplatků za diagnostiku ani drobné opravy.',
            ],
            [
                'title' => '12 měs. smlouva s pauzou',
                'body' => 'Kdykoliv můžete provoz pozastavit — při poklesu výnosnosti se účtování elektřiny zastaví.',
            ],
            [
                'title' => '24/7 monitoring + alerty',
                'body' => 'Reporting hashrate, výpadků a teploty na email. Vlastní dashboard s historií posledních 90 dní.',
            ],
            [
                'title' => 'Bezpečnost + záloha',
                'body' => 'Datacentrum s detekcí požáru, kamerovým systémem a záložním zdrojem. Pojištění stroje na celou pořizovací cenu.',
            ],
        ];
    }
}
