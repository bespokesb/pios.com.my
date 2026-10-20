---
phase: 10-crm-data-layer
plan: 01
subsystem: database
tags: [crm, eloquent, policy, ownership-scope, malaysian-phone, whatsapp, laravel]

# Dependency graph
requires:
  - phase: 09-product-cost-vendor-margin
    provides: scopeVisibleTo + policy ownership pattern (Customer/Product) mirrored here
provides:
  - customer_notes table + CustomerNote model (BO-only scopeVisibleTo, denormalized business_owner_id)
  - customer_follow_ups table + CustomerFollowUp model (due_on/completed_at, scopeOverdue, isOverdue)
  - CustomerNotePolicy + CustomerFollowUpPolicy registered in AppServiceProvider
  - Customer notes()/followUps() HasMany relations
  - App\Support\Phone helper (digits/toWhatsApp/toTel MY normalization)
affects: [11-customer-detail-view, 12-segments-stage-change, 13-whatsapp-call, 14-product-cards, 15-share-promote]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Child CRM tables denormalize business_owner_id + carry their own 3-branch scopeVisibleTo (admin/BO/else 1=0); no partner branch"
    - "view() policy gated through visibleTo()->whereKey()->exists() so a foreign BO id is denied, not merely hidden"
    - "Stateless App\\Support\\Phone normalizer as the single source for wa.me (60...) / tel (+60...) forms"

key-files:
  created:
    - database/migrations/2026_06_30_000001_create_customer_notes_table.php
    - database/migrations/2026_06_30_000002_create_customer_follow_ups_table.php
    - app/Models/CustomerNote.php
    - app/Models/CustomerFollowUp.php
    - app/Policies/CustomerNotePolicy.php
    - app/Policies/CustomerFollowUpPolicy.php
    - app/Support/Phone.php
    - tests/Unit/PhoneTest.php
    - tests/Feature/CrmDataLayerTest.php
  modified:
    - app/Models/Customer.php
    - app/Providers/AppServiceProvider.php

key-decisions:
  - "Notes and follow-ups are two separate tables (not polymorphic) — distinct shapes/indexes/lifecycles"
  - "completed_at null = not done (the done-flag); overdue computed in Asia/Kuala_Lumpur via startOfDay comparison"
  - "Child scopeVisibleTo is strictly BO-scoped (no partner branch); partner falls through to whereRaw('1=0')"

patterns-established:
  - "Pattern: denormalized business_owner_id on CRM child tables enables leak-proof direct-query scoping before any UI consumes it"
  - "Pattern: child policy view() uses visibleTo()->whereKey()->exists() (server-side deny, not UI hide)"

requirements-completed: [CRM-01, CRM-02]

# Metrics
duration: 3min
completed: 2026-06-30
---

# Phase 10 Plan 01: CRM Data Layer & Ownership Foundation Summary

**BO-owned `customer_notes` + `customer_follow_ups` tables with denormalized `business_owner_id`, per-model `scopeVisibleTo` + registered policies, follow-up overdue computation in Asia/Kuala_Lumpur, and the stateless `App\Support\Phone` MY normalizer — all proven by ownership-isolation, overdue, and phone-normalization tests.**

## Performance

- **Duration:** 3 min
- **Started:** 2026-06-30T10:43:47Z
- **Completed:** 2026-06-30T10:46:18Z
- **Tasks:** 3
- **Files modified:** 11 (9 created, 2 modified)

## Accomplishments
- Two additive, strictly BO-scoped CRM child tables with denormalized `business_owner_id`, indexed for later reminder/overdue queries; foundation migration untouched.
- `CustomerNote` + `CustomerFollowUp` models with 3-branch `scopeVisibleTo` (no partner branch), follow-up `scopeOverdue` + `isOverdue()`/`isCompleted()` computed in Asia/Kuala_Lumpur.
- `CustomerNotePolicy` + `CustomerFollowUpPolicy` mirror `CustomerPolicy` (view via `visibleTo()->whereKey()->exists()`), registered in `AppServiceProvider`.
- `App\Support\Phone` stateless MY normalizer: `digits`, `toWhatsApp` (60…), `toTel` (+60…), blank → null.
- Ownership isolation, overdue computation, and phone normalization proven by tests; full suite green (122 passed, 639 assertions).

## Task Commits

Each task was committed atomically:

1. **Task 1: Migrations + CustomerNote/CustomerFollowUp models + Customer relations** - `7e068aa` (feat)
2. **Task 2: CRM child policies (registered) + App\Support\Phone helper** - `698dca5` (feat)
3. **Task 3: Tests — ownership isolation + overdue + phone normalization** - `dfad502` (test)

## Files Created/Modified
- `database/migrations/2026_06_30_000001_create_customer_notes_table.php` - customer_notes (customer_id, business_owner_id, body, created_by)
- `database/migrations/2026_06_30_000002_create_customer_follow_ups_table.php` - customer_follow_ups (customer_id, business_owner_id, note, due_on, completed_at, created_by)
- `app/Models/CustomerNote.php` - BO-only scopeVisibleTo, BelongsTo customer/businessOwner/creator
- `app/Models/CustomerFollowUp.php` - casts due_on/completed_at, scopeVisibleTo, scopeOverdue, isOverdue/isCompleted
- `app/Policies/CustomerNotePolicy.php` - mirrors CustomerPolicy, typed to CustomerNote
- `app/Policies/CustomerFollowUpPolicy.php` - mirrors CustomerPolicy, typed to CustomerFollowUp
- `app/Support/Phone.php` - stateless MY phone normalizer (digits/toWhatsApp/toTel)
- `tests/Unit/PhoneTest.php` - MY phone normalization unit proof (7 tests)
- `tests/Feature/CrmDataLayerTest.php` - ownership isolation + overdue + create-on-own-customer proof (5 tests)
- `app/Models/Customer.php` - added notes()/followUps() HasMany relations
- `app/Providers/AppServiceProvider.php` - registered both child policies + imports

## Decisions Made
None beyond plan - followed plan as specified (notes/follow-ups as two tables, BO-only child scope, Asia/Kuala_Lumpur overdue, Phone as single normalizer).

## Deviations from Plan
None - plan executed exactly as written.

## Issues Encountered
None.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Ownership boundary is enforced server-side at the data layer before any UI consumes it — Phases 11–16 inherit a leak-proof scope/policy instead of inventing one.
- `App\Support\Phone` ready for Phase 13 (one-tap WhatsApp/call) and Phase 15 (share/promote).
- No blockers. Full suite green (122 tests).

## Self-Check: PASSED

All 9 created files verified present on disk; all 3 task commits (7e068aa, 698dca5, dfad502) verified in git history. Full suite: 122 passed, 639 assertions.

---
*Phase: 10-crm-data-layer*
*Completed: 2026-06-30*
