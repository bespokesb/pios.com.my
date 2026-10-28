<?php

namespace App\Enums;

enum CustomerCategory: string
{
    case Lead = 'lead';
    case Prospect = 'prospect';
    case Customer = 'customer';
    case LoyalCustomer = 'loyal_customer';
    case Member = 'member';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lead',
            self::Prospect => 'Prospek',
            self::Customer => 'Customer',
            self::LoyalCustomer => 'Loyal Customer',
            self::Member => 'Member',
            self::Partner => 'Partner',
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
