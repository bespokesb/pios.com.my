<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\RegistrationApprovalException;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Fee;
use App\Models\Partner;
use App\Models\User;
use App\Services\BusinessOwnerUpgradeService;
use App\Services\PartnerUpgradeService;
use App\Services\RegistrationApprover;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DualRoleUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    // ---- Panel access -------------------------------------------------------

    public function test_dual_role_user_can_access_both_partner_and_business_panels(): void
    {
        [$user] = $this->dualRole();

        $this->assertTrue($user->canAccessPanel(filament()->getPanel('partner')));
        $this->assertTrue($user->canAccessPanel(filament()->getPanel('business')));
        $this->assertFalse($user->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_single_role_users_keep_their_one_panel(): void
    {
        $mgr = Partner::factory()->create();

        $partnerUser = $mgr->user;
        $this->assertTrue($partnerUser->canAccessPanel(filament()->getPanel('partner')));
        $this->assertFalse($partnerUser->canAccessPanel(filament()->getPanel('business')));

        $bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);
        $boUser = $bo->user;
        $this->assertTrue($boUser->canAccessPanel(filament()->getPanel('business')));
        $this->assertFalse($boUser->canAccessPanel(filament()->getPanel('partner')));
    }

    // ---- Acting hat ---------------------------------------------------------

    public function test_acting_hat_follows_the_active_panel(): void
    {
        [$user] = $this->dualRole();

        Filament::setCurrentPanel('business');
        $this->assertTrue($user->actsAsBusinessOwner());
        $this->assertFalse($user->actsAsPartner());

        Filament::setCurrentPanel('partner');
        $this->assertTrue($user->actsAsPartner());
        $this->assertFalse($user->actsAsBusinessOwner());

        Filament::setCurrentPanel(null);
        // No panel: a dual-role user acts as neither single hat (union path handles it).
        $this->assertFalse($user->actsAsPartner());
        $this->assertFalse($user->actsAsBusinessOwner());
    }

    // ---- Customer visibility per panel (the core no-leak) -------------------

    public function test_customer_visibility_is_isolated_per_panel_for_dual_role_user(): void
    {
        [$user, $partner, $bo, $mgr] = $this->dualRole();

        $ownBoCustomer = Customer::factory()->forBusinessOwner($bo)->create();       // partner_id = mgr
        $partnerDirect = Customer::factory()->forPartner($partner)->create();        // partner_id = partner
        $downstreamBo = BusinessOwner::factory()->create(['partner_id' => $partner->id]);
        $downstreamCustomer = Customer::factory()->forBusinessOwner($downstreamBo)->create(); // partner_id = partner
        $foreign = Customer::factory()->forPartner(Partner::factory()->create())->create();

        // Business panel → only the user's own BO customers.
        Filament::setCurrentPanel('business');
        $ids = Customer::query()->visibleTo($user)->pluck('id');
        $this->assertEqualsCanonicalizing([$ownBoCustomer->id], $ids->all());

        // Partner panel → partner-scoped: partner-direct + downstream BO customers, NOT own-BO, NOT foreign.
        Filament::setCurrentPanel('partner');
        $ids = Customer::query()->visibleTo($user)->pluck('id');
        $this->assertEqualsCanonicalizing([$partnerDirect->id, $downstreamCustomer->id], $ids->all());

        // Console (no panel) → union of both own hats, still excluding foreign.
        Filament::setCurrentPanel(null);
        $ids = Customer::query()->visibleTo($user)->pluck('id');
        $this->assertEqualsCanonicalizing(
            [$ownBoCustomer->id, $partnerDirect->id, $downstreamCustomer->id],
            $ids->all(),
        );
    }

    public function test_single_role_customer_visibility_unchanged(): void
    {
        $mgr = Partner::factory()->create();
        $bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);
        $mine = Customer::factory()->forBusinessOwner($bo)->create();
        $foreign = Customer::factory()->forBusinessOwner(BusinessOwner::factory()->create())->create();

        // No panel set — single BO still resolves to their BO scope via the default hat arm.
        $ids = Customer::query()->visibleTo($bo->user)->pluck('id');
        $this->assertEqualsCanonicalizing([$mine->id], $ids->all());
    }

    // ---- BO -> Partner upgrade ---------------------------------------------

    public function test_managing_partner_initiates_bo_to_partner_upgrade_creating_pending_partner_on_same_user(): void
    {
        $mgr = Partner::factory()->create();
        $bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);

        $usersBefore = User::count();
        $this->actingAs($mgr->user);

        $partner = app(PartnerUpgradeService::class)->initiate($bo, $mgr->id);

        $this->assertSame($bo->user_id, $partner->user_id);          // reuses existing login
        $this->assertSame($mgr->id, $partner->parent_partner_id);
        $this->assertSame(RecordStatus::Pending, $partner->status);
        $this->assertSame($usersBefore, User::count());              // NO new user created
    }

    public function test_partner_cannot_initiate_upgrade_for_a_bo_they_do_not_manage(): void
    {
        $mgr = Partner::factory()->create();
        $other = Partner::factory()->create();
        $bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);

        $this->actingAs($other->user);

        $this->expectException(ValidationException::class);
        app(PartnerUpgradeService::class)->initiate($bo, $mgr->id);
    }

    public function test_upgrade_blocked_when_appointing_under_a_full_node(): void
    {
        $mgr = Partner::factory()->create();
        Partner::factory()->count(Partner::MAX_DIRECT_PARTNERS)->create([
            'parent_partner_id' => $mgr->id,
            'status' => RecordStatus::Active,
        ]);
        $bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);

        $this->actingAs($mgr->user);

        // Appointing the new partner under the (now full) managing partner is rejected.
        $this->expectException(RegistrationApprovalException::class);
        app(PartnerUpgradeService::class)->initiate($bo, $mgr->id);
    }

    public function test_cannot_initiate_upgrade_when_user_is_already_a_partner(): void
    {
        [$user, , $bo] = $this->dualRole();          // already holds both profiles
        $this->actingAs($bo->partner->user);          // managing partner acts

        $this->expectException(ValidationException::class);
        app(PartnerUpgradeService::class)->initiate($bo, $bo->partner->id);
    }

    public function test_hq_approval_activates_partner_on_same_user_enabling_dual_role(): void
    {
        $mgr = Partner::factory()->create();
        $bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);

        $this->actingAs($mgr->user);
        $partner = app(PartnerUpgradeService::class)->initiate($bo, $mgr->id);

        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $this->actingAs($admin);
        app(RegistrationApprover::class)->approvePartner($partner->fresh());

        $upgradedUser = $bo->user->fresh();
        $this->assertSame(RecordStatus::Active, $partner->fresh()->status);
        $this->assertSame(UserStatus::Active, $upgradedUser->status);
        $this->assertTrue($upgradedUser->canAccessPanel(filament()->getPanel('partner')));
        $this->assertTrue($upgradedUser->canAccessPanel(filament()->getPanel('business')));
    }

    // ---- Partner -> BO ------------------------------------------------------

    public function test_provisioning_gives_a_child_partner_a_bo_managed_by_recruiter_no_fee(): void
    {
        $recruiter = Partner::factory()->create();
        $child = Partner::factory()->create([
            'parent_partner_id' => $recruiter->id,
            'status' => RecordStatus::Active,
        ]);

        $this->actingAs($recruiter->user);
        $usersBefore = User::count();
        $feesBefore = Fee::count();

        $bo = app(BusinessOwnerUpgradeService::class)->provisionFor($child);

        $this->assertNotNull($bo);
        $this->assertSame($child->user_id, $bo->user_id);           // reuses existing login
        $this->assertSame($recruiter->id, $bo->partner_id);         // managed by the recruiter
        $this->assertSame(RecordStatus::Active, $bo->status);       // immediate
        $this->assertSame($usersBefore, User::count());             // NO new user
        $this->assertSame($feesBefore, Fee::count());               // NO setup fee
    }

    public function test_top_level_partner_provisioned_bo_falls_back_to_self_as_manager(): void
    {
        $partner = Partner::factory()->create(['parent_partner_id' => null]);

        $bo = app(BusinessOwnerUpgradeService::class)->provisionFor($partner);

        $this->assertSame($partner->id, $bo->partner_id);
    }

    public function test_provisioning_is_idempotent_when_user_already_a_bo(): void
    {
        [, $partner] = $this->dualRole();       // already a BO

        $this->assertNull(app(BusinessOwnerUpgradeService::class)->provisionFor($partner));
        $this->assertSame(1, BusinessOwner::query()->where('user_id', $partner->user_id)->count());
    }

    public function test_provisioning_skips_a_partner_with_no_login_or_not_active(): void
    {
        // No user account → nothing to provision.
        $noUser = Partner::factory()->create(['user_id' => null]);
        $this->assertNull(app(BusinessOwnerUpgradeService::class)->provisionFor($noUser));

        // Pending partner → provisioned only on activation.
        $pending = Partner::factory()->create(['status' => RecordStatus::Pending]);
        $this->assertNull(app(BusinessOwnerUpgradeService::class)->provisionFor($pending));
    }

    public function test_hq_approval_of_a_recruit_auto_provisions_a_business_owner(): void
    {
        $mgr = Partner::factory()->create();
        $recruit = Partner::factory()->create([
            'parent_partner_id' => $mgr->id,
            'status' => RecordStatus::Pending,
        ]);

        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $this->actingAs($admin);
        app(RegistrationApprover::class)->approvePartner($recruit);

        $user = $recruit->user->fresh();
        $this->assertTrue($user->hasPartnerProfile());
        $this->assertTrue($user->hasBusinessOwnerProfile());        // Partner is a BO by default
        $this->assertTrue($user->canAccessPanel(filament()->getPanel('partner')));
        $this->assertTrue($user->canAccessPanel(filament()->getPanel('business')));
    }

    // ---- Policy gating ------------------------------------------------------

    public function test_upgrade_action_policy_gates_correctly(): void
    {
        Filament::setCurrentPanel('partner');

        $mgr = Partner::factory()->create();
        $bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);

        // Managing partner may initiate a BO→Partner upgrade; a stranger may not.
        $this->assertTrue(Gate::forUser($mgr->user)->allows('initiatePartnerUpgrade', $bo));
        $stranger = Partner::factory()->create();
        $this->assertFalse(Gate::forUser($stranger->user)->allows('initiatePartnerUpgrade', $bo));
    }

    /**
     * A dual-role user: one User with an active Partner (parent = a managing
     * partner) AND an active BusinessOwner (managed by that same partner).
     *
     * @return array{0: User, 1: Partner, 2: BusinessOwner, 3: Partner}
     */
    private function dualRole(): array
    {
        $mgr = Partner::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);

        $partner = Partner::factory()->create([
            'user_id' => $user->id,
            'parent_partner_id' => $mgr->id,
            'status' => RecordStatus::Active,
        ]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $user->id,
            'partner_id' => $mgr->id,
            'status' => RecordStatus::Active,
        ]);

        return [$user->fresh(), $partner, $bo, $mgr];
    }
}
