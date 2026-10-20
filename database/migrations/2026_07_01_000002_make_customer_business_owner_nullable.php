<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A customer may now belong directly to a Partner (account-manager owned) with
 * no Business Owner — the legacy Partner-led customer flow (PRD §10.6). The
 * denormalized partner_id stays required; business_owner_id becomes optional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignId('business_owner_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignId('business_owner_id')->nullable(false)->change();
        });
    }
};
