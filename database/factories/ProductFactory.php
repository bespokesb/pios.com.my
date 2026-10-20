<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\RecordStatus;
use App\Models\BusinessOwner;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Women skincare catalogue: name, description, and high-quality Unsplash images.
     *
     * @var array<array{name: string, description: string, images: array<string>}>
     */
    private const SKINCARE_PRODUCTS = [
        [
            'name' => 'Skintific 5X Ceramide Barrier Moisture Gel',
            'description' => 'Moisturizer gel dengan kombinasi 5 tipe Ceramide untuk memperbaiki skin barrier, memberikan kelembapan intensif, dan menenangkan kulit sensitif.',
            'images' => [
                'https://images.unsplash.com/photo-1608248597279-f99d160bfcbc?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1612817288484-6f916006741a?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1617897903246-719242758050?auto=format&fit=crop&w=800&h=800&q=80',
            ],
        ],
        [
            'name' => 'Glad2Glow Centella Allantoin Soothing Gel',
            'description' => 'Gel penenang wajah yang ringan dengan Centella Asiatica dan Allantoin. Membantu menenangkan kerengsaan, mengurangkan kemerahan, dan melembapkan kulit berjerawat.',
            'images' => [
                'https://images.unsplash.com/photo-1601049541289-9b1b7bbbfe19?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?auto=format&fit=crop&w=800&h=800&q=80',
            ],
        ],
        [
            'name' => 'COSRX Advanced Snail 96 Mucin Power Essence',
            'description' => 'Essence dengan kandungan 96.3% Snail Secretion Filtrate untuk memperbaiki tekstur kulit, memudarkan parut jerawat, dan menghidrat kulit secara mendalam.',
            'images' => [
                'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1556229174-5e42a09e45af?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1512290923902-8a9f81dc236c?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1612817288484-6f916006741a?auto=format&fit=crop&w=800&h=800&q=80',
            ],
        ],
        [
            'name' => 'The Ordinary Niacinamide 10% + Zinc 1%',
            'description' => 'Serum berasaskan air dengan kepekatan Niacinamide tinggi untuk mencerahkan tona kulit tidak sekata, mengawal minyak, dan mengurangkan bintik hitam.',
            'images' => [
                'https://images.unsplash.com/photo-1626806787461-102c1bfaaea1?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1601049676099-e7ed07d825b0?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1611080626919-7cf5a9dbab5b?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?auto=format&fit=crop&w=800&h=800&q=80',
            ],
        ],
        [
            'name' => 'Anua Heartleaf 77% Soothing Toner',
            'description' => 'Toner penenang yang dirumus khas untuk kulit berjerawat dan sensitif. Mengandungi 77% ekstrak Heartleaf yang menenangkan kerengsaan kulit dengan segera.',
            'images' => [
                'https://images.unsplash.com/photo-1601049541289-9b1b7bbbfe19?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1612817288484-6f916006741a?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&h=800&q=80',
            ],
        ],
        [
            'name' => 'Beauty of Joseon Relief Sun SPF50+',
            'description' => 'Pelindung matahari organik yang mengandungi 30% ekstrak beras dan ekstrak probiotik bijirin untuk menenangkan kulit dan mencerahkan wajah.',
            'images' => [
                'https://images.unsplash.com/photo-1608248597279-f99d160bfcbc?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1617897903246-719242758050?auto=format&fit=crop&w=800&h=800&q=80',
                'https://images.unsplash.com/photo-1611080626919-7cf5a9dbab5b?auto=format&fit=crop&w=800&h=800&q=80',
            ],
        ],
    ];

    /**
     * Product categories (ProductType) seeded on demand: code => display name.
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'health_product' => 'Health Product',
        'services' => 'Services',
        'book' => 'Book',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $skincare = fake()->randomElement(self::SKINCARE_PRODUCTS);
        $selling = fake()->randomFloat(2, 15, 250);
        $productNo = 'PRD'.fake()->unique()->numerify('#####');

        return [
            'product_type_id' => $this->categoryId('health_product'),
            'business_owner_id' => BusinessOwner::factory(),
            'product_no' => $productNo,
            'name' => $skincare['name'],
            'description' => $skincare['description'],
            'selling_price' => $selling,
            'cost' => round($selling * fake()->randomFloat(2, 0.4, 0.8), 2),
            'coverage_area' => fake()->randomElement(['Semenanjung Malaysia', 'Seluruh Malaysia', 'Lembah Klang']),
            'media_assets' => $skincare['images'],
            'status' => ProductStatus::Active,
        ];
    }

    /**
     * A stable, deterministic demo image URL for a product (Lorem Picsum).
     * The product number is the seed, so each product keeps the same image.
     */
    public static function seededImageUrl(string $seed): string
    {
        return "https://picsum.photos/seed/{$seed}/800/800.jpg";
    }

    /**
     * Assign the product to a given category (by code, e.g. 'health_product').
     */
    public function category(string $code): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_type_id' => $this->categoryId($code),
        ]);
    }

    public function forBusinessOwner(BusinessOwner $businessOwner): static
    {
        return $this->state(fn (array $attributes): array => [
            'business_owner_id' => $businessOwner->id,
        ]);
    }

    /**
     * Pick a random [name, description] pair from a catalogue.
     *
     * @param  array<string, string>  $catalogue
     * @return array{0: string, 1: string}
     */
    private function pickProduct(array $catalogue): array
    {
        $name = fake()->randomElement(array_keys($catalogue));

        return [$name, $catalogue[$name]];
    }

    /**
     * Resolve (or create) the ProductType (category) row for a code.
     */
    private function categoryId(string $code): int
    {
        return ProductType::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => self::CATEGORIES[$code] ?? ucfirst(str_replace('_', ' ', $code)),
                'status' => RecordStatus::Active,
            ],
        )->id;
    }
}
