# Codebase Structure

**Analysis Date:** 2026-07-01

## Directory Layout

```
pios-my/
├── app/
│   ├── Console/Commands/          # Artisan commands (scheduled fee generation)
│   ├── Enums/                     # 14 backed enums (roles, statuses, categories)
│   ├── Exceptions/                # Domain exceptions (RegistrationApprovalException)
│   ├── Filament/                  # All Filament UI (resources, pages, widgets, plugins)
│   │   ├── Pages/                 # Custom pages + Auth login pages
│   │   ├── Plugins/               # PiosLoginPlugin
│   │   ├── Resources/<Name>/      # One dir per resource (see conventions below)
│   │   ├── Support/               # LocaleSwitcher, PanelSwitcher (user-menu helpers)
│   │   └── Widgets/               # Dashboard widgets (stats, charts, downline tree)
│   ├── Http/
│   │   ├── Controllers/           # PublicProductController (only non-Filament UI)
│   │   ├── Middleware/            # SetLocale
│   │   └── Requests/              # StorePublicOrderRequest
│   ├── Models/                    # Eloquent models + Concerns/ trait
│   ├── Policies/                  # One policy per model + Concerns/ trait
│   ├── Providers/
│   │   ├── AppServiceProvider.php # Policy registration, MYR/locale, currency defaults
│   │   └── Filament/              # Three *PanelProvider.php (admin/partner/business)
│   ├── Services/                  # Business operations (registration, upgrades, fees…)
│   └── Support/                   # Phone value helper
├── bootstrap/app.php              # Laravel 11+ app config (routing, middleware, exceptions)
├── config/                        # Standard Laravel config + custom pios.php
├── database/
│   ├── factories/                 # Model factories (test/seed data)
│   ├── migrations/                # Schema (chronological)
│   └── seeders/                   # Demo/system/order/product-image seeders
├── lang/{en,ms}/pios.php          # Bilingual translation strings
├── resources/
│   ├── css/, js/                  # Vite/Tailwind 4 assets + Filament admin theme
│   └── views/
│       ├── filament/              # Blade overrides for pages/widgets/partials
│       └── public/                # Public product page + layout
├── routes/{web,console}.php       # Public routes + scheduled commands
└── tests/{Feature,Unit}/          # Pest/PHPUnit tests
```

## Directory Purposes

**`app/Models/`:**
- Purpose: domain entities, ownership `scopeVisibleTo` scopes, eligibility logic, relationships.
- Key files: `User.php` (capability/acting-hat auth), `Customer.php`, `Partner.php` (downline + `MAX_DIRECT_PARTNERS`), `BusinessOwner.php`, `Product.php`.
- Shared: `app/Models/Concerns/ScopesByOwnerVisibility.php` (dual-owner visibility trait).

**`app/Filament/Resources/<Name>/`:**
- Purpose: the CRUD UI for one model. See conventions below.
- Note: **plural, folder-per-resource** naming is the current convention (`Customers/`, `Products/`, `BusinessOwners/`). Empty singular legacy shells exist (`AuditLogResource/`, `FeeSettingResource/`, `PartnerTierResource/`, `ProductTypeResource/`, `TerritoryResource/`, `UserResource/`) — they contain no PHP files; ignore/remove them, do not add to them.

**`app/Policies/`:**
- Purpose: per-model authorization. One `<Model>Policy` each, registered in `AppServiceProvider::boot()`.
- Shared: `app/Policies/Concerns/AllowsAdminSystemAccess.php` — trait for admin/system-only resources (gates every ability on `UserRole::canManageSystem()`).

**`app/Services/`:**
- Purpose: transactional/cross-cutting workflows. Key files: `IdentifierGenerator.php`, `AuditLogger.php`, `RegistrationAccountCreator.php`, `RegistrationApprover.php`, `PartnerUpgradeService.php`, `BusinessOwnerUpgradeService.php`, `PartnerQuotaService.php`, `OperationalReportService.php`, `ManualOrderService.php`, `UstazAiBlaster.php`.

