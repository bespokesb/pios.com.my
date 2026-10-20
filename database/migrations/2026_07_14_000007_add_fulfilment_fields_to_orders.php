<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v1.3 Phase 16 (DP-16): order fulfilment fields — a tracking number recorded at
 * shipment, and paid_at / shipped_at timestamps stamped as the order moves
 * through its lifecycle (read by the invoice/receipt docs and the BO reports).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('tracking_number')->nullable()->after('total');
            $table->timestamp('paid_at')->nullable()->after('ordered_at');
            $table->timestamp('shipped_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['tracking_number', 'paid_at', 'shipped_at']);
        });
    }
};
