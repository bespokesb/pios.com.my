<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->status !== UserStatus::Active || ! $this->role instanceof UserRole) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->isAdmin(),
            'partner' => $this->isAdmin() || $this->isPartner(),
            'business' => $this->isAdmin() || $this->isBusinessOwner(),
            default => false,
        };
    }

    public function isAdmin(): bool
    {
        return $this->role?->isAdmin() ?? false;
    }

    public function isPiosOwner(): bool
    {
        return $this->role === UserRole::PiosOwner;
    }

    /**
     * Capability: does this user hold a Partner identity at all (by role or by
     * owning a Partner profile record)? Used by policies + widget visibility.
     */
    public function hasPartnerProfile(): bool
    {
        return $this->role === UserRole::Partner || $this->partner !== null;
    }

    public function hasBusinessOwnerProfile(): bool
    {
        return $this->role === UserRole::BusinessOwner || $this->businessOwner !== null;
    }

    public function isPartner(): bool
    {
        return $this->hasPartnerProfile();
    }

    public function isBusinessOwner(): bool
    {
        return $this->hasBusinessOwnerProfile();
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    /**
     * Resolve the primary dashboard URL for this user based on their hierarchy role:
     * - Owner (SysAdmin, PiosOwner) -> Owner Dashboard (/admin)
     * - Partner -> Partner Dashboard (/partner)
     * - Business Owner -> Business Owner Dashboard (/dashboard or /business)
     * - Customer -> Marketplace (/market)
     */
    public function getDashboardUrl(): string
    {
        if ($this->isAdmin()) {
            return route('filament.admin.pages.dashboard');
        }

        if ($this->isPartner()) {
            return route('filament.partner.pages.dashboard');
        }

        if ($this->isBusinessOwner()) {
            return route('business.dashboard');
        }

        return route('marketplace.index');
    }

    /**
     * Acting "hat": which role a (possibly dual-role) user is exercising in the
     * CURRENT request, resolved from the active Filament panel. A single-role
     * user's hat always equals their capability, so their behavior is unchanged;
     * only a genuinely dual-role user diverges — and only at these disambiguation
     * points (ownership scopes, owner-resolution, shared-resource form gating).
     */
    public function actsAsPartner(): bool
    {
        if (! $this->hasPartnerProfile()) {
            return false;
        }

        return match (Filament::getCurrentPanel()?->getId()) {
            'partner' => true,
            'business' => false,
            // console / admin / report / test: unambiguous only for a single-capability user.
            default => ! $this->hasBusinessOwnerProfile(),
        };
    }

    public function actsAsBusinessOwner(): bool
    {
        if (! $this->hasBusinessOwnerProfile()) {
            return false;
        }

        return match (Filament::getCurrentPanel()?->getId()) {
            'business' => true,
            'partner' => false,
            default => ! $this->hasPartnerProfile(),
        };
    }

    public function canImpersonate(): bool
    {
        return $this->status === UserStatus::Active
            && $this->role === UserRole::SysAdmin;
    }

    public function canBeImpersonated(): bool
    {
        return $this->status === UserStatus::Active
            && $this->role !== UserRole::SysAdmin;
    }

    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    public function businessOwner(): HasOne
    {
        return $this->hasOne(BusinessOwner::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    /**
     * Resolve the dedicated pios-system audit actor for scheduled jobs.
     *
     * Idempotent and self-healing: creates the non-login system user on first
     * resolution if the seeder has not run (e.g. in tests).
     */
    public static function system(): self
    {
        return static::query()->firstOrCreate(
            ['email' => 'system@pios.com.my'],
            [
                'name' => 'PIOS System',
                'password' => Hash::make(Str::random(40)),
                'role' => UserRole::SysAdmin,
                'status' => UserStatus::Inactive,
            ],
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }
}
