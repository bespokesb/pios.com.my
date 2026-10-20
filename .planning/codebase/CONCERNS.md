# Codebase Concerns

**Analysis Date:** 2026-07-01

This catalogs real risks, tech debt, and gaps for PIOS (Laravel 13 + FilamentPHP 5 + MySQL). Ranked most-severe first within each section. Severity reflects likelihood × blast radius on a live, production, revenue-facing app (pios.com.my).

---

## Operational Risks

### Production is running in `development` env with full dev dependencies — **High**
- Issue: Per the deploy memo, prod was deliberately switched to `APP_ENV=development` with `composer install` run **without** `--no-dev` (fakerphp/faker, phpunit, laravel/pail all installed on the live box) so `DemoDataSeeder` could run against production. This is flagged as a "⚠️ TEMP STATE (set 2026-07-01, 'for now')."
- Files: production `.env` on `root@187.77.149.107:/var/www/pios_com_my`; `database/seeders/DemoDataSeeder.php`; documented in `~/.claude/projects/-Users-habib-Git-pios-my/memory/pios-production-server.md` (line 24).
- Impact: Dev-only providers loaded in prod; larger attack surface; env-conditioned framework behavior (debug helpers, error verbosity paths, seeded fake data) active on a public site handling personal data (IC, phone, email). Factories call `fake()` which only exists while dev deps are present, so reverting is not a no-op.
- Fix approach: Execute the documented revert once demo period ends: `APP_ENV=production` → `composer install --no-dev --optimize-autoloader` → `rm -f bootstrap/cache/packages.php bootstrap/cache/services.php && php artisan package:discover` → `php artisan optimize` → `chown -R 502:50 app vendor bootstrap/cache`. Mitigating factor already in place: `APP_DEBUG=false` was intentionally kept, so stack traces are not exposed.

### Demo/seed data seeded into the production database — **High**
- Issue: Partners, BOs, customers, and products were seeded live, all with login password `password`.
- Files: `database/seeders/DemoDataSeeder.php`; production DB `pios_my`.
- Impact: Known-credential accounts (`password`) exist on a public login across all three panels (`/admin`, `/partner`, `/business`). Any of these that remain Active are an account-takeover vector. Demo records also pollute real reports/quota counts.
- Fix approach: Before hardening, either delete demo accounts/records or force-rotate their passwords and set them `Inactive`. Audit `users` for `password`-hash logins.

### App files owned by uid 502, not `www-data` — **Medium**
- Issue: After rsync-as-root, app files end up owned by uid 502 (group 50); only `bootstrap/cache/` is `www-data`.
- Files: whole app tree on server; deploy procedure in `pios-production-server.md` (line 20).
- Impact: Ownership drift means a careless `chown -R www-data` (from the documented generic deploy step) or a partial deploy can break the intended permission split; php-fpm currently reads fine only because files are 644. Fragile and easy to get wrong under time pressure.
- Fix approach: Standardize on one ownership model and encode it in the deploy script; always `chown 502:50` uploaded files to match siblings and leave `bootstrap/cache` as `www-data`.

### `laravel/pail` bootstrap-cache deploy pitfall — **Medium**
- Issue: rsync does not exclude `bootstrap/cache/` by default, so a deploy can overwrite the server's prod manifest with the local dev manifest referencing `Laravel\Pail\PailServiceProvider`. `config:cache` then fatals with "Class not found" on lighter deploys that skip `composer install`.
- Files: `bootstrap/cache/packages.php`, `bootstrap/cache/services.php`; `composer.json` (pail in `require-dev`); documented in `pios-production-server.md` (line 16).
- Impact: A deploy can take the site down at the `config:cache` step.
- Fix approach: Exclude `bootstrap/cache` in rsync, or run `rm -f bootstrap/cache/packages.php bootstrap/cache/services.php && php artisan package:discover` before caching. (Currently masked while dev deps are installed — will re-surface the moment prod is hardened back to `--no-dev`.)

### `MAIL_MAILER=log` — password-reset & notification emails do not send — **Medium**
- Issue: The example env ships `MAIL_MAILER=log` (`.env.example` line 51). If prod inherits this, all mail (password resets, notifications) is written to the log instead of delivered.
- Files: `.env.example:51`; production `.env` (verify the live value).
- Impact: Users who forget their password cannot self-recover; any transactional email silently no-ops. High-friction for real partners/BOs.
- Fix approach: Confirm the production `.env` `MAIL_MAILER` points at a real transport (SMTP/API) and send a live test reset. If intentionally `log` for now, document it and disable/soften the "forgot password" UI.

