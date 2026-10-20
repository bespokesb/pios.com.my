# PIOS Product Requirements Document

Status: Draft  
Version: 0.2  
Prepared: 2026-06-05  
Updated: 2026-06-06  
Sources:
- `/Users/habib/Downloads/PIOS PRODUCT FEATURES V1.0.pdf`
- `/Users/habib/Downloads/WhatsApp Image 2026-06-05 at 10.37.51 PM.jpeg`
- `/Users/habib/Downloads/WhatsApp Image 2026-06-05 at 10.37.52 PM.jpeg`
- `/Users/habib/Downloads/WhatsApp Image 2026-06-05 at 10.37.52 PM (1).jpeg`
- `/Users/habib/Downloads/WhatsApp Image 2026-06-05 at 10.37.52 PM (2).jpeg`
- `/Users/habib/Downloads/WhatsApp Image 2026-06-05 at 10.37.53 PM.jpeg`

## 1. Product Summary

PIOS, short for Pak ITAM Online Supermarket, is an online supermarket platform for selling daily-use products that are premium, organic, exclusive, and unique. The system supports a partner-led selling model where PIOS Owner users register Partners, Partners register Business Owners, and Business Owners register contacts/customers, add or propose products, promote products, manage responses, pay fees, and review reports.

The platform must enforce ownership and product eligibility rules so each user only acts on records within their hierarchy and only sells product types that each customer is eligible to buy.

## 2. Objectives

- Provide a centralized online system for PIOS Owner, PIOS System administrators, Partners, customers, products, services, tools, accounts, and AI prospecting.
- Enable PIOS Owner and PIOS System users to appoint and manage Partners.
- Enable Partners to register and manage Business Owners.
- Enable Business Owners to register contacts, prospects, and customers; add or propose products; promote products; manage responses; pay fees; and use reports.
- Enable Partners to appoint downstream Partners, where that remains part of the active business model.
- Support Amway and Non-Amway product catalogs with clear purchasing eligibility rules.
- Support customer segmentation into leads, prospects, customers, VIP Customers under LOS, and Non-LOS Customers.
- Provide reporting for registration, partner performance, product/service activity, payments, and overall operations.

## 3. Non-Goals

- This PRD does not define final visual design beyond the functional structure shown in the source PDF.
- This PRD does not define integration details with Amway systems, payment gateways, SMS, email, or AI vendors.
- This PRD does not define financial settlement, tax, inventory, or logistics workflows unless explicitly mentioned in the source PDF.
- This PRD does not define the exact criteria for Partner tier advancement because the source PDF only states example factors.

## Technical Stack Decision

PIOS will be implemented as a Laravel application using FilamentPHP 5 and MySQL.

| Layer | Decision |
| --- | --- |
| Backend Framework | Laravel |
| Admin/Application UI | FilamentPHP 5.x |
| Database | MySQL |
| ORM | Laravel Eloquent |
| UI Runtime | Filament components built on Livewire, Alpine.js, and Tailwind CSS |
| Authentication | Laravel authentication with Filament panel login |
| Authorization | Laravel policies and role-based permissions |
| Reports | Filament tables, filters, widgets, and custom report pages |
| File Uploads | Laravel filesystem storage for product photos, videos, certifications, and promotional content |

### Filament Implementation Approach

- Use Filament Panels for authenticated PIOS back-office access.
- Use Filament Resources for core CRUD modules:
  - Partners
  - Business Owners
  - Customers
  - Contacts and Prospects
  - Products & Services
  - Product Selections
  - Product Proposals
  - Territories
  - Fees and Payments
  - Promotions and Responses
  - Reports
  - Users and Roles
- Use Filament navigation groups to match the source modules: Register, 3S 1T, Partner, PIOS Mart, Fee, Sys Admin, and Report.
- Use Filament forms for Partner, Business Owner, contact/customer, product, product proposal, territory, and fee registration.
- Use Filament tables for searchable and filterable lists.
- Use Filament widgets for dashboard metrics such as Partner counts, Business Owner counts, customer counts, product counts, sales activity, and payment status.
- Use Laravel model policies to enforce hierarchy ownership, product eligibility, and admin permissions.
- Use MySQL foreign keys and indexes to protect hierarchy, ownership, and reporting performance.

