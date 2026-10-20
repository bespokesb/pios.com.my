<?php

use App\Enums\PromotionMode;
use App\Enums\PromotionStatus;
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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('mode')->default(PromotionMode::Manual->value)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('discount_type', 16)->default('amount');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->text('offer_terms')->nullable();
            $table->string('photo_1_path')->nullable();
            $table->string('photo_2_path')->nullable();
            $table->unsignedInteger('max_customers')->nullable();
            $table->string('status')->default(PromotionStatus::Draft->value)->index();
            $table->timestamp('ran_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('promotion_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('selected')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['promotion_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_customers');
        Schema::dropIfExists('promotions');
    }
};
