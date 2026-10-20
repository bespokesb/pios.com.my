# Phase 1 Pattern Map

## Existing Patterns

### Models

- `App\Models\User` uses PHP attributes for `#[Fillable]` and `#[Hidden]`.
- Model casts are defined through a `casts(): array` method.
- Factories live under `database/factories`.

### Filament

- `AdminPanelProvider` configures the default panel, `/admin` path, login, resource discovery, page discovery, and widgets.
- Resources should live under `app/Filament/Resources` and rely on auto-discovery.

### Database

- Migrations use anonymous migration classes and Laravel schema builder.
- The default users table already exists and should be altered by a new migration rather than edited destructively.

## Files to Create or Modify

- `app/Enums/*` for role/status/type enums.
- `database/migrations/*` for user role/status and PIOS tables.
- `app/Models/*` for domain entities and relationships.
- `app/Services/IdentifierGenerator.php` and `app/Services/AuditLogger.php`.
- `app/Policies/*` and `app/Providers/AppServiceProvider.php` for access control.
- `app/Filament/Resources/*` for foundational admin resources.
- `database/seeders/*` for sequences and first admin users.
- `tests/Feature/*` and `tests/Unit/*` for verification.

