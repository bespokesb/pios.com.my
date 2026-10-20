<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Enums\CustomerCategory;
use App\Filament\Resources\Customers\Actions\CustomerContactActions;
use App\Models\Customer;
use App\Models\CustomerTag;
use App\Services\AuditLogger;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_no')
                    ->label('Customer No')
                    ->visible(self::visibleToNonBusinessOwner())
                    ->searchable(),
                IconColumn::make('is_vip')
                    ->label(__('pios.customer.vip.is_vip'))
                    ->boolean()
                    ->visible(self::visibleToNonBusinessOwner())
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('hp_no')
                    ->label('Phone (HP No)')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('tags')
                    ->label(__('pios.customer.tags.label'))
                    ->badge()
                    ->separator(',')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('businessOwner.applicant_name')
                    ->label('Business Owner')
                    ->placeholder(__('pios.customer.partner_owned'))
                    ->visible(self::visibleToNonBusinessOwner())
                    ->searchable(),
                TextColumn::make('category')
                    ->badge()
                    ->visible(self::visibleToNonBusinessOwner())
                    ->searchable(),
                TextColumn::make('kp_no')
                    ->label('IC/KP No')
                    ->visible(self::visibleToNonBusinessOwner())
                    ->searchable(),
                TextColumn::make('abo_no')
                    ->label('ABO No')
                    ->visible(self::visibleToNonBusinessOwner())
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->visible(self::visibleToNonBusinessOwner())
                    ->searchable(),
                TextColumn::make('partner.applicant_name')
                    ->label('Partner')
                    ->visible(self::visibleToNonBusinessOwner())
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nick_name')
                    ->visible(self::visibleToNonBusinessOwner())
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dd_name')
                    ->visible(self::visibleToNonBusinessOwner())
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dd_hp_no')
                    ->visible(self::visibleToNonBusinessOwner())
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->visible(self::visibleToNonBusinessOwner())
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->visible(self::visibleToNonBusinessOwner())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading(__('pios.customer.empty.heading'))
            ->emptyStateDescription(__('pios.customer.empty.hint'))
            ->emptyStateIcon('heroicon-o-user-plus')
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                CustomerContactActions::whatsApp(),
                CustomerContactActions::call(),
                ViewAction::make(),
                ActionGroup::make([
                    Action::make('addNote')
                        ->label(__('pios.customer.note.quick_add'))
                        ->icon('heroicon-o-pencil-square')
                        ->modalHeading(__('pios.customer.note.quick_add'))
                        ->visible(fn (Customer $record): bool => auth()->user()?->can('update', $record) ?? false)
                        ->schema([
                            Textarea::make('body')
                                ->label(__('pios.customer.note.body'))
                                ->required(),
                        ])
                        ->action(function (Customer $record, array $data): void {
                            abort_unless(auth()->user()?->can('update', $record) ?? false, 403);

                            $note = $record->notes()->create([
                                'body' => $data['body'],
                                'business_owner_id' => $record->business_owner_id,
                                'created_by' => auth()->id(),
                            ]);

                            app(AuditLogger::class)->log(
                                actor: auth()->user(),
                                event: 'created',
                                auditable: $note,
                                newValues: $note->fresh()->getAttributes(),
                            );

                            Notification::make()->success()->title(__('pios.customer.note.added'))->send();
                        }),
                    Action::make('setFollowUp')
                        ->label(__('pios.customer.followup.quick_add'))
                        ->icon('heroicon-o-calendar-days')
                        ->modalHeading(__('pios.customer.followup.quick_add'))
                        ->visible(fn (Customer $record): bool => auth()->user()?->can('update', $record) ?? false)
                        ->schema([
                            DatePicker::make('due_on')
                                ->label(__('pios.customer.followup.due_on'))
                                ->native(false)
                                ->required()
                                ->default(today()->addDays(3)),
                            Textarea::make('note')
                                ->label(__('pios.customer.followup.note')),
                        ])
                        ->action(function (Customer $record, array $data): void {
                            abort_unless(auth()->user()?->can('update', $record) ?? false, 403);

                            $followUp = $record->followUps()->create([
                                'due_on' => $data['due_on'],
                                'note' => $data['note'] ?? null,
                                'business_owner_id' => $record->business_owner_id,
                                'created_by' => auth()->id(),
                            ]);

                            app(AuditLogger::class)->log(
                                actor: auth()->user(),
                                event: 'created',
                                auditable: $followUp,
                                newValues: $followUp->fresh()->getAttributes(),
                            );

                            Notification::make()->success()->title(__('pios.customer.followup.added'))->send();
                        }),
                    Action::make('setStage')
                        ->label(__('pios.customer.stage.action'))
                        ->icon('heroicon-o-arrows-right-left')
                        ->modalHeading(__('pios.customer.stage.action'))
                        ->modalSubmitActionLabel(__('pios.customer.stage.action'))
                        ->visible(fn (Customer $record): bool => (auth()->user()?->can('update', $record) ?? false)
                            && in_array($record->category, self::settableStages(), true))
                        ->schema([
                            Select::make('category')
                                ->label(__('pios.customer.stage.label'))
                                ->options(self::settableStageOptions())
                                ->default(fn (Customer $record): ?string => $record->category?->value)
                                ->required(),
                        ])
                        ->action(function (Customer $record, array $data): void {
                            abort_unless(auth()->user()?->can('update', $record) ?? false, 403);

                            $target = CustomerCategory::tryFrom($data['category'] ?? '');
                            if ($target === null || ! in_array($target, self::settableStages(), true)) {
                                throw ValidationException::withMessages([
                                    'category' => __('pios.customer.stage.invalid'),
                                ]);
                            }

                            $old = $record->getOriginal();
                            $record->update(['category' => $target]);

                            app(AuditLogger::class)->log(
                                actor: auth()->user(),
                                event: 'updated',
                                auditable: $record,
                                oldValues: $old,
                                newValues: $record->fresh()->getAttributes(),
                            );

                            Notification::make()->success()->title(__('pios.customer.stage.updated'))->send();
                        }),
                    Action::make('tagVip')
                        ->label(__('pios.customer.vip.action'))
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->modalHeading(__('pios.customer.vip.action'))
                        ->modalSubmitActionLabel(__('pios.customer.vip.action'))
                        ->visible(fn (Customer $record): bool => auth()->user()?->can('tagVip', $record) ?? false)
                        ->schema([
                            Toggle::make('is_vip')
                                ->label(__('pios.customer.vip.is_vip'))
                                ->default(fn (Customer $record): bool => (bool) $record->is_vip)
                                ->live(),
                            TextInput::make('abo_no')
                                ->label(__('pios.customer.vip.abo_no'))
                                ->helperText(__('pios.customer.vip.abo_hint'))
                                ->default(fn (Customer $record): ?string => $record->abo_no)
                                ->visible(fn (Get $get): bool => (bool) $get('is_vip'))
                                ->maxLength(255),
                        ])
                        ->action(function (Customer $record, array $data): void {
                            abort_unless(auth()->user()?->can('tagVip', $record) ?? false, 403);

                            $isVip = (bool) ($data['is_vip'] ?? false);
                            $old = $record->getOriginal();

                            // VIP is a simple boolean flag; ABO No is kept only for VIP customers.
                            $attributes = ['is_vip' => $isVip];

                            if ($isVip) {
                                $attributes['abo_no'] = $data['abo_no'] ?? $record->abo_no;
                            }

                            $record->update($attributes);

                            app(AuditLogger::class)->log(
                                actor: auth()->user(),
                                event: 'updated',
                                auditable: $record,
                                oldValues: $old,
                                newValues: $record->fresh()->getAttributes(),
                            );

                            Notification::make()->success()->title(__('pios.customer.vip.updated'))->send();
                        }),
                    Action::make('manageTags')
                        ->label(__('pios.customer.tags.action'))
                        ->icon('heroicon-o-tag')
                        ->color('gray')
                        ->modalHeading(__('pios.customer.tags.action'))
                        ->modalSubmitActionLabel(__('pios.customer.tags.action'))
                        ->visible(fn (Customer $record): bool => auth()->user()?->can('tagLabels', $record) ?? false)
                        ->schema([
                            Select::make('tags')
                                ->label(__('pios.customer.tags.label'))
                                ->placeholder(__('pios.customer.tags.placeholder'))
                                ->multiple()
                                ->searchable()
                                // Admin-managed tag list (DP-15), plus the record's
                                // current tags so none is dropped on save.
                                ->options(fn (Customer $record): array => CustomerTag::options($record->tags ?? []))
                                ->default(fn (Customer $record): array => $record->tags ?? []),
                        ])
                        ->action(function (Customer $record, array $data): void {
                            abort_unless(auth()->user()?->can('tagLabels', $record) ?? false, 403);

                            $old = $record->getOriginal();
                            $tags = array_values(array_unique(array_filter($data['tags'] ?? [])));
                            $record->update(['tags' => $tags]);

                            app(AuditLogger::class)->log(
                                actor: auth()->user(),
                                event: 'updated',
                                auditable: $record,
                                oldValues: $old,
                                newValues: $record->fresh()->getAttributes(),
                            );

                            Notification::make()->success()->title(__('pios.customer.tags.updated'))->send();
                        }),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Business Owners get a minimal customer list (name, phone, email only);
     * the richer columns are reserved for HQ/admin users.
     */
    private static function visibleToNonBusinessOwner(): Closure
    {
        return fn (): bool => ! (auth()->user()?->actsAsBusinessOwner() ?? false);
    }

    /**
     * @return array<int, CustomerCategory> The only stages a BO may set (VIP/Non-LOS need LOS verification, out of scope).
     */
    private static function settableStages(): array
    {
        return [CustomerCategory::Lead, CustomerCategory::Prospect, CustomerCategory::Customer];
    }

    /**
     * @return array<string, string>
     */
    private static function settableStageOptions(): array
    {
        return [
            CustomerCategory::Lead->value => __('pios.customer.segments.lead'),
            CustomerCategory::Prospect->value => __('pios.customer.segments.prospect'),
            CustomerCategory::Customer->value => __('pios.customer.segments.customer'),
        ];
    }
}
