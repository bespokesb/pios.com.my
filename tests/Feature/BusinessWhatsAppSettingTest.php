<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BusinessWhatsAppSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BusinessOwner $bo;

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
            'business_owner_no' => 'BO000999',
            'company_name' => 'Mega Enterprise',
            'hp_no' => '60123456789',
            'whatsapp_provider' => 'manual',
            'whatsapp_status' => 'connected',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_settings(): void
    {
        $response = $this->get(route('business.settings.index'));

        $response->assertRedirect('/login');
    }

    public function test_business_owner_can_view_settings_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('business.settings.index'));

        $response->assertOk();
        $response->assertSee('Tetapan WhatsApp Blaster');
        $response->assertSee('Wasapla.org (API)');
        $response->assertSee('Matching Base URL');
        $response->assertSee('API Key Pelanggan (Bearer Token)');
        $response->assertSee('Paired Domain (X-App-Domain)');
        $response->assertSee('Webhook Secret (X-Bot-Token)');
        $response->assertSee('Keselamatan 4 Lapisan:');
        $response->assertSee('Wati.io (Official API)');
        $response->assertSee('Manual (wa.me)');
    }

    public function test_business_owner_can_save_wasapla_provider_with_bot_token(): void
    {
        Http::fake([
            'https://api.wasapla.org/v1/instances/kedaianda-com/status' => function (Request $request) {
                $this->assertSame('Bearer wsp_bot_secret998877', $request->header('Authorization')[0]);
                $this->assertSame('wsp_bot_secret998877', $request->header('X-Bot-Token')[0]);
                $this->assertSame('kedaianda.com', $request->header('X-App-Domain')[0]);

                return Http::response([
                    'status' => 'connected',
                    'state' => 'open',
                ], 200);
            },
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.settings.whatsapp.update'), [
            'whatsapp_provider' => 'wasapla',
            'whatsapp_api_token' => '',
            'whatsapp_bot_token' => 'wsp_bot_secret998877',
            'whatsapp_instance_id' => 'kedaianda-com',
            'whatsapp_domain' => 'kedaianda.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'connected',
            'provider' => 'wasapla',
        ]);

        $this->bo->refresh();
        $this->assertSame('wasapla', $this->bo->whatsapp_provider);
        $this->assertNull($this->bo->whatsapp_api_token);
        $this->assertSame('wsp_bot_secret998877', $this->bo->whatsapp_bot_token);
        $this->assertSame('kedaianda-com', $this->bo->whatsapp_instance_id);
        $this->assertSame('connected', $this->bo->whatsapp_status);
    }

    public function test_wasapla_ping_retries_with_bot_token_when_api_key_returns_401(): void
    {
        Http::fake([
            'https://api.wasapla.org/v1/instances/kedaianda-com/status' => function (Request $request) {
                if ($request->header('Authorization')[0] === 'Bearer invalid_api_key') {
                    return Http::response([
                        'success' => false,
                        'error' => 'API Key tidak sah atau telah dibatalkan.',
                    ], 401);
                }

                if ($request->header('Authorization')[0] === 'Bearer valid_bot_token') {
                    return Http::response([
                        'status' => 'connected',
                        'state' => 'open',
                    ], 200);
                }

                return Http::response(['error' => 'Unauthorized'], 401);
            },
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.settings.whatsapp.test'), [
            'whatsapp_provider' => 'wasapla',
            'whatsapp_api_token' => 'invalid_api_key',
            'whatsapp_bot_token' => 'valid_bot_token',
            'whatsapp_instance_id' => 'kedaianda-com',
            'whatsapp_domain' => 'kedaianda.com',
            'send_test_message' => false,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_wasapla_live_message_sends_with_bot_token(): void
    {
        Http::fake([
            'https://api.wasapla.org/v1/instances/kedaianda-com/status' => Http::response([
                'status' => 'connected',
                'state' => 'open',
            ], 200),
            'https://api.wasapla.org/v1/instances/kedaianda-com/send' => function (Request $request) {
                $this->assertSame('Bearer wsp_bot_live_token', $request->header('Authorization')[0]);
                $this->assertSame('wsp_bot_live_token', $request->header('X-Bot-Token')[0]);
                $this->assertSame('kedaianda.com', $request->header('X-App-Domain')[0]);
                $data = $request->data();
                $this->assertSame('60123456789', $data['phone']);
                $this->assertSame('wsp_bot_live_token', $data['bot_token']);

                return Http::response([
                    'status' => true,
                    'message_id' => 'WSP-MSG-998811',
                ], 200);
            },
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.settings.whatsapp.test'), [
            'whatsapp_provider' => 'wasapla',
            'whatsapp_api_token' => '',
            'whatsapp_bot_token' => 'wsp_bot_live_token',
            'whatsapp_instance_id' => 'kedaianda-com',
            'whatsapp_domain' => 'kedaianda.com',
            'test_phone' => '0123456789',
            'send_test_message' => true,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_business_owner_can_save_manual_whatsapp_provider(): void
    {
        $response = $this->actingAs($this->user)->post(route('business.settings.whatsapp.update'), [
            'whatsapp_provider' => 'manual',
        ]);

        $response->assertRedirect(route('business.settings.index'));
        $this->bo->refresh();

        $this->assertSame('manual', $this->bo->whatsapp_provider);
        $this->assertSame('connected', $this->bo->whatsapp_status);
        $this->assertNotNull($this->bo->whatsapp_last_tested_at);
    }

    public function test_business_owner_can_save_fonnte_provider_with_api_ping(): void
    {
        Http::fake([
            'https://api.fonnte.com/device' => Http::response([
                'status' => true,
                'device' => 'Device Connected',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.settings.whatsapp.update'), [
            'whatsapp_provider' => 'fonnte',
            'whatsapp_api_token' => 'sample-fonnte-token-12345',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'connected',
            'provider' => 'fonnte',
        ]);

        $this->bo->refresh();
        $this->assertSame('fonnte', $this->bo->whatsapp_provider);
        $this->assertSame('sample-fonnte-token-12345', $this->bo->whatsapp_api_token);
        $this->assertSame('connected', $this->bo->whatsapp_status);
    }

    public function test_business_owner_can_save_ultramsg_provider_with_api_ping(): void
    {
        Http::fake([
            'https://api.ultramsg.com/instance9999/instance/status*' => Http::response([
                'status' => [
                    'accountStatus' => ['authenticated' => true],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.settings.whatsapp.update'), [
            'whatsapp_provider' => 'ultramsg',
            'whatsapp_instance_id' => 'instance9999',
            'whatsapp_api_token' => 'ultramsg-token-xyz',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'connected',
            'provider' => 'ultramsg',
        ]);

        $this->bo->refresh();
        $this->assertSame('ultramsg', $this->bo->whatsapp_provider);
        $this->assertSame('instance9999', $this->bo->whatsapp_instance_id);
        $this->assertSame('ultramsg-token-xyz', $this->bo->whatsapp_api_token);
        $this->assertSame('connected', $this->bo->whatsapp_status);
    }

    public function test_test_whatsapp_endpoint_pings_provider(): void
    {
        Http::fake([
            'https://api.fonnte.com/device' => Http::response([
                'status' => true,
                'device' => 'Phone Online',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.settings.whatsapp.test'), [
            'whatsapp_provider' => 'fonnte',
            'whatsapp_api_token' => 'test-token',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_test_whatsapp_endpoint_handles_connection_failure(): void
    {
        Http::fake([
            'https://api.fonnte.com/device' => Http::response([
                'status' => false,
                'reason' => 'Invalid token provided',
            ], 401),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.settings.whatsapp.test'), [
            'whatsapp_provider' => 'fonnte',
            'whatsapp_api_token' => 'invalid-token',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_blast_product_via_fonnte_sends_to_all_customers(): void
    {
        // Setup customers
        Customer::create([
            'partner_id' => $this->bo->partner_id,
            'business_owner_id' => $this->bo->id,
            'customer_no' => 'CUST0001',
            'name' => 'Ahmad Albab',
            'hp_no' => '0123456789',
            'address' => 'Kuala Lumpur',
        ]);

        Customer::create([
            'partner_id' => $this->bo->partner_id,
            'business_owner_id' => $this->bo->id,
            'customer_no' => 'CUST0002',
            'name' => 'Siti Nurhaliza',
            'hp_no' => '0198765432',
            'address' => 'Selangor',
        ]);

        // Product
        $product = Product::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'Minyak Habbatussauda Premium',
            'selling_price' => 45.00,
        ]);

        // Configure BO as Fonnte
        $this->bo->update([
            'whatsapp_provider' => 'fonnte',
            'whatsapp_api_token' => 'valid-fonnte-token',
            'whatsapp_status' => 'connected',
        ]);

        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'id' => 'msg-999',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.products.blast', $product), [
            'target' => 'all',
            'message' => 'Salam {name}, jom dapatkan tawaran istimewa!',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'result' => [
                'total' => 2,
                'sent' => 2,
                'failed' => 0,
            ],
        ]);
    }

    public function test_blast_product_via_fonnte_sends_to_custom_phone(): void
    {
        $product = Product::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'Kopi Arabika Organik',
            'selling_price' => 28.00,
        ]);

        $this->bo->update([
            'whatsapp_provider' => 'fonnte',
            'whatsapp_api_token' => 'valid-fonnte-token',
            'whatsapp_status' => 'connected',
        ]);

        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.products.blast', $product), [
            'target' => 'custom',
            'custom_phone' => '01122334455',
            'message' => 'Ujian blast produk!',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'result' => [
                'total' => 1,
                'sent' => 1,
                'failed' => 0,
            ],
        ]);
    }

    public function test_blast_product_via_fonnte_sends_to_selected_customers(): void
    {
        $cust1 = Customer::create([
            'partner_id' => $this->bo->partner_id,
            'business_owner_id' => $this->bo->id,
            'customer_no' => 'CUST0010',
            'name' => 'Target Customer 1',
            'hp_no' => '0121111111',
            'address' => 'KL',
        ]);

        $cust2 = Customer::create([
            'partner_id' => $this->bo->partner_id,
            'business_owner_id' => $this->bo->id,
            'customer_no' => 'CUST0020',
            'name' => 'Ignored Customer 2',
            'hp_no' => '0122222222',
            'address' => 'KL',
        ]);

        $product = Product::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'Produk Terhad',
            'selling_price' => 30.00,
        ]);

        $this->bo->update([
            'whatsapp_provider' => 'fonnte',
            'whatsapp_api_token' => 'valid-fonnte-token',
            'whatsapp_status' => 'connected',
        ]);

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.products.blast', $product), [
            'target' => 'selected',
            'customer_ids' => [$cust1->id],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'result' => [
                'total' => 1,
                'sent' => 1,
            ],
        ]);
    }

    public function test_cannot_blast_product_belonging_to_another_business_owner(): void
    {
        $otherPartner = Partner::factory()->create();
        $otherBo = BusinessOwner::factory()->create([
            'partner_id' => $otherPartner->id,
            'business_owner_no' => 'BO000777',
        ]);

        $otherProduct = Product::factory()->create([
            'business_owner_id' => $otherBo->id,
            'name' => 'Barang Orang Lain',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.products.blast', $otherProduct), [
            'target' => 'all',
        ]);

        $response->assertStatus(403);
    }

    public function test_blast_product_in_manual_mode_returns_manual_share_url(): void
    {
        $product = Product::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'Madu Kelulut Asli',
            'selling_price' => 50.00,
        ]);

        Customer::create([
            'partner_id' => $this->bo->partner_id,
            'business_owner_id' => $this->bo->id,
            'customer_no' => 'CUST0003',
            'name' => 'Pak Samad',
            'hp_no' => '0139998888',
        ]);

        $this->bo->update([
            'whatsapp_provider' => 'manual',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.products.blast', $product), [
            'target' => 'all',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'result' => [
                'is_manual' => true,
                'total' => 1,
            ],
        ]);
    }

    public function test_products_page_renders_manual_wa_options_correctly(): void
    {
        $this->bo->update(['whatsapp_provider' => 'manual']);

        $customer = Customer::create([
            'partner_id' => $this->bo->partner_id,
            'business_owner_id' => $this->bo->id,
            'customer_no' => 'CUST0004',
            'name' => 'Ketapang',
            'hp_no' => '0121234567',
        ]);

        $product = Product::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'Sabun Basuh',
            'selling_price' => 25.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('business.products.index'));

        $response->assertOk();
        $response->assertSee('Bebas');
        $response->assertSee('Pelanggan');
        $response->assertSee('No. Khas / Ujian');
        $response->assertSee('blast-customer-radio');
        $response->assertSee('name="selected_customer_id"', false);
        $response->assertDontSee('id="blastSelectAllBtn"', false);
    }

    public function test_products_page_renders_auto_provider_options_correctly(): void
    {
        $this->bo->update(['whatsapp_provider' => 'fonnte']);

        $customer = Customer::create([
            'partner_id' => $this->bo->partner_id,
            'business_owner_id' => $this->bo->id,
            'customer_no' => 'CUST0005',
            'name' => 'Ahmad',
            'hp_no' => '0129990000',
        ]);

        $product = Product::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'Kopi Arabica',
            'selling_price' => 35.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('business.products.index'));

        $response->assertOk();
        $response->assertSee('Semua Pelanggan (1)');
        $response->assertSee('id="blastSelectAllBtn"', false);
        $response->assertSee('name="customer_ids[]"', false);
    }

    public function test_wasapla_ping_warns_when_device_is_not_scanned_qr(): void
    {
        Http::fake([
            'https://api.wasapla.org/v1/instances/kedaianda-com/status' => Http::response([
                'success' => true,
                'instance_id' => 'kedaianda-com',
                'status' => 'qr',
                'last_connected_at' => null,
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.settings.whatsapp.test'), [
            'whatsapp_provider' => 'wasapla',
            'whatsapp_api_token' => 'wsp_live_token_123',
            'whatsapp_instance_id' => 'https://api.wasapla.org/v1/instances/kedaianda-com',
            'whatsapp_domain' => 'kedaianda.com',
            'send_test_message' => false,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'is_device_connected' => false,
            'device_status' => 'qr',
        ]);
    }

    public function test_wasapla_send_test_message_returns_qr_warning_when_device_is_unlinked(): void
    {
        Http::fake([
            'https://api.wasapla.org/v1/instances/kedaianda-com/status' => Http::response([
                'success' => true,
                'instance_id' => 'kedaianda-com',
                'status' => 'qr',
                'last_connected_at' => null,
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.settings.whatsapp.test'), [
            'whatsapp_provider' => 'wasapla',
            'whatsapp_api_token' => 'wsp_live_token_123',
            'whatsapp_instance_id' => 'https://api.wasapla.org/v1/instances/kedaianda-com',
            'whatsapp_domain' => 'kedaianda.com',
            'test_phone' => '0123456789',
            'send_test_message' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'is_device_connected' => false,
            'device_status' => 'qr',
        ]);
    }

    public function test_wasapla_get_qr_code_returns_base64_image(): void
    {
        Http::fake([
            'https://api.wasapla.org/v1/instances/kedaianda-com/qr' => Http::response([
                'success' => true,
                'instance_id' => 'kedaianda-com',
                'status' => 'qr',
                'qr' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('business.settings.whatsapp.qr'), [
            'whatsapp_provider' => 'wasapla',
            'whatsapp_api_token' => 'wsp_live_token_123',
            'whatsapp_instance_id' => 'https://api.wasapla.org/v1/instances/kedaianda-com',
            'whatsapp_domain' => 'kedaianda.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'qr',
            'qr' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA',
        ]);
    }
}
