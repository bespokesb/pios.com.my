# Architecture Research

**Domain:** BO-facing CRM + product-listing inside an existing Laravel 13 / Filament 5.6 multi-panel app (PIOS v1.2)
**Researched:** 2026-06-30
**Confidence:** HIGH (every recommendation is checked against the real files in this repo and the installed `vendor/filament` 5.6 source)

This is **integration research**, not greenfield. The job is to slot five v1.2 features into the existing `/business` panel without breaking the BO-ownership invariant. Every recommendation below reuses an existing convention rather than inventing a parallel one.

## Standard Architecture

### How the existing app is layered (and where v1.2 plugs in)

```
┌──────────────────────────────────────────────────────────────────────┐
│  PRESENTATION  ── Filament panels (admin / partner / business)         │
│  ┌───────────────────────── /business panel ────────────────────────┐ │
│  │ CustomerResource          ProductResource        [Dashboard]      │ │
│  │  · ListCustomers           · ListProducts          widgets        │ │
│  │  · CreateCustomer          · CreateProduct                        │ │
│  │  · EditCustomer            · EditProduct                          │ │
│  │  · [NEW ViewCustomer]      · table → [NEW card grid]              │ │
│  │     + Infolist             · [NEW share action]                  │ │
│  │     + [NEW Notes RM]       · [simplified BO form]                │ │
│  │     + [NEW FollowUps RM]                                          │ │
│  │  · [NEW Wa/Call actions]                                          │ │
│  └──────────────────────────────────────────────────────────────────┘ │
├──────────────────────────────────────────────────────────────────────┤
│  AUTHORIZATION  ── the invariant layer (server-side, never UI-only)    │
│   getEloquentQuery() → Model::scopeVisibleTo($query, $user)           │
│   BO ⇒ where business_owner_id = user.businessOwner.id                │
├──────────────────────────────────────────────────────────────────────┤
│  DOMAIN / SERVICES                                                      │
│   IdentifierGenerator · AuditLogger · [NEW App\Support\Phone]         │
├──────────────────────────────────────────────────────────────────────┤
│  DATA  ── MySQL (Eloquent, #[Fillable] attr, enum casts)              │
│   customers · products  +  [NEW customer_notes] [NEW customer_follow_ups]│
└──────────────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | How it is built here |
|-----------|----------------|----------------------|
| Resource `getEloquentQuery()` | Apply `visibleTo($user)` so a BO only ever loads their own rows | Already implemented on Customer/Product; child Relation Managers inherit it via the parent record |
| `scopeVisibleTo` | The single ownership truth: BO → `business_owner_id`, Partner → `partner_id`, Admin → all | Lives on each model; **new child models must add their own** |
| `IdentifierGenerator` | Generate `customer_no` / `product_no` business IDs | Child records (notes/follow-ups) do **not** need business IDs — skip it |
| `AuditLogger` | Record create/update of sensitive records | Call `->log()` in Relation-Manager `afterSave`/`afterCreate` hooks |
| `App\Support\Phone` (NEW) | Normalize MY mobile numbers → `wa.me` / `tel:` | Stateless helper; thin model accessors delegate to it |

## Recommended Project Structure

New and modified files, matching the repo's existing folder conventions exactly:

```
database/migrations/
├── 2026_07_xx_create_customer_notes_table.php          # NEW
└── 2026_07_xx_create_customer_follow_ups_table.php      # NEW

app/Models/
├── Customer.php                # MOD: add notes()/followUps() HasMany (+ wa/tel accessors)
├── CustomerNote.php            # NEW
└── CustomerFollowUp.php        # NEW

app/Enums/
├── FollowUpStatus.php          # NEW (pending, done, cancelled)
└── FollowUpChannel.php         # NEW (whatsapp, call, visit, other) — optional

app/Support/
└── Phone.php                   # NEW (toWhatsApp / toTel / digits)

