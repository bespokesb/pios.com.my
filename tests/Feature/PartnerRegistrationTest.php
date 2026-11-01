<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Partners\Pages\CreatePartner;
use App\Filament\Resources\Partners\Pages\EditPartner;
use App\Models\AuditLog;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\AccountActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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

    public function test_partner_account_creation_with_direct_password_authenticates_immediately(): void
    {
        Notification::fake();
        $this->actingAs($this->adminUser());

        Livewire::test(CreatePartner::class)
            ->fillForm([
                'applicant_name' => 'Partner Direct Password',
                'email' => 'partner-direct@pios.test',
                'status' => 'active',
                'create_user' => true,
                'account_password' => 'partner-pass-123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $partner = Partner::query()->firstWhere('email', 'partner-direct@pios.test');
        $user = User::query()->firstWhere('email', 'partner-direct@pios.test');

        $this->assertNotNull($partner);
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('partner-pass-123', $user->password));
        $this->assertSame(UserStatus::Active, $user->status);
        Notification::assertNothingSent();

        // Verify login works
        $this->assertTrue(
            Auth::attempt([
                'email' => 'partner-direct@pios.test',
                'password' => 'partner-pass-123',
            ])
        );
    }

    public function test_admin_can_set_direct_password_on_edit_partner_page(): void
    {
        $admin = $this->adminUser();

        $partnerUser = User::factory()->create([
            'email' => 'edit-partner@example.com',
            'password' => Hash::make('old-partner-pass'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::query()->create([
            'user_id' => $partnerUser->id,
            'partner_no' => 'P000888',
            'applicant_name' => 'Partner To Edit',
            'email' => 'edit-partner@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditPartner::class, ['record' => $partner->id])
            ->fillForm([
                'create_user' => true,
                'account_password' => 'new-partner-pass-999',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $partnerUser->refresh();
        $this->assertTrue(Hash::check('new-partner-pass-999', $partnerUser->password));
        $this->assertSame(UserStatus::Active, $partnerUser->status);

        // Verify login works
        $this->assertTrue(
            Auth::attempt([
                'email' => 'edit-partner@example.com',
                'password' => 'new-partner-pass-999',
            ])
        );
    }

    public function test_partner_admin_routes_are_registered(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('admin/partners', $routes);
        $this->assertContains('admin/partners/create', $routes);
        $this->assertContains('admin/partners/{record}/edit', $routes);
    }

    public function test_rapid_double_click_on_create_partner_does_not_create_duplicate_record(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        // First click
        Livewire::test(CreatePartner::class)
            ->fillForm([
                'applicant_name' => 'Fast Click Partner',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Partner::query()->where('applicant_name', 'Fast Click Partner')->count());

        // Second click within 15 seconds (same user, same applicant name)
        Livewire::test(CreatePartner::class)
            ->fillForm([
                'applicant_name' => 'Fast Click Partner',
                'status' => 'active',
            ])
            ->call('create');

        $this->assertSame(1, Partner::query()->where('applicant_name', 'Fast Click Partner')->count());
    }

    public function test_edit_partner_links_existing_unlinked_user_and_saves_successfully(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        $unlinkedUser = User::factory()->create([
            'email' => 'unlinked-partner@pios.test',
            'name' => 'Unlinked Partner User',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::query()->create([
            'user_id' => null,
            'partner_no' => 'P000999',
            'applicant_name' => 'Unlinked Partner',
            'email' => 'unlinked-partner@pios.test',
            'status' => 'active',
        ]);

        Livewire::test(EditPartner::class, ['record' => $partner->id])
            ->fillForm([
                'create_user' => true,
                'account_password' => 'linked-pass-123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $partner->refresh();
        $this->assertSame($unlinkedUser->id, $partner->user_id);
        $this->assertTrue(Hash::check('linked-pass-123', $unlinkedUser->fresh()->password));
        $this->assertDatabaseHas('business_owners', [
            'user_id' => $unlinkedUser->id,
            'email' => 'unlinked-partner@pios.test',
        ]);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
    }
}
