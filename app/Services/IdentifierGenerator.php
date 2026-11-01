<?php

namespace App\Services;

use App\Models\IdentifierSequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IdentifierGenerator
{
    /**
     * @var array<string, array{prefix: string, padding: int}>
     */
    private const DEFAULTS = [
        'partner' => ['prefix' => 'P', 'padding' => 6],
        'business_owner' => ['prefix' => 'BO', 'padding' => 6],
        'customer' => ['prefix' => 'C', 'padding' => 6],
        'vip_customer' => ['prefix' => 'VIP', 'padding' => 6],
        'product' => ['prefix' => 'PRD', 'padding' => 6],
        'order' => ['prefix' => 'ORD', 'padding' => 6],
    ];

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function generate(string $key, string $modelClass, string $column): string
    {
        if (! is_a($modelClass, Model::class, true)) {
            throw new InvalidArgumentException("{$modelClass} must be an Eloquent model.");
        }

        return DB::transaction(function () use ($key, $modelClass, $column): string {
            $sequence = IdentifierSequence::query()
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $defaults = self::DEFAULTS[$key] ?? ['prefix' => strtoupper(substr($key, 0, 3)), 'padding' => 6];

                $sequence = IdentifierSequence::query()->create([
                    'key' => $key,
                    'prefix' => $defaults['prefix'],
                    'padding' => $defaults['padding'],
                    'next_number' => 1,
                ]);
            }

            // Pluck existing numbers with this prefix to detect any gaps from deleted records
            $existing = $modelClass::query()
                ->where($column, 'like', $sequence->prefix.'%')
                ->pluck($column)
                ->map(function ($val) use ($sequence) {
                    $numStr = substr($val, strlen($sequence->prefix));

                    return is_numeric($numStr) ? (int) $numStr : null;
                })
                ->filter()
                ->values()
                ->all();

            $existingMap = array_flip($existing);
            $maxExisting = empty($existing) ? 0 : max($existing);

            // 1. Check if there are any gaps in existing records (e.g. 1, 2, 3, 5 -> 4 was deleted)
            $gapNumber = null;
            for ($i = 1; $i <= $maxExisting; $i++) {
                if (! isset($existingMap[$i])) {
                    $gapNumber = $i;
                    break;
                }
            }

            if ($gapNumber !== null) {
                // Fill the missing gap left by deleted records
                $candidateNumber = $gapNumber;
            } else {
                // If sequence next_number has drifted ahead of maxExisting (e.g. latest records deleted),
                // sync it back to maxExisting + 1
                if ($maxExisting > 0 && $sequence->next_number > $maxExisting + 1) {
                    $sequence->next_number = $maxExisting + 1;
                }

                while (isset($existingMap[$sequence->next_number])) {
                    $sequence->next_number++;
                }

                $candidateNumber = $sequence->next_number;
                $sequence->next_number++;
            }

            $candidate = $sequence->prefix.str_pad((string) $candidateNumber, $sequence->padding, '0', STR_PAD_LEFT);
            $sequence->save();

            return $candidate;
        });
    }

    /**
     * Synchronize next_number sequence counter based on actual existing records in the model.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function sync(string $key, string $modelClass, string $column): void
    {
        $sequence = IdentifierSequence::query()->where('key', $key)->first();
        if (! $sequence) {
            return;
        }

        $existing = $modelClass::query()
            ->where($column, 'like', $sequence->prefix.'%')
            ->pluck($column)
            ->map(function ($val) use ($sequence) {
                $numStr = substr($val, strlen($sequence->prefix));

                return is_numeric($numStr) ? (int) $numStr : null;
            })
            ->filter()
            ->values()
            ->all();

        $max = empty($existing) ? 0 : max($existing);
        $sequence->update(['next_number' => $max + 1]);
    }

    /**
     * @return array<string, array{prefix: string, padding: int}>
     */
    public static function defaults(): array
    {
        return self::DEFAULTS;
    }
}
