# Phase 4: Research Notes

## Local Framework Patterns

- FilamentPHP 5 generated resources use `Schemas`, `Tables`, and `Pages` subfolders.
- Existing create/edit pages use `mutateFormDataBeforeCreate`, `mutateFormDataBeforeSave`, `handleRecordCreation`, `handleRecordUpdate`, and `afterCreate`/`afterSave` hooks.
- Existing resources use navigation groups from the configured panel: Register, Business Owner, Products, Fees, Reports, and System.

## Existing Domain Support

- `products` already supports catalog fields, classification, JSON media assets, JSON pricing/promotional content, commission, status, and created_by.
- `business_owner_product_selections` already supports unique Business Owner + Product rows and selected_at timestamps.
- `product_proposals` already supports Business Owner ownership, pricing fields, media path, approval status, reviewer, and reviewed_at.
- `IdentifierGenerator` already supports key `product`.
- `AuditLogger` can log product, selection, and proposal changes without schema changes.

## Validation and Test Strategy

- Product create should generate `product_no`; the form should not accept a user-supplied Product No.
- Product visibility for non-admin users should be constrained to active catalog products.
- Selection create/edit must resolve Business Owner server-side and only allow active products.
- Selection create/edit should block duplicate Business Owner + Product rows before database unique constraints throw.
- Selection create/edit should enforce max five non-inactive selections per Business Owner.
- Proposal create/edit must force Business Owner ownership for Business Owner users and default status to pending.
- Proposal update policy should allow Business Owner edits only while a proposal remains pending.

## Risks

- Existing `ProductStatus` does not yet include `archived`, and existing `ProductProposalStatus` does not yet include `pending`; both need enum updates before forms can expose accepted values.
- Filament file-upload tests are not necessary for full filesystem upload internals in this phase; resource form presence and persistence fields are enough for focused feature coverage.
- Admin approval behavior should set reviewer fields when an approval status is reviewed, but conversion into master catalog should remain out of scope.
