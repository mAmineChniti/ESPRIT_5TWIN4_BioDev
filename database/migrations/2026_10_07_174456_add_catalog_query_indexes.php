<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the columns the catalog filters, sorts and groups on.
 *
 * Category::firstOrCreate() during a CSV import relies on name being unique, so
 * that index is created here alongside the rest rather than being patched into
 * the original create migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unique('name');
        });

        Schema::table('foods', function (Blueprint $table) {
            $table->index('environmental_score');
            $table->index('scans_count');
            $table->index('created_at');
        });

        Schema::table('meals', function (Blueprint $table) {
            $table->index('consumed_on');
        });
    }

    public function down(): void
    {
        // Collapse any duplicate categories the missing index allowed before
        // dropping it, or the unique constraint cannot be created.
        $duplicates = DB::table('categories')
            ->select('name', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('name')
            ->having('total', '>', 1)
            ->pluck('keep_id', 'name');

        foreach ($duplicates as $name => $keepId) {
            DB::table('foods')->where('category_id', '!=', $keepId)
                ->whereIn('category_id', function ($query) use ($name): void {
                    $query->select('id')->from('categories')->where('name', $name);
                })
                ->update(['category_id' => $keepId]);

            DB::table('categories')->where('name', $name)->where('id', '!=', $keepId)->delete();
        }

        Schema::table('meals', function (Blueprint $table) {
            $table->dropIndex(['consumed_on']);
        });

        Schema::table('foods', function (Blueprint $table) {
            $table->dropIndex(['environmental_score']);
            $table->dropIndex(['scans_count']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
