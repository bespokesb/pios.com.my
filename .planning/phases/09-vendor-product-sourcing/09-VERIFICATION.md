---
phase: 09-vendor-product-sourcing
verified: 2026-06-20T00:00:00Z
status: passed
score: 4/4 must-haves verified
---

# Phase 9: Vendor / Product Sourcing Verification Report

**Phase Goal:** Let HQ record vendors and an admin-only cost on each product, surface margin to HQ, and guarantee that cost, vendor, and margin never appear in the partner or business panels.
**Verified:** 2026-06-20
**Status:** passed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| #   | Truth | Status | Evidence |
| --- | ----- | ------ | -------- |
| 1 | HQ/admin can create + edit vendor records (name + contacts) in /admin only | ✓ VERIFIED | `Vendor` model + auto-discovered `VendorResource` (navigationGroup 'System'); `VendorPolicy` uses `AllowsAdminSystemAccess`; registered via `Gate::policy(Vendor::class, VendorPolicy::class)`; test `test_vendor_routes_are_admin_only` confirms `admin/vendors` exists, `business/vendors`+`partner/vendors` absent |
| 2 | products has nullable vendor_id (FK nullOnDelete) + admin-only cost decimal(10,2) | ✓ VERIFIED | Migration `2026_06_20_000003` adds `foreignId('vendor_id')->nullable()->constrained()->nullOnDelete()` + `decimal('cost', 10, 2)->nullable()`; Product fillable/casts/`vendor()` relation updated; foundation migration untouched (last touched commit 44700e0, pre-phase-9) |
| 3 | cost/vendor/margin appear ONLY in /admin; absent (not dehydrated) for BO + Partner in SHARED ProductResource | ✓ VERIFIED | `ProductForm` + `ProductsTable` gate cost/vendor/margin with `->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)`; `VendorSourcingTest` proves admin sees them, BO+Partner do not (schema `getFlatFields(withHidden:false)`), table hides 40.00/60.00 from BO, shows 60.00 to admin |
| 4 | margin = selling_price − cost, null-safe, admin-only | ✓ VERIFIED | `ProductsTable` margin column: `$record->cost === null ? null : 'RM '.number_format((float)$record->selling_price - (float)$record->cost, 2)`, gated `->visible(isAdmin())`; test asserts admin sees 60.00 (100−40) |

**Score:** 4/4 truths verified

### Required Artifacts

| Artifact | Expected | Status | Details |
| -------- | -------- | ------ | ------- |
| `database/migrations/2026_06_20_000002_create_vendors_table.php` | vendors table | ✓ VERIFIED | name, contact_name, phone(idx), email(idx), address, status(idx) |
| `database/migrations/2026_06_20_000003_add_vendor_and_cost_to_products.php` | vendor_id FK + cost | ✓ VERIFIED | `nullOnDelete` FK after territory_id; `decimal('cost',10,2)` nullable after selling_price |
| `app/Models/Vendor.php` | model + products HasMany + status cast | ✓ VERIFIED | Fillable, `products()` HasMany, RecordStatus cast |
| `app/Models/Product.php` | vendor() + vendor_id/cost fillable + cost cast | ✓ VERIFIED | `vendor()` BelongsTo, vendor_id+cost in Fillable, `'cost' => 'decimal:2'` |
| `app/Policies/VendorPolicy.php` | admin-only policy | ✓ VERIFIED | `use AllowsAdminSystemAccess` |
| `app/Filament/Resources/Vendors/VendorResource.php` | auto-discovered admin resource | ✓ VERIFIED | model = Vendor, navigationGroup 'System'; not in business/partner panel resource lists |
| `app/Filament/Resources/Products/Schemas/ProductForm.php` | admin-only cost+vendor fields | ✓ VERIFIED | vendor_id Select + cost TextInput gated `->visible(isAdmin())` |
| `app/Filament/Resources/Products/Tables/ProductsTable.php` | admin-only cost+vendor+margin columns | ✓ VERIFIED | all three gated `->visible(isAdmin())`; margin null-safe computed |
| `tests/Feature/VendorSourcingTest.php` | column-level isolation proof | ✓ VERIFIED | 7 tests / 22 assertions, all passing |

### Key Link Verification

| From | To | Via | Status | Details |
| ---- | -- | --- | ------ | ------- |
| Product.php | vendors table | belongsTo + vendor_id fillable | ✓ WIRED | `function vendor(): BelongsTo` present; vendor_id fillable |
| AppServiceProvider | VendorPolicy | Gate::policy | ✓ WIRED | `Gate::policy(Vendor::class, VendorPolicy::class)` at boot |
| ProductForm | auth isAdmin() | ->visible() gate | ✓ WIRED | both cost + vendor_id gated; matches required pattern |
| VendorSourcingTest | shared ProductResource | schema/table assertions for BO absence | ✓ WIRED | `getFlatFields(withHidden:false)` absence + `assertDontSee` table isolation |

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
| -------- | ------- | ------ | ------ |
| Column-level isolation + policy + routes | `php artisan test --filter=VendorSourcingTest` | 7 passed, 22 assertions | ✓ PASS |
| No regressions | `php artisan test` (full) | 109 passed, 604 assertions | ✓ PASS |
| en/ms vendor keys identical | key compare | singular,plural,name,contact_name,phone,email,address,status (both) | ✓ PASS |
| en/ms product keys identical | key compare | cost,vendor,margin,sourcing (both) | ✓ PASS |
| Foundation migration untouched | git log | last touched pre-phase-9 (44700e0) | ✓ PASS |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
| ----------- | ----------- | ----------- | ------ | -------- |
| SRC-01 | 09-01 | HQ/admin manages vendor records | ✓ SATISFIED | Vendor model + admin-only VendorResource + VendorPolicy; route test |
| SRC-02 | 09-01 | Product linked to vendor + admin-only cost | ✓ SATISFIED | vendor_id FK nullOnDelete + cost decimal(10,2); Product wiring |
| SRC-03 | 09-02 | cost/vendor/margin admin-only, never in partner/business | ✓ SATISFIED | ->visible(isAdmin()) gating + VendorSourcingTest isolation proof |
| SRC-04 | 09-02 | HQ views margin (price − cost) | ✓ SATISFIED | null-safe admin-only computed margin column; test asserts 60.00 |

No orphaned requirements: REQUIREMENTS.md maps SRC-01..04 to Phase 9, all claimed across plans 09-01/09-02.

### Anti-Patterns Found

None blocking. The intentional `return null` in the margin column closure is the required null-safe behavior (cost === null guard), not a stub. `->visible(false)` for non-admins is the required SRC-03 column-level guarantee, not a hidden-field anti-pattern.

### Human Verification Required

None required for automated sign-off. Optional manual sanity: log into /business as a BO and confirm the product create form has no cost/vendor inputs and the list shows no cost/margin/vendor columns; in /admin they are present. This is already covered programmatically by VendorSourcingTest.

### Gaps Summary

No gaps. All four observable truths verified, all artifacts pass existence/substantive/wiring/data-flow checks, all key links wired, full suite green with zero regressions. The CRITICAL SRC-03 column-level isolation is gated inside the SHARED ProductForm/ProductsTable (not panel config) and proven by a dedicated authorization test covering admin (sees), Business Owner (absent), and Partner (absent), plus vendor policy denial and route isolation.

---

_Verified: 2026-06-20_
_Verifier: Claude (gsd-verifier)_