### MySQL Data Storage Notes

- Use integer or ULID primary keys consistently across tables.
- Add unique indexes for PIOS No, Business Owner No, Customer No, VIP Customer No, Product No, and user email.
- Add foreign keys for Partner hierarchy, Business Owner ownership, customer ownership, product references, product selections, product proposals, sales records, and payment records.
- Use enum-like columns or lookup tables for Partner tier, customer category, product type, product proposal status, payment status, and user role.
- Index high-use report filters such as partner_id, business_owner_id, customer_category, product_type, territory_id, payment_status, and created_at.
- Store flexible fields such as UDF1 to UDF5 and promotional metadata as JSON only when the expected structure is not yet stable.

## 4. Stakeholders and Users

| User | Description | Primary Responsibilities |
| --- | --- | --- |
| PIOS Owner | Project sponsor and owner of the PIOS system. | Appoint Partners, oversee business growth, review reports. |
| PIOS System | Co-sponsor or system owner. | Administer the platform, appoint Partners, manage system-level settings and reporting. |
| Partner | System user appointed by PIOS Owner. | Register Business Owners, review Business Owner activity, manage fees and reports. |
| Business Owner | Business user registered by a Partner. | Register contacts, prospects, and customers; select available products; propose new products; promote products; manage responses; pay fees; review reports. |
| Customer | A customer or prospective customer entered into the system by a Business Owner or authorized user. | Buy eligible products through the owning Business Owner or assigned seller. |

## 5. Key Definitions

- Partner: A business user appointed in PIOS.
- Partner Tier: Partner rank or level. Source tiers are Executive Partner, Staff Partner, Senior Partner, Associate Partner, and Principal.
- Business Owner: A seller/operator registered by a Partner. In the May 2026 UI mockups, this role owns the contact/customer and product promotion workflow.
- Business Owner No: Unique identifier generated by the system for each Business Owner.
- Customer Ownership: A customer belongs exclusively to the Business Owner or seller account that entered that customer into PIOS, with parent Partner visibility controlled by permissions.
- Customer No: Unique identifier generated by the system for each customer.
- VIP Customer (LOS): A VIP customer under LOS MSMY or a public customer registered in PIOS as eligible for Amway and Non-Amway products.
- Non-LOS Customer: A customer outside LOS MSMY. This customer can only buy Non-Amway products.
- Lead Prospect: Minimal lead record with name and phone number.
- Product: An item or service sold by Business Owners or authorized sellers to their customers.
- Available Product: Existing product that a Business Owner can select for promotion or sale.
- Proposed Product: New product submitted by a Business Owner for creation or approval.
- Amway Product: Product type that can only be sold to eligible VIP Customers.
- Non-Amway Product: Product type that can be sold to all customer categories, including Amway members outside LOS MSMY.
- Tools App: PIOS modules that help Partners with prospecting, selling, promotion, service, training, and related activities.

## 6. Business Rules

### 6.1 Partner Rules

- PIOS Owner and PIOS System users can appoint Partners.
- Partners can register Business Owners.
- Partners can appoint other Partners if the Partner-to-Partner hierarchy remains in scope.
- Partners have one of five tiers:
  - Executive Partner
  - Staff Partner
  - Senior Partner
  - Associate Partner
  - Principal
- Partner tier criteria must support factors such as:
  - Number of Partners appointed by the Partner.
  - Number of active Partners under the Partner.
- The source PDF includes the Partner growth sequence `1 < 4 < 16 < 64 < 256 = 341`; the exact meaning and tier mapping must be confirmed.
- Partner tiering must support a mixed model: system-calculated rules with admin override. Exact thresholds are still pending.

### 6.2 Business Owner Rules

