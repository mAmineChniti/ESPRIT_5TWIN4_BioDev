<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function consumer(): User
    {
        return User::factory()->create(['role' => 'consumer']);
    }

    public function test_a_consumer_can_log_a_meal(): void
    {
        $user = $this->consumer();
        $food = Food::factory()->create();

        $response = $this->actingAs($user)->post(route('meals.store'), [
            'name' => 'Power lunch',
            'type' => 'lunch',
            'consumed_on' => now()->toDateString(),
            'foods' => [$food->id],
            'quantities' => [$food->id => 150],
        ]);

        $response->assertRedirect(route('meals.index'));

        $this->assertDatabaseHas('meals', [
            'user_id' => $user->id,
            'name' => 'Power lunch',
            'type' => 'lunch',
        ]);

        $this->assertDatabaseHas('meal_food', [
            'meal_id' => Meal::firstOrFail()->id,
            'food_id' => $food->id,
            'quantity' => 150,
        ]);
    }

    public function test_a_meal_is_owned_by_the_user_who_logged_it(): void
    {
        $user = $this->consumer();
        $food = Food::factory()->create();

        $this->actingAs($user)->post(route('meals.store'), [
            'name' => 'Breakfast',
            'type' => 'breakfast',
            'foods' => [$food->id],
        ]);

        $this->assertSame($user->id, Meal::firstOrFail()->user_id);
    }

    public function test_a_meal_requires_at_least_one_product(): void
    {
        $this->actingAs($this->consumer())
            ->post(route('meals.store'), [
                'name' => 'Nothing',
                'type' => 'snack',
                'foods' => [],
            ])
            ->assertSessionHasErrors('foods');

        $this->assertDatabaseCount('meals', 0);
    }

    public function test_an_unknown_meal_type_is_rejected(): void
    {
        $food = Food::factory()->create();

        $this->actingAs($this->consumer())
            ->post(route('meals.store'), [
                'name' => 'Brunch',
                'type' => 'brunch',
                'foods' => [$food->id],
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_a_future_date_is_rejected(): void
    {
        $food = Food::factory()->create();

        $this->actingAs($this->consumer())
            ->post(route('meals.store'), [
                'name' => 'Tomorrow',
                'type' => 'lunch',
                'consumed_on' => now()->addWeek()->toDateString(),
                'foods' => [$food->id],
            ])
            ->assertSessionHasErrors('consumed_on');
    }

    public function test_a_consumer_only_sees_their_own_meals(): void
    {
        $mine = Meal::factory()->create(['user_id' => $this->consumer()->id, 'name' => 'My Meal']);
        $theirs = Meal::factory()->create(['name' => 'Their Meal']);

        $response = $this->actingAs($mine->user)->get(route('meals.index'));

        $response->assertOk();
        $response->assertSee('My Meal');
        $response->assertDontSee('Their Meal');
        $this->assertDatabaseHas('meals', ['id' => $theirs->id]);
    }

    public function test_a_consumer_cannot_view_or_delete_someone_elses_meal(): void
    {
        $theirs = Meal::factory()->create();
        $me = $this->consumer();

        $this->actingAs($me)->get(route('meals.show', $theirs))->assertForbidden();
        $this->actingAs($me)->delete(route('meals.destroy', $theirs))->assertForbidden();

        $this->assertDatabaseHas('meals', ['id' => $theirs->id]);
    }

    public function test_a_consumer_can_delete_their_own_meal(): void
    {
        $mine = Meal::factory()->create();

        $this->actingAs($mine->user)
            ->delete(route('meals.destroy', $mine))
            ->assertRedirect(route('meals.index'));

        $this->assertDatabaseMissing('meals', ['id' => $mine->id]);
    }

    public function test_calories_are_derived_from_the_products_and_quantities(): void
    {
        $food = Food::factory()->create(['calories' => 200]);
        $meal = Meal::factory()->create(['user_id' => $this->consumer()->id]);
        $meal->foods()->attach($food, ['quantity' => 150]);

        // 200 kcal per 100g, 150g consumed.
        $this->assertSame(300, $meal->fresh()->totalCalories());
    }
}
