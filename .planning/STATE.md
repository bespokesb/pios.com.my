---
gsd_state_version: 1.0
milestone: v1.3
milestone_name: BO Order Management & Fulfilment
status: shipped
stopped_at: Phases 16–21 implemented, tested (267 green) and deployed (2026-07-15)
last_updated: "2026-07-15T00:00:00.000Z"
last_activity: 2026-07-15
progress:
  total_phases: 6
  completed_phases: 6
  total_plans: 6
  completed_plans: 6
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-06-29)

**Core value:** Business Owners can manage their own customers and products while PIOS enforces hierarchy ownership correctly. (Amway/Non-Amway eligibility was removed in DP-10; product Category replaces it.)
**Current focus:** v1.3 planning — Phase 16 (Order Lifecycle) next.

## Current Position

Milestone: v1.3 — BO Order Management & Fulfilment
Phase: 16 (not started)
Plan: Not started
Status: Roadmap + requirements drafted from the Jira DP backlog; ready to plan Phase 16.
Last activity: 2026-07-14

> Note: The DP-3..DP-14 batch (client feedback tickets) shipped ad-hoc from Jira on 2026-07-14 — outside the GSD phase flow — and is deployed + In Review on the DP board. v1.3 is the first milestone to run the remaining DP backlog through GSD phases.

## Milestone v1.3 Phase Map

| Phase | Name | Requirements | Jira | Depends on |
|-------|------|--------------|------|------------|
| 16 | Order Lifecycle, Statuses & Tracking | ORD-01, ORD-04 | DP-16 | existing Order model |
| 17 | Invoicing & Receipts | ORD-02, ORD-03 | DP-16 | Phase 16 |
| 18 | BO Order Dashboard | ORD-05 | DP-17 | Phase 16 |
| 19 | BO Sales Reporting | RPT-01, RPT-02 | DP-18 | Phase 16 |
| 20 | Vendor Register | VEN-01 | DP-19 | existing Vendor model |
| 21 | CRM Polish | CRMP-01, CRMP-02 | DP-9, DP-15 | Customer resource |

Phase 16 (order lifecycle) is the prerequisite for 17/18/19 (all read order status/date). Phases 20 and 21 are independent and parallelizable. Scope decisions (operator, 2026-07-14): payment is **documents-only with a manual mark-paid** (no gateway); customer tags become an **admin-managed list**; dashboard + reports are **BO-panel, own-orders scoped**. Natural cut line: ship 16 + 17 (order/invoice core), defer the rest.

## Performance Metrics

**Velocity:**

- Total plans completed: 17 (10 in v1.0, 7 in v1.1)
- Average duration: n/a
- Total execution time: 0.0 hours

**By Phase (v1.2):**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 10 | 0/? | n/a | n/a |
| 11 | 0/? | n/a | n/a |
| 12 | 0/? | n/a | n/a |
| 13 | 0/? | n/a | n/a |
| 14 | 0/? | n/a | n/a |
| 15 | 0/? | n/a | n/a |

**Recent Trend:**

- Last 5 plans: v1.1 phases 7–9 (all complete)
- Trend: n/a

| Phase 06 P01 | 2m | 3 tasks | 10 files |
| Phase 07 P01 | 3m | 3 tasks | 4 files |
| Phase 07 P02 | 4m | 3 tasks | 4 files |
| Phase 08 P01 | 2m | 2 tasks | 3 files |
| Phase 08 P02 | 4min | 2 tasks | 7 files |
| Phase 09 P01 | 3m | 2 tasks | 14 files |
| Phase 09 P02 | 6m | 2 tasks | 5 files |
| Phase 10 P01 | 3m | 3 tasks | 11 files |
| Phase 11 P01 | 12 | 3 tasks | 9 files |
| Phase 12 P01 | 18min | 3 tasks | 5 files |
| Phase 13 P01 | 2min | 2 tasks | 6 files |
| Phase 14 P01 | 3m | 3 tasks | 7 files |
| Phase 15 P01 | 2 | 3 tasks | 6 files |

## Accumulated Context

### Decisions

Decisions are logged in PROJECT.md Key Decisions table.
Recent decisions affecting current work:

