---
status: complete
created: 2026-06-07
description: Fix root redirect to login instead of loading loop
---

# Quick Task 260607-jsu: Root Login Redirect

## Scope

Make the application root route redirect directly to the Business Owner Filament login page instead of first redirecting through the business panel dashboard.

## Tasks

1. [x] Inspect current root redirect behavior on Herd.
2. [x] Change `/` to redirect to `filament.business.auth.login`.
3. [x] Update the root redirect regression test.
4. [x] Verify Herd returns a direct `/business/login` redirect.
5. [x] Run focused and full test coverage.
