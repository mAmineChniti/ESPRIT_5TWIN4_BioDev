<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Upsert the international city reference used for route distances.
     */
    public function run(): void
    {
        foreach ($this->cities() as [$name, $countryCode, $region, $latitude, $longitude]) {
            $normalizedName = City::normalizeName($name);

            City::query()->updateOrCreate(
                [
                    'name_normalized' => $normalizedName,
                    'country_code' => $countryCode,
                ],
                [
                    'name' => $name,
                    'region' => $region,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ],
            );
        }
    }

    /**
     * Return the initial international reference cities and their coordinates.
     *
     * @return list<array{0: string, 1: string, 2: ?string, 3: float, 4: float}>
     */
    private function cities(): array
    {
        return [
            ['Tunis', 'TN', 'Tunis', 36.8065, 10.1815],
            ['Sousse', 'TN', 'Sousse', 35.8256, 10.6084],
            ['Sfax', 'TN', 'Sfax', 34.7406, 10.7603],
            ['Bizerte', 'TN', 'Bizerte', 37.2744, 9.8739],
            ['Gabès', 'TN', 'Gabès', 33.8815, 10.0982],
            ['Kairouan', 'TN', 'Kairouan', 35.6781, 10.0963],
            ['Monastir', 'TN', 'Monastir', 35.7643, 10.8113],
            ['Nabeul', 'TN', 'Nabeul', 36.4513, 10.7357],
            ['Djerba', 'TN', 'Médenine', 33.8076, 10.8451],
            ['Gafsa', 'TN', 'Gafsa', 34.4250, 8.7842],
            ['Tozeur', 'TN', 'Tozeur', 33.9197, 8.1335],
            ['Médenine', 'TN', 'Médenine', 33.3549, 10.5055],
            ['Tataouine', 'TN', 'Tataouine', 32.9297, 10.4518],
            ['Béja', 'TN', 'Béja', 36.7256, 9.1817],
            ['Jendouba', 'TN', 'Jendouba', 36.5011, 8.7802],
            ['Le Kef', 'TN', 'Le Kef', 36.1742, 8.7049],
            ['Siliana', 'TN', 'Siliana', 36.0849, 9.3708],
            ['Zaghouan', 'TN', 'Zaghouan', 36.4029, 10.1429],
            ['Mahdia', 'TN', 'Mahdia', 35.5047, 11.0622],
            ['Paris', 'FR', 'Île-de-France', 48.8566, 2.3522],
            ['Marseille', 'FR', 'Provence-Alpes-Côte d’Azur', 43.2965, 5.3698],
            ['Lyon', 'FR', 'Auvergne-Rhône-Alpes', 45.7640, 4.8357],
            ['Bordeaux', 'FR', 'Nouvelle-Aquitaine', 44.8378, -0.5792],
            ['Toulouse', 'FR', 'Occitanie', 43.6047, 1.4442],
            ['Nantes', 'FR', 'Pays de la Loire', 47.2184, -1.5536],
            ['Lille', 'FR', 'Hauts-de-France', 50.6292, 3.0573],
            ['Rome', 'IT', 'Lazio', 41.9028, 12.4964],
            ['Milan', 'IT', 'Lombardy', 45.4642, 9.1900],
            ['Naples', 'IT', 'Campania', 40.8518, 14.2681],
            ['Turin', 'IT', 'Piedmont', 45.0703, 7.6869],
            ['Palerme', 'IT', 'Sicily', 38.1157, 13.3615],
            ['Bari', 'IT', 'Apulia', 41.1171, 16.8719],
            ['Gênes', 'IT', 'Liguria', 44.4056, 8.9463],
            ['Madrid', 'ES', 'Community of Madrid', 40.4168, -3.7038],
            ['Barcelone', 'ES', 'Catalonia', 41.3874, 2.1686],
            ['Valence', 'ES', 'Valencian Community', 39.4699, -0.3763],
            ['Séville', 'ES', 'Andalusia', 37.3891, -5.9845],
            ['Bilbao', 'ES', 'Basque Country', 43.2630, -2.9350],
            ['Casablanca', 'MA', 'Casablanca-Settat', 33.5731, -7.5898],
            ['Rabat', 'MA', 'Rabat-Salé-Kénitra', 34.0209, -6.8416],
            ['Marrakech', 'MA', 'Marrakesh-Safi', 31.6295, -7.9811],
            ['Fès', 'MA', 'Fès-Meknès', 34.0181, -5.0078],
            ['Tanger', 'MA', 'Tanger-Tétouan-Al Hoceïma', 35.7595, -5.8340],
            ['Le Caire', 'EG', 'Cairo Governorate', 30.0444, 31.2357],
            ['Alexandrie', 'EG', 'Alexandria Governorate', 31.2001, 29.9187],
            ['Louxor', 'EG', 'Luxor Governorate', 25.6872, 32.6396],
            ['Istanbul', 'TR', 'Istanbul', 41.0082, 28.9784],
            ['Ankara', 'TR', 'Ankara', 39.9334, 32.8597],
            ['Izmir', 'TR', 'Izmir', 38.4237, 27.1428],
            ['Antalya', 'TR', 'Antalya', 36.8969, 30.7133],
            ['Berlin', 'DE', 'Berlin', 52.5200, 13.4050],
            ['Munich', 'DE', 'Bavaria', 48.1351, 11.5820],
            ['Hambourg', 'DE', 'Hamburg', 53.5511, 9.9937],
            ['Francfort', 'DE', 'Hesse', 50.1109, 8.6821],
            ['Londres', 'GB', 'England', 51.5072, -0.1276],
            ['Manchester', 'GB', 'England', 53.4808, -2.2426],
            ['New York', 'US', 'New York', 40.7128, -74.0060],
            ['Los Angeles', 'US', 'California', 34.0522, -118.2437],
            ['Chicago', 'US', 'Illinois', 41.8781, -87.6298],
            ['São Paulo', 'BR', 'São Paulo', -23.5505, -46.6333],
            ['Rio de Janeiro', 'BR', 'Rio de Janeiro', -22.9068, -43.1729],
            ['Mumbai', 'IN', 'Maharashtra', 19.0760, 72.8777],
            ['Delhi', 'IN', 'Delhi', 28.6139, 77.2090],
            ['Pékin', 'CN', 'Beijing', 39.9042, 116.4074],
            ['Shanghai', 'CN', 'Shanghai', 31.2304, 121.4737],
            ['Tokyo', 'JP', 'Tokyo', 35.6762, 139.6503],
            ['Montréal', 'CA', 'Quebec', 45.5019, -73.5674],
            ['Toronto', 'CA', 'Ontario', 43.6532, -79.3832],
            ['Sydney', 'AU', 'New South Wales', -33.8688, 151.2093],
        ];
    }
}
