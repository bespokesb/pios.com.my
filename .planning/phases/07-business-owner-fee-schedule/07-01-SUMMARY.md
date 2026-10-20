---
phase: 07-business-owner-fee-schedule
plan: 01
subsystem: fees
tags: [fees, business-owner, fee-setting, registration, audit, tdd]
requires:
  - "Phase 6: BusinessOwnerSetup FeeType, fees_bo_type_period_unique index, User::system(), Asia/Kuala_Lumpur timezone"
  - "RegistrationApprover::approveBusinessOwner (single approval funnel)"
  - "FeeSetting model (name UNIQUE, amount decimal:2, effective_from/until date casts)"
provides:
  - "FeeSetting::activeAmount(FeeType, ?Carbon): ?string — table-sourced active fee amount resolver"
  - "Seeded FeeSetting rows: Business Owner Setup Fee (10.00 MYR), Business Owner Monthly Fee (5.00 MYR)"
  - "Idempotent, audited one-time BusinessOwnerSetup fee generated on BO approval"
affects:
  - "Plan 02 monthly command (reuses FeeSetting::activeAmount(FeeType::BusinessOwnerMonthly))"
  - "Fee/Payment module (new setup fee rows for every approved BO)"
tech-stack:
  added: []
  patterns:
    - "Effective-dated config resolver returning newest active row within day bounds"
    - "firstOrCreate on NULL billing_period as the idempotency guard for non-recurring fees"
    - "Audit only on wasRecentlyCreated to avoid duplicate audit entries on re-approval"
key-files:
  created:
    - "tests/Feature/BusinessOwnerSetupFeeTest.php"
  modified:
    - "app/Models/FeeSetting.php"
    - "database/seeders/DatabaseSeeder.php"
    - "app/Services/RegistrationApprover.php"
decisions:
  - "Compare effective dates against full day bounds (startOfDay/endOfDay), not toDateString(), so today-dated rows resolve on SQLite (datetime-string storage) and MySQL (DATE column)."
  - "Setup-fee generation lives in approveBusinessOwner(), never the shared activate(), so partners never receive a setup fee."
  - "Setup fee is a non-blocking obligation: Unpaid, no status gate on the BO."
metrics:
  duration: 3m
  tasks: 3
  files: 4
  completed: 2026-06-20
---

# Phase 7 Plan 01: Business Owner Fee Schedule (Setup Fee + Configurable Amounts) Summary

One-time RM10 Business Owner setup fee generated idempotently and audited at HQ approval, with both setup (10.00) and monthly (5.00) MYR amounts resolved from `FeeSetting` via a new effective-dated `activeAmount()` resolver — never hardcoded.

## What Was Built

**FEE-03 — Configurable fee amounts (`FeeSetting::activeAmount`)**
- Added `FeeSetting::activeAmount(FeeType $type, ?Carbon $on = null): ?string`. It returns the newest `Active` row whose effective window contains `$on` (defaults to `now()` in Asia/Kuala_Lumpur), or `null` when none is active (caller decides fallback — no hard-fail).
- Seeded two effective-dated, idempotent `FeeSetting` rows via `updateOrCreate` keyed on the unique `name`: **Business Owner Setup Fee** = 10.00 MYR, **Business Owner Monthly Fee** raised from 0 to 5.00 MYR.

**FEE-01 — One-time setup fee at approval (`RegistrationApprover`)**
- `approveBusinessOwner()` now calls `generateSetupFee()` after `activate()`. It creates exactly one `BusinessOwnerSetup` fee: `status=Unpaid`, `billing_period=NULL`, `amount` from `FeeSetting::activeAmount(BusinessOwnerSetup)`, `partner_id = $bo->partner_id`, `due_date = now()+30d`.
- Kept strictly inside `approveBusinessOwner` (not the shared `activate()`) so partners never get a setup fee.
- Non-blocking: no status gate added — the BO is `Active` and operable regardless of the fee.

**Idempotency mechanism (reused by Plan 02)**
- `Fee::firstOrCreate` keyed on `(business_owner_id, fee_type, billing_period=null)`. Because `billing_period` is NULL, the `fees_bo_type_period_unique` index does not enforce uniqueness — `firstOrCreate`'s SELECT-then-INSERT is the guard. The `where('billing_period', null)` lookup matches the existing NULL row, so re-approval finds the existing fee and creates nothing.
- Audit (`AuditLogger::log`, event `fee_generated`, actor = approving HQ user, falling back to `User::system()`) fires only on `wasRecentlyCreated`, so re-approval never duplicates the audit entry either.

