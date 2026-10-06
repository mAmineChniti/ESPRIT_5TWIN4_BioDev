<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record every hand-off a product makes along the supply chain, so the
     * journey from producer to distributor is actually queryable.
     */
    public function up(): void
    {
        Schema::create('stage_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_id')->constrained('foods')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_stage')->nullable();
            $table->string('to_stage');
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['food_id', 'occurred_at']);
            $table->index(['to_stage', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_transitions');
    }
};
