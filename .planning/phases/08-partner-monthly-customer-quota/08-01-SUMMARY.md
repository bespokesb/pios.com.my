---
phase: 08-partner-monthly-customer-quota
plan: 01
subsystem: quota
tags: [laravel, carbon, timezone, quota, read-model, tdd]

# Dependency graph
requires:
  - phase: 06-fee-quota-correctness-foundation
    provides: "(partner_id, created_at) composite index on customers; Asia/Kuala_Lumpur app timezone"
provides:
  - "config/pios.php with partner_monthly_customer_target (default 100, env PARTNER_MONTHLY_CUSTOMER_TARGET)"
  - "App\\Services\\PartnerQuotaService: monthToDateCount(Partner, ?reference), target(), attainment(Partner, ?reference) => {count,target,percent}"
  - "Half-open MYT month-range count contract on customers.partner_id (the single quota read model)"
affects: [08-02 (partner widget + HQ report), QUOTA-05 (future v2 enforcement seam)]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Half-open [start, nextStart) MYT month range via CarbonImmutable anchored to Asia/Kuala_Lumpur (no whereMonth/whereYear, no whereBetween/endOfMonth)"
    - "Single canonical attribution column (customers.partner_id) — no business_owner aggregation to avoid double counting"
    - "Advisory read-only service: no policy changes, no blocking, no writes"

key-files:
  created:
    - config/pios.php
    - app/Services/PartnerQuotaService.php
    - tests/Feature/PartnerQuotaServiceTest.php
  modified: []

key-decisions:
  - "Quota counts ANY customer the partner registered (whole downstream via customers.partner_id), not Prospect+ only — matches the denormalized FK set at creation."
  - "Target is config-driven (config('pios.partner_monthly_customer_target'), default 100, env-overridable) so v2 enforcement and per-env tuning need no code change."
  - "attainment().percent uses floor() and a target>0 guard so percent is integer and div-by-zero safe."

patterns-established:
  - "Half-open MYT month range is the canonical quota/period boundary computation (CarbonImmutable::parse($ref ?? now(), 'Asia/Kuala_Lumpur')->startOfMonth() .. ->addMonth())."
  - "Quota read model is a plain App\\Services class with no constructor deps, mirroring OperationalReportService style."

requirements-completed: [QUOTA-01, QUOTA-04]

# Metrics
duration: 2min
completed: 2026-06-20
---

# Phase 8 Plan 01: PartnerQuotaService Summary

**Advisory, read-only PartnerQuotaService that counts a partner's current-MYT-month customer registrations over a half-open `[startOfMonth, +1 month)` range on `customers.partner_id`, against a configurable target (default 100).**

## Performance

- **Duration:** 2 min
- **Started:** 2026-06-20T05:31:18Z
- **Completed:** 2026-06-20T05:32:46Z
- **Tasks:** 2
- **Files modified:** 3 (all created)

## Accomplishments
- `PartnerQuotaService::monthToDateCount()` counts only the given partner's customers created in the current Malaysia-time calendar month, using a timezone-safe half-open interval that relies on the Phase 6 `(partner_id, created_at)` index.
- `config/pios.php` introduces `partner_monthly_customer_target` (default 100, overridable via `PARTNER_MONTHLY_CUSTOMER_TARGET`).
- `attainment()` returns `{count, target, percent}` with a div-by-zero-safe integer percent.
- Proven by TDD: boundary (first instant of month included, last instant of prev month excluded), partner isolation, configurability, and percent math — full suite stays green (97 passed).

## Task Commits

Each task was committed atomically (TDD red → green):

1. **Task 1: Write failing PartnerQuotaService tests (RED)** - `bee2f93` (test)
2. **Task 2: Add config key and implement PartnerQuotaService (GREEN)** - `148b905` (feat)

_No refactor commit needed — implementation was clean on first green._

## Files Created/Modified
- `config/pios.php` - New Laravel config; `partner_monthly_customer_target` env-with-default (100).
- `app/Services/PartnerQuotaService.php` - Read-only quota read model: `monthToDateCount`, `target`, `attainment`.
- `tests/Feature/PartnerQuotaServiceTest.php` - 4 tests: boundary (fixed-time via `Carbon::setTestNow`), isolation, configurability, attainment math + div-by-zero guard.

## Decisions Made
- Counted via the single canonical `customers.partner_id` column only (no `business_owner` join) to avoid double counting (PITFALLS Pitfall 9).
- Used a half-open `[start, nextStart)` interval rather than `whereMonth`/`whereBetween`/`endOfMonth` to avoid the timezone trap and the 23:59:59 boundary gap (PITFALLS Pitfall 8).
- Anchored `CarbonImmutable::parse(..., 'Asia/Kuala_Lumpur')` explicitly so the month boundary stays correct even if `APP_TIMEZONE` is overridden per environment.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None. (A local `grep`/`ugrep` alias mis-parsed argument strings starting with `-` during verification; worked around with `grep -F` — no impact on code or tests.)

## User Setup Required
None - no external service configuration required. Optionally set `PARTNER_MONTHLY_CUSTOMER_TARGET` in `.env` to override the default target of 100.

## Next Phase Readiness
- Plan 02 (partner quota widget + HQ report) can consume `PartnerQuotaService::attainment($partner)` directly for `{count, target, percent}`.
- The half-open-range count is the exact seam a future v2 hard-enforcement (QUOTA-05, deferred) would call.

## Known Stubs
None.

## Self-Check: PASSED

- Files: config/pios.php, app/Services/PartnerQuotaService.php, tests/Feature/PartnerQuotaServiceTest.php, 08-01-SUMMARY.md — all FOUND.
- Commits: bee2f93 (test RED), 148b905 (feat GREEN) — all FOUND.
- Tests: `php artisan test` → 97 passed, 0 failed.

---
*Phase: 08-partner-monthly-customer-quota*
*Completed: 2026-06-20*
