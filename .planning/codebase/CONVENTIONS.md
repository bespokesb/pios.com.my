# Coding Conventions

**Analysis Date:** 2026-07-01

Stack: Laravel 13 + FilamentPHP 5 + MySQL, PHP 8.4. These are the conventions
actually in force in this repo — follow them when adding code.

## Naming Patterns

**Files:**
- Models: singular PascalCase — `app/Models/Customer.php`, `app/Models/BusinessOwner.php`.
- Enums: PascalCase in `app/Enums/` — `app/Enums/CustomerCategory.php`, `app/Enums/RecordStatus.php`.
- Services: PascalCase `*Service`/`*Logger`/`*Generator` in `app/Services/` — `app/Services/AuditLogger.php`, `app/Services/IdentifierGenerator.php`, `app/Services/RegistrationApprover.php`.
- Policies: `<Model>Policy` in `app/Policies/` (one per model) — `app/Policies/CustomerPolicy.php`.
- Filament classes: see the Filament section — Resources decompose into `Schemas/`, `Tables/`, `Pages/`, `Actions/`, `RelationManagers/`.
- Tests: `<Feature>Test.php` in `tests/Feature/`.

**Database columns / fields:** `snake_case`. Malay domain field names are used verbatim and MUST be preserved:
- `bandar` (town), `negeri` (state), `poskod` (postcode), `ic_no` / `kp_no` (ID number), `hp_no` (mobile), `abo_no`, `diamond_name`, `spouse_ic_no`.
- System-generated unique ids: `partner_no`, `business_owner_no`, `customer_no`, `product_no` (see IdentifierGenerator below).

**Functions/methods:** `camelCase`. Boolean capability/hat methods read as predicates: `isAdmin()`, `isPartner()`, `hasPartnerProfile()`, `actsAsPartner()`, `canBuyProduct()`, `canAccessPanel()`.

**Types:** enum cases are PascalCase (`CustomerCategory::Lead`, `RecordStatus::Active`).

## PHP 8.4 Model Style

Models use **PHP 8.4 attributes, not legacy array properties**:

```php
#[Fillable([
    'business_owner_id', 'partner_id', 'customer_no', 'category',
    'is_vip', 'name', 'hp_no', 'hp_no_normalized', 'kp_no', 'status', 'created_by',
])]
class Customer extends Model
```

- Use `#[\Illuminate\Database\Eloquent\Attributes\Fillable([...])]` instead of `protected $fillable`.
- Use `#[\Illuminate\Database\Eloquent\Attributes\Hidden([...])]` instead of `protected $hidden` — see `app/Models/User.php` (`#[Hidden(['password', 'remember_token'])]`).
- Casts go in a **`casts(): array` method** (not a `$casts` property), returning enum-backed casts:
  ```php
  protected function casts(): array
  {
      return ['category' => CustomerCategory::class, 'is_vip' => 'boolean', 'status' => RecordStatus::class];
  }
  ```
- Relationships are typed (`: BelongsTo`, `: HasMany`, `: HasOne`, `: BelongsToMany`) with return type declarations.
- Model event hooks live in `booted(): void` (e.g. `Customer::booted()` keeps `hp_no_normalized` in sync on save).
- Constants for business rules on the model: `Partner::MAX_DIRECT_PARTNERS = 4`.
- Strict typing throughout: typed params, typed returns, typed closures (`fn (Builder $q) => ...`), and array-shape docblocks (`@return array<int, int>`, `@return array<string, string>`).

## Enum Classes (`app/Enums/`)

Backed string enums with two conventional helpers:

```php
enum CustomerCategory: string
{
    case Lead = 'lead';
    // ...
    public function label(): string { return match ($this) { self::Lead => 'Lead', /* ... */ }; }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c): array => [$c->value => $c->label()])
            ->all();
    }
}
```

- Every enum exposes `label()` (display) and, where used in Filament selects, a static `options()` returning `value => label`.
- Behavior can live on the enum (e.g. `UserRole::isAdmin()` consumed by `User::isAdmin()`).
- Full set in `app/Enums/`: `CustomerCategory`, `RecordStatus`, `UserRole`, `UserStatus`, `ProductClassification`, `ProductStatus`, `ProductSelectionStatus`, `ProductProposalStatus`, `FeeStatus`, `FeeType`, `OrderStatus`, `PaymentStatus`, `PromotionMode`, `PromotionStatus`.

## Code Style / Formatting

**Laravel Pint** (`laravel/pint` in `require-dev`, binary `vendor/bin/pint`). No `pint.json` is present, so the **default Laravel preset** applies.

```bash
./vendor/bin/pint            # format the codebase
./vendor/bin/pint --test     # check without writing (CI / pre-commit)
```

`.editorconfig` enforces: UTF-8, LF line endings, 4-space indent (2 for YAML), final newline, trimmed trailing whitespace. Markdown keeps trailing whitespace.

## Import Organization

Pint's default preset governs ordering (alphabetical, single `use` per line, no grouping braces). Observed order in files: framework/vendor `use` statements and app `use` statements are interleaved alphabetically by FQCN, e.g. `App\Enums\...`, `App\Models\...`, `App\Support\Phone`, then `Illuminate\...`, `Filament\...`. No path aliases (this is PHP/PSR-4, autoloaded via `App\` → `app/`).

## Filament Conventions

