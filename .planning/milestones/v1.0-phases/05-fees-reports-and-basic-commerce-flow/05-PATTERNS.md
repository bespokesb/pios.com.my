# Phase 5: Patterns

## Resource Structure

- Use generated Filament resources for Orders, Fees, and Payments, then patch forms/tables/page hooks.
- Orders resource navigation group: `Business Owner`.
- Fees and Payments resource navigation group: `Fees`.
- Report pages navigation group: `Reports`.

## Order Pattern

- Business Owner users are forced to their own `business_owner_id`.
- Admin users must choose a Business Owner.
- Customer options are constrained to the resolved Business Owner.
- Partner ID is always derived from the resolved Business Owner.
- Inline order item rows accept Product and quantity only; product name, unit price, and line total are calculated server-side.
- Eligible products are active, selected by the resolved Business Owner, and allowed by `Customer::canBuyProduct()`.
- Generate `order_no` on create.
- Set `ordered_at` when status moves beyond draft and timestamp is blank.
- Log create/update events.

## Fee and Payment Pattern

- Admin users create and update fees and payments.
- Partner and Business Owner users view only records in hierarchy scope.
- Fees derive Partner from selected Business Owner or Customer when possible.
- Payments require exactly one of Fee or Order.
- Payment `recorded_by` is set from the authenticated user.
- Log create/update events.

## Report Pattern

- Build a reusable report query service with explicit filter inputs.
- Each report query starts from the model's hierarchy-aware visible scope.
- Report pages are thin UI shells around the service.
- Summary stats include counts and totals for Business Owners, Customers/VIP Customers, Fees, Payments, and Orders.

## Closeout Pattern

- Run focused tests first, then full test suite, Pint, frontend build, and fresh seeded migration.
- Commit implementation, mark Phase 5 complete, then run milestone audit/complete/cleanup commands if available.
