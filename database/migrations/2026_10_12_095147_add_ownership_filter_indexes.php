<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ownership-scoped filter indexes.
 *
 * The shipments listing filters on user_id plus status/transport_mode and
 * orders by shipped_on; the meals listing and the consumer dashboard filter
 * on user_id plus consumed_on. The single-column indexes from the earlier
 * audit migrations stay; these composites cover the owner-scoped queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'transport_mode'], 'shipments_owner_filter_index');
        });

        Schema::table('meals', function (Blueprint $table): void {
            $table->index(['user_id', 'consumed_on'], 'meals_owner_consumed_index');
        });
    }

    public function down(): void
    {
        Schema::table('meals', function (Blueprint $table): void {
            $table->dropIndex('meals_owner_consumed_index');
        });

        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropIndex('shipments_owner_filter_index');
        });
    }
};
