<?php

namespace Tests\Feature;

use App\Enums\ProductProposalStatus;
use App\Enums\ProductSelectionStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\BusinessOwnerProductSelections\Pages\CreateBusinessOwnerProductSelection;
use App\Filament\Resources\ProductProposals\Pages\CreateProductProposal;
use App\Filament\Resources\ProductProposals\Pages\EditProductProposal;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\BusinessOwnerProductSelection;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSelectionAndProposalTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_select_active_product_for_own_account(): void
    {
        [$businessOwnerUser, $businessOwner] = $this->businessOwnerUser('P000801', 'BO000801', 'Selection BO');
        [, $otherBusinessOwner] = $this->partnerAndBusinessOwner('P000802', 'BO000802', 'Other Selection BO');
        $product = $this->product('PRD000801', 'Selectable Product', ProductStatus::Active);

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreateBusinessOwnerProductSelection::class)
            ->fillForm([
                'business_owner_id' => $otherBusinessOwner->id,
                'product_id' => $product->id,
                'status' => ProductSelectionStatus::Selected->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $selection = BusinessOwnerProductSelection::query()->firstWhere('product_id', $product->id);

        $this->assertNotNull($selection);
        $this->assertSame($businessOwner->id, $selection->business_owner_id);
        $this->assertSame(ProductSelectionStatus::Selected, $selection->status);
        $this->assertNotNull($selection->selected_at);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => BusinessOwnerProductSelection::class,
            'auditable_id' => $selection->id,
            'event' => 'created',
        ]);
    }

    public function test_selection_blocks_duplicate_inactive_product_and_more_than_five_products(): void
    {
        $admin = $this->adminUser();
        [, $businessOwner] = $this->partnerAndBusinessOwner('P000803', 'BO000803', 'Selection Limit BO');
        $activeProduct = $this->product('PRD000802', 'Active Duplicate Product', ProductStatus::Active);
        $draftProduct = $this->product('PRD000803', 'Draft Product', ProductStatus::Draft);

        BusinessOwnerProductSelection::query()->create([
            'business_owner_id' => $businessOwner->id,
            'product_id' => $activeProduct->id,
            'status' => ProductSelectionStatus::Selected,
            'selected_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateBusinessOwnerProductSelection::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'product_id' => $activeProduct->id,
                'status' => ProductSelectionStatus::Selected->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['product_id']);

        Livewire::test(CreateBusinessOwnerProductSelection::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'product_id' => $draftProduct->id,
                'status' => ProductSelectionStatus::Selected->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['product_id']);

        foreach (range(1, 4) as $index) {
            BusinessOwnerProductSelection::query()->create([
                'business_owner_id' => $businessOwner->id,
                'product_id' => $this->product("PRD00081{$index}", "Selected Product {$index}", ProductStatus::Active)->id,
                'status' => ProductSelectionStatus::Selected,
                'selected_at' => now(),
            ]);
        }

        Livewire::test(CreateBusinessOwnerProductSelection::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'product_id' => $this->product('PRD000899', 'Sixth Product', ProductStatus::Active)->id,
                'status' => ProductSelectionStatus::Selected->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['product_id']);
    }

    public function test_partner_can_view_downstream_selections_but_not_modify_them(): void
    {
        [$partnerUser, $partner] = $this->partnerUser('P000804', 'Selection Partner');
        $businessOwner = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000804',
            'applicant_name' => 'Downstream BO',
        ]);
        $selection = BusinessOwnerProductSelection::query()->create([
            'business_owner_id' => $businessOwner->id,
            'product_id' => $this->product('PRD000804', 'Downstream Product', ProductStatus::Active)->id,
            'status' => ProductSelectionStatus::Selected,
        ]);

        $this->assertTrue(Gate::forUser($partnerUser)->allows('view', $selection));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('create', BusinessOwnerProductSelection::class));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('update', $selection));
    }

    public function test_business_owner_can_submit_pending_product_proposal_for_own_account(): void
    {
        [$businessOwnerUser, $businessOwner] = $this->businessOwnerUser('P000805', 'BO000805', 'Proposal BO');
        [, $otherBusinessOwner] = $this->partnerAndBusinessOwner('P000806', 'BO000806', 'Other Proposal BO');

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreateProductProposal::class)
            ->fillForm([
                'business_owner_id' => $otherBusinessOwner->id,
                'name' => 'Proposed Product One',
                'area' => 'Klang Valley',
                'partner_price' => 80,
                'customer_price' => 110,
                'description' => 'Proposal description',
                'approval_status' => ProductProposalStatus::Approved->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $proposal = ProductProposal::query()->firstWhere('name', 'Proposed Product One');

        $this->assertNotNull($proposal);
        $this->assertSame($businessOwner->id, $proposal->business_owner_id);
        $this->assertSame(ProductProposalStatus::Pending, $proposal->approval_status);
        $this->assertNull($proposal->reviewed_by);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => ProductProposal::class,
            'auditable_id' => $proposal->id,
            'event' => 'created',
        ]);
    }

    public function test_admin_can_approve_product_proposal_and_set_reviewer_fields(): void
    {
        $admin = $this->adminUser();
        [, $businessOwner] = $this->partnerAndBusinessOwner('P000807', 'BO000807', 'Approval BO');
        $proposal = ProductProposal::query()->create([
            'business_owner_id' => $businessOwner->id,
            'name' => 'Approval Proposal',
            'area' => 'Penang',
            'partner_price' => 70,
            'customer_price' => 100,
            'description' => 'Pending approval',
            'approval_status' => ProductProposalStatus::Pending,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditProductProposal::class, ['record' => $proposal->getRouteKey()])
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'name' => 'Approval Proposal',
                'area' => 'Penang',
                'partner_price' => 70,
                'customer_price' => 100,
                'description' => 'Pending approval',
                'approval_status' => ProductProposalStatus::Approved->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $proposal->refresh();

        $this->assertSame(ProductProposalStatus::Approved, $proposal->approval_status);
        $this->assertSame($admin->id, $proposal->reviewed_by);
        $this->assertNotNull($proposal->reviewed_at);
    }

    public function test_business_owner_can_edit_only_own_pending_proposals(): void
    {
        [$businessOwnerUser, $businessOwner] = $this->businessOwnerUser('P000808', 'BO000808', 'Pending Proposal BO');
        $pendingProposal = ProductProposal::query()->create([
            'business_owner_id' => $businessOwner->id,
            'name' => 'Pending Proposal',
            'approval_status' => ProductProposalStatus::Pending,
        ]);
        $approvedProposal = ProductProposal::query()->create([
            'business_owner_id' => $businessOwner->id,
            'name' => 'Approved Proposal',
            'approval_status' => ProductProposalStatus::Approved,
        ]);

        $this->assertTrue(Gate::forUser($businessOwnerUser)->allows('update', $pendingProposal));
        $this->assertFalse(Gate::forUser($businessOwnerUser)->allows('update', $approvedProposal));
    }

    public function test_product_selection_and_proposal_routes_are_registered(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('admin/business-owner-product-selections', $routes);
        $this->assertContains('admin/business-owner-product-selections/create', $routes);
        $this->assertContains('admin/product-proposals', $routes);
        $this->assertContains('admin/product-proposals/create', $routes);
    }

    private function product(string $productNo, string $name, ProductStatus $status): Product
    {
        return Product::query()->create([
            'product_no' => $productNo,
            'name' => $name,
            'status' => $status,
        ]);
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

    /**
     * @return array{0: User, 1: BusinessOwner}
     */
    private function businessOwnerUser(string $partnerNo, string $businessOwnerNo, string $name): array
    {
        $user = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        [, $businessOwner] = $this->partnerAndBusinessOwner($partnerNo, $businessOwnerNo, $name, $user);

        return [$user, $businessOwner];
    }

    /**
     * @return array{0: Partner, 1: BusinessOwner}
     */
    private function partnerAndBusinessOwner(string $partnerNo, string $businessOwnerNo, string $name, ?User $user = null): array
    {
        $partner = Partner::query()->create([
            'partner_no' => $partnerNo,
            'applicant_name' => "{$name} Partner",
        ]);
        $businessOwner = BusinessOwner::query()->create([
            'user_id' => $user?->id,
            'partner_id' => $partner->id,
            'business_owner_no' => $businessOwnerNo,
            'applicant_name' => $name,
        ]);

        return [$partner, $businessOwner];
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
    }
}
