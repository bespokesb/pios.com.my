# Requirements: PIOS

**Defined:** 2026-06-06  
**Core Value:** Business Owners can manage their own customers and products while PIOS enforces hierarchy ownership and Amway/Non-Amway eligibility correctly.

## v1 Requirements

Requirements for the initial release. Each maps to exactly one roadmap phase.

### Authentication and Access

- [x] **AUTH-01**: User can log in through the Filament panel.
- [x] **AUTH-02**: User can log out from the Filament panel.
- [x] **AUTH-03**: User sees navigation and resources allowed by their role.
- [x] **AUTH-04**: Unauthorized users cannot access records outside their permitted hierarchy.

### Foundation

- [x] **FND-01**: Database schema exists for users, partners, business owners, customers, products, product selections, product proposals, fees, payments, reports, territories, and audit logs.
- [x] **FND-02**: Partner No, Business Owner No, Customer No, VIP Customer No, Product No, and Order No are stored as unique generated business identifiers.
- [x] **FND-03**: Shared enums or equivalent constants exist for roles, statuses, partner tiers, customer categories, product types, fee statuses, and proposal statuses.
- [x] **FND-04**: Critical create/update/delete actions can be audited for sensitive records.
- [x] **FND-05**: Sys Admin can manage users, roles, permissions, territories, tiers, product types, and fee settings.

### Partner and Business Owner

- [x] **PBO-01**: PIOS Owner/System user can register a Partner with applicant, spouse, ABO, Diamond, contact, and address details.
- [x] **PBO-02**: Partner registration generates a unique Partner No.
- [x] **PBO-03**: Partner can register a Business Owner with applicant, spouse, company, product description, contact, and address details.
- [x] **PBO-04**: Business Owner registration generates a unique Business Owner No and links the record to the registering Partner.
- [x] **PBO-05**: Business Owner account access can be created or enabled after registration.
- [x] **PBO-06**: Partner tier can be stored with support for future system calculation and admin override.

### Customer Management

- [x] **CUST-01**: Business Owner can add a contact/prospect/customer with HP No, name, optional nick name, IC/KP No, email, and address.
- [x] **CUST-02**: Customer creation generates a unique Customer No.
- [x] **CUST-03**: Customer records are linked to the owning Business Owner and parent Partner reporting scope.
- [x] **CUST-04**: Duplicate ownership conflicts are checked using HP No and IC/KP No before customer creation.
- [x] **CUST-05**: Customer category supports lead, prospect, customer, VIP Customer (LOS), and Non-LOS Customer.
- [x] **CUST-06**: VIP Customer and Non-LOS Customer records support ABO No, DD Name, and DD HP No where applicable.

### Product Management

- [x] **PROD-01**: Authorized user can create products with product type, product no, name, description, selling price, coverage area, certification, promotional content, and media.
- [x] **PROD-02**: Product creation generates or validates a unique Product No.
- [x] **PROD-03**: Product is classified as Amway or Non-Amway.
- [x] **PROD-04**: Business Owner can view and select available products for their account.
- [x] **PROD-05**: Business Owner can submit a proposed product with name, area, partner price, customer price, image/video, and description.
- [x] **PROD-06**: Product proposals track approval status and reviewer details when review is required.

### Commerce, Fees, and Reporting

- [x] **COM-01**: PIOS Mart or equivalent sale workflow only shows products the selected customer is eligible to buy.
- [x] **COM-02**: Sale workflow requires the seller to own the selected customer or have explicit hierarchy permission.
- [x] **COM-03**: Business Owner monthly fees can be recorded with amount, due date, and status.
- [x] **COM-04**: Fee payments can be recorded with amount, status, reference number, and payment date.
- [x] **COM-05**: PIOS Owner reports include Business Owner list, Customer/VIP Customer list, and fee payment reports.
- [x] **COM-06**: Partner reports include downstream Business Owner, customer, and fee activity.
- [x] **COM-07**: Reports can be filtered by date range, Partner, Business Owner, product type, customer category, territory, and status where relevant.

