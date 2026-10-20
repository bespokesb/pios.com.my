<?php

namespace App\Filament\Resources\Customers\Actions;

use App\Models\CustomerFollowUp;
use App\Services\AuditLogger;
use App\Support\Phone;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;

/**
 * Single source of truth for the one-tap follow-up actions (Done / Reschedule /
 * WhatsApp-the-customer). The SAME definitions feed the FollowUpsDueWidget on
 * the dashboard and the FollowUpsRelationManager on the customer detail page,
 * so the BO's daily loop is: open dashboard → WhatsApp → mark Done — no forms.
 */
class FollowUpActions
{
    public static function complete(): Action
    {
        return Action::make('complete')
            ->label(__('pios.customer.followup.mark_done'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (CustomerFollowUp $record): bool => ! $record->isCompleted())
            ->action(function (CustomerFollowUp $record): void {
                $old = $record->getOriginal();
                $record->update(['completed_at' => now()]);

                app(AuditLogger::class)->log(
                    actor: auth()->user(),
                    event: 'updated',
                    auditable: $record,
                    oldValues: $old,
                    newValues: $record->fresh()->getAttributes(),
                );

                Notification::make()->success()->title(__('pios.customer.followup.marked_done'))->send();
            });
    }

    public static function reschedule(): Action
    {
        return Action::make('reschedule')
            ->label(__('pios.customer.followup.reschedule'))
            ->icon('heroicon-o-calendar')
            ->color('warning')
            ->visible(fn (CustomerFollowUp $record): bool => ! $record->isCompleted())
            ->schema([
                DatePicker::make('due_on')
                    ->label(__('pios.customer.followup.due_on'))
                    ->native(false)
                    ->required()
                    ->minDate(today()),
            ])
            ->action(function (CustomerFollowUp $record, array $data): void {
                $old = $record->getOriginal();
                $record->update(['due_on' => $data['due_on']]);

                app(AuditLogger::class)->log(
                    actor: auth()->user(),
                    event: 'updated',
                    auditable: $record,
                    oldValues: $old,
                    newValues: $record->fresh()->getAttributes(),
                );

                Notification::make()->success()->title(__('pios.customer.followup.rescheduled'))->send();
            });
    }

    public static function whatsApp(): Action
    {
        return Action::make('whatsapp')
            ->label(__('pios.customer.contact.whatsapp'))
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('success')
            ->url(fn (CustomerFollowUp $record): string => 'https://wa.me/'.Phone::toWhatsApp($record->customer?->hp_no))
            ->openUrlInNewTab()
            ->visible(fn (CustomerFollowUp $record): bool => Phone::toWhatsApp($record->customer?->hp_no) !== null);
    }
}
