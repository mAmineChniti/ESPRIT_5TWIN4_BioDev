<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How many times a product has been looked up by a consumer, so the most
     * scanned products can be surfaced.
     */
    public function up(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->unsignedInteger('scans_count')->default(0)->after('environmental_score');
        });
    }

    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropColumn('scans_count');
        });
    }
};
