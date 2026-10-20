<?php

namespace App\Casts;

use App\Support\Phone;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a phone number in one canonical, symbol-free form: Malaysian wa.me
 * digits (e.g. 60123456789). Applied to every phone column so ANY write path —
 * Filament forms, the public order endpoint, seeders, tinker — persists clean
 * digits, never "012-345 6789". Blank/unparseable → null.
 *
 * @implements CastsAttributes<?string, ?string>
 */
class PhoneNumber implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return ($value === null || $value === '') ? null : (string) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Phone::toWhatsApp((string) $value);
    }
}
