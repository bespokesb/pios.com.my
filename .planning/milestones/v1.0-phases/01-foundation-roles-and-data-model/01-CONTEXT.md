# Phase 1: Foundation, Roles, and Data Model - Context

**Gathered:** 2026-06-06
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 1 establishes the Laravel + Filament + MySQL foundation required by every later PIOS workflow: role-aware authentication, the core domain schema, generated business identifiers, Eloquent relationships, server-side hierarchy access controls, audit logging foundations, and basic Filament navigation scaffolding. This phase stops before implementing the full Partner, Business Owner, Customer, Product, Fee, Report, and Commerce workflows, which are assigned to later phases.

</domain>

<decisions>
## Implementation Decisions

### Roles, Hierarchy, and Access
- Use four v1 roles: `sys_admin`, `pios_owner`, `partner`, and `business_owner`.
- Enforce hierarchy boundaries with Laravel policies and model query scopes, not Filament-only visibility.
- Keep login credentials on `users`, with domain profile records in `partners` and `business_owners`.
- Use role-specific Filament navigation groups for Register, Business Owner, Products, Fees, Reports, and System areas.

### Schema, Identifiers, and Sensitive Data
- Store generated public IDs separately from primary keys: `partner_no`, `business_owner_no`, `customer_no`, `vip_customer_no`, `product_no`, and `order_no`.
- Use a central identifier generator service with prefixes and collision checks.
- Store IC/KP, HP, ABO, DD, and address fields as structured columns for v1; add encryption later only if policy requires it.
- Use PHP enums for roles, statuses, customer categories, product types, fee statuses, and proposal statuses.

### Audit, Admin Foundation, and Phase Boundary
- Add a simple internal `audit_logs` table and helper now for sensitive create/update/delete events.
- Add manageable base tables and enum-backed fields for territories, partner tiers, product types, fee settings, and roles/permissions-ready access.
- Create foundational Filament resources only where needed for login, navigation, and admin scaffolding; full workflow resources land in later phases.
- Stop Phase 1 after schema, relationships, identifiers, role-aware access foundation, seed users, and smoke tests pass.

### the agent's Discretion
The agent may choose conservative Laravel and Filament implementation details when they preserve the accepted role model, hierarchy boundaries, generated identifier model, and later-phase boundaries.

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets
- Laravel 13 scaffold is already installed with FilamentPHP 5.
- `app/Providers/Filament/AdminPanelProvider.php` defines the default `/admin` panel with Filament login.
- `app/Models/User.php` is the only application model currently present.
- `database/migrations/0001_01_01_000000_create_users_table.php` contains the default users, password reset tokens, and sessions tables.
- `erd.md` contains an implementation-oriented draft of the PIOS entity relationships.

### Established Patterns
- The codebase uses PHP attributes such as `#[Fillable]` and `#[Hidden]` on models.
- Laravel migrations and seeders follow the default framework layout.
- Filament resources are auto-discovered from `app/Filament/Resources`.
- Tests currently use the default Laravel/PHPUnit structure.

### Integration Points
- Domain migrations should extend the existing default user schema rather than replacing it.
- User role and profile relationships should be added to `App\Models\User`.
- Filament panel gating belongs in the panel provider and policies/resources.
- Later phases will attach Partner, Business Owner, Customer, Product, Fee, Report, and Commerce resources to the schema created here.

</code_context>

<specifics>
## Specific Ideas

Use Laravel + FilamentPHP 5 + MySQL as requested. Preserve the current PIOS mockup hierarchy: PIOS Owner/System registers Partners, Partners register Business Owners, and Business Owners own customer/product workflows.

</specifics>

<deferred>
## Deferred Ideas

- Partner and Business Owner registration forms are deferred to Phase 2.
- Customer registration and duplicate ownership checks are deferred to Phase 3.
- Product selection and proposal workflows are deferred to Phase 4.
- Fee, payment, reporting, and commerce workflows are deferred to Phase 5.
- Payment gateway automation, WhatsApp automation, direct Amway integration, inventory, delivery, refunds, customer self-service, and AI prospecting remain out of v1 scope.

</deferred>
