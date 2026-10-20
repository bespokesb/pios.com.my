# Roadmap: PIOS

## Overview

The v1 roadmap turns the existing Laravel + Filament scaffold into a usable PIOS back-office MVP. It starts with the data model, roles, policies, and Filament panel structure, then builds the hierarchy from Partner to Business Owner, adds customer ownership, product catalog/selection/proposal workflows, and finishes with basic commerce eligibility, fee tracking, and reports.

The v1.1 milestone (Partner Quota, Fees & Product Sourcing) is additive integration work on the shipped v1.0 app. It adds the three business rules v1.0 left unbuilt — a partner monthly customer quota, a concrete Business Owner fee schedule (RM10 setup + RM5 recurring), and a merchant/vendor product-sourcing layer with admin-only cost — using existing infrastructure (scheduler, `pios-queue`, `FeeSetting`, `scopeVisibleTo`, `AuditLogger`) and zero new runtime packages.

The v1.2 milestone (BO-facing CRM & Product Listing) turns the `/business` panel from a bare list-and-edit screen into a real lightweight personal CRM + seller product catalog: customer notes and follow-up reminders, a customer detail view, search/filters/segments with counts, one-tap WhatsApp/call, richer product cards with categories and status, a simpler guided BO product form, and a share/promote action. It adds zero new runtime packages — every feature maps to a Filament 5.6 built-in plus a small `App\Support\Phone` helper and two additive tables. The governing constraint is the BO-ownership invariant: every new surface is a fresh place scope can leak, so each routes through `visibleTo`, child tables get their own scope + policy + denormalized owner, the detail page is a `ViewRecord`, BO can never edit `classification`/cost/vendor, and Share stays a text-only `wa.me` action (no public page).

## Phases

**Phase Numbering:**

- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

### Milestone v1.0 (Shipped 2026-06-06)

- [x] **Phase 1: Foundation, Roles, and Data Model** - Establish the schema, role model, policies, generated IDs, audit hooks, and Filament navigation. (completed 2026-06-06)
- [x] **Phase 2: Partner and Business Owner Registration** - Build Partner registration and Partner-to-Business Owner onboarding. (completed 2026-06-06)
- [x] **Phase 3: Customer Ownership and Eligibility Base** - Build Business Owner customer management and category/ownership enforcement. (completed 2026-06-06)
- [x] **Phase 4: Product Catalog, Selection, and Proposals** - Build product CRUD, Business Owner product selections, and proposed products. (completed 2026-06-06)
- [x] **Phase 5: Fees, Reports, and Basic Commerce Flow** - Build fee tracking, report views, and product eligibility in the sale flow. (completed 2026-06-06)

### Milestone v1.1 (Partner Quota, Fees & Product Sourcing)

- [x] **Phase 6: Fee & Quota Correctness Foundation** - Lay the schema, enum, system audit user, and timezone scaffolding that make recurring fees and monthly quota counts safe. (completed 2026-06-20)
- [x] **Phase 7: Business Owner Fee Schedule** - Generate the one-time setup fee on approval and the idempotent recurring monthly fee, with HQ obligation reporting. (completed 2026-06-20)
- [x] **Phase 8: Partner Monthly Customer Quota** - Track each partner's monthly customer attainment toward a configurable target, shown in the partner panel and an HQ report. (completed 2026-06-20)
- [x] **Phase 9: Vendor / Product Sourcing** - Add vendor records and admin-only product cost/margin that never leak to partner or business panels. (completed 2026-06-20)

### Milestone v1.2 (BO-facing CRM & Product Listing)

