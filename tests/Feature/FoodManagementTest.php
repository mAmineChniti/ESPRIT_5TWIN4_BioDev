<?php

namespace Tests\Feature;

use App\Enums\EnvironmentalScore;
use App\Models\Category;
use App\Models\Certification;
use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Organic Apple',
            'category_id' => Category::factory()->create()->id,
            'origin' => 'France',
            'environmental_score' => 'A',
            'calories' => 52,
            'protein' => 0.3,
            'carbs' => 13.8,
            'fat' => 0.2,
            'certifications' => [],
        ], $overrides);
    }

    private function producer(): User
    {
        return User::factory()->create(['role' => 'producer']);
    }

    public function test_index_only_lists_products_owned_by_the_signed_in_producer(): void
    {
        $user = $this->producer();
        $category = Category::factory()->create(['name' => 'Fruit']);

        $mine = Food::factory()->forCategory($category)->create([
            'producer_id' => $user->id,
            'name' => 'My Apple',
        ]);
        $theirs = Food::factory()->forCategory($category)->create(['name' => 'Someone Elses Pear']);

        $response = $this->actingAs($user)->get(route('foods.index'));

        $response->assertOk();
        $response->assertSee($mine->name);
        $response->assertDontSee($theirs->name);
    }

    public function test_admins_see_every_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $food = Food::factory()->create(['name' => 'Anything At All']);

        $this->actingAs($admin)->get(route('foods.index'))
            ->assertOk()
            ->assertSee($food->name);
    }

    public function test_create_form_lists_categories_and_certifications(): void
    {
        $category = Category::factory()->create(['name' => 'Grain']);
        $certification = Certification::factory()->create(['name' => 'Organic']);

        $response = $this->actingAs($this->producer())->get(route('foods.create'));

        $response->assertOk();
        $response->assertSee($category->name);
        $response->assertSee($certification->name);
    }

    public function test_a_producer_can_create_a_food_and_owns_it(): void
    {
        $user = $this->producer();

        $response = $this->actingAs($user)
            ->post(route('foods.store'), $this->validPayload());

        $response->assertRedirect(route('foods.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('foods', [
            'name' => 'Organic Apple',
            'producer_id' => $user->id,
            'environmental_score' => 'A',
        ]);
    }

    public function test_creating_a_food_records_the_first_supply_chain_step(): void
    {
        $response = $this->actingAs($this->producer())
            ->post(route('foods.store'), $this->validPayload());

        $food = Food::firstOrFail();

        $this->assertCount(1, $food->transitions);
        $this->assertSame('produced', $food->transitions->first()->to_stage->value);
        $response->assertRedirect(route('foods.index'));
    }

    public function test_certifications_are_attached_from_validated_ids(): void
    {
        // Named explicitly: a "Local" certification fails validation unless the
        // origin is Tunisie, and the factory picks that name at random.
        $certification = Certification::factory()->create(['name' => 'Organic']);

        $this->actingAs($this->producer())->post(
            route('foods.store'),
            $this->validPayload(['certifications' => [$certification->id]])
        );

        $this->assertDatabaseHas('certification_food', [
            'certification_id' => $certification->id,
            'food_id' => Food::firstOrFail()->id,
        ]);
    }

    public function test_free_text_certifications_are_rejected(): void
    {
        $response = $this->actingAs($this->producer())
            ->post(route('foods.store'), $this->validPayload(['certifications' => 'Bio, AOP']));

        $response->assertSessionHasErrors('certifications');
        $this->assertDatabaseCount('foods', 0);
    }

    public function test_creating_a_food_requires_an_existing_category(): void
    {
        $response = $this->actingAs($this->producer())
            ->post(route('foods.store'), $this->validPayload(['category_id' => 999999]));

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseCount('foods', 0);
    }

    public function test_creating_a_food_rejects_an_unknown_environmental_score(): void
    {
        $response = $this->actingAs($this->producer())
            ->post(route('foods.store'), $this->validPayload(['environmental_score' => 'F']));

        $response->assertSessionHasErrors('environmental_score');
        $this->assertDatabaseCount('foods', 0);
    }

    public function test_every_environmental_grade_is_accepted(): void
    {
        foreach (EnvironmentalScore::cases() as $grade) {
            $this->actingAs($this->producer())->post(
                route('foods.store'),
                $this->validPayload(['environmental_score' => $grade->value])
            )->assertSessionHasNoErrors();
        }

        $this->assertSame(count(EnvironmentalScore::cases()), Food::count());
    }

    public function test_an_owner_can_update_their_product(): void
    {
        $user = $this->producer();
        $food = Food::factory()->create(['producer_id' => $user->id, 'name' => 'Stale Name']);

        $response = $this->actingAs($user)
            ->put(route('foods.update', $food), $this->validPayload(['name' => 'Fresh Name']));

        $response->assertRedirect(route('foods.index'));
        $this->assertDatabaseHas('foods', ['id' => $food->id, 'name' => 'Fresh Name']);
    }

    public function test_a_producer_cannot_update_someone_elses_product(): void
    {
        $food = Food::factory()->create(['name' => 'Not Mine']);

        $this->actingAs($this->producer())
            ->put(route('foods.update', $food), $this->validPayload(['name' => 'Hijacked']))
            ->assertForbidden();

        $this->assertDatabaseHas('foods', ['id' => $food->id, 'name' => 'Not Mine']);
    }

    public function test_a_producer_cannot_delete_someone_elses_product(): void
    {
        $food = Food::factory()->create();

        $this->actingAs($this->producer())
            ->delete(route('foods.destroy', $food))
            ->assertForbidden();

        $this->assertDatabaseHas('foods', ['id' => $food->id]);
    }

    public function test_an_owner_can_delete_their_product(): void
    {
        $user = $this->producer();
        $food = Food::factory()->create(['producer_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('foods.destroy', $food))
            ->assertRedirect(route('foods.index'));

        $this->assertDatabaseMissing('foods', ['id' => $food->id]);
    }

    public function test_a_food_page_shows_its_traceability_details(): void
    {
        $food = Food::factory()->create([
            'origin' => 'Morocco',
            'environmental_score' => EnvironmentalScore::C,
        ]);
        $food->certifications()->attach(Certification::factory()->create(['name' => 'Fair trade']));

        $response = $this->actingAs($this->producer())->get(route('foods.show', $food));

        $response->assertOk();
        $response->assertSee('Morocco');
        $response->assertSee('Fair trade');
        $response->assertSee('Medium impact');
    }
}
