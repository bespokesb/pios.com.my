<?php

namespace App\Enums;

enum UserRole: string
{
    case SysAdmin = 'sys_admin';
    case PiosOwner = 'pios_owner';
    case Partner = 'partner';
    case BusinessOwner = 'business_owner';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SysAdmin => 'Sys Admin',
            self::PiosOwner => 'PIOS Owner',
            self::Partner => 'Partner',
            self::BusinessOwner => 'Business Owner',
            self::Customer => 'Customer',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::SysAdmin, self::PiosOwner], true);
    }

    public function canManageSystem(): bool
    {
        return $this->isAdmin();
    }

    public function isPartner(): bool
    {
        return $this === self::Partner;
    }

    public function isBusinessOwner(): bool
    {
        return $this === self::BusinessOwner;
    }

    public function isCustomer(): bool
    {
        return $this === self::Customer;
    }
}