**`app/Enums/`:**
- Purpose: typed domain vocabulary. `UserRole`, `UserStatus`, `RecordStatus`, `CustomerCategory`, `ProductClassification`, `ProductStatus`, `ProductProposalStatus`, `ProductSelectionStatus`, `FeeType`, `FeeStatus`, `PaymentStatus`, `OrderStatus`, `PromotionMode`, `PromotionStatus`. Backed string enums with `label()`/`options()` helpers.

**`database/migrations/`:**
- Purpose: schema, chronological. Foundation tables in `2026_06_06_000002_create_pios_foundation_tables.php`; later migrations add promotions, vendors, BO-owned products, nullable customer BO, VIP boolean, CRM notes/follow-ups, phone normalization.

**`tests/Feature/`:**
- Purpose: end-to-end behavior tests (the bulk of coverage). One file per feature area (`HierarchyPolicyTest`, `DualRoleUpgradeTest`, `ProductCatalogTest`, `PartnerQuotaServiceTest`, `FilamentAccessTest`, etc.). `tests/Unit/` holds `IdentifierGeneratorTest`, `PhoneTest`.

## Key File Locations

**Entry Points:**
- `app/Providers/Filament/AdminPanelProvider.php`: `/admin` panel (default).
- `app/Providers/Filament/PartnerPanelProvider.php`: `/partner` panel.
- `app/Providers/Filament/BusinessPanelProvider.php`: `/business` panel.
- `routes/web.php`: public product page + order + locale + welcome.
- `bootstrap/app.php`: global middleware (`SetLocale` on `web`), routing, exception rendering.

**Configuration:**
- `config/pios.php`: app-specific config.
- `config/app.php`: supported locales, default timezone.
- `.env` / `.env.example`: environment (do not commit secrets).

**Core Logic:**
- `app/Models/Concerns/ScopesByOwnerVisibility.php`: central ownership scope.
- `app/Models/User.php`: capability vs acting-hat auth model.
- `app/Providers/AppServiceProvider.php`: policy registration + MYR/locale defaults.

**Testing:**
- `tests/TestCase.php`: base case. `phpunit.xml`: config (SQLite `database/database.sqlite` for tests).

## Filament Resource Conventions

Each resource is a **folder** `app/Filament/Resources/<PluralName>/` containing:

```
<PluralName>/
├── <SingularName>Resource.php     # The Resource class: $model, nav, getEloquentQuery, wiring
├── Pages/                         # List<..>, Create<..>, Edit<..>, View<..> page classes
├── Schemas/                       # <..>Form.php (form), <..>Infolist.php (view layout)
├── Tables/                        # <..>Table.php (list/table definition)
├── RelationManagers/              # (optional) related-record editors
└── Actions/                       # (optional) custom table/page actions
```

Rules observed (see `app/Filament/Resources/Customers/CustomerResource.php`):
- The Resource class is thin: it names `$model`, sets navigation (`getNavigationGroup`/`getNavigationLabel` via `__('pios.nav.*')` for i18n), and delegates `form()`/`table()`/`infolist()` to the `Schemas`/`Tables` classes.
- **`getEloquentQuery()` MUST apply the ownership scope**: `return $user ? $query->visibleTo($user) : $query->whereRaw('1 = 0');` — this is the enforcement seam. Never omit it on an owned resource.
- Form/table classes expose a static `configure(Schema|Table $x): Schema|Table`.
- `getPages()` maps `index`/`create`/`view`/`edit` to page classes via `::route()`.
- Column-level gating (e.g. admin-only cost/vendor/margin on Product) lives inside the shared `Schemas`/`Tables` classes, keyed on `auth()->user()` capability.

## Panel → Resource/Widget Map

**`/admin` (`AdminPanelProvider`)** — SysAdmin/PiosOwner:
- Resources/pages/widgets are **auto-discovered** (`discoverResources`/`discoverPages`/`discoverWidgets`) — every resource in `app/Filament/Resources` is available here.
- Explicit: `Dashboard`, `AccountWidget`, `OperationalReports` page.
- Nav groups: register, business_owner, products, fees, reports, system.

**`/partner` (`PartnerPanelProvider`)** — Partner + admins:
- Resources (explicit): `PartnerResource`, `BusinessOwnerResource`, `CustomerResource`.
- Widgets: `PartnerQuotaWidget`, `PiosStatsOverview`, `PartnerBusinessOwners`, `AccountWidget`.
- Nav groups: register, Customers.

