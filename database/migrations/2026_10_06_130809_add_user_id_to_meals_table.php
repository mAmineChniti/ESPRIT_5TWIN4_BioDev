<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Own meals per user and let a meal reference the products it contained.
     */
    public function up(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        Schema::create('meal_food', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_id')->constrained('meals')->cascadeOnDelete();
            $table->foreignId('food_id')->constrained('foods')->cascadeOnDelete();
            $table->decimal('quantity', 8, 2)->default(1);
            $table->timestamps();

            $table->unique(['meal_id', 'food_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_food');

        Schema::table('meals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
