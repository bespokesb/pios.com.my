# PIOS Architecture & Flow — diagram transcription + implementation status

> Sources: "PIOS High Level Overview" slide (2026-06-29) and an earlier hand-drawn
> whiteboard photo (2026-06-19). This document transcribes both, maps each flow
> element to the current codebase, and records what is built vs. not. Items marked
> **(needs decision)** are genuinely new modelling choices awaiting the author.
>
> Last reviewed: 2026-06-29.

## 0. Roles (clarified by author, 2026-06-29)

- **Owner (Said / HQ)** — system root; registers and approves Partners.
- **Partner** — an **account manager**. Manages, trains, and supports their Business
  Owner clients and their accounts. Recruited by the Owner or by another Partner;
  each Partner has **max 4 legs** (direct recruits). Recruits need HQ approval.
- **Product Partner** — a Partner who is **Verified + paying a recurring membership fee**.
  A *level/status of Partner*, not a separate entity (verified by HQ + an active recurring
  partner membership, analogous to the BO monthly fee).
- **Business Owner (BO)** — the platform's **paying customer** (the PIOS product is, in
  effect, SaaS for BOs: a customer database + product listing). A BO is a **Partner's
  client**, and may be **upgraded to a Partner** — the upgrade is **triggered by the BO's
  managing partner** (subject to verification/HQ approval).
- **Customer / VIP Customer** — the **Business Owner's own** customers (their database).
  "BO is a Customer/VIP Cust to the Partner" = the BO is the Partner's *client/account*,
  not a literal customer record.

**Upgrade chain (progression):** `Customer → Business Owner → Partner → Product Partner`.
A BO's customers themselves have potential to become BOs and Partners over time.

## 1. High Level Overview (primary flow — 2026-06-29 slide)

```
                 ┌─ PARTNER ───────────┐
   OWNER ───────►├─ PARTNER ───────────┤──►  CUSTOMER
  (Said)         └─ PRODUCT PARTNER ───┘     VIP CUSTOMER
                          │  (via pios.com.my)
                          ▼
                    BUSINESS OWNER ──►  CUSTOMER
                                    └─►  PRODUCT
```

Author's notes on the slide (Malay → English):
- *Said daftarkan PARTNER* — Owner (Said) registers Partners.
- *PARTNER pun boleh daftarkan PARTNER BARU (pending kelulusan SAID)* — a Partner can also register a NEW partner, **pending Said's approval**.
- *PARTNER daftarkan business owner (BO)* — Partner registers Business Owners.
- *BO berstatus CUSTOMER/VIP CUST kpd PARTNER* — a BO **has the status of Customer/VIP Customer to the Partner**.
- *BO daftarkan customer & prospek & produk kedalam PIOS* — BO registers customers, prospects, and products into PIOS.
- *Customer & prospek BO Adalah LEAD kepada PARTNER* — the BO's customers & prospects **are LEADS to the Partner**.
- *BO mempromosi produk kepada customer & prospek nya* — BO **promotes products** to their customers & prospects.

### Implementation status of the overview flow

| Flow element | Status | Notes |
| --- | --- | --- |
| Owner registers Partner | ✅ Built | `/admin` panel, `RegistrationApprover`. |
| Partner registers new Partner, **pending Owner approval** | ✅ Built | Max 4 active sub-partners (`Partner::MAX_DIRECT_PARTNERS`); recruits land `Pending` until HQ/Said approves. |
| Partner registers Business Owner | ✅ Built | BO registration also lands Pending → HQ approval. |
| BO registers Customer & Prospect | ✅ Built | `CustomerCategory` Lead/Prospect/Customer/VIP; BO form captures Name + HP (+ optional email). |
| BO registers Product into PIOS | ✅ Built | BO-owned products (`products.business_owner_id`), isolated per BO. |
| Partner sees the BO's customers (roll-up) | ⚠️ Partial | Partner **sees** downstream customers via `customers.partner_id` (`visibleTo`). No explicit "treat as the Partner's LEAD" workflow. |
| **"Product Partner"** (a Verified + Paid Partner level) | ❌ Not built | Clarified: it's a *status of Partner*, not a new entity. Today a Partner is only Pending/Active (HQ approval). Needs a **Verified** flag + a **Paid** signal (settled partner fee) that together mark a partner as a Product Partner. |
| **BO has status Customer / VIP Cust to the Partner** | ✅ Conceptually built | Clarified: the BO is the Partner's *client/account* (and the platform's paying customer), not a literal customer record. Partner→BO management already works. Missing only the **BO→Partner upgrade** path. |
| BO promotes products to customers/prospects | ❌ Not built | Promotion/WhatsApp flow is not in the business panel (PRD Phase 2). |

