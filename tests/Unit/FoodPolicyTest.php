<?php

namespace Tests\Unit;

use App\Models\Food;
use App\Models\User;
use App\Policies\FoodPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The policy and ProAccess must not disagree.
 *
 * Middleware alone cannot catch a policy that grants more than the middleware
 * allows, because the request never reaches the policy: the grant is
 * unreachable dead code. These assert the policy's own answers.
 */
class FoodPolicyTest extends TestCase
{
    // Persisted models: the policy compares producer_id against the actor's
    // id, and an unsaved model has a null id, which would match anything.
    use RefreshDatabase;

    private FoodPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new FoodPolicy;
    }

    private function actor(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function professionalProvider(): array
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
    public static function nonProfessionalProvider(): array
    {
        return [
            'admin' => ['admin'],
            'consumer' => ['consumer'],
        ];
    }

    public function test_anyone_signed_in_may_browse(): void
    {
        foreach (['admin', 'producer', 'processor', 'distributor', 'consumer'] as $role) {
            $actor = $this->actor($role);

            $this->assertTrue($this->policy->viewAny($actor), $role);
            $this->assertTrue($this->policy->view($actor, Food::factory()->create()), $role);
        }
    }

    #[DataProvider('professionalProvider')]
    public function test_a_supply_chain_professional_may_register_a_product(string $role): void
    {
        $this->assertTrue($this->policy->create($this->actor($role)));
    }

    #[DataProvider('nonProfessionalProvider')]
    public function test_only_a_supply_chain_professional_may_register_a_product(string $role): void
    {
        $this->assertFalse($this->policy->create($this->actor($role)));
    }

    public function test_an_admin_may_not_register_a_product(): void
    {
        // ProAccess lets an admin reach /foods/create, but registering is the
        // professionals' first supply chain step, so the policy still refuses.
        $this->assertFalse($this->policy->create($this->actor('admin')));
    }

    public function test_the_owning_professional_may_update_and_delete(): void
    {
        $owner = $this->actor('producer');
        $food = Food::factory()->create(['producer_id' => $owner->id]);

        $this->assertTrue($this->policy->update($owner, $food));
        $this->assertTrue($this->policy->delete($owner, $food));
    }

    public function test_an_admin_may_update_and_delete_any_product(): void
    {
        // Admins moderate the catalogue, so they are not limited to the
        // products they registered. ProAccess admits them for the same reason.
        $food = Food::factory()->create(['producer_id' => $this->actor('producer')->id]);
        $admin = $this->actor('admin');

        $this->assertTrue($this->policy->update($admin, $food));
        $this->assertTrue($this->policy->delete($admin, $food));
    }

    public function test_a_consumer_may_not_update_or_delete(): void
    {
        $consumer = $this->actor('consumer');

        // Even a product that names them as the producer: ownership only counts
        // for a supply chain role.
        $food = Food::factory()->create(['producer_id' => $consumer->id]);

        $this->assertFalse($this->policy->update($consumer, $food));
        $this->assertFalse($this->policy->delete($consumer, $food));
    }

    public function test_another_professional_may_not_update_a_product_they_do_not_own(): void
    {
        $food = Food::factory()->create(['producer_id' => $this->actor('producer')->id]);

        $this->assertFalse($this->policy->update($this->actor('processor'), $food));
        $this->assertFalse($this->policy->delete($this->actor('processor'), $food));
    }
}
