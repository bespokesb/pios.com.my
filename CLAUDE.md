# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project State

This is a Laravel + FilamentPHP 5 + MySQL application. The current baseline is Laravel 13.14, Filament 5.6, PHP 8.4, Vite/Tailwind 4, and MySQL database `pios_my`. Milestones v1.0 and v1.1 have shipped (see Implementation Status below); `.planning/` holds the GSD roadmap/state.

### Implementation Status (as of 2026-06-29)

Built and deployed to production (pios.com.my):
- **Three Filament panels**: `/admin` (SysAdmin/PiosOwner), `/partner` (Partner), `/business` (BusinessOwner). Panel access is **capability-based, not mutually exclusive**: a user reaches `/partner` if they hold a Partner profile and `/business` if they hold a BusinessOwner profile (admins reach all three). **Dual-role users** (one user with both a Partner *and* a BusinessOwner record) access both panels. `User::isPartner()/isBusinessOwner()` are capability checks (role OR profile record — used by policies/widgets); `User::actsAsPartner()/actsAsBusinessOwner()` resolve the **acting "hat"** from the current Filament panel and are used only where a dual-role user must be disambiguated (ownership scopes — see `Concerns\ScopesByOwnerVisibility` — owner-resolution on customer create/edit, and shared-`Customer` form gating). The panels share one `web` session; a user still gets a 403 on any panel they can't access.
- **A Partner is a Business Owner by default.** Whenever a Partner becomes **active** — admin-created with a login, or a recruit approved by HQ — `BusinessOwnerUpgradeService::provisionFor()` automatically creates a *Active* BusinessOwner profile on the same user (managed by their recruiter/parent partner; top-level falls back to self; no fee). This is idempotent (skips if the user already holds a BO). So every active partner is dual-role and reaches both `/partner` and `/business` with no manual step.
- **Only the reverse is a deliberate activation**: a **BO→Partner** upgrade is **initiated by the BO's managing partner** (`PartnerUpgradeService`, action on the BO edit page, gated by `BusinessOwnerPolicy::initiatePartnerUpgrade` + in-service guard). The managing partner **appoints the placement** — a `placement_parent_id` chosen from `Partner::eligiblePlacementParents()` (themselves or a descendant with an open slot); the service re-validates the target is within their own line and has capacity (race-safe `lockForUpdate`). Result: a *Pending* Partner on the BO's existing user → **HQ verifies via the standard partner Approve** (`RegistrationApprover::approvePartner`, active-only cap re-check; that approval's auto-provision is a no-op since the user is already a BO).
- **4-leg cap (`MAX_DIRECT_PARTNERS = 4`) is enforced at ADD time, not just approval.** A slot is "occupied" by a direct child in `Partner::SLOT_OCCUPYING_STATUSES` (Active + Pending + Suspended; only Inactive frees it). `directChildPartnersCount()/hasOpenDirectSlot()` gate recruitment (`CreatePartner`, for partners AND admins picking a parent, race-safe in `handleRecordCreation`) and appointment; `RegistrationApprover` stays **active-only** on purpose (a pending-inclusive count there would count the record being approved). A `rejectRecruit` action (Pending→Inactive) frees a slot. All reuse the existing login (unique `user_id` prevents duplicates). Partner membership fee (Product Partner) remains TBC/deferred.
- **Downline tree views**: a regular partner sees only their **direct children** (max 4) via the `PartnerDownlineTree` widget on their partner record. Owners/admins get the dedicated **`PartnerNetworkTree` custom page** (`/admin/partner-network-tree`) — a centered **org chart built with `d3-org-chart`** (npm dep, own Vite entry `resources/js/partner-tree.js`; the page stays a Filament-native Livewire page feeding embedded JSON). Pan/zoom, expand/collapse-all, fit-to-screen, client-side search (highlights + centres matches), virtual "PIOS HQ" root anchoring the multi-root forest, and rich node cards (initials avatar, tier, status, `x/4` slots, deep downline, BOs managed, customers). Node click opens the partner's edit page. Assets must be rebuilt (`npm run build`) when `partner-tree.js` changes — no Node on the prod server.
- **Partner recruiting**: a Partner can register sub-partners, capped at `Partner::MAX_DIRECT_PARTNERS = 4` *active* ones; recruits land `Pending` until HQ ("Said") approves via `RegistrationApprover`.
- **Registration**: Owner→Partner→BusinessOwner→Customer. BO and partner-recruited registrations land Pending until HQ approval (inactive login until then).
- **Customers**: BO-owned, isolated via `Customer::scopeVisibleTo` (`business_owner_id`); partner sees its whole downstream (`partner_id`). BO create form is minimal (Name + Phone required, email optional → defaults to Lead).
- **Products**: now **BO-owned** (`products.business_owner_id`) — a BO creates and manages only their own products; partners see the active shared catalog; admins see all. Cost/vendor/margin are **admin-only** (column-level gating in the shared `ProductForm`/`ProductsTable`).
- **Fees** (FR-070): RM10 setup fee generated on BO approval; idempotent RM5 monthly fee via `fees:generate-monthly` (scheduled `monthlyOn(1)`); amounts from `FeeSetting`. Tracked as obligations only — **no payment collection/gateway**.
- **Partner quota**: advisory "100 customers/month" via `PartnerQuotaService` + partner widget + HQ report. **Not enforced** (no consequence on a miss).
- **Vendor sourcing**: `Vendor` admin resource + product cost/margin (admin-only). **No GPS/location.**
- Idempotency/timezone foundations: `fees.billing_period` unique index, `pios-system` audit user, `Asia/Kuala_Lumpur` timezone.

**BO order management shipped (v1.3, 2026-07-15 — DP-16/17/18/19/9/15):** the `/business` panel now has a full order lifecycle. `OrderStatus` is a forward-only workflow (Draft → Submitted → Paid → Packed → Shipped → Completed, + Cancelled) enforced by `Order::transitionTo()` (rejects illegal jumps, stamps `paid_at`/`shipped_at`); the incoming-orders `updateStatus` action offers only legal next steps and requires a tracking number at Shipped. Printable **invoice** (once submitted) and **receipt** (once paid) are auth-guarded routes (`order.invoice`/`order.receipt`) re-checking `Order::scopeVisibleTo` — HTML, no PDF dep, no cost/margin. A BO **order dashboard** (`OrderStatsOverview` widget) and **daily/weekly sales report** (`BusinessSalesReport` page, Reports group) are own-orders-scoped. Customer **tags** are now an admin-managed vocabulary (`CustomerTag` + `/admin` resource; BOs pick from the list). Payment stays **documents-only, manual mark-paid** (no gateway).

Not yet built (from the architecture diagrams — see `PIOS_ARCHITECTURE_SKETCH.md`):
- **Public BO order/promote flow** beyond the existing `wa.me` share + public product page (no in-panel customer-facing checkout).
- **Services & Programs** offerings (only Products modelled).
- **Quota enforcement**, **fee collection** (payment gateway), **GPS**.

Clarified roles (author, 2026-06-29) — terminology that the code should converge on:
- **Partner = account manager.** Manages, trains, and supports their Business Owner clients and accounts. Recruited by the Owner or another Partner; max 4 legs each.
- **Product Partner = a Verified Partner paying a recurring membership fee.** A *status/level of Partner*, not a separate entity. Not yet modelled — would mirror the BO monthly-fee machinery + a `verified` flag. **Blocked: partner membership fee amount is TBC.**
- **Business Owner = the platform's paying customer.** PIOS is effectively SaaS for BOs (customer database + product listing); the RM10/RM5 fees are the BO paying for it. A BO is a Partner's client and may be **upgraded to a Partner by their managing partner** (Pending → HQ approval). **This upgrade is now built** (see the panel/upgrade notes in Implementation Status); on upgrade the BO's customers stay in the database and the user becomes dual-role (both panels).
- **Upgrade chain:** Customer → Business Owner → Partner → Product Partner. A BO's customers can themselves become BOs/partners over time.
- **Customer/VIP Customer = the BO's own customers.** "BO is a Customer/VIP Cust to the Partner" just means the BO is the Partner's client/account, not a literal customer record (already how it works).
- **Customer ↔ lead rollup + verification (clarified 2026-07-15).** A BO's customer is a *customer* to the BO but rolls up to that BO's managing partner (via `customers.partner_id`, auto-set on create) as a downstream *lead*. **Only the partner (not the BO) verifies leads** — enforced today by the partner-only `CustomerPolicy::tagVip` (VIP/LOS verification); a BO can't self-certify. Separately, whether a **BO is an "Amway agent"** is an agent-level boolean `business_owners.is_amway_agent` (unrelated to the removed product classification) **verified only by the managing partner or HQ** via the `verifyAmwayAgent` action/policy on the partner-panel BO list (deliberately NOT `update()`, where a BO may edit its own record) — the BO can never self-certify. It's informational (gates nothing).

See `PIOS_ARCHITECTURE_SKETCH.md` for the full flow and the built-vs-not matrix.

Useful commands:
- `composer run dev` — start Laravel server, queue listener, logs, and Vite together.
- `php artisan serve` — start only the Laravel HTTP server.
- `npm run dev` — start only Vite.
- `npm run build` — build frontend assets.
- `php artisan test` — run the test suite (`--compact` for terse output).
- `php artisan test --filter=SomeTest` — run a single test class/method (fastest inner loop).
- `php artisan migrate` — run migrations against configured MySQL.
- `php artisan make:filament-user` — create a local Filament admin user.

## Development workflow, quality gate & deploy

**Before finalizing any change, this gate must pass** (used on every commit this project ships):
1. `php artisan test` — all green (feature tests via Livewire are the norm; use factories + `Filament::setCurrentPanel(...)` + `actingAs`).
2. `vendor/bin/pint --dirty` — fix formatting (never commit unformatted PHP).
3. **Bilingual parity** — `lang/en/pios.php` and `lang/ms/pios.php` must stay key-for-key identical. Every user-facing string goes through `__('pios....')`; add the key to BOTH files. Quick check:
   `php -r '$en=require"lang/en/pios.php";$ms=require"lang/ms/pios.php";function f($a,$p=""){$o=[];foreach($a as $k=>$v){$key=$p===""?$k:"$p.$k";if(is_array($v))$o+=f($v,$key);else $o[$key]=true;}return $o;}$fe=f($en);$fm=f($ms);echo count(array_diff_key($fe,$fm))+count(array_diff_key($fm,$fe))?"MISMATCH\n":"ok\n";'`

**Shared form components** — reuse these, don't hand-roll: `App\Filament\Support\PhoneInput::make('hp_no')` (fixed `+60` prefix; stores normalised international digits `60…`; the single place to add a country dropdown later) and `App\Filament\Support\AddressFields::make()` (the standard 3-line + Bandar/Negeri/Poskod set, used by Customer and BusinessOwner). Phone validation is `App\Rules\MalaysianMobile` via `App\Support\Phone`.

**Deploy (production = pios.com.my, `root@187.77.149.107:/var/www/pios_com_my`, nginx docroot `.../public`):** rsync the tree (exclude `.git node_modules vendor storage bootstrap/cache .env .DS_Store .phpunit.result.cache`), then on the server: `rm -f bootstrap/cache/{packages,services}.php && php artisan package:discover && php artisan migrate --force` (only if a migration was added) `&& php artisan optimize && chown -R 502:50 app config routes resources lang database public composer.* artisan && chown -R www-data:www-data storage bootstrap/cache && systemctl reload php8.4-fpm`. Restart `systemctl restart pios-queue` when queued notifications/jobs changed. Run `npm run build` locally first (no Node on prod) whenever `resources/js/**` or the Filament theme CSS changed. `MAIL_MAILER=log` on prod — outbound email is only logged, not delivered, until SMTP is configured.

**GSD planning** lives in `.planning/` (ROADMAP.md, REQUIREMENTS.md, STATE.md, MILESTONES.md); work maps to Jira project **DP** on `pios-com-my.atlassian.net` (jira-multi MCP, `account: pios`). Ad-hoc feature work has shipped directly from Jira tickets; larger milestones (e.g. v1.3) run through GSD phases.

`PIOS_PRD.md` is the **single source of truth** for scope and behavior. When requirements are ambiguous, prefer it over inference, and consult Section 15 (Open Questions) — several core rules (Partner tier thresholds, the `1 < 4 < 16 < 64 < 256 = 341` sequence, LOS MSMY verification, VIP RM2 fee, Business Owner monthly fees, product proposal approval, WhatsApp promotion method, and field mandatoriness) are explicitly unresolved. Surface these rather than guessing.

## Branding & Login

The product is branded **"PIOS BizGrowth Engine"** (tagline: "Automate Growth. Accelerate Success." / "Automasikan Pertumbuhan. Gandakan Kejayaan.") — client-requested rebrand adopted inside the existing Filament panels (not a redesign). The name is set via each panel's `->brandName('PIOS BizGrowth Engine')`, the `APP_NAME` env, the landing `pios.landing.kicker/title`, and the login `pios.auth.*.kicker`. The topbar shows the PIOS wordmark + "BizGrowth Engine" text (`.pios-brand-name`, hidden below 640px) + the panel tag pill (`resources/views/filament/brand.blade.php`).

**Login by PIOS ID** — users sign in with an **email, phone, OR IC number** (not email only). `App\Filament\Pages\Auth\Concerns\ResolvesPiosIdLogin` (used by both `Login` and `BusinessLogin`) relabels the field "PIOS ID", drops `->email()`, and resolves the identifier to the account email before Filament authenticates: email passes through; otherwise it matches a Partner/BusinessOwner profile by exact `ic_no`, then by normalised phone digits (`App\Support\Phone::digits`), returning the linked user's email (unresolved → auth fails normally). Password check and every downstream guard are unchanged.

## What PIOS Is

PIOS (Pak ITAM Online Supermarket) is a **partner-led** online supermarket for premium/organic/exclusive products. The current UI flow is Owner registers Partner, Partner registers Business Owner, and Business Owner registers contacts/customers, selects or proposes products, promotes through WhatsApp, pays fees, and reviews reports. The defining characteristic is that it is not a generic storefront — selling is gated by **ownership** rules (Rule 1) that cut across nearly every module. (Product *eligibility* — the old Amway/Non-Amway gate — was removed in DP-10; see Rule 2.)

### The core architecture: a 3-tier ownership hierarchy

Everything hangs off a strict hierarchy. This maps to **three Filament login contexts / panels** (PRD §7.1):

```
PIOS Owner / System  →  Partner  →  Business Owner  →  Customer / VIP Customer
 (registers Partners,   (registers   (registers contacts,   (buys eligible
  SysAdmin, fees,        Business      customers; selects/    products)
  platform reports)      Owners +      proposes products;
                         their login)  WhatsApp promo; fees)
```

> v0.1 modeled customers as owned by *Partners* directly; v0.2 moved primary customer/product ownership down to **Business Owners**, now the primary seller for v1. PRD §10.6 keeps the legacy Partner-led customer flow for compatibility, but treat **Business Owner** as the owning entity for customers, product selections, and proposals unless confirmed otherwise.

### Rule 1 — Customer Ownership (must enforce, not just store)
A customer belongs **exclusively** to the Business Owner or seller account that created them. A Business Owner may only view/sell to their own customers unless hierarchy permissions explicitly allow otherwise. This is an authorization invariant, not a display preference: it must hold in PIOS Mart checkout (FR-052), Customer Manager (FR-034), and Reports (FR-083). Reassignment/duplication requires an authorized override. Treat "owning Business Owner" as a required, enforced foreign key on every customer record for the current UI flow.

### Rule 2 — Product Eligibility (REMOVED as of DP-10, 2026-07-14)
The **Amway / Non-Amway classification is gone** (client ticket DP-10). The `ProductClassification` enum and the `classification` column on `products` and `product_types` were dropped; there is no product-eligibility gate anymore. `Customer::canBuyProduct()` now **always returns true** (kept as the single extension point that Orders/Promotions route through), and every active product with an owning BO is publicly orderable (`Product::isPubliclyOrderable()` === `isPubliclyVisible()`).

Product **Category** replaces classification: it is expressed solely through `product_type_id` (the `ProductType` model, surfaced in the UI as "Category" and seeded as **Health Product / Services / Book**). The `ProductForm` category picker is shown to every seller (no Amway-seller gating; `visibleToAmwaySeller` was removed).

**VIP/LOS status remains** a single boolean `customers.is_vip` (cast boolean), orthogonal to the buyer **stage** (`category` = Lead → Prospect → Customer only), but it no longer restricts what a customer may buy — it is now just a segmentation/tag flag (used e.g. in the WhatsApp-blast "VIP" segment). The old `VipCustomer`/`NonLosCustomer` categories and the `vip_customer_no` id were already removed earlier. VIP is toggled via the partner/admin `tagVip` action (boolean, optional ABO No).

These two rules compose: a sale requires `seller owns customer` AND `customer category permits product type`. Implement both in shared **Laravel Policies + Eloquent query scopes** that every Filament Resource/panel routes through — server-side, not via Filament UI hiding alone. Duplicate-ownership conflicts are detected on **HP No + IC No** (PRD §8.4).

## Key Entities & Non-Obvious Behaviors

- **System-generated unique IDs**: Partner No, Business Owner No, Customer No / VIP Customer No, Product No are system-generated and unique — never manually entered. Mockups mark these "System Generated"; "No Unik" = unique field, "Key" = lookup/identity field (PRD §7.2). Use ULID/integer PKs consistently with unique indexes (PRD "MySQL Data Storage Notes").
- **Available vs Proposed products** (PRD §8.5, data model §9.5–9.6): Business Owners *select* from the existing catalog (Available Product, possibly capped at "max 5" — unconfirmed) and may *propose* new products (name, area, partner price, customer price, image/video, description) that may require approval before sale/promotion. These are two distinct entities: `Business Owner Product Selection` and `Product Proposal` — not a column on Product.
- **Monthly Business Owner fees** (FR-070) and **WhatsApp promotion** (FR-066) are first-class flows; amounts/policy and deep-link-vs-Business-API are open questions.

## Domain Vocabulary (used throughout the PRD)

- **Partner** — appointed business user registered by PIOS Owner. Partners register downstream Business Owners. Partner-to-Partner hierarchy remains an open question. Five tiers: Executive, Staff, Senior, Associate, Principal.
- **Business Owner** — seller/operator registered by a Partner. Current UI makes this the primary role for adding contacts/customers, selecting products, proposing products, paying fees, and reporting.
- **Customer stages** — Lead (name + phone only) → Prospect → Customer (`category` enum). **VIP/LOS is a separate boolean `is_vip`**, not a stage — a customer at any stage may be VIP or not.
- **LOS / Non-LOS** — LOS MSMY membership; historically determined Amway eligibility, but the Amway gate was removed in DP-10, so `is_vip` is now only a segmentation flag.
- **PIOS No** — Partner identifier; must be unique. Sponsor's PIOS No must resolve to an existing Partner.
- **Business Owner No** — Business Owner identifier; system generated and unique.
- **Customer No** — Customer identifier; system generated and unique.
- **3S 1T** — Sales/Service/Prospecting + Training enablement modules (mostly Phase 2, behavior under-specified).
- Many fields use Malay names (Bandar=town, Negeri=state, Poskod=postcode, IC/KP No=ID number, HP No=mobile). Bilingual (Malay/English) support is an open question — don't hardcode English-only labels.

## Module Map (top-level navigation)

`Register` · `3S 1T` · `Partner` · `PIOS Mart` · `Fee` · `Sys Admin` · `Report` — all gated by role-based access (FR-002/FR-004). See PRD §7 for submodules and §8 for per-module functional requirements (IDs `FR-0xx`).

## Release Scope

Build **Phase 1 first** (auth + RBAC nav, Register Partner, Register Business Owner, Business Owner dashboard shell, Add Contact/Prospect/Customer, Add Product with available products and proposed products, Customer Manager, eligibility enforcement, Business Owner monthly fee tracking, basic PIOS Mart, basic reports, Sys Admin roles — PRD §13). Phase 2 covers advanced 3S 1T managers, WhatsApp promotion, promotion responses, payment automation, accounts, and AI prospecting. The dated schedule in PRD §14 is historical (now past) — ignore those dates for planning.

## When Implementing

- Bake hierarchy ownership + eligibility checks into a shared authorization layer that PIOS Mart, Customer Manager, Product flows, Fees, and Reports all go through — do not re-implement per module.
- Personal data (Partner/Business Owner/customer IC, phone, email, address) is sensitive; audit creation/updates of Partner, Business Owner, customer, product, fee, and admin records (FR-092, NFR §12).
- Keep this file current when architecture, commands, or framework versions change.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== filament/filament rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')
                ->columnSpan(1),
            TextInput::make('last_name')
                ->columnSpan(1),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or changed modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

</laravel-boost-guidelines>
