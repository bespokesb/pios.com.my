<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\PartnerNetworkTree;
use App\Filament\Resources\Partners\Pages\EditPartner;
use App\Filament\Resources\Partners\PartnerResource;
use App\Filament\Resources\Partners\RelationManagers\DownlineRelationManager;
use App\Filament\Widgets\PartnerDownlineTree;
use App\Models\Partner;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerDownlineTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_descendant_ids_walk_the_full_tree_to_the_bottom(): void
    {
        $root = $this->partner('P000901');
        $childA = $this->partner('P000902', $root->id);
        $childB = $this->partner('P000903', $root->id);
        $grandchild = $this->partner('P000904', $childA->id);
        $greatGrandchild = $this->partner('P000905', $grandchild->id);
        $unrelated = $this->partner('P000906');

        $ids = $root->descendantIds();

        sort($ids);
        $expected = [$childA->id, $childB->id, $grandchild->id, $greatGrandchild->id];
        sort($expected);

        $this->assertSame($expected, $ids);
        $this->assertSame(4, $root->downlineCount());
        $this->assertNotContains($unrelated->id, $ids);
        $this->assertNotContains($root->id, $ids);
    }

    public function test_downline_relation_manager_is_registered(): void
    {
        $this->assertContains(DownlineRelationManager::class, PartnerResource::getRelations());
    }

    public function test_super_admin_sees_a_partners_direct_sub_partners(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $root = $this->partner('P000910');
        $child = $this->partner('P000911', $root->id);
        $grandchild = $this->partner('P000912', $child->id);
        $unrelated = $this->partner('P000913');

        $this->actingAs($admin);

        Livewire::test(DownlineRelationManager::class, [
            'ownerRecord' => $root,
            'pageClass' => EditPartner::class,
        ])
            ->assertCanSeeTableRecords([$child])            // direct recruit
            ->assertCanNotSeeTableRecords([$grandchild])    // reached by drilling into $child
            ->assertCanNotSeeTableRecords([$unrelated]);
    }

    public function test_flat_downline_walks_the_whole_tree_in_depth_order(): void
    {
        $root = $this->partner('P000920');
        $child = $this->partner('P000921', $root->id);
        $grandchild = $this->partner('P000922', $child->id);
        $greatGrandchild = $this->partner('P000923', $grandchild->id);

        $rows = $root->flatDownline();

        $this->assertCount(3, $rows);
        $this->assertSame($child->id, $rows[0]['partner']->id);
        $this->assertSame(1, $rows[0]['depth']);
        $this->assertSame($grandchild->id, $rows[1]['partner']->id);
        $this->assertSame(2, $rows[1]['depth']);
        $this->assertSame($greatGrandchild->id, $rows[2]['partner']->id);
        $this->assertSame(3, $rows[2]['depth']);
    }

    public function test_network_tree_page_renders_the_entire_forest_for_super_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $root = $this->partner('P000930');
        $child = $this->partner('P000931', $root->id);
        $grandchild = $this->partner('P000932', $child->id);
        $greatGrandchild = $this->partner('P000933', $grandchild->id);
        $unrelated = $this->partner('P000934');

        $this->actingAs($admin);
        $this->withoutVite();
        Filament::setCurrentPanel('admin');

        $this->assertTrue(PartnerNetworkTree::canAccess());

        // The org chart's embedded JSON payload carries the WHOLE network —
        // every line to the bottom, including partners outside any one root's
        // subtree (client-side search/expand operates on this full dataset).
        Livewire::test(PartnerNetworkTree::class)
            ->assertSee($root->partner_no)
            ->assertSee($child->partner_no)
            ->assertSee($grandchild->partner_no)
            ->assertSee($greatGrandchild->partner_no)
            ->assertSee($unrelated->partner_no)
            ->assertSee('partner-tree-data', escape: false); // JSON payload for d3-org-chart
    }

    public function test_network_tree_nodes_carry_hierarchy_and_stats(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $root = $this->partner('P000940');
        $child = $this->partner('P000941', $root->id);

        $this->actingAs($admin);
        $this->withoutVite();
        Filament::setCurrentPanel('admin');

        $component = Livewire::test(PartnerNetworkTree::class);
        $nodes = collect($component->viewData('nodes'));

        // Virtual HQ root anchors the forest; top-level partners hang off it.
        $this->assertSame('hq', $nodes->firstWhere('id', 'hq')['id']);
        $this->assertSame('hq', $nodes->firstWhere('id', $root->id)['parentId']);
        $this->assertSame($root->id, $nodes->firstWhere('id', $child->id)['parentId']);

        // Rich card stats: slot usage + deep downline count.
        $rootNode = $nodes->firstWhere('id', $root->id);
        $this->assertSame(1, $rootNode['used']);
        $this->assertSame(1, $rootNode['downline']);
        $this->assertTrue($rootNode['hasOpenSlot']);
    }

    public function test_network_tree_access_is_panel_aware(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        Partner::query()->create([
            'user_id' => $partnerUser->id,
            'partner_no' => 'P000944',
            'applicant_name' => 'Widget Partner',
        ]);
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);

        // Admin panel: owners only.
        Filament::setCurrentPanel('admin');
        $this->actingAs($admin);
        $this->assertTrue(PartnerNetworkTree::canAccess());
        $this->assertFalse(PartnerDownlineTree::canView()); // owner uses the page instead
        $this->actingAs($partnerUser->fresh());
        $this->assertFalse(PartnerNetworkTree::canAccess());

        // Partner panel: partners get their own "My Network" tree.
        Filament::setCurrentPanel('partner');
        $this->assertTrue(PartnerNetworkTree::canAccess());
        $this->assertTrue(PartnerDownlineTree::canView());
    }

    public function test_partner_panel_tree_is_rooted_at_the_viewer_and_scoped_to_their_line(): void
    {
        $upline = $this->partner('P000950');
        $selfUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $self = Partner::query()->create([
            'user_id' => $selfUser->id,
            'partner_no' => 'P000951',
            'applicant_name' => 'Self Partner',
            'parent_partner_id' => $upline->id,
        ]);
        $child = $this->partner('P000952', $self->id);
        $grandchild = $this->partner('P000953', $child->id);
        $sibling = $this->partner('P000954', $upline->id);   // upline's other leg
        $foreign = $this->partner('P000955');                // different line entirely

        $this->actingAs($selfUser->fresh());
        $this->withoutVite();
        Filament::setCurrentPanel('partner');

        $component = Livewire::test(PartnerNetworkTree::class);
        $nodes = collect($component->viewData('nodes'));

        // Rooted at SELF (no HQ node, no upline), whole own line to the bottom.
        $this->assertNull($nodes->firstWhere('id', 'hq'));
        $this->assertSame('', $nodes->firstWhere('id', $self->id)['parentId']);
        $this->assertNotNull($nodes->firstWhere('id', $child->id));
        $this->assertNotNull($nodes->firstWhere('id', $grandchild->id));

        // No upline, sibling legs, or foreign lines — and no edit links.
        $this->assertNull($nodes->firstWhere('id', $upline->id));
        $this->assertNull($nodes->firstWhere('id', $sibling->id));
        $this->assertNull($nodes->firstWhere('id', $foreign->id));
        $this->assertNull($nodes->firstWhere('id', $self->id)['url']);
    }

    private function partner(string $partnerNo, ?int $parentId = null): Partner
    {
        return Partner::query()->create([
            'partner_no' => $partnerNo,
            'applicant_name' => 'Partner '.$partnerNo,
            'parent_partner_id' => $parentId,
        ]);
    }
}
