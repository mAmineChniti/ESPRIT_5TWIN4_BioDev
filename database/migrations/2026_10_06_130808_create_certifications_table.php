<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the free-text certifications column with a real, validated entity
     * carrying an issuing body and an expiry date.
     */
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->unique();
            $table->string('issuer')->nullable();
            $table->string('certificate_number')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });

        Schema::create('certification_food', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_id')->constrained('foods')->cascadeOnDelete();
            $table->date('obtained_on')->nullable();
            $table->timestamps();

            $table->unique(['certification_id', 'food_id']);
        });

        Schema::table('foods', function (Blueprint $table) {
            $table->dropColumn('certifications');
        });
    }

    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->string('certifications')->nullable()->after('origin');
        });

        Schema::dropIfExists('certification_food');
        Schema::dropIfExists('certifications');
    }
};
