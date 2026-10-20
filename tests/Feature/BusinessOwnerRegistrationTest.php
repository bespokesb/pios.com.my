<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\BusinessOwners\Pages\CreateBusinessOwner;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessOwnerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_create_business_owner_under_own_partner_record(): void
    {
        [$partnerUser, $partner] = $this->partnerUser('P000501', 'Partner One');
        $otherPartner = Partner::query()->create([
            'partner_no' => 'P000502',
            'applicant_name' => 'Partner Two',
        ]);

        $this->actingAs($partnerUser);

        Livewire::test(CreateBusinessOwner::class)
            ->fillForm([
                'partner_id' => $otherPartner->id,
                'applicant_name' => 'Business Owner One',
                'ic_no' => '810101010001',
                'hp_no' => '60130000001',
                'email' => 'bo-one@pios.test',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $businessOwner = BusinessOwner::query()->firstWhere('email', 'bo-one@pios.test');

        $this->assertNotNull($businessOwner);
        $this->assertSame($partner->id, $businessOwner->partner_id);
        $this->assertSame('BO000001', $businessOwner->business_owner_no);
    }

    public function test_admin_can_create_business_owner_for_any_partner(): void
    {
        $admin = $this->adminUser();
        $partner = Partner::query()->create([
            'partner_no' => 'P000503',
            'applicant_name' => 'Admin Selected Partner',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateBusinessOwner::class)
            ->fillForm([
                'partner_id' => $partner->id,
                'applicant_name' => 'Business Owner Admin',
                'email' => 'bo-admin@pios.test',
                'status' => 'active',
                'create_user' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $businessOwner = BusinessOwner::query()->firstWhere('email', 'bo-admin@pios.test');
        $user = User::query()->firstWhere('email', 'bo-admin@pios.test');

        $this->assertNotNull($businessOwner);
        $this->assertSame($partner->id, $businessOwner->partner_id);
        $this->assertSame($user->id, $businessOwner->user_id);
        $this->assertTrue($user->role === UserRole::BusinessOwner);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => BusinessOwner::class,
            'auditable_id' => $businessOwner->id,
            'event' => 'created',
        ]);
    }

    public function test_duplicate_business_owner_ic_and_hp_are_blocked_within_partner_scope(): void
    {
        $admin = $this->adminUser();
        $partner = Partner::query()->create([
            'partner_no' => 'P000504',
            'applicant_name' => 'Duplicate Scope Partner',
        ]);
        $otherPartner = Partner::query()->create([
            'partner_no' => 'P000505',
            'applicant_name' => 'Other Partner',
        ]);

        BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000900',
            'applicant_name' => 'Existing Business Owner',
            'ic_no' => '810101010002',
            'hp_no' => '60130000002',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateBusinessOwner::class)
            ->fillForm([
                'partner_id' => $partner->id,
                'applicant_name' => 'Duplicate IC',
                'ic_no' => '810101010002',
                'hp_no' => '60130000003',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasFormErrors(['ic_no']);

        Livewire::test(CreateBusinessOwner::class)
            ->fillForm([
                'partner_id' => $partner->id,
                'applicant_name' => 'Duplicate HP',
                'ic_no' => '810101010003',
                'hp_no' => '60130000002',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasFormErrors(['hp_no']);

        Livewire::test(CreateBusinessOwner::class)
            ->fillForm([
                'partner_id' => $otherPartner->id,
                'applicant_name' => 'Allowed Other Partner',
                'ic_no' => '810101010002',
                'hp_no' => '60130000002',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_partner_can_set_business_owner_password_at_creation_no_activation_email(): void
    {
        Notification::fake();

        [$partnerUser, $partner] = $this->partnerUser('P000601', 'Password Partner');
        $this->actingAs($partnerUser);

        Livewire::test(CreateBusinessOwner::class)
            ->fillForm([
                'applicant_name' => 'BO With Password',
                'email' => 'bo-pass@pios.test',
                'hp_no' => '60130000601',
                'create_user' => true,
                'account_password' => 'secret-pass-123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->firstWhere('email', 'bo-pass@pios.test');

        $this->assertNotNull($user);
        // The partner-set password works; no activation email is dispatched.
        $this->assertTrue(Hash::check('secret-pass-123', $user->password));
        Notification::assertNothingSent();
    }

    public function test_business_owner_rejects_an_invalid_phone_number(): void
    {
        $admin = $this->adminUser();
        $partner = Partner::query()->create([
            'partner_no' => 'P000602',
            'applicant_name' => 'Phone Partner',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateBusinessOwner::class)
            ->fillForm([
                'partner_id' => $partner->id,
                'applicant_name' => 'Bad Phone BO',
                'hp_no' => '123',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasFormErrors(['hp_no']);
    }

    public function test_business_owner_admin_routes_are_registered(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('admin/business-owners', $routes);
        $this->assertContains('admin/business-owners/create', $routes);
        $this->assertContains('admin/business-owners/{record}/edit', $routes);
    }

    /**
     * @return array{0: User, 1: Partner}
     */
    private function partnerUser(string $partnerNo, string $name): array
    {
        $user = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::query()->create([
            'user_id' => $user->id,
            'partner_no' => $partnerNo,
            'applicant_name' => $name,
        ]);

        return [$user, $partner];
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
    }
}
