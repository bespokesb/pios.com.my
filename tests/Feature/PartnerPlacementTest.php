<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\RegistrationApprovalException;
use App\Filament\Pages\PartnerNetworkTree;
use App\Filament\Resources\Partners\Pages\CreatePartner;
use App\Filament\Resources\Partners\Pages\EditPartner;
use App\Filament\Widgets\PartnerDownlineTree;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use App\Services\PartnerUpgradeService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerPlacementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    // ---- Model slot accounting ---------------------------------------------

    public function test_direct_slot_count_includes_active_pending_suspended_not_inactive(): void
    {
        $parent = Partner::factory()->create();
        foreach ([RecordStatus::Active, RecordStatus::Pending, RecordStatus::Suspended, RecordStatus::Inactive] as $status) {
            Partner::factory()->create(['parent_partner_id' => $parent->id, 'status' => $status]);
        }

        $this->assertSame(3, $parent->directChildPartnersCount());   // Inactive excluded
        $this->assertSame(1, $parent->openDirectSlots());
        $this->assertTrue($parent->hasOpenDirectSlot());
    }

    public function test_no_open_slot_at_four_occupying(): void
    {
        $parent = Partner::factory()->create();
        Partner::factory()->count(3)->create(['parent_partner_id' => $parent->id, 'status' => RecordStatus::Active]);
        Partner::factory()->create(['parent_partner_id' => $parent->id, 'status' => RecordStatus::Pending]);

        $this->assertSame(0, $parent->openDirectSlots());
        $this->assertFalse($parent->hasOpenDirectSlot());
    }

    public function test_eligible_placement_parents_are_line_nodes_with_an_open_slot(): void
    {
        $root = Partner::factory()->create();
        $child = Partner::factory()->create(['parent_partner_id' => $root->id, 'status' => RecordStatus::Active]);
        $grandchild = Partner::factory()->create(['parent_partner_id' => $child->id, 'status' => RecordStatus::Active]);
        // Fill the root's remaining 3 slots so root has no open slot.
        Partner::factory()->count(3)->create(['parent_partner_id' => $root->id, 'status' => RecordStatus::Active]);
        $unrelated = Partner::factory()->create();

        $eligible = Partner::eligiblePlacementParents($root)->pluck('id');

        $this->assertFalse($eligible->contains($root->id));          // root full (1 child + 3 = 4)
        $this->assertTrue($eligible->contains($child->id));          // has open slots
        $this->assertTrue($eligible->contains($grandchild->id));
        $this->assertFalse($eligible->contains($unrelated->id));     // outside the line
    }

    public function test_is_within_line_of(): void
    {
        $root = Partner::factory()->create();
        $child = Partner::factory()->create(['parent_partner_id' => $root->id]);
        $grandchild = Partner::factory()->create(['parent_partner_id' => $child->id]);
        $sibling = Partner::factory()->create();

        $this->assertTrue($root->isWithinLineOf($root));
        $this->assertTrue($grandchild->isWithinLineOf($root));
        $this->assertFalse($sibling->isWithinLineOf($root));
        $this->assertFalse($root->isWithinLineOf($child));           // ancestor not within descendant
    }

    // ---- Add-time recruit cap ----------------------------------------------

    public function test_partner_cannot_recruit_a_fifth_direct_partner(): void
    {
        $recruiterUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $recruiter = Partner::factory()->create(['user_id' => $recruiterUser->id, 'status' => RecordStatus::Active]);
        // 4 occupying direct children (3 active + 1 pending).
        Partner::factory()->count(3)->create(['parent_partner_id' => $recruiter->id, 'status' => RecordStatus::Active]);
        Partner::factory()->create(['parent_partner_id' => $recruiter->id, 'status' => RecordStatus::Pending]);

        $this->actingAs($recruiterUser->fresh());

        Livewire::test(CreatePartner::class)
            ->fillForm([
                'applicant_name' => 'Fifth Recruit',
                'email' => 'fifth@pios.test',
            ])
            ->call('create')
            ->assertHasFormErrors(['applicant_name']);

        $this->assertNull(Partner::query()->firstWhere('email', 'fifth@pios.test'));
    }

    // ---- BO -> Partner appoint ---------------------------------------------

    public function test_appoint_under_self_when_managing_partner_has_a_slot(): void
    {
        $mgr = Partner::factory()->create();
        $bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);

        $this->actingAs($mgr->user);
        $partner = app(PartnerUpgradeService::class)->initiate($bo, $mgr->id);

        $this->assertSame($mgr->id, $partner->parent_partner_id);
        $this->assertSame(RecordStatus::Pending, $partner->status);
    }

    public function test_appoint_under_a_descendant_when_managing_partner_is_full(): void
    {
        $mgr = Partner::factory()->create();
        $child = Partner::factory()->create(['parent_partner_id' => $mgr->id, 'status' => RecordStatus::Active]);
        // Fill the manager's other 3 slots.
        Partner::factory()->count(3)->create(['parent_partner_id' => $mgr->id, 'status' => RecordStatus::Active]);
        $bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);

        $this->actingAs($mgr->user);
        $partner = app(PartnerUpgradeService::class)->initiate($bo, $child->id);

        $this->assertSame($child->id, $partner->parent_partner_id);  // placed at the bottom of the line
    }

    public function test_cannot_appoint_outside_the_managing_partners_line(): void
    {
        $mgr = Partner::factory()->create();
        $bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);
        $foreign = Partner::factory()->create();                     // not in mgr's line

        $this->actingAs($mgr->user);

        try {
            app(PartnerUpgradeService::class)->initiate($bo, $foreign->id);
            $this->fail('Expected a ValidationException for out-of-line placement.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('placement_parent_id', $e->errors());
        }

        $this->assertNull(Partner::query()->where('user_id', $bo->user_id)->first());
    }

    public function test_cannot_appoint_under_a_full_node(): void
    {
        $mgr = Partner::factory()->create();
        Partner::factory()->count(Partner::MAX_DIRECT_PARTNERS)->create([
            'parent_partner_id' => $mgr->id,
            'status' => RecordStatus::Active,
        ]);
        $bo = BusinessOwner::factory()->create(['partner_id' => $mgr->id]);

        $this->actingAs($mgr->user);

        $this->expectException(RegistrationApprovalException::class);
        app(PartnerUpgradeService::class)->initiate($bo, $mgr->id);
    }

    // ---- Reject / withdraw frees a slot ------------------------------------

    public function test_rejecting_a_pending_recruit_frees_the_parents_slot(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $parent = Partner::factory()->create();
        Partner::factory()->count(3)->create(['parent_partner_id' => $parent->id, 'status' => RecordStatus::Active]);
        $pending = Partner::factory()->create(['parent_partner_id' => $parent->id, 'status' => RecordStatus::Pending]);

        $this->assertFalse($parent->fresh()->hasOpenDirectSlot());

        $this->actingAs($admin);
        Livewire::test(EditPartner::class, ['record' => $pending->id])
            ->callAction('rejectRecruit');

        $this->assertSame(RecordStatus::Inactive, $pending->fresh()->status);
        $this->assertTrue($parent->fresh()->hasOpenDirectSlot());    // slot freed
    }

    // ---- Tree view: viewer-aware + capacity --------------------------------

    public function test_partner_widget_shows_only_direct_children_admin_page_shows_full_depth(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $root = Partner::factory()->create(['user_id' => $partnerUser->id, 'partner_no' => 'P900001']);
        $child = Partner::factory()->create(['parent_partner_id' => $root->id, 'partner_no' => 'P900002', 'status' => RecordStatus::Active]);
        $grandchild = Partner::factory()->create(['parent_partner_id' => $child->id, 'partner_no' => 'P900003', 'status' => RecordStatus::Active]);

        Filament::setCurrentPanel('partner');

        // Partner viewer (widget) → only direct child, NOT the grandchild.
        $this->actingAs($partnerUser->fresh());
        Livewire::test(PartnerDownlineTree::class, ['record' => $root])
            ->assertSee('P900002')
            ->assertDontSee('P900003');

        // Admin viewer → the dedicated network page carries full depth.
        Filament::setCurrentPanel('admin');
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $this->actingAs($admin);
        $this->withoutVite();
        Livewire::test(PartnerNetworkTree::class)
            ->assertSee('P900002')
            ->assertSee('P900003');
    }
}
