# newfactory_web

Symfony 7.4 inline-editable marketing site for new-factory.cz — Czech crypto-mining hardware lead-gen. Victor 3D template ported from `/Users/mrfazolka/workspace/workspace-old/app/newfactory/www/petar11199.github.io/Victor/`.

## Tech stack

- Symfony 7.4 + PHP `^8.3` (platform pin `8.3.30` until shared container bumps)
- Strapi v5 (shared instance at `strapi.dosmart.world`) for content + auth — **no local DB**
- `dosmart/cms-core-bundle` Plan 03 inline-edit overlay
- `dstest11/mail-http-sdk` (HTTPS → ell06 mail-http-api — DO blocks SMTP egress)
- Asset Mapper + Stimulus (no webpack)
- Twig + Twig UX Components (PricingCard)
- sentry/sentry-symfony → bugsink.dosmart.world

## Infrastructure

This app runs in the shared `nginx_fpm_shared` container.

- **Infra repo**: [../../CLAUDE.md](../../CLAUDE.md)
- **Container path**: `/srv/www/nginx/sites/newfactory_web`
- **Host path**: `/Users/mrfazolka/workspace/docker-compose-for-do-droplet/apps/newfactory_web`
- **Dev URL**: http://newfactory.localhost
- **Prod URL**: https://new-factory.cz
- **Staging URL**: https://staging.new-factory.cz

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

- `dstest11/mail-http-sdk` vendor name differs from PHP namespace: bundle class is `Dosmart\MailHttpSdk\Bundle\MailRelayBundle`.
- `dosmart/cms-core-bundle` is a private Composer VCS dep — needs `COMPOSER_AUTH` (gh token with Contents:Read on dstest11/cms-core-bundle).
- No local DB. Strapi is truth. File-based sessions.
- Local dev requires `127.0.0.1 newfactory.localhost` in `/etc/hosts` (Traefik routes by Host header).
