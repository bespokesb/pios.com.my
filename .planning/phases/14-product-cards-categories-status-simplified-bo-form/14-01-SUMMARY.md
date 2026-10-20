---
phase: 14-product-cards-categories-status-simplified-bo-form
plan: 01
subsystem: ui
tags: [filament, products, contentGrid, fileupload, authorization, eloquent-scope]

# Dependency graph
requires:
  - phase: 09-vendor-sourcing-product-cost
    provides: "Shared ProductResource/ProductForm/ProductsTable with field-level ->visible(isAdmin()) cost/vendor gating + VendorSourcingTest schema-level pattern"
  - phase: 10-crm-data-layer
    provides: "BO-ownership invariants + scopeVisibleTo discipline reused for product ownership"
provides:
  - "Business-panel product card grid (contentGrid) showing image/name/price/category/status badge, eager-loading productType"
  - "Category (product_type) + status SelectFilters on all panels"
  - "Simplified BO product form (name, selling_price, image, description, product_type, status) with admin-only fields field-gated"
  - "Server-side admin-key stripping in Create/Edit; BO products default classification = Non-Amway"
  - "Immutable owner FK: EditProduct strips business_owner_id + created_by for EVERY role (blocks cross-BO reassignment)"
  - "Hardened image FileUpload (->image(), maxSize 4096, png/jpeg/webp only — SVG blocked)"
affects: [15-share-promote, product-flows, pios-mart]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Panel-aware ProductsTable branch via Filament::getCurrentPanel()?->getId() === 'business' (cards vs tabular)"
    - "Private visibleToAdmin(): Closure applied per-field (never whole Section) mirroring CustomerForm::visibleToNonBusinessOwner"
    - "Server-side write-guard: unset admin-only keys + stamp eligibility default in mutateFormDataBeforeCreate/Save"
    - "Immutable FK defense: always unset owner/creator keys on edit for all roles"

key-files:
  created:
    - tests/Feature/ProductListingTest.php
  modified:
    - app/Filament/Resources/Products/Tables/ProductsTable.php
    - app/Filament/Resources/Products/Schemas/ProductForm.php
    - app/Filament/Resources/Products/Pages/CreateProduct.php
    - app/Filament/Resources/Products/Pages/EditProduct.php
    - tests/Feature/ProductCatalogTest.php
    - lang/en/pios.php
    - lang/ms/pios.php

key-decisions:
  - "EditProduct strips business_owner_id + created_by for EVERY role (not just BO) — the owner FK is immutable post-create, closing a cross-BO reassignment the read-scope cannot catch"
  - "BO products are always Non-Amway: classification stamped server-side in CreateProduct, never BO-writable; preserved (unset) on edit"
  - "BO status options surface only Draft/Active/Archived (Inactive treated as a hidden admin synonym)"
  - "Generated IDs section guarded to admins so a BO sees exactly the six essentials (section holds no BO-essential field, so Pitfall 3 does not apply)"
  - "Image upload restricted to png/jpeg/webp (SVG/video removed) + maxSize 4096 KB for all roles"

patterns-established:
  - "Panel-aware table layout: business -> contentGrid cards; admin/partner -> existing tabular columns (shared filters/actions extracted to local vars)"
  - "Defense-in-depth field gating: schema-level ->visible() + server-side unset() so a crafted Livewire payload cannot persist gated fields"

requirements-completed: [PLST-01, PLST-02, PLST-03]

# Metrics
duration: 3min
completed: 2026-06-30
---

# Phase 14 Plan 01: Product Cards, Categories, Status & Simplified BO Form Summary

**Business-panel product card grid with category/status filters, a six-field BO product form that field-gates and server-strips cost/vendor/commission/classification, and an immutable owner FK that blocks cross-BO product reassignment.**

## Performance

- **Duration:** 3 min
- **Started:** 2026-06-30T19:52:32Z
- **Completed:** 2026-06-30T19:55:47Z
- **Tasks:** 3
- **Files modified:** 7 (1 created, 6 modified)

