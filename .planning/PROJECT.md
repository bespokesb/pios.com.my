# PIOS

## What This Is

PIOS (Pak ITAM Online Supermarket) is a partner-led online supermarket and back-office platform for premium, organic, exclusive, and unique daily-use products. PIOS Owner users register Partners, Partners register Business Owners, and Business Owners register contacts/customers, select or propose products, manage fees, and report on their activity through a Laravel + FilamentPHP application.

The v1 product is not a generic storefront. Its core behavior is controlled by hierarchy ownership and product eligibility: each user only acts on records within their permitted hierarchy, and customers only see or buy product types they are allowed to purchase.

## Core Value

Business Owners can manage their own customers and products while PIOS enforces hierarchy ownership and Amway/Non-Amway eligibility correctly.

## Current Milestone: v1.2 BO-facing CRM & Product Listing

**Goal:** Turn the Business Owner panel (`/business`) into a genuine CRM + product-listing tool for the platform's paying customer, while keeping everything strictly BO-scoped (a BO only ever sees/manages their own customers and products).

**Target features:**
- CRM: customer detail view with activity notes + follow-up reminders; search, filters & segments (by stage/category, name/phone, counts); one-tap WhatsApp/call from the customer's phone. (No Kanban board; stage stays a filter dimension — light category-set so segments are meaningful.)
- Product Listing: richer product cards (image/price/description/status grid); categories & status management (product type + active/draft/archived); share/promote a product (link or pre-filled WhatsApp message); simpler guided BO create form (essentials only, hide admin fields).
- Deferred to Phase 2: BO→Partner upgrade; Partner membership / Product Partner status (amount TBC).

**Status: v1.2 COMPLETE & deployed** (2026-07-01) — 6 phases (10–15), 10/10 requirements, full suite 153 green, passed a final adversarial BO-ownership audit (verdict SHIP). Shipped: customer detail ViewRecord + notes/follow-ups, stage segments + gated stage-change, one-tap WhatsApp/call, product card grid + categories/status + simplified BO form, cost-safe share/promote. The ultracode red-team caught + fixed a HIGH product-reassignment vector pre-ship.

**Prior milestone v1.1 (shipped & deployed):** partner quota (advisory), RM10/RM5 BO fees + monthly scheduler, admin vendor sourcing, BO-owned products. See MILESTONES.md.

## Requirements

### Validated

(None yet - ship to validate)

### Active

- [ ] Users can authenticate through Filament and access only modules permitted by their role.
- [ ] PIOS Owner/System users can register Partners with generated unique Partner No values.
- [ ] Partners can register Business Owners with generated unique Business Owner No values and login access.
- [ ] Business Owners can register contacts, prospects, customers, and VIP customers with generated unique Customer No values.
- [ ] Customer ownership is enforced by Business Owner, with parent Partner visibility controlled by permissions.
- [ ] Products are classified as Amway or Non-Amway and include the required catalog fields.
- [ ] Business Owners can select available products and submit proposed products.
- [ ] Product visibility and checkout enforce customer category eligibility and ownership rules.
- [ ] Monthly Business Owner fee tracking and payment reporting exist.
- [ ] PIOS Owner, Partner, Business Owner, Fee, Product, and Customer reports exist with useful filters.
- [ ] Sys Admin users can manage roles, permissions, territories, tiers, product types, and fee settings.
- [ ] Critical changes to sensitive records are auditable.

### Out of Scope

- Native mobile app - web/Filament app first.
- Full visual redesign beyond the supplied low-fidelity UI flow - implement usable Filament workflows first.
- Direct Amway integration - ABO/DD details are stored manually until integration requirements are confirmed.
- Automated payment gateway integration - v1 tracks fees/payments; gateway choice is unresolved.
- Inventory, delivery, refunds, and customer self-service - not defined in current PRD.
- AI prospecting - acknowledged for future release, not required for v1.
- WhatsApp Business API automation - promote-product workflow is deferred until integration method is confirmed.

## Context

The repo already contains a Laravel scaffold with FilamentPHP 5 and MySQL configured:

- Laravel 13.14
- FilamentPHP 5.6
- PHP 8.4
- MySQL database `pios_my`
- Vite and Tailwind CSS 4

Important product documents:

- `PIOS_PRD.md` - source of truth for product scope and open questions.
- `erd.md` - implementation-oriented entity relationship diagram.
- `CLAUDE.md` - coding-agent guidance and domain invariants.

The current UI flow from the May 2026 mockups is:

```text
PIOS Owner / System -> Partner -> Business Owner -> Customer / VIP Customer
```

Business Owner is the primary seller role for v1 unless the user later confirms Partner-led selling should remain active.

## Constraints

- **Tech stack**: Laravel + FilamentPHP 5 + MySQL - chosen by the user and already scaffolded.
- **Authorization**: Hierarchy ownership must be enforced server-side through Laravel policies and query scopes, not only hidden in Filament UI.
- **Eligibility**: Amway products are only available to VIP Customer (LOS); Non-LOS customers can only buy Non-Amway products.
- **Data privacy**: IC/KP numbers, HP numbers, emails, addresses, ABO numbers, and DD details are sensitive.
- **Identifiers**: Partner No, Business Owner No, Customer No, VIP Customer No, Product No, and Order No are generated business identifiers, separate from database primary keys.
- **Unresolved rules**: Partner tier thresholds, LOS verification, VIP RM2 fee, Business Owner fee policy, product proposal approval, and WhatsApp integration method remain open questions.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Use Laravel + FilamentPHP 5 + MySQL | PIOS is mostly authenticated CRUD, workflow, reporting, and role-specific back-office UI. | - Pending |
| Treat Business Owner as the v1 customer/product owner | Updated UI mockups place contact/customer/product flows under Business Owner login. | - Pending |
| Store business identifiers separately from primary keys | UI requires generated Partner No, Business Owner No, Customer No, and Product No values. | - Pending |
| Build v1 as a Filament back-office MVP | Fastest path to usable forms, tables, filters, dashboards, and policy-gated modules. | - Pending |
| Keep WhatsApp automation deferred | The PRD does not confirm deep link vs Business API integration. | - Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `$gsd-transition`):
1. Requirements invalidated? -> Move to Out of Scope with reason
2. Requirements validated? -> Move to Validated with phase reference
3. New requirements emerged? -> Add to Active
4. Decisions to log? -> Add to Key Decisions
5. "What This Is" still accurate? -> Update if drifted

**After each milestone** (via `$gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check - still the right priority?
3. Audit Out of Scope - reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-07-01 — milestone v1.2 complete & deployed*

