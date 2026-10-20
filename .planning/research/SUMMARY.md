# Project Research Summary

**Project:** PIOS v1.2 — Business Owner CRM + Product Listing
**Domain:** Lightweight per-seller (Business Owner) CRM + product-listing UX inside an existing Laravel 13 / Filament 5.6 / MySQL multi-panel app
**Researched:** 2026-06-30
**Confidence:** HIGH

## Executive Summary

PIOS v1.2 turns the `/business` (Business Owner) Filament panel from a bare list-and-edit
screen into a real **lightweight personal CRM + seller product catalog**: a customer detail
view with activity notes and follow-up reminders, search/filters/segments, one-tap
WhatsApp/call, richer product cards with categories and status, a share/promote action, and a
simpler BO product-create form. This is **integration work, not greenfield** — every feature
slots into existing conventions (resources, `scopeVisibleTo`, the `isAdmin()` field-visibility
pattern, the `public` upload disk, the v1.1 scheduler/`pios-queue` worker).

The headline finding across all four research streams is **zero new runtime packages**. Every
target feature maps to a Filament 5.6 built-in already installed: customer detail = a
`ViewRecord` page + Infolist; notes/follow-ups = plain Eloquent models + Relation Managers;
segments = list-page `getTabs()` with badge counts; WhatsApp/call/share = Filament `Action`s
emitting `wa.me`/`tel:` deep links built by a tiny `App\Support\Phone` helper; product cards =
the native table `->contentGrid()` layout; image uploads = the existing
`FileUpload->disk('public')`. Plugins for Kanban, calendars, media library, phone validation,
or table-layout-toggle are explicitly rejected. The build follows a consensus 7-slice
dependency order: CRM data layer -> customer detail view -> segments + gated stage-change ->
WhatsApp/call -> product card grid + category/status -> simpler BO form -> share/promote.

The dominant risk is **not** technical difficulty — it is the **BO-ownership invariant**.
Today, ownership is enforced in exactly one place per resource (`getEloquentQuery()->visibleTo()`),
and **every new v1.2 surface is a fresh place that scope can leak**: child note/follow-up tables
with no scope of their own, a hand-rolled detail page that does `Customer::findOrFail()`, a
relaxed form Section that re-exposes the v1.1-isolated `cost`/`vendor`/`classification` columns,
an unauthenticated public share route, or un-normalized phone numbers. Mitigation is consistent
and non-negotiable: route every new surface through `visibleTo`; give notes/follow-ups their own
`scopeVisibleTo` + policy + denormalized `business_owner_id`; build detail as a `ViewRecord`;
re-expose fields individually (never whole Sections); keep `share` to a pre-filled `wa.me`
message (no public page in v1.2); and normalize phones through one tested helper.

## Key Findings

### Recommended Stack

**Zero new runtime packages.** See `.planning/research/STACK.md`. The "stack" for v1.2 is a
mapping of each feature to a Filament 5.6 built-in plus ~30 lines of helper code and two simple
additive migrations. The only active decision is what *not* to add (the explicit do-NOT-add list:
Spatie Media Library, S3, `laravel-phone`, any WhatsApp SDK/Business API, Kanban/calendar/
table-toggle plugins, `laravel-activitylog`).

**Core technologies (all already installed):**
- **Filament 5.6.6** — Infolists, Relation Managers, table `contentGrid()` layout, Actions,
  FileUpload, Database Notifications — covers 100% of v1.2 UX natively.
- **Laravel 13 / PHP 8.4** — trivial Eloquent models for notes/follow-ups; existing per-minute
  scheduler + `pios-queue` worker (from v1.1 fees) reused for any due-follow-up surfacing.
- **MySQL 8 (`pios_my`)** — two simple additive tables (`customer_notes`,
  `customer_follow_ups`); existing `category`/`hp_no`/`status` columns are already indexed.
- **`App\Support\Phone` helper (NEW, ~30 lines)** — normalizes MY numbers to `wa.me`
  (`60...`, no `+`/leading `0`) and `tel:` (`+60...`); single source of truth, `rawurlencode` text.