- [x] **Phase 10: CRM Data Layer & Ownership Foundation** - Two BO-owned tables (notes, follow-ups) with their own scope + policy + denormalized owner, plus the shared `App\Support\Phone` helper. The highest-risk slice, built before any UI. (completed 2026-06-30)
- [x] **Phase 11: Customer Detail View** - A `ViewCustomer` ViewRecord + Infolist + the notes/follow-ups relation managers, resolving through the scoped query so no BO can open another BO's customer. (completed 2026-06-30)
- [x] **Phase 12: Segments & Gated Stage-Change** - Stage segments with per-stage counts + name/phone search, and an inline stage action limited to Lead/Prospect/Customer. (completed 2026-06-30)
- [x] **Phase 13: One-Tap WhatsApp & Call** - List-row and detail actions that build `wa.me`/`tel:` links via the Phone helper and open in a new tab. (completed 2026-06-30)
- [x] **Phase 14: Product Cards, Categories, Status & Simplified BO Form** - A `contentGrid()` card listing with category/status filters and a simplified BO create/edit form that never exposes cost/vendor/commission/classification. (completed 2026-06-30)
- [x] **Phase 15: Share / Promote** - A `wa.me` pre-filled-message action only; no public product page in v1.2. (completed 2026-06-30)

### Milestone v1.3 (BO Order Management & Fulfilment)

Turns the `/business` panel into an order-to-fulfilment tool for the Business Owner: an explicit order status lifecycle with a tracking number, printable invoice/receipt documents (no payment gateway — the BO marks orders paid manually), a BO order dashboard, daily/weekly sales reports, a vendor register, and two CRM polish items (customer list sort + an admin-managed tag vocabulary). Scope decisions (operator, 2026-07-14): **payment is documents-only with a manual mark-paid** (PRD payment deferral upheld); **customer tags become an admin-managed list** BOs pick from; **dashboard + reports live in `/business`, scoped to the BO's own orders**. Maps 1:1 to Jira DP-16, DP-17, DP-18, DP-19, DP-9, DP-15.

- [x] **Phase 16: Order Lifecycle, Statuses & Tracking** - An explicit order status workflow (Submitted → Paid → Packed → Shipped → Completed, plus Cancelled) with BO-driven transitions and a tracking-number field set at shipment. (DP-16 core) (completed 2026-07-15)
- [x] **Phase 17: Invoicing & Receipts** - A printable HTML invoice generated on submit and a receipt on manual mark-paid — no payment gateway, no PDF dependency. (DP-16) (completed 2026-07-15)
- [x] **Phase 18: BO Order Dashboard** - A `/business` OrderStatsOverview widget: order counts by status + revenue, scoped to the BO's own orders (LatestOrders provides the recent list). (DP-17) (completed 2026-07-15)
- [x] **Phase 19: BO Sales Reporting** - Daily and weekly sales reports in `/business`, scoped to the BO. (DP-18) (completed 2026-07-15)
- [x] **Phase 20: Vendor Register** - Vendor register audited + phone normalised (register already existed from v1.1; product stock shipped in DP-13). (DP-19) (completed 2026-07-15)
- [x] **Phase 21: CRM Polish** - Customer listing sortable by Name, and an admin-managed customer tag list BOs pick from. (DP-9, DP-15) (completed 2026-07-15)

## Phase Details

### Phase 1: Foundation, Roles, and Data Model

**Goal**: Establish the MySQL schema, role model, generated identifiers, audit base, policies, and Filament panel navigation needed by every later workflow.
**Mode:** mvp
**Depends on**: Nothing (first phase)
**Requirements**: [AUTH-01, AUTH-02, AUTH-03, AUTH-04, FND-01, FND-02, FND-03, FND-04, FND-05]
**Success Criteria** (what must be TRUE):

  1. User can log in and out through the Filament panel.
  2. Role-based navigation exists for PIOS Owner/System, Partner, and Business Owner contexts.
  3. Core migrations and Eloquent relationships exist for v1 entities.
  4. Generated business identifiers are represented separately from primary keys.
  5. Policies or query scopes prevent cross-hierarchy access at the server layer.

**Plans**: 2 plans
Plans:
**Wave 1**

- [x] 01-01: Schema, enums, generated IDs, and core relationships

