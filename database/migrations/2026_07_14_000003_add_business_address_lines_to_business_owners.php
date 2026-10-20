<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DP-4: the Business Owner's business address is captured as three lines
 * (address = line 1, plus line 2 and line 3), alongside the existing
 * Bandar / Negeri / Poskod fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_owners', function (Blueprint $table): void {
            $table->string('address_line_2')->nullable()->after('address');
            $table->string('address_line_3')->nullable()->after('address_line_2');
        });
    }

    public function down(): void
    {
        Schema::table('business_owners', function (Blueprint $table): void {
            $table->dropColumn(['address_line_2', 'address_line_3']);
        });
    }
};
