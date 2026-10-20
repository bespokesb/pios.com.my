# Phase 2: Partner and Business Owner Registration - Research

## Research Complete

### Stack Findings

- FilamentPHP 5 resource generation creates resources in plural subdirectories, with separate `Schemas`, `Tables`, and `Pages` classes.
- `CreateRecord` supports `mutateFormDataBeforeCreate()`, `handleRecordCreation()`, and `afterCreate()` hooks for generated identifiers and audit logging.
- `EditRecord` supports save hooks that can compare original values, create optional account access, and log update events.
- Filament resource authorization delegates to Laravel policies, and `getEloquentQuery()` can be overridden to apply hierarchy scopes.

### Implementation Approach

- Generate `PartnerResource` and `BusinessOwnerResource` with Filament, then patch schemas, tables, and pages.
- Add reusable account-creation helpers to a small service to avoid duplicating user creation logic in both resources.
- Generate `partner_no` and `business_owner_no` in create-page data mutation before the model is created.
- For Partner users creating Business Owners, force `partner_id` from `auth()->user()->partner`.
- Block duplicate `ic_no` and `hp_no` within the relevant scope:
  - Partners: global duplicate check for Partner registration.
  - Business Owners: duplicate check within the selected Partner.
- Log create/update events through `AuditLogger` after successful writes.

### Verification Strategy

- Add feature tests that create Partners and Business Owners through resource page hooks or Livewire pages.
- Assert generated IDs are present and unique.
- Assert Partner users cannot attach Business Owners to another Partner.
- Assert optional account creation creates linked `users` with the correct role.
- Assert duplicate IC/HP checks raise validation errors.
- Run full `php artisan test`, `vendor/bin/pint --test`, `npm run build`, and `php artisan migrate:fresh --seed`.

