# Testing Patterns

**Analysis Date:** 2026-07-01

## Test Framework

**Runner:**
- PHPUnit `^12.5.12` (`phpunit/phpunit` in `require-dev`).
- Config: `phpunit.xml`.
- Support: `mockery/mockery ^1.6`, `fakerphp/faker ^1.23`, `nunomaduro/collision ^8.6`.

**Assertions:**
- PHPUnit assertions (`$this->assertSame`, `assertTrue`, `assertNull`) plus Laravel testing helpers (`assertDatabaseHas`, `assertModelExists`) and Filament/Livewire fluent assertions (`assertHasNoFormErrors`, `assertSuccessful`, `assertForbidden`).

**Test database (`phpunit.xml` `<php>` env):**
- `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` — in-memory SQLite, so the schema is built fresh per run.
- Also set for tests: `APP_ENV=testing`, `BCRYPT_ROUNDS=4`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `MAIL_MAILER=array`, Pulse/Telescope/Nightwatch disabled.
- Because tests run on SQLite while production is MySQL, hierarchy queries are written to work on both (e.g. `Partner::descendantIds()` is breadth-first rather than a recursive CTE).

**Run commands:**
```bash
php artisan test                              # run the whole suite
php artisan test --filter=DualRoleUpgradeTest # single test class
php artisan test --filter=test_acting_hat_follows_the_active_panel  # single method
php artisan test tests/Feature/CustomerRegistrationTest.php         # single file
vendor/bin/phpunit --testsuite=Feature        # via phpunit directly
```

## Suite Size & Layout

- **~206 test methods** (`grep -rn "public function test" tests/` → 206).
- Two suites declared in `phpunit.xml`: `Unit` (`tests/Unit`) and `Feature` (`tests/Feature`).
- The overwhelming majority live in **`tests/Feature/`** (~39 files); `tests/Unit/` holds only `IdentifierGeneratorTest.php`, `PhoneTest.php`, and the scaffold `ExampleTest.php`.
- `.phpunit.result.cache` is committed/present (enables ordering by prior results).

**Base class:** `tests/TestCase.php` is intentionally bare (extends `Illuminate\Foundation\Testing\TestCase`, no shared helpers). Each test class **defines its own private fixture-builder helpers** (e.g. `dualRole()`, `businessOwnerUser($partnerNo, $boNo, $name)`) that return tuples of `[user, partner, businessOwner, ...]`.

## Test File Organization

- Location: separate `tests/Feature/` and `tests/Unit/` directories (not co-located with source).
- Naming: `<Feature>Test.php`, methods `test_snake_case_description(): void` (long descriptive names, e.g. `test_business_owner_can_create_customer_under_own_profile`).
- Grouping inside a class via `// ---- Section ----` comment banners.

## Test Structure

Every feature test uses `RefreshDatabase` and, when it touches panel-aware behavior, resets the panel in `tearDown()`:

```php
class DualRoleUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);   // avoid panel leaking across tests
        parent::tearDown();
    }

    public function test_acting_hat_follows_the_active_panel(): void
    {
        [$user] = $this->dualRole();
        Filament::setCurrentPanel('business');
        $this->assertTrue($user->actsAsBusinessOwner());
    }
}
```

## Key Testing Patterns

**1. Livewire / Filament page testing** — exercise Resource pages as Livewire components:

```php
use Livewire\Livewire;

$this->actingAs($businessOwnerUser);
Livewire::test(CreateCustomer::class)          // App\Filament\Resources\Customers\Pages\CreateCustomer
    ->fillForm([
        'name' => 'Customer One',
        'hp_no' => '60130010001',
        'category' => CustomerCategory::Customer->value,
    ])
    ->call('create')
    ->assertHasNoFormErrors();
```

**2. Table actions** — invoke Filament table actions by name against a record (used across ~20 feature files):

```php
Livewire::test(ListCustomers::class)
    ->callTableAction('tagVip', $customer, data: ['is_vip' => true]);
```

**3. Panel-aware behavior** — set the active panel to exercise the acting-hat / visibility logic:

```php
Filament::setCurrentPanel('partner');   // or 'business' / null
// ... assert scopeVisibleTo / actsAsPartner outcomes
```
`canAccessPanel` checks use `filament()->getPanel('partner')`.

**4. Policy testing** — assert authorization directly through the gate as a specific user:

```php
use Illuminate\Support\Facades\Gate;

$this->assertTrue(Gate::forUser($partnerUser)->allows('view', $ownedBusinessOwner));
$this->assertFalse(Gate::forUser($partnerUser)->allows('view', $unrelatedBusinessOwner));
```

**5. Audit assertions** — verify the audit trail was written:

```php
$this->assertDatabaseHas(AuditLog::class, [
    'auditable_type' => Customer::class,
    'auditable_id'   => $customer->id,
    'event'          => 'created',
]);
```

## Factories & States

Located in `database/factories/` (5 factories). Rich named states are the primary fixture mechanism:

- **`UserFactory.php`** — base user; state `unverified()`.
- **`PartnerFactory.php`** — creates its own `User`; state `pending()`.
- **`BusinessOwnerFactory.php`** — creates its own `User` + parent `Partner`; state `pending()`.
- **`CustomerFactory.php`** — states `vip()` (LOS, Amway-eligible), `lead()` (name+phone only), `prospect()`, `forBusinessOwner(BusinessOwner $bo)` (sets `business_owner_id` + follows `partner_id`), `forPartner(Partner $p)` (legacy partner-owned, `business_owner_id = null`).
- **`ProductFactory.php`** — state `amway()` (classification).

Factory idioms:
- Related ownership stays consistent — `CustomerFactory` derives `partner_id` from the chosen `business_owner_id` via a lazy closure so hierarchy invariants hold.
- Enums assigned directly (`'category' => CustomerCategory::Customer`, `'status' => RecordStatus::Active`).
- Phones use MY-shaped fakes (`'601'.fake()->numerify('########')`).

Typical composition in a test:
```php
$bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);
$vip = Customer::factory()->forBusinessOwner($bo)->vip()->create();
$partnerDirect = Customer::factory()->forPartner($partner)->create();
```

## Notable Test Files

- `tests/Feature/DualRoleUpgradeTest.php` — panel access, acting-hat resolution, per-panel customer visibility (the core no-leak), BO→Partner upgrade services.
- `tests/Feature/PartnerDownlineTest.php` — recursive downline (`descendantIds`, `flatDownline`) on SQLite.
- `tests/Feature/HierarchyPolicyTest.php` — `Gate::forUser` policy allow/deny across the hierarchy.
- `tests/Feature/CustomerRegistrationTest.php` — BO customer creation via `CreateCustomer` Livewire page, id generation, audit.
- `tests/Feature/PartnerVipTaggingTest.php` — `callTableAction('tagVip', ...)` authorization.
- `tests/Feature/PartnerRecruitmentTest.php` / `PartnerRegistrationTest.php` — MAX_DIRECT_PARTNERS cap, pending→HQ approval.
- `tests/Feature/ProductSelectionAndProposalTest.php`, `ProductShareTest.php`, `ProductWhatsAppBlastTest.php` — product catalog, selection/proposal, WhatsApp promotion.
- `tests/Feature/CommerceAndFeesTest.php`, `MonthlyBusinessOwnerFeeTest.php`, `BusinessOwnerSetupFeeTest.php`, `FeeQuotaFoundationTest.php` — fee generation/idempotency, quotas.
- `tests/Feature/FilamentAccessTest.php`, `PanelSwitcherAndResetTest.php` — panel gating and switching.
- `tests/Feature/LoginCopyAndLocaleTest.php` — bilingual copy / locale.
- `tests/Unit/IdentifierGeneratorTest.php`, `tests/Unit/PhoneTest.php` — the two pure-logic support units.

## Test Types

- **Feature tests** dominate: they boot the app, hit Filament pages/actions through Livewire, and assert DB + authorization outcomes.
- **Unit tests** are reserved for framework-free logic (`IdentifierGenerator`, `Phone`).
- **E2E / browser tests:** none (no Dusk/Playwright).

## Coverage

No enforced coverage threshold and no coverage driver configured in `phpunit.xml`. To generate a report locally you would need Xdebug/PCOV:
```bash
php artisan test --coverage
```

## Common Patterns Reference

**Form-error assertions (validation):**
```php
Livewire::test(CreateCustomer::class)->fillForm([...])->call('create')->assertHasFormErrors(['hp_no']);
```

**Forbidden / policy denial through Livewire:**
```php
Livewire::test(EditCustomer::class, ['record' => $foreign->id])->assertForbidden();
```

**Panel reset discipline:** any test that calls `Filament::setCurrentPanel(...)` must reset it to `null` in `tearDown()` to prevent hat/visibility bleed into later tests.

---

*Testing analysis: 2026-07-01*
