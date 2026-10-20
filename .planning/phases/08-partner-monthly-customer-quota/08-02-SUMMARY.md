---
phase: 08-partner-monthly-customer-quota
plan: 02
subsystem: quota
tags: [laravel, filament, livewire, widget, report, tdd, bilingual]

# Dependency graph
requires:
  - phase: 08-partner-monthly-customer-quota
    provides: "PartnerQuotaService::attainment/monthToDateCount/target (half-open MYT month read model)"
provides:
  - "App\\Filament\\Widgets\\PartnerQuotaWidget: partner-panel stats widget (own count/target/percent), admin-safe via canView()"
  - "OperationalReports::getPartnerQuotaAttainmentProperty(): per-partner current-month attainment via ONE grouped query, scoped by visibleTo"
  - "Partner Monthly Quota section in the operational-reports view"
  - "Bilingual quota labels (en/ms) under pios.widgets"
affects: [QUOTA-05 (future v2 enforcement seam — both surfaces already read the advisory service)]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Discover-safe Filament widget: canView() returns false unless actual partner, so AdminPanelProvider discoverWidgets() does not mount it on /admin"
    - "Single grouped COUNT(*) ... groupBy('partner_id') over the half-open [start,+1mo) Asia/Kuala_Lumpur range (no N+1, no whereMonth/endOfMonth) — mirrors PartnerQuotaService boundary"
    - "Target sourced from PartnerQuotaService::target() (config-driven), never hardcoded"

key-files:
  created:
    - app/Filament/Widgets/PartnerQuotaWidget.php
    - tests/Feature/PartnerQuotaReportTest.php
  modified:
    - app/Providers/Filament/PartnerPanelProvider.php
    - app/Filament/Pages/OperationalReports.php
    - resources/views/filament/pages/operational-reports.blade.php
    - lang/en/pios.php
    - lang/ms/pios.php

key-decisions:
  - "Widget guards both canView() (panel-level, hides on /admin auto-discovery) AND getStats() (null ->partner returns []) — defence in depth, never errors for admins."
  - "HQ attainment restricted to the visible partner ids via ->whereIn('partner_id', $visibleIds) in the grouped query, so a partner viewing the report sees only their own row; admin sees all."
  - "Reused PartnerQuotaService for the widget count and the report target; the report's grouped query is the ONLY place counting is re-expressed (required for N+1 avoidance) and uses the identical half-open MYT range form."

requirements-completed: [QUOTA-02, QUOTA-03]

# Metrics
duration: 4min
completed: 2026-06-20
---

# Phase 8 Plan 02: Partner Quota Widget + HQ Attainment Report Summary

**Surfaces the Plan-01 `PartnerQuotaService` in two advisory, read-only places: a partner-panel stats widget showing the logged-in partner's own month-to-date customer count vs target/percent, and an HQ `OperationalReports` section listing every visible partner's current-MYT-month attainment via a single grouped query.**

## Continuation Context

This plan was partially executed by a prior agent that committed ONLY the RED widget tests (`425c6cf`, `tests/Feature/PartnerQuotaWidgetTest.php`) then died from a connection error, leaving the suite red. This run completed the GREEN implementation for both tasks, added the missing HQ report test, and made the full suite green. The existing `425c6cf` commit was NOT amended.

## Performance

- **Duration:** ~4 min
- **Started:** 2026-06-20T06:34:44Z
- **Completed:** 2026-06-20T06:38:48Z
- **Tasks:** 2
- **Files modified:** 7 (2 created, 5 modified)

## Accomplishments