## Accomplishments
- `/business` products now render a `contentGrid` card layout (image, name, selling_price, category badge, status badge) eager-loading `productType` (no N+1); admin/partner panels keep their full tabular cost/vendor/margin columns.
- Category (`product_type_id`) + status `SelectFilter`s added on every panel.
- BO product create/edit form simplified to the six essentials; `classification`, `territory_id`, `coverage_area`, `certification`, `sales_commission`, `pricing_structure`, `promotional_content` (plus existing `cost`/`vendor_id`) are field-gated to admins and stripped server-side for BOs, with `classification` defaulting to Non-Amway.
- RED-TEAM FIX: `EditProduct::mutateFormDataBeforeSave` now unconditionally unsets `business_owner_id` and `created_by` for every role, closing a cross-BO product-reassignment write — proven by a crafted-payload test.
- Image FileUpload hardened: `->image()`, `->maxSize(4096)`, accepts only png/jpeg/webp (SVG/video blocked).
- Bilingual (en/ms) product listing/form labels added.

## Task Commits

1. **Task 1: ProductsTable card grid + category/status filters + eager-load** - `69ca4fd` (feat)
2. **Task 2: Simplify BO ProductForm + server-side admin-key strip & owner-FK immutability** - `ab3ad5d` (feat)
3. **Task 3: ProductListingTest (TDD)** - `c5cb571` (test)

_Implementation contracts existed from Tasks 1–2, so Task 3's tests landed green (single test commit)._

## Files Created/Modified
- `app/Filament/Resources/Products/Tables/ProductsTable.php` - Panel-aware: business contentGrid cards (Stack layout) vs tabular; shared product_type/status filters; eager-load productType.
- `app/Filament/Resources/Products/Schemas/ProductForm.php` - `visibleToAdmin(): Closure` per-field gating; role-aware status options; hardened image upload.
- `app/Filament/Resources/Products/Pages/CreateProduct.php` - BO: strip admin-only keys + stamp `classification = Non-Amway`.
- `app/Filament/Resources/Products/Pages/EditProduct.php` - Always strip `business_owner_id` + `created_by`; BO strips admin-only keys.
- `tests/Feature/ProductListingTest.php` - 6 tests: schema field exclusion, crafted-save block, owner-FK reassignment block, own-only grid, filter existence, image SVG/oversize rejection.
- `tests/Feature/ProductCatalogTest.php` - Updated BO create test for simplified form + Non-Amway/0.00 default assertions.
- `lang/en/pios.php`, `lang/ms/pios.php` - Added product name/price/image/description/category/status labels.

## Decisions Made
- Owner FK + creator are immutable post-create and stripped for ALL roles on edit (not just BO) — the strongest defense against cross-BO reassignment.
- BO classification is never BO-writable; defaulted to Non-Amway server-side (drives Rule 2 eligibility).
- BO status limited to Draft/Active/Archived; Inactive remains an admin-only synonym (resolves the STATE.md open question conservatively, allowing self-publish to Active).
- Image upload accepts png/jpeg/webp only; SVG and video removed.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing Critical Security] Owner FK immutability for every role on edit**
- **Found during:** Task 2 (EditProduct hardening)
- **Issue:** The plan's Task 2C only stripped admin-only keys for BOs, leaving `business_owner_id` and `created_by` mutable via a crafted Livewire payload — a BO could reassign their own product to another BO (a cross-BO write the read-scope cannot stop).
- **Fix:** `EditProduct::mutateFormDataBeforeSave` now unconditionally `unset($data['business_owner_id'], $data['created_by'])` for every role, plus a dedicated reassignment-attempt test.
- **Files modified:** app/Filament/Resources/Products/Pages/EditProduct.php, tests/Feature/ProductListingTest.php
- **Verification:** `test_business_owner_cannot_reassign_product_to_another_owner_on_edit` passes (owner FK + creator unchanged after a crafted save).
- **Committed in:** `ab3ad5d` (Task 2) / `c5cb571` (test)

---

**Total deviations:** 1 auto-fixed (1 missing-critical security). This was the mandated red-team fix and is the core security outcome of the phase.
**Impact on plan:** Strengthens the plan's stated invariant; no scope creep.

## Issues Encountered
None — all three task verifications and the full suite passed on first run.

## User Setup Required
None - no external service configuration required. (Note: production deploys must run `php artisan storage:link` so uploaded product images resolve on the `public` disk — existing deploy concern, unchanged.)

## Next Phase Readiness
- Phase 15 (Share / Promote, PLST-04) can build on the BO product card grid; product cards expose name/price/image without any cost/margin, so a `wa.me` share message can be composed safely.
- Full suite green at 148 tests (142 baseline + 6 new), 756 assertions.

---
*Phase: 14-product-cards-categories-status-simplified-bo-form*
*Completed: 2026-06-30*

## Self-Check: PASSED

All created files and task commits verified present.