## 2. Earlier whiteboard detail (2026-06-19)

Adds detail consistent with the overview, plus a sourcing layer:

| Sketch element | Status | Notes |
| --- | --- | --- |
| Owner → Partner (×4 +1) → BO → Customer (Lead/Prospect/Customer/VIP) | ✅ Built | Core hierarchy across `/admin`, `/partner`, `/business` panels. |
| Amway \| Non-Amway product eligibility | ✅ Built | Amway sells only to VIP/LOS customers (`canBuyProduct`). |
| Capture customer Name + HP No | ✅ Built | Simplified BO customer form. |
| **Partner quota — 100 customers/month** ("Syarat Partner") | ⚠️ Advisory only | `PartnerQuotaService` + partner widget + HQ report. **No enforcement/consequence** on a miss (deferred, QUOTA-05). |
| **Fee schedule — RM10 setup + RM5 monthly** | ⚠️ Tracked, not collected | Setup fee on BO approval; idempotent monthly `fees:generate-monthly` command; amounts from `FeeSetting`. **No payment collection/gateway** — obligations only. |
| **Cost / Vendor / merchants** sourcing | ✅ Built (admin-only) | `Vendor` model + admin resource; `products.cost`/`vendor_id`; margin. Cost/vendor/margin never visible in partner/business panels. |
| **GPS / location** element | ❌ Not built | **(needs decision)** — deferred to v2 (SRC-05); meaning unconfirmed. |
| **PIOS offerings: Produk / Servis / Program** | ⚠️ Products only | Services and Programs are not modelled as offerings. |
| **SPP**, **"P" box**, **"semua Ak"**, **L–C–LC–B.B** circles | ❓ Unclear | Could not be read confidently; not built. |

## 3. Cross-cutting gaps (in the diagrams, not yet built)

1. **Business-owner selling flow** — the diagrams centre on customers buying eligible products and BOs promoting/selling. Eligibility + an Order model exist in `/admin` only; the business panel has **no sell/order/promote flow**. Likely the biggest functional gap.
2. **Services & Programs** as offerings (only Products exist).
3. **Quota enforcement** (advisory today).
4. **Fee collection** (tracked today; no gateway).
5. **GPS / location** (deferred).

## 4. Next work implied by the clarified roles

1. **Partner recurring membership + Product Partner status** — add a recurring partner
   **membership fee** (mirror the BO monthly-fee machinery: a `Partner` `FeeType`,
   `FeeSetting` amount, a `partners:generate-monthly`-style command) and a **Verified**
   flag, then derive **Product Partner** = verified + membership current.
   **Blocked:** membership **amount is TBC** — build once confirmed.
2. **BO → Partner upgrade** — **deferred to Phase 2.** An "Upgrade to Partner" action,
   triggered by the managing partner (Pending → HQ approval). On upgrade, **the BO's
   customers stay in the database** (they remain, owned by the upgraded person).
3. **BO selling / promote flow** in the business panel (Orders + WhatsApp promotion).
4. **Services & Programs** offerings; **quota enforcement**; **fee collection**; **GPS**.

### Deferred to Phase 2
- BO → Partner upgrade (above); customers persist in the database on upgrade.

### Still unread on the older whiteboard (low priority)
- **SPP**, the **"P" box**, **"semua Ak"**, and the **L–C–LC–B.B** circles.

### Settled defaults
- Fee currency = **RM/MYR**. Quota = **advisory** (enforcement deferred).
