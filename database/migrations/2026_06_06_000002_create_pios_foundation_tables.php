<?php

use App\Enums\CustomerCategory;
use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductProposalStatus;
use App\Enums\ProductSelectionStatus;
use App\Enums\ProductStatus;
use App\Enums\RecordStatus;
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
        Schema::create('identifier_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('prefix', 16);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->timestamps();
        });

        Schema::create('territories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('coverage_area')->nullable();
            $table->string('status')->default(RecordStatus::Active->value)->index();
            $table->timestamps();
        });

        Schema::create('partner_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default(RecordStatus::Active->value)->index();
            $table->timestamps();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('classification')->default('non_amway')->index();
            $table->string('status')->default(RecordStatus::Active->value)->index();
            $table->timestamps();
        });

        Schema::create('fee_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('fee_type')->default(FeeType::BusinessOwnerMonthly->value)->index();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('MYR');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->string('status')->default(RecordStatus::Active->value)->index();
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('parent_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('territory_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partner_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('partner_no')->unique();
            $table->string('abo_no')->nullable()->index();
            $table->string('diamond_name')->nullable();
            $table->string('applicant_name');
            $table->string('ic_no')->nullable()->index();
            $table->string('hp_no')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('bandar')->nullable();
            $table->string('negeri')->nullable();
            $table->string('poskod', 16)->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('spouse_ic_no')->nullable();
            $table->string('spouse_hp_no')->nullable();
            $table->string('spouse_email')->nullable();
            $table->string('status')->default(RecordStatus::Active->value)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('business_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('territory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_owner_no')->unique();
            $table->string('applicant_name');
            $table->string('ic_no')->nullable()->index();
            $table->string('hp_no')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('bandar')->nullable();
            $table->string('negeri')->nullable();
            $table->string('poskod', 16)->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('spouse_ic_no')->nullable();
            $table->string('spouse_hp_no')->nullable();
            $table->string('spouse_email')->nullable();
            $table->string('company_name')->nullable();
            $table->text('product_description')->nullable();
            $table->string('status')->default(RecordStatus::Active->value)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('customer_no')->unique();
            $table->string('vip_customer_no')->nullable()->unique();
            $table->string('category')->default(CustomerCategory::Lead->value)->index();
            $table->string('name');
            $table->string('nick_name')->nullable();
            $table->string('hp_no')->nullable()->index();
            $table->string('kp_no')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('abo_no')->nullable()->index();
            $table->string('dd_name')->nullable();
            $table->string('dd_hp_no')->nullable();
            $table->string('status')->default(RecordStatus::Active->value)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('territory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_no')->unique();
            $table->string('classification')->default('non_amway')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->string('coverage_area')->nullable();
            $table->string('certification')->nullable();
            $table->json('promotional_content')->nullable();
            $table->json('media_assets')->nullable();
            $table->json('pricing_structure')->nullable();
            $table->decimal('sales_commission', 10, 2)->default(0);
            $table->string('status')->default(ProductStatus::Draft->value)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('business_owner_product_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(ProductSelectionStatus::Selected->value)->index();
            $table->timestamp('selected_at')->nullable();
            $table->timestamps();

            $table->unique(['business_owner_id', 'product_id'], 'bo_product_unique');
        });

        Schema::create('product_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_owner_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('area')->nullable();
            $table->decimal('partner_price', 10, 2)->nullable();
            $table->decimal('customer_price', 10, 2)->nullable();
            $table->string('image_video_path')->nullable();
            $table->text('description')->nullable();
            $table->string('approval_status')->default(ProductProposalStatus::Pending->value)->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_owner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fee_type')->default(FeeType::BusinessOwnerMonthly->value)->index();
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->string('status')->default(FeeStatus::Unpaid->value)->index();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('order_no')->unique();
            $table->string('status')->default(OrderStatus::Draft->value)->index();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_type')->default('manual')->index();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default(PaymentStatus::Pending->value)->index();
            $table->string('reference_no')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('auditable');
            $table->string('event')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('fees');
        Schema::dropIfExists('product_proposals');
        Schema::dropIfExists('business_owner_product_selections');
        Schema::dropIfExists('products');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('business_owners');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('fee_settings');
        Schema::dropIfExists('product_types');
        Schema::dropIfExists('partner_tiers');
        Schema::dropIfExists('territories');
        Schema::dropIfExists('identifier_sequences');
    }
};