**Wave 2** *(blocked on Wave 1 completion)*

- [x] 01-02: Filament panel structure, roles, policies, and audit base

### Phase 2: Partner and Business Owner Registration

**Goal**: Let PIOS Owner/System users register Partners and let Partners register Business Owners with generated identifiers and account access.
**Mode:** mvp
**Depends on**: Phase 1
**Requirements**: [PBO-01, PBO-02, PBO-03, PBO-04, PBO-05, PBO-06]
**Success Criteria** (what must be TRUE):

  1. PIOS Owner/System user can create a Partner from Filament.
  2. Partner No is generated and unique.
  3. Partner can create a Business Owner linked to that Partner.
  4. Business Owner No is generated and unique.
  5. Business Owner login access can be created or enabled.

**Plans**: 2 plans
Plans:
**Wave 1**

- [x] 02-01: Partner resource, forms, identifiers, and tier storage

**Wave 2** *(blocked on Wave 1 completion)*

- [x] 02-02: Business Owner resource, parent Partner link, and account access

### Phase 3: Customer Ownership and Eligibility Base

**Goal**: Let Business Owners register contacts, prospects, customers, and VIP/Non-LOS customer records while enforcing duplicate checks and ownership boundaries.
**Mode:** mvp
**Depends on**: Phase 2
**Requirements**: [CUST-01, CUST-02, CUST-03, CUST-04, CUST-05, CUST-06]
**Success Criteria** (what must be TRUE):

  1. Business Owner can create contact/prospect/customer records.
  2. Customer No is generated and unique.
  3. Customer records are tied to owning Business Owner and parent Partner reporting scope.
  4. Duplicate HP No and IC/KP No conflicts are detected before creation.
  5. Customer category supports lead, prospect, customer, VIP LOS, and Non-LOS.

**Plans**: 2 plans
Plans:
**Wave 1**

- [x] 03-01: Customer schema, category model, generated IDs, and duplicate checks

**Wave 2** *(blocked on Wave 1 completion)*

- [x] 03-02: Business Owner customer Filament resource and ownership policies

### Phase 4: Product Catalog, Selection, and Proposals

**Goal**: Let authorized users manage the product catalog while Business Owners select available products and submit proposed products.
**Mode:** mvp
**Depends on**: Phase 3
**Requirements**: [PROD-01, PROD-02, PROD-03, PROD-04, PROD-05, PROD-06]
**Success Criteria** (what must be TRUE):

  1. Authorized user can create and manage products.
  2. Product No is generated or validated as unique.
  3. Product type is Amway or Non-Amway.
  4. Business Owner can select available products for their account.
  5. Business Owner can submit a proposed product with prices, media, area, and description.

**Plans**: 2 plans
Plans:
**Wave 1**

- [x] 04-01: Product catalog resource, media fields, product type, and unique product numbers

**Wave 2** *(blocked on Wave 1 completion)*

- [x] 04-02: Business Owner product selections and product proposal workflow

### Phase 5: Fees, Reports, and Basic Commerce Flow

**Goal**: Add basic Business Owner fee tracking, payment reporting, report filters, and sale-flow eligibility checks so v1 can be operated.
**Mode:** mvp
**Depends on**: Phase 4
**Requirements**: [COM-01, COM-02, COM-03, COM-04, COM-05, COM-06, COM-07]
**Success Criteria** (what must be TRUE):

  1. Sale workflow only exposes eligible products for the selected customer.
  2. Sale workflow blocks sellers from selling to customers they do not own.
  3. Business Owner monthly fees and payment records can be recorded.
  4. PIOS Owner can view Business Owner, Customer/VIP Customer, and fee payment reports.
  5. Partner can view downstream Business Owner, customer, and fee activity reports.

**Plans**: 2 plans
Plans:
**Wave 1**

- [x] 05-01: Fee, payment, and basic commerce eligibility workflow

