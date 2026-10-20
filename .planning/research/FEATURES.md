# Feature Research

**Domain:** Lightweight SMB CRM + seller product catalog (Business Owner panel, Laravel + Filament 5)
**Researched:** 2026-06-30
**Confidence:** HIGH (codebase verified directly; CRM/WhatsApp conventions verified against current sources)

> Scope note: this milestone (v1.2) makes the `/business` panel a real CRM + product-listing
> tool for the **Business Owner (BO)** — the platform's paying customer. Everything stays
> strictly BO-scoped via the existing `scopeVisibleTo()` query scopes. This is a
> **lightweight personal CRM**, not a team sales CRM: one BO, their own contacts, their own
> products. That framing drives every "table-stakes vs nice-to-have" call below.

---

## Existing building blocks (verified in codebase)

These already exist and shape what v1.2 must *extend* vs *build new*:

| Asset | State | Relevance |
|-------|-------|-----------|
| `Customer` model + `CustomerResource` (business panel) | BO form = name + phone (+ optional email); category hidden, defaults to `Lead`; trimmed table; **Edit action only, no View page** | Extend: add View/detail page, expose stage, add notes + follow-ups |
| `CustomerCategory` enum | `Lead / Prospect / Customer / VipCustomer / NonLosCustomer` (has `label()`, `options()`) | Reuse as the "stage/segment" dimension |
| `customers` table | has `category` (indexed), `status`, `hp_no` (indexed), `email`, `business_owner_id`, `partner_id` | Stage column already exists & indexed — no migration needed to filter by it |
| `Product` model + `ProductResource` (business panel) | BO can create; `cost/vendor_id/margin` admin-only (column-isolated); `media_assets` (array), `selling_price`, `description`, `status` | Extend: card view, share action, simpler create form |
| `ProductStatus` enum | `Draft / Active / Inactive / Archived` | Already covers status management — needs semantics pinned (Inactive vs Archived overlap) |
| `ProductType` model | admin-managed; carries `classification` (Amway/Non-Amway); `product_type_id` is fillable on Product | This **is** the "product categories" feature — decide if BO picks from it |
| `Promotion` model + `PromotionCustomer` pivot | Heavyweight campaign entity: mode (manual/auto), discount, 2 photos, `max_customers`, send/response tracking | **Decision point:** reuse for "share/promote" or build a lightweight share. See OQ-9 |
| `Order` model (`customer->orders()`) | Exists (admin panel only today) | Can surface on customer detail (nice-to-have) |
| `BusinessStatsOverview` widget | Shipped in v1.1 | Extend with segment counts + due-follow-up count |
| **No** notes/activity table, **no** reminders/tasks table | — | Both are net-new migrations |

---

## Feature Landscape

### Table Stakes (Users Expect These)

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| **Set / change customer stage** (Lead→Prospect→Customer…) | A CRM where you can't move a contact through stages isn't a CRM. Today the BO literally cannot change category. | LOW | Inline row action + on edit/detail. VIP/Non-LOS need gating (OQ-2). Column already exists. |
| **Customer detail / profile view** | Every CRM has a "open the contact" screen. Filament BO panel currently has none (edit only). | MEDIUM | New `ViewRecord` page + Infolist + relation managers. Anchor for notes/follow-ups/quick actions. |
| **Activity notes (timeline)** | "What did I last say to this person?" is the #1 CRM job-to-be-done. | MEDIUM | New `customer_notes` table; timeline on detail view. |
| **Follow-up reminders** (due date + list of due/overdue) | Confirmed source pattern: every SMB CRM task has a **due date + note + done state**, surfaced as an urgency-sorted list. | MEDIUM | New `customer_follow_ups` table; "Due today / Overdue" surfaced on dashboard. List-based, not push (OQ-4). |
| **Search by name / phone** | Baseline lookup. Partially exists (searchable columns). | LOW | Already mostly present; ensure phone search normalizes formatting. |
| **Filter by stage + status** | Segmenting by category is the explicit ask. | LOW | Filament `SelectFilter` on existing indexed columns. |
| **Segment counts** (N Leads, N Prospects…) | "How many prospects do I have?" — the core segment ask. | LOW | Filament list **tabs with badge counts**, or widget stats. |
| **One-tap WhatsApp** from phone | Primary channel for this audience (MY direct-sales). Confirmed `wa.me/<intl-number>?text=` pattern. | LOW | URL action; **must normalize MY numbers** (strip leading 0, prepend 60) — see Pitfall. |
| **One-tap call** from phone | Mobile-first sellers. | LOW | `tel:` link action. |
| **Richer product cards** (image/price/description/status) | A "product listing tool" implies visual cards, not a bare table. | MEDIUM | Filament grid layout / custom card view over existing fields + `media_assets`. |
| **Product status management** (draft/active/archived) | Sellers need to hide WIP/retired items. Enum exists. | LOW | Pin semantics (OQ-6); add status filter + inline status action. |
| **Share / promote a product** (pre-filled WhatsApp) | The whole point of a seller catalog is sharing it. PRD §FR-066. | LOW–MEDIUM | v1.2 = generated WhatsApp text (name + price + link/image). Public page = separate decision (OQ-8/9). |
| **Simpler guided BO product create form** | BO shouldn't see admin/cost/territory fields. Column-isolation already done for cost. | LOW | Hide admin fields (mirror the customer-form `visibleToNonBusinessOwner()` pattern). |

