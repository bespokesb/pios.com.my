<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\FollowUpsRelationManager;
use App\Filament\Resources\Customers\RelationManagers\NotesRelationManager;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\CustomerFollowUp;
use App\Models\CustomerNote;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerDetailViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_view_own_customer_detail(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P011001', 'BO011001', 'Detail BO');
        $cust = $this->customer($bo, $partner, 'C011001', CustomerCategory::Customer);

        $this->actingAs($boUser);

        Livewire::test(ViewCustomer::class, ['record' => $cust->getRouteKey()])
            ->assertOk()
            ->assertSee($cust->name)
            ->assertDontSee('C011001'); // Customer No shows only on the listing, not the detail page.
    }

    public function test_business_owner_detail_hides_sensitive_fields(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P011010', 'BO011010', 'Hidden BO');
        $cust = Customer::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $partner->id,
            'customer_no' => 'C011010',
            'is_vip' => true,
            'category' => CustomerCategory::Customer,
            'name' => 'Sensitive Customer',
            'hp_no' => '60130110010',
            'kp_no' => '900101019999',
            'abo_no' => 'ABO-SECRET-9999',
            'address' => '88 Hidden Lane, Kuala Lumpur',
            'dd_name' => 'Diamond Secret',
            'dd_hp_no' => '60199999999',
        ]);

        $this->actingAs($boUser);

        Livewire::test(ViewCustomer::class, ['record' => $cust->getRouteKey()])
            ->assertOk()
            ->assertSee('Sensitive Customer')
            ->assertDontSee('900101019999')   // kp_no
            ->assertDontSee('ABO-SECRET-9999') // abo_no
            ->assertDontSee('88 Hidden Lane, Kuala Lumpur') // address
            ->assertDontSee('60199999999')     // dd_hp_no (admin-only)
            ->assertDontSee('Diamond Secret'); // dd_name
    }

    public function test_business_owner_cannot_open_another_business_owners_customer(): void
    {
        [, $partner, $bo] = $this->businessOwnerUser('P011002', 'BO011002', 'Owner BO');
        $cust = $this->customer($bo, $partner, 'C011002', CustomerCategory::Customer);
        [$otherBoUser] = $this->businessOwnerUser('P011003', 'BO011003', 'Other BO');

        $this->actingAs($otherBoUser);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(ViewCustomer::class, ['record' => $cust->getRouteKey()]);
    }

    public function test_customer_policy_denies_cross_business_owner_view(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P011004', 'BO011004', 'Policy Owner BO');
        $cust = $this->customer($bo, $partner, 'C011004', CustomerCategory::Customer);
        [$otherBoUser] = $this->businessOwnerUser('P011005', 'BO011005', 'Policy Other BO');

        $this->assertTrue(Gate::forUser($boUser)->allows('view', $cust));
        $this->assertFalse(Gate::forUser($otherBoUser)->allows('view', $cust));
    }

    public function test_creating_note_via_relation_manager_stamps_owner_and_audits(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P011006', 'BO011006', 'Note BO');
        $cust = $this->customer($bo, $partner, 'C011006', CustomerCategory::Customer);

        $this->actingAs($boUser);

        Livewire::test(NotesRelationManager::class, [
            'ownerRecord' => $cust,
            'pageClass' => ViewCustomer::class,
        ])
            ->callTableAction('create', data: ['body' => 'Called the customer, keen on the organic range'])
            ->assertHasNoTableActionErrors();

        $note = CustomerNote::query()->firstWhere('body', 'Called the customer, keen on the organic range');

        $this->assertNotNull($note);
        $this->assertSame($bo->id, $note->business_owner_id);
        $this->assertSame($cust->id, $note->customer_id);
        $this->assertSame($boUser->id, $note->created_by);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => CustomerNote::class,
            'auditable_id' => $note->id,
            'event' => 'created',
        ]);
    }

    public function test_relation_manager_lists_only_parent_customers_children(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P011007', 'BO011007', 'List BO');
        $cust = $this->customer($bo, $partner, 'C011007', CustomerCategory::Customer);
        $ownNote = CustomerNote::query()->create([
            'customer_id' => $cust->id,
            'business_owner_id' => $bo->id,
            'body' => 'Own note',
            'created_by' => $boUser->id,
        ]);

        [$otherBoUser, $otherPartner, $otherBo] = $this->businessOwnerUser('P011008', 'BO011008', 'Other List BO');
        $otherCust = $this->customer($otherBo, $otherPartner, 'C011008', CustomerCategory::Customer);
        $otherNote = CustomerNote::query()->create([
            'customer_id' => $otherCust->id,
            'business_owner_id' => $otherBo->id,
            'body' => 'Foreign note',
            'created_by' => $otherBoUser->id,
        ]);

        $this->actingAs($boUser);

        Livewire::test(NotesRelationManager::class, [
            'ownerRecord' => $cust,
            'pageClass' => ViewCustomer::class,
        ])
            ->assertCanSeeTableRecords([$ownNote])
            ->assertCanNotSeeTableRecords([$otherNote]);
    }

    public function test_another_business_owner_cannot_edit_or_delete_foreign_child_records(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P011011', 'BO011011', 'Child Owner BO');
        $cust = $this->customer($bo, $partner, 'C011011', CustomerCategory::Customer);
        $note = CustomerNote::query()->create([
            'customer_id' => $cust->id,
            'business_owner_id' => $bo->id,
            'body' => 'Owner note for child auth',
            'created_by' => $boUser->id,
        ]);
        $followUp = CustomerFollowUp::query()->create([
            'customer_id' => $cust->id,
            'business_owner_id' => $bo->id,
            'note' => 'Owner follow-up for child auth',
            'due_on' => now()->addDay()->toDateString(),
            'created_by' => $boUser->id,
        ]);

        [$otherBoUser, $otherPartner, $otherBo] = $this->businessOwnerUser('P011012', 'BO011012', 'Foreign Child BO');

        // The owning BO can manage their own child records.
        $this->assertTrue(Gate::forUser($boUser)->allows('view', $note));
        $this->assertTrue(Gate::forUser($boUser)->allows('update', $note));
        $this->assertTrue(Gate::forUser($boUser)->allows('delete', $note));

        // A different BO is denied view/update/delete on the foreign note + follow-up.
        $this->assertFalse(Gate::forUser($otherBoUser)->allows('view', $note));
        $this->assertFalse(Gate::forUser($otherBoUser)->allows('update', $note));
        $this->assertFalse(Gate::forUser($otherBoUser)->allows('delete', $note));
        $this->assertFalse(Gate::forUser($otherBoUser)->allows('view', $followUp));
        $this->assertFalse(Gate::forUser($otherBoUser)->allows('update', $followUp));
        $this->assertFalse(Gate::forUser($otherBoUser)->allows('delete', $followUp));

        // And the foreign customer's relation-manager table never lists the owner's child record.
        $otherCust = $this->customer($otherBo, $otherPartner, 'C011013', CustomerCategory::Customer);

        $this->actingAs($otherBoUser);

        Livewire::test(NotesRelationManager::class, [
            'ownerRecord' => $otherCust,
            'pageClass' => ViewCustomer::class,
        ])
            ->assertCanNotSeeTableRecords([$note]);
    }

    public function test_creating_follow_up_via_relation_manager_stamps_owner(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P011009', 'BO011009', 'FollowUp BO');
        $cust = $this->customer($bo, $partner, 'C011009', CustomerCategory::Customer);

        $this->actingAs($boUser);

        Livewire::test(FollowUpsRelationManager::class, [
            'ownerRecord' => $cust,
            'pageClass' => ViewCustomer::class,
        ])
            ->callTableAction('create', data: [
                'due_on' => now()->addDays(2)->toDateString(),
                'note' => 'Call back about delivery',
            ])
            ->assertHasNoTableActionErrors();

        $fu = CustomerFollowUp::query()->firstWhere('note', 'Call back about delivery');

        $this->assertNotNull($fu);
        $this->assertSame($bo->id, $fu->business_owner_id);
        $this->assertSame($cust->id, $fu->customer_id);
        $this->assertSame($boUser->id, $fu->created_by);
    }

    /**
     * @return array{0: User, 1: Partner, 2: BusinessOwner}
     */
    private function businessOwnerUser(string $partnerNo, string $businessOwnerNo, string $name): array
    {
        $user = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::query()->create([
            'partner_no' => $partnerNo,
            'applicant_name' => "{$name} Partner",
        ]);
        $businessOwner = BusinessOwner::query()->create([
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'business_owner_no' => $businessOwnerNo,
            'applicant_name' => $name,
        ]);

        return [$user, $partner, $businessOwner];
    }

    private function customer(
        BusinessOwner $businessOwner,
        Partner $partner,
        string $customerNo,
        CustomerCategory $category,
        bool $isVip = false
    ): Customer {
        return Customer::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'customer_no' => $customerNo,
            'is_vip' => $isVip,
            'category' => $category,
            'name' => "{$category->label()} Customer",
        ]);
    }
}
