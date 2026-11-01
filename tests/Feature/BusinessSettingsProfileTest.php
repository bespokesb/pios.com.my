<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\SubscriptionProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessSettingsProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BusinessOwner $bo;

    protected Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->seed(SubscriptionProductSeeder::class);

        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'applicant_name' => 'Dato Partner Hebat',
            'hp_no' => '0198887777',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Ahmad Usahawan',
            'email' => 'ahmad@example.com',
            'password' => Hash::make('oldpassword123'),
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        $this->bo = BusinessOwner::factory()->create([
            'user_id' => $this->user->id,
            'partner_id' => $this->partner->id,
            'applicant_name' => 'Ahmad Usahawan',
            'company_name' => 'Ahmad Maju Enterprise',
            'hp_no' => '0123456789',
            'business_owner_no' => 'BO000888',
        ]);

        app(SubscriptionService::class)->ensureInitialBgeSubscription($this->bo);
    }

    public function test_subscriptions_page_displays_countdown_and_partner_info(): void
    {
        $response = $this->actingAs($this->user)->get(route('business.subscriptions.index'));

        $response->assertOk();
        $response->assertSee('BizGrowth Engine (BGE)');
        $response->assertSee('Subscription Status &amp; Countdown', false);
        $response->assertSee('HOURS LEFT');
        $response->assertSee('Dato Partner Hebat');
        $response->assertSee('Renew Subscription (via WhatsApp)');
    }

    public function test_settings_page_displays_profile_and_password_forms(): void
    {
        $response = $this->actingAs($this->user)->get(route('business.settings.index'));

        $response->assertOk();
        $response->assertSee('Edit Profile &amp; Picture', false);
        $response->assertSee('Change Password');
        $response->assertDontSee('Subscription Status &amp; Countdown', false);
    }

    public function test_business_owner_can_update_profile_info(): void
    {
        $response = $this->actingAs($this->user)->post(route('business.settings.profile.update'), [
            'applicant_name' => 'Ahmad Razali Bin Abu',
            'company_name' => 'Razali Global Resources',
            'hp_no' => '01122334455',
        ]);

        $response->assertRedirect(route('business.settings.index'));
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->bo->refresh();

        $this->assertSame('Ahmad Razali Bin Abu', $this->user->name);
        $this->assertSame('Ahmad Razali Bin Abu', $this->bo->applicant_name);
        $this->assertSame('Razali Global Resources', $this->bo->company_name);
        $this->assertStringContainsString('1122334455', (string) $this->bo->hp_no);
    }

    public function test_business_owner_can_upload_avatar(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('my_avatar.png', 200, 200);

        $response = $this->actingAs($this->user)->post(route('business.settings.profile.update'), [
            'applicant_name' => 'Ahmad Razali',
            'avatar' => $file,
        ]);

        $response->assertRedirect(route('business.settings.index'));
        $this->user->refresh();

        $this->assertNotNull($this->user->avatar);
        Storage::disk('public')->assertExists($this->user->avatar);
        $this->assertStringContainsString('storage/', $this->user->avatarUrl());
    }

    public function test_business_owner_can_update_password_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->user)->post(route('business.settings.password.update'), [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertRedirect(route('business.settings.index'));
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword456', $this->user->password));
    }

    public function test_business_owner_cannot_update_password_with_invalid_current_password(): void
    {
        $response = $this->actingAs($this->user)->post(route('business.settings.password.update'), [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertSessionHasErrors(['current_password']);

        $this->user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $this->user->password));
    }

    public function test_business_owner_can_trigger_renew_via_whatsapp(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('business.subscriptions.renew-whatsapp'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'whatsapp_url',
            'payment_no',
            'message',
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('wa.me/60198887777', $data['whatsapp_url']);
        $this->assertStringContainsString('RM 30.00', urldecode($data['whatsapp_url']));

        // Check that a pending payment was created in database
        $this->assertDatabaseHas('subscription_payments', [
            'business_owner_id' => $this->bo->id,
            'payment_no' => $data['payment_no'],
            'status' => 'pending_approval',
        ]);
    }

    public function test_partner_approves_payment_and_grants_30_days_to_business_owner(): void
    {
        // 1. BO triggers renew via WhatsApp
        $this->actingAs($this->user)
            ->postJson(route('business.subscriptions.renew-whatsapp'));

        $bgeProduct = SubscriptionProduct::where('code', 'bge')->first();
        $sub = $this->bo->subscriptions()->where('subscription_product_id', $bgeProduct->id)->first();
        $payment = $sub->payments()->where('status', 'pending_approval')->first();

        $this->assertNotNull($payment);

        // 2. Partner approves payment in portal
        $partnerUser = $this->partner->user;
        app(SubscriptionService::class)->approvePayment($payment, $partnerUser);

        $payment->refresh();
        $this->assertSame('approved', $payment->status);
        $this->assertSame($partnerUser->id, $payment->approved_by);

        // 3. Subscription is extended by 30 days and active
        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertTrue($sub->isActive());
        $this->assertGreaterThanOrEqual(29, $sub->daysRemaining());
    }
}
