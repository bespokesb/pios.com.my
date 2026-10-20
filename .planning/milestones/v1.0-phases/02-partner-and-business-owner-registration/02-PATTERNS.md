# Phase 2 Pattern Map

## Existing Patterns

- Filament resources are generated under plural folders, e.g. `app/Filament/Resources/Users/UserResource.php`.
- Resource schemas are configured through `Schema $schema` classes using Filament form components.
- Tables are configured through dedicated table classes.
- `UserResource` uses `Hash::make()` in form dehydration for password fields.
- `AppServiceProvider` explicitly registers model policies.

## Files to Create or Modify

- `app/Filament/Resources/Partners/*`
- `app/Filament/Resources/BusinessOwners/*`
- `app/Services/RegistrationAccountCreator.php`
- `app/Models/Partner.php`
- `app/Models/BusinessOwner.php`
- `app/Policies/PartnerPolicy.php`
- `app/Policies/BusinessOwnerPolicy.php`
- `tests/Feature/PartnerRegistrationTest.php`
- `tests/Feature/BusinessOwnerRegistrationTest.php`