### Expected Features

See `.planning/research/FEATURES.md`. Framed as a **lightweight personal CRM** (one BO, their
own contacts, their own products), not a team sales CRM.

**Must have (table stakes):**
- **Customer stage management** — inline action; BO sets Lead/Prospect/Customer (VIP/Non-LOS gated)
- **Customer detail view** — `ViewRecord` + Infolist; the anchor screen the panel lacks today
- **Activity notes** — the #1 CRM job-to-be-done ("what did I last say?")
- **Follow-up reminders** — due/overdue list + dashboard count (list-based, no push/email)
- **Search + stage/status filters + segment counts** — "how many prospects do I have?"
- **One-tap WhatsApp + call** — primary channel for MY direct-sales sellers
- **Richer product cards + status management** — visual listing, not a bare table
- **Product categories via `ProductType`** — classification derives from type, not BO-set
- **Share/promote = pre-filled WhatsApp text** — the point of a seller catalog
- **Simpler guided BO product-create form** — hide admin/cost/vendor fields

**Should have (competitive, post-launch):**
- Public tokenized product page — clickable landing beats a text blob (**deferred to v1.x** —
  adds an unauthenticated scope-bypass surface; see Pitfalls)
- Dashboard "Today" action stream; customer order history on detail; richer auto-logged timeline

**Defer (Phase 2+):**
- Full `Promotion` campaign engine (scheduling/discount/response tracking)
- WhatsApp Business API automated sending (deferred per PRD)
- Email/SMS/push reminder notifications

### Architecture Approach

See `.planning/research/ARCHITECTURE.md`. Pure integration: slot five feature groups into the
existing `/business` panel, reusing each existing convention rather than inventing a parallel one.
The authorization layer (`getEloquentQuery()->visibleTo($user)`) stays the single ownership
truth; new child models add their own `scopeVisibleTo`; ownership columns are stamped
server-side, never from form input.

**Major components:**
1. **CRM data layer** — `customer_notes` + `customer_follow_ups` as **TWO separate tables**
   (decided: not polymorphic — different shapes/lifecycles, cleaner indexing/widgets), each with
   `customer_id` + denormalized `business_owner_id` + `created_by`, its own `scopeVisibleTo` +
   policy; plus `FollowUpStatus` enum and the `App\Support\Phone` helper.
2. **Customer detail** — `ViewCustomer extends ViewRecord` + `CustomerInfolist` + the two
   Relation Managers (mirrors the shipped `AuditLog` ViewRecord pattern); LOS/PII entries keep the
   existing `->visible(fn () => ! auth()->user()?->isBusinessOwner())` guard.
3. **List segments + actions** — `ListCustomers::getTabs()` category segments with badge counts;
   `SelectFilter`s; WhatsApp/Call/View row actions; a gated inline stage-change action.
4. **Product listing** — `ProductsTable` converted to `->contentGrid()` card layout +
   classification/type/status filters + a `wa.me` share `Action`.
5. **Simpler BO product form** — extend the existing `isAdmin()` field-visibility guards into a
   clean BO-only essentials section (last, because the grid reveals which fields matter).

> **Cross-file note (resolved):** FEATURES.md (OQ-3) floated a unified `customer_activities`
> table as an alternative. The confirmed milestone decision and ARCHITECTURE.md both mandate
> **two separate tables** — that is the build target. Auto-logged unified timeline is a v1.x
> nice-to-have, not v1.2.

### Critical Pitfalls

See `.planning/research/PITFALLS.md`. Every critical pitfall is an ownership/PII leak on a new
surface — the existing protection lives only in `getEloquentQuery()->visibleTo()`, so anything
that does not route through it is unguarded.

1. **Child records (notes/follow-ups) inherit no ownership scope** — a Relation Manager / Action
   resolves records by raw primary key over Livewire. Give each child model its own
   `scopeVisibleTo` + registered policy + denormalized `business_owner_id`; stamp owner
   server-side. *(P-CRM-Data — highest-risk item.)*
