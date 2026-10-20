<?php

namespace App\Enums;

enum ProductSelectionStatus: string
{
    case Selected = 'selected';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Selected => 'Selected',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