app/Filament/Resources/Customers/
├── CustomerResource.php        # MOD: getRelations() + getPages('view')
├── Pages/ViewCustomer.php      # NEW (ViewRecord)
├── Schemas/CustomerInfolist.php# NEW
├── Tables/CustomersTable.php   # MOD: filters, Wa/Call/View actions, segments
├── Pages/ListCustomers.php     # MOD: getTabs() = category segments w/ counts
└── RelationManagers/
    ├── CustomerNotesRelationManager.php      # NEW
    └── CustomerFollowUpsRelationManager.php  # NEW

app/Filament/Resources/Products/
├── Tables/ProductsTable.php    # MOD: ->contentGrid() card layout + filters + share action
└── Schemas/ProductForm.php     # MOD: BO-simplified essentials layout

app/Filament/Widgets/
└── FollowUpRemindersWidget.php # NEW (optional) — "due today / overdue" on BO dashboard

lang/en/pios.php, lang/ms/pios.php   # MOD: customer.note.*, customer.followup.*, product.share.*
```

### Structure Rationale

- **Relation Managers under the Customer resource:** notes and follow-ups are children of a customer and are only ever edited in a customer's context. Filament Relation Managers scope to the parent automatically, so they ride on the parent's `visibleTo` gate for free.
- **`App\Support\Phone` as a standalone class, not a trait/accessor-only:** the same normalization is needed by Customer (call/WhatsApp), Product share, and potentially BusinessOwner. A stateless helper is the single source of truth; per-model accessors delegate to it (DRY, testable in isolation).
- **No new business identifier for notes/follow-ups:** they are internal child rows, not externally-referenced entities, so `IdentifierGenerator` is intentionally skipped.

## Key Decisions (answers to the brief)

### (a) Notes & follow-ups data model — RECOMMEND: two separate tables

**Decision: `customer_notes` + `customer_follow_ups`, NOT one polymorphic `activities` table.**

Why separate beats polymorphic here:

- **Different shapes, different lifecycles.** A note is append-only free text (`body`, author, when). A follow-up is a stateful task (`due_at`, `status` pending→done, `channel`, optional `remind_at`, `completed_at`). A polymorphic `activities` table forces all of these into nullable columns plus a `type` discriminator, so every query (e.g. "overdue follow-ups", "notes timeline") gains a `where type = …` and validation must branch on type.
- **Polymorphism pays off when many *parents* share one *child shape*** (that is exactly why `audit_logs` here uses `nullableMorphs`). v1.2 is the inverse: one parent (Customer), two distinct child shapes. So the polymorphic win does not apply.
- **Cleaner indexing & widgets.** A dashboard "follow-ups due today" widget queries one narrow table with `(business_owner_id, status, due_at)` indexed — no type filter, no nullable columns.

Both child tables carry the **same three ownership/audit columns** as existing tables: `customer_id` (FK, cascade), `business_owner_id` (denormalized, indexed), `created_by` (FK users, nullOnDelete).

**Migration sketch (`customer_follow_ups`; notes is the simpler subset):**

```php
Schema::create('customer_follow_ups', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
    $table->foreignId('business_owner_id')->constrained()->cascadeOnDelete(); // denormalized for scoping
    $table->string('title');
    $table->text('notes')->nullable();
    $table->string('channel')->default(FollowUpChannel::Whatsapp->value)->index(); // optional
    $table->timestamp('due_at')->index();
    $table->timestamp('completed_at')->nullable();
    $table->string('status')->default(FollowUpStatus::Pending->value)->index();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->index(['business_owner_id', 'status', 'due_at']); // drives reminder widget
});
```

**Model (mirrors existing `#[Fillable]` + casts + `scopeVisibleTo` convention):**

