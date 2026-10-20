# Architecture

**Analysis Date:** 2026-07-01

## Pattern Overview

**Overall:** Laravel 13 monolith with FilamentPHP 5 admin panels as the primary UI. Three role-scoped Filament panels sit on top of a shared Eloquent domain model, guarded by a two-part authorization layer (query scopes + policies). There is no separate API layer; the only non-Filament surface is a public product/order page (`routes/web.php` → `PublicProductController`).

**Key Characteristics:**
- **3-tier ownership hierarchy** drives everything: PIOS Owner/System → Partner → Business Owner → Customer. Selling is gated by ownership and product eligibility, not a generic storefront.
- **Panel-per-role**: three Filament panels (`/admin`, `/partner`, `/business`) share one `web` session; access is mutually exclusive via `User::canAccessPanel()`.
- **Authorization is server-side and centralized**: every list/query routes through `scopeVisibleTo()` (query scopes) and Laravel Policies. Filament UI hiding is never the sole gate.
- **Capability vs acting-hat auth model**: a user's *capabilities* (which identities they hold) are separated from the *hat* they wear in the current panel, so genuinely dual-role users behave correctly in each panel.
- **Fat services, thin resources**: cross-cutting business operations (registration, upgrades, fee generation, identifier allocation, reporting, quota) live in `app/Services`; Filament Resources delegate to them.
- **Enum-typed domain**: 14 backed enums in `app/Enums` model roles, statuses, categories, and classifications.

## Layers

**Domain models (Eloquent):**
- Purpose: entities + ownership rules + relationships. Ownership scopes and eligibility logic live here, not in controllers.
- Location: `app/Models/`
- Contains: `User`, `Partner`, `BusinessOwner`, `Customer`, `Product`, `Order`/`OrderItem`, `Promotion`/`PromotionCustomer`, `Fee`/`FeeSetting`/`Payment`, `ProductProposal`/`BusinessOwnerProductSelection`/`ProductType`, `Vendor`, `Territory`, `PartnerTier`, `CustomerNote`/`CustomerFollowUp`, `AuditLog`, `IdentifierSequence`.
- Shared concern: `app/Models/Concerns/ScopesByOwnerVisibility.php` (trait).
- Depends on: `app/Enums`, `app/Support/Phone.php`.
- Used by: Filament Resources, Services, Policies.

**Authorization layer (scopes + policies):**
- Purpose: enforce the two invariants — (1) a seller sees/manages only what they own; (2) product eligibility (Amway → VIP-only).
- Location: `scopeVisibleTo()` on models + `app/Policies/*` + `app/Policies/Concerns/AllowsAdminSystemAccess.php`.
- Registered in: `app/Providers/AppServiceProvider.php` (`Gate::policy()` for all 20 models).
- Used by: every `Resource::getEloquentQuery()` (e.g. `CustomerResource` line 41-47 calls `->visibleTo($user)`), Filament action/visibility gates, and services (defense-in-depth guards).

**Filament presentation (panels + resources):**
- Purpose: the operator UI. Each panel registers a role-appropriate subset of resources/widgets.
- Location: `app/Providers/Filament/*PanelProvider.php`, `app/Filament/{Resources,Pages,Widgets,Plugins,Support}`.
- Depends on: models, policies, services.

**Services (application/business operations):**
- Purpose: multi-step, transactional, or cross-cutting workflows.
- Location: `app/Services/`.
- Depends on: models, enums, `AuditLogger`.

**Public web surface:**
- Purpose: anonymous, mobile-first product page + order intake driven by WhatsApp share links.
- Location: `routes/web.php`, `app/Http/Controllers/PublicProductController.php`, `app/Http/Requests/StorePublicOrderRequest.php`, `resources/views/public/*`.

## The Capability vs Acting-Hat Auth Model

Read `app/Models/User.php`. This is the single most important non-obvious mechanism.

**Capability (what identities the user holds):**
- `isAdmin()` — role is `SysAdmin` or `PiosOwner` (`UserRole::isAdmin()`).
- `hasPartnerProfile()` / `isPartner()` — role is `Partner` **OR** the user owns a `Partner` record (`$this->partner !== null`).
- `hasBusinessOwnerProfile()` / `isBusinessOwner()` — role is `BusinessOwner` **OR** the user owns a `BusinessOwner` record.
- Capability is used by **policies** and **widget visibility** (a broad "can this user do X at all" check).

**Acting hat (which single role the user exercises in the current request):**
- `actsAsPartner()` / `actsAsBusinessOwner()` — resolved from `Filament::getCurrentPanel()?->getId()`:
  - In `/partner` panel → acts as Partner (never BO).
  - In `/business` panel → acts as BusinessOwner (never Partner).
  - In a **panel-less** context (console, scheduled jobs, tests): unambiguous only for a single-capability user; a dual-role user returns `false` from both.
- Acting hat is used at **disambiguation points**: ownership query scopes, owner-resolution, and shared-resource form gating.