**Wave 2** *(blocked on Wave 1 completion)*

- [x] 05-02: Owner, Partner, Business Owner, and operational report views

### Phase 6: Fee & Quota Correctness Foundation

**Goal**: Put the shared schema, enum, system audit actor, and timezone scaffolding in place so the recurring fee and monthly quota features can be built without double-billing or off-by-one month bugs.
**Mode:** mvp
**Depends on**: Phase 5
**Requirements**: [FND-06]
**Success Criteria** (what must be TRUE):

  1. A dedicated system user exists and is recorded as the audit actor on system/scheduled-generated records.
  2. The `fees` table carries a `billing_period` column with a unique index on `(business_owner_id, fee_type, billing_period)`, so a duplicate period fee cannot be inserted.
  3. A `BusinessOwnerSetup` fee type exists alongside the recurring monthly type.
  4. Fee billing periods and quota month boundaries resolve in Asia/Kuala_Lumpur, not UTC.
  5. The `customers` table has a composite index supporting partner-by-month counts.

**Plans**: 1 plan
Plans:
**Wave 1**

- [x] 06-01: billing_period idempotency index, BusinessOwnerSetup fee type, pios-system audit user, and Asia/Kuala_Lumpur timezone

### Phase 7: Business Owner Fee Schedule

**Goal**: Charge each Business Owner a one-time setup fee at HQ approval and a recurring monthly fee thereafter, with amounts driven by Fee Settings and obligations visible to HQ — without ever double-billing.
**Mode:** mvp
**Depends on**: Phase 6
**Requirements**: [FEE-01, FEE-02, FEE-03, FEE-04]
**Success Criteria** (what must be TRUE):

  1. When HQ approves a Business Owner registration, exactly one RM10 setup-fee obligation is created and audited, and re-approval never creates a second one; the Business Owner can still operate with it pending.
  2. A recurring RM5 monthly fee is generated for each active Business Owner at most once per Business Owner per calendar month, even if the generator runs repeatedly, is re-run manually, or overlaps.
  3. Setup and monthly fee amounts and currency come from Fee Settings, so changing the Fee Setting changes future generated fees without code changes.
  4. HQ can view each Business Owner's fee obligations with paid / pending / overdue status.

**Plans**: 2 plans
Plans:
**Wave 1**

- [x] 07-01: FeeSetting resolver + seeded settings, and the one-time setup fee on BO approval (FEE-03, FEE-01)

**Wave 2** *(blocked on Wave 1 completion)*

- [x] 07-02: Idempotent monthly fee command + scheduler, and HQ obligations view with overdue filter (FEE-02, FEE-04)

### Phase 8: Partner Monthly Customer Quota

**Goal**: Show each partner how their downstream customer registrations this month track against a configurable target, give HQ an attainment report, and keep it advisory and reusable so enforcement can be switched on later.
**Mode:** mvp
**Depends on**: Phase 6
**Requirements**: [QUOTA-01, QUOTA-02, QUOTA-03, QUOTA-04]
**Success Criteria** (what must be TRUE):

  1. A partner's customers created in the current Malaysia-time calendar month, across all their downstream Business Owners, are counted toward a configurable target (default 100).
  2. A partner can see their current month's progress toward the target in the partner panel.
  3. HQ can view every partner's monthly quota attainment in a report.
  4. Customer creation is never blocked by quota in v1.1, and the count is provided by a reusable service so enforcement can be enabled later without rework.

**Plans**: 2 plans
Plans:
**Wave 1**

- [x] 08-01-PLAN.md — Reusable advisory PartnerQuotaService + configurable target (QUOTA-01, QUOTA-04)

**Wave 2** *(blocked on Wave 1 completion)*

- [x] 08-02-PLAN.md — Partner-panel quota widget + HQ attainment report (QUOTA-02, QUOTA-03)

**UI hint**: yes

### Phase 9: Vendor / Product Sourcing