2. **Custom detail page bypasses the resource scope** — `Customer::findOrFail($id)` loads any
   BO's PII. Build it as a `ViewRecord` (resolves through the scoped `getEloquentQuery()` for
   free); never hand-roll; scope every detail widget too. *(P-Detail.)*
3. **Category/status re-exposure drags sensitive fields back into the BO form** — relaxing a whole
   Section's `visible()` re-hydrates and persists `cost`/`vendor`/`classification`, undoing v1.1
   column isolation. Re-expose fields **individually**; keep field-level guards; `unset()`
   admin keys in `mutateFormDataBeforeSave`; **BO must never edit `classification`** (it drives
   Amway eligibility). *(P-Cards.)*
4. **Share/promote public link bypasses the entire scope + policy layer** — a guest hitting
   `Product::scopeVisibleTo` falls through to "all Active products," exposing every BO's catalog +
   cost/margin, enumerable by `id`, Google-indexed. **v1.2 decision: no public page** — ship a
   pre-filled `wa.me` text only. (If ever built: signed URLs, field whitelist, Active+own+shareable
   gate, noindex, throttle.) *(P-Share — its own security-reviewed slice.)*
5. **WhatsApp/tel links from un-normalized numbers + un-encoded text** — `hp_no` is free-form;
   `wa.me` needs `60...` (no `+`/leading `0`). One shared `Phone` normalizer + `rawurlencode` +
   client-side `wa.me` (no server redirect -> no PII in logs) + `rel=noopener` new tab. *(P-WA.)*
6. **Image uploads break/leak on prod** — missing `storage:link` after rsync deploy (404s),
   perms mismatch, no `->maxSize()`, SVG stored-XSS, public-disk exposure. Add idempotent
   `storage:link` to deploy + exclude `storage/` from rsync `--delete`; `->image()->maxSize()` +
   block SVG; keep customer media off the public disk. *(P-Upload.)*

## Implications for Roadmap

Based on combined research, the suggested phase structure follows the consensus dependency order
(data before views; CRM before product work; share isolated as its own security slice):

### Phase 1: CRM Data Layer (P-CRM-Data)
**Rationale:** Everything CRM depends on it; it is also the single highest-risk slice (child-record
ownership leak). Build the foundation with the invariant baked in before any UI.
**Delivers:** `FollowUpStatus` enum; `customer_notes` + `customer_follow_ups` migrations (two
separate tables, each with `customer_id` + denormalized `business_owner_id` + `created_by`);
`CustomerNote`/`CustomerFollowUp` models with `#[Fillable]`, casts, `scopeVisibleTo`, `customer()`;
`Customer::notes()/followUps()`; `App\Support\Phone` helper + thin accessors; registered child
policies.
**Addresses:** F3 (notes), F4 (follow-ups) data foundations; F6 phone helper.
**Avoids:** Pitfall 1 (child scope), Pitfall 10 (Relation Manager actor).

### Phase 2: Customer Detail View (P-Detail)
**Rationale:** The anchor screen the panel lacks today; notes/follow-ups render here. Must be a
`ViewRecord` to inherit the scope.
**Delivers:** `ViewCustomer extends ViewRecord` + `CustomerInfolist`; the two Relation Managers
(server-side owner stamping + `AuditLogger`); WhatsApp/Call header + entry actions.
**Uses:** Filament ViewRecord/Infolist/Relation Manager built-ins (mirrors `AuditLog` pattern).
**Avoids:** Pitfall 2 (custom-page bypass), Pitfall 9 (duplicate-boundary assumption — confirm
per-BO vs per-Partner).

### Phase 3: Segments + Gated Stage-Change (P-Segments)
**Rationale:** "How many prospects?" + the core CRM verb (move a contact through stages). Builds
on the detail/list once data exists.
**Delivers:** `ListCustomers::getTabs()` category segments with badge counts; `SelectFilter`s
(stage/status); inline "Change stage" action **gated** so BO sets Lead/Prospect/Customer only —
VIP/Non-LOS (LOS/ABO-dependent) stay out of scope.
**Addresses:** F1 (stage), F5 (search/filters/segments).
**Avoids:** Pitfall 7 (N+1 — use `withCount`), Pitfall 8 (free-text categories — use enums), and
the eligibility-invariant break from un-gated VIP.

