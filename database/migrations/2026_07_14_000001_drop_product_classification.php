<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DP-10: remove the Amway / Non-Amway classification entirely. Product category
 * is now expressed solely through product_type_id (ProductType, surfaced as
 * "Category" — Health Product, Services, Book).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'product_types'] as $table) {
            if (Schema::hasColumn($table, 'classification')) {
                Schema::table($table, function (Blueprint $table): void {
                    // The column carries an index (…_classification_index); drop it
                    // first or SQLite rejects the column drop.
                    $table->dropIndex(['classification']);
                    $table->dropColumn('classification');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['products', 'product_types'] as $table) {
            if (! Schema::hasColumn($table, 'classification')) {
                Schema::table($table, function (Blueprint $table): void {
                    $table->string('classification')->default('non_amway')->index();
                });
            }
        }
    }
};
