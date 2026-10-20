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

            do {
                $candidate = $sequence->prefix.str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT);
                $sequence->next_number++;
            } while ($modelClass::query()->where($column, $candidate)->exists());

            $sequence->save();

            return $candidate;
        });
    }

    /**
     * @return array<string, array{prefix: string, padding: int}>
     */
    public static function defaults(): array
    {
        return self::DEFAULTS;
    }
}
