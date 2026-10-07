<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foods', function (Blueprint $table): void {
            $table->index('producer_id');
        });

        DB::statement(<<<'SQL'
            DELETE FROM stage_transitions
            WHERE id NOT IN (
                SELECT MIN(id)
                FROM stage_transitions
                GROUP BY food_id, to_stage
            )
        SQL);

        Schema::table('stage_transitions', function (Blueprint $table): void {
            $table->unique(['food_id', 'to_stage']);
        });
    }

    public function down(): void
    {
        Schema::table('stage_transitions', function (Blueprint $table): void {
            $table->dropUnique(['food_id', 'to_stage']);
        });

        Schema::table('foods', function (Blueprint $table): void {
            $table->dropIndex(['producer_id']);
        });
    }
};
