<?php

namespace App\Enums;

enum FeeStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Waived => 'Waived',
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
