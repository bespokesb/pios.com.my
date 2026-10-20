<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ManualOrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Seeds demo orders for existing Business Owners by driving the real public
 * order flow (ManualOrderService) — so each order gets a matched-or-created
 * Lead customer, an order item, and correct BO/partner attribution.
 *
 * Assumes DemoDataSeeder has already run (BOs, products, customers exist).
 * Run with:  php artisan db:seed --class=OrderSeeder
 * Refuses to run in production.
 */
class OrderSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('OrderSeeder skipped: not intended for production.');

            return;
        }

        Auth::login(User::system());
        $service = app(ManualOrderService::class);

        // Weighted status mix: mostly new (Submitted), some done, a few cancelled.
        $statuses = [
            OrderStatus::Submitted, OrderStatus::Submitted, OrderStatus::Submitted,
            OrderStatus::Completed, OrderStatus::Completed, OrderStatus::Cancelled,
        ];

        $created = 0;

        BusinessOwner::query()->with('user')->get()->each(function (BusinessOwner $bo) use ($service, $statuses, &$created): void {
            $products = Product::query()
                ->where('business_owner_id', $bo->id)
                ->where('status', ProductStatus::Active->value)
                ->get();

            if ($products->isEmpty()) {
                return;
            }

            $existing = Customer::query()->where('business_owner_id', $bo->id)->get();

            foreach (range(1, rand(2, 5)) as $ignored) {
                $product = $products->random();

                // ~half re-order from an existing customer (matched by phone),
                // the rest arrive as brand-new leads — mirroring real WhatsApp traffic.
                if ($existing->isNotEmpty() && rand(0, 1) === 1) {
                    $customer = $existing->random();
                    $buyer = [
                        'name' => $customer->name,
                        'phone' => $customer->hp_no ?: '01'.fake()->numerify('#########'),
                    ];
                } else {
                    $buyer = [
                        'name' => fake()->name(),
                        'phone' => '01'.fake()->numerify('#########'),
                    ];
                }

                $buyer['quantity'] = rand(1, 4);
                $buyer['address'] = fake()->streetAddress().', '.fake()->city();

                $order = $service->placePublicOrder($product, $buyer);

                // Spread orders over the last three weeks with a varied status.
                $order->forceFill([
                    'status' => fake()->randomElement($statuses),
                    'ordered_at' => now()->subDays(rand(0, 21))->subHours(rand(0, 23)),
                ])->save();

                $created++;
            }
        });

        Auth::logout();

        $this->command?->info(sprintf(
            'Seeded %d orders across business owners. Total orders now: %d.',
            $created,
            Order::query()->count(),
        ));
    }
}
