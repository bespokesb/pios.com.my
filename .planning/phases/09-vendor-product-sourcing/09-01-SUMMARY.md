---
phase: 09-vendor-product-sourcing
plan: 01
subsystem: database
tags: [filament, eloquent, vendor, sourcing, policy, migration, i18n]

requires:
  - phase: 06-fee-quota-correctness-foundation
    provides: RecordStatus enum, AllowsAdminSystemAccess admin-only policy concern, Territory admin-resource pattern
provides:
  - vendors table (name, contact_name, phone, email, address, status)
  - products.vendor_id nullable FK (nullOnDelete) + products.cost decimal(10,2) nullable
  - Vendor model with products() HasMany and status cast
  - Product model vendor() BelongsTo + vendor_id/cost fillable + cost decimal:2 cast
  - admin-only VendorResource (auto-discovered into /admin, System group)
  - VendorPolicy (AllowsAdminSystemAccess) registered in AppServiceProvider
  - bilingual pios.vendor labels (en + ms)
affects: [09-02-product-cost-isolation, pios-mart, reports]

tech-stack:
  added: []
  patterns:
    - "Admin-only Filament resource = mirror Territory (auto-discovered /admin only) + AllowsAdminSystemAccess policy"
    - "Schema extensions via NEW dated migrations, never editing the foundation migration"

key-files:
  created:
    - database/migrations/2026_06_20_000002_create_vendors_table.php
    - database/migrations/2026_06_20_000003_add_vendor_and_cost_to_products.php
    - app/Models/Vendor.php
    - app/Policies/VendorPolicy.php
    - app/Filament/Resources/Vendors/VendorResource.php
    - app/Filament/Resources/Vendors/Schemas/VendorForm.php
    - app/Filament/Resources/Vendors/Tables/VendorsTable.php
    - app/Filament/Resources/Vendors/Pages/ListVendors.php
    - app/Filament/Resources/Vendors/Pages/CreateVendor.php
    - app/Filament/Resources/Vendors/Pages/EditVendor.php
  modified:
    - app/Models/Product.php
    - app/Providers/AppServiceProvider.php
    - lang/en/pios.php
    - lang/ms/pios.php

key-decisions:
  - "vendor_id uses nullOnDelete so deleting a vendor leaves products intact (cost/source becomes unknown, not cascade-deleted)"
  - "cost lives on products now but is NOT yet exposed in any form/table — column-level isolation in the SHARED ProductResource is deferred to plan 09-02"
  - "VendorResource left out of Business/Partner panel ->resources([]) lists; admin-only enforced at both panel-registration and policy (AllowsAdminSystemAccess) levels"

patterns-established:
  - "Vendor admin CRUD mirrors Territory exactly (Resource + Schema + Table + 3 Pages, navigationGroup System)"
  - "New entity labels added to both lang/en/pios.php and lang/ms/pios.php with identical key sets"

requirements-completed: [SRC-01, SRC-02]

duration: 3min
completed: 2026-06-20
---

# Phase 9 Plan 01: Vendor / Product Sourcing Foundation Summary

**Vendors table + admin-only Filament VendorResource (SRC-01), plus products.vendor_id FK and admin-only products.cost decimal(10,2) with Vendor model wiring (SRC-02 storage half).**

## Performance

- **Duration:** ~3 min
- **Started:** 2026-06-20T06:55:07Z
- **Completed:** 2026-06-20T06:58:00Z
- **Tasks:** 2
- **Files modified:** 14 (10 created, 4 modified)

## Accomplishments
- New `vendors` table and a new migration adding `vendor_id` (nullOnDelete FK) + `cost` (decimal 10,2 nullable) to `products` — foundation migration untouched.
- `Vendor` model (products HasMany, status cast); `Product` model gains `vendor()` BelongsTo, `vendor_id`/`cost` fillable, and `cost` decimal:2 cast.
- Admin-only `VendorResource` auto-discovered into `/admin` under the System group, with `VendorPolicy` (AllowsAdminSystemAccess) registered in `AppServiceProvider`.
- Vendor management absent from business and partner panels (their explicit `->resources([])` lists were not touched).
- Bilingual `pios.vendor` labels added to en + ms with matching keys.

## Task Commits

Each task was committed atomically:

1. **Task 1: vendors table + products vendor_id/cost migrations and Vendor model** - `4993ded` (feat)
2. **Task 2: admin-only VendorResource + VendorPolicy + bilingual labels** - `36a877c` (feat)

**Plan metadata:** see final docs commit.

## Files Created/Modified
- `database/migrations/2026_06_20_000002_create_vendors_table.php` - vendors table
- `database/migrations/2026_06_20_000003_add_vendor_and_cost_to_products.php` - products.vendor_id FK + cost column
- `app/Models/Vendor.php` - Vendor model (products HasMany, status cast)
- `app/Models/Product.php` - vendor() relation, vendor_id/cost fillable, cost decimal:2 cast
- `app/Policies/VendorPolicy.php` - admin-only policy via AllowsAdminSystemAccess
- `app/Providers/AppServiceProvider.php` - registers Gate::policy(Vendor::class, VendorPolicy::class)
- `app/Filament/Resources/Vendors/**` - Resource, Form, Table, 3 Pages (mirrors Territory)
- `lang/en/pios.php`, `lang/ms/pios.php` - pios.vendor labels (matching keys)

## Decisions Made
- `vendor_id` uses `nullOnDelete` — deleting a vendor nulls the product link rather than cascade-deleting products.
- `cost` stored now but deliberately NOT surfaced in any product form/table; column-level isolation in the shared ProductResource is plan 09-02's responsibility.
- Admin-only enforced at two layers: panel registration (absent from business/partner `->resources([])`) and authorization (AllowsAdminSystemAccess policy).

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None.

## Known Stubs
None. The `cost`/`vendor_id` columns are intentionally storage-only this plan; exposing/isolating them in the shared ProductResource is explicitly scoped to plan 09-02 (documented in the plan objective).

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Storage + admin CRUD ready. Plan 09-02 can now add cost/vendor columns to the shared ProductResource with column-level authorization and the partner/business-panel isolation test.
- Full suite green: 102 tests, 582 assertions passing.

## Verification
- `php artisan migrate:fresh --env=testing` — both new migrations apply cleanly.
- `php artisan test` (full suite) — **102 passed, 582 assertions**, no regressions.
- `FilamentAccessTest` — 14 passed (no panel-route regression).
- en/ms `vendor` label keys identical (LANG_KEYS_MATCH).
- VendorResource not referenced in BusinessPanelProvider or PartnerPanelProvider.

## Self-Check: PASSED

All 10 created files exist on disk; both task commits (4993ded, 36a877c) are present in git history.

---
*Phase: 09-vendor-product-sourcing*
*Completed: 2026-06-20*
