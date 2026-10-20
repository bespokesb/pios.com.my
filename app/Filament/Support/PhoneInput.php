<?php

namespace App\Filament\Support;

use App\Support\Phone;
use Filament\Forms\Components\TextInput;

/**
 * A phone-number field with a fixed +60 (Malaysia) country-code prefix. The user
 * types only the local subscriber part; the value is stored normalised to full
 * international digits (e.g. 60123456789) and displayed back without the prefix.
 *
 * Country dropdown (future): the '+60' prefix and the '60' passed to Phone are
 * the single point to swap for a country <select> — make this prefix dynamic and
 * carry the dialling code through to Phone::national()/toWhatsApp(). Nothing
 * else in the forms needs to change.
 */
class PhoneInput
{
    private const COUNTRY_CODE = '60';

    private const LOCAL_PLACEHOLDER = '12-345 6789';

    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->tel()
            ->prefix('+'.self::COUNTRY_CODE)
            ->placeholder(self::LOCAL_PLACEHOLDER)
            ->rule(Phone::rule())
            // Show the stored value without the country-code prefix…
            ->formatStateUsing(fn (?string $state): string => Phone::national($state, self::COUNTRY_CODE))
            // …and store the full normalised international digits.
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                ? (Phone::toWhatsApp($state, self::COUNTRY_CODE) ?? $state)
                : null)
            ->maxLength(255);
    }
}