**Why it exists:** A single user may hold **both** a `Partner` profile and a `BusinessOwner` profile (created via the upgrade services below). Such a dual-role user can access both `/partner` and `/business`. `canAccessPanel()` (`User.php` line 29-41) admits them to each panel; the acting hat then makes them see their **downline** in `/partner` and their **own BO records** in `/business` — the same query scope yields different rows per panel. A single-role user's hat always equals their capability, so their behavior is unchanged.

## Ownership Isolation (the central invariant)

Two implementation shapes of `scopeVisibleTo(Builder $query, User $user)`:

**Shared trait** — `app/Models/Concerns/ScopesByOwnerVisibility.php`, used by models carrying **both** `partner_id` and `business_owner_id`: `Customer`, `Order`, `Promotion`, `Fee`.
- Admin → unscoped.
- `actsAsPartner()` → `where partner_id = user.partner.id`.
- `actsAsBusinessOwner()` → `where business_owner_id = user.businessOwner.id`.
- Dual-role, no panel → union of both.
- Otherwise → `whereRaw('1 = 0')` (fail closed).

**Bespoke scopes** on models with different shapes:
- `Partner::scopeVisibleTo` (`app/Models/Partner.php` line 116) — partner sees self + direct children (`parent_partner_id`); a BO sees only their managing partner.
- `BusinessOwner::scopeVisibleTo` (line 41) — partner sees their managed BOs; a BO sees only their own record.
- `Product::scopeVisibleTo` (`app/Models/Product.php` line 39) — a BO (acting hat) sees/manages only `business_owner_id = own`; everyone else sees the shared **active** catalog (`status = Active`). Products are BO-owned; cost/vendor/margin columns are admin-only (gated in the shared `ProductForm`/`ProductsTable`).

**Customer ownership is dual-shaped** (`app/Models/Customer.php`):
- BO-owned: `business_owner_id` set.
- Partner-direct: `business_owner_id` **null**, `partner_id` set (migration `2026_07_01_000002_make_customer_business_owner_nullable`).
- `CustomerPolicy` (`app/Policies/CustomerPolicy.php`) encodes the nuance: a BO manages customers where `business_owner_id === own`; a Partner fully manages only **partner-direct** customers (`ownsPartnerDirectCustomer`), but may **tag VIP** on any downstream customer (`tagVip` — LOS verification sits above the BO, so a BO cannot self-certify VIP).

**Policy pattern:** `view()` reuses the scope for consistency — `Customer::query()->visibleTo($user)->whereKey($customer->id)->exists()`. Admin/system-only resources use the `AllowsAdminSystemAccess` trait (`app/Policies/Concerns/`) which gates every ability on `UserRole::canManageSystem()`.

## Product Eligibility (Amway vs Non-Amway)

- Every `Product` has `classification` (`App\Enums\ProductClassification`: Amway / NonAmway).
- Every `Customer` carries a boolean `is_vip` (migration `2026_07_01_000003_replace_vip_category_with_boolean` replaced the old VIP category enum with the flag).
- Gate: `Customer::canBuyProduct(Product $product)` (`Customer.php` line 55) — non-Amway is always buyable; Amway requires `is_vip === true`.
- Public surface enforces this structurally: `Product::isPubliclyOrderable()` excludes Amway products entirely (an anonymous buyer's VIP status cannot be verified).

## Role Upgrade Flows

Both upgrade services **reuse the existing `user_id`** (no new login) and log via `AuditLogger`. This is how a user becomes dual-role.

**BO → Partner** — `app/Services/PartnerUpgradeService.php`
- Initiated by the BO's **managing partner** or HQ (independent auth guard at line 28-37, beyond the Filament action's visibility gate).
- Creates a **Pending** `Partner` parented to `bo.partner_id`; advisory `MAX_DIRECT_PARTNERS` pre-check.
- Activation goes through the existing HQ path: `RegistrationApprover::approvePartner()` re-checks the active-downline cap, then activates.

**Partner → BO** — `app/Services/BusinessOwnerUpgradeService.php`
- Authorized by HQ, the partner's recruiter (parent), or a top-level partner acting on self.
- Creates an **immediately Active** `BusinessOwner` (no HQ approval, no setup fee — deliberately skipped), managed by the recruiter (`parentPartner ?? self`).

**Registration/approval machinery:**
- `RegistrationAccountCreator` — creates the linked `User` login for a Partner or BusinessOwner (`createForPartner`/`createForBusinessOwner`), enforcing email uniqueness; attaches via `forceFill(['user_id' => …])`.
- `RegistrationApprover` — HQ approval. `approvePartner()` enforces the downline cap; `approveBusinessOwner()` activates and generates the one-time RM10 setup fee (idempotent `Fee::firstOrCreate` keyed on `business_owner_id + fee_type + null billing_period`; amount from `FeeSetting`, never hardcoded).
- `RegistrationApprovalException` (`app/Exceptions/`) signals a cap breach.

## Recursive Partner Downline

