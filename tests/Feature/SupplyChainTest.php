<?php

namespace Tests\Feature;

use App\Enums\Stage;
use App\Models\Food;
use App\Models\StageTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplyChainTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create(['role' => 'producer']);
    }

    private function foodOwnedBy(User $user): Food
    {
        return Food::factory()->create(['producer_id' => $user->id]);
    }

    public function test_the_trace_page_shows_the_recorded_chain(): void
    {
        $user = $this->owner();
        $food = $this->foodOwnedBy($user);
        $food->transitions()->create([
            'actor_id' => $user->id,
            'from_stage' => Stage::Produced->value,
            'to_stage' => Stage::Processed->value,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('foods.transitions.index', $food));

        $response->assertOk();
        $response->assertSee('Produced');
        $response->assertSee('Processed');
    }

    public function test_an_owner_can_advance_a_product_to_the_next_stage(): void
    {
        $user = $this->owner();
        $food = $this->foodOwnedBy($user);
        StageTransition::create([
            'food_id' => $food->id,
            'actor_id' => $user->id,
            'from_stage' => null,
            'to_stage' => Stage::Produced->value,
            'occurred_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('foods.transitions.store', $food), [
                'to_stage' => Stage::Processed->value,
                'notes' => 'Washed and packed',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('stage_transitions', [
            'food_id' => $food->id,
            'from_stage' => Stage::Produced->value,
            'to_stage' => Stage::Processed->value,
        ]);
    }

    public function test_a_product_cannot_move_backwards(): void
    {
        $user = $this->owner();
        $food = $this->foodOwnedBy($user);
        StageTransition::create([
            'food_id' => $food->id,
            'actor_id' => $user->id,
            'from_stage' => Stage::Produced->value,
            'to_stage' => Stage::Processed->value,
            'occurred_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('foods.transitions.store', $food), [
                'to_stage' => Stage::Produced->value,
            ])
            ->assertSessionHasErrors('to_stage');

        $this->assertDatabaseCount('stage_transitions', 1);
    }

    public function test_a_stage_cannot_be_recorded_twice(): void
    {
        $user = $this->owner();
        $food = $this->foodOwnedBy($user);
        StageTransition::create([
            'food_id' => $food->id,
            'actor_id' => $user->id,
            'from_stage' => null,
            'to_stage' => Stage::Produced->value,
            'occurred_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('foods.transitions.store', $food), [
                'to_stage' => Stage::Produced->value,
            ])
            ->assertSessionHasErrors('to_stage');
    }

    public function test_an_unknown_stage_is_rejected(): void
    {
        $user = $this->owner();
        $food = $this->foodOwnedBy($user);

        $this->actingAs($user)
            ->post(route('foods.transitions.store', $food), ['to_stage' => 'retail'])
            ->assertSessionHasErrors('to_stage');

        $this->assertDatabaseCount('stage_transitions', 0);
    }

    public function test_someone_else_cannot_advance_a_product(): void
    {
        $food = $this->foodOwnedBy($this->owner());

        $this->actingAs(User::factory()->create(['role' => 'producer']))
            ->post(route('foods.transitions.store', $food), [
                'to_stage' => Stage::Processed->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('stage_transitions', 0);
    }

    public function test_consumers_cannot_record_supply_chain_steps(): void
    {
        $food = $this->foodOwnedBy($this->owner());

        $this->actingAs(User::factory()->create(['role' => 'consumer']))
            ->post(route('foods.transitions.store', $food), [
                'to_stage' => Stage::Processed->value,
            ])
            ->assertForbidden();
    }

    /**
     * A product with the first $stages steps of the chain recorded.
     */
    private function foodWithStages(int $stages): Food
    {
        $food = Food::factory()->create();
        $previous = null;

        foreach (array_slice(Stage::order(), 0, $stages) as $offset => $stage) {
            $food->transitions()->create([
                'actor_id' => User::factory()->create(['role' => 'producer'])->id,
                'from_stage' => $previous,
                'to_stage' => $stage,
                'occurred_at' => now()->subDays((3 - $offset) * 2),
            ]);
            $previous = $stage;
        }

        return $food;
    }

    // ---------- Which step is "current" ----------

    public function test_the_next_stage_follows_the_recorded_chain(): void
    {
        $food = $this->foodWithStages(1);

        $this->assertSame(Stage::Processed, $food->nextStage());
    }

    public function test_a_product_with_no_history_starts_at_produced(): void
    {
        $food = Food::factory()->create();

        $this->assertNull($food->currentStage());
        $this->assertSame(Stage::Produced, $food->nextStage());
    }

    public function test_a_completed_chain_offers_no_next_stage(): void
    {
        $food = $this->foodWithStages(count(Stage::cases()));

        $this->assertSame(Stage::Distributed, $food->currentStage());
        $this->assertNull($food->nextStage());
    }

    public function test_the_current_stage_is_the_last_recorded_step_even_within_one_second(): void
    {
        // occurred_at ties are resolved by insertion order, so "current" is
        // never ambiguous.
        $food = Food::factory()->create();
        $moment = now();

        foreach ([Stage::Produced, Stage::Processed, Stage::Distributed] as $stage) {
            StageTransition::factory()->create([
                'food_id' => $food->id,
                'to_stage' => $stage,
                'occurred_at' => $moment,
            ]);
        }

        $this->assertSame(Stage::Distributed, $food->fresh()->currentStage());
        $this->assertNull($food->fresh()->nextStage());
    }
}
