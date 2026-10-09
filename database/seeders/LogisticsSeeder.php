<?php

namespace Database\Seeders;

use App\Models\Food;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class LogisticsSeeder extends Seeder
{
    public function run(): void
    {
        $distributorId = User::where('email', 'distributor@example.com')->value('id');
        $foodIds = Food::pluck('id');

        Warehouse::factory(6)->create()->each(function (Warehouse $warehouse) use ($distributorId, $foodIds): void {
            Shipment::factory(fake()->numberBetween(3, 6))
                ->for($warehouse)
                ->state(fn (): array => [
                    'user_id' => $distributorId,
                    'food_id' => $foodIds->isNotEmpty() ? $foodIds->random() : null,
                ])
                ->create();
        });
    }
}
