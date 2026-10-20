---
status: complete
completed: 2026-06-07
description: Fix root redirect to login instead of loading loop
---

# Quick Task 260607-jsu Summary

## Completed

- Changed `/` from redirecting to the business panel dashboard route to redirecting directly to the Business Owner Filament login route.
- Updated the landing page test to assert `/business/login`.
- Cleared Laravel/Filament caches after the route change.

## Verification

- `php artisan test tests/Feature/ExampleTest.php` passed: 3 tests, 11 assertions.
- `curl -I http://pios-my.test/` returns `302 Found` with `Location: http://pios-my.test/business/login`.
- `curl -I -L --max-redirs 4 http://pios-my.test/` reaches `200 OK` on the login page.
- `vendor/bin/pint --test` passed.
- `php artisan optimize:clear` passed.
- `git diff --check` passed.
- `php artisan test` passed: 67 tests, 456 assertions.