## v1.1 Requirements

Milestone v1.1 — Partner Quota, Fees & Product Sourcing. Each maps to exactly one roadmap phase.

### Foundation (v1.1)

- [x] **FND-06**: A dedicated system user exists and is used as the audit actor for scheduled/system-generated records (e.g. recurring fees).

### Business Owner Fee Schedule

- [x] **FEE-01**: A one-time RM10 setup fee is generated for a Business Owner when HQ approves their registration, as a tracked obligation that does not block the Business Owner from operating.
- [x] **FEE-02**: A recurring RM5 monthly fee is generated automatically for each active Business Owner, at most once per Business Owner per calendar month.
- [x] **FEE-03**: Setup and monthly fee amounts and currency are configurable through Fee Settings rather than hardcoded.
- [x] **FEE-04**: HQ can view each Business Owner's fee obligations with paid / pending / overdue status.

### Partner Monthly Quota

- [x] **QUOTA-01**: Each partner's customers registered in the current calendar month (Malaysia time) are counted toward a configurable monthly target (default 100), attributed to customers created under the partner's downstream Business Owners.
- [x] **QUOTA-02**: A partner can see their current month's progress toward the monthly customer target in the partner panel.
- [x] **QUOTA-03**: HQ can view partners' monthly quota attainment in a report.
- [x] **QUOTA-04**: Quota tracking is advisory in v1.1 (no blocking) and implemented as a reusable service so enforcement can be enabled later without rework.

### Vendor / Product Sourcing

- [x] **SRC-01**: HQ/admin can manage vendor (merchant) records with name and contact details.
- [x] **SRC-02**: A product can be linked to a vendor and store an admin-only cost price.
- [x] **SRC-03**: Product cost, vendor, and margin are visible only in the admin panel and are never exposed in the partner or business panels.
- [x] **SRC-04**: HQ can view product margin (customer price − cost) in the catalog or a report.

## v1.2 Requirements

Milestone v1.2 — BO-facing CRM & Product Listing. Everything is strictly BO-scoped (a Business Owner only ever sees/manages their own customers, notes, follow-ups, and products).

### Customer CRM

- [x] **CRM-01**: A Business Owner can add timestamped notes to their own customers, and only ever sees notes on customers they own.
- [x] **CRM-02**: A Business Owner can create follow-up reminders (due date, note, done flag) on their own customers, and see which are due/overdue.
- [x] **CRM-03**: A Business Owner can open a customer detail view showing the customer's info, their notes, and their follow-ups.
- [x] **CRM-04**: A Business Owner can segment/filter their customer list by stage (Lead/Prospect/Customer/VIP) with per-stage counts, and search by name or phone.
- [x] **CRM-05**: A Business Owner can change a customer's stage between Lead, Prospect, and Customer via an inline action (VIP/Non-LOS remain out of scope, as they depend on LOS verification).
- [x] **CRM-06**: A Business Owner can start a WhatsApp chat or phone call to a customer in one tap from the customer's phone number.

### Product Listing

- [x] **PLST-01**: A Business Owner sees their own products as a card/grid listing showing image, price, and status.
- [x] **PLST-02**: A Business Owner can organize their products by category (product type) and manage status (active/draft/archived), and filter the listing by both.
- [x] **PLST-03**: A Business Owner's product create/edit form is simplified to the essentials (name, price, image, description, category, status); admin-only fields (cost, vendor, commission, classification) stay hidden and non-editable.
- [x] **PLST-04**: A Business Owner can share/promote one of their products to a customer/prospect via a pre-filled WhatsApp message (name, price, description).

## v1.3 Requirements

