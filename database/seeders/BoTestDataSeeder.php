<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use App\Services\ManualOrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Populates the demo Business Owner account bo-test@pios.com.my with Malay
 * products, customers and orders, so logging in as that BO shows a fully
 * furnished panel (and its products have live public order pages).
 *
 * Additive and idempotent-ish: reuses the existing account/BO if present.
 * Run with:  php artisan db:seed --class=BoTestDataSeeder
 * Refuses to run in production.
 */
class BoTestDataSeeder extends Seeder
{
    private const EMAIL = 'bo-test@pios.com.my';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('BoTestDataSeeder skipped: not intended for production.');

            return;
        }

        Auth::login(User::system());

        $businessOwner = $this->resolveBusinessOwner();

        // Malay products owned by this BO, across categories.
        Product::factory()->count(8)->forBusinessOwner($businessOwner)->create();

        // Malay customers across categories.
        Customer::factory()->count(8)->forBusinessOwner($businessOwner)->create();
        Customer::factory()->count(3)->vip()->forBusinessOwner($businessOwner)->create();
        Customer::factory()->count(3)->lead()->forBusinessOwner($businessOwner)->create();

        // Orders through the real public flow (matched leads + items + audit).
        $this->seedOrders($businessOwner);

        Auth::logout();

        $this->command?->info(sprintf(
            '%s ready — products: %d, customers: %d, orders: %d.',
            self::EMAIL,
            Product::query()->where('business_owner_id', $businessOwner->id)->count(),
            Customer::query()->where('business_owner_id', $businessOwner->id)->count(),
            Order::query()->where('business_owner_id', $businessOwner->id)->count(),
        ));
    }

    private function resolveBusinessOwner(): BusinessOwner
    {
        $user = User::query()->firstOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Pak Itam Test',
                'password' => Hash::make('password'),
                'role' => UserRole::BusinessOwner,
                'status' => UserStatus::Active,
            ],
        );

        if ($user->businessOwner) {
            return $user->businessOwner;
        }

        $partner = Partner::query()->inRandomOrder()->first() ?? Partner::factory()->create();

        return BusinessOwner::factory()->forPartner($partner)->create([
            'user_id' => $user->id,
            'applicant_name' => $user->name,
        ]);
    }

    private function seedOrders(BusinessOwner $businessOwner): void
    {
        $products = Product::query()
            ->where('business_owner_id', $businessOwner->id)
            ->where('status', ProductStatus::Active->value)
            ->get();

        if ($products->isEmpty()) {
            return;
        }

        $service = app(ManualOrderService::class);
        $statuses = [
            OrderStatus::Submitted, OrderStatus::Submitted, OrderStatus::Submitted,
            OrderStatus::Completed, OrderStatus::Completed, OrderStatus::Cancelled,
        ];

        foreach (range(1, rand(6, 10)) as $ignored) {
            $product = $products->random();

            $order = $service->placePublicOrder($product, [
                'name' => fake()->name(),
                'phone' => '01'.fake()->numerify('#########'),
                'quantity' => rand(1, 4),
                'address' => fake()->streetAddress().', '.fake()->city(),
            ]);

            $order->forceFill([
                'status' => fake()->randomElement($statuses),
                'ordered_at' => now()->subDays(rand(0, 21))->subHours(rand(0, 23)),
            ])->save();
        }
    }
}
