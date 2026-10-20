<?php

namespace App\Enums;

enum CustomerCategory: string
{
    case Lead = 'lead';
    case Prospect = 'prospect';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lead',
            self::Prospect => 'Prospect',
            self::Customer => 'Customer',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $category): array => [$category->value => $category->label()])
            ->all();
    }
}
