<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Give customers the same standard address shape as Business Owners: three
 * address lines (address = line 1) plus Bandar / Negeri / Poskod.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('address_line_2')->nullable()->after('address');
            $table->string('address_line_3')->nullable()->after('address_line_2');
            $table->string('bandar')->nullable()->after('address_line_3');
            $table->string('negeri')->nullable()->after('bandar');
            $table->string('poskod', 16)->nullable()->after('negeri');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['address_line_2', 'address_line_3', 'bandar', 'negeri', 'poskod']);
        });
    }
};
