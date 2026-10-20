---
phase: 09-vendor-product-sourcing
plan: 02
subsystem: authorization
tags: [filament, authorization, column-level, product, vendor, margin, i18n, tests]

requires:
  - phase: 09-vendor-product-sourcing
    plan: 01
    provides: vendors table, products.vendor_id FK + products.cost, Product::vendor() relation + cost cast, admin-only VendorResource + VendorPolicy
provides:
  - admin-only cost + vendor_id fields in the SHARED ProductForm (gated by ->visible(isAdmin()) → absent + non-dehydrated for non-admins)
  - admin-only cost, vendor.name, and computed null-safe margin columns in the SHARED ProductsTable
  - margin = selling_price - cost as an admin-only computed (non-DB) column, null-safe when cost is null
  - bilingual pios.product labels (cost/vendor/margin/sourcing) in en + ms with matching keys
  - tests/Feature/VendorSourcingTest.php — column-level isolation proof (admin sees, BO + Partner do not) + vendor policy/route denial
affects: [pios-mart, reports]

tech-stack:
  added: []
  patterns:
    - "Column-level (field) authorization in a SHARED Filament resource = ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false) on the field/column, NOT ->hidden()/->disabled() (must be absent + non-dehydrated)"
    - "Non-mountable-page schema isolation test = build the schema via Schema::make(new PageClass) + getFlatFields(withHidden: false) acting as the user, since admin-only create policy blocks Livewire::test(CreateProduct) mounting for non-admins"

key-files:
  created:
    - tests/Feature/VendorSourcingTest.php
  modified:
    - app/Filament/Resources/Products/Schemas/ProductForm.php
    - app/Filament/Resources/Products/Tables/ProductsTable.php
    - lang/en/pios.php
    - lang/ms/pios.php

key-decisions:
  - "cost/vendor gated with ->visible(isAdmin()) inside ProductForm/ProductsTable (not panel config) because ProductResource is SHARED — BusinessPanelProvider registers it, so the same form/table classes render in both panels"
  - "BO/Partner form-field absence proven at the SCHEMA level (getFlatFields withHidden:false) rather than via Livewire::test(CreateProduct), because ProductPolicy::create is admin-only and a non-admin cannot mount the create page (getDefaultTestingSchemaName on null)"
  - "margin is a computed non-DB column; null-safe (returns null when cost is null) and emitted as an 'RM '-prefixed number_format string — no ->money() double-format"

patterns-established:
  - "Confidential commercial columns (cost/vendor/margin) in a shared resource are isolated field-by-field via ->visible(isAdmin()), with a dedicated authorization test asserting absence for every non-admin role + policy/route denial"

requirements-completed: [SRC-03, SRC-04]

duration: 6min
completed: 2026-06-20
---

# Phase 9 Plan 02: Product Cost / Vendor / Margin Column-Level Isolation Summary

**Cost, vendor, and margin now render ONLY in /admin and are absent (not dehydrated) for partner and business panel users in the SHARED ProductResource — gated by `->visible(isAdmin())` and proven by a dedicated column-level authorization test (SRC-03); HQ-only computed null-safe margin = selling_price − cost (SRC-04).**

## Performance

- **Duration:** ~6 min
- **Started:** 2026-06-20T07:00:20Z
- **Completed:** 2026-06-20T07:06:13Z
- **Tasks:** 2
- **Files modified:** 5 (1 created, 4 modified)

## Accomplishments
- Added admin-only `vendor_id` Select and `cost` TextInput to the shared `ProductForm` Pricing section, gated with `->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)` so the fields are removed from the schema AND non-dehydrated for non-admins (Partner + Business Owner).
- Added admin-only `cost`, `vendor.name`, and computed `margin` columns to the shared `ProductsTable`; margin is a null-safe non-DB column (`RM '.number_format(selling_price - cost, 2)`, returns null when cost is null).
- Added bilingual `pios.product` labels (`cost`/`vendor`/`margin`/`sourcing`) to `lang/en/pios.php` and `lang/ms/pios.php` with identical key sets (`Cost`→`Kos`, `Sourcing`→`Sumber`).
- Created `tests/Feature/VendorSourcingTest.php` (7 tests) proving column-level isolation, vendor policy denial, and vendor route isolation.