```php
#[Fillable(['customer_id','business_owner_id','title','notes','channel','due_at','completed_at','status','created_by'])]
class CustomerFollowUp extends Model
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) return $query;
        if ($user->isBusinessOwner() && $user->businessOwner) {
            return $query->where('business_owner_id', $user->businessOwner->id);
        }
        // partner: reach own BOs' customers via the parent
        if ($user->isPartner() && $user->partner) {
            return $query->whereHas('customer', fn ($q) => $q->where('partner_id', $user->partner->id));
        }
        return $query->whereRaw('1 = 0');
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    protected function casts(): array
    {
        return ['status' => FollowUpStatus::class, 'due_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
```

**How ownership applies to the child records (the critical invariant):**

There are **two enforcement layers, both required**:

1. **Parent gate (primary).** A Relation Manager can only be reached through a customer record, and `CustomerResource::getEloquentQuery()` already filters customers by `visibleTo`. So a BO physically cannot open another BO's customer page, therefore cannot reach its notes/follow-ups RM. This is the main guard and it is free.
2. **Child `scopeVisibleTo` (defense-in-depth + direct queries).** Any code that queries notes/follow-ups **directly** — the reminder widget, reports, a future global "my tasks" view — must call `->visibleTo($user)`. Denormalizing `business_owner_id` onto the child row makes this a cheap indexed `where`, consistent with how `customers` already denormalizes `partner_id`.

**Setting the denormalized `business_owner_id` safely:** do it server-side in the Relation Manager (never trust the form). Either a `mutateFormDataBeforeCreate()` that copies `$this->getOwnerRecord()->business_owner_id`, or a model `creating` event that backfills it from `customer`. Same place to stamp `created_by = auth()->id()` and fire `AuditLogger`.

**Relation Manager approach:** standard `Filament\Resources\RelationManagers\RelationManager` with `$relationship = 'notes'` / `'followUps'`. The RM's form hides `customer_id`/`business_owner_id` (set in mutate hook). Follow-ups RM adds a "Mark done" `Action` that sets `status` + `completed_at`. Register both in `CustomerResource::getRelations()`.

### (b) Customer detail = ViewRecord page + Infolist + Relation Managers

This repo already ships the exact pattern (AuditLog: `ViewAuditLog extends ViewRecord` + `AuditLogInfolist` + `'view' => ViewAuditLog::route('/{record}')`). Copy it.

Outline:

- **`CustomerResource::getPages()`** add `'view' => ViewCustomer::route('/{record}')`. Add a `ViewAction` to the table row so BOs land here.
- **`ViewCustomer extends ViewRecord`** — header actions = WhatsApp + Call + Edit.
- **`CustomerInfolist`** (Schema with `Infolists\Components\TextEntry`, grouped in `Section`s):
  - *Contact* — `name`, `hp_no` (with a call/WhatsApp `Action` on the entry), `email`, `category` badge, `status` badge.
  - *Identity / LOS* — `kp_no`, `abo_no`, `dd_name` — wrapped in the existing `->visible(fn () => ! auth()->user()?->isBusinessOwner())` guard so admins see them and BOs don't, exactly like the form/table do today.
  - *At a glance* — counts (notes, open follow-ups, next due date) via `TextEntry::state(...)`.
- **Relation Managers** rendered below the infolist by Filament automatically once registered: `CustomerNotesRelationManager` (timeline, newest first), `CustomerFollowUpsRelationManager` (with status filter + Mark-done action).

### (c) WhatsApp / call links — normalization lives in `App\Support\Phone`

No `wa.me`/`tel:` usage exists in the codebase yet — this is net new. Use a Filament `Action` with `->url(...)->openUrlInNewTab()` (verified: `CanOpenUrl` concern is present in `vendor/filament/actions`).

```php
Action::make('whatsapp')
    ->label('WhatsApp')->icon(Heroicon::ChatBubbleLeftRight)->color('success')
    ->url(fn (Customer $r) => Phone::toWhatsApp($r->hp_no))   // https://wa.me/60123456789
    ->openUrlInNewTab()
    ->visible(fn (Customer $r) => filled($r->hp_no));

Action::make('call')
    ->label('Call')->icon(Heroicon::Phone)
    ->url(fn (Customer $r) => Phone::toTel($r->hp_no))        // tel:+60123456789
    ->visible(fn (Customer $r) => filled($r->hp_no));
```

