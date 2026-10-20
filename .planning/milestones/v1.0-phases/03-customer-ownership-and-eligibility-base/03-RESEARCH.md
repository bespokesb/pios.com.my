# Phase 3: Research Notes

## Local Framework Patterns

- FilamentPHP 5 generated resources live under plural resource folders, with `Schemas/*Form.php`, `Tables/*Table.php`, and `Pages/*` classes.
- The existing Partner and Business Owner resources use page hooks for generated IDs, ownership forcing, duplicate checks, optional account behavior, and audit logging.
- The existing panel provider discovers resources automatically from `app/Filament/Resources`.

## Existing Domain Support

- `customers` already has the fields required for Phase 3: owner IDs, generated IDs, contact fields, LOS fields, category, status, and creator.
- `IdentifierGenerator` already supports `customer` and `vip_customer` keys.
- `CustomerPolicy` already allows admin and Business Owner create/update/delete, Partner read-only visibility, and visible-to checks.
- `CustomerCategory` already contains lead, prospect, customer, VIP Customer, and Non-LOS Customer values.
- `ProductClassification` already separates Amway and Non-Amway products.

## Validation and Test Strategy

- Duplicate checks should live in create/edit page hooks so they can evaluate the resolved owner and parent Partner before persistence.
- Business Owner user ownership must be forced server-side from `auth()->user()->businessOwner`, even if submitted form data includes another Business Owner.
- Admin-created customer records must derive parent Partner from the selected Business Owner.
- Tests should cover generated IDs, forced ownership, admin creation, Partner read-only access, partner-scoped duplicate blocking, VIP-specific ABO/VIP No behavior, and product eligibility.

## Risks

- Filament relationship validation can reject submitted IDs before server-side ownership hooks run. If that appears, use explicit options and server-side ownership resolution, matching the Business Owner resource pattern.
- VIP number generation on edit must avoid regenerating an existing VIP number. It should only generate when category is VIP Customer and `vip_customer_no` is blank.
- Product eligibility should remain simple in this phase and avoid coupling to checkout implementation that has not been built.
