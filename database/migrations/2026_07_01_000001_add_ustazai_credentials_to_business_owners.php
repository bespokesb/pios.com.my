<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_owners', function (Blueprint $table): void {
            // Each Business Owner has their own UstazAI account. The API key is
            // stored encrypted (cast in the model); sender is the WhatsApp device id.
            $table->text('ustazai_api_key')->nullable()->after('status');
            $table->string('ustazai_sender')->nullable()->after('ustazai_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('business_owners', function (Blueprint $table): void {
            $table->dropColumn(['ustazai_api_key', 'ustazai_sender']);
        });
    }
};
