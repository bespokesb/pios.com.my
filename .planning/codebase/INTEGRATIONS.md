# External Integrations

**Analysis Date:** 2026-07-01

PIOS is a mostly self-contained Laravel + Filament back-office. It has **no payment gateway** and **no third-party auth provider**. External touch points are: the UstazAI WhatsApp blaster, WhatsApp deep-link sharing, outbound mail, and the production hosting/deploy stack (nginx + Cloudflare + systemd queue/scheduler).

## APIs & External Services

**UstazAI WhatsApp Blaster (ustazai.my):**
- Purpose: send WhatsApp broadcasts to a Business Owner's customer list.
- Client: `app/Services/UstazAiBlaster.php` — uses Laravel's `Http` facade (`Http::acceptJson()->withToken(...)->post(...)`).
- Shared config (`config/services.php` `ustazai` block): `base_url` (env `USTAZAI_BASE_URL`, default `https://ustazai.my/api`) and `endpoint` (env `USTAZAI_SEND_ENDPOINT`, default `/send`). **Only the URL is shared.**
- Per-Business-Owner credentials: each `BusinessOwner` has its **own** UstazAI account. `ustazai_api_key` and `ustazai_sender` live on the `business_owners` record (`app/Models/BusinessOwner.php`). The API key is **encrypted at rest** — cast `'ustazai_api_key' => 'encrypted'` (`app/Models/BusinessOwner.php:116`), so it is stored using Laravel's `APP_KEY` encryption, never in plaintext.
- Contract status: `blast()` posts a JSON payload of `{sender, recipients[], message}` with bearer-token auth. Marked with a `// TODO: confirm endpoint + payload against UstazAI's API docs` — the exact endpoint/payload is a **sensible default not yet verified** against the real UstazAI API. `isConfiguredFor()` gates sending on a present API key.
- Return shape: `array{ok, sent, failed, message}`.

**WhatsApp deep-link sharing (no API):**
- Purpose: click-to-chat / share links to individual customers, independent of the blaster.
- Helper: `app/Support/Phone.php` normalizes Malaysian mobile numbers to `wa.me` intl digits (`toWhatsApp()` → e.g. `60123456789`, default country code `60`) and to `tel:` E.164 form (`toTel()` → `+60123456789`). This is pure string handling — no outbound request.

## Data Storage

**Database:**
- MySQL database `pios_my` (prod MySQL user `pios`). Connection via standard Laravel `DB_*` env vars (`config/database.php`).
- Also backs **queue**, **cache**, and **session** (all default to the `database` driver — `config/queue.php`, `config/cache.php`, `config/session.php`). No Redis.

**File Storage:**
- Local filesystem only (`config/filesystems.php` default `local`). No S3/cloud object storage configured (the `ses` block in `config/services.php` is for mail, not storage).

**Caching:**
- Database-backed cache store. No dedicated cache server.

## Authentication & Identity

**Auth Provider:**
- Custom / built-in Laravel auth (`config/auth.php`), surfaced through Filament panel login. No external IdP / OAuth / SSO.
- Impersonation via `stechstudio/filament-impersonate` (admin-initiated, in-app only).
- Single shared `web` session across the three Filament panels (`/admin`, `/partner`, `/business`); role gates access per panel.

## Mail

- Configured in `config/mail.php`. Default mailer from env `MAIL_MAILER` (falls back to `log`).
- Supported transports pre-wired: `smtp`, `ses`, `postmark`, `resend`, `sendmail`, `log`, `array`, plus `failover`/`roundrobin`. Credentials for `postmark`, `resend`, and `ses` are read from `config/services.php` (env-driven; none committed).
- Used for Laravel's **password-reset** flow and Filament notifications. In tests, `MAIL_MAILER=array` (`phpunit.xml`) so no mail is actually sent.
- Global from-address via `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME`.

## Monitoring & Observability

**Error Tracking:**
- None external (no Sentry/Bugsnag). `nunomaduro/collision` for CLI error output only.
- Pulse / Telescope / Nightwatch are referenced as env toggles in `phpunit.xml` (all disabled) but the packages are **not installed** — these are Laravel defaults, not active integrations.

**Logs:**
- Standard Laravel logging (`config/logging.php`). `laravel/pail` tails logs in dev (`php artisan pail`, part of `composer run dev`).

## CI/CD & Deployment

