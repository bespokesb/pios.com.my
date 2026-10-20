# Phase 2: Partner and Business Owner Registration - Context

**Gathered:** 2026-06-06
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 2 adds the first role-specific registration workflows on top of the Phase 1 foundation. PIOS Owner/System users can register Partners with generated Partner No values, while Partner users can register Business Owners under their own Partner record with generated Business Owner No values. Optional linked login accounts can be created during registration or enabled later from the edit workflow.

</domain>

<decisions>
## Implementation Decisions

### Partner Registration
- Only `sys_admin` and `pios_owner` users can create Partner records in v1.
- Partner No is generated automatically on create using `IdentifierGenerator`.
- Partner login account access is optional during creation and can be enabled later.
- Partner tier is stored as `partner_tier_id` and remains admin-editable until tier calculation rules are confirmed.

### Business Owner Registration
- Partners can create Business Owners under their own Partner record; admins can create Business Owners for any Partner.
- Business Owner No is generated automatically on create using `IdentifierGenerator`.
- Business Owner login account access is optional during creation and can be enabled later.
- Partner users are forced to their own `partner_id`; admins choose the parent Partner.

### Form Behavior and Audit
- Use Filament sections matching the mockup groupings: applicant, spouse, contact/address, business details, generated IDs, and account access.
- Block duplicate IC/HP values within the relevant registration scope before creating or updating records.
- Log Partner and Business Owner create/update events through `AuditLogger`.
- Stop after Partner/Business Owner resources, generated IDs, optional account access, policy tests, route checks, and verification. Customer registration remains Phase 3.

### the agent's Discretion
The agent may choose exact Filament resource class paths, field ordering, and test structure based on the installed FilamentPHP 5 conventions, provided the accepted authorization, identifier, account access, duplicate blocking, and audit requirements are preserved.

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets
- Phase 1 created `Partner`, `BusinessOwner`, `PartnerTier`, `Territory`, and `User` models.
- Phase 1 created `IdentifierGenerator` and seeded identifier sequence rows for `partner` and `business_owner`.
- Phase 1 created `AuditLogger` and registered `PartnerPolicy` and `BusinessOwnerPolicy`.
- Filament v5 resources are generated under plural folders such as `app/Filament/Resources/Users/UserResource.php`.

### Established Patterns
- Filament schemas live under resource-specific `Schemas` classes.
- Tables live under resource-specific `Tables` classes.
- Create and edit page hooks can handle generated fields, optional account creation, and audit logging.
- Setup resources use enum `options()` helpers and `System` navigation grouping.

### Integration Points
- Partner and Business Owner resources should be discovered by the existing admin panel resource discovery.
- Resource queries should use model `visibleTo(User $user)` scopes for non-admin users.
- Optional user account creation should create `User` records with `UserRole::Partner` or `UserRole::BusinessOwner` and link them through `user_id`.
- Later customer, product, fee, and reporting phases depend on accurate `partner_id`, `business_owner_id`, and linked user account relationships.

</code_context>

<specifics>
## Specific Ideas

Follow the supplied UI mockups for logical form sections, but implement as practical Filament admin resources rather than pixel-perfect custom pages.

</specifics>

<deferred>
## Deferred Ideas

- Customer/contact/prospect/VIP registration is deferred to Phase 3.
- Product selection and proposals are deferred to Phase 4.
- Fee, payment, reporting, and commerce workflows are deferred to Phase 5.
- Partner tier auto-calculation remains deferred until thresholds are confirmed.

</deferred>

