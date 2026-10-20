---
phase: 01-foundation-roles-and-data-model
plan: "01"
subsystem: database
tags: [laravel, filament, mysql, eloquent, enums, identifiers]
requires: []
provides:
  - Core PIOS MySQL schema
  - Domain enum classes
  - Eloquent relationships and hierarchy scopes
  - Transactional identifier generator
  - Baseline seed data and schema tests
affects: [phase-2-registration, phase-3-customers, phase-4-products, phase-5-fees-reports]
tech-stack:
  added: []
  patterns:
    - PHP backed enums with `options()` helpers
    - `visibleTo(User $user)` query scopes for hierarchy-sensitive models
    - `identifier_sequences` row locking for public business IDs
key-files:
  created:
    - app/Enums/UserRole.php
    - app/Services/IdentifierGenerator.php
    - database/migrations/2026_06_06_000002_create_pios_foundation_tables.php
    - app/Models/Partner.php
    - app/Models/BusinessOwner.php
    - app/Models/Customer.php
  modified:
    - app/Models/User.php
    - database/seeders/DatabaseSeeder.php
key-decisions:
  - "User role/status are enum-cast fields on users."
  - "Generated public IDs are separate unique columns backed by identifier_sequences."
  - "Hierarchy access starts at Eloquent scopes, not only Filament navigation."
patterns-established:
  - "Domain models use PHP attributes for fillable fields and local enum casts."
  - "Ownership-sensitive models expose visibleTo(User $user)."
requirements-completed: [AUTH-04, FND-01, FND-02, FND-03, FND-04]
duration: 20min
completed: 2026-06-06
---

# Phase 1: Foundation, Roles, and Data Model Summary

**Core PIOS schema, enum model, hierarchy scopes, and generated identifier service for Laravel + Filament + MySQL**

## Performance

- **Duration:** 20 min
- **Started:** 2026-06-06T00:00:00+08:00
- **Completed:** 2026-06-06T00:20:00+08:00
- **Tasks:** 4
- **Files modified:** 36

## Accomplishments

- Added core v1 tables for partners, business owners, customers, products, selections, proposals, fees, payments, orders, setup records, identifier sequences, and audit logs.
- Added PHP backed enums for roles, statuses, customer categories, product classifications, proposals, fees, payments, and orders.
- Updated `User` with Filament panel access, role/status casts, profile relationships, and role helpers.
- Added Eloquent models and `visibleTo(User $user)` scopes for hierarchy-sensitive records.
- Added `IdentifierGenerator` with transactional row locking and baseline seed data.

## Task Commits

1. **Plan 01-01: Foundation schema** - `edb3182` (feat)

## Files Created/Modified

- `app/Enums/*` - Shared enum values and Filament-friendly option helpers.
- `database/migrations/2026_06_06_000001_add_pios_access_fields_to_users_table.php` - Adds role/status fields to users.
- `database/migrations/2026_06_06_000002_create_pios_foundation_tables.php` - Creates the PIOS domain schema.
- `app/Models/*` - Domain models, casts, relationships, and hierarchy scopes.
- `app/Services/IdentifierGenerator.php` - Transactional public ID generator.
- `database/seeders/DatabaseSeeder.php` - Seeds sequences, setup records, and admin users.
- `tests/Feature/FoundationSchemaTest.php` - Schema, cast, and scope coverage.
- `tests/Unit/IdentifierGeneratorTest.php` - Identifier uniqueness coverage.

## Decisions Made

- Used a first-party enum and policy foundation instead of adding a third-party RBAC package in Phase 1.
- Created full foundational tables now so later phases can focus on workflows and resources.
- Kept sensitive fields structured for duplicate checks and report filters.

## Deviations from Plan

None - plan executed exactly as written.

---

**Total deviations:** 0 auto-fixed.  
**Impact on plan:** No scope change.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Verification

- `find app/Enums app/Models app/Services database/migrations -name '*.php' -print0 | xargs -0 -n1 php -l` passed.
- `php artisan migrate:fresh --seed` passed.
- `php artisan test --filter=IdentifierGeneratorTest` passed.
- `php artisan test --filter=FoundationSchemaTest` passed.

## Self-Check: PASSED

## Next Phase Readiness

Phase 1 Plan 02 can build Filament resources, policies, and audit helpers on top of the schema and models created here.

---
*Phase: 01-foundation-roles-and-data-model*
*Completed: 2026-06-06*