- A Partner can register one or more Business Owners.
- Each Business Owner must belong to the registering Partner or assigned parent Partner.
- The system must generate a unique Business Owner No.
- Business Owners must receive login credentials or account access after registration.
- Business Owners can add contacts, prospects, customers, available products, proposed products, promotions, fees/payment activity, and reports within their own account.
- Parent Partner visibility into Business Owner records must be permission-controlled.

### 6.3 Customer Ownership Rules

- A Business Owner may only sell to customers registered under that Business Owner unless authorized hierarchy rules permit otherwise.
- A Business Owner must not sell to customers owned by another Business Owner.
- When a Business Owner creates a customer record, that customer becomes exclusive to that Business Owner.
- The system must prevent duplicate or conflicting customer ownership based on configured identity checks.
- Parent Partners can view or report on downstream Business Owner customers only if role permissions allow it.

### 6.4 Product Eligibility Rules

- Products must be classified as either Amway or Non-Amway.
- Amway products can only be sold to VIP Customers.
- VIP Customers can buy both Amway and Non-Amway products.
- Non-LOS Customers can only buy Non-Amway products.
- Non-Amway products can be sold to all customers, including Amway members outside LOS MSMY.
- VIP Customer status is only for public customers and LOS MSMY customers, based on the source PDF.

### 6.5 Data Access Rules

- Business Owners can view and manage only their own contact, customer, product, promotion, fee, and sales records unless they are granted additional permissions.
- Partners can view and manage Business Owner records within their hierarchy where authorized.
- PIOS Owner, PIOS System, and authorized system administrators can view operational and reporting data across the platform.
- Partner hierarchy data must support parent-child relationships between appointing and appointed Partners.

## 7. Information Architecture

The main PIOS login area must expose these top-level modules:

| Module | Submodules |
| --- | --- |
| Register | Partner, Product & Services, PIOS Territory, Fees, Report |
| 3S 1T | Promotion, Sales Manager, Prospecting Manager, Service Manager, Training Manager, Report |
| Partner | Partner Manager, Customer Manager, Product & Service Manager, Report |
| PIOS Mart | PIOS Mart, Report |
| Fee | Payment, Report |
| Sys Admin | Sys Admin |
| Report | Report |

### 7.1 Updated May 2026 UI Flow

The May 2026 UI mockups refine the application flow into three login contexts.

#### PIOS Main Page Login

| Area | Functions |
| --- | --- |
| Register Partner | Owner registers Partners. |
| SysAdmin | System administration. |
| Fees | Monthly fees paid by Business Owners. |
| Report / Dashboard | Business Owner list, Customer/VIP Customer list, fee payment reports. |

#### Partner Login

| Area | Functions |
| --- | --- |
| Register Business Owner | Partner registers Business Owner and creates ID/password access. |
| Fees | Business Owner fee-related workflows. |
| Report | Partner-level reporting. |

#### Business Owner Login

| Area | Functions |
| --- | --- |
| Dashboard | Business Owner summary view. |
| Add Contact, Prospect, Customer | Register contact/customer records. |
| Add Product, Services, Programme | Select available products and propose new products. |
| Promote Product to Contact | Send product promotions to contacts through WhatsApp. |
| Business Tax / Manage Promotion Respond | Track or manage promotion responses. Exact meaning of "Business Tax" requires confirmation. |
| Fees Payment | Pay monthly or account fees. |
| Reporting | Business Owner reports. |

### 7.2 UI Field Legend

The May 2026 mockups use highlighted fields to indicate important system behavior:

| UI Marker | Requirement Meaning |
| --- | --- |
| Key | Field is important for lookup, identity, or workflow. |
| No Unik | Field must be unique. |
| System Generated | Field value is generated by the system and must not be manually duplicated. |

## 8. Functional Requirements

