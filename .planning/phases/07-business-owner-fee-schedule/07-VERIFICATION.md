---
phase: 07-business-owner-fee-schedule
verified: 2026-06-20T00:00:00Z
status: passed
score: 4/4 must-haves verified
---

# Phase 7: Business Owner Fee Schedule Verification Report

**Phase Goal:** Charge each Business Owner a one-time setup fee at HQ approval and a recurring monthly fee thereafter, with amounts driven by Fee Settings and obligations visible to HQ — without ever double-billing.
**Verified:** 2026-06-20
**Status:** passed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths (mapped to FEE-01..04)

| # | Truth (Requirement) | Status | Evidence |
| --- | --- | --- | --- |
| 1 | FEE-01: `approveBusinessOwner()` generates exactly one idempotent, audited, non-blocking setup fee; re-approval does not duplicate; partners excluded | ✓ VERIFIED | `RegistrationApprover.php:39-81` — `generateSetupFee()` uses `Fee::firstOrCreate` keyed on (business_owner_id, fee_type=BusinessOwnerSetup, billing_period=null), `status=Unpaid`, no status gate. Audit fires only on `wasRecentlyCreated`. `generateSetupFee` called only in `approveBusinessOwner` (line 42), NOT in `activate()` (shared) or `approvePartner()`. Tests `test_approving_business_owner_creates_one_setup_fee`, `test_reapproving_..._does_not_duplicate`, `test_setup_fee_is_audited_with_actor` green. |
| 2 | FEE-02: `fees:generate-monthly` generates one BusinessOwnerMonthly fee per ACTIVE BO per (Y-m, Asia/KL) via firstOrCreate; idempotent; inactive excluded; actor User::system(); registered with overlap guards | ✓ VERIFIED | `GenerateMonthlyBusinessOwnerFees.php:22-70` — period `now()->format('Y-m')`, `where('status', RecordStatus::Active)`, `firstOrCreate` on (business_owner_id, fee_type, billing_period), actor `User::system()`. `routes/console.php:12-15` registers with `monthlyOn(1,'00:30')->withoutOverlapping()->onOneServer()`; `schedule:list` shows `30 0 1 * *`. Tests for one-per-active-BO, twice-no-dup, inactive-exclusion, audit-actor-system green. |
| 3 | FEE-03: amounts come from FeeSetting (10.00 setup / 5.00 monthly MYR), proven by setting-change test | ✓ VERIFIED | `FeeSetting::activeAmount()` resolver (`FeeSetting.php:20-38`) reads newest Active effective-dated row. Seeder seeds Setup 10.00 + Monthly 5.00 (`DatabaseSeeder.php:72-86`). Approver and command both call `activeAmount(...)`; only literal is the `?? 10.00` safety net in approver. Tests `test_setup_fee_amount_comes_from_setting` (12.00) and `test_amount_comes_from_fee_setting` (7.00) prove table-sourced. |
| 4 | FEE-04: FeesTable shows obligations with paid/pending/overdue (overdue = unpaid/pending AND due_date past) + status/overdue filter | ✓ VERIFIED | `FeesTable.php:31-61` — `billing_period` "Period" column, `status` badge column, `SelectFilter('status')` + `Filter('overdue')` querying `status IN (unpaid,pending) AND due_date < today`. Test `test_overdue_is_computed` mirrors the predicate. |

**Score:** 4/4 truths verified

### Required Artifacts

| Artifact | Expected | Status | Details |
| --- | --- | --- | --- |
| `app/Models/FeeSetting.php` | `activeAmount(FeeType, ?Carbon)` resolver | ✓ VERIFIED | Static resolver present, table-sourced, day-bounds comparison handles SQLite/MySQL. |
| `database/seeders/DatabaseSeeder.php` | Setup 10.00 + Monthly 5.00 via updateOrCreate | ✓ VERIFIED | Both rows seeded, idempotent on unique `name`, correct fee_types. |
| `app/Services/RegistrationApprover.php` | Idempotent setup-fee in approveBusinessOwner | ✓ VERIFIED | `firstOrCreate`, audited, partner-excluded, non-blocking. |
| `app/Console/Commands/GenerateMonthlyBusinessOwnerFees.php` | Idempotent per-active-BO monthly generation | ✓ VERIFIED | `firstOrCreate`, active-only, `--period` backfill, system actor, FAILs on missing setting. |
| `routes/console.php` | Scheduled registration with guards | ✓ VERIFIED | `Schedule::command(...)->monthlyOn(1,'00:30')->withoutOverlapping()->onOneServer()`. |
| `app/Filament/Resources/Fees/Tables/FeesTable.php` | period column + status + overdue filter | ✓ VERIFIED | All present; existing columns/scoping untouched. |
| `tests/Feature/BusinessOwnerSetupFeeTest.php` | TDD coverage | ✓ VERIFIED | 5 tests, all green. |
| `tests/Feature/MonthlyBusinessOwnerFeeTest.php` | TDD coverage | ✓ VERIFIED | 7 tests, all green. |

