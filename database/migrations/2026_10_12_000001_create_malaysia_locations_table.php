<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('malaysia_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('state', 64)->index();
            $table->string('city', 64)->index();
            $table->string('postcode', 10)->index();
            $table->timestamps();

            $table->unique(['state', 'city', 'postcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('malaysia_locations');
    }
};