**Goal**: Let HQ record vendors and an admin-only cost on each product, surface margin to HQ, and guarantee that cost, vendor, and margin never appear in the partner or business panels.
**Mode:** mvp
**Depends on**: Phase 6
**Requirements**: [SRC-01, SRC-02, SRC-03, SRC-04]
**Success Criteria** (what must be TRUE):

  1. HQ/admin can create and manage vendor records with name and contact details.
  2. A product can be linked to a vendor and carry an admin-only cost price.
  3. Product cost, vendor, and margin are visible only in the admin panel and never appear in the partner or business panels (enforced server-side and covered by an explicit column-authorization test).
  4. HQ can view product margin (customer price minus cost) in the catalog or a report.

**Plans**: 2 plans
Plans:
**Wave 1**

- [x] 09-01-PLAN.md — Vendor model + admin-only VendorResource/policy + products vendor_id/cost schema (SRC-01, SRC-02)

**Wave 2** *(blocked on Wave 1 completion)*

- [x] 09-02-PLAN.md — Admin-only cost/vendor/margin in shared ProductResource + column-authorization test (SRC-03, SRC-04)

**UI hint**: yes

### Phase 10: CRM Data Layer & Ownership Foundation

**Goal**: Stand up the BO-owned data foundation for customer notes and follow-up reminders — two separate tables, each strictly scoped to the owning Business Owner — plus the shared `App\Support\Phone` helper, so every later CRM screen inherits a leak-proof ownership boundary instead of inventing one. This is the highest-risk slice; ownership is baked in before any UI consumes it.
**Mode:** mvp
**Depends on**: Phase 9 (existing Customer model / scope conventions)
**Requirements**: [CRM-01, CRM-02]
**Success Criteria** (what must be TRUE):

  1. Notes and follow-ups live in two separate tables (`customer_notes`, `customer_follow_ups`), each carrying `customer_id`, a denormalized `business_owner_id`, and `created_by`, with `business_owner_id` and `created_by` stamped server-side (never from form input).
  2. A Business Owner can create a timestamped note and a follow-up (due date, note, done flag) against one of their own customers, and each record's owner is the creating Business Owner.
  3. Each child model has its own `scopeVisibleTo` plus a registered policy, so a direct query (e.g. a due/overdue reminder count) returns only the requesting Business Owner's rows, and fetching another Business Owner's note/follow-up id returns 403/404 — not merely hidden in the UI.
  4. A follow-up exposes a due/overdue state (status + due date) that can be queried for surfacing reminders.
  5. `App\Support\Phone` normalizes Malaysian numbers to `wa.me` form (`60…`, no `+`/leading `0`) and `tel:` form (`+60…`) for inputs like `012-345 6789`, `+6012…`, and `6012…`, and is unit-tested.

**Plans**: 1 plan
Plans:
**Wave 1**

- [x] 10-01-PLAN.md — CRM child tables (customer_notes, customer_follow_ups) + models/policies/scopes, registration, and the App\Support\Phone helper (CRM-01, CRM-02)

### Phase 11: Customer Detail View

**Goal**: Give the Business Owner the anchor screen the panel lacks today — a single customer profile showing the customer's info, their notes, and their follow-ups — built as a Filament `ViewRecord` so it resolves through the already-scoped resource query and can never load another Business Owner's PII.
**Mode:** mvp
**Depends on**: Phase 10
**Requirements**: [CRM-03]
**Success Criteria** (what must be TRUE):

  1. A Business Owner can open a customer detail view (a `ViewCustomer` ViewRecord + Infolist) reached from a row action, showing the customer's contact info, their notes, and their follow-ups.
  2. The detail page resolves through the scoped resource query, so editing the URL id for a customer the Business Owner does not own returns 403/404 (no hand-rolled `findOrFail`).
  3. Notes and follow-ups render via relation managers on the detail page (newest-first), with `business_owner_id`/`created_by` stamped server-side and creation/edits audited.
  4. LOS/identity PII entries (IC/KP, ABO, DD details) stay hidden from the Business Owner using the existing `isBusinessOwner()` visibility guard, while admins still see them.

