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
        Schema::table('business_owners', function (Blueprint $table): void {
            $table->text('whatsapp_bot_token')->nullable()->after('whatsapp_api_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_owners', function (Blueprint $table): void {
            $table->dropColumn('whatsapp_bot_token');
        });
    }
};