- v1.2 roadmap: 6 phases (10–15), zero new runtime packages — every feature maps to a Filament 5.6 built-in plus the `App\Support\Phone` helper and two additive tables (`customer_notes`, `customer_follow_ups`).
- v1.2 roadmap: Phase 10 (CRM data layer) is isolated as the highest-risk slice so the BO-ownership invariant on net-new child tables gets an independent security/verifier pass before any UI consumes it.
- v1.2 roadmap: notes and follow-ups are TWO separate tables (not polymorphic) — different shapes/lifecycles, cleaner indexing/widgets; each carries `customer_id` + denormalized `business_owner_id` + `created_by`, its own `scopeVisibleTo`, and a registered policy.
- v1.2 roadmap: customer detail is a Filament `ViewRecord` (never a hand-rolled `findOrFail`) so it resolves through the scoped resource query; mirrors the shipped AuditLog ViewRecord pattern.
- v1.2 roadmap: applied coarse-granularity compression by merging the product card grid + simplified BO form into one phase (Phase 14); Share (Phase 15) kept separate as its own security-reviewed slice.
- v1.2 roadmap: no public product page in v1.2 — Share is a text-only `wa.me` action; the tokenized public landing page is deferred to v2 (SHARE-PUB-01).
- Initialization: Use Laravel + FilamentPHP 5 + MySQL.
- Initialization: Treat Business Owner as the v1 owner of customers, selections, proposals, fees, and reports.
- Initialization: Defer WhatsApp Business API, payment gateway automation, direct Amway integration, and AI prospecting.
- [Phase 09]: cost/vendor/margin isolated in the SHARED ProductResource via ->visible(isAdmin()) on each field/column (absent + non-dehydrated for BO+Partner), NOT panel config — proven by VendorSourcingTest.
- [Phase 10]: Phase 10: notes/follow-ups are two separate BO-scoped child tables with denormalized business_owner_id + own scopeVisibleTo + registered policy; overdue computed in Asia/Kuala_Lumpur; App\Support\Phone is the single MY wa.me/tel normalizer.
- [Phase 11]: ViewCustomer is a Filament ViewRecord inheriting the resource visibleTo scope; cross-BO id 404s server-side (no hand-rolled findOrFail)
- [Phase 11]: Relation managers stamp business_owner_id from getOwnerRecord() + created_by from auth server-side; override isReadOnly()=>false to allow create/edit/delete on the ViewRecord page
- [Phase 12]: category re-exposed to BO only via narrow setStage action; PII form Section guard untouched
- [Phase 12]: settableStages allowlist [Lead,Prospect,Customer]; VIP/Non-LOS rejected server-side (need LOS verification)
- [Phase 13]: Contact-action visibility uses Phone normalizer result (!== null), not filled(hp_no), so garbage numbers are also hidden
- [Phase 14]: EditProduct strips business_owner_id + created_by for EVERY role — owner FK immutable post-create, blocking cross-BO product reassignment
- [Phase 14]: BO product form simplified to six essentials; cost/vendor/commission/classification field-gated AND server-stripped; classification defaults Non-Amway server-side
- [Phase 15]: Share uses recipient-less wa.me/?text= picker; no public product page (SHARE-PUB-01 deferred to v2 — guest route would bypass visibleTo and leak cost/margin)

### Pending Todos

None yet.

### Blockers/Concerns

Non-negotiable correctness invariants baked into v1.2 success criteria (every new surface is a fresh place BO-ownership/PII can leak):

- Child records (notes/follow-ups) must each have their own `scopeVisibleTo` + registered policy + denormalized `business_owner_id`, stamped server-side — a direct query (reminder count) must never see another BO's rows, and another BO's record id must 403/404, not just hide.
- The customer detail page must be a `ViewRecord` (resolves through the scoped query); never `Customer::findOrFail`; every detail widget scoped too.
- Re-exposing category/status to the BO must NOT re-open cost/vendor/IC/address/`classification` — re-expose fields individually, keep field-level guards, `unset()` admin keys server-side; BO must never write `classification` (drives Amway eligibility).
- Share/promote must stay a client-side `wa.me` text link — no public/unauthenticated route, no cost/margin in the message.
- WhatsApp/tel links must go through one tested `App\Support\Phone` normalizer (`60…` / `+60…`) with `rawurlencode`d text; no PII in server logs.
- Image uploads must be validated server-side: `->image()` + `->maxSize()` + SVG blocked; deploy must run `storage:link` (rsync deploy does not recreate the symlink).

Open policy questions to resolve during phase planning (from research):

