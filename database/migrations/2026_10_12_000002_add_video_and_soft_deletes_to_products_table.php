<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'video_path')) {
                $table->string('video_path')->nullable()->after('media_assets');
            }
            if (! Schema::hasColumn('products', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('product_proposals', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_proposals', 'video_path')) {
                $table->string('video_path')->nullable()->after('media_assets');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'video_path')) {
                $table->dropColumn('video_path');
            }
            if (Schema::hasColumn('products', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('product_proposals', function (Blueprint $table): void {
            if (Schema::hasColumn('product_proposals', 'video_path')) {
                $table->dropColumn('video_path');
            }
        });
    }
};
