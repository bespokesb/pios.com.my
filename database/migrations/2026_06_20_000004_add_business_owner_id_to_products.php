<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // Nullable: existing admin-created catalog products have no owner;
            // a non-null owner marks a product created by that Business Owner.
            $table->foreignId('business_owner_id')
                ->nullable()
                ->after('territory_id')
                ->constrained('business_owners')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('business_owner_id');
        });
    }
};
