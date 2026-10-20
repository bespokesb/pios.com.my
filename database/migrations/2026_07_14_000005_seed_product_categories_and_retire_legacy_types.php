<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DP-10 data migration. ProductType is now the product "Category". Ensure the
 * three real categories exist (Health Product / Services / Book) and retire the
 * legacy Amway / Non-Amway rows left over from before the classification was
 * removed. Products pointing at a legacy row are set to uncategorised (null) so
 * none is silently mislabelled — the owner re-assigns a real category.
 *
 * Runs on the deploy path (plain `migrate --force`) since the seeder is not run
 * there. Idempotent and safe on a fresh DB (no legacy rows → only the inserts).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['name' => 'Health Product', 'code' => 'health_product'],
            ['name' => 'Services', 'code' => 'services'],
            ['name' => 'Book', 'code' => 'book'],
        ] as $type) {
            DB::table('product_types')->updateOrInsert(
                ['code' => $type['code']],
                ['name' => $type['name'], 'status' => 'active', 'updated_at' => $now, 'created_at' => $now],
            );
        }

        $legacyIds = DB::table('product_types')
            ->whereIn('code', ['amway', 'non_amway'])
            ->pluck('id');

        if ($legacyIds->isNotEmpty()) {
            DB::table('products')
                ->whereIn('product_type_id', $legacyIds)
                ->update(['product_type_id' => null]);

            DB::table('product_types')->whereIn('id', $legacyIds)->delete();
        }
    }

    public function down(): void
    {
        // Reference data only; the legacy Amway/Non-Amway rows are not restored.
    }
};