Milestone v1.3 — BO Order Management & Fulfilment. Order pipeline, documents, dashboard, reports, vendor register, and CRM polish — all strictly BO-scoped. Payment stays documents-only with a manual mark-paid (no gateway); customer tags become an admin-managed list; dashboard and reports live in the `/business` panel over the BO's own orders. Maps to Jira DP-16/17/18/19/9/15.

### Order Management

- [ ] **ORD-01** (DP-16): An order has an explicit status lifecycle (Submitted → Paid → Packed → Shipped → Completed, plus Cancelled); the owning Business Owner transitions it forward through allowed steps only, enforced server-side and audited.
- [ ] **ORD-02** (DP-16): Submitting an order makes a printable/PDF invoice available (order no, seller, customer, line items, totals) with no cost/margin fields.
- [ ] **ORD-03** (DP-16): A Business Owner can mark an order Paid manually (no payment gateway); a receipt document becomes available once paid.
- [ ] **ORD-04** (DP-16): An order can carry a tracking number, set at/after Shipped and visible where the order is viewed.
- [ ] **ORD-05** (DP-17): A Business Owner sees an order dashboard in `/business` — counts by status and revenue over their own orders, with a recent-orders list.

### Sales Reporting

- [ ] **RPT-01** (DP-18): A Business Owner can view a daily sales report (orders + revenue) in `/business`, scoped to their own orders, in Asia/Kuala_Lumpur.
- [ ] **RPT-02** (DP-18): A Business Owner can view a weekly sales report with a per-day breakdown.

### Vendor Sourcing

- [ ] **VEN-01** (DP-19): An authorised user can register and manage vendors for product sourcing; products reference a vendor consistent with the existing admin-only cost/vendor confidentiality. (Product stock quantity already shipped as DP-13.)

### CRM Polish

- [ ] **CRMP-01** (DP-9): The customer listing can be sorted by Name.
- [ ] **CRMP-02** (DP-15): An admin-managed customer tag list exists; a Business Owner tagging a customer picks from that list (value still stored on the customer, scoped as today; existing free-text tags preserved).

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### Sourcing and Location

- **SRC-05**: Products/vendors carry a GPS/location element (store locator, delivery zone, or territory — meaning to be confirmed before build).

### Partner Quota Enforcement

- **QUOTA-05**: Missing the monthly customer target triggers a defined consequence (warning, tier impact, or suspension) once the policy is confirmed.

### Promotion and Prospecting

- **PROM-01**: Business Owner can promote selected products to contacts through WhatsApp.
- **PROM-02**: Business Owner can manage promotion responses.
- **PROM-03**: System can integrate with WhatsApp Business API if confirmed.
- **AIP-01**: System can support AI prospecting workflows for lead generation or scoring.

### Payments and Operations

- **PAY-01**: System can integrate with a payment gateway for automated fee and order payments.
- **OPS-01**: System can support inventory, delivery, order tracking, refunds, and customer self-service if confirmed.

### Advanced Management

- **RPT-01**: System can provide advanced dashboards and exportable reports.
- **TRN-01**: System can support full 3S 1T training, sales, prospecting, and service managers.
- **SHARE-PUB-01**: Public tokenized product landing page (signed/expiring URL + field whitelist + noindex + throttle) — deferred from v1.2 Share because an unauthenticated route bypasses the scope/policy layer.

## Out of Scope

