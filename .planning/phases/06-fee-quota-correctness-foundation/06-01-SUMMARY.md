---
phase: 06-fee-quota-correctness-foundation
plan: 01
subsystem: fees-quota-foundation
tags: [schema, migration, enum, seeder, timezone, audit-actor]
requires: []
provides:
  - fees.billing_period column (nullable YYYY-MM)
  - unique index fees_bo_type_period_unique (business_owner_id, fee_type, billing_period)
  - customers_partner_created_index (partner_id, created_at)
  - FeeType::BusinessOwnerSetup enum case
  - pios-system audit user (system@pios.com.my) + User::system() resolver
  - Asia/Kuala_Lumpur app timezone (env-driven)
affects:
  - Phase 7 (Business Owner Fee Schedule)
  - Phase 8 (Partner Monthly Customer Quota)
tech-stack:
  added: []
  patterns:
    - firstOrCreate idempotency for system actor and recurring-fee keys
    - DB-level unique index as the duplicate-fee guard (not cron timing)
    - env-driven timezone default
key-files:
  created:
    - database/migrations/2026_06_20_000001_add_billing_period_to_fees_and_quota_index.php
    - database/seeders/SystemUserSeeder.php
    - tests/Feature/FeeQuotaFoundationTest.php
  modified:
    - app/Enums/FeeType.php
    - app/Models/Fee.php
    - app/Models/User.php
    - database/seeders/DatabaseSeeder.php
    - config/app.php
    - .env.example
    - .gitignore
decisions:
  - Nullable billing_period so non-recurring fees (setup/VIP/partner) leave it null; MySQL treats NULLs as non-duplicate, intentionally allowing multiple non-recurring fees per BO.
  - System user is SysAdmin + Inactive so it is a valid audit FK actor but can never log into any Filament panel.
  - User::system() uses firstOrCreate to be self-healing in environments where the seeder has not run (tests).
metrics:
  duration: ~2 min
  completed: 2026-06-20
requirements: [FND-06]
---

# Phase 06 Plan 01: Fee & Quota Correctness Foundation Summary

DB-enforced no-double-billing foundation: a nullable `billing_period` on `fees` guarded by a composite unique index, a `(partner_id, created_at)` quota index, a `BusinessOwnerSetup` fee type, an idempotent non-login `pios-system` audit actor, and an env-driven `Asia/Kuala_Lumpur` default timezone — all proven by passing tests with the full suite green.

## What Was Built

- **New migration** `database/migrations/2026_06_20_000001_add_billing_period_to_fees_and_quota_index.php`:
  - Adds nullable `billing_period` (`VARCHAR(7)`, YYYY-MM) to `fees` after `fee_type`.
  - Unique index **`fees_bo_type_period_unique`** on `(business_owner_id, fee_type, billing_period)` — the database-level guard that makes duplicate monthly fees impossible regardless of cron timing (Pitfall 1).
  - Index **`customers_partner_created_index`** on `(partner_id, created_at)` for partner-by-month quota counts (Phase 8 prerequisite).
  - The existing foundation migration `2026_06_06_000002_create_pios_foundation_tables.php` was NOT edited (verified no diff).
- **Enum** `FeeType::BusinessOwnerSetup = 'business_owner_setup'` with label `Business Owner Setup`, alongside the existing `BusinessOwnerMonthly`.
- **Fee model** `billing_period` added to `#[Fillable([...])]`.
- **SystemUserSeeder** `firstOrCreate` non-login `system@pios.com.my` user (role `SysAdmin`, status `Inactive`), registered in `DatabaseSeeder::run()`.
- **`User::system()`** static resolver — idempotent/self-healing via `firstOrCreate`, ready as the Phase 7 scheduled-job audit actor (FND-06).
- **Timezone** `config/app.php` now `env('APP_TIMEZONE', 'Asia/Kuala_Lumpur')` (was hardcoded `'UTC'`); `.env.example` gains `APP_TIMEZONE=Asia/Kuala_Lumpur`.
- **Tests** `tests/Feature/FeeQuotaFoundationTest.php` (6 tests, 11 assertions): duplicate-period rejection (QueryException), firstOrCreate single-row, nullable-period multi-insert, setup fee type value/label/options, seeder idempotency + non-login, timezone.

## Verification

- `php artisan migrate:fresh --env=testing` (sqlite in-memory): clean, new migration applies.
- `php artisan test --filter=FeeQuotaFoundationTest`: **6 passed, 11 assertions**.
- `php artisan test` (full suite): **81 passed, 520 assertions** — no regression from the timezone change or new column/index.
- `php artisan tinker --execute="echo config('app.timezone');"`: prints `Asia/Kuala_Lumpur`.
- Foundation migration shows no diff (a new migration was added).

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Removed stray `pios_my` sqlite artifact and gitignored it**
- **Found during:** Post-task working-tree check.
- **Issue:** Running `php artisan migrate:fresh --database=sqlite` (per Task 1's verify step) caused sqlite to interpret the MySQL database name `pios_my` as a file path, creating a 430KB stray `pios_my` sqlite file in the repo root.
- **Fix:** Deleted the file and added `/pios_my` to `.gitignore` so this generated artifact is never committed.
- **Files modified:** `.gitignore`
- **Commit:** included in the final docs commit.

No other deviations — the three tasks were executed exactly as written.

## Notes for Downstream Phases

- **Phase 7 (Fees):** Resolve the fee audit actor via `User::system()`. Key monthly-fee idempotency on `(business_owner_id, FeeType::BusinessOwnerMonthly, billing_period='YYYY-MM')` via `firstOrCreate` — the DB unique index `fees_bo_type_period_unique` is the backstop. Read amounts from `FeeSetting` (do not hardcode the $10/$5; currency is MYR per repo default — confirm before user-facing labels). `BusinessOwnerSetup` fees leave `billing_period` null (non-recurring; multiple allowed).
- **Phase 8 (Quota):** Count quota via the `customers_partner_created_index` `(partner_id, created_at)` using a half-open calendar-month interval `[start, nextMonthStart)` computed in `Asia/Kuala_Lumpur` (now the app default).

## Self-Check: PASSED

All created/modified files exist on disk and all three task commits (`f60059e`, `bdf97b2`, `4b37fb9`) are present in git history.