- **PartnerQuotaWidget** (`app/Filament/Widgets/PartnerQuotaWidget.php`): a `StatsOverviewWidget` showing `count / target` and `percent% of target this month` via `PartnerQuotaService::attainment(auth()->user()->partner)`. Reads ONLY the current partner's own number. `canView()` returns false unless `$user->isPartner() && $user->partner !== null`, which also prevents the `AdminPanelProvider` `discoverWidgets()` auto-registration from mounting it on `/admin`. `getStats()` independently guards null `->partner` (returns `[]`), so admins never error.
- Registered explicitly in `PartnerPanelProvider->widgets([PartnerQuotaWidget::class, AccountWidget::class])` (the partner panel does not use `discoverWidgets`).
- **HQ attainment report**: `OperationalReports::getPartnerQuotaAttainmentProperty()` returns per-partner `{partner_id, partner, partner_no, count, target, percent}` for the current MYT month, computed with ONE grouped `COUNT(*) ... groupBy('partner_id')` over the half-open `[startOfMonth, +1 month)` `Asia/Kuala_Lumpur` range, restricted to `whereIn('partner_id', $visibleIds)` (visibleTo-scoped). Target comes from `PartnerQuotaService::target()`. Rendered as a "Partner Monthly Quota" `pios-panel`/`pios-table` section in the report view.
- Bilingual labels added to `lang/en/pios.php` and `lang/ms/pios.php` under `widgets` (`monthly_quota`, `monthly_quota_hint`, and the `partner_quota_*` report column keys).
- Proven by TDD: widget own-number / admin-guard / isolation (3 tests) and HQ attainment rows + single-grouped-query assertion (2 tests). Full suite green (102 passed).

## Task Commits

1. **Task 1: Partner quota dashboard widget (QUOTA-02)** — `8f2ef3b` (feat) — made the pre-existing RED `PartnerQuotaWidgetTest` green.
2. **Task 2: HQ partner monthly quota attainment report (QUOTA-03)** — `9b3a53b` (feat) — RED report test written then implemented to green in the same commit.

_(Pre-existing RED commit `425c6cf` from the prior agent left intact, not amended.)_

## Files Created/Modified

- `app/Filament/Widgets/PartnerQuotaWidget.php` (created) — discover-safe partner quota stats widget.
- `tests/Feature/PartnerQuotaReportTest.php` (created) — HQ attainment correctness + single-grouped-query (N+1) assertion.
- `app/Providers/Filament/PartnerPanelProvider.php` — explicit widget registration + import.
- `app/Filament/Pages/OperationalReports.php` — `getPartnerQuotaAttainmentProperty()` grouped-query attainment.
- `resources/views/filament/pages/operational-reports.blade.php` — Partner Monthly Quota section.
- `lang/en/pios.php`, `lang/ms/pios.php` — quota widget + report labels.

## Decisions Made

- Defence-in-depth guarding on the widget: `canView()` (panel/discovery level) plus `getStats()` null-partner guard — admins neither see it nor error (Pitfall: discoverWidgets auto-mount).
- HQ grouped query restricted to visible partner ids so a partner viewing the report sees only their own attainment; admin sees all (consistent with `scopeVisibleTo`).
- The report's grouped query is the only place counting is re-expressed (needed to avoid per-partner N+1, Pitfall 10) and deliberately reuses the identical half-open MYT range form as `PartnerQuotaService` (Pitfall 8) rather than `whereMonth`/`endOfMonth`.

## Deviations from Plan

None of substance. The plan's `<action>` for Task 2 showed both a `whereBetween` line and a half-open line with a "replace the line above" note; I implemented the half-open form only (two explicit `>=` / `<` bounds), exactly as the plan's IMPORTANT directive and acceptance criteria require. Translation keys for the report columns (`partner_quota_*`) were added in addition to the two widget keys the plan named explicitly, to keep the report view bilingual (consistent with the project's no-hardcoded-English-labels rule) — tracked as Rule 2 (missing critical functionality: bilingual labels).

## Issues Encountered

None. RED → GREEN was clean for both surfaces on first green.

## User Setup Required

None.

## Next Phase Readiness

- Both advisory surfaces (widget + HQ report) read through `PartnerQuotaService`; a future v2 hard-enforcement (QUOTA-05, deferred) plugs into the same service seam without touching these UI surfaces.

## Known Stubs

None.

## Self-Check: PASSED

- Files: app/Filament/Widgets/PartnerQuotaWidget.php, tests/Feature/PartnerQuotaReportTest.php, OperationalReports.php (getPartnerQuotaAttainmentProperty), operational-reports.blade.php (partnerQuotaAttainment section), lang/en/pios.php + lang/ms/pios.php (monthly_quota) — all FOUND.
- Commits: 8f2ef3b (feat widget), 9b3a53b (feat report) — both FOUND; prior RED 425c6cf intact.
- Tests: `php artisan test` → 102 passed, 0 failed, 582 assertions.

---
*Phase: 08-partner-monthly-customer-quota*
*Completed: 2026-06-20*