## Task Commits

Each task was committed atomically:

1. **Task 1: gate cost/vendor in ProductForm + admin-only cost/vendor/margin columns in ProductsTable + bilingual labels** - `222e0ed` (feat)
2. **Task 2: column-level authorization isolation test (VendorSourcingTest)** - `24f858c` (test)

**Plan metadata:** see final docs commit.

## Files Created/Modified
- `app/Filament/Resources/Products/Schemas/ProductForm.php` - admin-only vendor_id + cost fields in Pricing section, `->visible(isAdmin())` gated
- `app/Filament/Resources/Products/Tables/ProductsTable.php` - admin-only cost, vendor.name, and computed null-safe margin columns
- `lang/en/pios.php`, `lang/ms/pios.php` - `pios.product` labels (matching keys)
- `tests/Feature/VendorSourcingTest.php` - column-level isolation + vendor policy + vendor route test

## Decisions Made
- Gating lives inside `ProductForm`/`ProductsTable` (not panel config) because `ProductResource` is SHARED across the admin and business panels — the same classes render in both.
- `->visible(false)` (not `->hidden()`/`->disabled()`) is used so the field is absent from the schema and non-dehydrated: a non-admin can never submit cost/vendor. This is the column-level guarantee named in PITFALLS.md Pitfall 6 (row-level `scopeVisibleTo` is NOT sufficient).
- `margin` is computed in the table layer only (non-DB), null-safe.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] BO/Partner form-field absence proven at the schema level instead of via `Livewire::test(CreateProduct)`**
- **Found during:** Task 2 (RED)
- **Issue:** The plan's `<interfaces>` suggested `Livewire::test(CreateProduct::class)` acting as a BO/Partner with `assertFormFieldDoesNotExist`. That mount fails with `Call to a member function getDefaultTestingSchemaName() on null` because `ProductPolicy::create` is admin-only — a non-admin cannot mount the create page at all (the page aborts before the Livewire component is bound).
- **Fix:** For non-admin roles, the test builds the shared form schema directly via `ProductForm::configure(Schema::make(new CreateProduct))` and asserts `cost`/`vendor_id` are absent from `getFlatFields(withHidden: false)` while acting as the BO/Partner. `getFlatFields(withHidden: false)` excludes `->visible(false)` fields, which is exactly the absent + non-dehydrated column-level guarantee. The admin case still uses the authoritative `Livewire::test(CreateProduct)->assertFormFieldExists(...)` (admin can mount) plus the same schema check.
- **Files modified:** tests/Feature/VendorSourcingTest.php
- **Commit:** 24f858c

## Issues Encountered
- Same as the deviation above: admin-only create policy blocks Livewire mounting for non-admins; resolved by schema-level field introspection. Table isolation test sets the current panel explicitly (`Filament::setCurrentPanel('business'|'admin')`) so `ListProducts` renders in the correct panel context.

## Known Stubs
None. cost/vendor/margin are fully wired and admin-gated; the only intentionally-absent rendering (cost/vendor/margin for non-admins) is the required SRC-03 behavior, proven by VendorSourcingTest.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- SRC-01..04 complete; the vendor/product-sourcing layer is done. cost/vendor/margin are confidential and admin-isolated across the shared ProductResource — PIOS Mart and Reports can safely surface selling_price without leaking cost.

## Verification
- `php artisan test --filter=VendorSourcingTest` — **7 passed, 22 assertions** (column-level isolation + vendor policy + vendor route denial).
- `php artisan test --filter=ProductCatalogTest` — **4 passed, 23 assertions** (existing admin product create/visibility unaffected).
- `php artisan test` (FULL suite) — **109 passed, 604 assertions**, zero regressions (was 102; +7 new).
- en/ms `pios.product` label keys identical (`cost`/`vendor`/`margin`/`sourcing`).

## Self-Check: PASSED

All claimed files exist on disk; both task commits (222e0ed, 24f858c) are present in git history.

---
*Phase: 09-vendor-product-sourcing*
*Completed: 2026-06-20*