**Where normalization lives:** a dedicated stateless helper `App\Support\Phone`, **not** scattered accessors. Reason: the same logic is reused by the product *share* action and potentially BusinessOwner contact, so one tested class beats N model accessors. For ergonomics in Infolist/table you may add thin Customer accessors (`waLink`, `telLink`) that simply `return Phone::toWhatsApp($this->hp_no)`. Helper sketch (Malaysian rules):

```php
class Phone
{
    public static function digits(?string $raw): ?string {
        if (blank($raw)) return null;
        $d = preg_replace('/\D+/', '', $raw);
        if (str_starts_with($d, '0'))  $d = '60'.substr($d, 1); // 012… → 6012…
        if (! str_starts_with($d, '60')) $d = '60'.$d;          // bare → prefix
        return $d;
    }
    public static function toWhatsApp(?string $raw): ?string {
        $d = self::digits($raw); return $d ? "https://wa.me/{$d}" : null;
    }
    public static function toTel(?string $raw): ?string {
        $d = self::digits($raw); return $d ? "tel:+{$d}" : null;
    }
}
```

### (d) Product card grid — RECOMMEND: Filament table `->contentGrid()`, not custom Livewire

**Decision: a Filament table with a card layout (`->contentGrid()` + layout columns), the lighter path.** Verified the installed Filament 5.6 supports it (`vendor/filament/tables/src/Table/Concerns/HasContent.php` exposes `contentGrid()`).

Why table-as-grid wins:

- **Reuses everything already wired:** `getEloquentQuery()->visibleTo()` scoping, pagination, search, filters, row actions, the admin-only column guards. A custom Livewire view would re-implement scoping, pagination and filters by hand — and is the exact place an ownership leak gets introduced.
- **Card look is a layout, not a new page:** wrap columns in `Stack`/`Split`/`Grid` layout components, add an `ImageColumn` for `media_assets`, `TextColumn` for name/price/status badge, then `->contentGrid(['md' => 2, 'xl' => 3])`. Filters (classification, product type, status) become `SelectFilter`s; share/promote is a row `Action`.
- **Go custom Livewire only if** the design needs something tables genuinely can't express (drag-reorder, bespoke hover canvas). Not the case for an image/price/description/status card.

### (e) Share / promote — Action building a `wa.me` link with text; NO public page needed for v1.2

**Decision: a row `Action` that builds a pre-filled WhatsApp message (text + optional media URL). No public product route required.**

- Product images are already stored on the **public** disk (`ProductForm`: `FileUpload->disk('public')->directory('products')`), so the first asset can be linked directly via `Storage::disk('public')->url($asset)` inside the message — no landing page, no controller.
- Two shapes of the same action:
  - **Generic share** → `https://wa.me/?text={urlencoded message}` (opens WhatsApp, user picks recipient).
  - **Share to a known customer** → `https://wa.me/{Phone::digits($customer->hp_no)}?text=…`.
- Message text = product name + `RM price` + short description (+ public image URL if present), assembled through a small builder and `rawurlencode`d.

```php
Action::make('share')
    ->label(__('pios.product.share.label'))->icon(Heroicon::Share)->color('success')
    ->url(fn (Product $r) => 'https://wa.me/?text='.rawurlencode(
        "{$r->name}\nRM ".number_format((float) $r->selling_price, 2)
        .($r->description ? "\n\n{$r->description}" : '')
    ))
    ->openUrlInNewTab();
```

**A public product landing page is explicitly Phase-2 / deferred** — it aligns with `PROJECT.md` deferring WhatsApp Business API and richer promotion. Note also that a heavier `Promotion` entity already exists from v1.1 (`PromotionResource`, `promotions` table with discount/recipients) but is **not** registered in `BusinessPanelProvider`. Keep v1.2 "share" as the lightweight one-tap action; do **not** conflate it with the campaign builder.

