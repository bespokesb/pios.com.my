---
phase: 11-customer-detail-view
plan: 01
subsystem: filament-ui
tags: [crm, filament, view-record, infolist, relation-manager, ownership-scope, audit, bilingual]

# Dependency graph
requires:
  - phase: 10-crm-data-layer
    provides: CustomerNote/CustomerFollowUp models, scopeVisibleTo, child policies, Customer notes()/followUps() relations
provides:
  - ViewCustomer detail page (Filament ViewRecord) routed through CustomerResource scoped getEloquentQuery
  - CustomerInfolist (core fields visible to BO; LOS/PII/admin gated by isBusinessOwner closure)
  - NotesRelationManager + FollowUpsRelationManager (owner-stamped business_owner_id + created_by, scoped query, audited)
  - ViewAction on the customers table; bilingual 'customer' lang section (en/ms)
affects: [12-segments-stage-change, 13-whatsapp-call]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Filament ViewRecord inherits resource scoped getEloquentQuery()->visibleTo for free — cross-owner id 404s server-side, no hand-rolled findOrFail"
    - "Relation Manager create stamps owner FK + created_by via CreateAction->mutateFormDataUsing (server-side), never a form field; ->after writes AuditLog"
    - "Override isReadOnly()=>false to re-enable create/edit/delete on RMs that sit on a ViewRecord page (Filament v5 read-only default)"

key-files:
  created:
    - app/Filament/Resources/Customers/Pages/ViewCustomer.php
    - app/Filament/Resources/Customers/Schemas/CustomerInfolist.php
    - app/Filament/Resources/Customers/RelationManagers/NotesRelationManager.php
    - app/Filament/Resources/Customers/RelationManagers/FollowUpsRelationManager.php
    - tests/Feature/CustomerDetailViewTest.php
  modified:
    - app/Filament/Resources/Customers/CustomerResource.php
    - app/Filament/Resources/Customers/Tables/CustomersTable.php
    - lang/en/pios.php
    - lang/ms/pios.php

key-decisions:
  - "ViewCustomer is a Filament ViewRecord (not a hand-rolled page) so it inherits the resource's visibleTo scope; opening another BO's id throws ModelNotFoundException"
  - "Relation managers stamp business_owner_id from getOwnerRecord() (the parent customer) + created_by from auth — never a form field"
  - "Override isReadOnly()=>false on both RMs because Filament v5 makes relation managers read-only on ViewRecord pages by default, which would have hidden create/edit/delete"

patterns-established:
  - "Pattern: infolist defense-in-depth — sensitive Section is gated AND each sensitive entry carries the same isBusinessOwner guard"

requirements-completed: [CRM-03]

# Metrics
duration: 12min
completed: 2026-06-30
---

# Phase 11 Plan 01: Customer Detail View Summary

**A Filament `ViewCustomer` ViewRecord in the customers resource rendering a BO-gated `CustomerInfolist` plus owner-stamped, audited, scope-isolated Notes + Follow-ups Relation Managers — all routed through the resource's `visibleTo` query so no Business Owner can open or mutate another BO's customer or its child records.**

## Performance

- **Duration:** ~12 min
- **Tasks:** 3
- **Files modified:** 9 (5 created, 4 modified)

## Accomplishments
- `ViewCustomer` (Filament `ViewRecord`) wired into `CustomerResource` as the `'view'` page; it resolves the record through `getEloquentQuery()->visibleTo($user)` — no hand-rolled `findOrFail`, so a cross-BO id hits `ModelNotFoundException`.
- `CustomerInfolist`: core contact fields (name, hp_no, email, category, customer_no, status) visible to BOs; LOS/PII/admin entries (vip_customer_no, kp_no, abo_no, dd_name, dd_hp_no, nick_name, address, businessOwner, partner, timestamps) gated at BOTH section and entry level by the existing `isBusinessOwner` closure.
- `NotesRelationManager` + `FollowUpsRelationManager`: no owner fields on the form; `business_owner_id` stamped from `getOwnerRecord()` + `created_by` from auth via `mutateFormDataUsing`; table query scoped via `visibleTo`; `AuditLog` written on create; edit/delete available.
- `ViewAction` added to the customers table so a BO lands on the detail page.
- Bilingual `customer` lang section added to `lang/en/pios.php` + `lang/ms/pios.php` with identical keys; classes use `__('pios.customer.*')`.
- Feature test (8 cases) proves view-own, cross-BO `ModelNotFoundException` + policy deny, PII-hiding via `assertDontSee`, owner-stamp + audit on create, scoped child listing, and foreign-BO edit/delete denial. Full suite green (130 passed, 683 assertions).

## Task Commits

1. **Task 1: ViewCustomer page + CustomerInfolist, wired into CustomerResource + ViewAction** - `8b34a45` (feat)
2. **Task 2: NotesRelationManager + FollowUpsRelationManager (owner-stamped, scoped, audited)** - `5d03d84` (feat)
3. **Task 3: Feature test — ownership isolation + PII-hiding + child auth** - `705ae92` (test)

## Decisions Made
- ViewCustomer is a Filament ViewRecord so it inherits the resource's `visibleTo` scope rather than re-implementing access control.
- Relation managers stamp ownership server-side from the owner record, never from form input.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Relation managers were read-only on the ViewRecord page**
- **Found during:** Task 3 (the `create` table action asserted invisible)
- **Issue:** Filament v5 makes relation managers read-only on `ViewRecord` pages by default (`isReadOnly()` returns true when the page class is a `ViewRecord`), which hid the create/edit/delete actions the plan requires.
- **Fix:** Overrode `public function isReadOnly(): bool { return false; }` in both `NotesRelationManager` and `FollowUpsRelationManager`. Ownership is still enforced server-side via the stamped `business_owner_id`, the `visibleTo` query scope, and the child policies.
- **Files modified:** NotesRelationManager.php, FollowUpsRelationManager.php
- **Commit:** `705ae92`

### Red-team / plan-checker additions applied
- Added `assertDontSee` assertions (kp_no, abo_no, address, vip_customer_no, dd_name set non-empty on the fixture) proving the infolist PII-hiding holds for a BO viewing their OWN customer.
- Added a test that a DIFFERENT BO is denied view/update/delete on another BO's note + follow-up via the child policy, and that the foreign customer's relation table never lists the owner's note.
- The relation-manager create action is asserted via `callTableAction('create', ...)->assertHasNoTableActionErrors()` (the installed Filament 5.6 table-action testing API) so the test is green.

## Known Stubs
None — all infolist entries and relation-manager columns bind to real model attributes/relations; no placeholder data sources.

## Issues Encountered
- The Livewire action-invocation API: relation-manager header `CreateAction` is a TABLE action, so `callTableAction(...)` / `assertHasNoTableActionErrors()` is required (not the page-level `callAction`). Confirmed against `vendor/filament/tables/src/Testing/TestsActions.php`.

## Next Phase Readiness
- Phase 12 (segments & gated stage-change) builds on this detail view; the scoped ViewRecord + RM pattern is now established.
- Phase 13 (one-tap WhatsApp/call) can hang actions off this detail page using the Phase 10 Phone helper.
- No blockers. Full suite green (130 tests, 683 assertions).

## Self-Check: PASSED

All 5 created files verified present on disk; all 4 task/fix commits (8b34a45, 5d03d84, 705ae92, 09cfd18) verified in git history. Full suite: 130 passed, 683 assertions.

---
*Phase: 11-customer-detail-view*
*Completed: 2026-06-30*
