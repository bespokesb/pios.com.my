<?php

namespace App\Models;

use App\Enums\FeeType;
use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Fillable(['name', 'fee_type', 'amount', 'currency', 'effective_from', 'effective_until', 'status'])]
class FeeSetting extends Model
{
    /**
     * Resolve the active effective-dated amount for a fee type.
     *
     * Returns the newest Active row whose effective window contains $on
     * (defaults to now() in the app timezone), or null when none is active.
     * Amounts are sourced from the table — never hardcoded in callers.
     */
    public static function activeAmount(FeeType $type, ?Carbon $on = null): ?string
    {
        $on = $on ?? now();

        // Stored effective dates are persisted at start-of-day. Compare against the
        // day's full bounds so a row dated today is included on both DATE (MySQL) and
        // datetime-string (SQLite) columns, regardless of the current time of day.
        $dayStart = $on->copy()->startOfDay()->toDateTimeString();
        $dayEnd = $on->copy()->endOfDay()->toDateTimeString();

        return static::query()
            ->where('fee_type', $type)
            ->where('status', RecordStatus::Active)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $dayEnd))
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', $dayStart))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->value('amount');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'fee_type' => FeeType::class,
            'status' => RecordStatus::class,
        ];
    }
}
