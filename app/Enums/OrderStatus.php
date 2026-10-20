<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Paid = 'paid';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Paid => 'Paid',
            self::Packed => 'Packed',
            self::Shipped => 'Shipped',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'warning',
            self::Paid, self::Packed => 'info',
            self::Shipped => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * The order lifecycle a Business Owner drives forward:
     * Draft → Submitted → Paid → Packed → Shipped → Completed.
     * Cancelled is reachable from any non-terminal state; Completed and
     * Cancelled are both terminal.
     *
     * @return array<int, self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted, self::Cancelled],
            self::Submitted => [self::Paid, self::Cancelled],
            self::Paid => [self::Packed, self::Cancelled],
            self::Packed => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->nextStatuses(), true);
    }

    public function isTerminal(): bool
    {
        return $this->nextStatuses() === [];
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
