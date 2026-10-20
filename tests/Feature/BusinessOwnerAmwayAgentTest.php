<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\BusinessOwners\Pages\ListBusinessOwners;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessOwnerAmwayAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_only_the_managing_partner_and_admin_may_verify_amway_agent(): void
    {
        [$partnerUser, $partner] = $this->partner('P300001');
        $bo = BusinessOwner::factory()->forPartner($partner)->create();

        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        [$strangerUser] = $this->partner('P300002');
        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo->forceFill(['user_id' => $boUser->id])->save();

        $this->assertTrue(Gate::forUser($partnerUser)->allows('verifyAmwayAgent', $bo));
        $this->assertTrue(Gate::forUser($admin)->allows('verifyAmwayAgent', $bo));
        // A stranger partner cannot; and the BO can NEVER self-certify.
        $this->assertTrue(Gate::forUser($strangerUser)->denies('verifyAmwayAgent', $bo));
        $this->assertTrue(Gate::forUser($boUser->fresh())->denies('verifyAmwayAgent', $bo));
    }

    public function test_partner_verifies_their_bo_as_an_amway_agent_via_the_action(): void
    {
        [$partnerUser, $partner] = $this->partner('P300003');
        $bo = BusinessOwner::factory()->forPartner($partner)->create(['is_amway_agent' => false]);

        $this->actingAs($partnerUser);
        Filament::setCurrentPanel('partner');

        Livewire::test(ListBusinessOwners::class)
            ->callTableAction('verifyAmwayAgent', $bo, data: ['is_amway_agent' => true])
            ->assertHasNoTableActionErrors();

        $this->assertTrue($bo->fresh()->is_amway_agent);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => BusinessOwner::class,
            'auditable_id' => $bo->id,
            'event' => 'amway_agent_verified',
        ]);
    }

    public function test_the_flag_defaults_to_false_and_casts_boolean(): void
    {
        $bo = BusinessOwner::factory()->create();

        $this->assertFalse($bo->fresh()->is_amway_agent);
    }

    /** @return array{0: User, 1: Partner} */
    private function partner(string $partnerNo): array
    {
        $user = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::query()->create([
            'user_id' => $user->id,
            'partner_no' => $partnerNo,
            'applicant_name' => 'P '.$partnerNo,
        ]);

        return [$user->fresh(), $partner];
    }
}
