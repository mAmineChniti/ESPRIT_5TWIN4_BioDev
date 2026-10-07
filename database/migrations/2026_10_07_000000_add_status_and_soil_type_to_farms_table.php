<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            if (!Schema::hasColumn('farms', 'soil_type')) {
                $table->string('soil_type')->nullable()->after('farming_type');
            }
            if (!Schema::hasColumn('farms', 'status')) {
                $table->string('status')->default('validee')->after('soil_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            if (Schema::hasColumn('farms', 'soil_type')) {
                $table->dropColumn('soil_type');
            }
            if (Schema::hasColumn('farms', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
