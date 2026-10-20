<?php

namespace App\Filament\Pages\Auth\Concerns;

use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;
use App\Support\Phone;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

/**
 * Sign in with a "PIOS ID" — an email address, phone number, OR IC number —
 * instead of email only (per the client mockup). The identifier is resolved to
 * the account's email before Filament authenticates, so the password check and
 * every downstream guard are unchanged.
 */
trait ResolvesPiosIdLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('pios.auth.pios_id'))
            ->placeholder(__('pios.auth.pios_id_placeholder'))
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'email' => $this->resolvePiosIdToEmail((string) ($data['email'] ?? '')),
            'password' => $data['password'],
        ];
    }

    protected function resolvePiosIdToEmail(string $login): string
    {
        $login = trim($login);

        // Already an email (or empty — let authentication fail normally).
        if ($login === '' || str_contains($login, '@')) {
            return $login;
        }

        // IC number — exact match on a Partner or Business Owner profile.
        foreach ([Partner::class, BusinessOwner::class] as $model) {
            $email = $model::query()
                ->where('ic_no', $login)
                ->whereNotNull('user_id')
                ->with('user:id,email')
                ->first()?->user?->email;

            if ($email) {
                return $email;
            }
        }

        // Check Customer IC number if matching a user
        $customerEmail = Customer::query()
            ->where('kp_no', $login)
            ->whereNotNull('email')
            ->value('email');
        if ($customerEmail && User::query()->where('email', $customerEmail)->exists()) {
            return $customerEmail;
        }

        // Phone number — compare normalised digits (handles +60 / 0 / spacing).
        $digits = Phone::digits($login);

        if ($digits !== '') {
            foreach ([Partner::class, BusinessOwner::class] as $model) {
                $match = $model::query()
                    ->whereNotNull('user_id')
                    ->whereNotNull('hp_no')
                    ->get(['user_id', 'hp_no'])
                    ->first(fn ($row): bool => Phone::digits($row->hp_no) === $digits
                        || Phone::toWhatsApp($row->hp_no) === Phone::toWhatsApp($login));

                if ($match) {
                    $email = User::query()->whereKey($match->user_id)->value('email');

                    if ($email) {
                        return $email;
                    }
                }
            }

            $custPhoneMatch = Customer::query()
                ->whereNotNull('email')
                ->get(['email', 'hp_no'])
                ->first(fn ($row): bool => Phone::digits((string) $row->hp_no) === $digits
                    || Phone::toWhatsApp((string) $row->hp_no) === Phone::toWhatsApp($login));

            if ($custPhoneMatch && User::query()->where('email', $custPhoneMatch->email)->exists()) {
                return $custPhoneMatch->email;
            }
        }

        // Unresolved — return as-is so authentication fails with invalid credentials.
        return $login;
    }
}
