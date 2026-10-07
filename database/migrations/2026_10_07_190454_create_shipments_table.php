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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            // 1-N relation: one warehouse has many shipments
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            // Optional link to a product of the shared catalog
            $table->foreignId('food_id')->nullable()->constrained('foods')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('destination', 120);
            $table->decimal('distance_km', 8, 1);
            $table->decimal('weight_kg', 10, 2);
            $table->string('transport_mode', 30);
            $table->string('status', 20)->default('preparing');
            $table->date('shipped_on');
            $table->decimal('carbon_footprint_kg', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