### Key Link Verification

| From | To | Via | Status |
| --- | --- | --- | --- |
| RegistrationApprover | FeeSetting | `activeAmount(FeeType::BusinessOwnerSetup)` | ✓ WIRED (line 57) |
| RegistrationApprover | fees table | `Fee::firstOrCreate` on bo+type+null period | ✓ WIRED (line 59) |
| GenerateMonthlyBusinessOwnerFees | FeeSetting | `activeAmount(FeeType::BusinessOwnerMonthly)` | ✓ WIRED (line 26) |
| GenerateMonthlyBusinessOwnerFees | fees table | `Fee::firstOrCreate` on bo+type+period | ✓ WIRED (line 41) |
| routes/console.php | command | `Schedule::command(...)->withoutOverlapping()->onOneServer()` | ✓ WIRED |
| GenerateMonthly | unique index | `fees_bo_type_period_unique` on (business_owner_id, fee_type, billing_period) | ✓ WIRED (migration 2026_06_20_000001) |

### No-Double-Bill Verification (core goal invariant)

- Setup fee: `firstOrCreate` SELECT-then-INSERT guards the NULL billing_period (MySQL unique index does not enforce NULLs); re-approval test confirms count stays 1.
- Monthly fee: `firstOrCreate` triple + enforced `fees_bo_type_period_unique` index; twice-run test confirms count stays 1 per BO/period; `withoutOverlapping`+`onOneServer` add defense-in-depth.

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
| --- | --- | --- | --- |
| Phase 7 test suites | `php artisan test --filter='BusinessOwnerSetupFeeTest\|MonthlyBusinessOwnerFeeTest'` | 12 passed, 42 assertions | ✓ PASS |
| Full regression suite | `php artisan test` | 93 passed, 562 assertions | ✓ PASS |
| Schedule registered | `php artisan schedule:list` | `30 0 1 * * php artisan fees:generate-monthly` | ✓ PASS |
| Documented commits exist | `git cat-file -t e99ebb5 fdd50ff 659abf4 56f76ec` | all present | ✓ PASS |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
| --- | --- | --- | --- | --- |
| FEE-01 | 07-01 | One-time RM10 setup fee on HQ approval, non-blocking | ✓ SATISFIED | Truth 1 |
| FEE-02 | 07-02 | Recurring RM5 monthly fee, at most once per BO per month | ✓ SATISFIED | Truth 2 |
| FEE-03 | 07-01 | Amounts/currency configurable via Fee Settings | ✓ SATISFIED | Truth 3 |
| FEE-04 | 07-02 | HQ view of obligations with paid/pending/overdue | ✓ SATISFIED | Truth 4 |

All four phase requirement IDs (FEE-01..04) are declared across the two plans' frontmatter and present in REQUIREMENTS.md (lines 72-75) and the traceability table (lines 174-177). No orphaned requirements.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
| --- | --- | --- | --- | --- |
| RegistrationApprover.php | 57 | `?? 10.00` literal | ℹ️ Info | Documented misconfiguration safety net; primary value comes from FeeSetting. Not a stub — verified by amount-from-setting test. |

No blocker or warning anti-patterns. No TODO/FIXME/placeholder, no empty returns, no hardcoded fee amounts in the generation paths.

### Human Verification Required

None required for goal verification. Optional visual confirmation: that the Filament FeesTable "Overdue only" filter and "Period" column render as expected in the HQ panel UI (the underlying query predicate is already test-covered).

### Gaps Summary

No gaps. All four observable truths (FEE-01..04) are verified against the actual codebase: setup fee is idempotent/audited/non-blocking and partner-excluded, the monthly command bills only active BOs once per Asia/Kuala_Lumpur month with a system audit actor and overlap-safe schedule, both amounts are sourced from FeeSetting (proven by setting-change tests), and HQ obligations are visible with paid/pending/overdue status and an overdue filter. The no-double-bill invariant holds via firstOrCreate plus the enforced unique index. Full suite green (93/93), no regressions.

---

_Verified: 2026-06-20_
_Verifier: Claude (gsd-verifier)_
