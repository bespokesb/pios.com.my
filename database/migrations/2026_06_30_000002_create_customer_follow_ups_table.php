<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_owner_id')->constrained()->cascadeOnDelete(); // denormalized for direct-query scoping
            $table->text('note')->nullable();
            $table->date('due_on')->index();
            $table->timestamp('completed_at')->nullable(); // null = not done (the "done flag")
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['business_owner_id', 'due_on']); // drives later reminder/overdue queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_follow_ups');
    }
};
