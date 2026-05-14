# Správa obsahu webu new-factory.cz

Tento návod popisuje, kde a jak měnit obsah webu **new-factory.cz** bez nutnosti
sahat do kódu. Veškerý obsah se spravuje v Strapi v5 administraci.

## Přihlášení do Strapi

- URL: <https://strapi.dosmart.world/admin>
- Přihlašovací údaje superadmina jsou uloženy v `~/workspace/!!!_credentials.md` (sekce „Strapi v5“) — pokud k souboru nemáte přístup, kontaktujte správce.

> Tip: Strapi automaticky odhlásí po několika hodinách nečinnosti. Sezení vám zůstane platné, dokud okno nezavřete.

## Co kde editovat

Web má 5 sekcí ovládaných ze Strapi:

| Typ obsahu (Strapi) | Co ovlivňuje | Frekvence změn |
|---|---|---|
| **Newfactory homepage** (single-type) | Hero (Sekce 1 STROJE) — eyebrow, dvouřádkový titulek, lead. Plus titulky a perex pro sekce PROČ NÁS, NÁŠ TÝM a KONTAKT. | Občas |
| **Newfactory products** (collection) | Stroje viditelné v Sekci 1, Sekci 2 (kompaktní triptych) a na `/produkty` + `/produkty/{slug}`. Včetně cen, hashrate, příkonu, ALL-IN výpočtů. | Často |
| **Newfactory FAQs** (collection) | Sekce „Co se obvykle ptáme“ pod listem strojů (`location = list`) a pod detailem stroje (`location = detail`). | Sem tam |
| **Newfactory benefits** (collection) | 6 kartiček v Sekci 3 „PROČ PRÁVĚ NEW FACTORY“ na homepage. | Občas |

Ostatní části webu (Sekce 4 TÝM, popup CO NABÍZÍME, formulář v `/kontakt`) jsou
stále v šablonách. Pokud je budete chtít také přesunout do Strapi, řekněte
si — je to dalších cca 30 min práce.

## Postupy

### Změna titulku / leadu na homepage

1. Strapi → Content Manager → **Newfactory homepage** (Single Types).
2. Najděte pole, které chcete změnit:
   - `hero_eyebrow` — drobný popisek nad titulkem (např. `[NABÍDKA / 03]`).
   - `hero_title_top` — první řádek velkého titulku (`STROJE`).
   - `hero_title_bottom` — druhý řádek (`NA TĚŽBU`).
   - `hero_lead` — odstavec pod titulkem.
   - `benefits_title` / `benefits_lead` — Sekce 3 PROČ NÁS.
   - `team_title` / `team_lead` — Sekce 4 NÁŠ TÝM.
   - `contact_title` / `contact_lead` — Sekce 5 KONTAKT.
3. Klikněte **Save** (uloží draft) a poté **Publish** (zveřejní). Bez publish se na webu nic neukáže.

### Přidání nového stroje

1. Content Manager → **Newfactory products** → **+ Create new entry**.
2. Vyplňte:
   - `name` (např. „Antminer S21 Pro“).
   - `slug` se vygeneruje automaticky z `name`. Zkontrolujte, že neobsahuje diakritiku — pokud ano, ručně přepište (`antminer-s21-pro`).
   - `manufacturer` — výrobce (Bitmain, MicroBT…).
   - `algorithm` — `SHA-256` (default), případně jiný.
   - `currency_type` — `Bitcoin` (default).
   - `delivery` — `Fast Track` nebo `Standard` (ovlivňuje barvu badge).
   - `hashrate` — řetězec `číslo + mezera + jednotka`, např. `234 TH/s`. Toto je důležité — šablona to splituje podle mezery.
   - `power_w` — příkon ve wattech (celé číslo, např. `3531`).
   - `dimensions` — volné textové pole (např. `400 × 195 × 290 mm`).
   - `weight_kg` — desetinné číslo.
   - `release_date` — řetězec typu `2026-Q1`.
   - `annual_btc` — řetězec např. `≈ 0,21 BTC / rok`.
   - `machine_price_czk` — cena stroje (celé Kč).
   - `accessories_czk` — příslušenství (celé Kč).
   - `electricity_yearly_czk` — odhad ročních nákladů na elektřinu.
   - `short_description` — krátký popis (2–3 věty).
   - `image` — název souboru (např. `antminer-s21-pro.webp`) nebo plné URL na CDN. Skutečné obrázky stále leží v `public/victor/...` v repu webu, zatím se neuploadují ze Strapi.
   - `order` — pořadí v listu (0 = první). Nechte 0, pokud chcete defaultní řazení.
3. **Service lines** (rozpis služeb) — sekce „Doprava + clo + pojištění“ a volitelné položky. Klikněte **Add an entry**, vyplňte `name`, `price_czk`, `is_optional` (false = povinné, počítá se do ALL-IN ceny; true = volitelné).
4. **Save** → **Publish**.

### Přidání FAQ položky

1. Content Manager → **Newfactory FAQs** → **+ Create new entry**.
2. Vyplňte:
   - `question` — otázka.
   - `answer` — odpověď.
   - `location` — `list` (zobrazí se na `/produkty`) nebo `detail` (zobrazí se pod každým detailem stroje).
   - `order` — pořadí (0 = první).
