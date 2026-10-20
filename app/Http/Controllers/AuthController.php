<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
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
        ]);

        $login = (string) $credentials['piosId'];
        $email = $this->resolvePiosIdToEmail($login);
        $remember = (bool) ($credentials['rememberMe'] ?? false);

        if (! Auth::attempt(['email' => $email, 'password' => $credentials['password']], $remember)) {
            // Check if login matches a registered Customer
            $customer = $this->resolveCustomerByIdentifier($login);
            if ($customer) {
                $customerData = [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->hp_no,
                    'email' => $customer->email,
                    'kp_no' => $customer->kp_no,
                    'address' => $customer->address,
                    'is_vip' => (bool) $customer->is_vip,
                ];

                session()->put('market_customer', $customerData);
                session()->put('market_customer_id', $customer->id);
                session()->put('market_customer_name', $customer->name);
                session()->put('market_customer_phone', $customer->hp_no);
                session()->put('market_customer_email', $customer->email);
                session()->put('market_customer_kp_no', $customer->kp_no);
                session()->put('market_customer_address', $customer->address);

                return redirect()->intended(route('marketplace.index'))
                    ->with('customer_status', __('Welcome back, :name!', ['name' => $customer->name]));
            }

            throw ValidationException::withMessages([
                'piosId' => __('pios.auth.failed', ['attribute' => 'PIOS ID / Password']),
            ]);
        }

        $user = Auth::user();

        if (! $user instanceof User || $user->status !== UserStatus::Active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

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
            route('filament.business.auth.login'),
        ], true)) {
            return redirect()->to($intended);
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

            // Customer by phone matching User by email
            $custPhoneMatch = Customer::query()
                ->whereNotNull('email')
                ->get(['email', 'hp_no'])
                ->first(fn ($row): bool => Phone::digits((string) $row->hp_no) === $digits
                    || Phone::toWhatsApp((string) $row->hp_no) === Phone::toWhatsApp($login));

            if ($custPhoneMatch && User::query()->where('email', $custPhoneMatch->email)->exists()) {
                return $custPhoneMatch->email;
            }
        }

        return $login;
    }

    protected function resolveCustomerByIdentifier(string $login): ?Customer
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        $customer = Customer::query()
            ->where('customer_no', $login)
            ->orWhere('kp_no', $login)
            ->orWhere('email', $login)
            ->first();

        if ($customer) {
            return $customer;
        }

        $digits = Phone::digits($login);
        if ($digits !== '') {
            $customer = Customer::query()
                ->where('hp_no_normalized', Phone::toWhatsApp($login))
                ->orWhere('hp_no', $login)
                ->first();

            if (! $customer) {
                $customer = Customer::query()
                    ->get(['id', 'hp_no', 'name', 'email', 'kp_no', 'address', 'is_vip', 'business_owner_id', 'partner_id'])
                    ->first(fn ($c): bool => Phone::digits((string) $c->hp_no) === $digits
                        || Phone::toWhatsApp((string) $c->hp_no) === Phone::toWhatsApp($login));
            }
        }

        return $customer;
    }
}
