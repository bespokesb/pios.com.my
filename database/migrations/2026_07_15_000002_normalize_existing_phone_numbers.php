<?php

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: re-normalise every phone number already stored to the canonical,
 * symbol-free wa.me form (60…), matching the new PhoneNumber cast. Public-order
 * customers in particular kept the raw "012-345 6789" they typed. Also refreshes
 * customers.hp_no_normalized to the same canonical key used for lookup.
 */
return new class extends Migration
{
    public function up(): void
    {
        $targets = [
            'customers' => ['hp_no', 'dd_hp_no'],
            'business_owners' => ['hp_no', 'spouse_hp_no'],
            'partners' => ['hp_no', 'spouse_hp_no'],
            'vendors' => ['phone'],
        ];

        foreach ($targets as $table => $columns) {
            DB::table($table)->select(['id', ...$columns])->orderBy('id')
                ->chunkById(500, function ($rows) use ($table, $columns): void {
                    foreach ($rows as $row) {
                        $update = [];
                        foreach ($columns as $column) {
                            if (filled($row->{$column})) {
                                $update[$column] = Phone::toWhatsApp($row->{$column});
                            }
                        }
                        if ($update !== []) {
                            DB::table($table)->where('id', $row->id)->update($update);
                        }
                    }
                });
        }

        // Keep the indexed lookup key in the same canonical form as hp_no.
        DB::table('customers')->select(['id', 'hp_no'])->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('customers')->where('id', $row->id)
                        ->update(['hp_no_normalized' => Phone::toWhatsApp($row->hp_no)]);
                }
            });
    }

    public function down(): void
    {
        // Normalisation is not reversible (the original formatting is lost).
    }
};
