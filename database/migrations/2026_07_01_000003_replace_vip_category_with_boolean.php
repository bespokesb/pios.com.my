<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * VIP is now a single boolean flag orthogonal to the buyer stage, replacing the
 * VipCustomer/NonLosCustomer categories and the generated vip_customer_no id.
 *
 * category collapses to Lead/Prospect/Customer; is_vip carries LOS/Amway
 * eligibility. Existing VIP customers keep VIP status (is_vip = true) and land
 * on the Customer stage; Non-LOS customers become plain Customers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->boolean('is_vip')->default(false)->after('category');
        });

        DB::table('customers')->where('category', 'vip_customer')->update(['is_vip' => true]);
        DB::table('customers')
            ->whereIn('category', ['vip_customer', 'non_los_customer'])
            ->update(['category' => 'customer']);

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique(['vip_customer_no']);
            $table->dropColumn('vip_customer_no');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('vip_customer_no')->nullable()->unique()->after('customer_no');
        });

        // Best-effort: restore the VIP category from the boolean (ids are not recoverable).
        DB::table('customers')->where('is_vip', true)->update(['category' => 'vip_customer']);

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('is_vip');
        });
    }
};
