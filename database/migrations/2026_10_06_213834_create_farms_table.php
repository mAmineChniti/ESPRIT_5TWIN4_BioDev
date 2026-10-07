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
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agricultural_region_id')->constrained('agricultural_regions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('producer_name')->nullable();
            $table->string('address');
            $table->decimal('surface_hectares', 8, 2)->default(0);
            $table->string('farming_type')->default('Biologique'); // Biologique, Raisonné, Conventionnel
            $table->string('soil_type')->nullable();
            $table->string('status')->default('validee'); // en_attente, validee, refusee
            $table->string('phone')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};
