<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use Filament\Facades\Filament;
use Tests\TestCase;

class LoginCopyAndLocaleTest extends TestCase
{
    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_default_application_locale_is_english(): void
    {
        $this->assertSame('en', config('app.locale'));
    }

    public function test_login_copy_is_panel_aware(): void
    {
        $login = new Login;

        Filament::setCurrentPanel('admin');
        $this->assertSame('pios.auth.admin', $login->authKey());

        Filament::setCurrentPanel('partner');
        $this->assertSame('pios.auth.partner', $login->authKey());
    }

    public function test_partner_and_business_login_copy_exists_in_both_locales(): void
    {
        foreach (['en', 'ms'] as $locale) {
            foreach (['partner', 'business'] as $panel) {
                foreach (['title', 'heading', 'subheading', 'kicker', 'hero_title', 'hero_copy', 'secure_access'] as $key) {
                    $value = __("pios.auth.{$panel}.{$key}", [], $locale);
                    $this->assertIsString($value);
                    $this->assertNotSame("pios.auth.{$panel}.{$key}", $value, "Missing {$locale} {$panel}.{$key}");
                }
                $this->assertIsArray(__("pios.auth.{$panel}.chips", [], $locale));
            }
        }
    }
}
