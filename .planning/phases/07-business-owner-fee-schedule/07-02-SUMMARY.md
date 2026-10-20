---
phase: 07-business-owner-fee-schedule
plan: 02
subsystem: fees
tags: [fees, business-owner, scheduler, console-command, idempotency, audit, filament, tdd]
requires:
  - "Plan 07-01: FeeSetting::activeAmount(FeeType::BusinessOwnerMonthly) resolver + seeded 5.00 MYR Monthly Fee"
  - "Phase 6: fees_bo_type_period_unique index, User::system(), Asia/Kuala_Lumpur timezone, BusinessOwnerMonthly FeeType"
  - "AuditLogger::log (scheduled-job audit), Fee model (#[Fillable] incl. billing_period, no auto partner_id)"
provides:
  - "fees:generate-monthly Artisan command — idempotent per-active-BO monthly fee generation with --period=YYYY-MM backfill"
  - "Scheduled registration: monthlyOn(1, '00:30')->withoutOverlapping()->onOneServer()"
  - "HQ fee obligations view: billing_period column + status SelectFilter + 'Overdue only' filter on FeesTable"
affects:
  - "Fee/Payment module — recurring monthly BusinessOwnerMonthly rows now created automatically each month"
  - "Plan 08 (Quota) and any reporting that reads monthly fee obligations"
tech-stack:
  added: []
  patterns:
    - "firstOrCreate on (business_owner_id, fee_type, billing_period) as the recurring-fee idempotency guard, backed by the unique index"
    - "Scheduled job audit actor resolved via User::system() (no auth()->user() in cron context)"
    - "Computed-for-display 'overdue' (status in {unpaid,pending} AND due_date < today); stored status never flipped by the generator"
    - "chunkById(200) over active BOs for memory-safe batch generation"
key-files:
  created:
    - "app/Console/Commands/GenerateMonthlyBusinessOwnerFees.php"
    - "tests/Feature/MonthlyBusinessOwnerFeeTest.php"
  modified:
    - "routes/console.php"
    - "app/Filament/Resources/Fees/Tables/FeesTable.php"
decisions:
  - "Idempotency rests on the firstOrCreate triple + fees_bo_type_period_unique index, NOT cron timing; withoutOverlapping/onOneServer are defense-in-depth only."
  - "Overdue is computed in the FeesTable filter, never persisted by the command — the generator always writes status=Unpaid."
  - "Amount is always sourced from FeeSetting::activeAmount(BusinessOwnerMonthly); the command FAILs (no rows created) if no active setting exists, rather than guessing an amount."
metrics:
  duration: 4m
  tasks: 3
  files: 4
  completed: 2026-06-20
---

# Phase 7 Plan 02: Business Owner Monthly Fee Schedule + HQ Obligations View Summary

The recurring RM5 Business Owner monthly fee is now generated idempotently by a scheduled `fees:generate-monthly` Artisan command — one `BusinessOwnerMonthly` fee per ACTIVE Business Owner per Asia/Kuala_Lumpur calendar month, amount sourced from `FeeSetting`, audited as `User::system()`, and never double-billed on rerun/overlap — and HQ can review every BO's obligations (period, status, overdue) in the extended FeeResource table.

## What Was Built

**FEE-02 — Idempotent recurring monthly fee (`fees:generate-monthly`)**
- New `app/Console/Commands/GenerateMonthlyBusinessOwnerFees.php`.
- Signature: `fees:generate-monthly {--period= : Billing period YYYY-MM (defaults to current Asia/Kuala_Lumpur month)}`.
- For each `BusinessOwner` with `status = RecordStatus::Active`, creates one `Fee` via:
  ```php
  Fee::firstOrCreate(
      ['business_owner_id' => $bo->id, 'fee_type' => FeeType::BusinessOwnerMonthly, 'billing_period' => $period],
      ['partner_id' => $bo->partner_id, 'amount' => $amount, 'due_date' => $dueDate, 'status' => FeeStatus::Unpaid],
  );
  ```
- **Idempotency key:** `(business_owner_id, fee_type=BusinessOwnerMonthly, billing_period='YYYY-MM')` — the `firstOrCreate` SELECT-then-INSERT plus the Phase 6 `fees_bo_type_period_unique` index guarantee reruns and overlapping runs never duplicate.
- **Period:** `now()->format('Y-m')` (already MYT) or `--period=YYYY-MM` for backfill. **Due date:** `Carbon::createFromFormat('Y-m', $period)->endOfMonth()->toDateString()` (e.g. `2026-01` → `2026-01-31`).
- **Amount:** `FeeSetting::activeAmount(FeeType::BusinessOwnerMonthly)` — never hardcoded. If no active setting exists the command warns and returns `FAILURE` without creating rows.
- **Active-only:** Pending / Suspended / Inactive BOs are never billed.
- **Audit:** each newly created fee (`wasRecentlyCreated`) is logged via `AuditLogger::log` with `event='fee_generated'`, actor `User::system()`.
- **Registration (`routes/console.php`):**
  ```php
  Schedule::command(GenerateMonthlyBusinessOwnerFees::class)
      ->monthlyOn(1, '00:30')
      ->withoutOverlapping()
      ->onOneServer();
  ```