## Tasks Completed

| Task | Name | Commit | Files |
| ---- | ---- | ------ | ----- |
| 1 | FeeSetting::activeAmount resolver + seed setup/monthly settings | e99ebb5 | app/Models/FeeSetting.php, database/seeders/DatabaseSeeder.php, tests/Feature/BusinessOwnerSetupFeeTest.php |
| 2 | Generate idempotent, audited setup fee in approveBusinessOwner | fdd50ff | app/Services/RegistrationApprover.php, tests/Feature/BusinessOwnerSetupFeeTest.php |
| 3 | TDD test suite (resolver + idempotency + audit) | fdd50ff | tests/Feature/BusinessOwnerSetupFeeTest.php (5 tests, written incrementally across Tasks 1-2) |

## Test Coverage (tests/Feature/BusinessOwnerSetupFeeTest.php)

- `test_active_amount_reads_from_fee_setting` — null before seeding; 10.00/5.00 after; returns new amount after the row's amount is changed (proves table-sourced).
- `test_approving_business_owner_creates_one_setup_fee` — exactly one Unpaid setup fee, amount 10.00, partner_id set, billing_period NULL, due_date set, BO Active.
- `test_reapproving_business_owner_does_not_duplicate_setup_fee` — count stays 1 after a second approval.
- `test_setup_fee_amount_comes_from_setting` — setting changed to 12.00 before approval → fee amount 12.00.
- `test_setup_fee_is_audited_with_actor` — AuditLog row (auditable Fee, event `fee_generated`, actor = approving admin).

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Effective-date comparison failed against start-of-day-stored datetimes**
- **Found during:** Task 1 (RED→GREEN: resolver returned null for a today-dated active row).
- **Issue:** The plan's resolver compared `effective_from <= $on->toDateString()`. `date`-cast columns persist at start-of-day (e.g. SQLite stores `'2026-06-20 00:00:00'`). Lexically `'2026-06-20 00:00:00' <= '2026-06-20'` is FALSE (the trailing time makes the stored string "greater"), so today-dated rows were excluded.
- **Fix:** Compare against full day bounds — `effective_from <= $on->endOfDay()` and `effective_until >= $on->startOfDay()` (both as datetime strings). Correct on SQLite (datetime-string storage) and MySQL (DATE column truncates time).
- **Files modified:** app/Models/FeeSetting.php
- **Commit:** e99ebb5

**2. [Rule 3 - Blocking] `UserStatus::Pending` does not exist**
- **Found during:** Task 2 (test helper for a pending BO).
- **Issue:** Plan task 3 implied seeding a BO user with a pending user status, but `UserStatus` only has Active/Inactive/Suspended.
- **Fix:** Used `UserStatus::Inactive` for the pre-approval BO user (the `RecordStatus::Pending` BO record drives the approval; `activate()` flips an Inactive user to Active). No production code affected.
- **Files modified:** tests/Feature/BusinessOwnerSetupFeeTest.php
- **Commit:** fdd50ff

## Verification

- `php artisan test --filter=BusinessOwnerSetupFeeTest` — 5 passed, 14 assertions.
- `php artisan test` (FULL suite) — 86 passed, 534 assertions, no regressions.
- Grep checks: `activeAmount` present in FeeSetting; `BusinessOwnerSetup` seeded; `firstOrCreate` present in RegistrationApprover; the only literal fee amount in the approver is the `?? 10.00` safety-net fallback.

## Notes for Plan 02

- Reuse `FeeSetting::activeAmount(FeeType::BusinessOwnerMonthly)` for the monthly command — the Monthly Fee setting (5.00 MYR) is now seeded and Active.
- For recurring monthly fees the DB unique index `(business_owner_id, fee_type, billing_period)` IS the enforced guard (billing_period non-NULL); pair it with `firstOrCreate` and `User::system()` as the audit actor for the scheduled run (no `auth()->user()` in cron context).

## Self-Check: PASSED

All 4 modified/created source files and the SUMMARY exist on disk; both task commits (e99ebb5, fdd50ff) are present in git history. Full suite green (86/86).
