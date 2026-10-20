<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_proposals', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_proposals', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->nullable()->after('area');
            }
            if (! Schema::hasColumn('product_proposals', 'stock_in_hand')) {
                $table->unsignedInteger('stock_in_hand')->default(0)->after('description');
            }
            if (! Schema::hasColumn('product_proposals', 'stock_added')) {
                $table->unsignedInteger('stock_added')->default(0)->after('stock_in_hand');
            }
            if (! Schema::hasColumn('product_proposals', 'stock_date_added')) {
                $table->date('stock_date_added')->nullable()->after('stock_added');
            }
            if (! Schema::hasColumn('product_proposals', 'media_assets')) {
                $table->json('media_assets')->nullable()->after('image_video_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_proposals', function (Blueprint $table): void {
            $columnsToDrop = [];
            foreach (['cost_price', 'stock_in_hand', 'stock_added', 'stock_date_added', 'media_assets'] as $column) {
                if (Schema::hasColumn('product_proposals', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
