<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Closure;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('pios.customer.detail.contact'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('pios.customer.field.name')),
                        TextEntry::make('hp_no')
                            ->label(__('pios.customer.field.hp_no'))
                            ->placeholder('-'),
                        TextEntry::make('email')
                            ->label(__('pios.customer.field.email'))
                            ->placeholder('-'),
                        TextEntry::make('category')
                            ->label(__('pios.customer.field.category'))
                            ->badge(),
                        TextEntry::make('status')
                            ->label(__('pios.customer.field.status'))
                            ->badge(),
                        TextEntry::make('next_follow_up')
                            ->label(__('pios.customer.detail.next_follow_up'))
                            ->state(fn (Customer $record) => $record->followUps()
                                ->whereNull('completed_at')
                                ->orderBy('due_on')
                                ->first()?->due_on)
                            ->badge()
                            ->date()
                            ->color(fn ($state): string => $state && $state->startOfDay()->lt(now('Asia/Kuala_Lumpur')->startOfDay())
                                ? 'danger'
                                : 'warning')
                            ->placeholder(__('pios.customer.detail.no_follow_up')),
                        TextEntry::make('created_at')
                            ->label(__('pios.customer.detail.customer_since'))
                            ->date(),
                        TextEntry::make('tags')
                            ->label(__('pios.customer.tags.label'))
                            ->badge()
                            ->separator(',')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('pios.customer.detail.admin'))
                    ->visible(self::visibleToNonBusinessOwner())
                    ->schema([
                        IconEntry::make('is_vip')
                            ->label(__('pios.customer.vip.is_vip'))
                            ->boolean()
                            ->visible(self::visibleToNonBusinessOwner()),
                        TextEntry::make('kp_no')
                            ->label(__('pios.customer.field.kp_no'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->placeholder('-'),
                        TextEntry::make('abo_no')
                            ->label(__('pios.customer.field.abo_no'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->placeholder('-'),
                        TextEntry::make('dd_name')
                            ->label(__('pios.customer.field.dd_name'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->placeholder('-'),
                        TextEntry::make('dd_hp_no')
                            ->label(__('pios.customer.field.dd_hp_no'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->placeholder('-'),
                        TextEntry::make('nick_name')
                            ->label(__('pios.customer.field.nick_name'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->placeholder('-'),
                        TextEntry::make('address')
                            ->label(__('pios.customer.field.address'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('businessOwner.applicant_name')
                            ->label(__('pios.customer.field.business_owner'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->placeholder('-'),
                        TextEntry::make('partner.applicant_name')
                            ->label(__('pios.customer.field.partner'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label(__('pios.customer.field.created_at'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label(__('pios.customer.field.updated_at'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Business Owners see only the core contact fields; LOS/PII/admin entries
     * stay reserved for HQ/admin users. Mirrors the closure used in
     * CustomersTable and CustomerForm — do NOT invent a parallel role check.
     */
    private static function visibleToNonBusinessOwner(): Closure
    {
        return fn (): bool => ! (auth()->user()?->actsAsBusinessOwner() ?? false);
    }
}