### 8.1 Authentication and Navigation

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-001 | The system must provide a main login page for authorized PIOS users. | Must |
| FR-002 | After login, users must see modules based on their role and permissions. | Must |
| FR-003 | The system must support top-level navigation for Register, 3S 1T, Partner, PIOS Mart, Fee, Sys Admin, and Report. | Must |
| FR-004 | The system must prevent unauthorized access to modules and records. | Must |

### 8.2 Register Module

The Register module supports creation and administration of foundational business records.

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-010 | Authorized users must be able to register a Partner. | Must |
| FR-011 | Partners must be able to register Business Owner records. | Must |
| FR-012 | Authorized users must be able to register and manage PIOS Territory records. | Should |
| FR-013 | Authorized users must be able to register and manage fee-related records. | Should |
| FR-014 | Users must be able to view Register module reports. | Should |
| FR-015 | Business Owners must be able to register or propose Product & Service records from their Business Owner login. | Must |

#### Partner Registration Fields

The Partner registration form must capture:

| Section | Fields |
| --- | --- |
| Amway Info | ABO No, Diamond Name |
| Applicant | Name, IC No, HP No, Email, Address, Bandar, Negeri, Poskod |
| Spouse | Name, IC No, HP No, Email |
| System Generated | Partner No |

#### Partner Registration Acceptance Criteria

- A Partner cannot be created without required identity and contact fields.
- Partner No must be generated by the system and remain unique.
- ABO No must be stored when provided.
- The system must support spouse details as optional fields unless business policy later makes them mandatory.
- The system must show validation errors inline before submission is accepted.

#### Business Owner Registration Fields

The Partner-to-Business Owner registration form must capture:

| Section | Fields |
| --- | --- |
| Applicant | Name, IC No, HP No, Email, Address, Bandar, Negeri, Poskod |
| Spouse | Name, IC No, HP No, Email |
| Business | Company Name, Product Description |
| System Generated | Business Owner No |

#### Business Owner Registration Acceptance Criteria

- A Business Owner cannot be created without required identity and contact fields.
- Business Owner No must be generated by the system and remain unique.
- The registering Partner must be stored as the parent Partner.
- The system must support spouse details as optional fields unless business policy later makes them mandatory.
- The system must capture company name and initial product description.
- The system must create or enable login access for the Business Owner.
- The system must show validation errors inline before submission is accepted.

### 8.3 Partner Manager

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-020 | Partners and authorized managers must be able to view Partner profiles. | Must |
| FR-021 | Partners must be able to register and view downstream Business Owners. | Must |
| FR-022 | The system must maintain hierarchy links between a Partner and appointed Partners. | Must |
| FR-023 | The system must calculate or store Partner tier. | Should |
| FR-024 | The Partner Manager must include a Partner Performance Manager. | Should |
| FR-025 | The Partner Manager must provide Partner reports. | Should |
| FR-026 | Authorized users must be able to register downstream Partners if Partner-to-Partner appointments remain in scope. | Should |
| FR-027 | Partner reports must include Business Owner list, Customer/VIP Customer list, and fee payment reports. | Must |

### 8.4 Customer Manager

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-030 | Business Owners must be able to register contact records. | Must |
| FR-031 | Business Owners must be able to convert or manage prospects. | Must |
| FR-032 | Business Owners must be able to register Customer records. | Must |
| FR-033 | Business Owners must be able to register VIP Customer records when the customer is eligible. | Must |
| FR-034 | The system must enforce customer ownership by Business Owner or assigned seller. | Must |
| FR-035 | The Customer Manager must support the VIP Customer RM2 reference from the source PDF once fee behavior is confirmed. | Should |
| FR-036 | Customer No must be generated by the system and remain unique. | Must |
| FR-037 | Customer records must be linked to Business Owner No. | Must |

#### Lead Prospect Fields

| Field | Requirement |
| --- | --- |
| Name | Required |
| HP No | Required |

#### Contact / Prospect / Customer Screen Fields

The Business Owner add-contact screen must capture:

| Field | Requirement |
| --- | --- |
| Business Owner No | Required; resolves to the current Business Owner account |
| HP No | Required; key lookup field |
| Name | Required |
| Nick Name | Optional |
| IC No | Required or configurable; key identity field |
| Email | Optional or configurable |
| Address | Optional or configurable |
| Customer No | System generated and unique |

#### Contact / Prospect / Customer Acceptance Criteria

- Business Owner No must be prefilled or resolved from the logged-in Business Owner.
- HP No and IC No must be checked for duplicate customer ownership conflicts.
- Customer No must be generated after a valid customer record is created.
- A Business Owner must not create a customer under another Business Owner No.
- The same screen must support contact, prospect, and customer state transitions if those statuses remain separate.

#### VIP Customer (LOS) Fields

| Field | Requirement |
| --- | --- |
| VIP Customer No | Required and unique |
| Name | Required |
| HP No | Required |
| KP No | Required or configurable |
| Email | Optional or configurable |
| Address | Required or configurable |
| ABO No | Optional or configurable |
| DD Name | Optional or configurable |
| DD HP No | Optional or configurable |

#### Non-LOS Customer Fields

| Field | Requirement |
| --- | --- |
| Customer No | Required and unique |
| Name | Required |
| HP No | Required |
| KP No | Required or configurable |
| Email | Optional or configurable |
| Address | Required or configurable |
| ABO No | Optional or configurable |
| DD Name | Optional or configurable |
| DD HP No | Optional or configurable |

### 8.5 Product & Service Manager

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-040 | Business Owners and authorized users must be able to create or propose Product & Service records. | Must |
| FR-041 | Each product must be classified as Amway or Non-Amway. | Must |
| FR-042 | Each product must include product number, product name, description, selling price, coverage area, certification, and promotional content. | Must |
| FR-043 | The system must support design, photo, or video assets for products and services. | Should |
| FR-044 | The system must support pricing structure configuration. | Should |
| FR-045 | The system must support sales commission configuration. | Should |
| FR-046 | The Product & Service Manager must include product/service reports. | Should |
| FR-047 | Business Owners must be able to view and select available products for their account. | Must |
| FR-048 | The Add Product screen must support a maximum selected available-product list if the max-5 rule is confirmed. | Should |
| FR-049 | Business Owners must be able to submit a proposed product with name, area, partner price, customer price, image/video, and description. | Must |

#### Product Fields

| Field | Requirement |
| --- | --- |
| Product Type | Required; Amway or Non-Amway |
| Product No | Required and unique |
| Product Name | Required |
| Description | Required |
| Selling Price | Required |
| Coverage Area | Required or configurable |
| Certification | Optional or configurable |
| Promotional Content | Optional or configurable |

#### Business Owner Add Product Screen Fields

The Business Owner add-product screen must capture:

| Section | Fields |
| --- | --- |
| Business Owner Context | Business Owner No |
| Available Product List | Product Name, Area, Merchant Name, Select checkbox, Image/Video, Description |
| Proposed Product | Name, Area, Partner Price, Customer Price, Image/Video, Description |

#### Product Selection and Proposal Acceptance Criteria

- Business Owner No must be prefilled or resolved from the logged-in Business Owner.
- Available products must be searchable or selectable from the current product catalog.
- If the source `max 5` rule is confirmed, the system must prevent selecting more than five available products.
- Proposed product submissions must be stored with the submitting Business Owner.
- Proposed product approval status must be tracked if approval is required before sale or promotion.
- Product media uploads must use Laravel filesystem storage and be associated with the product or proposal record.

### 8.6 PIOS Mart

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-050 | PIOS Mart must allow eligible customers to browse or purchase eligible products. | Must |
| FR-051 | PIOS Mart must restrict product visibility or checkout based on customer eligibility rules. | Must |
| FR-052 | PIOS Mart must ensure the selling Business Owner or assigned seller owns the customer record. | Must |
| FR-053 | PIOS Mart must provide reports for sales or activity. | Should |

### 8.7 3S 1T Module