### Differentiators (Competitive Advantage)

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| **Public shareable product page** (tokenized link) | A real clickable landing page (image, price, "WhatsApp me") beats a copy-pasted text blob; works when the recipient isn't a contact yet. | MEDIUM–HIGH | New **unauthenticated** route + share token; must show only safe fields (never cost/vendor/margin). New public surface = privacy/SEO care. See OQ-8. |
| **Unified activity timeline** (notes + auto-logged stage changes + sent WhatsApp) | "Everything that happened with this contact, in order" feels premium and costs little if modeled once. | MEDIUM | One `customer_activities` table with a `type` enum instead of separate note/log tables. See OQ-3. |
| **Dashboard "Today" view** (due/overdue follow-ups + new leads) | Turns the CRM into a daily action list — the OnePageCRM "action stream" pattern that makes SMBs actually log in. | MEDIUM | Filament widget over follow-ups. High retention value for the paying BO. |
| **Per-segment one-tap bulk WhatsApp share** | "Send this product to all my Prospects" — but as copy/open, not automated send. | MEDIUM | Bridges to existing `Promotion`/`PromotionCustomer` if reused. Risks scope creep (see Anti-Features). |
| **Customer order history on detail view** | Ties CRM to the `Order` model already present; shows lifetime value. | LOW–MEDIUM | Relation manager; read-only in v1.2. |

### Anti-Features (Commonly Requested, Often Problematic)

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| **Kanban pipeline board** | "CRM = drag cards between columns" mental model | Explicitly out of scope; drag-drop on 100s of contacts is heavy in Filament; stage is a filter dimension, not a workspace | List + tabs/filters by stage + segment counts (already the plan) |
| **Automated WhatsApp sending** (Business API) | "Just blast my promo to everyone" | PRD defers WA Business API; per-message templates, opt-in, MY DNC/PDPA, cost, approval — all unresolved | v1.2 generates the **link/text**; the BO sends from their own WhatsApp (deep link). Keep human-in-loop. |
| **Push/email/SMS reminder notifications** | "Remind me so I don't forget" | Needs queue + channel config + delivery reliability + user prefs; over-builds a v1.2 list feature | List-based due/overdue surfaced in-app (dashboard + badge). Notifications = Phase 2. |
| **Full marketing-campaign engine** (the existing `Promotion` machinery: scheduling, discount, max-customers, response tracking) for "share a product" | The model already exists, tempting to wire up | "Share/promote" in v1.2 is a *one-tap personal share*, not a tracked campaign. Wiring the full pipeline balloons scope and re-opens deferred WA-API questions | Lightweight share action now; reserve `Promotion` for the Phase-2 targeted-promotion flow. See OQ-9. |
| **BO freely sets customers to VIP** | "Let me just mark them VIP" | VIP/Non-LOS gate **Amway eligibility** (`canBuyProduct`) and require ABO/LOS verification (admin-only fields today). Free-set breaks the eligibility invariant. | BO sets Lead/Prospect/Customer freely; VIP/Non-LOS requires ABO No capture + (LOS verification rule — still an open PRD question). See OQ-2. |
| **Free-text product categories per BO** | "Let me name my own categories" | Fragments the catalog, breaks Amway/Non-Amway `classification` derivation tied to `ProductType` | BO selects from admin-defined `ProductType`; classification derives from it. See OQ-7. |
| **Rich-text / attachments on notes** | "Let me format and attach files" | Storage, sanitization, moderation scope for a v1.2 note | Plain textarea notes (optional single timestamp + author). Upgrade later. |

