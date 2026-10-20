---
phase: 15-share-promote
plan: 01
subsystem: ui
tags: [filament, whatsapp, share, policy, eloquent, i18n]

# Dependency graph
requires:
  - phase: 14-product-cards-categories-status
    provides: BO product card grid + filters on ProductsTable (business panel)
  - phase: 10-crm-data-layer
    provides: Product model, ProductPolicy::ownsProduct, visibleTo scope
provides:
  - "Product::shareMessage/shareWhatsAppUrl/firstShareableImageUrl — cost-safe wa.me text builders"
  - "ProductPolicy::share — owning-BO-only gate"
  - "Share record Action on BO product listing (opens wa.me/?text= in new tab)"
  - "bilingual product.share.* lang keys (en/ms)"
affects: [whatsapp-promotion, share-pub-public-page-v2]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Pure model message-builder structurally incapable of leaking cost/vendor/margin"
    - "URL record Action gated server-side via Policy ability (can('share'))"

key-files:
  created:
    - tests/Feature/ProductShareTest.php
  modified:
    - app/Models/Product.php
    - app/Policies/ProductPolicy.php
    - app/Filament/Resources/Products/Tables/ProductsTable.php
    - lang/en/pios.php
    - lang/ms/pios.php

key-decisions:
  - "Generic wa.me/?text= picker (no recipient) — no Phone helper dependency in v1.2"
  - "No public/unauthenticated product page built; SHARE-PUB-01 deferred to v2 (guest route would bypass visibleTo and leak cost/margin)"
  - "Cost-omission enforced by construction (builder only references name/price/description/media_assets) + proven by negative-assertion tests"

patterns-established:
  - "Customer-facing share text built from whitelisted fields only — never references cost/vendor/margin/sales_commission/pricing_structure"
  - "Share/promote actions gated by a dedicated Policy ability, not UI hiding alone"

requirements-completed: [PLST-04]

# Metrics
duration: 2min
completed: 2026-06-30
---

# Phase 15 Plan 01: Share / Promote Summary

**One-tap WhatsApp "Share" record Action on the BO product listing that opens a rawurlencoded `wa.me/?text=` message (name + RM price + description + optional public image) — gated to the owning BO and structurally incapable of leaking cost/vendor/margin.**

## Performance

- **Duration:** ~2 min
- **Started:** 2026-06-30T19:58:21Z
- **Completed:** 2026-06-30T20:00:19Z
- **Tasks:** 3
- **Files modified:** 6 (5 modified, 1 created)

## Accomplishments
- `Product::shareMessage()` / `shareWhatsAppUrl()` / `firstShareableImageUrl()` build a customer-facing WhatsApp text from name, `RM <selling_price>`, description and an optional public-disk image URL — referencing no cost/vendor/margin fields.
- `ProductPolicy::share` reuses `ownsProduct` so the action is server-side-gated to the owning Business Owner (other BOs and admins denied).
- Share record Action prepended to `ProductsTable` recordActions, opening the wa.me URL in a new tab, visible only when `can('share', $record)`.
- Bilingual `product.share.{label,intro,price}` keys added to `lang/en` (Share) and `lang/ms` (Kongsi).
- Feature test proves URL contents (rawurlencoded name+price+description), cost/commission omission, image resolution, policy gating, and listing-action visibility.

## Task Commits

1. **Task 1: Product share methods + ProductPolicy::share + bilingual lang keys** - `4c96206` (feat)
2. **Task 2: Wire Share record Action into ProductsTable** - `865ce97` (feat)
3. **Task 3: Feature test — URL contents, no-cost guarantee, own-products-only gating** - `12fd75c` (test)

_Production code (Tasks 1-2) preceded the verification test; the implementation was already green when Task 3's test ran (verification-style TDD on an already-built pure method)._

## Files Created/Modified
- `app/Models/Product.php` - Added `shareMessage`, `shareWhatsAppUrl`, `firstShareableImageUrl` + `Storage` import
- `app/Policies/ProductPolicy.php` - Added `share(User, Product)` ability (owning BO only)
- `app/Filament/Resources/Products/Tables/ProductsTable.php` - Prepended Share URL Action gated via `can('share')`
- `lang/en/pios.php` / `lang/ms/pios.php` - Added nested `product.share.*` keys
- `tests/Feature/ProductShareTest.php` - 5 tests / 17 assertions proving share behavior

## Decisions Made
- Used the recipient-less `wa.me/?text=` picker (v1.2 shape) — no `App\Support\Phone` dependency.
- Did NOT build any `routes/web.php` / controller / Blade / signed URL / public product page; SHARE-PUB-01 stays deferred to v2 (a guest route would bypass `visibleTo` and expose cost/margin/cross-BO catalog).

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None.

## Known Stubs
None - the Share action is fully wired to live product data through `Product::shareWhatsAppUrl()`.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- PLST-04 complete; this is the final plan of milestone v1.2's product track.
- Full suite green at 153 tests / 773 assertions (was 148 at plan start; +5 new).
- Public share page (SHARE-PUB-01) remains an explicit v2 deferral with a documented security rationale.

---
*Phase: 15-share-promote*
*Completed: 2026-06-30*

## Self-Check: PASSED

All created/modified files present; all task commits (4c96206, 865ce97, 12fd75c) exist in history.
