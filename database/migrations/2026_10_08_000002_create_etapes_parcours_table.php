<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etapes_parcours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parcours_id')->constrained('parcours')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordre');
            $table->string('type', 20);
            $table->string('lieu', 120);
            $table->date('date_etape');
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('ferme_id')->nullable();
            $table->unsignedBigInteger('expedition_id')->nullable();
            $table->timestamps();

            $table->unique(['parcours_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapes_parcours');
    }
};
