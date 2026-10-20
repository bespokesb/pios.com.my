# Phase 3: Customer Ownership and Eligibility Base - Context

**Gathered:** 2026-06-06
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 3 adds the Business Owner customer management workflow on top of the Partner and Business Owner registration foundation. Business Owner users can create contacts, prospects, customers, VIP Customer (LOS), and Non-LOS Customer records under their own Business Owner profile. Admin users can create and manage customer records for any Business Owner. Partner users can view downstream customer records for reporting scope, but cannot create or edit customer records in this phase.

This phase stops before product selection, product proposal, fee, payment, order, and dashboard/report implementation. It provides the ownership, category, duplicate check, generated ID, and eligibility base needed by those later phases.

</domain>

<decisions>
## Implementation Decisions

### Ownership and Access
- Business Owner users create customer records only under their own Business Owner profile.
- Admin users can create customer records for any Business Owner.
- Customer records store both `business_owner_id` and parent `partner_id` for reporting scope and query policy enforcement.
- Partner users can view downstream customer records but cannot create or edit them.

### Customer Categories and IDs
- Use existing category enum values: lead, prospect, customer, VIP Customer (LOS), and Non-LOS Customer.
- Generate `customer_no` automatically for every created customer record.
- Generate `vip_customer_no` only for VIP Customer (LOS) records; keep it null for lead, prospect, customer, and Non-LOS Customer records.
- Keep `status` separate from category using `RecordStatus`.

### Duplicate and Eligibility Rules
- Block duplicate HP No and IC/KP No within the same Partner reporting scope before create or update.
- Show ABO No, DD Name, and DD HP No for VIP Customer (LOS) and Non-LOS Customer records.
- Require ABO No for VIP Customer (LOS) records.
- Add a reusable `Customer::canBuyProduct(Product $product)` method for later product and checkout phases.
- VIP Customer (LOS) can buy Amway and Non-Amway products; Non-LOS Customer cannot buy Amway products.

### Phase Boundary
- Implement the Customer Filament resource, generated identifiers, duplicate checks, ownership policies, eligibility method, audit logging, and tests.
- Defer product selection UI and checkout behavior to later phases.

### the agent's Discretion
The agent may choose exact Filament resource class paths, field ordering, and test structure based on the installed FilamentPHP 5 conventions, provided accepted authorization, identifier, duplicate blocking, category, LOS field, audit, and eligibility requirements are preserved.

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets
- Phase 1 created `Customer`, `BusinessOwner`, `Partner`, `Product`, and `User` models with ownership relationships.
- Phase 1 created `CustomerCategory`, `RecordStatus`, and `ProductClassification` enums.
- Phase 1 created `IdentifierGenerator` sequence defaults for `customer` and `vip_customer`.
- Phase 1 created `AuditLogger` and registered `CustomerPolicy`.
- Phase 2 established Filament resource structure under plural folders, schema classes, table classes, and create/edit page hooks.

### Established Patterns
- Resource queries call `visibleTo(auth()->user())` for hierarchy-limited records.
- Forms use Filament sections that map to mockup groupings while staying practical for admin UI.
- Create/edit page hooks generate business identifiers, force hierarchy ownership, perform duplicate checks, and log audit events.
- Tests use Livewire page tests against Filament resource pages with `RefreshDatabase`.

### Integration Points
- Customer resource should be discovered by the existing admin panel resource discovery.
- Customer records must derive `partner_id` from the selected or forced Business Owner.
- Later product phases will call `Customer::canBuyProduct(Product $product)` to enforce Amway vs Non-Amway eligibility.

</code_context>

<specifics>
## Specific Ideas

Use the supplied Business Owner "Add Contact" mockup as the logical form shape: generated IDs, owning Business Owner, contact details, category/status, and LOS details. The implementation should remain a maintainable Filament resource instead of a pixel-perfect custom page.

</specifics>

<deferred>
## Deferred Ideas

- Product selection and proposed product workflows are deferred to Phase 4.
- Fee, payment, reporting, and commerce workflows are deferred to Phase 5.
- VIP Customer RM2 fee behavior remains deferred until fee policy is confirmed.

</deferred>
