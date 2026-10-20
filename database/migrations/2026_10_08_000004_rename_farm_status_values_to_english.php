<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('farms')->where('status', 'en_attente')->update(['status' => 'pending']);
        DB::table('farms')->where('status', 'validee')->update(['status' => 'approved']);
        DB::table('farms')->where('status', 'refusee')->update(['status' => 'rejected']);
        DB::table('farms')->where('farming_type', 'Biologique')->update(['farming_type' => 'Organic']);
        DB::table('farms')->where('farming_type', 'Raisonné')->update(['farming_type' => 'Sustainable']);
        DB::table('farms')->where('farming_type', 'Traditionnel')->update(['farming_type' => 'Traditional']);
        DB::table('farms')->where('farming_type', 'Biodynamique')->update(['farming_type' => 'Biodynamic']);

        $this->rewriteDefaults('approved', 'Organic');
    }

    public function down(): void
    {
        // Intentionally a no-op: rolling back would resurrect the forbidden
        // French status/farming-type values that this migration removed.
    }

    /**
     * Move the column defaults onto the renamed values.
     *
     * Rewriting the rows is only half the rename: a farm inserted without an
     * explicit status still falls back to the column default, and `status` is
     * cast to an enum, so a leftover French default would hand the model a
     * backing value the enum does not understand. Both columns are restated in
     * full because `change()` drops every attribute it is not given again.
     */
    private function rewriteDefaults(string $status, string $farmingType): void
    {
        Schema::table('farms', function (Blueprint $table) use ($status, $farmingType) {
            $table->string('farming_type')->default($farmingType)->change();
            $table->string('status')->default($status)->change();
        });
    }
};