**Plans**: TBD
**UI hint**: yes

### Phase 12: Segments & Gated Stage-Change

**Goal**: Let a Business Owner answer "how many prospects do I have?" and move a contact through stages — list segments by category with live counts, name/phone search, and an inline stage action constrained to the stages a Business Owner is allowed to set.
**Mode:** mvp
**Depends on**: Phase 11
**Requirements**: [CRM-04, CRM-05]
**Success Criteria** (what must be TRUE):

  1. A Business Owner's customer list is segmented by stage (Lead/Prospect/Customer/VIP) with an accurate per-stage count on each segment, computed with `withCount`-style aggregates (no per-row N+1).
  2. A Business Owner can filter/search the list by name or phone and the counts stay consistent with their own scoped customers only.
  3. A Business Owner can change a customer's stage between Lead, Prospect, and Customer via an inline action.
  4. The stage action cannot set VIP or Non-LOS (gated out of scope, as they depend on LOS verification), and re-exposing the category field does not re-expose cost, IC/KP, address, or other PII.

**Plans**: TBD
**UI hint**: yes

### Phase 13: One-Tap WhatsApp & Call

**Goal**: Give the Business Owner the primary channel for Malaysian direct-sales sellers — one-tap WhatsApp and phone-call actions on both the customer list rows and the detail page — built on the Phase 10 Phone helper with no server round-trip so customer PII never enters a server log.
**Mode:** mvp
**Depends on**: Phase 13 inputs are Phase 10 (Phone helper) and Phase 11 (detail surface); ordered after Phase 12.
**Requirements**: [CRM-06]
**Success Criteria** (what must be TRUE):

  1. From a customer's phone number, a Business Owner can start a WhatsApp chat in one tap (`wa.me/60…`) from both the list row and the detail page.
  2. A Business Owner can start a phone call in one tap (`tel:+60…`) from the same surfaces.
  3. Links are built through `App\Support\Phone` and work for `012-345 6789`, `+6012…`, and `6012…`; any pre-filled message text is `rawurlencode`d; links open in a new tab with `rel=noopener`.
  4. The WhatsApp/call actions appear only when the customer has a phone number, and no server route logs the number or message.

**Plans**: TBD
**UI hint**: yes

### Phase 14: Product Cards, Categories, Status & Simplified BO Form

**Goal**: Turn the Business Owner's product list into a visual seller catalog — an image/price/status card grid with category and status filters — and simplify the create/edit form to the essentials, all while keeping the v1.1 cost/vendor/margin isolation intact and never letting a Business Owner write `classification`.
**Mode:** mvp
**Depends on**: Phase 10 (reuses existing scoped ProductResource; independent of CRM views, so parallelizable with Phases 11–13)
**Requirements**: [PLST-01, PLST-02, PLST-03]
**Success Criteria** (what must be TRUE):

  1. A Business Owner sees their own products as a card/grid listing (Filament `contentGrid()`) showing image, price, and status, with relations eager-loaded so query count stays flat against 100+ products.
  2. A Business Owner can organize products by category (product type) and status (active/draft/archived) and filter the listing by both, using the existing curated `ProductType`/`ProductStatus` sets (no free-text taxonomy).
  3. A Business Owner's create/edit form is simplified to name, price, image, description, category, and status.
  4. The simplified form never exposes or persists cost, vendor, commission, or `classification`; fields are re-exposed individually (not by relaxing a whole Section), field-level guards stay, and a crafted Business Owner save payload cannot write those admin-only columns.
  5. Image uploads are validated server-side: `->image()` with a `->maxSize()` ceiling and SVG rejected.

**Plans**: TBD
**UI hint**: yes

### Phase 15: Share / Promote

