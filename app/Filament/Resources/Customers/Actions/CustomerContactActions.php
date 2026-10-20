<?php

namespace App\Filament\Resources\Customers\Actions;

use App\Models\Customer;
use App\Support\Phone;
use Filament\Actions\Action;

/**
 * Single source of truth for the one-tap WhatsApp + Call contact actions (CRM-06).
 *
 * The SAME definitions feed both the customers table rows and the ViewCustomer
 * header, so url-building, the new-tab behavior, and the hidden-when-blank guard
 * only need to be defined — and tested — once. Links are built through the
 * already-unit-tested App\Support\Phone helper (Phase 10).
 */
class CustomerContactActions
{
    public static function whatsApp(): Action
    {
        return Action::make('whatsapp')
            ->label(__('pios.customer.contact.whatsapp'))
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('success')
            ->url(fn (Customer $record): string => 'https://wa.me/'.Phone::toWhatsApp($record->hp_no))
            ->openUrlInNewTab()
            ->visible(fn (Customer $record): bool => Phone::toWhatsApp($record->hp_no) !== null);
    }

    public static function call(): Action
    {
        return Action::make('call')
            ->label(__('pios.customer.contact.call'))
            ->icon('heroicon-o-phone')
            ->url(fn (Customer $record): string => 'tel:'.Phone::toTel($record->hp_no))
            ->visible(fn (Customer $record): bool => Phone::toTel($record->hp_no) !== null);
    }
}
