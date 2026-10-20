# Phase 1: Foundation, Roles, and Data Model - Research

## Research Complete

### Stack Findings

- The project is already a Laravel 13 application with FilamentPHP 5 installed and a default `/admin` panel configured in `App\Providers\Filament\AdminPanelProvider`.
- Laravel models currently use PHP attributes for fillable and hidden fields, so new models should follow that pattern where it stays readable.
- Filament resources are auto-discovered from `app/Filament/Resources`.
- MySQL is configured in `.env` and `.env.example`; migrations can rely on MySQL-compatible column types and indexes.

### Implementation Approach

- Use PHP backed enums for roles and status/type fields so Filament select options and model casts share one source of truth.
- Extend the default `users` table with `role` and `status` instead of introducing a package-level RBAC dependency in Phase 1.
- Create core PIOS domain tables now, including tables needed by later phases, while deferring full workflow forms and actions to their assigned phases.
- Use an `identifier_sequences` table plus an `IdentifierGenerator` service to generate public business identifiers separately from primary keys.
- Enforce hierarchy access with policy methods and `visibleTo()` model scopes, then use those scopes from Filament resources and later workflow queries.
- Add an internal `audit_logs` table and helper service so later phases can log sensitive changes without choosing an external audit package.

### Risks and Mitigations

- **Role leakage through Filament navigation**: hide resources by policy and role-aware resource `canAccess()` checks, not only labels.
- **Generated ID collisions**: generate inside a database transaction using row locks on `identifier_sequences`; also enforce unique indexes on public ID columns.
- **Scope overreach**: create tables and base admin resources now, but keep registration, customer, product selection, fee, report, and commerce workflows in later phases.
- **Sensitive fields**: use structured columns for v1 to keep filters and duplicate checks simple; encryption can be added later behind model casts if compliance requires it.

### Verification Strategy

- Run migrations from scratch against the configured MySQL database.
- Run PHPUnit tests that assert enum casts, identifier uniqueness, hierarchy scopes, and policy access.
- Run Filament smoke checks for admin login availability and route registration.
- Run Pint and Vite build after implementation.