- LOS/VIP verification (open PRD question): whether a BO may self-assert VIP or it needs admin/LOS verification. Default keeps VIP/Non-LOS out of BO-settable stages. (Phase 12.)
- Duplicate-detection boundary: `EditCustomer` currently checks HP/IC uniqueness within `partner_id`, not `business_owner_id` — confirm intended boundary vs Rule 1 before segment/merge features. (Phase 11.)
- Product status semantics: `ProductStatus` has both `Inactive` and `Archived`; default surfaces Draft/Active/Archived to BO, treats `Inactive` as a hidden synonym of Draft. (Phase 14.)
- `status = Active` self-publish vs approval (PRD §8.5): if approval is required, cap BO-settable status to Draft/Archived and route publish through the proposal flow. (Phase 14.)
- Pre-filled WhatsApp greeting + bilingual (Malay/English) labels: keep templates editable; don't hardcode English. (Phases 13 & 15.)

## Deferred Items

| Category | Item | Status | Deferred At |
|----------|------|--------|-------------|
| Promotion | Public tokenized product landing page (SHARE-PUB-01) | Deferred to v2 | v1.2 roadmap |
| Hierarchy | BO→Partner upgrade; Partner membership / Product Partner status (amount TBC) | Deferred to Phase 2 | v1.2 scope |
| Sourcing | GPS / geolocation element (SRC-05) | Deferred to v2 | v1.1 roadmap |
| Quota | Hard quota enforcement + miss consequence (QUOTA-05) | Deferred to v2 | v1.1 roadmap |
| Fees | BO suspension/gating on unpaid fees; first-month proration | Out of scope (v1.1) | v1.1 roadmap |
| Integration | Direct Amway integration | Deferred | Initialization |
| Integration | Payment gateway automation | Deferred | Initialization |
| Integration | WhatsApp Business API automation | Deferred | Initialization |
| Feature | AI prospecting | Deferred | Initialization |
| Operations | Inventory, delivery, refunds, customer self-service | Deferred | Initialization |

## Quick Tasks Completed

| Date | Task | Summary |
|------|------|---------|
| 2026-06-07 | Fix root redirect to business login | Changed `/` to redirect directly to `/business/login`, avoiding the extra `/business` dashboard hop, and verified Herd reaches the login page cleanly. |
| 2026-06-07 | Add super admin user impersonation | Installed the Filament impersonate plugin, limited impersonation to active `sys_admin` users, added Users table/edit-page actions, and covered role-aware redirects with tests. |
| 2026-06-07 | Add Business landing redirect and Malay i18n switcher | Redirected `/` to `/business`, added session-backed `en`/`ms` locale support, localized the Filament login screens, and added Filament language switchers. |
| 2026-06-07 | Add Business Owner Filament panel | Added a separate `/business` Filament panel with its own login screen, Business Owner-facing resources, operational reports, and panel-specific access rules. |
| 2026-06-07 | Add branded Filament login redirect | Redirected `/` to `/admin/login`, added a local Filament login plugin, and built a split PIOS/Pak ITAM login page with branded side imagery. |
| 2026-06-07 | Apply PIOS logo blue branding | Extracted the PIOS PDF wordmark into a public logo asset, sampled the logo blue as `#215F9A`, and applied it to the Filament panel palette and reports theme. |
| 2026-06-07 | Implement Business Transaction promotions | Added `/admin/business-transactions` for Manual/Auto promotions with scheduling, selected product, special offer fields, eligible customer recipients, hierarchy enforcement, and tests. |
| 2026-06-07 | Apply better PIOS reports theme | Added a custom Filament admin theme, changed panel palette, and rebuilt `/admin/operational-reports` with branded KPI, finance, filter, and table components. |
| 2026-06-06 | Improve Filament operational reports UI | Rebuilt `/admin/operational-reports` into a dashboard-style Filament page with filters, KPI cards, richer tables, reset action, and focused Livewire coverage. |

## Session Continuity

Last session: 2026-06-30T20:01:19.473Z
Stopped at: Completed 15-01-PLAN.md
Resume file: None

## Operator Next Steps

- Plan the first v1.3 phase with /gsd:plan-phase 16 (Order Lifecycle, Statuses & Tracking).
- After phases land, run jira-sync (or move DP-16..DP-19/DP-9/DP-15 as each phase completes).
- DP-3..DP-14 are already In Review on the DP board and deployed — awaiting operator review/Done.