`app/Models/Partner.php`:
- Self-referential tree via `parent_partner_id` (`parentPartner()` / `childPartners()`).
- `const MAX_DIRECT_PARTNERS = 4` — cap on **active** direct sub-partners (`activeChildPartnersCount()`), enforced at recruitment and re-checked at approval.
- `descendantIds()` — breadth-first traversal collecting the full downline at all depths (works on MySQL and SQLite; no recursive CTE).
- `flatDownline()` — pre-order flatten tagging each row with its depth, for the `PartnerDownlineTree` widget.

## Key Abstractions / Services

Location: `app/Services/`
- **`IdentifierGenerator`** — allocates system-generated unique IDs (Partner No `P######`, BO No `BO######`, Customer `C######`, VIP `VIP######`, Product `PRD######`, Order `ORD######`). Row-locked `IdentifierSequence` in a transaction with a collision-retry loop. Never manually entered.
- **`AuditLogger`** — writes `AuditLog` (actor, event, auditable morph, old/new values, IP, UA). Called across registration, upgrades, approvals, fee generation.
- **`PartnerQuotaService`** — read-only advisory quota (customers registered this MYT calendar month vs a configurable target). Never blocks; the seam for future v2 enforcement.
- **`OperationalReportService`** — aggregates BOs/customers/fees/payments/orders, scoped by the requesting user, for `OperationalReports` page.
- **`GenerateMonthlyBusinessOwnerFees`** (`app/Console/Commands/`) — idempotent `fees:generate-monthly` (scheduled `monthlyOn(1)`); amount from `FeeSetting`; `billing_period` unique index prevents duplicates; audited as `User::system()`.
- **`ManualOrderService`**, **`UstazAiBlaster`** — order intake and WhatsApp/AI blast integration helpers.

## Data Flow

**Customer list in a panel:**
1. Filament renders a Resource; `Resource::getEloquentQuery()` calls `->visibleTo(auth()->user())`.
2. `scopeVisibleTo` reads the acting hat from the current panel and constrains by `partner_id` or `business_owner_id`.
3. Row-level access re-checked by the Policy on view/edit.

**Public order:**
1. WhatsApp share link (`Product::shareWhatsAppUrl()`) → `/p/{product_no}`.
2. `PublicProductController::show` renders only if `isPubliclyVisible()`; order form only if `isPubliclyOrderable()` (non-Amway).
3. POST throttled (`throttle:8,1`), validated by `StorePublicOrderRequest`, persisted via `ManualOrderService`.

**State Management:** Server-rendered Filament/Livewire; per-request state only. Locale persisted in session (`SetLocale` middleware + `/locale/{locale}` route).

## Entry Points

- **`/admin`** — `AdminPanelProvider` (`->default()`). SysAdmin/PiosOwner. Auto-discovers **all** resources/pages/widgets.
- **`/partner`** — `PartnerPanelProvider`. Partner + admins. Explicit resource list.
- **`/business`** — `BusinessPanelProvider`. BusinessOwner + admins. Custom `BusinessLogin` page.
- **Public** — `routes/web.php` (product page, order, locale switch, welcome).
- **Console** — `routes/console.php` + `app/Console/Commands` (scheduled fee generation).

## Error Handling

**Strategy:** Domain guard failures throw `Illuminate\Validation\ValidationException::withMessages()` (surfaces inline in Filament forms); business-rule breaches throw the domain `RegistrationApprovalException`. JSON error rendering only for `api/*` (`bootstrap/app.php`), which is currently unused.

**Patterns:**
- Fail-closed queries: unrecognized user context → `whereRaw('1 = 0')`.
- Defense-in-depth: services re-assert authorization even though the Filament action already gated visibility (see `PartnerUpgradeService` "MF-1").
- Idempotency via `firstOrCreate` + unique indexes (fees).

## Cross-Cutting Concerns

**Logging/Audit:** `AuditLogger` → `audit_logs` (morph). Scheduled jobs attribute to the non-login `User::system()` (`system@pios.com.my`, self-healing `firstOrCreate`).
**Validation:** Form Requests (`StorePublicOrderRequest`) for public; Filament schema validation + service-level `ValidationException` for panels.
**Authentication:** Shared `web` session across panels; `canAccessPanel()` per-panel gate; SysAdmin impersonation (`canImpersonate`/`canBeImpersonated`). Password reset enabled per panel.
**Authorization:** `scopeVisibleTo` scopes + `app/Policies/*` registered in `AppServiceProvider::boot()`.
**Localization:** Bilingual EN/MS (`lang/en/pios.php`, `lang/ms/pios.php`); `SetLocale` middleware; nav labels via `__('pios.nav.*')`.
**Money:** Forced MYR + `ms_MY` locale globally in `AppServiceProvider::boot()` (`Number::useLocale` + `Table::configureUsing`).
**Timezone:** `Asia/Kuala_Lumpur` anchors billing/quota period math.

---

*Architecture analysis: 2026-07-01*
