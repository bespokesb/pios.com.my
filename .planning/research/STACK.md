# Stack Research

**Domain:** PIOS v1.2 — BO-facing CRM + product listing UX on an existing Laravel 13 / Filament 5.6 / MySQL app
**Researched:** 2026-06-30
**Confidence:** HIGH

## TL;DR

**Zero new runtime packages are required for milestone v1.2.** Every target feature
(customer detail view, activity notes, follow-up reminders, search/filters/segments,
one-tap WhatsApp/call, product card grid, categories/status, share/promote, simpler BO
create form) maps directly to a Filament 5.6 built-in already installed
(`filament/filament v5.6.6`) plus ~30 lines of helper code.

- **Card/grid product listing** → native Filament table layout components
  `Split` / `Stack` / `Grid` + `$table->contentGrid([...])`. **No package** (and explicitly
  not a table-layout-toggle plugin).
- **WhatsApp share / one-tap chat / call** → plain `https://wa.me/<intl>?text=<rawurlencode>`
  deep links and `tel:` links built by a tiny helper. **No SDK, no Business API** (deferred
  per PROJECT.md).
- **Product image uploads** → the **existing** `public` disk + `storage:link` (already used
  by `ProductForm`'s `FileUpload->disk('public')->directory('products')`). **No S3, no Spatie
  Media Library.**
- **Notes / follow-ups** → plain Eloquent models + Filament **Relation Managers**; reminders
  use the **existing** scheduler + `pios-queue` worker via Database Notifications. **No
  package.**

The only thing to actively decide against is reaching for plugins (Kanban, calendar, media
library, table-toggle, phone validation) — see the explicit do-NOT-add list.

## Recommended Stack

### Core Technologies (all already installed — confirming fitness, not adding)

| Technology | Version | Purpose for v1.2 | Why it suffices |
|------------|---------|------------------|-----------------|
| Filament | v5.6.6 (installed) | Infolists, Relation Managers, table layout (Split/Stack/Grid + `contentGrid()`), Actions, Database Notifications, FileUpload | Covers 100% of v1.2 UX natively; existing resources/`getEloquentQuery()` BO scoping extend directly |
| Laravel Framework | 13.x (^13.8) | Eloquent models for notes/follow-ups; scheduler + queue for reminders; policies/scopes | `CustomerNote`/`FollowUp` are trivial models; reminders reuse the per-minute `schedule:run` cron + `pios-queue` worker from v1.1 |
| PHP | 8.4.22 | Runtime | Matches prod (php8.4-fpm) |
| MySQL | 8.x (`pios_my`) | Storage | New `customer_notes` / `follow_ups` tables are simple additive migrations |

### Supporting Libraries

**None required.** This is the headline finding. Each feature is implemented with Filament 5
built-ins plus new app code (migrations, models, Filament schemas/resources, and small helpers).

### Filament 5 built-ins → feature mapping (the real "stack" for this milestone)

| Feature need | Filament 5 built-in | Integration notes |
|--------------|---------------------|-------------------|
| Customer **detail view** | **Infolist** schema (`TextEntry`, `IconEntry`, `ImageEntry`, `Section`, `Grid`) on a new `ViewCustomer` page (`'view' => ViewCustomer::route('/{record}')`) | Read-only; automatically inherits `CustomerResource::getEloquentQuery()` BO isolation |
| **Activity notes** | **Relation Manager** + new `CustomerNote` model (`belongsTo` customer, `created_by`) | Register in `CustomerResource::getRelations()`; inline create/edit/delete on the detail page |
| **Follow-up reminders** | New `FollowUp` model (due_at, status, note) + **Relation Manager**; **scheduled command** + **Database Notifications** (`Notification::make()->sendToDatabase($user)`) | Reuses the existing per-minute cron + `pios-queue` worker — no new infra |
| **List search** | `TextColumn->searchable()` (already used on Customer/Product tables) | Global + per-column search built in |
| **Filters** (stage/category/status/Amway) | `SelectFilter`, `TernaryFilter`, `Filter` + query closure | Server-side; composes with BO scope |
| **Segments** | List page **`getTabs()`** (All / Lead / Prospect / Customer / VIP), each a query modifier + `->badge()` count | This is the "segment" UX; PROJECT.md says no Kanban — tabs/filters are the stage dimension |
| **One-tap WhatsApp / call** | record/infolist **`Action`** `->url(fn ($record) => …)->openUrlInNewTab()` (or `TextColumn->url(...)`) | URL built by the helper below |
| **Product card / grid listing** | **Table layout columns** `Filament\Tables\Columns\Layout\{Split,Stack,Grid}` + `$table->contentGrid(['md' => 2, 'xl' => 3])` | Confirmed in Filament 5.x docs; responsive card grid with no custom Blade |
| **Product image in cards** | `ImageColumn` / `ImageEntry` on the `public` disk | Renders already-uploaded `media_assets` |
| **Categories & status** | existing `ProductType` relation + `ProductStatus` enum (Draft/Active/Archived) | Expose as `SelectFilter` + tabs |
| **Share / promote product** | record **`Action`**: "Copy link" (`->action()` + `Notification`) and "Send via WhatsApp" (`->url('https://wa.me/?text='.rawurlencode($msg))`) | Pre-filled message built server-side from name/price/link |
| **Simpler BO create form** | form field `->visible(fn () => auth()->user()->isAdmin())` (pattern already used for cost/vendor) + a trimmed `BusinessProductForm` schema | Hide admin fields; no package |
| **Image upload** | `FileUpload->disk('public')->directory('products')` (already in `ProductForm`) | Keep as-is; constrain to images for BO (`->image()->maxSize(...)`) |

### Development Tools (already present)

| Tool | Purpose | Notes |
|------|---------|-------|
| `laravel/pail` 1.2 | Tail logs incl. reminder job output | Already in `composer run dev` |
| `php artisan schedule:list` | Verify the follow-up reminder schedule registered | Confirm cron wiring after adding the command |
| `phpunit` 12.5 | Test note/follow-up scoping + WhatsApp link formatting | `Carbon::setTestNow()` for "due today" tests |

## Installation

```bash
# Nothing to install for v1.2 features. Implementation is migrations + classes only:

php artisan make:model CustomerNote -mf
php artisan make:model FollowUp -mf
php artisan make:command NotifyDueFollowUps         # pios:notify-due-follow-ups
# Filament: add ViewCustomer page (infolist), CustomerNotes + FollowUps relation managers,
# a BO product card table + trimmed BusinessProductForm — all via existing make:filament-* flow.
```

## WhatsApp / call link formats (decided — deep links only)

```php
// One-tap WhatsApp to a known customer number:
//   "012-345 6789" -> "60123456789" (intl, digits only, no +, no leading 0)
$digits = preg_replace('/\D/', '', $hp);                 // "0123456789"
$intl   = str_starts_with(trim($hp), '+')
            ? preg_replace('/\D/', '', $hp)
            : '60'.ltrim($digits, '0');                   // "60123456789"
$url    = 'https://wa.me/'.$intl.'?text='.rawurlencode($message);

// Share/promote a product (recipient chosen in WhatsApp):
$url = 'https://wa.me/?text='.rawurlencode($message);

// One-tap call:
$tel = 'tel:'.preg_replace('/[^0-9+]/', '', $hp);
```

Rules (HIGH confidence — `wa.me` is WhatsApp's documented click-to-chat format):
- Number in the path must be **international, digits only, no `+`, no `0` prefix, no spaces/dashes**.
- Use **`rawurlencode`** (not `urlencode`) so spaces → `%20` (not `+`) and newlines (`%0A`,
  which WhatsApp honors) survive.
- Always `->openUrlInNewTab()` on the Filament action.
- Default country code `60` (Malaysia, since HP No is Malaysian) — keep it a named constant,
  not inlined, for future edge cases.

## Image uploads on production (decided: keep `public` disk + `storage:link`)

Already correct and in use:
- `config/filesystems.php` `public` disk → `storage/app/public`, URL `{APP_URL}/storage`,
  `links` maps `public/storage` → `storage/app/public`.
- `ProductForm` already does `FileUpload->disk('public')->directory('products')`.

**Do not add S3 or Spatie Media Library for v1.2** — single small server, low volume, plain
product photos.

### Production pitfalls to flag (prod deploys via rsync per MEMORY)

| Pitfall | Why it bites with rsync deploy | Mitigation |
|---------|--------------------------------|------------|
| `public/storage` symlink missing/broken | rsync may not recreate it or copies a stale link → images 404 | Run `php artisan storage:link` in the deploy script (idempotent); ensure rsync uses `-a` (preserve symlinks) |
| Uploaded files wiped on deploy | rsync `--delete` over a tree containing `storage/app/public` deletes user uploads | **Exclude `storage/` from rsync** (`--exclude=storage`); treat it as server-owned persistent data |
| File permissions | php8.4-fpm (www-data) must own/write `storage/app/public/products` | `chown -R www-data:www-data storage`; dir `775` |
| nginx not following symlink | nginx serves `public/`; must resolve the symlink | Default Laravel nginx config works; confirm no `disable_symlinks on;` |
| Upload size limits | Large photos/videos silently rejected | Set `upload_max_filesize`/`post_max_size` (php-fpm) + `client_max_body_size` (nginx); add `FileUpload->maxSize(...)` |
| `acceptedFileTypes(['image/*','video/*'])` lets big videos through | Blows past limits / fills disk | For BO listing constrain to images: `->image()->maxSize(4096)`; keep video admin-only if needed |
| Wrong `APP_URL` | `public` disk URL derives from `APP_URL` → broken image links | Ensure prod `.env` `APP_URL=https://pios.com.my` |

## Alternatives Considered

| Recommended | Alternative | When the alternative would make sense |
|-------------|-------------|---------------------------------------|
| Native `contentGrid()` card view | `hydrat/filament-table-layout-toggle` plugin | Only if BOs must flip the *same* list between dense table and card grid at runtime — defer until explicitly requested |
| `public` local disk | S3 + CDN | If media volume grows large or multi-server scaling arrives (post-v1) |
| `wa.me` deep links | WhatsApp Business Cloud API / Twilio | When PROJECT.md's deferred automated-promotion workflow is greenlit (Phase 2) |
| `FileUpload->disk('public')` | Spatie Media Library | If you later need auto thumbnails/conversions/responsive images or multiple collections per product |
| Tiny `Phone::toIntl()` helper | `propaganistas/laravel-phone` | If the app expands beyond Malaysia and needs per-region validation |
| List page Tabs (segments) | Kanban plugin (relaticle/flowforge etc.) | Never for v1.2 — PROJECT.md explicitly says no Kanban |

## What NOT to Use (explicit do-NOT-add list)

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| `spatie/laravel-medialibrary` | Heavy (conversions, separate `media` table, queue jobs) for simple product photos; extra migration + complexity | Existing `FileUpload->disk('public')` into `media_assets` |
| S3 disk / `league/flysystem-aws-s3` | Single small server, low volume, no CDN need yet | `public` local disk + `storage:link` (already configured) |
| `propaganistas/laravel-phone` / `giggsey/libphonenumber` | One country (MY), one format need; a dependency + data for a 5-line normaliser | Tiny `Phone::toIntl()` helper |
| Any WhatsApp SDK / Business Cloud API / Twilio | v1.2 is **deep-link only**; PROJECT.md defers WhatsApp Business API automation | `https://wa.me/<intl>?text=<rawurlencode>` |
| Kanban/board plugin (relaticle/flowforge, filament-kanban) | PROJECT.md explicitly says **no Kanban**; stage is a filter dimension | List page `getTabs()` (segments) + `SelectFilter` |
| `hydrat`/`tgeorgel` table-layout-toggle plugins | New dependency; product listing can simply *be* the card grid | Native `contentGrid()` + `Split`/`Stack`/`Grid` |
| Calendar plugin (filament-fullcalendar) for follow-ups | Reminders are a due-date list + notification, not a calendar in v1.2 | `FollowUp` model + Relation Manager + scheduled "due today" Database Notification |
| `spatie/laravel-activitylog` for CRM notes | "Notes" are user-authored content, not an audit trail; app already has `AuditLog` for sensitive records | Plain `CustomerNote` model + Relation Manager |
| Custom Livewire/Blade card components | `contentGrid()` + layout columns already render responsive cards | Filament table layout components |
| `laravel/cashier` (any fee link) | Out of scope; not relevant to v1.2 CRM/listing | n/a |

## Stack Patterns by Variant

**If follow-up reminders must surface proactively (recommended):**
- One scheduled command `pios:notify-due-follow-ups` registered alongside the existing v1.1
  fee scheduler; it sends Database Notifications to the **owning BO** for follow-ups due today.
- Because reminders that only appear when you open the record defeat the purpose.

**If the product list needs both dense and card views:**
- Ship the **card grid only** for v1.2 (the BO-facing "listing" UX is inherently card-like).
- Add a runtime toggle only if BOs request it later — and even then prefer two table methods
  over a plugin.

**If notes/follow-ups need stricter isolation:**
- Add `scopeVisibleTo` mirroring the existing customer isolation, OR rely on reaching them
  only through the BO-scoped `CustomerResource` relation managers (which already inherit the
  parent's `getEloquentQuery()` scope). Prefer the explicit scope for defense-in-depth.

## Version Compatibility

| Package | Compatible With | Notes |
|---------|-----------------|-------|
| filament/filament v5.6.6 | laravel 13, PHP 8.4 | All cited features (Infolists, Relation Managers, table layout Split/Stack/Grid, `contentGrid()`, Actions, Database Notifications, FileUpload) are **core to Filament 5** — no plugin needed. Avoid v3-only Filament plugins. |
| laravel/framework ^13.8 | PHP 8.4.22 | Scheduler + `pios-queue` worker already running (from v1.1 fees) — reuse for reminders |
| MySQL 8.x | new additive tables | `customer_notes`, `follow_ups` are simple migrations; no extension needed |
| (no new packages) | — | Nothing to reconcile |

## Integration Notes for Requirements/Roadmap Authors

1. **New models/migrations only:** `CustomerNote` (customer_id, body, created_by, timestamps)
   and `FollowUp` (customer_id, due_at, status, note, created_by). Reach both only through the
   BO-scoped `CustomerResource`, and/or add a `scopeVisibleTo`.
2. **Detail page:** add a `ViewCustomer` page + `infolist()` schema to `CustomerResource`;
   register `CustomerNotesRelationManager` and `FollowUpsRelationManager` in `getRelations()`
   (currently empty).
3. **Segments = List page Tabs:** implement `getTabs()` on `ListCustomers` (All / Lead /
   Prospect / Customer / VIP) with `->badge()` counts; each tab a query modifier composing
   with the existing `visibleTo` scope.
4. **Product card grid:** build a BO-facing product table using `Stack`/`Split` layout columns
   + `$table->contentGrid([...])`; surface the image via `ImageColumn` on the `public` disk.
   The current `ProductsTable` is a plain column table — the card grid is a new/parallel table
   config, not a rewrite of admin reporting.
5. **Share/promote + WhatsApp/call:** record `Action`s using the WhatsApp/`tel` helpers, all
   `->openUrlInNewTab()`. Build pre-filled messages server-side.
6. **Simpler BO create form:** a trimmed product form reusing the existing
   `->visible(isAdmin)` pattern to hide cost/vendor/territory/commission from BOs.
7. **Reminders:** register `pios:notify-due-follow-ups` in the existing scheduler; verify the
   prod cron entry for `schedule:run` exists (it does, from v1.1). No new infra.
8. **Deploy guardrail:** add `php artisan storage:link` to the deploy script and exclude
   `storage/` from rsync `--delete` so BO-uploaded product images persist across releases.

## Sources

- Codebase verified firsthand (HIGH): `composer.lock` (`filament/filament v5.6.6`),
  `app/Filament/Resources/Products/Schemas/ProductForm.php` (existing
  `FileUpload->disk('public')->directory('products')`),
  `app/Filament/Resources/Products/Tables/ProductsTable.php`,
  `CustomerResource.php`/`ProductResource.php` (BO `getEloquentQuery()` scoping, empty
  `getRelations()`), `config/filesystems.php` (`public` disk + `storage:link`), `php -v`
  (PHP 8.4.22), MEMORY (rsync deploy, `pios-queue` worker, per-minute scheduler).
- [Filament 5.x — Tables: Layout (Split / Stack / Grid / `contentGrid`)](https://filamentphp.com/docs/5.x/tables/layout) — HIGH.
- [Filament — Table as Grid with Cards (example)](https://filamentexamples.com/project/table-layout-as-grid-with-cards) — MEDIUM (corroborating).
- WhatsApp Click-to-Chat `wa.me` format — widely documented standard — HIGH.

---
*Stack research for: PIOS v1.2 — BO-facing CRM + product listing*
*Researched: 2026-06-30*
