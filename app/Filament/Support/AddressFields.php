<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

/**
 * The standard PIOS address form: three address lines (address = line 1) plus
 * Bandar / Negeri / Poskod. Shared verbatim by every resource that captures an
 * address (Customer, Business Owner, …) so the shape stays identical everywhere.
 *
 * Drop the returned array into a Section using ->columns(3): the line inputs
 * span the full width and Bandar / Negeri / Poskod sit on one row.
 *
 * @return array<int, TextInput>
 */
class AddressFields
{
    /**
     * @return array<int, TextInput>
     */
    public static function make(): array
    {
        return [
            TextInput::make('address')
                ->label(__('pios.address.line1'))
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('address_line_2')
                ->label(__('pios.address.line2'))
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('address_line_3')
                ->label(__('pios.address.line3'))
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('bandar')
                ->label(__('pios.address.bandar'))
                ->maxLength(255),
            TextInput::make('negeri')
                ->label(__('pios.address.negeri'))
                ->maxLength(255),
            TextInput::make('poskod')
                ->label(__('pios.address.poskod'))
                ->maxLength(16),
        ];
    }
}
