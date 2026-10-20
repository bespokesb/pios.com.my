<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Pages\Auth\Concerns\ResolvesPiosIdLogin;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class BusinessLogin extends BaseLogin
{
    use ResolvesPiosIdLogin;

    protected static string $layout = 'filament-panels::components.layout.base';

    protected string $view = 'filament.pages.auth.business-login';

    protected Width|string|null $maxWidth = Width::Full;

    public function getTitle(): string|Htmlable
    {
        return __('pios.auth.business.title');
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('pios.auth.business.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('pios.auth.business.subheading');
    }

    public function hasLogo(): bool
    {
        return false;
    }
}
