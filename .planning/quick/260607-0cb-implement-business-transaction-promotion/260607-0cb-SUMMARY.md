---
status: complete
completed: 2026-06-07
description: Implement business transaction promotion mockup with manual and auto modes
---

# Quick Task 260607-0cb Summary

## Completed

- Added `promotions` and `promotion_customers` tables for Business Transaction promotion runs.
- Added promotion mode/status enums, models, relationships, and policies.
- Added a Filament `Business Txn` resource at `/admin/business-transactions` with Manual/Auto promotion mode, schedule fields, selected product, promotion media, special offer/T&C, max customers, and eligible customer selection.
- Added server-side enforcement for hierarchy ownership, selected products, customer ownership, Amway/Non-Amway eligibility, max customer count, and auto schedule requirements.
- Added focused tests for manual run, auto schedule, blocking invalid product/customer selections, policy behavior, and route registration.

## Verification

- `php artisan test tests/Feature/PromotionBusinessTransactionTest.php` passed: 5 tests, 47 assertions.
- `php artisan test` passed: 55 tests, 382 assertions.
- `vendor/bin/pint --test` passed.
- `npm run build` passed.
- `php artisan migrate` applied `2026_06_07_000001_create_promotions_tables`.
- `php artisan route:list --path=admin/business-transactions` shows index/create/edit routes.
