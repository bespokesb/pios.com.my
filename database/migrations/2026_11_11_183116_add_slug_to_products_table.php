<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug', 64)->nullable()->unique()->after('product_no');
        });

        // Populate existing products with secure unique slugs
        $products = DB::table('products')->select('id')->get();
        foreach ($products as $product) {
            $slug = Str::lower(Str::random(12));
            while (DB::table('products')->where('slug', $slug)->exists()) {
                $slug = Str::lower(Str::random(12));
            }
            DB::table('products')->where('id', $product->id)->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