## Data Flow

### Create-a-follow-up (ownership stamped server-side)

```
BO opens own Customer (getEloquentQuery → visibleTo, already gated)
   ↓
FollowUps RelationManager → Create form (no owner fields shown)
   ↓
mutateFormDataBeforeCreate: business_owner_id ← ownerRecord, created_by ← auth id
   ↓
CustomerFollowUp::create()  →  AuditLogger::log('created')
   ↓
Reminder widget later: CustomerFollowUp::visibleTo($user)->whereDate('due_at', today())
```

### One-tap contact

```
Table/Infolist Action → Phone::toWhatsApp($record->hp_no) → openUrlInNewTab → wa.me/60…
```

## Anti-Patterns (specific to this integration)

### Anti-Pattern 1: Relying on the parent gate alone for child records
**What people do:** skip `scopeVisibleTo` on `CustomerNote`/`CustomerFollowUp` because "the customer is already scoped."
**Why it's wrong:** the moment a dashboard widget or report queries the child table directly, there is no parent gate and a BO sees everyone's notes.
**Do this instead:** add `scopeVisibleTo` to both child models and denormalize `business_owner_id` so the scope is a cheap indexed `where`.

### Anti-Pattern 2: Trusting the form for `business_owner_id`
**What people do:** put `business_owner_id` in the RM form (even hidden).
**Why it's wrong:** a crafted request can set another BO's id.
**Do this instead:** never expose it; stamp it server-side from `getOwnerRecord()` in a mutate hook or model `creating` event — mirroring `CreateProduct::mutateFormDataBeforeCreate`.

### Anti-Pattern 3: A custom Livewire product grid
**What people do:** hand-roll a Livewire/Blade grid for "nicer cards."
**Why it's wrong:** re-implements scoping/pagination/filters and is where ownership leaks appear.
**Do this instead:** `->contentGrid()` on the existing scoped table.

### Anti-Pattern 4: Polymorphic `activities` for two child shapes
**What people do:** one table with a `type` column and many nullable fields.
**Why it's wrong:** every query needs a type filter; validation branches; widgets get awkward.
**Do this instead:** two purpose-built tables.

### Anti-Pattern 5: Inventing a new BO-hiding mechanism
**What people do:** add a fresh role check for the new view/grid.
**Why it's wrong:** drift from the one already used everywhere (`->visible(fn () => ! auth()->user()?->isBusinessOwner())`). NB the milestone brief's shorthand `! isBusinessOwner()` is **not** a global helper in this repo — the real call is `auth()->user()?->isBusinessOwner()`.
**Do this instead:** reuse that exact closure for the new infolist entries / columns.

## Integration Points

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| Customer ↔ Notes / Follow-ups | Eloquent HasMany + Filament Relation Managers | child rows carry denormalized `business_owner_id` |
| Resource ↔ Authorization | `getEloquentQuery()->visibleTo($user)` | already in place for Customer/Product; child models add their own scope |
| Filament Action ↔ WhatsApp/tel | `->url(Phone::to…)->openUrlInNewTab()` | external deep link only; no API |
| Product share ↔ media | `Storage::disk('public')->url()` | images already on public disk; no public page in v1.2 |
| Create/update ↔ audit | `AuditLogger::log()` in page/RM hooks | same as `CreateProduct::afterCreate` |
| Business IDs | `IdentifierGenerator` | used by customers/products; **not** by notes/follow-ups |

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| WhatsApp | `wa.me` deep link (text + optional public image URL) | Business API deferred to Phase 2 per PROJECT.md |
| Phone dialer | `tel:+60…` | normalization in `App\Support\Phone` |

## Dependency-Ordered Build Sequence

Matches the brief's suggested order; each step only depends on prior steps.

