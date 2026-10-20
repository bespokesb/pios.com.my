<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Services\AuditLogger;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    /**
     * Keep notes editable from the customer ViewRecord page; Filament panels
     * default relation managers on view pages to read-only.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('pios.customer.note.label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('body')
                    ->label(__('pios.customer.note.body'))
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->visibleTo(auth()->user()))
            ->columns([
                TextColumn::make('body')
                    ->label(__('pios.customer.note.body'))
                    ->wrap()
                    ->limit(80),
                TextColumn::make('creator.name')
                    ->label(__('pios.customer.note.author'))
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label(__('pios.customer.note.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