**Hosting (production):**
- `https://pios.com.my`, **Cloudflare-proxied**, origin `root@187.77.149.107` (Ubuntu 24.04, nginx + PHP 8.4-FPM, MySQL).
- App root `/var/www/pios_com_my` (docroot `.../public`); nginx vhost `/etc/nginx/sites-enabled/pios.com.my`, Let's Encrypt SSL.

**Deploy procedure (manual, rsync-based — no CI pipeline):**
1. Build assets locally (`npm run build`) — no Node on the server; rsync `public/build` up.
2. rsync app up, **excluding** `.git`, `node_modules`, `vendor`, `.env`, tmp, storage runtime, **and `bootstrap/cache`**.
3. `composer install --no-dev --optimize-autoloader` (see temp-state note below).
4. `php artisan migrate --force`.
5. `php artisan optimize` (config/route/view/event cache); run `php artisan package:discover` first.
6. `chown` uploaded files to uid `502:50` (only `bootstrap/cache/` is `www-data`).
7. Restart the queue worker: `systemctl restart pios-queue`.

**Deploy pitfalls (from production memory):**
- rsync does NOT exclude `bootstrap/cache/` by default → it overwrites prod's manifest with the local dev manifest referencing `laravel/pail`, breaking `config:cache` with `Class "Laravel\Pail\PailServiceProvider" not found`. Exclude `bootstrap/cache` or `rm -f bootstrap/cache/packages.php bootstrap/cache/services.php && php artisan package:discover` before caching.
- Never `rsync -R` into a dest that already includes the subdir (creates `app/app/...`). After syncing `app/`, run `composer dump-autoload -o --no-dev` then `php artisan optimize`; verify by instantiating a class in tinker on the server.
- Cloudflare caches static assets `max-age=14400` (4h) and ignores query-string busters; in-place asset updates serve stale for up to 4h. No Cloudflare API creds on the server — purge via the CF dashboard or wait out the TTL.
- ⚠️ Temp state (set 2026-07-01): prod is running `APP_ENV=development` with full dev deps installed (so `DemoDataSeeder`/factories, which need `fakerphp/faker`, can run live). `APP_DEBUG` deliberately left `false`. Revert path documented in the production-server memory.

**CI Pipeline:**
- None. No GitHub Actions / CI config in the repo. Code style checked locally with `laravel/pint`; tests with `php artisan test`.

## Background Processing

**Queue worker:**
- systemd unit `pios-queue` (restart after every deploy). Database queue driver. In dev, `php artisan queue:listen` runs as part of `composer run dev`.

**Scheduler:**
- www-data crontab runs `php artisan schedule:run` every minute.
- Only scheduled job: `GenerateMonthlyBusinessOwnerFees` — `->monthlyOn(1, '00:30')->withoutOverlapping()->onOneServer()` (`routes/console.php`). Generates the idempotent RM5 monthly Business Owner fee.

## Environment Configuration

**Notable env vars (values never committed):**
- Database: `DB_CONNECTION`, `DB_DATABASE` (`pios_my`), `DB_*`.
- Mail: `MAIL_MAILER`, `MAIL_FROM_ADDRESS`, plus transport-specific (`POSTMARK_API_KEY`, `RESEND_API_KEY`, `AWS_*` for SES).
- UstazAI: `USTAZAI_BASE_URL`, `USTAZAI_SEND_ENDPOINT` (per-BO key/sender live in the DB, not env).
- PIOS: `PARTNER_MONTHLY_CUSTOMER_TARGET`.
- Optional Slack notify: `SLACK_BOT_USER_OAUTH_TOKEN`, `SLACK_BOT_USER_DEFAULT_CHANNEL` (`config/services.php` `slack` block — wired but not observed in use).

**Secrets location:**
- `.env` on each host (never committed; production `.env` holds the DB password and `APP_KEY` used to encrypt BO UstazAI keys). Prod `.env` backed up as `.env.bak.<ts>` before edits.

## Webhooks & Callbacks

**Incoming:** None. No public webhook endpoints (no payment/WhatsApp callback routes).

**Outgoing:** UstazAI send requests only (`UstazAiBlaster::blast()`). No other outbound service calls.

## MCP / Third-Party Tooling

- No MCP config in the repo (`.mcp.json` / `.cursor` absent).

---

*Integration audit: 2026-07-01*
