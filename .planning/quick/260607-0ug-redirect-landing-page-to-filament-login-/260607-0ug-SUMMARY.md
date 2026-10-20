---
status: complete
completed: 2026-06-07
description: Redirect landing page to Filament login and brand login page with side image and Pak Hitam logo
---

# Quick Task 260607-0ug Summary

## Completed

- Redirected `/` to the Filament login route.
- Added `PiosLoginPlugin` to register the custom login page through the Filament panel plugin API.
- Added a custom Filament login page and Blade view with a split layout, side branding image panel, PIOS wordmark, and Pak ITAM logo.
- Extracted the Pak ITAM logo from `/Users/habib/Downloads/PIOS PRODUCT FEATURES V1.0.pdf` into `public/brand/pak-itam-logo.png`.
- Added tests for the landing-page redirect and branded login HTML.

## Verification

- `php artisan test tests/Feature/ExampleTest.php tests/Feature/OperationalReportsTest.php` passed: 6 tests, 32 assertions.
- `php artisan test` passed: 56 tests, 387 assertions.
- `vendor/bin/pint --test` passed.
- `npm run build` passed and emitted the updated Filament theme asset.
- `php artisan optimize:clear` passed.
- Herd returns `302 Found` from `http://pios-my.test/` to `http://pios-my.test/admin/login`.
- Herd serves `http://pios-my.test/brand/pak-itam-logo.png` with `200 OK`, and the Filament login HTML includes `pios-login-shell`, `brand/pios-wordmark.png`, and `brand/pak-itam-logo.png`.
