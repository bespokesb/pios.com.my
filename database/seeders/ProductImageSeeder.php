<?php

namespace Database\Seeders;

use App\Models\Product;
use Database\Factories\ProductFactory;
use Illuminate\Database\Seeder;

/**
 * Backfills a demo image onto every product that has none, so existing seeded
 * catalogues (created before images were added) also show product photos.
 *
 * Idempotent: only touches products that lack an image asset.
 * Run with:  php artisan db:seed --class=ProductImageSeeder
 * Refuses to run in production.
 */
class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('ProductImageSeeder skipped: not intended for production.');

            return;
        }

        $updated = 0;

        Product::query()->cursor()->each(function (Product $product) use (&$updated): void {
            $assets = $product->media_assets ?? [];

            $hasImage = collect($assets)->contains(
                fn ($asset): bool => is_string($asset) && (
                    str_starts_with($asset, 'http')
                    || preg_match('/\.(jpe?g|png|webp|gif)$/i', $asset) === 1
                ),
            );

            if ($hasImage) {
                return;
            }

            $product->media_assets = array_merge($assets, [ProductFactory::seededImageUrl($product->product_no)]);
            $product->save();
            $updated++;
        });

        $this->command?->info("Added demo images to {$updated} product(s).");
    }
}