The source PDF names 3S 1T submodules but does not define detailed behavior. The May 2026 UI mockups add WhatsApp promotion and promotion-response management for Business Owners.

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-060 | The Promotion module must help Partners manage promotional activities and content. | Should |
| FR-061 | The Sales Manager must help Partners track sales activity. | Should |
| FR-062 | The Prospecting Manager must help Partners manage leads and prospecting activity. | Should |
| FR-063 | The Service Manager must help Partners manage service activity. | Should |
| FR-064 | The Training Manager must help Partners manage training activity. | Could |
| FR-065 | The 3S 1T module must provide reports. | Should |
| FR-066 | Business Owners must be able to promote selected products to contacts through WhatsApp. | Should |
| FR-067 | Business Owners must be able to manage promotion responses. | Should |

### 8.8 Fees and Payment

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-070 | The Fee module must support monthly Business Owner fee payment workflows. | Must |
| FR-071 | The Fee module must provide payment reports for Business Owner fees. | Must |
| FR-072 | The system must support fee rules for Partner, customer, VIP Customer, or product workflows once fee policy is confirmed. | Could |

### 8.9 Reports

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-080 | The system must provide reports for Register, Partner, Product & Service, PIOS Mart, Fee, 3S 1T, and overall operations. | Should |
| FR-081 | Reports must be filterable by date range, Partner, Business Owner, product type, customer category, and territory where relevant. | Should |
| FR-082 | Authorized users must be able to export reports. | Could |
| FR-083 | Reports must respect user permissions and customer ownership rules. | Must |
| FR-084 | PIOS Owner reports must include Business Owner list, Customer/VIP Customer list, and fee payment reports. | Must |

### 8.10 System Administration

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-090 | Sys Admin users must be able to manage users, roles, permissions, and system configuration. | Must |
| FR-091 | Sys Admin users must be able to manage reference data such as territories, Partner tiers, product types, and fee settings. | Should |
| FR-092 | The system must maintain audit records for critical changes. | Should |

### 8.11 Account and AI Prospecting

The overview diagram references Account and AI Prospecting. Detailed behavior is not defined in the source PDF.

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-100 | The system should provide account-related features for Partners, Business Owners, and customers. | Could |
| FR-101 | The system should support AI prospecting workflows for lead or prospect generation once requirements are defined. | Could |

## 9. Data Model

### 9.1 Partner

- Partner ID / PIOS No
- Parent Partner ID / Sponsor PIOS No, if Partner-to-Partner hierarchy is enabled
- ABO No
- Diamond Name
- Applicant name
- IC No
- HP No
- Email
- Address
- Bandar
- Negeri
- Poskod
- Spouse name
- Spouse IC No
- Spouse HP No
- Spouse email
- Partner tier
- Status
- Created by
- Created date

### 9.2 Business Owner

- Business Owner ID / Business Owner No
- Parent Partner ID
- Applicant name
- IC No
- HP No
- Email
- Address
- Bandar
- Negeri
- Poskod
- Spouse name
- Spouse IC No
- Spouse HP No
- Spouse email
- Company name
- Product description
- Login user ID
- Status
- Created by
- Created date

### 9.3 Customer

- Customer ID or VIP Customer ID
- Customer category: Lead, Prospect, Customer, VIP Customer, Non-LOS Customer
- Owning Business Owner ID
- Parent Partner ID, derived or stored for reporting
- Name
- Nick name
- HP No
- KP No
- Email
- Address
- ABO No
- DD Name
- DD HP No
- Status
- Created by
- Created date

### 9.4 Product

- Product ID
- Product type: Amway or Non-Amway
- Product number
- Product name
- Description
- Selling price
- Coverage area
- Certification
- Promotional content
- Media assets
- Pricing structure
- Sales commission
- Status
- Created by
- Created date

### 9.5 Business Owner Product Selection

- Selection ID
- Business Owner ID
- Product ID
- Selected status
- Selected date

### 9.6 Product Proposal

