<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Filament\Resources\IncomingOrders\IncomingOrderResource;
use App\Filament\Resources\IncomingOrders\Pages\ListIncomingOrders;
use App\Filament\Resources\IncomingOrders\Pages\ViewIncomingOrder;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Product;
use App\Notifications\OrderPlaced;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PublicProductPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function ownedProduct(array $overrides = []): Product
    {
        $partner = Partner::factory()->create();
        $bo = BusinessOwner::factory()->forPartner($partner)->create(['company_name' => 'Honey Co']);

        return Product::factory()->forBusinessOwner($bo)->create(array_merge([
            'name' => 'Manuka Honey',
            'selling_price' => 25,
            'cost' => 99.99,
            'sales_commission' => 7.77,
            'status' => ProductStatus::Active,
        ], $overrides));
    }

    public function test_active_bo_product_page_renders_with_order_form(): void
    {
        $product = $this->ownedProduct();

        $this->get(route('public.product.show', $product))
            ->assertOk()
            ->assertSee('Manuka Honey')
            ->assertSee('25.00')
            ->assertSee('Honey Co')
            ->assertSee(__('pios.public.form.submit'));
    }

    public function test_page_never_leaks_cost_or_commission(): void
    {
        $product = $this->ownedProduct();

        $html = $this->get(route('public.product.show', $product))->getContent();

        $this->assertStringNotContainsString('99.99', $html); // cost
        $this->assertStringNotContainsString('7.77', $html);  // commission
    }

    public function test_draft_product_is_not_public(): void
    {
        $product = $this->ownedProduct(['status' => ProductStatus::Draft]);

        $this->get(route('public.product.show', $product))->assertNotFound();
    }

    public function test_admin_catalog_product_without_owner_is_not_public(): void
    {
        $product = Product::factory()->create([
            'business_owner_id' => null,
            'status' => ProductStatus::Active,
        ]);

        $this->get(route('public.product.show', $product))->assertNotFound();
    }

    public function test_placing_order_creates_lead_and_submitted_order(): void
    {
        $product = $this->ownedProduct();

        $this->post(route('public.product.order', $product), [
            'name' => 'Siti Aminah',
            'phone' => '012-345 6789',
            'quantity' => 3,
            'address' => '12 Jalan Mawar',
        ])->assertRedirect(route('public.product.show', $product))
            ->assertSessionHas('order_confirmation');

        $customer = Customer::query()->where('business_owner_id', $product->business_owner_id)->first();
        $this->assertNotNull($customer);
        $this->assertSame(CustomerCategory::Lead, $customer->category);
        $this->assertSame('Siti Aminah', $customer->name);

        $order = Order::query()->first();
        $this->assertSame(OrderStatus::Submitted, $order->status);
        $this->assertSame($product->business_owner_id, $order->business_owner_id);
        $this->assertEquals(75.00, (float) $order->total); // 25 * 3
        $this->assertSame(3, $order->items()->first()->quantity);
    }

    public function test_placing_order_notifies_the_business_owner_in_panel_and_by_mail(): void
    {
        Notification::fake();
        $product = $this->ownedProduct();
        $boUser = $product->businessOwner->user;

        $this->post(route('public.product.order', $product), [
            'name' => 'Siti Aminah',
            'phone' => '012-345 6789',
            'quantity' => 1,
        ])->assertRedirect();

        Notification::assertSentTo($boUser, OrderPlaced::class, function (OrderPlaced $notification) use ($boUser): bool {
            $channels = $notification->via($boUser);
            $database = $notification->toDatabase($boUser);

            return in_array('database', $channels, true)
                && in_array('mail', $channels, true)
                && str_contains($database['title'] ?? '', Order::query()->first()->order_no);
        });
    }

    public function test_repeat_order_matches_existing_customer_by_phone(): void
    {
        $product = $this->ownedProduct();

        $payload = ['name' => 'Repeat Buyer', 'phone' => '0123456789', 'quantity' => 1];
        $this->post(route('public.product.order', $product), $payload);
        // Different formatting of the same number must match, not duplicate.
        $this->post(route('public.product.order', $product), array_merge($payload, [
            'phone' => '012-345 6789',
        ]));

        $this->assertSame(1, Customer::query()->where('business_owner_id', $product->business_owner_id)->count());
        $this->assertSame(2, Order::query()->count());
    }

    public function test_honeypot_blocks_bot_submission(): void
    {
        $product = $this->ownedProduct();

        $this->post(route('public.product.order', $product), [
            'name' => 'Bot', 'phone' => '0123456789', 'quantity' => 1,
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_order_requires_name_and_phone(): void
    {
        $product = $this->ownedProduct();

        $this->post(route('public.product.order', $product), ['quantity' => 1])
            ->assertSessionHasErrors(['name', 'phone']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_bo_orders_resource_is_scoped_and_business_panel_only(): void
    {
        $productA = $this->ownedProduct();
        $productB = $this->ownedProduct();

        $this->post(route('public.product.order', $productA), ['name' => 'A', 'phone' => '0123000001', 'quantity' => 1]);
        $this->post(route('public.product.order', $productB), ['name' => 'B', 'phone' => '0123000002', 'quantity' => 1]);

        $boUserA = $productA->businessOwner->user;

        Filament::setCurrentPanel(Filament::getPanel('business'));
        $this->actingAs($boUserA);

        $visibleIds = IncomingOrderResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($visibleIds->contains(Order::where('business_owner_id', $productA->business_owner_id)->value('id')));
        $this->assertFalse($visibleIds->contains(Order::where('business_owner_id', $productB->business_owner_id)->value('id')));
        $this->assertTrue(IncomingOrderResource::canAccess());

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->assertFalse(IncomingOrderResource::canAccess());
    }

    public function test_bo_orders_list_and_view_pages_render(): void
    {
        $product = $this->ownedProduct();
        $this->post(route('public.product.order', $product), ['name' => 'Render Buyer', 'phone' => '0123000009', 'quantity' => 2]);
        $order = Order::query()->first();
        $boUser = $product->businessOwner->user;

        Filament::setCurrentPanel(Filament::getPanel('business'));
        $this->actingAs($boUser);

        Livewire::test(ListIncomingOrders::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$order]);

        Livewire::test(ViewIncomingOrder::class, [
            'record' => $order->getRouteKey(),
        ])
            ->assertOk()
            ->assertSee($order->order_no)
            ->assertSee('Render Buyer');
    }

    public function test_product_page_renders_multi_image_gallery(): void
    {
        $product = $this->ownedProduct([
            'media_assets' => [
                'https://example.com/img1.jpg',
                'https://example.com/img2.jpg',
                'https://example.com/img3.jpg',
            ],
        ]);

        $this->get(route('public.product.show', $product))
            ->assertOk()
            ->assertSee('https://example.com/img1.jpg')
            ->assertSee('https://example.com/img2.jpg')
            ->assertSee('https://example.com/img3.jpg')
            ->assertSee('thumbContainer')
            ->assertSee('startAutoplay');
    }

    public function test_public_guest_cannot_access_product_by_sequential_product_no(): void
    {
        $product = $this->ownedProduct();

        // Accessing via raw product_no (e.g. /p/PRD000008) must be blocked for guests
        $this->get('/p/'.$product->product_no)
            ->assertNotFound();
    }

    public function test_public_guest_can_access_product_by_secure_slug(): void
    {
        $product = $this->ownedProduct();

        $this->assertNotEmpty($product->slug);
        $this->get('/p/'.$product->slug)
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_changing_ending_character_or_guessing_slug_returns_not_found(): void
    {
        $product = $this->ownedProduct();

        // Mutate the last character
        $invalidSlug = substr($product->slug, 0, -1).'x';
        if ($invalidSlug === $product->slug) {
            $invalidSlug = substr($product->slug, 0, -1).'y';
        }

        $this->get('/p/'.$invalidSlug)
            ->assertNotFound();
        $this->get('/p/PRD000007')
            ->assertNotFound();
        $this->get('/p/PRD000009')
            ->assertNotFound();
    }

    public function test_authenticated_business_owner_can_preview_their_own_product_by_product_no(): void
    {
        $product = $this->ownedProduct();
        $owner = $product->businessOwner->user;

        $this->actingAs($owner)
            ->get('/p/'.$product->product_no)
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_other_business_owner_cannot_access_unowned_product_by_product_no(): void
    {
        $product = $this->ownedProduct();

        $otherBo = BusinessOwner::factory()->create();
        $otherUser = $otherBo->user;

        $this->actingAs($otherUser)
            ->get('/p/'.$product->product_no)
            ->assertNotFound();
    }
}
