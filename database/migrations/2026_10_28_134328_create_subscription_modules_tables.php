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
        Schema::create('subscription_products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(30.00);
            $table->unsignedInteger('period_days')->default(30);
            $table->string('target_role')->default('all'); // 'all', 'partner', 'business_owner'
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('qr_code_image')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('subscription_no')->unique();
            $table->foreignId('subscription_product_id')->constrained('subscription_products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subscriber_type'); // 'partner' or 'business_owner'
            $table->foreignId('partner_id')->nullable()->constrained('partners')->cascadeOnDelete();
            $table->foreignId('business_owner_id')->nullable()->constrained('business_owners')->cascadeOnDelete();
            $table->string('status')->default('pending_payment'); // pending_payment, pending_approval, active, expired, cancelled
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_renewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['partner_id', 'status']);
            $table->index(['business_owner_id', 'status']);
        });

        Schema::create('subscription_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_no')->unique();
            $table->string('receipt_no')->unique()->nullable();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('subscription_product_id')->constrained('subscription_products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('payer_role'); // 'partner' or 'business_owner'
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('business_owner_id')->nullable()->constrained('business_owners')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('period_days')->default(30);
            $table->string('payment_method')->default('qr_code'); // qr_code, cash, bank_transfer
            $table->string('payment_proof')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('status')->default('pending_approval'); // pending_approval, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index(['partner_id', 'status']);
            $table->index(['business_owner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_products');
    }
};
