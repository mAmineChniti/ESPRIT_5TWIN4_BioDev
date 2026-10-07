<?php

namespace Database\Seeders;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\Stage;
use App\Models\Category;
use App\Models\Certification;
use App\Models\Food;
use App\Models\GreenwashingReport;
use App\Models\Meal;
use App\Models\Review;
use App\Models\StageTransition;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $consumer = $this->user('test@example.com', 'Test User', 'consumer');
        $admin = $this->user('admin@nutritrace.com', 'Admin NutriTrace', 'admin');
        $producer = $this->user('producer@example.com', 'Producer User', 'producer');
        $processor = $this->user('processor@example.com', 'Processor User', 'processor');
        $distributor = $this->user('distributor@example.com', 'Distributor User', 'distributor');

        $categories = collect(['Fruit', 'Vegetable', 'Protein', 'Grain', 'Dairy', 'Seafood', 'Other'])
            ->map(fn (string $name): Category => Category::firstOrCreate(['name' => $name]));

        $certifications = collect([
            ['name' => 'Organic', 'issuer' => 'Ecocert'],
            ['name' => 'Local', 'issuer' => 'Regional Authority'],
            ['name' => 'Fair trade', 'issuer' => 'Fairtrade Labelling'],
        ])->map(fn (array $certification): Certification => Certification::firstOrCreate(
            ['name' => $certification['name']],
            [
                'issuer' => $certification['issuer'],
                'certificate_number' => strtoupper(fake()->bothify('??-####-????')),
                'valid_until' => now()->addYears(2)->format('Y-m-d'),
            ]
        ));

        $chain = [
            Stage::Produced->value => $producer,
            Stage::Processed->value => $processor,
            Stage::Distributed->value => $distributor,
        ];

        // Demo catalog content belongs here, not in the factories. Each product is
        // created against the category it actually belongs to, resolved from
        // the database rather than assumed.
        foreach ($this->demoCatalog() as $index => $product) {
            $food = Food::factory()->forCategory(
                Category::firstOrCreate(['name' => $product['category']])
            )->create([
                'name' => $product['name'],
                'producer_id' => $producer->id,
                'origin' => $product['origin'],
                'environmental_score' => $product['score'],
            ]);

            $food->certifications()->sync(
                $certifications->random(fake()->numberBetween(0, 3))
                    ->mapWithKeys(fn (Certification $certification): array => [
                        $certification->id => ['obtained_on' => now()->subYear()->format('Y-m-d')],
                    ])
                    ->all()
            );

            $this->recordChain($food, $chain, $index);
        }

        $foods = Food::all();

        $consumers = collect([$consumer, User::firstOrCreate(
            ['email' => 'consumer2@example.com'],
            ['name' => 'Second Consumer', 'password' => Hash::make('password'), 'role' => 'consumer'],
        )]);

        foreach ($foods as $food) {
            $consumers->random(fake()->numberBetween(0, 2))->each(
                fn (User $reviewer) => Review::firstOrCreate(
                    ['food_id' => $food->id, 'user_id' => $reviewer->id],
                    [
                        'rating' => fake()->numberBetween(2, 5),
                        'body' => fake()->randomElement([
                            'Matched the label exactly.',
                            'Arrived warmer than expected but the origin was as stated.',
                            'Packaging claims did not match what this page showed.',
                            'Good traceability, easy to follow where it came from.',
                        ]),
                    ]
                )
            );
        }

        // A couple of products carry an upheld report so the trust signal is visible.
        $foods->random(3)->each(function (Food $food) use ($consumers, $admin): void {
            GreenwashingReport::firstOrCreate(
                ['food_id' => $food->id, 'user_id' => $consumers->first()->id],
                [
                    'reason' => fake()->randomElement(ReportReason::cases()),
                    'details' => 'The eco grade did not match how this was produced.',
                    'status' => ReportStatus::Upheld,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                ]
            );
        });

        foreach (range(0, 9) as $index) {
            $meal = Meal::factory()->create([
                'user_id' => $consumer->id,
                'consumed_on' => now()->subDays($index)->format('Y-m-d'),
            ]);

            $meal->foods()->sync(
                $foods->random(fake()->numberBetween(1, 3))->pluck('id')
                    ->mapWithKeys(fn (int $foodId): array => [$foodId => ['quantity' => fake()->numberBetween(50, 300)]])
                    ->all()
            );
        }

        $this->call(LogisticsSeeder::class);
    }

    /**
     * @param  array<string, User>  $chain
     */
    private function recordChain(Food $food, array $chain, int $index): void
    {
        $previous = null;

        // Vary how far along the chain each product has travelled.
        $depth = fake()->numberBetween(1, 3);
        $steps = array_slice(array_keys($chain), 0, $depth);

        foreach ($steps as $offset => $stage) {
            // Each step happens after the previous one, so the newest step is
            // the closest to today.
            $daysAgo = ($depth - $offset) * 2 + ($index % 3);

            StageTransition::create([
                'food_id' => $food->id,
                'actor_id' => $chain[$stage]->id,
                'from_stage' => $previous,
                'to_stage' => $stage,
                'notes' => $stage === Stage::Produced->value ? 'Harvested and registered' : null,
                'occurred_at' => now()->subDays($daysAgo),
            ]);

            $previous = $stage;
        }
    }

    /**
     * Reference catalog used to populate a demo environment. Every product is
     * matched to a real category so the seeded data is internally consistent.
     *
     * @return list<array{name: string, category: string, origin: string, score: string}>
     */
    private function demoCatalog(): array
    {
        $catalog = [];

        $products = [
            'Apple' => 'Fruit', 'Banana' => 'Fruit', 'Blueberries' => 'Fruit', 'Orange' => 'Fruit',
            'Broccoli' => 'Vegetable', 'Carrot' => 'Vegetable', 'Spinach' => 'Vegetable',
            'Brown rice' => 'Grain', 'Oats' => 'Grain', 'Wholemeal bread' => 'Grain',
            'Grilled chicken' => 'Protein', 'Lentils' => 'Protein', 'Egg' => 'Protein',
            'Salmon' => 'Seafood', 'Plain yogurt' => 'Dairy', 'Cheese' => 'Dairy',
            'Olive oil' => 'Other', 'Green tea' => 'Other',
        ];

        $origins = ['France', 'Spain', 'Italy', 'Morocco', 'Greece', 'Tunisia'];
        $scores = ['A', 'B', 'C', 'D', 'E'];

        foreach ($products as $name => $category) {
            $catalog[] = [
                'name' => $name,
                'category' => $category,
                'origin' => $origins[array_rand($origins)],
                'score' => $scores[array_rand($scores)],
            ];
        }

        return $catalog;
    }

    private function user(string $email, string $name, string $role): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password'), 'role' => $role]
        );
    }
}
