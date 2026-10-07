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

    // ---------- Ownership and the policy/middleware agreement ----------

    public function test_an_admin_may_update_a_product_the_supply_chain_owns(): void
    {
        $food = Food::factory()->create(['producer_id' => $this->producer()->id, 'name' => 'Not Mine']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Admins moderate the catalogue rather than originating products, so
        // they may correct any product but may not register one.
        $this->actingAs($admin)->get(route('foods.edit', $food))->assertOk();

        $this->actingAs($admin)
            ->patch(route('foods.update', $food), $this->validPayload(['name' => 'Corrected By Admin']))
            ->assertRedirect(route('foods.index'));

        $this->assertDatabaseHas('foods', ['id' => $food->id, 'name' => 'Corrected By Admin']);
    }

    public function test_an_admin_may_delete_a_product_the_supply_chain_owns(): void
    {
        $food = Food::factory()->create(['producer_id' => $this->producer()->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete(route('foods.destroy', $food))
            ->assertRedirect(route('foods.index'));

        $this->assertDatabaseMissing('foods', ['id' => $food->id]);
    }

    public function test_a_producer_cannot_update_another_producers_product(): void
    {
        $food = Food::factory()->create(['producer_id' => $this->producer()->id, 'name' => 'Theirs']);

        $this->actingAs(User::factory()->create(['role' => 'processor']))
            ->patch(route('foods.update', $food), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseHas('foods', ['id' => $food->id, 'name' => 'Theirs']);
    }

    public function test_the_owning_producer_can_update_and_delete(): void
    {
        $producer = $this->producer();
        $food = Food::factory()->create(['producer_id' => $producer->id]);

        $this->actingAs($producer)
            ->patch(route('foods.update', $food), $this->validPayload(['name' => 'Renamed']))
            ->assertRedirect(route('foods.index'));

        $this->assertDatabaseHas('foods', ['id' => $food->id, 'name' => 'Renamed']);

        $this->actingAs($producer)->delete(route('foods.destroy', $food));
        $this->assertDatabaseMissing('foods', ['id' => $food->id]);
    }

    // ---------- Atomic writes ----------

    public function test_a_new_product_always_lands_with_its_chain_and_certifications(): void
    {
        // Named so the certification/origin coherence rule in FoodRequest
        // cannot reject this payload: this test is about atomicity.
        $certification = Certification::factory()->create(['name' => 'Organic']);

        $this->actingAs($this->producer())->post(
            route('foods.store'),
            $this->validPayload(['certifications' => [$certification->id]])
        )->assertRedirect(route('foods.index'));

        $food = Food::firstOrFail();

        $this->assertDatabaseHas('certification_food', [
            'certification_id' => $certification->id,
            'food_id' => $food->id,
        ]);
        $this->assertCount(1, $food->transitions()->get());
    }

    public function test_a_food_is_not_left_behind_when_its_certifications_are_invalid(): void
    {
        $this->actingAs($this->producer())->post(
            route('foods.store'),
            $this->validPayload(['certifications' => [999999]])
        )->assertSessionHasErrors('certifications.*');

        // The whole write is one unit: a rejected certification id must not
        // leave an orphaned product row.
        $this->assertDatabaseCount('foods', 0);
    }

    // ---------- Certification sync ----------

    public function test_clearing_the_certifications_is_possible(): void
    {
        $producer = $this->producer();
        $food = Food::factory()->create(['producer_id' => $producer->id]);
        $food->certifications()->attach(Certification::factory()->create());

        $this->assertCount(1, $food->certifications()->get());

        $this->actingAs($producer)
            ->patch(route('foods.update', $food), $this->validPayload(['certifications' => []]))
            ->assertRedirect(route('foods.index'));

        // Synced unconditionally, so submitting no certifications clears them.
        $this->assertCount(0, $food->fresh()->certifications()->get());
    }

    public function test_updating_replaces_the_certification_set_rather_than_adding_to_it(): void
    {
        $producer = $this->producer();
        $food = Food::factory()->create(['producer_id' => $producer->id]);

        // Named so the certification/origin coherence rule in FoodRequest
        // cannot fire: this test is about the pivot, not about geography.
        $old = Certification::factory()->create(['name' => 'Organic']);
        $new = Certification::factory()->create(['name' => 'Rainforest']);
        $food->certifications()->attach($old);

        $this->actingAs($producer)->patch(
            route('foods.update', $food),
            $this->validPayload(['certifications' => [$new->id]])
        );

        // collect() so the assertion holds whether pluck() hands back a
        // Collection or a plain array.
        $this->assertSame([$new->id], collect($food->fresh()->certifications()->pluck('certifications.id'))->all());
    }

    public function test_omitting_the_certifications_key_also_clears_them(): void
    {
        $producer = $this->producer();
        $food = Food::factory()->create(['producer_id' => $producer->id]);
        $food->certifications()->attach(Certification::factory()->create());

        $payload = $this->validPayload();
        unset($payload['certifications']);

        $this->actingAs($producer)->patch(route('foods.update', $food), $payload);

        // The sync must not be conditional on the key being present, or
        // "remove every certification" is impossible to express.
        $this->assertCount(0, $food->fresh()->certifications()->get());
    }
}
