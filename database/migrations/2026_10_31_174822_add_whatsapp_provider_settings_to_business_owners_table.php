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
            $table->string('whatsapp_provider')->default('manual')->after('status');
            $table->text('whatsapp_api_token')->nullable()->after('whatsapp_provider');
            $table->string('whatsapp_instance_id')->nullable()->after('whatsapp_api_token');
            $table->string('whatsapp_status')->default('unconfigured')->after('whatsapp_instance_id');
            $table->timestamp('whatsapp_last_tested_at')->nullable()->after('whatsapp_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_owners', function (Blueprint $table): void {
            $table->dropColumn([
                'whatsapp_provider',
                'whatsapp_api_token',
                'whatsapp_instance_id',
                'whatsapp_status',
                'whatsapp_last_tested_at',
            ]);
        });
    }
};
