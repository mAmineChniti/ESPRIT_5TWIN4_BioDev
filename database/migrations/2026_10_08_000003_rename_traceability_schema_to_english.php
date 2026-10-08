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
        Schema::table('journey_steps', function ($table): void {
            $table->renameColumn('journey_id', 'parcours_id');
            $table->renameColumn('step_order', 'ordre');
            $table->renameColumn('location', 'lieu');
            $table->renameColumn('step_date', 'date_etape');
            $table->renameColumn('farm_id', 'ferme_id');
            $table->renameColumn('shipment_id', 'expedition_id');
        });

        Schema::table('journeys', function ($table): void {
            $table->renameColumn('product_id', 'produit_id');
            $table->renameColumn('qr_code', 'code_qr');
            $table->renameColumn('environmental_score', 'score_environnemental');
            $table->renameColumn('total_distance_km', 'distance_totale_km');
            $table->renameColumn('ai_summary', 'resume_ia');
        });

        DB::table('journey_steps')->where('type', 'origin')->update(['type' => 'origine']);
        DB::table('journey_steps')->where('type', 'storage')->update(['type' => 'stockage']);
        DB::table('journey_steps')->where('type', 'sale')->update(['type' => 'vente']);

        Schema::rename('journey_steps', 'etapes_parcours');
        Schema::rename('journeys', 'parcours');
    }
};
