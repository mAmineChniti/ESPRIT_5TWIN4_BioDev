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
        Schema::table('journey_steps', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude']);
            $table->char('country_code', 2)->nullable()->after('location');
        });

        Schema::table('farms', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }

    /**
     * Restore the legacy coordinate columns and remove the country code.
     */
    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table): void {
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
        });

        Schema::table('journey_steps', function (Blueprint $table): void {
            $table->dropColumn('country_code');
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
        });
    }
};
