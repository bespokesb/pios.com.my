<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a Business Owner is a verified Amway agent. This is an agent-level
 * attribute verified by the BO's managing Partner (not the BO themselves, and
 * unrelated to the removed product classification). Informational — gates nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_owners', function (Blueprint $table): void {
            $table->boolean('is_amway_agent')->default(false)->after('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('business_owners', function (Blueprint $table): void {
            $table->dropColumn('is_amway_agent');
        });
    }
};
