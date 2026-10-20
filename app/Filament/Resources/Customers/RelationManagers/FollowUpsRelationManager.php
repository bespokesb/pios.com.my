<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Customers\Actions\FollowUpActions;
use App\Models\CustomerFollowUp;
use App\Services\AuditLogger;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FollowUpsRelationManager extends RelationManager
{
    protected static string $relationship = 'followUps';

    /**
     * Keep follow-ups editable from the customer ViewRecord page; Filament panels
     * default relation managers on view pages to read-only.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('pios.customer.followup.label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('due_on')
                    ->label(__('pios.customer.followup.due_on'))
                    ->native(false)
                    ->required(),
                Textarea::make('note')
                    ->label(__('pios.customer.followup.note'))
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->visibleTo(auth()->user()))
            ->columns([
                TextColumn::make('due_on')
                    ->label(__('pios.customer.followup.due_on'))
                    ->badge()
                    ->color(fn (CustomerFollowUp $record): string => $record->isCompleted()
                        ? 'gray'
                        : ($record->isOverdue() ? 'danger' : 'warning'))
                    ->formatStateUsing(fn ($state, CustomerFollowUp $record): string => $record->isOverdue()
                        ? __('pios.widgets.overdue').' · '.$state->translatedFormat('d M')
                        : $state->translatedFormat('d M'))
                    ->sortable(),
                TextColumn::make('note')
                    ->label(__('pios.customer.followup.note'))
                    ->wrap()
                    ->limit(80),
                TextColumn::make('completed_at')
                    ->label(__('pios.customer.followup.completed_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextColumn::make('creator.name')
                    ->label(__('pios.customer.followup.author'))
                    ->visible(fn (): bool => ! (auth()->user()?->actsAsBusinessOwner() ?? false))
                    ->placeholder('-'),
            ])
            ->defaultSort('due_on', 'asc')
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['business_owner_id'] = $this->getOwnerRecord()->business_owner_id;
                        $data['created_by'] = auth()->id();

                        return $data;
                    })
                    ->after(function (Model $record): void {
                        app(AuditLogger::class)->log(
                            actor: auth()->user(),
                            event: 'created',
                            auditable: $record,
                            newValues: $record->fresh()->getAttributes(),
                        );
                    }),
            ])
            ->recordActions([
                FollowUpActions::complete(),
                FollowUpActions::reschedule(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