### Cloudflare static-asset cache with no purge credentials — **Medium**
- Issue: CF caches static assets `max-age=14400` (4h) and ignores query-string busters for them; in-place updates to an already-cached URL (e.g. `favicon.ico`) serve stale edge copies for up to 4h. There are **no Cloudflare API creds** on the server or in `.env`, so programmatic purge is impossible.
- Files: documented in `pios-production-server.md` (line 21); Vite build output `public/build/`.
- Impact: Asset/CSS/JS updates that reuse a path can appear "not deployed" for hours; no automated remedy.
- Fix approach: Rely on Vite content-hashed filenames (new paths cache fresh) and avoid in-place overwrites of stable URLs; obtain CF API token for dashboard-free purges, or document the manual dashboard purge step.

---

## Not-Yet-Built Scope (functional gaps)

### BO selling / order / promote flow absent from the business panel — **High**
- Issue: Eligibility rules and an `Order` model exist only in `/admin`. The business panel has no sell/order/promote workflow. Called out as "likely the biggest functional gap."
- Files: `app/Models/Order.php`, `app/Models/OrderItem.php`, `app/Models/Payment.php` (admin-side); `app/Models/Promotion.php`, `app/Models/PromotionCustomer.php`; `PIOS_ARCHITECTURE_SKETCH.md` §3.1.
- Impact: The core value proposition (BO promotes/sells eligible products to their customers) is not deliverable in the panel BOs actually use.
- Fix approach: Build Orders + promotion in `/business`, routed through the shared ownership + eligibility policies/scopes (`app/Policies/*`, `Customer::scopeVisibleTo`, `Customer::canBuyProduct`).

### Fees are tracked as obligations only — no collection / payment gateway — **High**
- Issue: RM10 setup fee (on BO approval) and idempotent RM5 monthly fee (`fees:generate-monthly`) are recorded, but there is no payment collection or gateway. `Payment` exists as a model but nothing settles fees.
- Files: `app/Models/Fee.php`, `app/Models/FeeSetting.php`, `app/Models/Payment.php`, the `fees:generate-monthly` command; PRD §15 ("What payment gateway... is required?"); `PIOS_ARCHITECTURE_SKETCH.md` §2 / §3.4.
- Impact: PIOS is SaaS-for-BOs but cannot actually bill. No revenue capture; no suspension-on-nonpayment loop. Fee amount/due-date/grace/suspension policy is itself an open question (PRD §15).
- Fix approach: Choose a Malaysian gateway, model settlement against `Fee`, define grace/suspension. Blocked partly on the unresolved fee-policy questions.

### Partner monthly quota is advisory only — no enforcement — **Medium**
- Issue: `PartnerQuotaService` is explicitly read-only ("never blocks customer creation, never alters a policy, never writes"). The "100 customers/month" quota has no consequence on a miss (deferred, QUOTA-05).
- Files: `app/Services/PartnerQuotaService.php` (esp. class docblock lines 9–17); `config/pios.php:16`.
- Impact: A documented business rule ("Syarat Partner") is not actually enforced; reports may imply a rule that has no teeth.
- Fix approach: The service is designed as the seam for future enforcement — add a policy/observer that calls `attainment()` at customer creation once the consequence is defined.

### Services & Programs offerings not modelled — **Medium**
- Issue: Only Products exist; the diagrams show "Produk / Servis / Program" as PIOS offerings.
- Files: `app/Models/Product.php` (only offering type); `PIOS_ARCHITECTURE_SKETCH.md` §2 / §3.2.
- Impact: A third of the intended catalog surface is missing; any pricing/reporting assuming a single offering type will need rework.
- Fix approach: Introduce Service/Program offering types (or a polymorphic offering) once scope is confirmed.

### WhatsApp promotion limited to deep-link + UstazAI blaster; no WhatsApp Business API — **Medium**
- Issue: Promotion is via share deep-links plus the UstazAI blaster; there is no official WhatsApp Business API integration. PRD §15 leaves "deep link vs Business API vs both" unresolved.
- Files: `app/Services/UstazAiBlaster.php`; `PIOS_ARCHITECTURE_SKETCH.md`; PRD §15.
- Impact: Deliverability/compliance risk relative to an official API; dependence on a third-party blaster (ustazai.my) for bulk send.
- Fix approach: Decide the promotion channel formally; if Business API is required, plan template approval + messaging limits.

### GPS / location element not built — **Low**
- Issue: A "GPS/location" element in the whiteboard is unbuilt and its meaning is unconfirmed (SRC-05, deferred to v2).
- Files: `PIOS_ARCHITECTURE_SKETCH.md` §2 / §3.5.
- Impact: Low — meaning is unclear; no dependent feature is blocked today.
- Fix approach: Clarify intent before modelling.