**Goal**: Let a Business Owner promote one of their own products to a customer/prospect with a single tap — a pre-filled WhatsApp message (name, RM price, description) built through the Phone helper — kept deliberately as its own slice so the deferred public-page surface (an unauthenticated scope bypass) is never folded in by accident.
**Mode:** mvp
**Depends on**: Phase 14 (share action on the product grid) and Phase 10 (Phone helper)
**Requirements**: [PLST-04]
**Success Criteria** (what must be TRUE):

  1. A Business Owner can trigger a share action on one of their own products that opens WhatsApp with a pre-filled message containing the product name, RM price, and description.
  2. The message text is `rawurlencode`d and the action opens in a new tab via a client-side `wa.me` link, so no product or customer data transits a server log.
  3. No public/unauthenticated product page exists in v1.2 — share is text-only — and cost, vendor, and margin are never included in the shared message.

**Plans**: TBD
**UI hint**: yes

### Phase 16: Order Lifecycle, Statuses & Tracking

**Goal**: Give orders an explicit, BO-driven status workflow so a Business Owner can move an order from Submitted through to Completed, capturing a tracking number at shipment — the backbone every other v1.3 phase (invoices, dashboard, reports) reads from.
**Mode:** mvp
**Depends on**: Existing Order model + ManualOrderService + BO ownership scope
**Requirements**: [ORD-01, ORD-04]
**Success Criteria** (what must be TRUE):

  1. An order has an explicit status lifecycle — Submitted → Paid → Packed → Shipped → Completed, plus Cancelled — and the BO can transition it forward through allowed steps only (no illegal jumps), server-side enforced.
  2. Status transitions are scoped to the BO's own orders (`visibleTo`) and audited.
  3. A tracking number can be recorded on an order (surfaced at/after Shipped) and shown to the customer where the order is viewed.

**Plans**: TBD
**UI hint**: yes

### Phase 17: Invoicing & Receipts

**Goal**: Produce the paper trail the client asked for — a printable invoice when an order is submitted and a receipt once it is paid — without introducing any payment gateway (the BO marks paid manually).
**Mode:** mvp
**Depends on**: Phase 16 (order statuses, incl. Paid)
**Requirements**: [ORD-02, ORD-03]
**Success Criteria** (what must be TRUE):

  1. Submitting an order makes a printable/PDF invoice available (order no, BO/seller, customer, line items, totals) — no cost/margin fields.
  2. The BO can mark an order Paid manually (no gateway); doing so makes a receipt document available.
  3. Invoice/receipt are reachable only for the BO's own orders and carry no other BO's data.

**Plans**: TBD
**UI hint**: yes

### Phase 18: BO Order Dashboard

**Goal**: A Business-panel landing view that answers "how is my order pipeline doing?" at a glance — counts by status, revenue, and the most recent orders — all scoped to the BO.
**Mode:** mvp
**Depends on**: Phase 16 (statuses to aggregate)
**Requirements**: [ORD-05]
**Success Criteria** (what must be TRUE):

  1. The `/business` panel shows an order dashboard with order counts per status and total/period revenue, computed only over the BO's own orders.
  2. A recent-orders list links through to each order; every figure reconciles with the orders table for that BO.
  3. Another BO's orders never contribute to any figure (scoped queries, not display hiding).

**Plans**: TBD
**UI hint**: yes

### Phase 19: BO Sales Reporting

**Goal**: Give the Business Owner daily and weekly sales reports in their own panel, distinct from the existing admin OperationalReports.
**Mode:** mvp
**Depends on**: Phase 16 (order statuses/dates)
**Requirements**: [RPT-01, RPT-02]
**Success Criteria** (what must be TRUE):

  1. A BO can view a daily sales report (orders + revenue for a chosen day) in `/business`, scoped to their own orders, in Asia/Kuala_Lumpur.
  2. A BO can view a weekly sales report (per-day breakdown across a week).
  3. Totals reconcile with the order dashboard and the underlying orders for the same BO and period.

