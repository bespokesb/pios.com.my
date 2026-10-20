<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\AccountActivation;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationAccountCreator
{
    public function createForPartner(
        Partner $partner,
        UserStatus $status = UserStatus::Active,
    ): User {
        return $this->createLinkedUser(
            role: UserRole::Partner,
            name: $partner->applicant_name,
            email: $partner->email,
            panelId: 'partner',
            status: $status,
            attach: fn (User $user): bool => $partner->forceFill(['user_id' => $user->id])->save(),
        );
    }

    public function createForBusinessOwner(
        BusinessOwner $businessOwner,
        UserStatus $status = UserStatus::Active,
        ?string $password = null,
    ): User {
        return $this->createLinkedUser(
            role: UserRole::BusinessOwner,
            name: $businessOwner->applicant_name,
            email: $businessOwner->email,
            panelId: 'business',
            status: $status,
            attach: fn (User $user): bool => $businessOwner->forceFill(['user_id' => $user->id])->save(),
            password: $password,
        );
    }

    /**
     * Email an existing user a panel-scoped link to set their password and
     * activate — reuses the password-reset token machinery.
     */
    public function sendActivationLink(User $user, string $panelId): void
    {
        $token = Password::broker()->createToken($user);
        $panel = Filament::getPanel($panelId, isStrict: false);

        $url = $panel
            ? $panel->getResetPasswordUrl($token, $user)
            : url(route('password.reset', ['token' => $token, 'email' => $user->email], absolute: false));

        $user->notify(new AccountActivation($url, $panel?->getBrandName() ?? 'PIOS'));
    }

    private function createLinkedUser(
        UserRole $role,
        string $name,
        ?string $email,
        string $panelId,
        callable $attach,
        UserStatus $status = UserStatus::Active,
        ?string $password = null,
    ): User {
        if (blank($email)) {
            throw ValidationException::withMessages([
                'email' => 'Email is required to create a login account.',
            ]);
        }

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'A user account already exists for this email.',
            ]);
        }

        // If a password was set at creation the account uses it directly; otherwise
        // it starts with an unusable random secret and the user sets their own via
        // the emailed activation link.
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(filled($password) ? $password : Str::random(40)),
            'role' => $role,
            'status' => $status,
        ]);

        $attach($user);

        // Only email an activation link when no password was set here — a
        // password-set account already has a working credential.
        if (blank($password)) {
            $this->sendActivationLink($user, $panelId);
        }

        return $user;
    }
}