Resources are **decomposed into sub-namespaces**, not monolithic files. Example under `app/Filament/Resources/Customers/`:
- `Schemas/CustomerForm.php`, `Schemas/CustomerInfolist.php` — form/infolist definitions, each a class with `public static function configure(Schema $schema): Schema`.
- `Tables/CustomersTable.php` — `public static function configure(Table $table): Table`.
- `Pages/` — `CreateCustomer`, `EditCustomer`, `ListCustomers`, `ViewCustomer`.
- `Actions/` — extracted custom actions (e.g. `CustomerContactActions`).
- `RelationManagers/` — related-record editors.

**Policy-driven action/column visibility** — never rely on UI hiding alone; gate on the policy through `->visible()`:

```php
->visible(fn (Product $record): bool => auth()->user()?->can('share', $record) ?? false)
```

And re-check server-side inside the action body:

```php
abort_unless(auth()->user()?->can('share', $record) ?? false, 403);
```

Column-level gating (admin-only cost/vendor/margin) uses shared `visibleToNonBusinessOwner()`-style predicates in `ProductsTable`/`CustomersTable`. Owner-scoped form defaults use the acting hat: `->default(fn () => auth()->user()?->businessOwner?->id)->disabled(fn () => (bool) auth()->user()?->actsAsBusinessOwner())`.

## Authorization Idioms

Authorization is a **shared layer**, enforced server-side, never UI-only:

- **Query scopes** — `scopeVisibleTo(Builder $query, User $user)`. Models carrying both `partner_id` and `business_owner_id` (Customer, Order, Promotion, Fee) use the trait `app/Models/Concerns/ScopesByOwnerVisibility.php`; `Partner` defines its own downline-aware `scopeVisibleTo`. Policies delegate `view()` to the scope: `Customer::query()->visibleTo($user)->whereKey($customer->id)->exists()`.
- **Capability vs acting hat** (`app/Models/User.php`): capability predicates `hasPartnerProfile()` / `hasBusinessOwnerProfile()` / `isPartner()` / `isBusinessOwner()` ask "does this user hold this identity at all"; acting-hat predicates `actsAsPartner()` / `actsAsBusinessOwner()` resolve which role a possibly dual-role user is exercising **in the current request**, from `Filament::getCurrentPanel()`. A single-role user's hat always equals capability. Use the hat only at disambiguation points (ownership scopes, owner resolution, shared-form gating).
- **Policies** (`app/Policies/*Policy.php`, one per model) — standard `viewAny/view/create/update/delete/deleteAny` plus domain methods (e.g. `CustomerPolicy::tagVip`). Private helpers encode nuanced rules (e.g. `ownsPartnerDirectCustomer`).
- **Panel access** — `User::canAccessPanel()` maps panel id → capability; the three panels (`admin`, `partner`, `business`) are mutually exclusive except admins.

## Audit Logging

Mutations of sensitive records are audited via `App\Services\AuditLogger`:

```php
app(AuditLogger::class)->log($actor, 'created', $model, $oldValues, $newValues);
```

- Records `actor_id`, `auditable_type`/`auditable_id` (morph), `event`, `old_values`/`new_values`, `ip_address`, `user_agent`.
- Called on create/update of Partner, BusinessOwner, Customer, Product, Fee, and admin records.
- Scheduled jobs act as the system user: `User::system()` (idempotent `firstOrCreate` of `system@pios.com.my`, inactive SysAdmin).

## System-Generated Identifiers

Never accept manual entry for `*_no` fields. Generate via `App\Services\IdentifierGenerator`:

```php
app(IdentifierGenerator::class)->generate('customer', Customer::class, 'customer_no'); // "C000001"
```

- Prefix/padding defaults in `IdentifierGenerator::DEFAULTS` (`partner`→`P`, `business_owner`→`BO`, `customer`→`C`, `vip_customer`→`VIP`, `product`→`PRD`, `order`→`ORD`, all padding 6).
- Uses `IdentifierSequence` rows with `lockForUpdate()` inside a DB transaction and a uniqueness retry loop — concurrency-safe.

## Phone Normalization

All phone handling goes through `App\Support\Phone`:
- `Phone::digits($raw)` — strip to digits (used to maintain the indexed `hp_no_normalized` column for duplicate detection).
- `Phone::toWhatsApp($raw)` — MY intl `wa.me` digits (e.g. `60123456789`).
- `Phone::toTel($raw)` — E.164-ish `+60...`.

Models keep an indexed `hp_no_normalized` in sync in `booted()` on `hp_no` change — do the same for any new phone-bearing model.

## Bilingual i18n

Every user-facing string goes through `__('pios.*')` — no hardcoded English (or Malay) labels.

- Two files must stay in **key parity**: `lang/en/pios.php` and `lang/ms/pios.php` (both 355 lines). Adding a key to one requires the same key in the other.
- Nested/dotted keys namespaced by area: `pios.nav.group.*`, `pios.customer.vip.is_vip`, `pios.customer.partner_owned`, `pios.language.*`.
- Filament labels use it directly: `->label(__('pios.customer.vip.is_vip'))`, `->placeholder(__('pios.customer.partner_owned'))`.

## Function & Module Design

- Filament schema/table/action builder classes are stateless with static `configure(...)`/factory methods.
- Services are constructor-injected (`AuditLogger(private readonly ?Request $request = null)`), resolved via `app(...)` or the container.
- Return typed values; prefer explicit early returns and `match` over nested conditionals (see `User::actsAsPartner()`, `canAccessPanel()`).
- Array shapes documented with generics docblocks where non-trivial.

---

*Convention analysis: 2026-07-01*