### Phase 4: WhatsApp / Call Actions (P-WA)
**Rationale:** Small, high-value; reuses the Phase-1 `Phone` helper.
**Delivers:** WhatsApp (`wa.me/60...?text=`) + call (`tel:+60...`) Filament Actions on list rows +
detail, `->openUrlInNewTab()`, visible only when `hp_no` is filled.
**Addresses:** F6.
**Avoids:** Pitfall 5 (normalization + `rawurlencode` + client-side, no PII in logs).

### Phase 5: Product Card Grid + Category/Status (P-Cards)
**Rationale:** The "product listing" half; independent of CRM but reuses the Phone helper, so
after Phase 1.
**Delivers:** `ProductsTable` -> `->contentGrid()` card layout (`ImageColumn` on `public` disk,
name/price/status badge); classification/`ProductType`/status `SelectFilter`s; inline status
action.
**Addresses:** F7 (cards + status), F8 (categories via `ProductType`).
**Avoids:** Pitfall 6 (uploads — `->image()->maxSize()`, block SVG, deploy `storage:link`),
Pitfall 7 (N+1 — eager-load), Pitfall 8 (curated categories).

### Phase 6: Simpler BO Product Create Form (P-Cards cont.)
**Rationale:** Last for product work — the grid reveals which fields actually matter to a BO.
**Delivers:** BO-essentials product form (name, type, price, description, image, status);
admin/cost/vendor/territory/commission hidden via the existing `isAdmin()` pattern.
**Addresses:** F10.
**Avoids:** Pitfall 3 — re-expose fields **individually**, keep field-level guards, `unset()`
admin keys server-side, **never let BO write `classification`**.

### Phase 7: Share / Promote (P-Share)
**Rationale:** Its own slice; if a public page is ever added it is a security-reviewed surface —
keep it separate from P-Cards.
**Delivers:** A row `Action` building a pre-filled `wa.me?text=` message (name + RM price +
description, optional public image URL); copy-to-clipboard fallback. **No public product page in
v1.2.**
**Addresses:** F9 baseline.
**Avoids:** Pitfall 4 (the deferred public page = unauthenticated scope bypass — explicitly out
of scope), Pitfall 12 (link-expiry UX — N/A while text-only).

### Phase Ordering Rationale
- **Data before views:** notes/follow-ups models + scope must exist before the detail page and
  widgets render them (Phase 1 -> 2).
- **CRM before product:** CRM is the core v1.2 ask and notes/follow-ups are foundational; the
  Phone helper from Phase 1 is reused by both WhatsApp actions and product share.
- **Listing before form simplification:** the card grid surfaces which fields are worth keeping on
  the simplified BO form (Phase 5 -> 6).
- **Share isolated:** it is the one surface that could become unauthenticated; keeping it last and
  separate forces a deliberate security review and prevents fold-in scope creep.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 1 (P-CRM-Data):** the ownership invariant on net-new child tables is the highest-risk
  item — verify Relation Manager record-resolution + policy behavior against current Filament 5.6
  docs (MEDIUM-HIGH confidence on Filament internals). Confirm the duplicate-boundary decision
  (per-BO vs per-Partner, Pitfall 9) before building on it.
- **Phase 3 (P-Segments):** VIP/Non-LOS stage gating ties to the still-open PRD "LOS verification"
  question — confirm BO-settable stages before coding; default keeps VIP out of scope.

Phases with standard patterns (skip research-phase):
- **Phase 2 (P-Detail):** the repo already ships the exact `ViewRecord` + Infolist pattern
  (`AuditLog`) — copy it.
- **Phase 4 (P-WA):** documented `wa.me` format + a small tested helper.
- **Phase 5/6 (P-Cards):** `contentGrid()` confirmed in installed Filament 5.6; `isAdmin()`
  visibility pattern already in use.