| Feature | Reason |
|---------|--------|
| Native mobile app | Web/Filament app first. |
| Direct Amway integration | Integration source and API requirements are not defined. |
| WhatsApp Business API automation | Integration method is unresolved. |
| Payment gateway automation | Gateway choice and fee policy are unresolved. |
| Inventory and delivery operations | Not defined in the current PRD. |
| AI prospecting | Mentioned as future capability, not needed for v1. |
| GPS / geolocation element | Meaning unconfirmed (store locator vs delivery zone vs territory); overlaps existing territories. Deferred to v2 (SRC-05). |
| Partner quota hard enforcement | v1.1 is advisory; miss-consequence policy undefined. Deferred to v2 (QUOTA-05). |
| BO suspension / activation gating on unpaid fees | v1.1 tracks fee obligations only; no gating or auto-suspension. |
| First-month fee proration | Full setup + monthly charged; no proration in v1.1. |
| Public product landing page (v1.2 Share) | Unauthenticated route bypasses scope/policy and exposes cost/margin/cross-BO catalog; v1.2 Share is text-only `wa.me`. Deferred to v2 (SHARE-PUB-01). |
| BO→Partner upgrade; Partner membership / Product Partner status | Acknowledged in v1.2 scope but deferred to Phase 2 (amount TBC). |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| AUTH-01 | Phase 1 | Complete |
| AUTH-02 | Phase 1 | Complete |
| AUTH-03 | Phase 1 | Complete |
| AUTH-04 | Phase 1 | Complete |
| FND-01 | Phase 1 | Complete |
| FND-02 | Phase 1 | Complete |
| FND-03 | Phase 1 | Complete |
| FND-04 | Phase 1 | Complete |
| FND-05 | Phase 1 | Complete |
| PBO-01 | Phase 2 | Complete |
| PBO-02 | Phase 2 | Complete |
| PBO-03 | Phase 2 | Complete |
| PBO-04 | Phase 2 | Complete |
| PBO-05 | Phase 2 | Complete |
| PBO-06 | Phase 2 | Complete |
| CUST-01 | Phase 3 | Complete |
| CUST-02 | Phase 3 | Complete |
| CUST-03 | Phase 3 | Complete |
| CUST-04 | Phase 3 | Complete |
| CUST-05 | Phase 3 | Complete |
| CUST-06 | Phase 3 | Complete |
| PROD-01 | Phase 4 | Complete |
| PROD-02 | Phase 4 | Complete |
| PROD-03 | Phase 4 | Complete |
| PROD-04 | Phase 4 | Complete |
| PROD-05 | Phase 4 | Complete |
| PROD-06 | Phase 4 | Complete |
| COM-01 | Phase 5 | Complete |
| COM-02 | Phase 5 | Complete |
| COM-03 | Phase 5 | Complete |
| COM-04 | Phase 5 | Complete |
| COM-05 | Phase 5 | Complete |
| COM-06 | Phase 5 | Complete |
| COM-07 | Phase 5 | Complete |
| FND-06 | Phase 6 | Complete |
| FEE-01 | Phase 7 | Complete |
| FEE-02 | Phase 7 | Complete |
| FEE-03 | Phase 7 | Complete |
| FEE-04 | Phase 7 | Complete |
| QUOTA-01 | Phase 8 | Complete |
| QUOTA-02 | Phase 8 | Complete |
| QUOTA-03 | Phase 8 | Complete |
| QUOTA-04 | Phase 8 | Complete |
| SRC-01 | Phase 9 | Complete |
| SRC-02 | Phase 9 | Complete |
| SRC-03 | Phase 9 | Complete |
| SRC-04 | Phase 9 | Complete |
| CRM-01 | Phase 10 | Complete |
| CRM-02 | Phase 10 | Complete |
| CRM-03 | Phase 11 | Complete |
| CRM-04 | Phase 12 | Complete |
| CRM-05 | Phase 12 | Complete |
| CRM-06 | Phase 13 | Complete |
| PLST-01 | Phase 14 | Complete |
| PLST-02 | Phase 14 | Complete |
| PLST-03 | Phase 14 | Complete |
| PLST-04 | Phase 15 | Complete |

**Coverage:**
- v1 requirements: 34 total — mapped to phases: 34, unmapped: 0
- v1.1 requirements: 13 total — mapped to phases: 13, unmapped: 0
- v1.2 requirements: 10 total — mapped to phases: 10, unmapped: 0

---
*Requirements defined: 2026-06-06*
*Last updated: 2026-06-30 after roadmapping milestone v1.2 (Phases 10–15)*
