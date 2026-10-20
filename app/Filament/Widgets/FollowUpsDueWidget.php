<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\Actions\FollowUpActions;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\CustomerFollowUp;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The Business Owner's actionable "who to contact" list: follow-up reminders
 * that are due today or overdue and not yet completed.
 *
 * Scoped strictly to the logged-in Business Owner via CustomerFollowUp::scopeVisibleTo
 * (business_owner_id). canView() keeps it off the /admin and /partner dashboards,
 * which auto-discover every widget under app/Filament/Widgets.
 */
class FollowUpsDueWidget extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isBusinessOwner() && $user->businessOwner !== null;
    }

    public function table(Table $table): Table
    {
        $today = now('Asia/Kuala_Lumpur')->toDateString();

        return $table
            ->heading(__('pios.widgets.followups_due'))
            ->description(__('pios.widgets.followups_due_hint'))
            ->query(
                fn (): Builder => CustomerFollowUp::query()
                    ->visibleTo(auth()->user())
                    ->whereNull('completed_at')
                    ->whereDate('due_on', '<=', $today)
                    ->with('customer')
                    ->orderBy('due_on'),
            )
            ->recordUrl(
                fn (CustomerFollowUp $record): ?string => $record->customer_id
                    ? CustomerResource::getUrl('view', ['record' => $record->customer_id])
                    : null,
            )
            ->emptyStateHeading(__('pios.widgets.followups_none'))
            ->emptyStateDescription(__('pios.widgets.followups_none_hint'))
            ->emptyStateIcon('heroicon-o-check-circle')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->columns([
                TextColumn::make('customer.name')
                    ->label(__('pios.widgets.followups_customer'))
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('due_on')
                    ->label(__('pios.widgets.followups_due_date'))
                    ->badge()
                    ->color(fn (CustomerFollowUp $record): string => $record->isOverdue() ? 'danger' : 'warning')
                    ->formatStateUsing(fn ($state, CustomerFollowUp $record): string => $record->isOverdue()
                        ? __('pios.widgets.overdue').' · '.$state->translatedFormat('d M')
                        : $state->translatedFormat('d M')),
                TextColumn::make('note')
                    ->label(__('pios.widgets.followups_note'))
                    ->limit(70)
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->recordActions([
                FollowUpActions::whatsApp()->iconButton(),
                FollowUpActions::complete()->iconButton(),
                FollowUpActions::reschedule()->iconButton(),
            ]);
    }
}