- Proposal ID
- Business Owner ID
- Name
- Area
- Partner price
- Customer price
- Image/video asset
- Description
- Approval status
- Reviewed by
- Reviewed date
- Created date

### 9.7 Territory

- Territory ID
- Territory name
- Coverage area
- Status

### 9.8 Payment or Fee

- Fee ID
- Fee type
- Related Partner, Business Owner, customer, or entity
- Amount
- Payment status
- Payment date
- Reference number

## 10. Core User Flows

### 10.1 Appoint Partner

1. PIOS Owner, PIOS System user, or authorized Partner opens Register Partner.
2. User enters sponsor, applicant, ABO, address, and optional spouse details.
3. System validates required fields and PIOS No uniqueness.
4. System creates Partner record and assigns tier/status.
5. New Partner appears in Partner hierarchy and reports.

### 10.2 Register Business Owner

1. Partner logs in and opens Register Business Owner.
2. Partner enters applicant, spouse, company, and product description details.
3. System validates required fields.
4. System generates a unique Business Owner No.
5. System links the Business Owner to the registering Partner.
6. Business Owner account access is created or enabled.

### 10.3 Register Contact, Prospect, or Customer

1. Business Owner logs in and opens Add Contact.
2. System resolves the logged-in Business Owner No.
3. Business Owner enters HP No, name, optional nick name, IC No, email, and address.
4. System checks duplicate ownership conflicts.
5. System creates the record and generates a unique Customer No when the record becomes a customer.

### 10.4 Register Product

1. Authorized user opens Product & Service registration.
2. User enters product type, number, name, description, price, coverage area, certification, and promotional content.
3. System validates product type and required fields.
4. Product becomes available for eligible sale workflows.

### 10.5 Business Owner Add Product

1. Business Owner opens Add Product.
2. System resolves Business Owner No.
3. Business Owner selects available products from the catalog.
4. Business Owner optionally submits a proposed product with name, area, partner price, customer price, image/video, and description.
5. System stores selections and product proposals under the Business Owner account.

### 10.6 Legacy Partner Register Customer

1. Partner opens Customer Manager.
2. Partner creates Lead, Prospect, Customer, or VIP Customer record.
3. System checks duplicate ownership and required fields.
4. Customer is assigned to the creating Partner.
5. Customer becomes eligible for permitted product types.

Note: This older Partner-led customer flow is retained for compatibility with the original PDF. The May 2026 UI mockups indicate that Business Owner-led customer registration is the primary flow for v1 unless confirmed otherwise.

### 10.7 Sell Product in PIOS Mart

1. Business Owner or assigned seller selects an owned customer.
2. System shows only products the customer is eligible to buy.
3. Business Owner or assigned seller selects products and completes sale or purchase workflow.
4. System records transaction and updates reports.

### 10.8 Promote Product Through WhatsApp

1. Business Owner selects one or more owned contacts or customers.
2. Business Owner selects an available or approved product.
3. System prepares a WhatsApp promotion message or link.
4. Business Owner sends the promotion.
5. System records response status where available.

## 11. Acceptance Criteria

- Users can log in and access only modules authorized for their role.
- PIOS Owner and PIOS System users can register Partners.
- Partners can register Business Owners assigned to their hierarchy.
- Business Owner No is generated by the system and is unique.
- Business Owners can register contacts, prospects, and customers assigned exclusively to themselves.
- Customer No is generated by the system and is unique.
- Customer records cannot be reassigned or duplicated across Business Owners without authorized override.
- Business Owners can select available products and submit proposed products.
- Products are classified as Amway or Non-Amway.
- VIP Customers can purchase Amway and Non-Amway products.
- Non-LOS Customers cannot purchase Amway products.
- Product records include the required source fields.
- Partner records include applicant, sponsor, ABO, contact, address, and optional spouse fields.
- Business Owner records include applicant, spouse, company, product description, parent Partner, and generated Business Owner No.
- Reports are available from the modules defined in the source PDF.
- Critical administrative actions are permission-controlled.

