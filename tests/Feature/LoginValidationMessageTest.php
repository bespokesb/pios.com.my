<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginValidationMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_login_credentials_shows_human_readable_error(): void
    {
        $user = User::factory()->create([
            'email' => 'validuser@example.com',
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'piosId' => 'validuser@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'piosId' => 'Please enter correct ID and PASSWORD',
        ]);

        $errorMessage = session('errors')->first('piosId');
        $this->assertSame('Please enter correct ID and PASSWORD', $errorMessage);
        $this->assertStringNotContainsString('pios.auth.failed', $errorMessage);

        $view = $this->view('auth.login', ['errors' => session('errors')]);
        $view->assertSee('Please enter correct ID and PASSWORD');
        $view->assertDontSee('pios.auth.failed');
    }

    public function test_non_existent_identifier_shows_human_readable_error(): void
    {
        $response = $this->from(route('login'))->post(route('login.submit'), [
            'piosId' => 'azizjr82@gmail.com',
            'password' => 'wrongpassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'piosId' => 'Please enter correct ID and PASSWORD',
        ]);

        $errorMessage = session('errors')->first('piosId');
        $this->assertSame('Please enter correct ID and PASSWORD', $errorMessage);
        $this->assertStringNotContainsString('pios.auth.failed', $errorMessage);

        $view = $this->view('auth.login', ['errors' => session('errors')]);
        $view->assertSee('Please enter correct ID and PASSWORD');
        $view->assertDontSee('pios.auth.failed');
    }

    public function test_empty_login_fields_show_human_readable_validation_messages(): void
    {
        $response = $this->from(route('login'))->post(route('login.submit'), [
            'piosId' => '',
            'password' => '',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'piosId' => 'Please enter your PIOS ID',
            'password' => 'Please enter your password',
        ]);

        $view = $this->view('auth.login', ['errors' => session('errors')]);
        $view->assertSee('Please enter your PIOS ID');
        $view->assertSee('Please enter your password');
        $view->assertDontSee('pios.auth.failed');
    }

    public function test_pios_auth_failed_translation_key_resolves_to_human_readable_text(): void
    {
        $this->assertSame('Please enter correct ID and PASSWORD', __('pios.auth.failed'));
        $this->assertSame('Please enter correct ID and PASSWORD', trans('pios.auth.failed', [], 'en'));
        $this->assertSame('Please enter correct ID and PASSWORD', trans('pios.auth.failed', [], 'ms'));
    }

    public function test_login_page_renders_single_warning_banner_and_no_inline_duplicate_under_pios_id(): void
    {
        $this->from(route('login'))->post(route('login.submit'), [
            'piosId' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ])->assertRedirect(route('login'));

        $rendered = $this->get(route('login'))->getContent();

        $this->assertStringContainsString('Log Masuk Tidak Berjaya:', $rendered);
        $this->assertStringContainsString('Please enter correct ID and PASSWORD', $rendered);

        // Exactly 1 occurrence on screen, not duplicated below the PIOS ID field
        $this->assertSame(1, substr_count($rendered, 'Please enter correct ID and PASSWORD'));
    }
}
