<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\RegistrationApprovalException;
use App\Filament\Resources\BusinessOwners\Pages\CreateBusinessOwner;
use App\Filament\Resources\Partners\Pages\CreatePartner;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use App\Services\RegistrationApprover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerRecruitmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_recruited_sub_partner_is_pending_and_parented_to_recruiter(): void
    {
        [$recruiterUser, $recruiter] = $this->partner('P000010');
        $this->actingAs($recruiterUser);

        Livewire::test(CreatePartner::class)
            ->fillForm([
                'applicant_name' => 'Recruited Partner',
                'ic_no' => '900101010001',
                'hp_no' => '60121110001',
                'email' => 'recruit@pios.test',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $sub = Partner::query()->firstWhere('email', 'recruit@pios.test');

        $this->assertNotNull($sub);
        $this->assertSame($recruiter->id, $sub->parent_partner_id);
        $this->assertSame(RecordStatus::Pending, $sub->status);
    }

    public function test_partner_recruited_sub_partner_login_is_inactive_until_approved(): void
    {
        [$recruiterUser] = $this->partner('P000020');
        $this->actingAs($recruiterUser);

        Livewire::test(CreatePartner::class)
            ->fillForm([
                'applicant_name' => 'Recruit With Login',
                'email' => 'recruit-login@pios.test',
                'create_user' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->firstWhere('email', 'recruit-login@pios.test');

        $this->assertNotNull($user);
        $this->assertSame(UserStatus::Inactive, $user->status);
    }

    public function test_hq_approval_activates_partner_and_login(): void
    {
        [, $recruiter] = $this->partner('P000030');
        $this->actingAs($this->adminUser());

        $login = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Inactive,
        ]);
        $sub = Partner::query()->create([
            'partner_no' => 'P000031',
            'parent_partner_id' => $recruiter->id,
            'applicant_name' => 'Pending Sub',
            'status' => RecordStatus::Pending,
            'user_id' => $login->id,
        ]);

        app(RegistrationApprover::class)->approvePartner($sub);

        $this->assertSame(RecordStatus::Active, $sub->fresh()->status);
        $this->assertSame(UserStatus::Active, $login->fresh()->status);
    }

    public function test_cap_blocks_approving_a_fifth_active_sub_partner(): void
    {
        [, $recruiter] = $this->partner('P000040');
        $this->actingAs($this->adminUser());

        foreach (range(1, Partner::MAX_DIRECT_PARTNERS) as $i) {
            Partner::query()->create([
                'partner_no' => 'P00005'.$i,
                'parent_partner_id' => $recruiter->id,
                'applicant_name' => 'Active Sub '.$i,
                'status' => RecordStatus::Active,
            ]);
        }

        $fifth = Partner::query()->create([
            'partner_no' => 'P000060',
            'parent_partner_id' => $recruiter->id,
            'applicant_name' => 'Fifth Sub',
            'status' => RecordStatus::Pending,
        ]);

        $this->expectException(RegistrationApprovalException::class);

        try {
            app(RegistrationApprover::class)->approvePartner($fifth);
        } finally {
            $this->assertSame(RecordStatus::Pending, $fifth->fresh()->status);
        }
    }

    public function test_partner_registered_business_owner_is_pending(): void
    {
        [$recruiterUser, $recruiter] = $this->partner('P000070');
        $this->actingAs($recruiterUser);

        Livewire::test(CreateBusinessOwner::class)
            ->fillForm([
                'applicant_name' => 'Pending BO',
                'ic_no' => '900202020002',
                'hp_no' => '60121110002',
                'email' => 'pending-bo@pios.test',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $businessOwner = BusinessOwner::query()->firstWhere('email', 'pending-bo@pios.test');

        $this->assertNotNull($businessOwner);
        $this->assertSame($recruiter->id, $businessOwner->partner_id);
        $this->assertSame(RecordStatus::Pending, $businessOwner->status);
    }

    /**
     * @return array{0: User, 1: Partner}
     */
    private function partner(string $partnerNo): array
    {
        $user = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::query()->create([
            'partner_no' => $partnerNo,
            'applicant_name' => 'Recruiter '.$partnerNo,
            'user_id' => $user->id,
            'status' => RecordStatus::Active,
        ]);

        return [$user->fresh(), $partner];
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
    }
}
