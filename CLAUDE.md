# newfactory_web

Symfony 7.4 inline-editable marketing site for new-factory.cz — Czech crypto-mining hardware lead-gen. Victor 3D template ported from `/Users/mrfazolka/workspace/workspace-old/app/newfactory/www/petar11199.github.io/Victor/`.

## Tech stack

- Symfony 7.4 + PHP 8.4 (shared `nginx_fpm_shared` container, since 2026-05-15 cutover; image sha-4b6e368f). composer.json may still have `platform.php` pin from earlier work — keep it at 8.3.99 only if Symfony 7.x compatibility requires.
- Strapi v5 (shared instance at `strapi.dosmart.world`) for content + auth — **no local DB**
- `dosmart/cms-core-bundle` Plan 03 inline-edit overlay
- `dstest11/mail-http-sdk` (HTTPS → ell06 mail-http-api — DO blocks SMTP egress)
- Asset Mapper + Stimulus (no webpack)
- Twig + Twig UX Components (PricingCard)
- sentry/sentry-symfony → bugsink.dosmart.world

## Dev workflow (mandatory)

**Vývoj zásadně na locale.** All inline-edit, content-pipeline, and UI changes get iterated against `http://newfactory.localhost` first; production only receives code through the standard deploy pipeline (feature branch → PR → merge → `gh release create` → CI build-vendor → webhook). E2E full cycles (Strapi PATCH + restore, contentEditable flows) belong on localhost. Smoke checks on prod after deploy are fine.

To bootstrap locally: infra `.env.local` (gitignored — auto-loaded by `docker-compose.override.yml` as second env_file on `nginx_fpm_shared`) holds the real `NEWFACTORY_EDITOR_EMAILS` / `NEWFACTORY_EDITOR_PASSWORD_HASH` / `NEWFACTORY_APP_SECRET` / `NEWFACTORY_STRAPI_API_TOKEN` (see `!!!_credentials.md`). Without those, login is dead and products fall back to the hardcoded `ProductCatalog` (no `documentId` → no inline edit at all).

## Infrastructure

This app runs in the shared `nginx_fpm_shared` container.

- **Infra repo**: [../../CLAUDE.md](../../CLAUDE.md)
- **Container path**: `/srv/www/nginx/sites/newfactory_web`
- **Host path**: `/Users/mrfazolka/workspace/docker-compose-for-do-droplet/apps/newfactory_web`
- **Dev URL**: http://newfactory.localhost
- **Prod URL**: https://new-factory.cz
- **Staging URL**: https://staging.new-factory.cz

## Contact form & mail

The `/kontakt` page is a lead form using the **same approach as dosmart_web / mikamiho_web** (since 2026-06-15): a plain HTML form that POSTs via `fetch()` to a JSON endpoint and shows an **inline success/error message with no full-page reload**.

