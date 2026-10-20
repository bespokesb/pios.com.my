<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DP-8: optional customer profile fields for richer CRM records — age (umur),
 * marital status, occupation and free-text remarks. Address already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->unsignedTinyInteger('age')->nullable()->after('email');
            $table->string('marital_status')->nullable()->after('age');
            $table->string('occupation')->nullable()->after('marital_status');
            $table->text('remarks')->nullable()->after('occupation');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['age', 'marital_status', 'occupation', 'remarks']);
        });
    }
};
