# Phase 4: Product Catalog, Selection, and Proposals - Context

**Gathered:** 2026-06-06
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 4 adds product catalog management and the Business Owner add-product workflow. Sys Admin and PIOS Owner users manage master catalog products. Business Owner users can view active catalog products, select up to five active products for their own account, and submit proposed products with prices, media, area, and description. Product proposals start pending and can be reviewed by admin users.

This phase stops before PIOS Mart checkout, customer product purchase screens, fee/payment workflows, product reports, WhatsApp promotion, and promotion-response management.

</domain>

<decisions>
## Implementation Decisions

### Product Catalog
- Sys Admin and PIOS Owner users can create and edit master catalog products.
- Partners and Business Owners can view active catalog products only.
- Product No is generated automatically using `IdentifierGenerator`.
- Product classification uses Amway or Non-Amway.
- Product status uses draft, active, inactive, and archived.
- Product media uses Filament file uploads to Laravel's `public` disk.

### Product Selection
- Business Owner users select products only for their own account.
- Admin users can manage selections for any Business Owner.
- Partners can view downstream selections but cannot create or edit selections.
- Product selections can only target active catalog products.
- Enforce a maximum of five non-inactive selections per Business Owner.
- Enforce one selection row per Business Owner and Product.

### Product Proposals
- Business Owner users can submit proposed products for their own account.
- Admin users can submit or manage proposals for any Business Owner.
- Proposed products start as `pending`.
- Business Owner users can edit their own pending proposals only.
- Admin users can edit and review all proposals.
- Proposal media uses optional image/video uploads to Laravel's `public` disk.

### Phase Boundary
- Implement Product, Business Owner Product Selection, and Product Proposal Filament resources with policies, generated IDs, media fields, validation, audit logging, and tests.
- Defer product reports, product sale/checkout eligibility, and promotion workflows to later phases.

### the agent's Discretion
The agent may choose exact Filament resource class paths, field ordering, and test structure based on the installed FilamentPHP 5 conventions, provided the accepted catalog, selection, proposal, ownership, media, identifier, max-five, and approval requirements are preserved.

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets
- Phase 1 created `Product`, `BusinessOwnerProductSelection`, and `ProductProposal` models.
- Phase 1 created product, selection, and proposal schema with product identifier, media JSON/path, pricing, statuses, reviewer fields, and unique selection index.
- Phase 1 created `ProductClassification`, `ProductStatus`, `ProductSelectionStatus`, and `ProductProposalStatus` enums.
- Phase 1 registered policies for Product, BusinessOwnerProductSelection, and ProductProposal.
- Phase 2 and Phase 3 established Filament resource patterns with page hooks for generated IDs, ownership forcing, validation, and audit logs.

### Established Patterns
- Resource queries apply hierarchy-aware scopes for owned downstream records.
- Page hooks resolve Business Owner ownership from the authenticated user for Business Owner users.
- Admin users can select hierarchy owner records explicitly.
- Tests use Livewire page tests against Filament resource pages with `RefreshDatabase`.

### Integration Points
- Later commerce phases will use active catalog products, Business Owner selections, and `Customer::canBuyProduct(Product $product)`.
- Proposed product approval may become a catalog product later, but catalog conversion is outside this phase.
- Product media fields should store relative filesystem paths, not binary data in the database.

</code_context>

<specifics>
## Specific Ideas

Use the supplied Business Owner Add Product mockup as the logical form shape: available product list with selection checkboxes plus a proposed-product section. Implement it through maintainable Filament resources rather than a custom pixel-perfect screen in this phase.

</specifics>

<deferred>
## Deferred Ideas

- Product reports are deferred to Phase 5.
- Sale-flow product eligibility and customer checkout are deferred to Phase 5.
- WhatsApp promotion and promotion responses are v2/deferred.
- Proposed-product conversion into a catalog product is deferred until approval workflow details are confirmed.

</deferred>
