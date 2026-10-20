# Phase 4: Patterns

## Resource Structure

- Use `php artisan make:filament-resource ... --generate --force --no-interaction` for Product, BusinessOwnerProductSelection, and ProductProposal resources.
- Keep generated Filament v5 folder conventions and patch behavior-specific code after generation.
- Product resource navigation group: `Products`.
- Selection and proposal resource navigation group: `Business Owner`.

## Product Catalog Pattern

- Product resource query returns all products to admin users.
- Non-admin users see active catalog products only.
- Product create generates `product_no` with `IdentifierGenerator`.
- Product form stores media uploads in `media_assets` on the `public` disk.
- Product create/update logs audit events.

## Selection Pattern

- Business Owner users are forced to their own `business_owner_id`.
- Admin users must choose a Business Owner.
- Partner users can view downstream selections through `visibleTo` but cannot create or edit.
- Product choices are limited to active products.
- Duplicate Business Owner + Product pairs are blocked with validation.
- Max five non-inactive selections are enforced per Business Owner.
- `selected_at` is populated when a selection is created or reactivated.

## Proposal Pattern

- Business Owner users are forced to their own `business_owner_id`.
- Admin users may choose a Business Owner.
- Business Owner-created proposals always start pending.
- Business Owner users can edit own pending proposals only.
- Admin users can review all proposals and reviewer fields are set when status moves to under review, approved, or rejected.
- Proposal media uploads are optional and stored on the `public` disk.

## Audit Pattern

- Log create and update events for products, selections, and proposals.
- Store old and new values for updates.
