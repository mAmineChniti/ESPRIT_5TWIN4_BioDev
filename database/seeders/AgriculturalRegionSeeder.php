<?php

namespace Database\Seeders;

use App\Models\AgriculturalRegion;
use App\Models\Farm;
use Illuminate\Database\Seeder;

class AgriculturalRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            [
                'name' => 'Cap Bon - Nabeul',
                'code' => 'REG-CAPBON',
                'climate' => 'Méditerranéen doux',
                'soil_type' => 'Argilo-sableux fertile',
                'description' => 'Région réputée pour ses agrumes, tomates, piments et cultures maraîchères.',
                'farms' => [
                    [
                        'name' => 'Ferme Bio Agroméditerranée',
                        'producer_name' => 'Sami Ben Ali',
                        'address' => 'Route de Korba Km 5, Nabeul',
                        'surface_hectares' => 45.00,
                        'farming_type' => 'Biologique',
                        'phone' => '+216 72 100 200',
                        'description' => 'Spécialisée dans la production d’agrumes bio et d’huile d’olive de qualité supérieure.',
                    ],
                    [
                        'name' => 'Domaine Agrume Soltane',
                        'producer_name' => 'Karim Soltane',
                        'address' => 'Grombalia Centre',
                        'surface_hectares' => 80.50,
                        'farming_type' => 'Raisonné',
                        'phone' => '+216 72 300 400',
                        'description' => 'Vergers d’oranges maltaises et de clémentines produites en agriculture raisonnée.',
                    ],
                ],
            ],
            [
                'name' => 'Nord-Ouest - Vallée de Béja',
                'code' => 'REG-BEJA',
                'climate' => 'Subhumide',
                'soil_type' => 'Argilo-limoneux profond',
                'description' => 'Le grenier à blé du pays, réputé pour les céréales, légumineuses et l’élevage bovin.',
                'farms' => [
                    [
                        'name' => 'Ferme Céréalière Teboursouk',
                        'producer_name' => 'Moncef Jebali',
                        'address' => 'Teboursouk, Béja',
                        'surface_hectares' => 120.00,
                        'farming_type' => 'Traditionnel',
                        'phone' => '+216 78 500 600',
                        'description' => 'Production de blé dur, d’orge et d’olives de la variété Chemlali.',
                    ],
                    [
                        'name' => 'Verger des Collines Bio',
                        'producer_name' => 'Fatma Gharbi',
                        'address' => 'Aïn Draham Road, Testour',
                        'surface_hectares' => 35.00,
                        'farming_type' => 'Biologique',
                        'phone' => '+216 78 700 800',
                        'description' => 'Culture biologique de grenades, figues et Légumes de saison.',
                    ],
                ],
            ],
            [
                'name' => 'Sahel - Sousse & Monastir',
                'code' => 'REG-SAHEL',
                'climate' => 'Semi-aride méditerranéen',
                'soil_type' => 'Sableux-calcaire',
                'description' => 'Bassin traditionnel de la culture de l’olivier et de la pêche maritime.',
                'farms' => [
                    [
                        'name' => 'Domaine Oléicole du Sahel',
                        'producer_name' => 'Youssef Trabelsi',
                        'address' => 'Msaken, Sousse',
                        'surface_hectares' => 150.00,
                        'farming_type' => 'Raisonné',
                        'phone' => '+216 73 900 111',
                        'description' => 'Production et extraction d’huile d’olive vierge extra pressée à froid.',
                    ],
                ],
            ],
        ];

        $producerUser = \App\Models\User::where('role', 'producer')->first();
        $adminUser = \App\Models\User::where('role', 'admin')->first();

        foreach ($regions as $data) {
            $farms = $data['farms'];
            unset($data['farms']);

            $region = AgriculturalRegion::firstOrCreate(['code' => $data['code']], $data);

            foreach ($farms as $farmData) {
                $farmData['agricultural_region_id'] = $region->id;
                $farmData['status'] = 'validee';
                // Assign first farm of Sahel to admin, others to producer to test filtering
                if ($data['code'] === 'REG-SAHEL' && $adminUser) {
                    $farmData['user_id'] = $adminUser->id;
                } else {
                    $farmData['user_id'] = $producerUser?->id;
                }

                Farm::firstOrCreate(
                    ['name' => $farmData['name'], 'agricultural_region_id' => $region->id],
                    $farmData
                );
            }
        }

        // Add a pending sample farm created by producer for validation demonstration
        $bizerteRegion = AgriculturalRegion::firstOrCreate(
            ['code' => 'REG-BIZERTE'],
            [
                'name' => 'Bizerte',
                'climate' => 'Méditerranéen humide',
                'soil_type' => 'Sol argileux',
                'description' => 'Région agricole fertile au nord de la Tunisie.',
            ]
        );

        if ($producerUser) {
            Farm::firstOrCreate(
                ['name' => 'Ferme Ahmed', 'agricultural_region_id' => $bizerteRegion->id],
                [
                    'agricultural_region_id' => $bizerteRegion->id,
                    'user_id' => $producerUser->id,
                    'name' => 'Ferme Ahmed',
                    'producer_name' => 'Ahmed',
                    'soil_type' => 'Sol argileux',
                    'address' => 'Bizerte Centre',
                    'surface_hectares' => 50.00,
                    'farming_type' => 'Biologique',
                    'status' => 'en_attente',
                    'description' => 'Sol fertile adapté aux cultures agricoles',
                ]
            );
        }

        // Add a global admin region without producer farms to test producer filtering
        AgriculturalRegion::firstOrCreate(
            ['code' => 'REG-TOZEUR'],
            [
                'name' => 'Sud - Oasis de Tozeur',
                'climate' => 'Désertique chaud',
                'soil_type' => 'Sableux oasien',
                'description' => 'Bassin phœnicicole national spécialisé dans les dattes Deglet Nour de haute qualité.',
            ]
        );
    }
}

