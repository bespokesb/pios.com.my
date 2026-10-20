---
status: complete
completed: 2026-06-07
description: Add FilamentPHP impersonate plugin for super admin
---

# Quick Task 260607-ir7 Summary

## Completed

- Installed `stechstudio/filament-impersonate` v5.5.0.
- Added `User::canImpersonate()` so only active `sys_admin` users can initiate impersonation.
- Added `User::canBeImpersonated()` so active non-`sys_admin` users can be impersonated.
- Added the plugin action to the admin Users table and Edit User header actions.
- Added role-aware redirects: Business Owners go to the business panel dashboard, other eligible roles go to the admin panel dashboard.
- Added tests covering model authorization, Filament table action visibility, table action execution, and edit-page action execution.

## Verification

- `php artisan test tests/Feature/FilamentAccessTest.php` passed: 13 tests, 69 assertions.
- `vendor/bin/pint` passed.
- `php artisan test` passed: 67 tests, 456 assertions.
- `git diff --check` passed.
