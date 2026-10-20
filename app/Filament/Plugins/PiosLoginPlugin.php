<?php

namespace App\Filament\Plugins;

use App\Filament\Pages\Auth\Login;
use Filament\Contracts\Plugin;
use Filament\Panel;

class PiosLoginPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'pios-login-branding';
    }

    public function register(Panel $panel): void
    {
        $panel->login(Login::class);
    }

    public function boot(Panel $panel): void {}
}
