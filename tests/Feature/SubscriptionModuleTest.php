<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Partner\Resources\Pages\ListPartnerBoPaymentApprovals;
use App\Filament\Resources\SubscriptionProducts\Pages\CreateSubscriptionProduct;
use App\Filament\Resources\SubscriptionProducts\Pages\EditSubscriptionProduct;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\SubscriptionProductSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(null);
        $this->seed(SubscriptionProductSeeder::class);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_default_products_are_seeded_with_bge_active_and_partner_fee_disabled(): void
    {
        $bge = SubscriptionProduct::where('code', 'bge')->first();
        $this->assertNotNull($bge);
        $this->assertSame('BizGrowth Engine (BGE)', $bge->name);
        $this->assertEquals(30.00, (float) $bge->price);
        $this->assertSame(30, $bge->period_days);
        $this->assertSame('all', $bge->target_role);
        $this->assertTrue($bge->is_mandatory);
        $this->assertTrue($bge->is_active);

        $partnerFee = SubscriptionProduct::where('code', 'partner_fee')->first();
        $this->assertNotNull($partnerFee);
        $this->assertSame('Partner Fee', $partnerFee->name);
        $this->assertSame('partner', $partnerFee->target_role);
        $this->assertFalse($partnerFee->is_active); // Disabled by default
    }

    public function test_owner_can_create_and_edit_subscription_product(): void
    {
        Filament::setCurrentPanel('admin');

        $admin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateSubscriptionProduct::class)
            ->fillForm([
                'name' => 'Sistem POS Pintar',
                'code' => 'pos_smart',
                'price' => 45.00,
                'period_days' => 30,
                'target_role' => 'business_owner',
                'is_mandatory' => false,
                'is_active' => true,
                'description' => 'Sistem juruwang mudah alih dan kawalan laci tunai.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = SubscriptionProduct::where('code', 'pos_smart')->first();
        $this->assertNotNull($product);
        $this->assertEquals(45.00, (float) $product->price);

        // Edit product
        Livewire::test(EditSubscriptionProduct::class, ['record' => $product->id])
            ->fillForm([
                'price' => 50.00,
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertEquals(50.00, (float) $product->price);
        $this->assertFalse($product->is_active);
    }

    public function test_business_owner_can_view_subscriptions_and_submit_bge_payment(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::factory()->create(['user_id' => $partnerUser->id]);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Kedai Kopi Abu',
        ]);

        $bge = SubscriptionProduct::where('code', 'bge')->first();

        // 1. Visit index page
        $response = $this->actingAs($boUser)->get(route('business.subscriptions.index'));
        $response->assertOk();
        $response->assertSee('BizGrowth Engine (BGE)');
        $response->assertSee('RM 30.00');

        // 2. Submit payment with receipt slip
        $slip = UploadedFile::fake()->image('resit_bayaran.jpg', 400, 400);

        $postResponse = $this->actingAs($boUser)->post(route('business.subscriptions.store'), [
            'subscription_product_id' => $bge->id,
            'payment_method' => 'qr_code',
            'payment_proof' => $slip,
            'reference_no' => 'MBB-QR-998877',
            'notes' => 'Telah scan DuitNow dan bayar RM30',
        ]);

        $postResponse->assertRedirect(route('business.subscriptions.index'));
        $postResponse->assertSessionHas('success');

        $payment = SubscriptionPayment::where('user_id', $boUser->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('pending_approval', $payment->status);
        $this->assertSame('business_owner', $payment->payer_role);
        $this->assertSame($partner->id, $payment->partner_id);
        $this->assertSame($bo->id, $payment->business_owner_id);
        $this->assertEquals(30.00, (float) $payment->amount);
        $this->assertNotNull($payment->payment_proof);
        Storage::disk('public')->assertExists($payment->payment_proof);

        $subscription = $payment->subscription;
        $this->assertNotNull($subscription);
        $this->assertSame('pending_approval', $subscription->status);
    }

    public function test_sponsoring_partner_can_approve_downstream_business_owner_payment(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::factory()->create(['user_id' => $partnerUser->id, 'partner_no' => 'P9901']);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Kedai Jahit Siti',
        ]);

        $bge = SubscriptionProduct::where('code', 'bge')->first();

        // BO submits payment
        $payment = app(SubscriptionService::class)->submitPayment($boUser, $bge, [
            'payment_method' => 'cash',
            'notes' => 'Bayar tunai kepada Partner',
        ]);

        $this->assertSame('pending_approval', $payment->status);

        // Partner sees payment and approves it
        Filament::setCurrentPanel('partner');
        $this->actingAs($partnerUser);

        Livewire::test(ListPartnerBoPaymentApprovals::class)
            ->callTableAction('approve', $payment)
            ->assertHasNoErrors();

        $payment->refresh();
        $this->assertSame('approved', $payment->status);
        $this->assertSame($partnerUser->id, $payment->approved_by);
        $this->assertNotNull($payment->receipt_no);
        $this->assertNotNull($payment->approved_at);

        // Verify Subscription is now active for 30 days
        $subscription = $payment->subscription->fresh();
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->isActive());
        $this->assertNotNull($subscription->expires_at);
        $this->assertGreaterThan(now()->addDays(28), $subscription->expires_at);
    }

    public function test_unauthorized_partner_cannot_approve_foreign_bo_payment(): void
    {
        $partnerOne = Partner::factory()->create();
        $partnerTwoUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partnerTwo = Partner::factory()->create(['user_id' => $partnerTwoUser->id]);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partnerOne->id, // Belongs to Partner One!
        ]);

        $bge = SubscriptionProduct::where('code', 'bge')->first();

        $payment = app(SubscriptionService::class)->submitPayment($boUser, $bge, [
            'payment_method' => 'cash',
        ]);

        // Partner Two attempts to approve Partner One's BO payment
        $this->actingAs($partnerTwoUser);

        $this->expectException(ValidationException::class);
        app(SubscriptionService::class)->approvePayment($payment, $partnerTwoUser);
    }

    public function test_partner_subscribes_and_owner_approves_payment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::factory()->create(['user_id' => $partnerUser->id, 'partner_no' => 'P7701']);

        $bge = SubscriptionProduct::where('code', 'bge')->first();

        // Partner submits payment
        $payment = app(SubscriptionService::class)->submitPayment($partnerUser, $bge, [
            'payment_method' => 'qr_code',
            'reference_no' => 'REF-PARTNER-001',
        ]);

        $this->assertSame('partner', $payment->payer_role);
        $this->assertSame('pending_approval', $payment->status);

        // Partner CANNOT approve their own payment
        $this->assertFalse(app(SubscriptionService::class)->canApprove($partnerUser, $payment));

        // Owner CAN approve Partner payment
        $this->assertTrue(app(SubscriptionService::class)->canApprove($admin, $payment));

        app(SubscriptionService::class)->approvePayment($payment, $admin);

        $payment->refresh();
        $this->assertSame('approved', $payment->status);
        $this->assertSame($admin->id, $payment->approved_by);
        $this->assertNotNull($payment->receipt_no);

        $sub = $payment->subscription->fresh();
        $this->assertTrue($sub->isActive());
    }

    public function test_approved_subscription_payment_receipt_is_viewable(): void
    {
        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $partner = Partner::factory()->create();
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Bengkel Abu',
        ]);

        $bge = SubscriptionProduct::where('code', 'bge')->first();

        $payment = app(SubscriptionService::class)->submitPayment($boUser, $bge, [
            'payment_method' => 'qr_code',
        ]);

        // Unapproved payment receipt should return 404
        $this->actingAs($boUser)
            ->get(route('subscriptions.receipt', $payment->payment_no))
            ->assertNotFound();

        // Admin approves payment
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        app(SubscriptionService::class)->approvePayment($payment, $admin);

        $payment->refresh();

        // Now receipt is accessible to the payer
        $response = $this->actingAs($boUser)
            ->get(route('subscriptions.receipt', $payment->payment_no));

        $response->assertOk();
        $response->assertSee($payment->receipt_no);
        $response->assertSee('Bengkel Abu');
        $response->assertSee('BizGrowth Engine (BGE)');
        $response->assertSee('RM 30.00');
        $response->assertSee('LULUS &amp; DISAHKAN', escape: false);
    }
}
