<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class HierarchyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_cannot_view_unrelated_business_owner_record(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner]);
        $partner = Partner::query()->create([
            'user_id' => $partnerUser->id,
            'partner_no' => 'P000100',
            'applicant_name' => 'Partner One',
        ]);

        $otherPartner = Partner::query()->create([
            'partner_no' => 'P000200',
            'applicant_name' => 'Partner Two',
        ]);

        $ownedBusinessOwner = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000100',
            'applicant_name' => 'Owned Business Owner',
        ]);

        $unrelatedBusinessOwner = BusinessOwner::query()->create([
            'partner_id' => $otherPartner->id,
            'business_owner_no' => 'BO000200',
            'applicant_name' => 'Unrelated Business Owner',
        ]);

        $this->assertTrue(Gate::forUser($partnerUser)->allows('view', $ownedBusinessOwner));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('view', $unrelatedBusinessOwner));
    }

    public function test_business_owner_can_only_view_own_business_owner_record(): void
    {
        $partner = Partner::query()->create([
            'partner_no' => 'P000300',
            'applicant_name' => 'Partner',
        ]);

        $businessOwnerUser = User::factory()->create(['role' => UserRole::BusinessOwner]);
        $businessOwner = BusinessOwner::query()->create([
            'user_id' => $businessOwnerUser->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000300',
            'applicant_name' => 'Owned Business Owner',
        ]);

        $otherBusinessOwner = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000301',
            'applicant_name' => 'Other Business Owner',
        ]);

        $this->assertTrue(Gate::forUser($businessOwnerUser)->allows('view', $businessOwner));
        $this->assertFalse(Gate::forUser($businessOwnerUser)->allows('view', $otherBusinessOwner));
    }

    public function test_audit_logger_persists_actor_event_and_model_values(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SysAdmin]);
        $partner = Partner::query()->create([
            'partner_no' => 'P000400',
            'applicant_name' => 'Audited Partner',
        ]);

        $auditLog = app(AuditLogger::class)->log(
            actor: $admin,
            event: 'created',
            auditable: $partner,
            oldValues: [],
            newValues: ['partner_no' => 'P000400'],
        );

        $this->assertSame($admin->id, $auditLog->actor_id);
        $this->assertSame('created', $auditLog->event);
        $this->assertSame(Partner::class, $auditLog->auditable_type);
        $this->assertSame(['partner_no' => 'P000400'], $auditLog->new_values);
    }
}
