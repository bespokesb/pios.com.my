<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_foundation_tables_and_public_identifiers_exist(): void
    {
        foreach ([
            'identifier_sequences',
            'partners',
            'business_owners',
            'customers',
            'products',
            'business_owner_product_selections',
            'product_proposals',
            'fees',
            'payments',
            'orders',
            'order_items',
            'territories',
            'partner_tiers',
            'product_types',
            'fee_settings',
            'audit_logs',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} table is missing.");
        }

        $this->assertTrue(Schema::hasColumn('partners', 'partner_no'));
        $this->assertTrue(Schema::hasColumn('business_owners', 'business_owner_no'));
        $this->assertTrue(Schema::hasColumn('customers', 'customer_no'));
        $this->assertTrue(Schema::hasColumn('customers', 'is_vip'));
        $this->assertFalse(Schema::hasColumn('customers', 'vip_customer_no'));
        $this->assertTrue(Schema::hasColumn('products', 'product_no'));
        $this->assertTrue(Schema::hasColumn('orders', 'order_no'));
    }

    public function test_user_role_and_status_are_enum_casts(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);

        $this->assertTrue($user->fresh()->role === UserRole::SysAdmin);
        $this->assertTrue($user->fresh()->status === UserStatus::Active);
        $this->assertTrue($user->fresh()->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_business_owner_customer_scope_is_limited_to_owner_hierarchy(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner]);
        $partner = Partner::query()->create([
            'user_id' => $partnerUser->id,
            'partner_no' => 'P000010',
            'applicant_name' => 'Partner One',
        ]);

        $businessOwnerUser = User::factory()->create(['role' => UserRole::BusinessOwner]);
        $businessOwner = BusinessOwner::query()->create([
            'user_id' => $businessOwnerUser->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000010',
            'applicant_name' => 'Business Owner One',
        ]);

        $otherPartner = Partner::query()->create([
            'partner_no' => 'P000011',
            'applicant_name' => 'Partner Two',
        ]);

        $otherBusinessOwner = BusinessOwner::query()->create([
            'partner_id' => $otherPartner->id,
            'business_owner_no' => 'BO000011',
            'applicant_name' => 'Business Owner Two',
        ]);

        Customer::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'customer_no' => 'C000010',
            'name' => 'Owned Customer',
        ]);

        Customer::query()->create([
            'business_owner_id' => $otherBusinessOwner->id,
            'partner_id' => $otherPartner->id,
            'customer_no' => 'C000011',
            'name' => 'Other Customer',
        ]);

        $this->assertSame(1, Customer::query()->visibleTo($partnerUser->fresh())->count());
        $this->assertSame(1, Customer::query()->visibleTo($businessOwnerUser->fresh())->count());
    }
}
