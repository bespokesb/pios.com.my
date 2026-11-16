<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            return redirect()->to($user instanceof User ? $user->getDashboardUrl() : route('home'));
        }

        if (session()->has('market_customer_id')) {
            return redirect()->route('marketplace.index');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'piosId' => ['required', 'string'],
            'password' => ['required', 'string'],
            'rememberMe' => ['nullable', 'boolean'],
        ], [
            'piosId.required' => 'Please enter your PIOS ID',
            'password.required' => 'Please enter your password',
        ]);

        $login = (string) $credentials['piosId'];
        $email = $this->resolvePiosIdToEmail($login);
        $remember = (bool) ($credentials['rememberMe'] ?? false);

        // Check if account is overdue locked (> 91 days) before checking password
        $targetUser = User::where('email', $email)->first();
        if (! $targetUser) {
            $bo = BusinessOwner::where('business_owner_no', $login)->first();
            $targetUser = $bo?->user;
        }

        if ($targetUser && $targetUser->isOverdueLocked()) {
            $partner = $targetUser->businessOwner?->partner;
            $partnerName = $partner?->applicant_name;
            $partnerPhone = $partner?->hp_no ?: ($partner?->spouse_hp_no ?: '');
            $contactInfo = '';
            if ($partnerName && $partnerPhone) {
                $contactInfo = " ({$partnerName} - {$partnerPhone})";
            } elseif ($partnerName) {
                $contactInfo = " ({$partnerName})";
            } elseif ($partnerPhone) {
                $contactInfo = " ({$partnerPhone})";
            }

            throw ValidationException::withMessages([
                'piosId' => 'Akaun anda telah disekat kerana bayaran tertunggak melebihi 90 hari. Sila hubungi Partner anda'.$contactInfo.' untuk mengaktifkan semula akaun. (Account locked. Please contact your partner.)',
            ]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $credentials['password']], $remember)) {
            $failedMsg = __('pios.auth.failed');
            if ($failedMsg === 'pios.auth.failed' || blank($failedMsg)) {
                $failedMsg = 'Please enter correct ID and PASSWORD';
            }

            throw ValidationException::withMessages([
                'piosId' => $failedMsg,
            ]);
        }

        $user = Auth::user();

        if (! $user instanceof User || $user->status !== UserStatus::Active || $user->isOverdueLocked()) {
            $isOverdue = $user instanceof User && $user->isOverdueLocked();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($isOverdue) {
                $partner = $user->businessOwner?->partner;
                $partnerName = $partner?->applicant_name;
                $partnerPhone = $partner?->hp_no ?: ($partner?->spouse_hp_no ?: '');
                $contactInfo = '';
                if ($partnerName && $partnerPhone) {
                    $contactInfo = " ({$partnerName} - {$partnerPhone})";
                } elseif ($partnerName) {
                    $contactInfo = " ({$partnerName})";
                } elseif ($partnerPhone) {
                    $contactInfo = " ({$partnerPhone})";
                }

                throw ValidationException::withMessages([
                    'piosId' => 'Akaun anda telah disekat kerana bayaran tertunggak melebihi 90 hari. Sila hubungi Partner anda'.$contactInfo.' untuk mengaktifkan semula akaun. (Account locked. Please contact your partner.)',
                ]);
            }

            throw ValidationException::withMessages([
                'piosId' => 'Account is inactive. Please contact your PIOS Partner or Administrator.',
            ]);
        }

        $user->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        if ($intended && ! in_array($intended, [
            route('business.dashboard'),
            url('/dashboard'),
            url('/business'),
            route('login'),
            url('/login'),
            url('/admin/login'),
            url('/partner/login'),
            url('/business/login'),
        ], true)) {
            return redirect()->to($intended);
        }

        $loginUpper = strtoupper(trim($login));
        if (str_starts_with($loginUpper, 'BO') && $user->isBusinessOwner()) {
            return redirect()->to(route('business.dashboard'));
        }

        if (str_starts_with($loginUpper, 'P') && $user->isPartner()) {
            return redirect()->to(route('filament.partner.pages.dashboard'));
        }

        return redirect()->to($user->getDashboardUrl());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->forget([
            'market_customer',
            'market_customer_id',
            'market_customer_name',
            'market_customer_phone',
            'market_customer_email',
            'market_customer_kp_no',
            'market_customer_address',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function resolvePiosIdToEmail(string $login): string
    {
        $login = trim($login);

        if ($login === '' || str_contains($login, '@')) {
            return $login;
        }

        // 1. Check by Business Owner No or Partner No
        $boByNo = BusinessOwner::query()
            ->where('business_owner_no', $login)
            ->whereNotNull('user_id')
            ->with('user:id,email')
            ->first();
        if ($boByNo?->user?->email) {
            return $boByNo->user->email;
        }

        $partnerByNo = Partner::query()
            ->where('partner_no', $login)
            ->whereNotNull('user_id')
            ->with('user:id,email')
            ->first();
        if ($partnerByNo?->user?->email) {
            return $partnerByNo->user->email;
        }

        // 2. Check IC number
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

        // 3. Check Phone number
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
        }

        return $login;
    }
}
