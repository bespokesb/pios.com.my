<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Partners\Pages\CreatePartner;
use App\Models\AuditLog;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\AccountActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_partner_with_generated_partner_no(): void
    {
        $this->actingAs($this->adminUser());

        Livewire::test(CreatePartner::class)
            ->fillForm([
                'applicant_name' => 'Partner One',
                'ic_no' => '800101010001',
                'hp_no' => '60120000001',
                'email' => 'partner-one@pios.test',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $partner = Partner::query()->firstWhere('email', 'partner-one@pios.test');

        $this->assertNotNull($partner);
        $this->assertSame('P000001', $partner->partner_no);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => Partner::class,
            'auditable_id' => $partner->id,
            'event' => 'created',
        ]);
    }

    public function test_partner_account_creation_emails_an_activation_link_no_password_set(): void
    {
        Notification::fake();
        $this->actingAs($this->adminUser());

        Livewire::test(CreatePartner::class)
            ->fillForm([
                'applicant_name' => 'Partner With Login',
                'email' => 'partner-login@pios.test',
                'status' => 'active',
                'create_user' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $partner = Partner::query()->firstWhere('email', 'partner-login@pios.test');
        $user = User::query()->firstWhere('email', 'partner-login@pios.test');

        $this->assertNotNull($partner);
        $this->assertNotNull($user);
        $this->assertSame($user->id, $partner->user_id);
        $this->assertTrue($user->role === UserRole::Partner);

        // The user sets their own password via an emailed activation link —
        // the creator never chose one, so 'password' does NOT authenticate.
        $this->assertFalse(Hash::check('password', $user->password));
        Notification::assertSentTo($user, AccountActivation::class);
    }

    public function test_duplicate_partner_ic_and_hp_are_blocked(): void
    {
        $this->actingAs($this->adminUser());

        Partner::query()->create([
            'partner_no' => 'P000900',
            'applicant_name' => 'Existing Partner',
            'ic_no' => '800101010002',
            'hp_no' => '60120000002',
        ]);

        Livewire::test(CreatePartner::class)
            ->fillForm([
                'applicant_name' => 'Duplicate IC',
                'ic_no' => '800101010002',
                'hp_no' => '60120000003',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasFormErrors(['ic_no']);

        Livewire::test(CreatePartner::class)
            ->fillForm([
                'applicant_name' => 'Duplicate HP',
                'ic_no' => '800101010003',
                'hp_no' => '60120000002',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasFormErrors(['hp_no']);
    }

    public function test_partner_admin_routes_are_registered(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('admin/partners', $routes);
        $this->assertContains('admin/partners/create', $routes);
        $this->assertContains('admin/partners/{record}/edit', $routes);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
    }
}
