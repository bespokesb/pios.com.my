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
            $table->string('whatsapp_endpoint')->nullable()->after('whatsapp_instance_id');
            $table->string('whatsapp_domain')->nullable()->after('whatsapp_endpoint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_owners', function (Blueprint $table): void {
            $table->dropColumn([
                'whatsapp_endpoint',
                'whatsapp_domain',
            ]);
        });
    }
};