1. **CRM data layer** — enums (`FollowUpStatus`, optional `FollowUpChannel`); migrations `customer_notes`, `customer_follow_ups` (with `customer_id` + denormalized `business_owner_id` + `created_by`); models `CustomerNote`/`CustomerFollowUp` with `#[Fillable]`, casts, `scopeVisibleTo`, `customer()`; `Customer::notes()/followUps()`; `App\Support\Phone` + thin Customer accessors. *Foundation for everything CRM.*
2. **CRM views & actions** — `ViewCustomer` page + `CustomerInfolist`; the two Relation Managers (with server-side owner stamping + `AuditLogger`); WhatsApp/Call actions on table + infolist; `CustomersTable` filters (`category`, `status`) and search; `ListCustomers::getTabs()` category **segments with counts**; optional `FollowUpRemindersWidget`. *Depends on step 1.*
3. **Product listing** — convert `ProductsTable` to `->contentGrid()` card layout; classification/product-type/status filters; **share/promote** action (reuses `Phone` from step 1). *Independent of CRM but reuses the Phone helper, so after step 1.*
4. **Product create-form simplification** — BO-essentials layout in `ProductForm` (extend the existing `isAdmin()` visibility guards into a clean BO-only section set). *Last: listing first reveals which fields actually matter to a BO.*

Rationale: data before views (step 1 → 2); CRM is the core v1.2 ask and notes/follow-ups are foundational, so it precedes product work; product listing precedes form simplification because the grid surfaces which fields are worth keeping on the simplified form.

## New vs Modified Components (for the roadmapper)

| Type | Component | Notes |
|------|-----------|-------|
| NEW | `customer_notes`, `customer_follow_ups` migrations | child tables, denormalized `business_owner_id` |
| NEW | `CustomerNote`, `CustomerFollowUp` models | `#[Fillable]` + casts + `scopeVisibleTo` |
| NEW | `FollowUpStatus` (+ optional `FollowUpChannel`) enums | follow `RecordStatus`/`CustomerCategory` enum style |
| NEW | `App\Support\Phone` helper | wa.me / tel normalization |
| NEW | `ViewCustomer` page + `CustomerInfolist` schema | mirrors AuditLog ViewRecord pattern |
| NEW | `CustomerNotesRelationManager`, `CustomerFollowUpsRelationManager` | owner-stamping + audit hooks |
| NEW (optional) | `FollowUpRemindersWidget` | BO dashboard "due today / overdue" |
| MOD | `Customer` model | `notes()`/`followUps()` HasMany + wa/tel accessors |
| MOD | `CustomerResource` | `getRelations()`, `getPages('view')` |
| MOD | `CustomersTable` | category/status `SelectFilter`, search, View/WhatsApp/Call row actions |
| MOD | `ListCustomers` | `getTabs()` = category segments with counts |
| MOD | `ProductsTable` | `->contentGrid()` card layout + filters + share action |
| MOD | `ProductForm` | BO-simplified essentials layout |
| MOD | `lang/en/pios.php`, `lang/ms/pios.php` | `customer.note.*`, `customer.followup.*`, `product.share.*` |

## Sources

- Repo files (HIGH): `app/Models/{Customer,Product,Promotion,User}.php`, `app/Filament/Resources/{Customers,Products,AuditLogs}/**`, `app/Services/{AuditLogger,IdentifierGenerator}.php`, `app/Providers/Filament/BusinessPanelProvider.php`, `database/migrations/2026_06_06_000002_create_pios_foundation_tables.php`, `erd.md`, `.planning/PROJECT.md`, `CLAUDE.md`.
- Installed framework (HIGH): `vendor/filament` 5.6 — `tables/.../HasContent.php` (`contentGrid`), `filament/.../RelationManagers/RelationManager.php`, `actions/.../Concerns/CanOpenUrl.php`.

---
*Architecture research for: PIOS v1.2 BO-facing CRM & Product Listing*
*Researched: 2026-06-30*
