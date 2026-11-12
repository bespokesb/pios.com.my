<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BusinessOwner $bo;

    protected Product $product;

    protected Customer $customer1;

    protected Customer $customer2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->user = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::factory()->create();

        $this->bo = BusinessOwner::factory()->create([
            'user_id' => $this->user->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000888',
            'company_name' => 'Promo Enterprise',
            'whatsapp_provider' => 'wasapla',
            'whatsapp_api_token' => 'wsp_live_promo_token',
            'whatsapp_instance_id' => 'https://api.wasapla.org/v1/instances/promo-test',
            'whatsapp_domain' => 'stag.pios.com.my',
            'whatsapp_status' => 'connected',
        ]);

        $this->product = Product::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'Sabun Organik Premium',
            'selling_price' => 45.00,
        ]);

        $this->customer1 = Customer::create([
            'partner_id' => $partner->id,
            'business_owner_id' => $this->bo->id,
            'customer_no' => 'CUST1001',
            'name' => 'Ali',
            'hp_no' => '0123456789',
            'is_vip' => true,
        ]);

        $this->customer2 = Customer::create([
            'partner_id' => $partner->id,
            'business_owner_id' => $this->bo->id,
            'customer_no' => 'CUST1002',
            'name' => 'Siti',
            'hp_no' => '0198765432',
            'is_vip' => false,
        ]);
    }

    public function test_business_owner_can_view_promotions_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('business.promotions.index'));

        $response->assertOk();
        $response->assertSee('WhatsApp Promotion');
        $response->assertSee('Scheduled Blast');
        $response->assertSee('Sabun Organik Premium');
        $response->assertSee('Ali');
        $response->assertSee('Siti');
    }

    public function test_business_owner_can_send_immediate_whatsapp_promotion(): void
    {
        Http::fake([
            'https://api.wasapla.org/v1/instances/promo-test/send' => function (Request $request) {
                $data = $request->data();
                $this->assertStringContainsString('Sabun Organik Premium', $data['message']);
                $this->assertStringContainsString('RM 45.00', $data['message']);

                return Http::response([
                    'status' => true,
                    'message_id' => 'MSG-PROMO-01',
                ], 200);
            },
        ]);

        $response = $this->actingAs($this->user)->post(route('business.promotions.store'), [
            'title' => 'Promosi Kilat Sabun',
            'product_id' => $this->product->id,
            'message' => 'Salam {name}, dapatkan {product_name} pada harga {price} di {link}',
            'target_type' => 'selected',
            'target_customer_ids' => [$this->customer1->id],
            'send_mode' => 'now',
        ]);

        $response->assertRedirect(route('business.promotions.index'));

        $this->assertDatabaseHas('whatsapp_campaigns', [
            'business_owner_id' => $this->bo->id,
            'product_id' => $this->product->id,
            'title' => 'Promosi Kilat Sabun',
            'status' => WhatsAppCampaign::STATUS_COMPLETED,
            'total_recipients' => 1,
            'sent_count' => 1,
            'failed_count' => 0,
        ]);
    }

    public function test_business_owner_can_schedule_whatsapp_blast_for_future(): void
    {
        Http::fake();

        $futureTime = now()->addHours(3)->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->user)->post(route('business.promotions.store'), [
            'title' => 'Promosi Hujung Minggu',
            'product_id' => $this->product->id,
            'message' => 'Hai {name}, promo {product_name}!',
            'target_type' => 'all',
            'send_mode' => 'schedule',
            'scheduled_at' => $futureTime,
        ]);

        $response->assertRedirect(route('business.promotions.index'));

        $this->assertDatabaseHas('whatsapp_campaigns', [
            'business_owner_id' => $this->bo->id,
            'title' => 'Promosi Hujung Minggu',
            'status' => WhatsAppCampaign::STATUS_SCHEDULED,
            'total_recipients' => 2,
            'sent_count' => 0,
        ]);

        Http::assertNothingSent();
    }

    public function test_scheduled_blast_command_processes_due_campaigns_only(): void
    {
        Http::fake([
            'https://api.wasapla.org/v1/instances/promo-test/send' => Http::response([
                'status' => true,
                'message_id' => 'MSG-SCHED-01',
            ], 200),
        ]);

        $dueCampaign = WhatsAppCampaign::create([
            'business_owner_id' => $this->bo->id,
            'product_id' => $this->product->id,
            'title' => 'Kempen Sudah Tiba Masa',
            'message' => 'Hai {name}, sila semak {product_name}',
            'target_type' => 'vip',
            'status' => WhatsAppCampaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->subMinutes(2),
            'total_recipients' => 1,
        ]);

        $futureCampaign = WhatsAppCampaign::create([
            'business_owner_id' => $this->bo->id,
            'product_id' => $this->product->id,
            'title' => 'Kempen Masa Depan',
            'message' => 'Hai {name}, tunggu esok',
            'target_type' => 'all',
            'status' => WhatsAppCampaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->addHours(5),
            'total_recipients' => 2,
        ]);

        $this->artisan('whatsapp:process-scheduled-blasts')
            ->assertExitCode(0);

        $dueCampaign->refresh();
        $futureCampaign->refresh();

        $this->assertSame(WhatsAppCampaign::STATUS_COMPLETED, $dueCampaign->status);
        $this->assertSame(1, $dueCampaign->sent_count);
        $this->assertNotNull($dueCampaign->sent_at);

        $this->assertSame(WhatsAppCampaign::STATUS_SCHEDULED, $futureCampaign->status);
        $this->assertSame(0, $futureCampaign->sent_count);
    }

    public function test_business_owner_can_cancel_and_delete_scheduled_campaign(): void
    {
        $campaign = WhatsAppCampaign::create([
            'business_owner_id' => $this->bo->id,
            'title' => 'Kempen Batal',
            'message' => 'Test',
            'target_type' => 'all',
            'status' => WhatsAppCampaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($this->user)
            ->post(route('business.promotions.cancel', $campaign))
            ->assertRedirect(route('business.promotions.index'));

        $this->assertSame(WhatsAppCampaign::STATUS_CANCELLED, $campaign->fresh()->status);

        $this->actingAs($this->user)
            ->delete(route('business.promotions.destroy', $campaign))
            ->assertRedirect(route('business.promotions.index'));

        $this->assertDatabaseMissing('whatsapp_campaigns', ['id' => $campaign->id]);
    }

    public function test_another_business_owner_cannot_manage_foreign_campaign(): void
    {
        $otherUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $otherBo = BusinessOwner::factory()->create([
            'user_id' => $otherUser->id,
            'partner_id' => $this->bo->partner_id,
        ]);

        $foreignCampaign = WhatsAppCampaign::create([
            'business_owner_id' => $otherBo->id,
            'title' => 'Kempen Orang Lain',
            'message' => 'Secret',
            'target_type' => 'all',
            'status' => WhatsAppCampaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($this->user)
            ->post(route('business.promotions.cancel', $foreignCampaign))
            ->assertForbidden();

        $this->actingAs($this->user)
            ->delete(route('business.promotions.destroy', $foreignCampaign))
            ->assertForbidden();
    }
}
