<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DP-15: an admin-managed vocabulary of customer tags. Business Owners pick tags
 * from this list rather than typing free text. Existing free-text tags are
 * preserved by seeding the distinct values already stored on customers, so
 * nothing is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        // Seed the managed list from tags already in use so no existing tag is lost.
        $now = now();
        $existing = DB::table('customers')->whereNotNull('tags')->pluck('tags');
        $names = [];

        foreach ($existing as $json) {
            foreach ((array) json_decode((string) $json, true) as $tag) {
                $tag = trim((string) $tag);
                if ($tag !== '') {
                    $names[$tag] = true;
                }
            }
        }

        foreach (array_keys($names) as $name) {
            DB::table('customer_tags')->insertOrIgnore([
                'name' => $name,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_tags');
    }
};
