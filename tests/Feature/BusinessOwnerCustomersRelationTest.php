<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\BusinessOwners\BusinessOwnerResource;
use App\Filament\Resources\BusinessOwners\Pages\EditBusinessOwner;
use App\Filament\Resources\BusinessOwners\RelationManagers\CustomersRelationManager;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessOwnerCustomersRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_relation_manager_is_registered_on_the_business_owner_resource(): void
    {
        $this->assertContains(
            CustomersRelationManager::class,
            BusinessOwnerResource::getRelations(),
        );
    }

    public function test_relation_manager_lists_only_that_business_owners_customers(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::query()->create([
            'user_id' => $partnerUser->id,
            'partner_no' => 'P000850',
            'applicant_name' => 'Rel Partner',
        ]);

        $bo = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000850',
            'applicant_name' => 'Rel BO',
        ]);
        $otherBo = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000851',
            'applicant_name' => 'Other BO',
        ]);

        $mine = $this->customer($bo, $partner, 'C000850');
        $theirs = $this->customer($otherBo, $partner, 'C000851');

        $this->actingAs($partnerUser);

        Livewire::test(CustomersRelationManager::class, [
            'ownerRecord' => $bo,
            'pageClass' => EditBusinessOwner::class,
        ])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    private function customer(BusinessOwner $bo, Partner $partner, string $no): Customer
    {
        return Customer::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $partner->id,
            'customer_no' => $no,
            'name' => 'Cust '.$no,
            'category' => CustomerCategory::Customer,
        ]);
    }
}