## 12. Non-Functional Requirements

- Security: Role-based access control must protect all modules and records.
- Privacy: Customer and Partner personal data must be handled as sensitive information.
- Auditability: Creation and updates of Partner, customer, product, fee, and admin records should be auditable.
- Data Quality: The system should validate phone numbers, emails, unique IDs, and required fields.
- Scalability: Partner hierarchy must support multi-level growth, including the source sequence up to at least 341 Partners per tree if confirmed.
- Usability: Forms should provide clear validation, searchable lists, and simple filtering.
- Reporting: Reports should load within acceptable business-use latency for common filters.

## 13. Proposed Release Scope

The source PDF includes an implementation plan for Phase 1 and Phase 2 across April and May 2026. Since this PRD is prepared on 2026-06-05, those dates are historical and should be refreshed before project planning.

### Phase 1 Proposed Scope

- Authentication and role-based navigation.
- Register Partner.
- Register Business Owner from Partner login.
- Business Owner dashboard shell.
- Business Owner add contact/prospect/customer screen.
- Business Owner add product screen for available products and proposed products.
- Partner hierarchy basics.
- Product & Service registration.
- Customer Manager for contacts, prospects, customers, VIP Customers, and Non-LOS Customers.
- Product eligibility enforcement.
- Business Owner monthly fee tracking.
- Basic PIOS Mart purchase flow.
- Basic reports.
- Sys Admin roles and permissions.

### Phase 2 Proposed Scope

- Partner Performance Manager.
- Advanced reports.
- Promotion module.
- WhatsApp product promotion.
- Promotion response management.
- Sales Manager.
- Prospecting Manager.
- Service Manager.
- Training Manager.
- Fee and payment workflow.
- Account features.
- AI prospecting features.

## 14. Source Implementation Plan

The source PDF shows this schedule:

| Workstream | Apr Wk 1 | Apr Wk 2 | May Wk 1 | May Wk 2 | May Wk 3 | May Wk 4 |
| --- | --- | --- | --- | --- | --- | --- |
| Development Phase 1 Module | Active | Active |  |  |  |  |
| Production Phase 1 Module |  |  | Active | Active | Active | Active |
| Development Phase 2 Module |  |  | Active | Active |  |  |
| Production Phase 2 Module |  |  |  |  | Active | Active |

Note: The final column in the source appears as `NAY WK 4`; this PRD treats it as `May Wk 4` pending confirmation.

## 15. Open Questions

- What are the exact Partner tier criteria and thresholds?
- What does the `1 < 4 < 16 < 64 < 256 = 341` Partner sequence represent?
- Is Business Owner now the primary seller role for v1, replacing the earlier Partner-led customer/product workflow?
- Can Partners still register customers/products directly, or only Business Owners?
- Should Business Owners be counted toward Partner tier thresholds?
- What is the exact definition of LOS MSMY and how will the system verify LOS membership?
- Is VIP Customer registration subject to a RM2 fee, based on the source `VIP Customer (RM2)` label?
- Which fields are mandatory versus optional for Partner, customer, and product records?
- Should PIOS integrate directly with Amway data or will ABO and DD details be manually entered?
- What payment gateway or payment method is required for the Fee and PIOS Mart modules?
- What is the Business Owner monthly fee amount, due date, grace period, and suspension policy?
- Does PIOS need inventory, delivery, order tracking, refunds, or customer self-service?
- Is the available-product `max 5` rule a hard system limit?
- Do proposed products require admin or Partner approval before they can be promoted or sold?
- What does "Business Tax / Manage Promotion Respond" mean in the May 2026 screen flow?
- Should WhatsApp promotion be a deep link/manual send flow, WhatsApp Business API integration, or both?
- What reports are required for the first release, and what export formats are needed?
- What AI prospecting capability is expected: lead scoring, content generation, audience search, or automated follow-up?
- What languages must the product support: Malay, English, or both?
- What compliance requirements apply to Malaysian personal data, payments, and e-commerce operations?
