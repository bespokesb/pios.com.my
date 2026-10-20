<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->string('billing_period', 7)->nullable()->after('fee_type');
            $table->unique(
                ['business_owner_id', 'fee_type', 'billing_period'],
                'fees_bo_type_period_unique'
            );
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index(['partner_id', 'created_at'], 'customers_partner_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_partner_created_index');
        });

        Schema::table('fees', function (Blueprint $table) {
            $table->dropUnique('fees_bo_type_period_unique');
            $table->dropColumn('billing_period');
        });
    }
};
