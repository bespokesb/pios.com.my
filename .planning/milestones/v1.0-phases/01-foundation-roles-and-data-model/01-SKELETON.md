# Phase 1 Walking Skeleton

## Purpose

Prove that the Laravel + Filament + MySQL stack can support the core PIOS domain with one authenticated admin surface, one real database-backed resource path, and server-side access boundaries.

## Skeleton Slice

1. **Database**: migrations create role/status fields, identifier sequences, and foundational PIOS domain tables.
2. **Model Layer**: Eloquent models expose relationships and `visibleTo(User $user)` hierarchy scopes.
3. **Service Layer**: `IdentifierGenerator` creates public business identifiers with collision checks.
4. **Filament UI**: `/admin` login is available and Sys Admin users can manage foundational setup records.
5. **Policy Layer**: non-admin roles cannot access records outside their hierarchy.
6. **Verification**: tests prove login access, scopes, policies, and generated IDs.

## Done When

- `php artisan migrate:fresh --seed` completes.
- A seeded Sys Admin user can access `/admin/login`.
- Foundational resources exist for users, territories, partner tiers, product types, fee settings, and audit logs.
- `php artisan test`, `vendor/bin/pint --test`, and `npm run build` pass.

