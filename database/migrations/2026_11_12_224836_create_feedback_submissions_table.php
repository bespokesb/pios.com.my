<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignId('partner_id')->nullable()->change();
        });

        Schema::create('feedback_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->string('full_name');
            $table->string('hp_no', 50);
            $table->string('partner_id_input')->nullable();
            $table->boolean('q1_income_growth')->nullable();
            $table->boolean('q2_seeking_way')->nullable();
            $table->boolean('q3_interested_tools')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('assigned_to_owner')->default(false);
            $table->timestamps();

            $table->index(['partner_id', 'assigned_to_owner']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_submissions');

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignId('partner_id')->nullable(false)->change();
        });
    }
};
