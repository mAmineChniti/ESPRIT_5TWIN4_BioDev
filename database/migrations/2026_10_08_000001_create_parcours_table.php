<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('produit_id')
                ->unique()
                ->constrained('foods')
                ->cascadeOnDelete();
            $table->string('code_qr', 64)->nullable()->unique();
            $table->decimal('score_environnemental', 5, 2);
            $table->decimal('distance_totale_km', 8, 1)->default(0);
            $table->text('resume_ia')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcours');
    }
};
