# Phase 3: Patterns

## Resource Structure

- Use `php artisan make:filament-resource Customer --generate --force --no-interaction` to align generated files with installed FilamentPHP 5 conventions.
- Keep form configuration in `app/Filament/Resources/Customers/Schemas/CustomerForm.php`.
- Keep table configuration in `app/Filament/Resources/Customers/Tables/CustomersTable.php`.
- Keep behavior-heavy logic in create/edit page hooks, following the existing Partner and Business Owner resources.

## Ownership Pattern

- `CustomerResource::getEloquentQuery()` applies `visibleTo(auth()->user())`.
- Create/edit hooks resolve ownership:
  - Business Owner user: force `business_owner_id` from linked profile and derive `partner_id`.
  - Admin user: require selected `business_owner_id` and derive `partner_id`.
  - Partner user: policy denies create/update.
- Do not trust submitted `partner_id`.

## Identifier Pattern

- Generate `customer_no` on create using `IdentifierGenerator` key `customer`.
- Generate `vip_customer_no` with key `vip_customer` only when category is `CustomerCategory::VipCustomer`.
- On edit, generate missing `vip_customer_no` only when a record becomes VIP Customer.

## Duplicate Pattern

- Ignore blank HP No and IC/KP No.
- Block duplicates for `hp_no` and `kp_no` within the resolved `partner_id`.
- On edit, exclude the current record.
- Surface validation errors on `data.hp_no` and `data.kp_no`.

## Eligibility Pattern

- `Customer::canBuyProduct(Product $product)` returns true for Non-Amway products.
- For Amway products, it returns true only when the customer category is VIP Customer (LOS).

## Audit Pattern

- Log create and update events with `AuditLogger`.
- Store old and new values for updates, matching existing edit page behavior.
