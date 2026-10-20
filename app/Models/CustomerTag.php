<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * An admin-managed customer tag (DP-15). The tag value stored on a customer is
 * still the plain string in customers.tags; this model is the pick list a
 * Business Owner chooses from.
 */
#[Fillable(['name', 'status'])]
class CustomerTag extends Model
{
    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
        ];
    }

    /**
     * Active tag names as a select option map (name => name), plus any extra
     * values passed in (e.g. a customer's current tags that predate the list),
     * so editing a record never silently drops a tag not yet in the vocabulary.
     *
     * @param  array<int, string>  $include
     * @return array<string, string>
     */
    public static function options(array $include = []): array
    {
        $names = static::query()
            ->where('status', RecordStatus::Active)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $all = array_values(array_unique(array_filter([...$names, ...$include])));
        sort($all);

        return array_combine($all, $all);
    }
}
