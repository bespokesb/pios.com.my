# Milestones

## v1.3 BO Order Management & Fulfilment (Planned: 2026-07-14)

**Phases planned:** 6 phases (16–21), mapped to Jira DP-16/17/18/19/9/15.

**Scope:**

- Order status lifecycle (Submitted → Paid → Packed → Shipped → Completed / Cancelled) + tracking number (Phase 16 / DP-16)
- Printable invoice on submit + receipt on manual mark-paid — no payment gateway (Phase 17 / DP-16)
- BO order dashboard: counts by status + revenue, own-orders scoped (Phase 18 / DP-17)
- BO daily/weekly sales reports in `/business` (Phase 19 / DP-18)
- Vendor register for product sourcing (Phase 20 / DP-19; stock already shipped as DP-13)
- CRM polish: customer list sort by name + admin-managed tag list (Phase 21 / DP-9, DP-15)

**Scope decisions (operator, 2026-07-14):** documents-only payment (manual mark-paid); admin-managed customer tags; dashboard/reports BO-panel and own-orders scoped.

**Status:** Roadmap + requirements drafted from the Jira DP backlog; ready to plan Phase 16. (The separate DP-3..DP-14 client-feedback batch shipped ad-hoc and is deployed + In Review.)

---

## v1.0 PIOS v1.0 (Shipped: 2026-06-06)

**Phases completed:** 5 phases, 10 plans, 14 tasks

**Key accomplishments:**

- Core PIOS schema, enum model, hierarchy scopes, and generated identifier service for Laravel + Filament + MySQL
- PIOS Filament admin foundation with role-gated system resources, explicit policies, and audit logging
- Partner registration resource with generated Partner No, optional login account linkage, duplicate blocking, and audit logging
- Business Owner registration resource with Partner-scoped ownership, generated Business Owner No, optional login account linkage, and duplicate blocking
- Completed:
- Completed:
- Completed:
- Completed:
- Completed:
- Completed:

---

## v1.2 BO-facing CRM & Product Listing (Shipped: 2026-07-01)

**Phases completed:** 6 phases (10–15), 6 plans

**Key accomplishments:**

- CRM data layer: `customer_notes` + `customer_follow_ups` (own scope/policy/denormalized owner) + `App\Support\Phone` helper
- Customer detail `ViewRecord` + infolist (PII-gated) + notes/follow-ups relation managers
- Stage segments (scoped tab counts) + gated inline stage-change (Lead/Prospect/Customer only)
- One-tap WhatsApp + call actions
- BO product card grid + category/status filters + simplified BO create form
- Cost-safe share/promote (`wa.me` text, own-products-only)
- Hardened: product owner FK immutable on edit (HIGH red-team fix); BO-without-profile sees no products
- 153 tests green; passed final adversarial BO-ownership audit (SHIP)

---
