<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the columns the application filters, sorts or groups on but that
 * no index currently covers.
 *
 *  - farms.status             FarmController::index statistics and status
 *                             filter, requests(), and the sidebar counter
 *  - meals.consumed_on        the consumer's meal log, ordered newest first
 *  - foods.environmental_score  the grade filter, grade sort and grade chart
 *  - foods.scans_count        the "most scanned" public sort
 *
 * Deliberately not added: farms.user_id and greenwashing_reports.food_id.
 * Both are foreign keys, so MySQL already indexes them, and a composite index
 * leading on those columns would be consumed as the FK's index instead of
 * existing alongside it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->index('status', 'farms_status_index');
        });

        Schema::table('meals', function (Blueprint $table) {
            $table->index('consumed_on', 'meals_consumed_on_index');
        });

        Schema::table('foods', function (Blueprint $table) {
            $table->index('environmental_score', 'foods_environmental_score_index');
            $table->index('scans_count', 'foods_scans_count_index');
        });
    }

    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->dropIndex('farms_status_index');
        });

        Schema::table('meals', function (Blueprint $table) {
            $table->dropIndex('meals_consumed_on_index');
        });

        Schema::table('foods', function (Blueprint $table) {
            $table->dropIndex('foods_environmental_score_index');
            $table->dropIndex('foods_scans_count_index');
        });
    }
};