- **Phase 7 (P-Share):** text-only `wa.me` action; no new surface while public page stays deferred.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Verified firsthand against `composer.lock`, existing resources, and installed `vendor/filament` 5.6 source; zero new packages. |
| Features | HIGH | Codebase verified directly; CRM/WhatsApp conventions confirmed against current official sources (WhatsApp Help Center, OnePageCRM, HubSpot). |
| Architecture | HIGH | Every recommendation checked against real repo files + the installed Filament 5.6 source (`HasContent::contentGrid`, RelationManager, `CanOpenUrl`). |
| Pitfalls | HIGH | Grounded in this repo's actual scopes/policies/forms; Filament-behavior claims MEDIUM-HIGH (verify Relation Manager auth + FileUpload dehydration during build). |

**Overall confidence:** HIGH

### Gaps to Address
- **LOS/VIP verification (open PRD question):** whether a BO may self-assert VIP or it needs
  admin/LOS verification is unresolved. Default for v1.2 keeps VIP/Non-LOS out of BO-settable
  stages — flag, don't silently decide. *(Handle in Phase 3 planning.)*
- **Duplicate-detection boundary:** today `EditCustomer` checks HP/IC uniqueness within
  `partner_id`, not `business_owner_id`, which may not match Rule 1. Confirm intended boundary
  before segment/merge features. *(Handle in Phase 2 planning.)*
- **Product status semantics:** `ProductStatus` has both `Inactive` and `Archived` (overlap).
  Default: surface only Draft/Active/Archived to BO; treat `Inactive` as a hidden synonym of
  Draft. *(Handle in Phase 5.)*
- **`status = Active` self-publish vs approval (PRD section 8.5):** if product approval is required,
  cap BO-settable status to Draft/Archived and route publish through the proposal flow. *(Confirm
  in Phase 6.)*
- **Pre-filled WhatsApp greeting + bilingual labels:** keep templates editable and Malay/English-
  ready (CLAUDE.md bilingual open question); don't hardcode English. *(Phases 4 & 7.)*

## Sources

### Primary (HIGH confidence)
- This repository, read firsthand — `app/Models/{Customer,Product,Promotion,User}.php`,
  `app/Filament/Resources/{Customers,Products,AuditLogs}/**`, `app/Policies/{Customer,Product}Policy.php`,
  `app/Services/{AuditLogger,IdentifierGenerator}.php`, `app/Providers/{AppServiceProvider,Filament/BusinessPanelProvider}.php`,
  `config/filesystems.php`, migrations, `composer.lock`, `.planning/PROJECT.md`, `CLAUDE.md`.
- Installed framework — `vendor/filament` 5.6 (`tables/.../HasContent.php` `contentGrid()`,
  `RelationManagers/RelationManager.php`, `actions/.../Concerns/CanOpenUrl.php`).
- [Filament 5.x — Tables: Layout (Split/Stack/Grid/`contentGrid`)](https://filamentphp.com/docs/5.x/tables/layout)
- [WhatsApp Help Center — How to use click to chat](https://faq.whatsapp.com/5913398998672934/) — confirms `wa.me/<intl>?text=`, no `+`/leading `0`.

### Secondary (MEDIUM confidence)
- [OnePageCRM — Follow-Up Reminders](https://www.onepagecrm.com/features/take-action/) — due-date urgency-list pattern.
- [HubSpot KB — Create tasks](https://knowledge.hubspot.com/tasks/create-tasks) — task = due date + notes + reminder.
- [Filament — Table as Grid with Cards (example)](https://filamentexamples.com/project/table-layout-as-grid-with-cards) — corroborating.
- WhatsApp pre-filled-message / URL-encoding guides (MD2WA, whatsform).

### Tertiary (LOW confidence)
- Filament Relation Manager authorization + FileUpload visibility/dehydration coupling — verify
  against current Filament 5.6 docs during Phase 1 build.

---
*Research completed: 2026-06-30*
*Ready for roadmap: yes*
