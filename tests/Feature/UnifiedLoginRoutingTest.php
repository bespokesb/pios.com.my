<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedLoginRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_admin_panel_is_redirected_to_unified_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    public function test_guest_accessing_partner_panel_is_redirected_to_unified_login(): void
    {
        $response = $this->get('/partner');

        $response->assertRedirect(route('login'));
    }

    public function test_guest_accessing_business_panel_is_redirected_to_unified_login(): void
    {
        $response = $this->get('/business');

        $response->assertRedirect(route('login'));
    }

    public function test_direct_panel_login_urls_redirect_to_unified_login(): void
    {
        $this->get('/admin/login')->assertRedirect(route('login'));
        $this->post('/admin/login')->assertRedirect(route('login'));

        $this->get('/partner/login')->assertRedirect(route('login'));
        $this->post('/partner/login')->assertRedirect(route('login'));

        $this->get('/business/login')->assertRedirect(route('login'));
        $this->post('/business/login')->assertRedirect(route('login'));
    }

    public function test_logging_out_from_auth_redirects_to_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
