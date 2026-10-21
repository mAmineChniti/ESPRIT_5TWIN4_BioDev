<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the city reference and canonical pair distance cache.
     */
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('name_normalized');
            $table->char('country_code', 2);
            $table->string('region')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->unique(['name_normalized', 'country_code']);
            $table->index('name_normalized');
            $table->index('country_code');
        });

        Schema::create('city_distances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_a_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('city_b_id')->constrained('cities')->cascadeOnDelete();
            $table->unsignedInteger('distance_km');
            $table->unique(['city_a_id', 'city_b_id']);
        });
    }

    /**
     * Drop the city reference and its cached distances.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_distances');
        Schema::dropIfExists('cities');
    }
};