- **Routes** (`src/Controller/ContactController.php`): `GET /kontakt` (name `contact`, renders the page; accepts `?product=<slug>` to prefill the "Zájem o produkt" field) + `POST /kontakt-odeslat` (name `contact_submit`, returns `JsonResponse`).
- **Anti-spam / validation**: hidden `website` honeypot (bots fill it → silent `200`), per-IP rate limit (`contact_form`, 5/hour — `config/packages/rate_limiter.yaml`), and server-side validation (name ≥ 2 chars + valid e-mail required; phone/product/message optional). Validation errors → `422 {ok:false, errors}`.
- **Frontend**: `templates/contact/contact.html.twig` — plain `<form id="contactForm">` + the existing `.nf-contact__form` grid styling (element-selector CSS, so plain markup keeps the look) + an inline `<script>` that `fetch()`es the endpoint and writes into `#contact-form-status` (green + `form.reset()` on success, red on error).
- **Mail — ONLY via `dstest11/mail-http-sdk` → `mail-http-api` on ell06** (DigitalOcean blocks the outbound SMTP ports, so a direct SMTP transport does NOT work from the droplet — there is a commented-out SMTP `MAILER_DSN` in `.env` as the documented future alternative). `MAILER_DSN=mail-relay+https://${NEWFACTORY_MAIL_RELAY_API_KEY}@ell06.vas-server.cz/?source=newfactory_web`; recipient `NEWFACTORY_LEAD_RECIPIENT`, from `noreply@new-factory.cz`. Dev `.env.local` overrides `MAILER_DSN=null://null`.
- **Key provisioning**: `NEWFACTORY_MAIL_RELAY_API_KEY` is the **shared** mail-http-api key — infra `deploy.sh` sources it from the live `STAVSYS_MAIL_RELAY_API_KEY` (newfactory's own GH secret was a `__PENDING__` placeholder), and `EXTRA_ENV_PATTERN=NEWFACTORY_` flows it into `.env.local`. The `?source=` is an audit label only; the key auth is shared across apps.
- **History**: until 2026-06-15 this was a full-page Symfony Form (`ContactType` + CSRF + flash + redirect) on `MAILER_DSN=null://null` — built but never wired, so it silently dropped leads. Refactored to the fetch+JSON+inline approach + activated (newfactory_web #22/#23, infra #313/#315/#316).

## Spec + plans

- Design spec: [docs/superpowers/specs/2026-05-14-newfactory-web-design.md](../../docs/superpowers/specs/2026-05-14-newfactory-web-design.md)
- Plan 1 (wizard audit): [docs/superpowers/plans/2026-05-14-newfactory-web-plan-1-wizard-audit.md](../../docs/superpowers/plans/2026-05-14-newfactory-web-plan-1-wizard-audit.md)
- Plan 2 (this app build): [docs/superpowers/plans/2026-05-14-newfactory-web-plan-2-app-build.md](../../docs/superpowers/plans/2026-05-14-newfactory-web-plan-2-app-build.md)
- Plan 3 (production provisioning): [docs/superpowers/plans/2026-05-14-newfactory-web-plan-3-production-provisioning.md](../../docs/superpowers/plans/2026-05-14-newfactory-web-plan-3-production-provisioning.md)

## Commands

```bash
make install        # composer install inside container (needs COMPOSER_AUTH env var with gh token)
make test           # PHPUnit
make cache-clear
make console CMD="debug:router"
make php-bash
```

## Gotchas

- **Deploy source vs infra mirror** — this app is deployed from the standalone repo `dstest11/newfactory_web` (per the webhook `REPO_URL`). The copy at `apps/newfactory_web` INSIDE the infra repo (`docker-compose-for-do-droplet`) is a **mirror used for local dev only** (the shared container mounts it). Prod-affecting changes MUST go to `dstest11/newfactory_web` (the deploy source); commit the same change to the infra mirror to keep local dev in sync. The two can (and did) diverge — e.g. the mirror's `.env` keeps `MAILER_DSN=null://null` for local dev while the deploy repo composes the real DSN.
- `dstest11/mail-http-sdk` vendor name differs from PHP namespace: bundle class is `Dosmart\MailHttpSdk\Bundle\MailRelayBundle`.
- `dosmart/cms-core-bundle` is a private Composer VCS dep — needs `COMPOSER_AUTH` (gh token with Contents:Read on dstest11/cms-core-bundle).
- No local DB. Strapi is truth. File-based sessions.
- Local dev requires `127.0.0.1 newfactory.localhost` in `/etc/hosts` (Traefik routes by Host header).
- **Victor SPA wheel + paged-scrollable sections** — `public/victor/main.js::windowWheelOrTouch` handles BOTH page-nav (sceneMovedAmmount++) AND inner overflow on `.content-section--scrollable`. The handler now early-returns when `e.target.closest('.content-section--scrollable')` matches and the inner content can still scroll in the wheel direction. Page-nav only fires once the inner scrollTop hits the top/bottom boundary. Don't reintroduce a unilateral `e.preventDefault()` or move the listener off `window` — both break the boundary behavior. Full rationale + verification: [docs/CHANGES_2026-05-19.md](docs/CHANGES_2026-05-19.md).
- **`/victor/*` cache busting** — nginx ships `/victor/*` with `Cache-Control: max-age=432000` (5 d). Bump `?v=…` on the main.js script tag in `templates/home/_victor_base.html.twig` whenever any `/victor/*.js` changes, otherwise returning visitors get the cached old file for up to 5 days.
- **Fixed-content-header `:has()` collapse** — Default `padding-top: 10px`; `:has(.content-section[data-page="0"]:not(.section--hidden))` expands to `5%` only while the hero is active. Editing the override or the data-page numbering will break the collapse animation.