3. **Save** → **Publish**.

### Přidání benefit kartičky (PROČ NÁS)

1. Content Manager → **Newfactory benefits** → **+ Create new entry**.
2. Vyplňte `title` (krátký nadpis), `body` (popis) a `order` (pořadí).
3. **Save** → **Publish**.

## Workflow draft → publish

Strapi pracuje ve dvou stavech:

- **Draft** = uloženo, ale neviditelné na webu.
- **Published** = viditelné na webu.

Pro každou změnu obsahu musíte kliknout **Publish**. Pokud chcete změnu rychle vrátit, klikněte **Unpublish** — entry zmizí z webu, ale zůstane v databázi.

## Jak dlouho trvá, než se změna ukáže

Web cachuje odpovědi ze Strapi po **5 minut**. Po publish vyčkejte do 5 minut a obnovte stránku v incognito (aby vám nedělal vrásky cache prohlížeče).

Pokud potřebujete změnu rychleji, požádejte správce, aby vyresetoval cache:
```bash
docker exec nginx_fpm_shared sh -c "cd /srv/www/nginx/sites/newfactory_web && php bin/console cache:pool:clear cache.app"
```

## Co se stane, když Strapi spadne nebo nepublikujete obsah

Web má **bezpečnostní záchrannou síť**: pokud Strapi neodpovídá nebo daný content type je prázdný, web zobrazí zabudovaný defaultní text. Nic nevypadne, jen uvidíte historickou kopii.

To znamená:

- Pokud zapomenete publish u homepage entry → web zobrazí původní hardkódovaný text z dob před Strapi migrací.
- Pokud nevyplníte žádnou FAQ → zobrazí se defaultních 5 (list) nebo 4 (detail) otázek.
- Pokud Strapi spadne → web jede dál.

Po dokončení **prvního naplnění obsahu** (viz níže) vás web bude reálně tahat ze Strapi.

## První naplnění obsahu po nasazení

Strapi má prázdné databáze pro nové content types. Než se ukáže reálná editovaná verze, musíte:

1. V **Newfactory homepage** vyplnit aspoň pole `tenant=newfactory` a `hero_title_top` + `hero_title_bottom` + `hero_lead`. Klikněte **Publish**.
2. V **Newfactory products** vytvořit minimálně 3 entries (Antminer S21 Pro, Antminer S21 Hydro, Whatsminer M60S) podle obsahu, který nyní vidíte na webu. Klikněte **Publish** u každého.
3. V **Newfactory FAQs** založit FAQ entries (alespoň 5 pro `list`, 4 pro `detail`).
4. V **Newfactory benefits** založit 6 entries pro PROČ NÁS grid.

Dokud krok není dokončen, web ukazuje hardkódovanou defaultní kopii.

## API token a co dělat při jeho rotaci

Web čte ze Strapi pomocí API tokenu pojmenovaného `tenant:newfactory`. Token je uložen v GitHub Secrets jako `NEWFACTORY_STRAPI_API_TOKEN` a propagován při každém deployi do produkce.

Pokud potřebujete token vyměnit (např. po incidentu):

1. Strapi → Settings → API Tokens → najít `tenant:newfactory` → **Regenerate**.
2. Zkopírujte nový token.
3. V GitHubu: `gh secret set NEWFACTORY_STRAPI_API_TOKEN --repo dstest11/docker-compose-for-do-droplet`.
4. Spustit deploy: `gh workflow run "Deploy Infrastructure to DigitalOcean"`.
5. Lokálně přepište `apps/newfactory_web/.env.local` (ten není v gitu, není deploylý — slouží jen vám).

**POZOR**: nový token musí mít přesně jméno `tenant:newfactory` (lowercase, dvojtečka) — Strapi tenant plugin podle jména filtruje data.

## Časté pasti

- **„Uložil jsem, ale na webu nic nevidím."** — Pravděpodobně jste neklikli **Publish** (jen **Save**). Zkontrolujte modrou hlavičku entry — musí být zelená „Published".
- **„Slug se mi v URL ukazuje s diakritikou."** — Strapi auto-generuje slug z `name`. Pokud jméno obsahovalo diakritiku, ručně upravte pole `slug` na ASCII variantu před publish.
- **„Změnil jsem cenu, ale web ji ukazuje starou."** — Cache 5 minut. Vyčkejte nebo si nechte vyčistit cache (viz výše).
- **„Smazal jsem entry, ale stejně vidím obsah."** — Web aktivoval fallback (defaultní kopie). Buď entry znovu vytvořte, nebo se smiřte s tím, že fallback zobrazuje starou kopii.

## Související dokumentace

- Plné technické pozadí (Strapi v5 + tenant plugin): [`apps/strapi_v5/CLAUDE.md`](../../strapi_v5/CLAUDE.md)
- Pipeline deployu: [`DEPLOYMENT.md`](../../../DEPLOYMENT.md)
- Spec webu: [`docs/superpowers/specs/2026-05-14-newfactory-web-design.md`](../../../docs/superpowers/specs/2026-05-14-newfactory-web-design.md)
