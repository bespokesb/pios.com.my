<?php

namespace App\Enums;

enum PromotionMode: string
{
    case Manual = 'manual';
    case Auto = 'auto';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Auto => 'Auto',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $mode): array => [$mode->value => $mode->label()])
            ->all();
    }
}