### Product Partner status (Verified + recurring membership) not modelled — **Low/Blocked**
- Issue: "Product Partner" = a Verified partner paying a recurring membership fee. Partners today are only Pending/Active; there is no `verified` flag or partner membership fee machinery.
- Files: `app/Models/Partner.php`, `app/Models/PartnerTier.php`; `PIOS_ARCHITECTURE_SKETCH.md` §4.1.
- Impact: A defined upgrade tier cannot be reached. **Blocked:** partner membership fee amount is TBC (PRD/CLAUDE).
- Fix approach: Mirror the BO monthly-fee machinery + add a `verified` flag once the fee amount is confirmed.

---

## Open Questions / Unresolved Rules (spec risk)

These are enumerated in `PIOS_PRD.md` §15 and CLAUDE.md; each is a decision that could invalidate current modelling. **Medium** as a class — building further without resolution risks rework.

- **Partner tier thresholds & the `1 < 4 < 16 < 64 < 256 = 341` sequence** — meaning unknown; `PartnerTier` exists but the progression criteria are undefined. Files: `app/Models/PartnerTier.php`; PRD §15.
- **LOS / MSMY verification** — VIP/Amway eligibility is now a **manual `is_vip` boolean** with **no external verification**. Anyone with edit rights can grant Amway eligibility by toggling a flag. Files: `app/Models/Customer.php:22,61,100`; `database/migrations/2026_07_01_000003_replace_vip_category_with_boolean.php`. Impact: the Amway-only-to-VIP invariant (CLAUDE Rule 2) rests entirely on an unverified manual boolean.
- **Product Partner membership fee amount** — TBC / blocked (above).
- **Product proposal approval rules** — whether proposed products need admin/partner approval before promote/sell is unresolved. Files: `app/Models/ProductProposal.php`, `app/Policies/ProductProposalPolicy.php`; PRD §15.
- **Field mandatoriness** — which Partner/customer/product fields are required vs optional is unresolved; current forms encode guesses (BO customer form = Name + HP only). PRD §15.
- **`max 5` available-product rule** — unconfirmed whether it is a hard system limit. Files: `app/Models/BusinessOwnerProductSelection.php`; PRD §15.
- **VIP Customer RM2 fee** — unresolved whether VIP registration carries a RM2 fee. PRD §15.
- **Bilingual (Malay/English) completeness** — both `lang/en/pios.php` and `lang/ms/pios.php` exist, but completeness/parity is not guaranteed and bilingual support is an open question. Files: `lang/en/pios.php`, `lang/ms/pios.php`; PRD §15. Risk: hardcoded English labels leaking through.

---

## Auth / Data-Integrity Watch-Items

### Dual-role capability-vs-hat model is subtle — **High**
- Issue: A user can hold both Partner and BusinessOwner profiles. Ownership scopes, owner-resolution, and shared-form gating branch on `actsAsPartner()` / `actsAsBusinessOwner()`, which resolve the acting "hat" from the **current Filament panel**. Outside a panel (console, queue, tests, reports, admin), the hat falls back to `default => ! hasOtherProfile()` — i.e. it is only unambiguous for a single-capability user.
- Files: `app/Models/User.php:33-40, 84-109`; every policy/scope that consumes these (`app/Policies/*`, `Customer::scopeVisibleTo`).
- Impact: For a genuine dual-role user, any authorization/scoping decision made **outside a panel context** (a scheduled job, a report generator, an artisan command, a background queue) is ambiguous by design and defaults based on profile presence, not intent. A mis-ordered `isPartner`/`isBusinessOwner` check, or reusing panel-derived scoping in a non-panel context, can leak or hide the wrong customers. This directly touches CLAUDE Rule 1 (customer ownership is an enforced invariant, not a display preference).
- Fix approach: Never rely on `actsAs*()` in non-panel code paths; pass an explicit acting-role. Add tests covering dual-role users in console/report contexts (only `DualRoleUpgradeTest.php` exists today).

### Partner-direct customers with `business_owner_id` NULL — **Medium**
- Issue: `customers.business_owner_id` was made nullable so partners can own customers directly (`partner_id` set, `business_owner_id` NULL).
- Files: `database/migrations/2026_07_01_000002_make_customer_business_owner_nullable.php`; `app/Models/Customer.php` (`business_owner_id`, `partner_id` fillable); `tests/Feature/PartnerOwnedCustomerTest.php`.
- Impact: Any code, scope, report, or eligibility check that assumes a customer always has an owning BO can throw or silently exclude/leak partner-direct customers. CLAUDE Rule 1 frames "owning Business Owner" as a required FK "for the current UI flow" — that assumption is now violated by design, so downstream code must handle the NULL branch everywhere.
- Fix approach: Audit every query filtering on `business_owner_id`; ensure `scopeVisibleTo` and reports treat the NULL-BO / partner-owned case explicitly.

