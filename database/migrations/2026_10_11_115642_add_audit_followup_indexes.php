<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Follow-up indexes from the codebase audit.
 *
 * Every column added here is filtered, sorted or grouped by a controller
 * query: shipments by status/mode/date, warehouses ordered by name, regions
 * ordered by recency, journey-step links, the one-open-report checks, and the
 * farm seeder lookup. Composite report/dispute indexes are deliberately not
 * unique: a reporter may file again after a decision, so only one *pending*
 * row is guarded, and that guard lives in the controllers' transactions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->index('status');
            $table->index('transport_mode');
            $table->index('shipped_on');
        });

        Schema::table('warehouses', function (Blueprint $table): void {
            $table->index('name');
        });

        Schema::table('agricultural_regions', function (Blueprint $table): void {
            $table->index('created_at');
        });

        Schema::table('journey_steps', function (Blueprint $table): void {
            $table->index('farm_id');
            $table->index('shipment_id');
        });

        Schema::table('greenwashing_reports', function (Blueprint $table): void {
            $table->index(['food_id', 'user_id', 'status']);
        });

        Schema::table('analysis_disputes', function (Blueprint $table): void {
            $table->index(['food_id', 'user_id', 'status']);
        });

        Schema::table('farms', function (Blueprint $table): void {
            $table->index(['name', 'agricultural_region_id']);
        });
    }

    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table): void {
            $table->dropIndex(['name', 'agricultural_region_id']);
        });

        Schema::table('analysis_disputes', function (Blueprint $table): void {
            $table->dropIndex(['food_id', 'user_id', 'status']);
        });

        Schema::table('greenwashing_reports', function (Blueprint $table): void {
            $table->dropIndex(['food_id', 'user_id', 'status']);
        });

        Schema::table('journey_steps', function (Blueprint $table): void {
            $table->dropIndex(['farm_id']);
            $table->dropIndex(['shipment_id']);
        });

        Schema::table('agricultural_regions', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });

        Schema::table('warehouses', function (Blueprint $table): void {
            $table->dropIndex(['name']);
        });

        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropIndex(['shipped_on']);
            $table->dropIndex(['transport_mode']);
            $table->dropIndex(['status']);
        });
    }
};
