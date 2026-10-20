<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Pages\Auth\Concerns\ResolvesPiosIdLogin;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    use ResolvesPiosIdLogin;

    protected string $view = 'filament.pages.auth.login';

    protected Width|string|null $maxWidth = Width::SevenExtraLarge;

    /**
     * The lang prefix for this login's copy, resolved from the active panel so
     * the shared login page shows admin- or partner-appropriate wording.
     */
    public function authKey(): string
    {
        return filament()->getCurrentPanel()?->getId() === 'partner'
            ? 'pios.auth.partner'
            : 'pios.auth.admin';
    }

    public function getTitle(): string|Htmlable
    {
        return __($this->authKey().'.title');
    }

    public function getHeading(): string|Htmlable|null
    {
        return __($this->authKey().'.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __($this->authKey().'.subheading');
    }

    public function hasLogo(): bool
    {
        return false;
    }
}
