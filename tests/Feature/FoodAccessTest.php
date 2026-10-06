<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FoodAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function professionalRoleProvider(): array
    {
        return [
            'producer' => ['producer'],
            'processor' => ['processor'],
            'distributor' => ['distributor'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allRoleProvider(): array
    {
        return [
            'producer' => ['producer'],
            'processor' => ['processor'],
            'distributor' => ['distributor'],
            'consumer' => ['consumer'],
            'admin' => ['admin'],
        ];
    }

    #[DataProvider('allRoleProvider')]
    public function test_every_signed_in_role_can_read_the_catalog(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $food = Food::factory()->create();

        $this->actingAs($user)->get(route('foods.index'))->assertOk();
        $this->actingAs($user)->get(route('foods.show', $food))->assertOk();
    }

    #[DataProvider('professionalRoleProvider')]
    public function test_professionals_can_open_the_create_form(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get(route('foods.create'))
            ->assertOk();
    }

    public function test_consumers_cannot_open_the_create_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'consumer']))
            ->get(route('foods.create'))
            ->assertForbidden();
    }

    public function test_consumers_cannot_store_a_food(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'consumer']))
            ->post(route('foods.store'), [])
            ->assertForbidden();

        $this->assertDatabaseCount('foods', 0);
    }

    public function test_admins_cannot_register_products(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('foods.create'))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login_from_foods(): void
    {
        $this->get(route('foods.index'))->assertRedirect(route('login'));
    }

    #[DataProvider('allRoleProvider')]
    public function test_a_user_cannot_open_another_roles_dashboard(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        foreach (['admin', 'producer', 'processor', 'distributor', 'consumer'] as $other) {
            $response = $this->actingAs($user)->get(route("{$other}.dashboard"));

            $other === $role
                ? $response->assertOk()
                : $response->assertForbidden();
        }
    }

    public function test_only_admins_can_list_users(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'consumer']))
            ->get(route('admin.users'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.users'))
            ->assertOk();
    }

    public function test_meals_are_only_available_to_consumers(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'consumer']))
            ->get(route('meals.index'))
            ->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'producer']))
            ->get(route('meals.index'))
            ->assertForbidden();
    }
}