**FEE-04 — HQ fee obligations view (`FeesTable`)**
- Added a `billing_period` "Period" column (searchable, sortable, `—` placeholder for non-recurring/NULL setup fees) after `fee_type`.
- Replaced the empty `->filters([])` with:
  - `SelectFilter::make('status')->options(FeeStatus::options())`.
  - `Filter::make('overdue')->label('Overdue only')` querying `status IN (unpaid, pending) AND due_date < today` — the overdue predicate (overdue is computed for display, not a stored status).
- `FeeResource::getEloquentQuery` / `visibleTo` scoping unchanged: HQ sees all, partner/BO remain scoped.

## Tasks Completed

| Task | Name | Commit | Files |
| ---- | ---- | ------ | ----- |
| 1 | `fees:generate-monthly` command + schedule registration (FEE-02) | 659abf4 | app/Console/Commands/GenerateMonthlyBusinessOwnerFees.php, routes/console.php, tests/Feature/MonthlyBusinessOwnerFeeTest.php |
| 2 | HQ obligations view — period column + status/overdue filters (FEE-04) | 56f76ec | app/Filament/Resources/Fees/Tables/FeesTable.php |
| 3 | TDD test suite (idempotency, exclusion, amount, backfill, audit, overdue) | 659abf4 | tests/Feature/MonthlyBusinessOwnerFeeTest.php (7 tests; written RED-first with Task 1) |

> Task 3's test file was authored RED-first as part of the Task 1 TDD cycle (the command was implemented to make these tests pass), so it shares Task 1's commit. All 7 plan-mandated cases are present and green.

## Test Coverage (tests/Feature/MonthlyBusinessOwnerFeeTest.php)

- `test_command_generates_one_monthly_fee_per_active_business_owner` — 2 active BOs → exactly 2 BusinessOwnerMonthly fees for the current period, each amount `5.00`, status Unpaid, partner_id set, billing_period = `now()->format('Y-m')`.
- `test_running_command_twice_does_not_duplicate` — two runs → still exactly one fee per BO for the period.
- `test_inactive_business_owners_are_not_billed` — Active + Pending + Suspended + Inactive → only the Active BO is billed.
- `test_amount_comes_from_fee_setting` — setting changed to `7.00` → generated fee amount `7.00`.
- `test_period_option_backfills_a_past_month_idempotently` — `--period=2026-01` twice → one fee, billing_period `2026-01`, due_date `2026-01-31`.
- `test_audit_actor_is_system_user` — AuditLog row (auditable Fee, event `fee_generated`, actor_id = `User::system()->id`).
- `test_overdue_is_computed` — past-due Unpaid fee matches the overdue predicate; future-due one does not (queries the same WHERE clause the FeesTable filter uses).

## Deviations from Plan

None — plan executed exactly as written. (`RecordStatus` has explicit `Pending`/`Suspended`/`Inactive` cases, so the inactive-exclusion test uses real statuses with no substitution needed.)

## Verification

- `php artisan test --filter=MonthlyBusinessOwnerFeeTest` — 7 passed, 28 assertions.
- `php artisan test` (FULL suite) — **93 passed, 562 assertions**, no regressions (was 86 in 07-01; +7 new).
- `php artisan list | grep fees` — `fees:generate-monthly` registered.
- `php artisan schedule:list` — `30 0 1 * * php artisan fees:generate-monthly` (Next Due: 1 week).
- Grep checks: `activeAmount(FeeType::BusinessOwnerMonthly` + `firstOrCreate` in the command; `Schedule::command` + `monthlyOn` + `withoutOverlapping` + `onOneServer` in routes/console.php; `billing_period` + `overdue` in FeesTable.

## Notes for Downstream

- The monthly command writes only `status=Unpaid`; "overdue" is purely a view-layer computation. Any report or gating that needs "overdue" must apply the same predicate (`status IN {unpaid,pending} AND due_date < today`), not read a stored Overdue status.
- BO suspension/gating on unpaid fees and first-month proration remain out of scope for v1.1 (see STATE.md Deferred Items).

## Self-Check: PASSED

All 4 created/modified files exist on disk; both task commits (659abf4, 56f76ec) are present in git history. Full suite green (93/93).
