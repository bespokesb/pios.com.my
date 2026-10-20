# Phase 5: Fees, Reports, and Basic Commerce Flow - Context

**Gathered:** 2026-06-06
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 5 completes the v1 operating surface by adding manual fees, payments, internal order entry as the PIOS Mart equivalent, and role-aware report pages. Business Owner users can create orders only for their own customers and only with active products selected for their account that the selected customer is eligible to buy. Admin users can manage any order, fee, and payment. Partner users can view downstream activity but cannot create orders, fees, or payments.

This phase also performs milestone closeout after verification: GSD phase completion, milestone audit/complete/cleanup where available, and final status reporting.

</domain>

<decisions>
## Implementation Decisions

### Basic Commerce
- Build an internal Filament Orders resource as the v1 PIOS Mart equivalent.
- Keep order line items inline in the Order form.
- Generate `order_no` automatically with `IdentifierGenerator`.
- Recalculate subtotal and total server-side from order items.
- Order item product choices are limited by the resolved Business Owner and Customer:
  - product is active,
  - product is selected by the Business Owner,
  - product passes `Customer::canBuyProduct(Product $product)`.
- Business Owner users can sell only to their own customers.
- Admin users can manage orders for any Business Owner.
- Partners can view downstream orders but cannot create or edit orders.

### Fees and Payments
- Build manual Fees and Payments Filament resources.
- Fee records support Business Owner monthly fees and existing fee types.
- Payment verification uses manual status, reference number, and paid-at fields only.
- No payment gateway fields or integration in v1.
- Admin users create/update fees and payments.
- Partners and Business Owners have read-only visibility through hierarchy scopes.

### Reports
- Build Filament report pages with filterable tables and summary stats.
- PIOS Owner reports include Business Owner list, Customer/VIP Customer list, fee payment list, and order/activity list.
- Partner reports include downstream Business Owner, customer, fee, payment, and order activity.
- Report filters include date range, Partner, Business Owner, customer category, product classification/type, territory, and status where applicable.
- CSV exports are deferred for v1 unless already provided by existing Filament table features without additional work.

### Milestone Closeout
- After Phase 5 verification passes, run GSD phase completion and milestone closeout commands where available.
- Keep the known v2 traceability warning as non-blocking unless GSD requires manual repair.

### the agent's Discretion
The agent may choose exact Filament resource class paths, report page layout, and test structure based on the installed FilamentPHP 5 conventions, provided accepted commerce, fee/payment, report filtering, hierarchy, eligibility, and closeout requirements are preserved.

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets
- Phase 1 created `Fee`, `Payment`, `Order`, and `OrderItem` schema and models.
- Phase 1 created `FeeStatus`, `FeeType`, `PaymentStatus`, and `OrderStatus` enums.
- Phase 1 created `Order::visibleTo()` and `Fee::visibleTo()` scopes.
- Phase 3 added `Customer::canBuyProduct(Product $product)`.
- Phase 4 added active catalog products and Business Owner product selections.
- `IdentifierGenerator` supports the `order` key.
- `AuditLogger` can log order, fee, and payment changes.

### Established Patterns
- Filament resources use plural folders with `Schemas`, `Tables`, and `Pages` subdirectories.
- Create/edit page hooks resolve owner IDs, validate hierarchy, generate IDs, calculate derived fields, and log audit events.
- Business Owner users are forced to their linked Business Owner profile server-side.
- Admin users choose hierarchy owner records explicitly.
- Partner users are read-only for downstream operational records.

### Integration Points
- Order item eligibility must join Customer category rules from Phase 3 with selected product records from Phase 4.
- Payments may link to either fees or orders.
- Reports must respect the same hierarchy scopes as resources.

</code_context>

<specifics>
## Specific Ideas

Use an internal admin-style order form rather than a public storefront. This keeps v1 useful for operations while avoiding the larger public customer journey and payment gateway scope.

</specifics>

<deferred>
## Deferred Ideas

- Public storefront/customer self-service is deferred.
- Payment gateway integration is deferred.
- CSV exports are deferred.
- Inventory, delivery, refunds, and order tracking are deferred.
- WhatsApp promotion and promotion responses remain v2/deferred.

</deferred>
