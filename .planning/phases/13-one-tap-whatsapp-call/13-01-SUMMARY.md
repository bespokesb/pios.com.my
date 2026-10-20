---
phase: 13-one-tap-whatsapp-call
plan: 01
subsystem: ui
tags: [filament, whatsapp, tel, customer, crm, phone, bilingual]

# Dependency graph
requires:
  - phase: 10-crm-data-layer
    provides: App\Support\Phone (toWhatsApp/toTel normalizers) + Customer::scopeVisibleTo ownership gate
  - phase: 11-customer-detail-view
    provides: ViewCustomer ViewRecord page (header actions surface)
provides:
  - CustomerContactActions factory (single-source WhatsApp + Call Filament actions)
  - One-tap wa.me/60... (new tab) + tel:+60... contact on customer rows and detail page
  - Hidden-when-blank guard driven by the Phone normalizer (server-side, not UI-only)
  - Bilingual contact labels (pios.customer.contact.whatsapp / .call) in en + ms
affects: [product-share, promotion, customer-detail]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Stateless action factory class feeding both table recordActions and page header actions (one source of truth, tested once)"

key-files:
  created:
    - app/Filament/Resources/Customers/Actions/CustomerContactActions.php
    - tests/Feature/CustomerContactActionsTest.php
  modified:
    - app/Filament/Resources/Customers/Tables/CustomersTable.php
    - app/Filament/Resources/Customers/Pages/ViewCustomer.php
    - lang/en/pios.php
    - lang/ms/pios.php

key-decisions:
  - "Visibility guard uses Phone::toWhatsApp/toTel(...) !== null (not filled(hp_no)) so non-blank-but-garbage numbers that normalize to null are also hidden"
  - "Call action intentionally omits openUrlInNewTab() so a tel: link does not spawn a blank tab"
  - "Single CustomerContactActions factory feeds both the table rows and ViewCustomer header so behavior is defined and tested once"

patterns-established:
  - "Action factory: static methods returning Filament\\Actions\\Action reused across table + page surfaces"

requirements-completed: [CRM-06]

# Metrics
duration: 2min
completed: 2026-06-30
---

# Phase 13 Plan 01: One-Tap WhatsApp & Call Summary

**One-tap WhatsApp (`wa.me/60…`, new tab) and Call (`tel:+60…`) Filament actions on customer rows and the detail page, built from the Phase-10 Phone normalizer, hidden when the number is unusable, and proven by an ownership-isolation test.**

## Performance

- **Duration:** 2 min
- **Started:** 2026-06-30T19:48:32Z
- **Completed:** 2026-06-30T19:50:17Z
- **Tasks:** 2
- **Files modified:** 6 (2 created, 4 modified)

## Accomplishments
- `CustomerContactActions` factory: a single source of truth that builds the WhatsApp and Call actions for both the customers table rows and the ViewCustomer header.
- Links built through the already-unit-tested `App\Support\Phone` (`'https://wa.me/'.toWhatsApp(hp)`, `'tel:'.toTel(hp)`); WhatsApp opens in a new tab, Call does not.
- Both actions hidden server-side when the phone normalizes to null (blank or garbage), via the `->visible()` guard.
- Bilingual labels added (`pios.customer.contact.whatsapp` / `.call`) in `lang/en` and `lang/ms` (Call → "Panggil").
- Feature test covering url-building, messy-number normalization, hidden-when-blank, new-tab, and BO ownership isolation (a BO never sees another BO's customer).

## Task Commits

Each task was committed atomically:

1. **Task 1: CustomerContactActions factory + wiring + bilingual labels** - `8d5374c` (feat)
2. **Task 2: Feature test (url building, hidden-when-blank, new-tab, ownership isolation)** - `73e0500` (test)

_Note: Task 2 is marked tdd, but the behavior under test was the factory built in Task 1; the test was authored and verified green in a single commit (no separate RED, since the implementation already existed by design of the plan ordering)._

## Files Created/Modified
- `app/Filament/Resources/Customers/Actions/CustomerContactActions.php` - Stateless factory with `whatsApp()` and `call()` returning `Filament\Actions\Action`, each with url/visible closures keyed on the injected `Customer $record`.
- `app/Filament/Resources/Customers/Tables/CustomersTable.php` - Added the two contact actions to `recordActions` (before View/Edit) + import.
- `app/Filament/Resources/Customers/Pages/ViewCustomer.php` - Prepended the two contact actions in `getHeaderActions()` + import.
- `lang/en/pios.php` / `lang/ms/pios.php` - Merged a `customer.contact` block (whatsapp/call) into the existing `customer` key.
- `tests/Feature/CustomerContactActionsTest.php` - 5 tests / 10 assertions.

## Decisions Made
- Visibility guard uses the normalized result (`Phone::toWhatsApp/toTel(...) !== null`) rather than `filled($record->hp_no)`, so a non-blank-but-unparseable value that normalizes to null is also hidden.
- Call action deliberately does not call `openUrlInNewTab()` (a `tel:` link must not open a blank browser tab).
- One factory feeds both surfaces — behavior is defined and tested in one place; no per-action ownership branch was added since the table/ViewRecord already ride on `CustomerResource::getEloquentQuery()->visibleTo()`.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- CRM-06 complete; full suite green at 142 tests / 718 assertions (137 baseline + 5 new).
- The `CustomerContactActions` factory pattern is reusable for any future "contact from a record" surface; no blockers.

## Self-Check: PASSED

- FOUND: app/Filament/Resources/Customers/Actions/CustomerContactActions.php
- FOUND: tests/Feature/CustomerContactActionsTest.php
- FOUND commit: 8d5374c (Task 1)
- FOUND commit: 73e0500 (Task 2)

---
*Phase: 13-one-tap-whatsapp-call*
*Completed: 2026-06-30*
