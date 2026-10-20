# Domain Pitfalls

**Domain:** BO-facing CRM + product listing inside a Filament 5 panel with a hard per-Business-Owner ownership invariant (PIOS v1.2)
**Researched:** 2026-06-30
**Overall confidence:** HIGH (grounded in this repo's actual resources/policies/forms; Filament-behavior claims MEDIUM-HIGH)

> Scope: common, high-impact mistakes when ADDING customer notes / follow-up reminders, a customer **detail/view** page, customer **filters/segments**, **WhatsApp/call** actions, **product cards/grid**, a **simpler BO product form** that re-enables category + status, **image uploads**, and **share/promote**. Every one of these is a new surface where the BO-ownership invariant can leak, because the existing protection lives almost entirely in `Resource::getEloquentQuery()->visibleTo($user)` (see `ProductResource`, `CustomerResource`). Anything that does not route through that scope is unguarded. Phases referenced: **P-CRM-Data** (notes + follow-ups model/policies), **P-Detail** (customer view page), **P-Segments** (filters/segments), **P-WA** (WhatsApp/call actions), **P-Cards** (product card/grid + simpler form + category/status), **P-Upload** (image uploads), **P-Share** (share/promote).

---

## Critical Pitfalls

Mistakes that cause an ownership leak, PII disclosure, or an authorization regression.

### Pitfall 1: Child records (notes / follow-ups) inherit no ownership scope
**What goes wrong:** A BO opens or edits another BO's customer note/reminder by guessing a record key. The new `CustomerNote` / `FollowUp` models have a `customer_id` but no `scopeVisibleTo`, no policy, and any direct entry point — a Filament Action invoked over Livewire with an arbitrary `recordKey`, a custom Livewire component, a ViewAction modal, or a notes Relation Manager that authorizes on the child's `viewAny` rather than the parent's ownership — resolves the record with a bare `Model::find($key)`.
**Why it happens:** In this codebase ownership is enforced in exactly one place: the resource's `getEloquentQuery()` override (`ProductResource`, `CustomerResource`). Child records get no resource, so they get no scope for free. Developers assume "it's nested under the customer, so it's safe," but Filament Relation Managers and Actions resolve records by primary key over the wire; nesting in the UI is not an authorization boundary.
**Consequences:** Cross-BO read/write of customer activity (which itself contains customer PII context) — a direct breach of the Rule 1 ownership invariant in CLAUDE.md.
**Prevention:**
- Give every child model its own `scopeVisibleTo(Builder, User)` that filters through the owning `business_owner_id`. Denormalize `business_owner_id` onto the note/follow-up at creation so the scope is a single `where`, mirroring `Customer::scopeVisibleTo`.
- Register a `CustomerNotePolicy` / `FollowUpPolicy` in `AppServiceProvider::boot()` exactly like the 17 existing `Gate::policy(...)` lines. Implement `view`/`update`/`delete` that re-check `business_owner_id === $user->businessOwner?->id` (copy `ProductPolicy::ownsProduct`).
- For Relation Managers: apply `visibleTo` in the relation's query (`modifyQueryUsing`/`getEloquentQuery`); do **not** rely on the parent being scoped — the relation query starts from the child table.
- Stamp `business_owner_id` server-side in a create mutator, never from form input (same pattern as `CreateProduct::mutateFormDataBeforeCreate`).
**Detection:** `Note::find(`, `FollowUp::findOrFail(`, `->where('customer_id', $id)` without a `visibleTo`, a Relation Manager with no query modifier, a missing `Gate::policy` line for the new model, or a policy `view()` that returns `true` for any BO.
**Owning phase:** **P-CRM-Data** (highest-risk item in the milestone).

### Pitfall 2: The new customer detail (View/Infolist) page bypasses the resource scope
**What goes wrong:** The CRM "customer detail view" is built as a **custom Filament Page or Livewire component** that does `Customer::findOrFail($this->record)` (or route-model-binds the raw model), so a BO loads any customer — including `kp_no`, `address`, `abo_no`, `dd_*` PII — by editing the URL id.
**Why it happens:** Today `CustomerResource` only has List/Create/Edit pages; there is **no ViewCustomer page yet**. Edit is safe only because Filament resolves its record through `CustomerResource::getEloquentQuery()` (which applies `visibleTo`). A hand-rolled detail page is the natural way to build a "CRM profile" screen, and it skips that resolver entirely.
**Consequences:** Cross-BO customer PII disclosure; violates CLAUDE.md's "enforce server-side, not just UI hiding" constraint.
**Prevention:**
- Build the detail screen as a **Resource `ViewRecord` page** (`Pages\ViewCustomer extends ViewRecord`) registered in `CustomerResource::getPages()`. It resolves through the already-scoped `getEloquentQuery()` for free, and `CustomerPolicy::view()` (already `Customer::query()->visibleTo($user)->whereKey(...)->exists()`) gates it.
- If a custom page is unavoidable, resolve via `Customer::query()->visibleTo(auth()->user())->findOrFail($id)` — never `Customer::findOrFail($id)` — and call `authorize('view', $record)`.
- Audit every widget feeding the detail screen (counts, recent notes, last order) for the same `visibleTo` scope; a stats widget doing `Customer::where('id', $id)` re-introduces the leak.
**Detection:** A new class under `app/Filament/Pages` (not a `Resources/.../Pages` ViewRecord), `Customer::find`/`findOrFail` outside a scoped query, unscoped route-model binding on `{customer}`, or a detail widget querying by raw id.
**Owning phase:** **P-Detail**.

### Pitfall 3: Category/status re-exposure drags sensitive fields back into the BO form
**What goes wrong:** To let BOs manage product **category (product_type) + status** and customer **category**, a developer relaxes the visibility guard on a whole form Section. But `CustomerForm` hides the entire "Category" section (and Owner, LOS, Address, IDs) behind `visibleToNonBusinessOwner()`, and `ProductForm` hides `cost` and `vendor_id` behind `->visible(isAdmin())`. Flipping a Section's `visible()` re-exposes — and, critically, **re-hydrates and persists** — every child field, undoing the v1.1 cost/vendor/margin "column-isolation" work (commit 09-02).
**Why it happens:** Filament couples *visibility* with *dehydration*: a hidden field isn't submitted, so hiding doubles as a write-guard. The cheapest way to "show category to BOs" is to drop the `visibleToNonBusinessOwner()` guard on the Section that happens to also contain `status`, `kp_no`, `address`, `abo_no` (customer) or `cost`, `vendor_id` (product). One toggle silently re-opens write access to sensitive columns.
**Consequences:** BOs see/write PIOS purchase cost, vendor, and margin (commercial-confidentiality breach) or customer IC/address/ABO; regresses the v1.1 authorization isolation.
**Prevention:**
- Re-expose fields **individually**, not by Section. Add a narrow "Categorisation" Section containing only `product_type_id` + `status` (and `category` for customers) made BO-visible, and leave `cost`/`vendor_id` on their own `->visible(isAdmin())` guards untouched.
- Keep field-level guards on every sensitive field even if its Section becomes BO-visible (defense-in-depth, not a single switch).
- Enforce real write rules in `mutateFormDataBeforeSave` by `unset()`-ing admin-only keys when `isBusinessOwner()` (pages already do this for `product_no`/`customer_no`). A BO submitting a crafted Livewire payload can otherwise set any column in the model's `#[Fillable]`.
- **Do not let BOs edit `classification` (Amway/Non-Amway).** It drives Rule 2 eligibility; a BO relabeling an Amway product as Non-Amway bypasses the VIP-only restriction. Keep it admin/proposal-gated.
- Decide whether BO setting `status = Active` self-publishes a product that PRD §8.5 may require approval for; if approval is unresolved, cap BO-settable status to `draft`/`archived` and route "publish" through the proposal flow.
**Detection:** A diff that deletes a `visibleToNonBusinessOwner()` / `->visible(isAdmin())` call instead of adding a scoped sibling field; `cost`, `vendor_id`, `kp_no`, `address`, `abo_no`, or `classification` in a BO save payload; deleted/failing tests asserting BO-form fields are absent.
**Owning phase:** **P-Cards** (simpler BO product form).

### Pitfall 4: Share/promote public link bypasses the entire scope + policy layer
**What goes wrong:** "Share a product" creates a public, unauthenticated URL that returns the model and renders a Blade view — sidestepping every policy and `visibleTo` scope. Common failures: the URL uses autoincrement `id` (enumerable → scrape the whole cross-BO catalog), it serves draft/archived products, it exposes `cost`/`vendor_id`/`sales_commission`/`pricing_structure` because the view dumps `$product` attributes, and it leaks the owning BO's data. Google indexes the links, turning "share with one customer" into a public catalog.
**Why it happens:** The security model here is *entirely* the authenticated panel + `getEloquentQuery` scope; a public route inherits none of it. And `Product::scopeVisibleTo` for a guest falls through to `where status Active` — returning **every** BO's active product, not just the sharer's.
**Consequences:** Commercial-confidentiality breach (cost/margin), cross-BO catalog disclosure/scraping, customer-facing PII leak.
**Prevention:**
- Use **signed, expiring URLs** (`URL::temporarySignedRoute`) or an opaque per-share token (ULID/random), never the raw `id`. Validate the signature in the controller.
- Build an explicit **public DTO / field whitelist** (name, description, selling price, image, BO display name) — never pass the Eloquent model to the view. Never expose `cost`, `vendor_id`, `sales_commission`, `pricing_structure`, `created_by`, internal ids.
- Gate strictly: `status === Active` **and** an explicit per-product "shareable" flag, scoped to the sharing BO. 404 otherwise.
- Add `noindex` headers/meta and rate-limit (Cloudflare + Laravel throttle) to deter scraping.
- If "share" only needs to hand a customer a link, prefer a pre-filled WhatsApp message containing the price + a signed link, rather than standing up a public catalog page at all.
**Detection:** A route in `routes/web.php` outside `auth` middleware returning product data, implicit model binding on `/{product}`, a Blade referencing `$product->cost`/`->vendor`, sequential ids in URLs, or no expiry/signature.
**Owning phase:** **P-Share** (own security-reviewed slice; do not fold into P-Cards).

### Pitfall 5: WhatsApp / tel links built from un-normalized Malaysian numbers and un-encoded text
**What goes wrong:** `hp_no` is a free `string(255)` with only `->tel()` (no normalization). Users enter `012-345 6789`, `0123456789`, `+60 12 345 6789`, `60123456789`. `wa.me` requires `60123456789` (international, no `+`, no leading `0`); `tel:` wants `+60123456789`. Naive concatenation yields broken links (`wa.me/0123456789` opens nothing / wrong country). The pre-filled message (customer name + product price) goes into the URL without `rawurlencode`, so spaces/`&`/`#`/newlines break or truncate it.
**Why it happens:** The number was captured as display text and `wa.me` "just works" in dev with a clean number, so normalization is skipped. URL encoding is forgotten because short test messages have no special chars.
**Consequences:** Broken/empty chats, wrong-country links, truncated messages — and, if a server-side redirect is added, customer PII in nginx/Cloudflare logs.
**Prevention:**
- Add one shared normalizer (`PhoneNumber::toWhatsApp($hp)` → strip non-digits, drop one leading `0`, prefix `60` if absent; `toTel($hp)` → `+60...`) used by every action, not per-call.
- `rawurlencode()` the message text; build `https://wa.me/{normalized}?text={encoded}`.
- Normalize `hp_no` on save so stored data trends to one format, but still normalize at link-build time for legacy rows.
- Open in a new tab with `rel="noopener noreferrer"`: Filament `Action::url(...)->openUrlInNewTab()`; for raw anchors set both `target="_blank"` and `rel`.
- Keep the message build **client-side / redirect-free** — link straight to `wa.me` so PII never transits a server route, app logs, or Cloudflare access logs. If a server redirect is added, do not log the `text` param.
**Detection:** `"wa.me/" . $customer->hp_no`, `tel:` with a leading `0`, raw spaces in a URL, missing `rel=noopener`, a `/share/whatsapp?text=...` route hitting logs, broken links for dashed numbers.
**Owning phase:** **P-WA**.

### Pitfall 6: Product image uploads break or leak on the production server
**What goes wrong:** Several independent failures:
- **Missing `storage:link` after deploy** — rsync deploys (per MEMORY) don't recreate the `public/storage` → `storage/app/public` symlink, or copy a dangling/relative one, so every image 404s.
- **Permission/ownership mismatch** — php-fpm writes uploads as `www-data`, but rsync deploys as a different user; `storage/app/public/products` ends up un-writable, or files written `www-data` under a dir the deploy user later clobbers. Uploads silently fail or vanish on next deploy.
- **No size/type ceiling** — `FileUpload::make('media_assets')` has `acceptedFileTypes(['image/*','video/*'])` but **no `->maxSize()`**. A BO uploads a 200 MB video and fills the disk; the mime list is a client-side hint, not server validation. An SVG (an `image/*`) served inline from the public disk is a stored-XSS vector.
- **Public-disk exposure** — the `public` disk is `visibility: public`; every uploaded file is world-readable by URL with no auth. Acceptable-ish for promo product images, but **any customer-related media must never go on this disk.** Cloudflare then caches old images under the same path, serving stale replacements.
**Why it happens:** It works in local dev (single user, `storage:link` already run, small files). Prod's www-data/deploy-user split and the rsync flow only surface the perms/symlink issues after go-live.
**Consequences:** Broken catalog images, disk-fill DoS, stored XSS via SVG, and uncontrolled public access to any media misrouted to the public disk.
**Prevention:**
- Add idempotent `php artisan storage:link` to the deploy script; verify the symlink target post-deploy; `--exclude` `public/storage` and `storage/app/public` from rsync overwrite.
- Standardize ownership: deploy + php-fpm share a group; `storage/` group-writable (`775` dirs, `g+s` setgid so new files inherit the group).
- Lock the FileUpload: `->image()` (or explicit `['image/jpeg','image/png','image/webp']`), `->maxSize(5120)`, `->maxFiles(n)`; reject SVG; consider re-encoding via `->imageEditor()`. Keep Filament's default randomized filenames (do **not** add `preserveFilenames()`) so URLs aren't enumerable.
- Keep product promo images on `public`, but route **any** customer-attached media to the `local` (private) disk served through an authorized controller — never `public`.
- Content-hash the path / set cache headers so replaced images bust Cloudflare cache.
**Detection:** Images 404 in prod but work locally; `Permission denied` in `storage/logs`; disk usage spiking; SVGs accepted; a customer media field on `disk('public')`; deploy script without `storage:link`; `preserveFilenames()` in a form.
**Owning phase:** **P-Upload** (perms/`storage:link` are deploy-infra tasks within it).

---

## Moderate Pitfalls

### Pitfall 7: Card/grid layout triggers N+1 on images and relations
**What goes wrong:** Rich product cards render `productType`, `territory`, `vendor`, and image per card; customer segments render per-row counts (orders, notes, follow-ups). Without eager loading each card/row fires extra queries — 50 products → 150+ queries — and the page crawls. Image columns regenerating thumbnails from full-size files per render compound it.
**Why it happens:** Filament's `contentGrid()` card view and custom Blade cards loop relations lazily; counts via `$record->orders()->count()` in a column closure run per row. Invisible at 5 seed rows; bites as a BO's catalog/customer base grows.
**Prevention:**
- Eager-load in the list query: `->modifyQueryUsing(fn ($q) => $q->with(['productType','territory'])->withCount('orders'))`. Use `withCount` for badge counts, not per-row `->count()`.
- Use Filament `ImageColumn` pointed at a stored conversion/disk; avoid resizing originals on the fly; cache thumbnails.
- Keep `visibleTo` applied to the eager-loaded query so eager loading doesn't widen visibility.
- Check query count with Telescope/Debugbar against 100+ seeded rows before sign-off.
**Detection:** List/grid slow with realistic data; query count scales with rows; `->count()`/`->load()` inside a column/card closure; full-size images used as thumbnails.
**Owning phase:** **P-Cards** and **P-Segments**.

### Pitfall 8: Free-text category instead of the curated set makes segments meaningless
**What goes wrong:** PROJECT.md asks for a "light category-set so segments are meaningful." If product type or customer stage is captured as free text rather than the existing `ProductType` relationship / `CustomerCategory` enum, segments fragment ("VIP", "vip", "V.I.P") and counts stop adding up.
**Prevention:** Constrain category to the existing `ProductType` relationship + the `ProductStatus`/`CustomerCategory` enums (already cast on the models). Don't introduce a parallel free-text taxonomy.
**Owning phase:** **P-Cards** (product type/status) + **P-Segments** (customer stage filters).

### Pitfall 9: Duplicate-detection scope is keyed on `partner_id`, not `business_owner_id`
**What goes wrong:** `EditCustomer::validateDuplicateCustomer` checks HP/IC uniqueness within `partner_id`. As the CRM grows and BOs add many customers, two BOs under the same Partner can't both hold the same contact, while two BOs under different Partners can — which may or may not match the intended Rule 1 ("belongs exclusively to the Business Owner"). New note/segment features that assume per-BO uniqueness can mis-merge.
**Prevention:** Confirm the intended duplicate boundary (per-BO vs per-Partner) before building segment/merge features on top of it; keep the check consistent with whatever Rule 1 enforcement the detail view relies on.
**Owning phase:** **P-Detail** / **P-Segments** (decision; tangential to existing code).

### Pitfall 10: Relation Manager visible to the wrong actor by default
**What goes wrong:** A notes/follow-ups Relation Manager attached to the customer resource is shown whenever the related model's `viewAny` passes. Without a child policy it may render for partners/admins in contexts where it shouldn't, or allow create/attach actions that don't stamp ownership.
**Prevention:** Implement the child policy (Pitfall 1), gate the Relation Manager with `canViewForRecord()` where needed, and stamp `business_owner_id` in `mutateRelationshipDataBeforeCreate`.
**Owning phase:** **P-CRM-Data**.

---

## Minor Pitfalls

### Pitfall 11: Stale Cloudflare cache on replaced product images
**What goes wrong:** Replacing an image at the same public path serves the old cached copy via Cloudflare.
**Prevention:** Content-hash filenames (Filament's random names already help), or set cache-busting headers; purge on replace if needed.
**Owning phase:** **P-Upload**.

### Pitfall 12: Share link expiry shows a raw 404
**What goes wrong:** An expired signed share URL returns a bare 404; the customer thinks the product is gone.
**Prevention:** Friendly "link expired" page instead of a raw 404.
**Owning phase:** **P-Share**.

---

## Phase-Specific Warnings

| Phase | Likely Pitfall | Mitigation |
|-------|---------------|------------|
| P-CRM-Data | Child-record ownership leak; Relation Manager actor/ownership | Per-model `scopeVisibleTo` + registered policy + denormalized `business_owner_id`; stamp owner server-side; scope the relation query |
| P-Detail | Custom page bypasses scope; duplicate-boundary assumptions | Build as `ViewRecord` (or `visibleTo()->findOrFail` + `authorize`); scope every detail widget; confirm duplicate boundary |
| P-Segments | N+1 per-row counts; free-text categories | `withCount` aggregates; constrain to enums/relationships |
| P-WA | Un-normalized numbers; un-encoded text; PII in logs | Shared normalizer (`60...` / `+60...`); `rawurlencode`; client-side `wa.me`; `rel=noopener` new tab |
| P-Cards | Field re-exposure (cost/vendor/classification); N+1 on cards | Re-expose fields individually; keep field-level guards; `unset()` admin keys server-side; eager-load |
| P-Upload | Missing `storage:link`/perms; no size/type limit; public-disk leak | `storage:link` in deploy + correct group perms; `->image()->maxSize()`; customer media on private disk |
| P-Share | Unauthenticated bypass of scope/policy; catalog scraping | Signed/expiring URL + field whitelist + Active+own+shareable gate + noindex + throttle |

---

## "Looks Done But Isn't" Checklist

- [ ] **Customer notes / follow-ups:** Verify a BO gets 403/404 fetching another BO's note id over Livewire — not just hidden in the UI.
- [ ] **Customer detail page:** Verify it's a `ViewRecord` (or scoped `findOrFail`) and every detail widget is scoped.
- [ ] **Simpler product form:** Verify a crafted BO save payload cannot write `cost`/`vendor_id`/`classification`.
- [ ] **Share link:** Verify only Active + shareable + own products, whitelisted fields, signed URL, `noindex`.
- [ ] **WhatsApp action:** Verify `012-3456789`, `+6012...`, `6012...`, and a message with `&`/newlines all produce a working link.
- [ ] **Image upload:** Verify large + SVG rejected; images load on prod after a deploy.
- [ ] **Card grid:** Verify a flat query count against 100+ seeded rows.

---

## Sources

- This repository (HIGH — read directly):
  - `app/Models/Customer.php`, `app/Models/Product.php` — `scopeVisibleTo` (row-level only), `#[Fillable]` lists, `media_assets` array cast, `canBuyProduct` (Rule 2).
  - `app/Policies/CustomerPolicy.php`, `app/Policies/ProductPolicy.php` — `view()` via `visibleTo`, `ownsProduct` ownership check.
  - `app/Filament/Resources/Customers/**`, `app/Filament/Resources/Products/**` — `getEloquentQuery()->visibleTo()` is the sole row guard; only List/Create/Edit pages exist (no ViewRecord yet); `CustomerForm::visibleToNonBusinessOwner()` and `ProductForm` `->visible(isAdmin())` on `cost`/`vendor_id`; `FileUpload` on `public` disk, dir `products`, no `maxSize()`; `mutateFormDataBeforeCreate/Save` server-side stamping.
  - `app/Providers/Filament/BusinessPanelProvider.php` — `/business` panel registers only Customer + Product resources.
  - `app/Providers/AppServiceProvider.php` — 17 `Gate::policy(...)` registrations (the pattern new child models must follow).
  - `config/filesystems.php` — `public` disk `visibility: public`, `storage:link` mapping; default disk `local`.
  - `.planning/PROJECT.md`, `CLAUDE.md` — Rule 1 (BO ownership) + Rule 2 (Amway/eligibility) invariants, v1.1 cost/vendor column-isolation, deferred WhatsApp Business API.
  - Production note (user memory): rsync + composer + migrate deploy, nginx/php-fpm (www-data), `pios-queue`, Cloudflare front, pios.com.my (Malaysia).
- Filament 5 behavior — record resolution via `getEloquentQuery()`, FileUpload defaults (randomized filenames, visibility/dehydration coupling), Relation Manager authorization (MEDIUM-HIGH; verify against current Filament docs during build).
- WhatsApp `wa.me` click-to-chat international-format requirement (MEDIUM; widely documented).