### UstazAI HTTP contract is a TODO / unverified against the real API — **Medium**
- Issue: `UstazAiBlaster::blast()` carries `// TODO: confirm endpoint + payload against UstazAI's API docs.` The endpoint path, JSON payload keys, and auth scheme are "a sensible default" and unverified.
- Files: `app/Services/UstazAiBlaster.php:47-54`; `config/services.php:40-43` (base_url + endpoint from env).
- Impact: Blasts may silently fail (or, worse, appear to succeed) against the real ustazai.my API; the success path returns `sent => count(recipients)` optimistically on any 2xx without parsing per-recipient results, so partial failures are reported as full success.
- Fix approach: Verify against UstazAI docs; parse the real response body for per-recipient outcome instead of assuming all-sent on HTTP 2xx.

### Encrypted per-BO API keys — key management — **Low/Medium**
- Issue: Each BusinessOwner stores its own `ustazai_api_key` cast `encrypted` (Laravel `APP_KEY`-based).
- Files: `app/Models/BusinessOwner.php:33-34,116`; `database/migrations/2026_07_01_000001_add_ustazai_credentials_to_business_owners.php`.
- Impact: All encrypted secrets are bound to `APP_KEY`; an `APP_KEY` rotation (or a mismatch between the seeded dev env and prod) renders every stored key undecryptable. No key-rotation path is evident.
- Fix approach: Document `APP_KEY` as a protected secret; add a re-encryption plan before any key rotation; never regenerate `APP_KEY` on the live box.

---

## Testing / Coverage Gaps

### SQLite in tests vs MySQL in production — schema-change divergence — **High**
- Issue: The suite runs on `sqlite :memory:` (`phpunit.xml`), while prod is MySQL. Several migrations perform `dropColumn` and column `->change()` operations that behave differently across drivers (SQLite emulates via table rebuild; MySQL applies in place, and older `->change()` needs doctrine/dbal).
- Files: `phpunit.xml` (`DB_CONNECTION=sqlite`, `:memory:`); risky migrations — `2026_07_01_000002_make_customer_business_owner_nullable.php` (`->change()`), `2026_07_01_000003_replace_vip_category_with_boolean.php` (`dropColumn` + data backfill), `2026_06_20_000003_add_vendor_and_cost_to_products.php` (`dropColumn`), `2026_07_01_000001_add_ustazai_credentials_to_business_owners.php`.
- Impact: A migration that passes green in CI (SQLite) can fail or behave differently on the live MySQL box (`php artisan migrate --force` during deploy). Data-backfilling migrations (VIP → boolean) are especially risky since the transformation logic runs unverified against real MySQL semantics/collation.
- Fix approach: Add a MySQL-backed migration smoke test (or CI job) before deploys; at minimum, dry-run migrations against a MySQL copy of prod.

### Feature tests only — no browser / E2E coverage — **Medium**
- Issue: 39 test files, all Feature/Unit (`tests/Feature/*`, `tests/Unit/*`); no `tests/Browser` / Dusk suite exists. Filament panels, forms, panel-switching, and column-level admin gating are exercised only at the request/livewire level, not through a real browser.
- Files: `tests/` (no `tests/Browser`); `tests/Feature/FilamentAccessTest.php`, `PanelSwitcherAndResetTest.php`, `LoginCopyAndLocaleTest.php`.
- Impact: JS-dependent Filament behavior, real login flows, and cross-panel session/403 behavior (a stated invariant in CLAUDE.md) are not covered end-to-end. Regressions in the actual UI can ship green.
- Fix approach: Add Dusk (or Playwright) smoke coverage for the three-panel login/403 matrix and the admin-only cost/vendor/margin gating.

### Thin coverage on newest/riskiest areas — **Medium**
- Issue: The subtlest logic has the least direct coverage:
  - Dual-role in **non-panel** contexts (console/report/queue) — only `tests/Feature/DualRoleUpgradeTest.php`; the panel-fallback hat resolution is untested outside a panel.
  - UstazAI blast is tested via `tests/Feature/ProductWhatsAppBlastTest.php`, but the HTTP contract is faked (TODO-marked real contract remains unverified).
  - Partner-direct (NULL BO) customer scoping — `tests/Feature/PartnerOwnedCustomerTest.php` exists but the interaction with reports/eligibility for NULL-BO customers is not exhaustively covered.
- Files: as listed above.
- Impact: The areas most likely to leak data or misfire (auth ambiguity, third-party send, nullable ownership) are the least protected.
- Fix approach: Add targeted tests for each: dual-role hat under console/report context; NULL-BO customer in every scope/report; a contract test (or recorded fixture) for UstazAI once the real API is confirmed.

---

*Concerns audit: 2026-07-01*
