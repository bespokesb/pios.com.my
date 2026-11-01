<?php

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Partner\Resources\Pages\ListPartnerBoFeeApprovals;
use App\Models\BusinessOwner;
use App\Models\Fee;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerBoFeeApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(null);
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_business_owner_fee_payment_becomes_pending_partner_confirmation(): void
    {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Partner Leader',
        ]);

        $boUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Usahawan Ahmad',
            'business_owner_no' => 'BO001001',
        ]);

        $fee = Fee::create([
            'partner_id' => $partner->id,
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'billing_period' => 'November 2026',
            'amount' => 30.00,
            'status' => FeeStatus::Unpaid,
            'due_date' => now()->addDays(15),
        ]);

        $response = $this->actingAs($boUser)->post(route('business.fees.pay', $fee), [
            'payment_method' => 'bank_transfer',
            'reference_no' => 'FPX-NOV-12345',
            'notes' => 'Telah transfer online',
        ]);

        $response->assertRedirect(route('business.fees.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('whatsapp_url');

        $waUrl = session('whatsapp_url');
        $this->assertNotNull($waUrl);
        $this->assertStringContainsString('wa.me', $waUrl);
        $this->assertStringContainsString('Partner%20Leader', $waUrl);

        $fee->refresh();
        $this->assertSame(FeeStatus::Pending, $fee->status);

        $payment = Payment::where('fee_id', $fee->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame('bank_transfer', $payment->payment_type);
        $this->assertSame('FPX-NOV-12345', $payment->reference_no);

        // Visiting fees page shows pending confirmation and WhatsApp reminder banner
        $indexResponse = $this->actingAs($boUser)->get(route('business.fees.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Menunggu Pengesahan');
    }

    public function test_business_owner_payment_returns_whatsapp_url_in_json(): void
    {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Partner Leader',
            'hp_no' => '0198887777',
        ]);

        $boUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Usahawan Siti',
            'business_owner_no' => 'BO001002',
        ]);

        $fee = Fee::create([
            'partner_id' => $partner->id,
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'billing_period' => 'November 2026',
            'amount' => 30.00,
            'status' => FeeStatus::Unpaid,
            'due_date' => now()->addDays(15),
        ]);

        $response = $this->actingAs($boUser)->postJson(route('business.fees.pay', $fee), [
            'payment_method' => 'bank_transfer',
            'reference_no' => 'FPX-NOV-9999',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['success', 'message', 'whatsapp_url']);
        $this->assertStringContainsString('wa.me/60198887777', $response->json('whatsapp_url'));
    }

    public function test_partner_can_use_check_payment_modal_to_view_and_approve_fee(): void
    {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Partner Leader',
        ]);

        $boUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Usahawan Ahmad',
            'business_owner_no' => 'BO001001',
        ]);

        $fee = Fee::create([
            'partner_id' => $partner->id,
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'billing_period' => 'November 2026',
            'amount' => 30.00,
            'status' => FeeStatus::Pending,
            'due_date' => now()->addDays(15),
        ]);

        $payment = Payment::create([
            'fee_id' => $fee->id,
            'payment_type' => 'bank_transfer',
            'amount' => 30.00,
            'status' => PaymentStatus::Pending,
            'paid_at' => now(),
            'recorded_by' => $boUser->id,
            'reference_no' => 'FPX-NOV-12345',
        ]);

        Filament::setCurrentPanel('partner');
        $this->actingAs($partnerUser);

        Livewire::test(ListPartnerBoFeeApprovals::class)
            ->callTableAction('check_payment', $fee, [
                'payment_method' => 'bank_transfer',
                'reference_no' => 'FPX-NOV-12345',
                'notes' => 'Disahkan dari semakan bank',
            ])
            ->assertHasNoErrors();

        $fee->refresh();
        $payment->refresh();

        $this->assertSame(FeeStatus::Paid, $fee->status);
        $this->assertSame(PaymentStatus::Verified, $payment->status);
    }

    public function test_partner_can_approve_downstream_bo_fee_payment(): void
    {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Partner Leader',
        ]);

        $boUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Usahawan Ahmad',
            'business_owner_no' => 'BO001001',
        ]);

        $fee = Fee::create([
            'partner_id' => $partner->id,
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'billing_period' => 'November 2026',
            'amount' => 30.00,
            'status' => FeeStatus::Pending,
            'due_date' => now()->addDays(15),
        ]);

        $payment = Payment::create([
            'fee_id' => $fee->id,
            'payment_type' => 'cash',
            'amount' => 30.00,
            'status' => PaymentStatus::Pending,
            'paid_at' => now(),
            'recorded_by' => $boUser->id,
        ]);

        Filament::setCurrentPanel('partner');
        $this->actingAs($partnerUser);

        Livewire::test(ListPartnerBoFeeApprovals::class)
            ->callTableAction('approve', $fee, [
                'payment_method' => 'cash',
                'reference_no' => 'CASH-REC-001',
            ])
            ->assertHasNoErrors();

        $fee->refresh();
        $payment->refresh();

        $this->assertSame(FeeStatus::Paid, $fee->status);
        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertSame('CASH-REC-001', $payment->reference_no);
        $this->assertSame($partnerUser->id, $payment->recorded_by);
    }

    public function test_partner_can_reject_downstream_bo_fee_payment(): void
    {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
        ]);

        $boUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
        ]);

        $fee = Fee::create([
            'partner_id' => $partner->id,
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'amount' => 30.00,
            'status' => FeeStatus::Pending,
            'due_date' => now()->addDays(10),
        ]);

        $payment = Payment::create([
            'fee_id' => $fee->id,
            'payment_type' => 'bank_transfer',
            'amount' => 30.00,
            'status' => PaymentStatus::Pending,
            'paid_at' => now(),
            'recorded_by' => $boUser->id,
        ]);

        Filament::setCurrentPanel('partner');
        $this->actingAs($partnerUser);

        Livewire::test(ListPartnerBoFeeApprovals::class)
            ->callTableAction('reject', $fee, [
                'reason' => 'Duit belum masuk akaun bank.',
            ])
            ->assertHasNoErrors();

        $fee->refresh();
        $payment->refresh();

        $this->assertSame(FeeStatus::Unpaid, $fee->status);
        $this->assertSame(PaymentStatus::Failed, $payment->status);
    }

    public function test_partner_can_directly_record_cash_payment_collected_from_bo(): void
    {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Partner Leader',
        ]);

        $boUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Usahawan Siti',
        ]);

        $fee = Fee::create([
            'partner_id' => $partner->id,
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'billing_period' => 'October 2026',
            'amount' => 30.00,
            'status' => FeeStatus::Unpaid,
            'due_date' => now()->subDay(),
        ]);

        Filament::setCurrentPanel('partner');
        $this->actingAs($partnerUser);

        Livewire::test(ListPartnerBoFeeApprovals::class)
            ->callAction('record_payment', [
                'business_owner_id' => $bo->id,
                'fee_id' => $fee->id,
                'payment_method' => 'cash',
                'reference_no' => 'KUTIPAN-TUNAI-001',
                'notes' => 'Kutipan tunai di kedai Siti',
            ])
            ->assertHasNoErrors();

        $fee->refresh();
        $this->assertSame(FeeStatus::Paid, $fee->status);

        $payment = Payment::where('fee_id', $fee->id)->latest()->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertSame('cash', $payment->payment_type);
        $this->assertSame('KUTIPAN-TUNAI-001', $payment->reference_no);
        $this->assertSame($partnerUser->id, $payment->recorded_by);
    }

    public function test_unrelated_partner_cannot_see_foreign_bo_fee(): void
    {
        $partnerOne = Partner::factory()->create();
        $partnerTwoUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partnerTwo = Partner::factory()->create([
            'user_id' => $partnerTwoUser->id,
        ]);

        $bo = BusinessOwner::factory()->create([
            'partner_id' => $partnerOne->id, // Belongs to Partner One!
        ]);

        $foreignFee = Fee::create([
            'partner_id' => $partnerOne->id,
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'amount' => 30.00,
            'status' => FeeStatus::Pending,
        ]);

        Filament::setCurrentPanel('partner');
        $this->actingAs($partnerTwoUser);

        Livewire::test(ListPartnerBoFeeApprovals::class)
            ->assertCanNotSeeTableRecords([$foreignFee]);
    }

    public function test_partner_can_approve_payment_with_duplicate_reference_no_without_unique_constraint_violation(): void
    {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Partner Leader',
        ]);

        // Pre-existing payment already using reference_no 'TnG'
        Payment::create([
            'payment_type' => 'qr_pay',
            'amount' => 25.00,
            'status' => PaymentStatus::Verified,
            'reference_no' => 'TnG',
            'paid_at' => now()->subDay(),
        ]);

        $boUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $bo = BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'applicant_name' => 'Usahawan Mazlan',
        ]);

        $fee = Fee::create([
            'partner_id' => $partner->id,
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'amount' => 10.00,
            'status' => FeeStatus::Unpaid,
            'due_date' => now()->addDays(5),
        ]);

        Filament::setCurrentPanel('partner');
        $this->actingAs($partnerUser);

        // Partner confirms payment using same reference_no 'TnG' via popup modal
        Livewire::test(ListPartnerBoFeeApprovals::class)
            ->callTableAction('check_payment', $fee, [
                'payment_method' => 'bank_transfer',
                'reference_no' => 'TnG',
                'notes' => 'Disahkan dari popup semakan bayaran',
            ])
            ->assertHasNoErrors();

        $fee->refresh();
        $this->assertSame(FeeStatus::Paid, $fee->status);

        $paymentsWithTnG = Payment::where('reference_no', 'TnG')->get();
        $this->assertCount(2, $paymentsWithTnG);

        $newPayment = Payment::where('fee_id', $fee->id)->first();
        $this->assertNotNull($newPayment);
        $this->assertSame(PaymentStatus::Verified, $newPayment->status);
        $this->assertSame('TnG', $newPayment->reference_no);
    }
}
