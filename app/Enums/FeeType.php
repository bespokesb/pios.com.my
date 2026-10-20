<?php

namespace App\Enums;

enum FeeType: string
{
    case BusinessOwnerMonthly = 'business_owner_monthly';
    case BusinessOwnerSetup = 'business_owner_setup';
    case VipCustomer = 'vip_customer';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::BusinessOwnerMonthly => 'Business Owner Monthly',
            self::BusinessOwnerSetup => 'Business Owner Setup',
            self::VipCustomer => 'VIP Customer',
            self::Partner => 'Partner',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