**Plans**: TBD
**UI hint**: yes

### Phase 20: Vendor Register

**Goal**: A place to register and manage vendors for product sourcing, completing DP-19 (product stock already shipped in DP-13).
**Mode:** mvp
**Depends on**: Existing Vendor model (v1.1) + admin cost-confidentiality pattern
**Requirements**: [VEN-01]
**Success Criteria** (what must be TRUE):

  1. An authorised user can register a vendor (name + contact/sourcing details) and edit/deactivate it.
  2. A product can reference a registered vendor, consistent with the existing admin-only cost/vendor confidentiality (never exposed to BO/partner panels).
  3. Vendor management is audited.

**Plans**: TBD
**UI hint**: yes

### Phase 21: CRM Polish

**Goal**: Two small, high-value CRM improvements — make the customer listing sortable by name, and replace free-text customer tags with an admin-managed tag vocabulary the BO picks from.
**Mode:** mvp
**Depends on**: Existing Customer resource + tags
**Requirements**: [CRMP-01, CRMP-02]
**Success Criteria** (what must be TRUE):

  1. The customer listing can be sorted by Name (both panels).
  2. An admin can manage a list of customer tags; a BO tagging a customer picks from that managed list (value still stored on the customer, scoped as today).
  3. Existing free-text tag data is preserved (not lost on migration).

**Plans**: TBD
**UI hint**: yes

**Execution Order:**
Phases execute in numeric order: 1 -> 2 -> 3 -> 4 -> 5 -> 6 -> 7 -> 8 -> 9 -> 10 -> 11 -> 12 -> 13 -> 14 -> 15

Within v1.3: Phase 16 (order lifecycle) is the prerequisite for 17, 18, and 19 (they all read order status/date). Phases 20 (vendor) and 21 (CRM polish) are independent and can run any time. Natural cut line if scope shrinks: ship 16 + 17 (the order/invoice core), defer dashboard/reporting.

Within v1.2: Phase 10 (CRM data layer + Phone helper) is the hard prerequisite for everything that follows. The CRM surface (11 -> 12 -> 13) builds on the detail view in order. The product track (14 -> 15) depends only on Phase 10's Phone helper and the existing ProductResource, so it is parallelizable with the CRM surface phases. Phase 15 (Share) is kept separate as its own security-reviewed slice and is the natural cut line if the milestone must shrink.

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation, Roles, and Data Model | 2/2 | Complete    | 2026-06-06 |
| 2. Partner and Business Owner Registration | 2/2 | Complete    | 2026-06-06 |
| 3. Customer Ownership and Eligibility Base | 2/2 | Complete    | 2026-06-06 |
| 4. Product Catalog, Selection, and Proposals | 2/2 | Complete    | 2026-06-06 |
| 5. Fees, Reports, and Basic Commerce Flow | 2/2 | Complete    | 2026-06-06 |
| 6. Fee & Quota Correctness Foundation | 0/1 | Complete    | 2026-06-20 |
| 7. Business Owner Fee Schedule | 0/? | Complete    | 2026-06-20 |
| 8. Partner Monthly Customer Quota | 0/2 | Complete    | 2026-06-20 |
| 9. Vendor / Product Sourcing | 0/? | Complete    | 2026-06-20 |
| 10. CRM Data Layer & Ownership Foundation | 1/1 | Complete    | 2026-06-30 |
| 11. Customer Detail View | 1/1 | Complete    | 2026-06-30 |
| 12. Segments & Gated Stage-Change | 1/1 | Complete    | 2026-06-30 |
| 13. One-Tap WhatsApp & Call | 1/1 | Complete    | 2026-06-30 |
| 14. Product Cards, Categories, Status & Simplified BO Form | 1/1 | Complete    | 2026-06-30 |
| 15. Share / Promote | 1/1 | Complete    | 2026-06-30 |