---

## Per-Feature Capability Groups (for requirements author)

### F1 — Customer Stage Management
- **Capabilities:** inline "Change stage" row action on the list; stage selector on detail + edit; stage badge everywhere; auto-log stage change to activity (nice-to-have).
- **Complexity:** LOW (data exists). MEDIUM if VIP path + ABO capture added.
- **Depends on:** `Customer` model, `CustomerCategory` enum (no migration). VIP path depends on LOS-detail fields currently admin-only.
- **Decisions:** OQ-1 (inline vs form), OQ-2 (which stages BO may set).

### F2 — Customer Detail View
- **Capabilities:** Filament `ViewRecord` + Infolist header (name, stage badge, phone w/ WhatsApp+call buttons); contact block; quick actions (set stage, add note, add follow-up); activity timeline; upcoming/overdue follow-ups panel; (nice-to-have) order history.
- **Complexity:** MEDIUM.
- **Depends on:** F1, F3, F4; new relation managers; `Order` model for the optional history block.
- **Decisions:** OQ-5 (what's on the detail view).

### F3 — Activity Notes
- **Capabilities:** add note (textarea); chronological timeline; author + timestamp; edit/delete own note.
- **Complexity:** MEDIUM (new table + UI).
- **Depends on:** F2 (rendered there). Migration: `customer_notes` or unified `customer_activities`.
- **Decisions:** OQ-3 (separate notes table vs unified activity timeline).

### F4 — Follow-up Reminders
- **Capabilities:** create follow-up (due date, note, link to customer); mark done; list "Due today / Overdue / Upcoming"; dashboard count badge; filter customers by "has overdue follow-up" (nice-to-have).
- **Complexity:** MEDIUM.
- **Depends on:** F2; new `customer_follow_ups` table; `BusinessStatsOverview` widget for counts.
- **Decisions:** OQ-4 (fields + notify vs list-only).

### F5 — Search, Filters & Segments
- **Capabilities:** search name/phone (normalize phone); `SelectFilter` by stage & status; list tabs per stage with badge counts; segment summary on dashboard.
- **Complexity:** LOW.
- **Depends on:** existing indexed columns. No migration.
- **Decisions:** none blocking; phone-normalization shared with F6 (see Pitfall).

### F6 — One-Tap WhatsApp / Call
- **Capabilities:** WhatsApp action (`wa.me/<60…>?text=`), call action (`tel:`), on list row + detail.
- **Complexity:** LOW.
- **Depends on:** shared MY phone-normalization helper.
- **Decisions:** OQ-10 (default pre-filled greeting text, if any).

### F7 — Richer Product Cards + Status
- **Capabilities:** card/grid view (image from `media_assets`, name, `selling_price`, status badge, short description); status filter; inline status action (draft↔active↔archived).
- **Complexity:** MEDIUM (cards), LOW (status).
- **Depends on:** `Product`, `ProductStatus`, `media_assets`.
- **Decisions:** OQ-6 (status semantics: Inactive vs Archived).

### F8 — Product Categories
- **Capabilities:** BO assigns a `ProductType` on create/edit; filter/group products by type; classification (Amway/Non-Amway) derived from type.
- **Complexity:** LOW.
- **Depends on:** `ProductType` (admin-managed), `product_type_id`.
- **Decisions:** OQ-7 (BO picks admin type vs derive classification independently).

### F9 — Share / Promote a Product
- **Capabilities (v1.2 baseline):** "Share" action → generates pre-filled WhatsApp text (name + price + description + image URL and/or public link) the BO sends from their own WhatsApp; copy-to-clipboard fallback.
- **Capabilities (differentiator):** public tokenized product page (safe fields only).
- **Complexity:** LOW (text), MEDIUM–HIGH (public page).
- **Depends on:** F7; phone helper; **public route + share token** if page is built; column-isolation must hold (never expose cost/vendor/margin).
- **Decisions:** OQ-8 (text-only vs public page), OQ-9 (reuse `Promotion` or lightweight share).

### F10 — Simpler Guided BO Product Create Form
- **Capabilities:** essentials-only form (name, type, price, description, image, status); hide admin fields (cost/vendor/territory/commission) — mirror `visibleToNonBusinessOwner()`.
- **Complexity:** LOW.
- **Depends on:** F7, F8; existing column-isolation pattern.
- **Decisions:** none blocking.

---

## OPEN QUESTIONS (each with a recommended default)

> These are the ambiguous behaviors the milestone must pin down. Defaults chosen to keep
> v1.2 lightweight, preserve existing invariants (ownership + Amway eligibility), and avoid
> re-opening deferred PRD questions (WA Business API, LOS verification).

**OQ-1 — How does the BO change a stage: inline action or edit form?**
→ **Default: both, but lead with an inline row/detail action.** A one-click "Change stage" action (Filament `Action` with a select) on the list row and detail header, plus the field on the edit form. Inline matches the CRM mental model (move a contact in one tap); the form covers bulk edits.

**OQ-2 — Which stages may a BO set?**
→ **Default: BO may set `Lead`, `Prospect`, `Customer` freely. `VIP Customer` requires capturing `abo_no` (LOS) and is the only path that unlocks Amway buying; `Non-LOS Customer` is selectable too but unlocks nothing extra.** Because VIP gates `canBuyProduct(Amway)`, expose the LOS-detail fields (currently admin-only) when a BO selects VIP/Non-LOS, and require `abo_no` for VIP. *Caveat:* whether the BO is *trusted* to self-assert VIP, or VIP needs admin/LOS verification, ties to the still-open PRD "LOS verification" question — flag, don't silently decide.

**OQ-3 — Separate `customer_notes` + `customer_follow_ups` tables, or one unified `customer_activities` table?**
→ **Default: one unified `customer_activities` table** with `type` (`note`, `follow_up`, `stage_change`, `whatsapp`, `call`), `body`, `due_on` (nullable, for follow_up), `completed_at` (nullable), `customer_id`, `created_by`, timestamps. Single timeline, extensible, less migration churn, and stage-change/contact events auto-log for free. (If the team prefers maximum simplicity, two small tables also work — but the unified model is barely more code and unlocks the "differentiator" timeline.)

**OQ-4 — Follow-up fields, and do we notify or just list?**
→ **Default fields: `due_on` (date; time optional, default none), `note` (text), `completed_at` (nullable = done flag), `customer_id`, `created_by`.** **Default behavior: list-only — no push/email/WhatsApp notifications in v1.2.** Surface as a dashboard "Due today / Overdue / Upcoming" widget + a count badge; sort overdue to the top (the confirmed OnePageCRM urgency-list pattern). Notifications = Phase 2. (Optional cheap add: Filament in-app database notification on login if any overdue — nice-to-have, not table stakes.)

**OQ-5 — What belongs on the customer detail view?**
→ **Default:** header (name, stage badge, status); **phone with WhatsApp + call buttons**; contact block (phone, email); quick actions (change stage, add note, add follow-up); **activity timeline** (notes + auto-logged events, newest first); **follow-ups panel** (upcoming + overdue); **(nice-to-have)** order history (read-only) and "owned by" provenance. Keep it single-screen and action-oriented, not a giant form.

**OQ-6 — Product status semantics (enum has Draft/Active/Inactive/Archived — Inactive vs Archived overlap)?**
→ **Default: use three for the BO — `Draft` (private, not shareable), `Active` (shareable/promotable, the only sellable state), `Archived` (hidden, retired).** Treat `Inactive` as a deprecated synonym of `Draft` (don't expose it in the BO UI) to avoid a confusing four-way choice. Only `Active` products are shareable/promotable.

**OQ-7 — Product categories: BO picks from admin `ProductType`, or something BO-defined?**
→ **Default: BO selects from admin-defined `ProductType`; `classification` (Amway/Non-Amway) derives from the chosen type** (don't let BO set classification directly — it drives eligibility). This keeps the catalog coherent and the Amway invariant intact. No BO free-text categories.

**OQ-8 — What does "share/promote a product" actually produce: WhatsApp text only, or a public product page link?**
→ **Default (v1.2 baseline, ship this): a pre-filled WhatsApp message** — product name + `selling_price` + short description + image URL — opened via `wa.me/...?text=` (and a copy-to-clipboard fallback). **Recommended stretch / strong nice-to-have: a tokenized public product page** (`/p/{token}`) showing image, name, price, description and a "WhatsApp me" button, with the share text linking to it. The public page is the better UX (clickable, works for non-contacts) but adds an **unauthenticated surface** — it must render **only safe fields** (never cost/vendor/margin/business internals) and respect product status (only `Active` is publicly viewable). If timeline is tight, ship text-only first; the page is a clean follow-on.

**OQ-9 — Reuse the existing heavyweight `Promotion` model for share, or build a lightweight share?**
→ **Default: build a lightweight share action; do NOT wire up the full `Promotion`/`PromotionCustomer` campaign machinery in v1.2.** The existing `Promotion` (scheduling, discount, max-customers, send/response tracking, manual/auto mode) is the Phase-2 *targeted promotion* flow and re-opens deferred WA-Business-API questions. v1.2 "share" is a stateless, human-in-the-loop deep link. (Optional: log each share as a `whatsapp`-type activity on the customer when shared to a specific contact — cheap, useful, no campaign state.)

**OQ-10 — Default pre-filled WhatsApp greeting text?**
→ **Default: a minimal, editable template** (e.g. customer-share: "Hi {name}, …"; product-share: "{product} — RM{price}\n{description}\n{link}"). Keep it overridable and **bilingual-ready** (Malay/English) — don't hardcode English (per CLAUDE.md bilingual open question). The BO can edit before sending since the deep link only pre-fills.

**OQ-11 — Scope of "share to a customer" vs "generic share"?**
→ **Default: generic share is table stakes** (BO gets a link/text to paste anywhere). **Share-to-specific-customer** (pre-fills their number + name, logs an activity) is a nice-to-have built on the same helper. Bulk/segment share = differentiator, watch for scope creep into the Promotion engine (OQ-9).

---

## Feature Dependencies

```
F2 Customer Detail View
   ├── requires ── F1 Stage Management (stage badge + action live here)
   ├── requires ── F3 Activity Notes (timeline)
   └── requires ── F4 Follow-ups (panel + dashboard counts)

F3 Notes ──may-merge-into──> F4 Follow-ups   (unified customer_activities table — OQ-3)

F5 Search/Filters/Segments ── uses ── existing indexed columns (category, hp_no, status)
F6 WhatsApp/Call ── requires ── shared MY phone-normalization helper
F9 Share Product ── requires ── F6 helper + F7 cards
F9 (public page) ── requires ── new public route + share token (must respect column-isolation)
F10 Simple Create Form ── requires ── F7 + F8 + existing visibleToNonBusinessOwner() pattern

F1 VIP path ── conflicts-if-unguarded ── Amway eligibility invariant (canBuyProduct) → gate via OQ-2
"Share via full Promotion engine" ── conflicts ── "lightweight v1.2" → resolved by OQ-9
```

---

## MVP Definition

### Launch With (v1.2)
- [ ] F1 Stage management (inline action; Lead/Prospect/Customer; VIP gated) — core CRM verb
- [ ] F2 Customer detail view — the anchor screen the panel currently lacks
- [ ] F3 Activity notes (unified table) — #1 CRM job-to-be-done
- [ ] F4 Follow-up reminders (due/overdue list + dashboard count; no notifications) — keeps the BO returning
- [ ] F5 Search + stage/status filters + segment counts — "how many prospects?"
- [ ] F6 One-tap WhatsApp + call (with MY phone normalization) — primary channel
- [ ] F7 Richer product cards + status management
- [ ] F8 Product categories via `ProductType`
- [ ] F9 Share product = pre-filled WhatsApp text (baseline)
- [ ] F10 Simpler guided BO product create form

### Add After Validation (v1.x)
- [ ] F9 public tokenized product page — once text-share usage proves demand
- [ ] Unified timeline auto-logging (stage changes, shares) surfaced richly
- [ ] Dashboard "Today" action stream
- [ ] Customer order history on detail view
- [ ] In-app (Filament) notification for overdue follow-ups

### Future Consideration (Phase 2+)
- [ ] Targeted promotions via the full `Promotion` engine + send/response tracking
- [ ] WhatsApp Business API automated sending (deferred per PRD)
- [ ] Email/SMS/push reminder notifications
- [ ] BO→Partner upgrade (already Phase 2 per architecture sketch)

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| F1 Stage management | HIGH | LOW | P1 |
| F2 Customer detail view | HIGH | MEDIUM | P1 |
| F3 Activity notes | HIGH | MEDIUM | P1 |
| F4 Follow-up reminders (list) | HIGH | MEDIUM | P1 |
| F5 Search/filters/segments | HIGH | LOW | P1 |
| F6 WhatsApp/call one-tap | HIGH | LOW | P1 |
| F7 Product cards + status | MEDIUM | MEDIUM | P1 |
| F8 Product categories (ProductType) | MEDIUM | LOW | P1 |
| F9 Share product (WhatsApp text) | HIGH | LOW | P1 |
| F10 Simpler create form | MEDIUM | LOW | P1 |
| F9 public product page | HIGH | MEDIUM–HIGH | P2 |
| Dashboard "Today" stream | MEDIUM | MEDIUM | P2 |
| Order history on detail | MEDIUM | LOW–MEDIUM | P2 |
| Follow-up notifications | MEDIUM | HIGH | P3 |
| Full Promotion campaign engine | MEDIUM | HIGH | P3 |

---

## Key Pitfall to Flag (carry into PITFALLS / requirements)

**Malaysian phone normalization for `wa.me` and `tel:`.** Verified: `wa.me` requires international format **without `+`, without leading `0`** (e.g. stored `012-3456789` / `0123456789` → `60123456789`). Numbers are entered free-form in `hp_no` today (no format enforced). A shared normalization helper (strip non-digits, drop leading `0`, prepend `60`) is a hard dependency for F6 and F9 and a likely source of "WhatsApp link doesn't open the right chat" bugs. `tel:` is more forgiving but should use the same cleaned value. Edge cases: numbers already stored with `60`/`+60`, non-MY numbers, empty/invalid numbers (disable the action).

**Public product page = new unauthenticated surface (if OQ-8 stretch is built).** Column-isolation for cost/vendor/margin is currently enforced only inside the authenticated panels. A public route must independently guarantee those fields never serialize, and must respect product `status` (only `Active` public). Treat as a security-review item.

---

## Sources

- Codebase (verified directly): `CustomerForm`, `CustomersTable`, `Customer`, `Product`, `ProductType`, `Promotion` models; `CustomerCategory`/`ProductStatus`/`ProductClassification`/`PromotionMode` enums; `customers` migration; `BusinessPanelProvider`; `PROJECT.md`; `PIOS_ARCHITECTURE_SKETCH.md`; `CLAUDE.md`. — HIGH
- [WhatsApp Help Center — How to use click to chat](https://faq.whatsapp.com/5913398998672934/) — HIGH (official; confirms `wa.me/<intl-no>?text=`, no `+`/leading `0`)
- [How to Create a WhatsApp Link with Pre-Filled Message (2026)](https://whatsform.com/blog/whatsapp-link-pre-filled-message/) — MEDIUM (URL-encoding + format details)
- [WhatsApp Click-to-Chat Links (wa.me): Complete Guide — MD2WA](https://markdowntowhatsapp.com/en/blog/whatsapp-click-to-chat-links-wa-me) — MEDIUM
- [OnePageCRM — Follow-Up Reminders feature](https://www.onepagecrm.com/features/take-action/) — MEDIUM (due-date + urgency-list pattern)
- [HubSpot Knowledge Base — Create tasks](https://knowledge.hubspot.com/tasks/create-tasks) — MEDIUM (task = due date + notes + reminder, record-associated)
- [SuperOffice — Task and reminder automation](https://www.superoffice.com/blog/task-and-reminder-automation-for-sales/) — MEDIUM (SMB reminder conventions)

---
*Feature research for: lightweight SMB CRM + seller product catalog (PIOS Business Owner panel, v1.2)*
*Researched: 2026-06-30*
