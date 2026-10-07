<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index for the farm status column.
 *
 * The other indexes this merge brought together — foods.environmental_score,
 * foods.scans_count, foods.created_at, meals.consumed_on and the unique
 * categories.name — are created by add_catalog_query_indexes, which runs
 * first. Only farms.status is added here, so the two migrations do not try to
 * create the same index twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            // Filtered by FarmController::index (statistics and status filter),
            // requests(), and the sidebar pending counter.
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
