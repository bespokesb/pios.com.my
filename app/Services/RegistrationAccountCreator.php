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
        ?string $password = null,
    ): User {
        return $this->createLinkedUser(
            role: UserRole::Partner,
            name: $partner->applicant_name,
            email: $partner->email,
            panelId: 'partner',
            status: $status,
            attach: fn (User $user): bool => $partner->forceFill(['user_id' => $user->id])->save(),
            password: $password,
            ownerId: $partner->id,
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
            ownerId: $businessOwner->id,
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
        ?int $ownerId = null,
    ): User {
        if (blank($email)) {
            throw ValidationException::withMessages([
                'data.email' => 'Email is required to create a login account.',
                'email' => 'Email is required to create a login account.',
            ]);
        }

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser) {
            if ($existingUser->isAdmin() || $existingUser->isPiosOwner()) {
                throw ValidationException::withMessages([
                    'data.email' => 'This email belongs to an administrative account and cannot be used.',
                    'email' => 'This email belongs to an administrative account and cannot be used.',
                ]);
            }

            if ($role === UserRole::Partner) {
                $otherPartner = Partner::query()->where('user_id', $existingUser->id)->first();
                if ($otherPartner && $otherPartner->id !== $ownerId) {
                    throw ValidationException::withMessages([
                        'data.email' => 'A user account already exists for another Partner with this email.',
                        'email' => 'A user account already exists for another Partner with this email.',
                    ]);
                }
            }

            if ($role === UserRole::BusinessOwner) {
                $otherBo = BusinessOwner::query()->where('user_id', $existingUser->id)->first();
                if ($otherBo && $otherBo->id !== $ownerId) {
                    throw ValidationException::withMessages([
                        'data.email' => 'A user account already exists for another Business Owner with this email.',
                        'email' => 'A user account already exists for another Business Owner with this email.',
                    ]);
                }
            }

            $userUpdates = [
                'name' => $name,
                'role' => $role,
                'status' => $status,
            ];

            if (filled($password)) {
                $userUpdates['password'] = Hash::make($password);
            }

            $existingUser->forceFill($userUpdates)->save();
            $attach($existingUser);

            if (blank($password) && blank($existingUser->password)) {
                $this->sendActivationLink($existingUser, $panelId);
            }

            return $existingUser;
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
