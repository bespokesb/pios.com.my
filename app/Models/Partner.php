<?php

namespace App\Models;

use App\Casts\PhoneNumber;
use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'user_id',
    'parent_partner_id',
    'territory_id',
    'partner_tier_id',
    'partner_no',
    'abo_no',
    'diamond_name',
    'applicant_name',
    'ic_no',
    'hp_no',
    'email',
    'address',
    'bandar',
    'negeri',
    'poskod',
    'spouse_name',
    'spouse_ic_no',
    'spouse_hp_no',
    'spouse_email',
    'status',
    'created_by',
])]
class Partner extends Model
{
    use HasFactory;

    /**
     * Maximum number of active, HQ-approved direct sub-partners a partner may have.
     */
    public const MAX_DIRECT_PARTNERS = 4;

    /**
     * Direct-child statuses that OCCUPY one of the MAX_DIRECT_PARTNERS slots at
     * ADD/APPOINT time. Active = live, Pending = a queued recruit (counts so a
     * 5th cannot even be queued), Suspended = reinstatable WITHOUT re-approval
     * (excluding it would let a parent overflow to 5 active). Inactive is the
     * ONLY vacated status, so it is excluded.
     *
     * NOTE: HQ approval uses the separate active-only activeChildPartnersCount()
     * — a pending-inclusive count there would count the very record being
     * approved and falsely reject it. The two gates are intentional.
     */
    public const SLOT_OCCUPYING_STATUSES = [
        RecordStatus::Active,
        RecordStatus::Pending,
        RecordStatus::Suspended,
    ];

    public function activeChildPartnersCount(): int
    {
        return $this->childPartners()
            ->where('status', RecordStatus::Active)
            ->count();
    }

    /**
     * Direct children occupying a slot (see SLOT_OCCUPYING_STATUSES). This is
     * the count gated against MAX_DIRECT_PARTNERS at recruit/appoint time.
     */
    public function directChildPartnersCount(): int
    {
        return $this->childPartners()
            ->whereIn('status', self::SLOT_OCCUPYING_STATUSES)
            ->count();
    }

    public function openDirectSlots(): int
    {
        return max(0, self::MAX_DIRECT_PARTNERS - $this->directChildPartnersCount());
    }

    public function hasOpenDirectSlot(): bool
    {
        return $this->openDirectSlots() > 0;
    }

    /** Is $this within $line's own sub-tree ($line itself or a descendant)? */
    public function isWithinLineOf(Partner $line): bool
    {
        return $this->getKey() === $line->getKey()
            || in_array($this->getKey(), $line->descendantIds(), true);
    }

    /**
     * $line + descendants that still have an open direct slot — the valid
     * placement targets when a managing partner appoints a BO→Partner upgrade.
     * Two queries, no N+1.
     *
     * @return Collection<int, Partner>
     */
    public static function eligiblePlacementParents(Partner $line): Collection
    {
        $ids = array_merge([$line->getKey()], $line->descendantIds());

        $nodes = static::query()->whereIn('id', $ids)->orderBy('partner_no')->get();

        $occupied = static::query()
            ->whereIn('parent_partner_id', $ids)
            ->whereIn('status', self::SLOT_OCCUPYING_STATUSES)
            ->selectRaw('parent_partner_id, COUNT(*) as c')
            ->groupBy('parent_partner_id')
            ->pluck('c', 'parent_partner_id');

        return $nodes
            ->filter(fn (Partner $p): bool => (self::MAX_DIRECT_PARTNERS - (int) ($occupied[$p->getKey()] ?? 0)) > 0)
            ->values();
    }

    /**
     * IDs of every partner beneath this one, at all depths (the full downline),
     * excluding self. Breadth-first so it works on both MySQL and SQLite.
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $collected = [];
        $frontier = [$this->getKey()];

        while ($frontier !== []) {
            $next = static::query()
                ->whereIn('parent_partner_id', $frontier)
                ->pluck('id')
                ->all();

            $next = array_values(array_diff($next, $collected, [$this->getKey()]));

            if ($next === []) {
                break;
            }

            $collected = array_merge($collected, $next);
            $frontier = $next;
        }

        return $collected;
    }

    public function downlineCount(): int
    {
        return count($this->descendantIds());
    }

    /**
     * The full downline flattened in tree (pre-order) order, each row tagged with
     * its depth below this partner — so a single screen can render the whole tree.
     *
     * @return array<int, array{partner: Partner, depth: int}>
     */
    public function flatDownline(): array
    {
        $byParent = static::query()
            ->with('partnerTier')
            ->whereIn('id', $this->descendantIds())
            ->orderBy('partner_no')
            ->get()
            ->groupBy('parent_partner_id');

        $rows = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$rows, $byParent): void {
            foreach ($byParent[$parentId] ?? [] as $partner) {
                $rows[] = ['partner' => $partner, 'depth' => $depth];
                $walk($partner->getKey(), $depth + 1);
            }
        };

        $walk((int) $this->getKey(), 1);

        return $rows;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->actsAsPartner() && $user->partner) {
            return $query->where(function (Builder $query) use ($user): void {
                $query->whereKey($user->partner->id)
                    ->orWhere('parent_partner_id', $user->partner->id);
            });
        }

        if ($user->actsAsBusinessOwner() && $user->businessOwner) {
            return $query->whereKey($user->businessOwner->partner_id);
        }

        // Dual-role, no active panel: self + downline, plus the partner managing their BO side.
        if ($user->hasPartnerProfile() && $user->partner
            && $user->hasBusinessOwnerProfile() && $user->businessOwner) {
            return $query->where(function (Builder $query) use ($user): void {
                $query->whereKey($user->partner->id)
                    ->orWhere('parent_partner_id', $user->partner->id)
                    ->orWhere('id', $user->businessOwner->partner_id);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentPartner(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_partner_id');
    }

    public function childPartners(): HasMany
    {
        return $this->hasMany(self::class, 'parent_partner_id');
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function partnerTier(): BelongsTo
    {
        return $this->belongsTo(PartnerTier::class);
    }

    public function businessOwners(): HasMany
    {
        return $this->hasMany(BusinessOwner::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
            'hp_no' => PhoneNumber::class,
            'spouse_hp_no' => PhoneNumber::class,
        ];
    }
}
