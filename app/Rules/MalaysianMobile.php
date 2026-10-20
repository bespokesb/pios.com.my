<?php

namespace App\Rules;

use App\Support\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Passes when the value is blank (optionality is handled separately via
 * ->required()) or a valid Malaysian mobile number. Implemented as a rule
 * object — not a bare closure — so Filament's ->rule() treats it as a rule
 * instance rather than a utility closure it would try to inject arguments into.
 */
class MalaysianMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filled($value) && ! Phone::isValidMobile(is_string($value) ? $value : null)) {
            $fail(__('pios.validation.phone'));
        }
    }
}
