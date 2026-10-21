<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace step and farm coordinates with an optional city country code.
     */
    public function up(): void
    {
        $stepCoordinates = array_values(array_filter(
            ['latitude', 'longitude'],
            fn (string $column): bool => Schema::hasColumn('journey_steps', $column),
        ));

        if ($stepCoordinates !== []) {
            Schema::table('journey_steps', function (Blueprint $table) use ($stepCoordinates): void {
                $table->dropColumn($stepCoordinates);
            });
        }

        if (! Schema::hasColumn('journey_steps', 'country_code')) {
            Schema::table('journey_steps', function (Blueprint $table): void {
                $table->char('country_code', 2)->nullable()->after('location');
            });
        }

        $farmCoordinates = array_values(array_filter(
            ['latitude', 'longitude'],
            fn (string $column): bool => Schema::hasColumn('farms', $column),
        ));

        if ($farmCoordinates !== []) {
            Schema::table('farms', function (Blueprint $table) use ($farmCoordinates): void {
                $table->dropColumn($farmCoordinates);
            });
        }
    }

    /**
     * Restore the legacy coordinate columns and remove the country code.
     */
    public function down(): void
    {
        if (Schema::hasColumn('farms', 'latitude') === false
            || Schema::hasColumn('farms', 'longitude') === false) {
            Schema::table('farms', function (Blueprint $table): void {
                if (! Schema::hasColumn('farms', 'latitude')) {
                    $table->double('latitude')->nullable();
                }

                if (! Schema::hasColumn('farms', 'longitude')) {
                    $table->double('longitude')->nullable();
                }
            });
        }

        if (Schema::hasColumn('journey_steps', 'country_code')) {
            Schema::table('journey_steps', function (Blueprint $table): void {
                $table->dropColumn('country_code');
            });
        }

        if (Schema::hasColumn('journey_steps', 'latitude') === false
            || Schema::hasColumn('journey_steps', 'longitude') === false) {
            Schema::table('journey_steps', function (Blueprint $table): void {
                if (! Schema::hasColumn('journey_steps', 'latitude')) {
                    $table->double('latitude')->nullable();
                }

                if (! Schema::hasColumn('journey_steps', 'longitude')) {
                    $table->double('longitude')->nullable();
                }
            });
        }
    }
};
