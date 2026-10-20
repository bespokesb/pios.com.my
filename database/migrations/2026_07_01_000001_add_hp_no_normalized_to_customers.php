<?php

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('hp_no_normalized')->nullable()->after('hp_no');
            // Indexed lookup/duplicate-detection by (owning BO, normalized phone).
            $table->index(['business_owner_id', 'hp_no_normalized']);
        });

        // Backfill existing rows (raw updates: no model events, portable).
        foreach (DB::table('customers')->select('id', 'hp_no')->cursor() as $row) {
            $digits = Phone::digits($row->hp_no);
            DB::table('customers')->where('id', $row->id)->update([
                'hp_no_normalized' => $digits !== '' ? $digits : null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['business_owner_id', 'hp_no_normalized']);
            $table->dropColumn('hp_no_normalized');
        });
    }
};
