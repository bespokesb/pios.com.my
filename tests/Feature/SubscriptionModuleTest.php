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
use App\Services\WhatsAppPassSender;
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
        $response->assertSee('APPROVED &amp; VERIFIED', escape: false);
        $response->assertSee('Official Receipt');
    }

    public function test_partner_directly_records_and_confirms_bo_payment_with_bundle(): void
    {
        $partnerUser = User::factory()->create([
            'name' => 'Partner Syazwan',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Partner Syazwan',
        ]);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Kedai Kopi Rozita',
            'hp_no' => '0198887766',
        ]);

        $service = app(SubscriptionService::class);

        // Partner records 2-month bundle (RM 60.00, 60 days) via Cash
        $payment = $service->recordPartnerPaymentForBo(
            $partnerUser,
            $bo,
            months: 2,
            paymentMethod: 'cash',
            referenceNo: 'CASH-REF-001',
            notes: 'Received cash at shop.'
        );

        $this->assertNotNull($payment);
        $this->assertSame('approved', $payment->status);
        $this->assertEquals(60.00, (float) $payment->amount);
        $this->assertSame(60, $payment->period_days);
        $this->assertSame('cash', $payment->payment_method);
        $this->assertNotNull($payment->receipt_no);
        $this->assertStringStartsWith('RCT', $payment->receipt_no);
        $this->assertSame($partnerUser->id, $payment->approved_by);

        // Verify Subscription countdown (extends existing 48h trial by 60 days)
        $sub = $payment->subscription->fresh();
        $this->assertTrue($sub->isActive());
        $this->assertSame('active', $sub->status);
        $this->assertGreaterThanOrEqual(60, $sub->daysRemaining());
        $this->assertLessThanOrEqual(62, $sub->daysRemaining());

        // Verify Official Receipt reflects bundle details in English
        $response = $this->actingAs($boUser)
            ->get(route('subscriptions.receipt', $payment->payment_no));

        $response->assertOk();
        $response->assertSee($payment->receipt_no);
        $response->assertSee('RM 60.00');
        $response->assertSee('60 Days');
        $response->assertSee('Cash');
        $response->assertSee('Partner Syazwan');

        // Verify WhatsApp receipt text generator
        $waReceiptUrl = WhatsAppPassSender::forSubscriptionReceiptToBo($payment);
        $this->assertNotNull($waReceiptUrl);
        $this->assertStringContainsString('60198887766', $waReceiptUrl);
        $this->assertStringContainsString(rawurlencode('RM 60.00'), $waReceiptUrl);
        $this->assertStringContainsString(rawurlencode('2 Month(s) (60 Days)'), $waReceiptUrl);
        $this->assertStringContainsString(rawurlencode($payment->receipt_no), $waReceiptUrl);
    }

    public function test_owner_cannot_approve_bo_payment_only_partner_or_sysadmin_can(): void
    {
        $owner = User::factory()->create(['role' => UserRole::PiosOwner, 'status' => UserStatus::Active]);
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::factory()->create(['user_id' => $partnerUser->id]);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
        ]);

        $bge = SubscriptionProduct::where('code', 'bge')->first();

        $payment = app(SubscriptionService::class)->submitPayment($boUser, $bge, [
            'payment_method' => 'bank_transfer',
        ]);

        $service = app(SubscriptionService::class);

        // Owner cannot approve BO payment (verification is exclusively by Partner)
        $this->assertFalse($service->canApprove($owner, $payment));

        // Partner CAN approve
        $this->assertTrue($service->canApprove($partnerUser, $payment));

        // Attempting owner approval throws ValidationException
        $this->expectException(ValidationException::class);
        $service->approvePayment($payment, $owner);
    }

    public function test_partner_can_approve_bo_payment_with_bundle_via_filament_table(): void
    {
        Filament::setCurrentPanel('partner');

        $partnerUser = User::factory()->create([
            'name' => 'Partner Firdaus',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create(['user_id' => $partnerUser->id]);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Kedai Runcit Mak Esah',
        ]);

        $bge = SubscriptionProduct::where('code', 'bge')->first();

        // Business owner submitted payment (default 30 days, RM 30)
        $payment = app(SubscriptionService::class)->submitPayment($boUser, $bge, [
            'payment_method' => 'cash',
        ]);

        $this->actingAs($partnerUser);

        Livewire::test(ListPartnerBoPaymentApprovals::class)
            ->callTableAction('approve', $payment, [
                'months' => 2, // 2 Months bundle (RM 60, 60 days)
                'payment_method' => 'cash',
                'notes' => 'Confirmed 2 months bundle.',
            ])
            ->assertHasNoTableActionErrors();

        $payment->refresh();
        $this->assertSame('approved', $payment->status);
        $this->assertEquals(60.00, (float) $payment->amount);
        $this->assertSame(60, $payment->period_days);
        $this->assertNotNull($payment->receipt_no);

        $sub = $payment->subscription->fresh();
        $this->assertTrue($sub->isActive());
        $this->assertSame('active', $sub->status);
    }

    public function test_partner_can_approve_payment_via_check_payment_popup_action(): void
    {
        Filament::setCurrentPanel('partner');

        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create(['user_id' => $partnerUser->id]);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Kedai Kopi Abu',
        ]);

        $bge = SubscriptionProduct::where('code', 'bge')->first();

        $payment = app(SubscriptionService::class)->submitPayment($boUser, $bge, [
            'payment_method' => 'cash',
        ]);

        $this->actingAs($partnerUser);

        // Partner clicks "Pending Confirmation" badge on status column (Check Payment popup)
        Livewire::test(ListPartnerBoPaymentApprovals::class)
            ->callTableAction('check_payment', $payment, [
                'months' => 1,
                'payment_method' => 'cash',
                'reference_no' => 'CASH-ABU-01',
                'notes' => 'Disahkan dari popup semakan status bayaran',
            ])
            ->assertHasNoTableActionErrors();

        $payment->refresh();
        $this->assertSame('approved', $payment->status);
        $this->assertSame('CASH-ABU-01', $payment->reference_no);
        $this->assertNotNull($payment->receipt_no);

        $sub = $payment->subscription->fresh();
        $this->assertTrue($sub->isActive());
        $this->assertSame('active', $sub->status);
    }

    public function test_new_business_owner_gets_initial_48_hour_bge_trial_subscription(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::factory()->create(['user_id' => $partnerUser->id]);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'created_at' => now(),
        ]);

        $subscription = app(SubscriptionService::class)->ensureInitialBgeSubscription($bo);

        $this->assertNotNull($subscription);
        $this->assertSame('trial', $subscription->status);
        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->isTrial());
        $this->assertFalse($subscription->isExpired());
        $this->assertGreaterThan(0, $subscription->hoursRemaining());
        $this->assertLessThanOrEqual(48, $subscription->hoursRemaining());
    }

    public function test_business_owner_created_more_than_48_hours_ago_is_marked_expired(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::factory()->create(['user_id' => $partnerUser->id]);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'created_at' => now()->subHours(49),
        ]);

        $subscription = app(SubscriptionService::class)->ensureInitialBgeSubscription($bo);

        $this->assertNotNull($subscription);
        $this->assertSame('expired', $subscription->status);
        $this->assertFalse($subscription->isActive());
        $this->assertFalse($subscription->isTrial());
        $this->assertTrue($subscription->isExpired());
        $this->assertSame(0, $subscription->hoursRemaining());
    }

    public function test_business_owner_with_active_trial_sees_trial_banner_on_dashboard(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::factory()->create(['user_id' => $partnerUser->id]);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($boUser)->get(route('business.dashboard'));

        $response->assertOk();
        $response->assertSee('48-Hour Free Trial');
        $response->assertSee('Your BizGrowth Engine (BGE) trial access has');
        $response->assertDontSee('id="bgeExpiredModalOverlay"', escape: false);
    }

    public function test_business_owner_with_expired_trial_sees_popup_modal_with_partner_whatsapp(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Dato Partner Hebat',
            'partner_no' => 'P1001',
            'hp_no' => '0123456789',
        ]);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Kedai Jaya',
            'created_at' => now()->subHours(50),
        ]);

        $response = $this->actingAs($boUser)->get(route('business.dashboard'));

        $response->assertOk();
        // Modal overlay present
        $response->assertSee('id="bgeExpiredModalOverlay"', escape: false);
        $response->assertSee('BGE Subscription Required');
        $response->assertSee('Your 48-hour BizGrowth Engine (BGE) free trial period has ended.');
        $response->assertSee('Dato Partner Hebat');
        $response->assertSee('P1001');
        $response->assertSee('Contact Partner via WhatsApp');
        $response->assertSee('https://wa.me/60123456789', escape: false);

        // Subscriptions page does not display the blocking modal so they can pay
        $subResponse = $this->actingAs($boUser)->get(route('business.subscriptions.index'));
        $subResponse->assertOk();
        $subResponse->assertDontSee('id="bgeExpiredModalOverlay"', escape: false);

        // Modal is non-dismissible: no close buttons or dismiss actions
        $response->assertDontSee('Dismiss');
        $response->assertDontSee('bge-modal-close');
        $response->assertDontSee('closeBgeModal');
    }

    public function test_pending_bge_approval_modal_is_non_dismissible_until_partner_approves(): void
    {
        $partnerUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Dato Partner Hebat',
            'partner_no' => 'P1002',
            'hp_no' => '0123456789',
        ]);

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Kedai Kopi Abu',
            'created_at' => now()->subHours(50),
        ]);

        $bge = SubscriptionProduct::where('code', 'bge')->first();

        // Ensure initial BGE sub (trial expired after 50 hours)
        app(SubscriptionService::class)->ensureInitialBgeSubscription($bo);

        // 1. BO submits payment
        $payment = app(SubscriptionService::class)->submitPayment($boUser, $bge, [
            'payment_method' => 'cash',
        ]);

        $this->assertSame('pending_approval', $payment->status);

        // 2. Visiting portal shows non-dismissible Pending Confirmation modal
        $response = $this->actingAs($boUser)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('id="bgeExpiredModalOverlay"', escape: false);
        $response->assertSee('BGE Payment Pending Confirmation');
        $response->assertSee('Your subscription request is being reviewed by your Partner.');
        $response->assertDontSee('Dismiss');
        $response->assertDontSee('bge-modal-close');
        $response->assertDontSee('closeBgeModal');

        // 3. Partner approves the payment
        app(SubscriptionService::class)->approvePayment($payment, $partnerUser);

        // 4. Modal disappears and Business Owner can now use the portal
        $postApprovalResponse = $this->actingAs($boUser)->get('/dashboard');
        $postApprovalResponse->assertOk();
        $postApprovalResponse->assertDontSee('id="bgeExpiredModalOverlay"', escape: false);
        $postApprovalResponse->assertDontSee('BGE Payment Pending Confirmation');
    }
}
