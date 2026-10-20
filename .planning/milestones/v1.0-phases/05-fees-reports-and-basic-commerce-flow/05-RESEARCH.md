# Phase 5: Research Notes

## Existing Domain Support

- `orders` stores Business Owner, Partner, Customer, generated Order No, status, subtotal, total, and ordered_at.
- `order_items` stores Product, product snapshot name, quantity, unit price, and line total.
- `fees` stores Partner, Business Owner, Customer, fee type, amount, due date, and status.
- `payments` stores optional Fee or Order link, payment type, amount, status, reference number, paid_at, and recorder.
- Product selections already enforce max-five and active product selection for a Business Owner.

## Implementation Notes

- Order create/update should not trust submitted `partner_id`, `subtotal`, `total`, `product_name`, `unit_price`, or `line_total`.
- Order create/update must derive `partner_id` from the resolved Business Owner and customer ownership.
- Repeater data can be validated and persisted manually in page hooks, which gives predictable server-side calculations.
- Payments need a `Payment::scopeVisibleTo(User $user)` helper so resource queries and reports can use one visibility path.
- Report filtering logic should live in a small service so tests can verify filter behavior without depending on a specific Blade layout.

## Test Strategy

- Focused commerce tests:
  - Business Owner order creation forces own Business Owner and own customer.
  - Ineligible Amway product for Non-LOS customer is blocked.
  - Unselected products are blocked.
  - Order totals and item snapshots are calculated server-side.
  - Partner order access is read-only.
- Focused fee/payment tests:
  - Admin creates Business Owner monthly fee.
  - Admin records fee/order payment with manual verification fields and recorder.
  - Partner/Business Owner visibility is read-only.
- Focused report tests:
  - Report service applies hierarchy scopes.
  - Report service applies date, Partner, Business Owner, customer category, product classification/type, territory, and status filters where data exists.
  - Report page routes are registered.

## Risks

- Filament repeater state can be awkward in tests; keep page hooks defensive and test with Livewire form state at the same shape used by the repeater.
- Orders can reference products that become inactive later; eligibility should apply at create/update time.
- Payment can link to a fee or an order; validation should require exactly one target.