**`/business` (`BusinessPanelProvider`)** — BusinessOwner + admins:
- Resources (explicit): `CustomerResource`, `ProductResource`, `IncomingOrderResource`.
- Widgets: `BusinessStatsOverview`, `FollowUpsDueWidget`, `CustomersByCategoryChart`, `BusinessCustomers`, `AccountWidget`.
- Custom login page: `app/Filament/Pages/Auth/BusinessLogin.php`.
- Nav groups: customers, products, orders.

**Shared across panels:** `PiosLoginPlugin`, `PanelSwitcher` + `LocaleSwitcher` user-menu items, `SetLocale` middleware, `#174A78` primary brand color, `resources/css/filament/admin/theme.css`.

**Widgets inventory** (`app/Filament/Widgets/`): `BusinessCustomers`, `BusinessStatsOverview`, `CustomersByCategoryChart`, `FollowUpsDueWidget`, `LatestOrders`, `MonthlyRevenueChart`, `PartnerBusinessOwners`, `PartnerDownlineTree`, `PartnerQuotaWidget`, `PiosStatsOverview`.

## Naming Conventions

**Files/Classes:**
- Models: singular PascalCase (`BusinessOwner.php`).
- Resources: plural folder + `<Singular>Resource` class (`Products/ProductResource.php`).
- Policies: `<Model>Policy`. Services: `<Verb/Noun>Service` or descriptive (`RegistrationApprover`).
- Enums: singular PascalCase, backed by `string`.
- Migrations: `YYYY_MM_DD_NNNNNN_<verb>_<subject>.php`.

**Domain vocabulary:** system-generated IDs use prefixes from `IdentifierGenerator::DEFAULTS` (`P`, `BO`, `C`, `VIP`, `PRD`, `ORD`). Many domain fields keep Malay names (`bandar`, `negeri`, `poskod`, `ic_no`/`kp_no`, `hp_no`).

## Where to Add New Code

**New Filament resource (owned entity):**
- Create `app/Filament/Resources/<Plural>/` with the full folder structure above.
- Add ownership: give the model a `scopeVisibleTo` (reuse `ScopesByOwnerVisibility` if it has both `partner_id` + `business_owner_id`), call `->visibleTo($user)` in `getEloquentQuery()`.
- Register a `<Model>Policy` in `AppServiceProvider::boot()`.
- Register the resource explicitly in `PartnerPanelProvider`/`BusinessPanelProvider` if it belongs there (admin auto-discovers).
- Add i18n keys to `lang/en/pios.php` and `lang/ms/pios.php`.

**New business workflow / multi-step operation:**
- Add a service in `app/Services/`; inject `IdentifierGenerator`/`AuditLogger` via constructor; wrap writes in `DB::transaction`; throw `ValidationException::withMessages()` on guard failures; call `AuditLogger::log()`.

**New model + table:**
- `app/Models/<Model>.php` (+ `#[Fillable([...])]` attribute, `casts()`, relationships, scope), migration in `database/migrations/`, factory in `database/factories/` if tested.

**New enum:** `app/Enums/<Name>.php`, backed string, with `label()`/`options()`.

**New dashboard widget:** `app/Filament/Widgets/`; register in the relevant panel provider's `->widgets([...])`.

**Tests:** feature tests in `tests/Feature/<Area>Test.php` (primary style); pure helpers in `tests/Unit/`.

## Special Directories

**`database/database.sqlite`:** SQLite file used by the test suite (production is MySQL `pios_my`). Committed shell; test data transient.

**`app/Filament/Resources/*Resource/` (singular legacy shells):** empty leftover directories from an older Filament generator layout. Not committed with PHP files, not wired anywhere — do not add code here; the live resources are the plural folders.

**`.planning/`:** GSD roadmap/state (milestones, phases, research, this codebase map). Not application code.

**`resources/views/filament/`:** Blade overrides for custom Filament pages/widgets (auth login screens, operational reports, partner downline tree, language switcher partial).

---

*Structure analysis: 2026-07-01*
