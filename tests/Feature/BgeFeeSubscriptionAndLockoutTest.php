<?php

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Fee;
use App\Models\Partner;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BgeFeeSubscriptionAndLockoutTest extends TestCase
{
    use RefreshDatabase;

    private function createBgeProduct(): SubscriptionProduct
    {
        return SubscriptionProduct::firstOrCreate(
            ['code' => 'bge'],
            [
                'name' => 'BizGrowth Engine (BGE)',
                'description' => 'Enjin pengurusan utama perniagaan PIOS.',
                'price' => 30.00,
                'period_days' => 30,
                'target_role' => 'all',
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }

    private function createBoWithPartner(
        string $email = 'bo_test@pios.com.my',
        string $boNo = 'BO000777'
    ): array {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
            'name' => 'Partner Siti',
        ]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Partner Siti',
            'hp_no' => '0123456789',
        ]);

        $boUser = User::factory()->create([
            'email' => $email,
            'password' => bcrypt('secret123'),
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
            'name' => 'BO Ali',
        ]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'business_owner_no' => $boNo,
            'applicant_name' => 'BO Ali',
            'hp_no' => '0198765432',
        ]);

        return [$boUser, $bo, $partner];
    }

    public function test_business_fees_page_counts_bge_trial_correctly(): void
    {
        $this->createBgeProduct();
        [$boUser, $bo] = $this->createBoWithPartner();

        $subService = app(SubscriptionService::class);
        $sub = $subService->ensureInitialBgeSubscription($bo);

        $response = $this->actingAs($boUser)->get(route('business.fees.index'));
        $response->assertOk();
        $response->assertViewHas('totalPaid', 0.0);
        $response->assertViewHas('totalOutstanding', 0.0);
        $response->assertViewHas('overdueCount', 0);
        $response->assertSee('Trial Aktif');
    }

    public function test_business_fees_counts_bge_expired_under_90_days_as_outstanding(): void
    {
        $this->createBgeProduct();
        [$boUser, $bo] = $this->createBoWithPartner();

        $sub = app(SubscriptionService::class)->ensureInitialBgeSubscription($bo);
        $sub->update([
            'status' => 'expired',
            'expires_at' => now()->subDays(30),
        ]);

        $this->assertFalse($bo->isOverdueLocked());

        $response = $this->actingAs($boUser)->get(route('business.fees.index'));
        $response->assertOk();
        $response->assertViewHas('totalPaid', 0.0);
        $response->assertViewHas('totalOutstanding', 30.0);
        $response->assertViewHas('overdueCount', 0);
        $response->assertViewHas('pendingCount', 1);
        $response->assertSee('Outstanding');
    }

    public function test_business_fees_counts_bge_expired_over_91_days_as_overdue_and_locks_account(): void
    {
        $this->createBgeProduct();
        [$boUser, $bo] = $this->createBoWithPartner();

        $sub = app(SubscriptionService::class)->ensureInitialBgeSubscription($bo);
        $sub->update([
            'status' => 'expired',
            'expires_at' => now()->subDays(92),
        ]);

        $this->assertTrue($bo->isOverdueLocked());
        $this->assertTrue($boUser->isOverdueLocked());
    }

    public function test_business_fees_counts_fee_over_91_days_as_overdue(): void
    {
        [$boUser, $bo, $partner] = $this->createBoWithPartner();

        Fee::create([
            'partner_id' => $partner->id,
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'billing_period' => 'January 2026',
            'amount' => 50.00,
            'due_date' => now()->subDays(95),
            'status' => FeeStatus::Unpaid,
        ]);

        $this->assertTrue($bo->isOverdueLocked());
    }

    public function test_user_cannot_login_when_overdue_over_91_days_and_receives_contact_partner_message(): void
    {
        $this->createBgeProduct();
        [$boUser, $bo, $partner] = $this->createBoWithPartner('locked_bo@pios.com.my', 'BO000888');

        $sub = app(SubscriptionService::class)->ensureInitialBgeSubscription($bo);
        $sub->update([
            'status' => 'expired',
            'expires_at' => now()->subDays(95),
        ]);

        $this->assertTrue($boUser->isOverdueLocked());

        // Attempt login via email
        $response = $this->post(route('login.submit'), [
            'piosId' => 'locked_bo@pios.com.my',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('piosId');
        $errorMessage = session('errors')->first('piosId');

        $this->assertStringContainsString('Partner anda', $errorMessage);
        $this->assertStringContainsString('melebihi 90 hari', $errorMessage);
        $this->assertStringNotContainsString('tidak sah', $errorMessage);

        // Attempt login via BO Number
        $responseBoNo = $this->post(route('login.submit'), [
            'piosId' => 'BO000888',
            'password' => 'secret123',
        ]);

        $responseBoNo->assertSessionHasErrors('piosId');
        $boErrorMessage = session('errors')->first('piosId');
        $this->assertStringContainsString('Partner anda', $boErrorMessage);
        $this->assertStringNotContainsString('tidak sah', $boErrorMessage);
    }

    public function test_user_can_login_when_overdue_under_90_days(): void
    {
        $this->createBgeProduct();
        [$boUser, $bo] = $this->createBoWithPartner('ok_bo@pios.com.my', 'BO000999');

        $sub = app(SubscriptionService::class)->ensureInitialBgeSubscription($bo);
        $sub->update([
            'status' => 'expired',
            'expires_at' => now()->subDays(45),
        ]);

        $this->assertFalse($boUser->isOverdueLocked());

        $response = $this->post(route('login.submit'), [
            'piosId' => 'ok_bo@pios.com.my',
            'password' => 'secret123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($boUser);
    }

    public function test_active_session_is_logged_out_by_middleware_if_overdue_over_91_days(): void
    {
        $this->createBgeProduct();
        [$boUser, $bo] = $this->createBoWithPartner();

        $sub = app(SubscriptionService::class)->ensureInitialBgeSubscription($bo);
        $sub->update([
            'status' => 'expired',
            'expires_at' => now()->subDays(100),
        ]);

        $response = $this->actingAs($boUser)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('piosId');
        $this->assertGuest();
    }

    public function test_business_fees_counts_approved_bge_payment_as_paid(): void
    {
        $product = $this->createBgeProduct();
        [$boUser, $bo, $partner] = $this->createBoWithPartner();

        $sub = app(SubscriptionService::class)->ensureInitialBgeSubscription($bo);

        SubscriptionPayment::create([
            'payment_no' => 'SPAY00001',
            'subscription_id' => $sub->id,
            'subscription_product_id' => $product->id,
            'user_id' => $boUser->id,
            'payer_role' => 'business_owner',
            'partner_id' => $partner->id,
            'business_owner_id' => $bo->id,
            'amount' => 30.00,
            'period_days' => 30,
            'payment_method' => 'cash',
            'status' => 'approved',
            'paid_at' => now(),
            'approved_at' => now(),
        ]);

        $sub->update([
            'status' => 'active',
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($boUser)->get(route('business.fees.index'));
        $response->assertOk();
        $response->assertViewHas('totalPaid', 30.0);
        $response->assertViewHas('totalOutstanding', 0.0);
        $response->assertViewHas('overdueCount', 0);
        $response->assertSee('SPAY00001');
        $response->assertSee('Settled');
    }
}
