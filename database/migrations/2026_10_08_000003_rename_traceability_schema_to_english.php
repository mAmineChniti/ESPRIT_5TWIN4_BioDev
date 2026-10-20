<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('parcours', 'journeys');
        Schema::rename('etapes_parcours', 'journey_steps');

        Schema::table('journeys', function ($table): void {
            $table->renameColumn('produit_id', 'product_id');
            $table->renameColumn('code_qr', 'qr_code');
            $table->renameColumn('score_environnemental', 'environmental_score');
            $table->renameColumn('distance_totale_km', 'total_distance_km');
            $table->renameColumn('resume_ia', 'ai_summary');
        });

        Schema::table('journey_steps', function ($table): void {
            $table->renameColumn('parcours_id', 'journey_id');
            $table->renameColumn('ordre', 'step_order');
            $table->renameColumn('lieu', 'location');
            $table->renameColumn('date_etape', 'step_date');
            $table->renameColumn('ferme_id', 'farm_id');
            $table->renameColumn('expedition_id', 'shipment_id');
        });

        DB::table('journey_steps')->where('type', 'origine')->update(['type' => 'origin']);
        DB::table('journey_steps')->where('type', 'stockage')->update(['type' => 'storage']);
        DB::table('journey_steps')->where('type', 'vente')->update(['type' => 'sale']);
    }

    public function down(): void
    {
        // Intentionally a no-op: rolling back would resurrect the forbidden
        // French identifiers (parcours, etapes_parcours, …) that this
        // migration removed. Reverting to them is no longer supported.
    }
};
