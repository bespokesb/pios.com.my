<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_renders_the_welcome_view(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_locale_switcher_stores_supported_locale_and_localizes_business_login(): void
    {
        $this->withHeader('referer', 'http://localhost/login')
            ->get('/locale/ms')
            ->assertRedirect('http://localhost/login')
            ->assertSessionHas('locale', 'ms');

        app()->setLocale('en');

        $this->withSession(['locale' => 'ms'])
            ->get('/login')
            ->assertOk()
            ->assertSee('Log Masuk');
    }

    public function test_locale_switcher_rejects_unsupported_locale(): void
    {
        $this->get('/locale/fr')->assertNotFound();
    }
}
