---
phase: 12-segments-gated-stage-change
plan: 01
subsystem: filament-ui
tags: [crm, filament, list-tabs, scoped-badge, inline-action, ownership-policy, audit, bilingual]

# Dependency graph
requires:
  - phase: 11-customer-detail-view
    provides: CustomerResource scoped getEloquentQuery, CustomersTable, ViewAction, bilingual 'customer' lang section
provides:
  - ListCustomers::getTabs() with All/Lead/Prospect/Customer/VIP segments and BO-scoped badge counts (CRM-04)
  - Name + hp_no searchable customer table (CRM-04)
  - Inline ownership-gated setStage Action (Lead/Prospect/Customer only) with server-side re-check + audit (CRM-05)
  - bilingual customer.segments.* + customer.stage.* keys (en/ms)
affects: [13-whatsapp-call]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "List tab badge counts derive from CustomerResource::getEloquentQuery() so they inherit visibleTo (BO sees only own customers) — never a bare Customer::query()"
    - "Tab ->modifyQueryUsing only needs ->where('category', ...) because the active tab modifier is applied on top of the already-scoped resource query"
    - "Inline record Action re-exposes a hidden field (category) narrowly: ->visible(can('update')) for UI + abort_unless(can('update'),403) inside ->action() for defense-in-depth, plus an enum-allowlist reject"

key-files:
  created:
    - tests/Feature/CustomerSegmentsAndStageTest.php
  modified:
    - app/Filament/Resources/Customers/Pages/ListCustomers.php
    - app/Filament/Resources/Customers/Tables/CustomersTable.php
    - lang/en/pios.php
    - lang/ms/pios.php

key-decisions:
  - "category is re-exposed to the BO ONLY through the narrow setStage action — the form Section hiding IC/cost/PII stays guarded (Pitfall 3); no CustomerForm change"
  - "settableStages() is the single allowlist [Lead, Prospect, Customer]; VIP/Non-LOS are neither offered in the Select nor accepted server-side because they require LOS/ABO verification out of this phase's scope"
  - "setStage is hidden for customers already VIP/Non-LOS (in_array($record->category, settableStages())) so the action never appears where it cannot legally act"

patterns-established:
  - "Pattern: gated inline stage-change Action — visible-gate + server-side abort_unless + enum allowlist + AuditLogger, reusable for any narrow field re-exposure"

requirements-completed: [CRM-04, CRM-05]

# Metrics
duration: 18min
completed: 2026-07-01
---

# Phase 12 Plan 01: Segments & Gated Stage-Change Summary

**The `/business` customer list became a segmented CRM surface: `ListCustomers::getTabs()` shows All/Lead/Prospect/Customer/VIP tabs whose badge counts route through `CustomerResource::getEloquentQuery()` (so each BO counts only their own customers), the table searches by name and phone, and a narrow ownership-gated inline `setStage` action lets a BO move their OWN customer between Lead/Prospect/Customer only — VIP/Non-LOS are excluded both in the Select and server-side, the change is audited, and another BO's customer is neither visible nor updatable.**

## Performance

- **Duration:** ~18 min (continued from a session-interrupted prior executor)

## What Was Built

This plan was finished as a continuation. The prior session committed Task 1 (`5c80db9`): `getTabs()` stage segments with BO-scoped badges and the bilingual `customer.segments.*` / `customer.stage.*` lang keys. This session completed the remaining work:

- **Task 2 (CRM-05) — inline setStage action** (`775f56e`): added an `Action::make('setStage')` to the existing `recordActions` on `CustomersTable` (prepended before the existing View/Edit actions, which remain intact). It is `->visible()` only when `auth()->user()?->can('update', $record)` AND the record is currently Lead/Prospect/Customer; its `->action()` re-checks with `abort_unless(... can('update'), 403)`, validates the target against the `settableStages()` allowlist (rejecting VIP/Non-LOS with a `ValidationException`), updates `category` server-side, and logs the change via `AuditLogger`. `name`/`hp_no` `->searchable()` (CRM-04 search) were left untouched, and no `visibleToNonBusinessOwner()` PII guard was relaxed.
- **Task 3 — Feature test** (`ce2095f`): `tests/Feature/CustomerSegmentsAndStageTest.php` with 7 tests proving BO-scoped tab badges (string assertions for Filament v5 `getBadge()`), active-tab filtering, name + phone search, own-customer stage change with audit row, VIP rejection (category unchanged), action hidden for VIP customers, and cross-BO refusal (not visible + policy `denies('update')`).

## Deviations from Plan

None - the remaining tasks were executed as written in the plan. Filament v5 `getBadge()` returns a string, so badge assertions use `assertSame('4', ...)` per the plan's interface notes.

## Verification

- `php artisan test --filter=CustomerSegmentsAndStageTest` → 7 passed.
- `php artisan test` (full suite) → **137 passed, 708 assertions** (was 130; +7 new, zero regressions).

## Known Stubs

None.

## Self-Check: PASSED
