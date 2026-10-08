<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A link must resolve for the person who can see it, and a control that mutates
 * state must be gated by the same rule the controller enforces.
 *
 * These assert on rendered output, not only on status codes: a page can return
 * 200 and still be offering buttons that would 403.
 */
class NavigationAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function roleProvider(): array
    {
        return [
            'admin' => ['admin'],
            'producer' => ['producer'],
            'processor' => ['processor'],
            'distributor' => ['distributor'],
            'consumer' => ['consumer'],
        ];
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * The href of the first link in the shared footer that points at a dashboard.
     */
    private function footerDashboardHref(string $html): ?string
    {
        preg_match('#<a[^>]*href="([^"]+)"[^>]*>\s*(?:Back Office|My Dashboard)\s*</a>#', $html, $match);

        return $match[1] ?? null;
    }

    // ---------- The shared footer ----------

    #[DataProvider('roleProvider')]
    public function test_the_footer_dashboard_link_resolves_for_every_role(string $role): void
    {
        $user = $this->user($role);

        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $href = $this->footerDashboardHref($html);
        $this->assertNotNull($href, 'The footer offers no dashboard link.');

        // The whole point: following the link the reader is actually shown
        // must not land on a 403 or a redirect loop.
        $this->actingAs($user)->get($href)->assertOk();
    }

    public function test_the_footer_offers_a_guest_the_login_page_instead_of_a_gated_dashboard(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertNull(
            $this->footerDashboardHref($html),
            'A guest was offered a dashboard link that bounces to login.'
        );
        $this->assertStringContainsString(route('login'), $html);
    }

    #[DataProvider('roleProvider')]
    public function test_the_header_dashboard_link_resolves_for_every_role(string $role): void
    {
        $user = $this->user($role);

        $html = $this->actingAs($user)->get(route('products.index'))->assertOk()->getContent();

        preg_match('#<a[^>]*href="([^"]+)"[^>]*>\s*My Dashboard\s*</a>#', $html, $match);
        $this->assertNotEmpty($match, 'The header offers no dashboard link.');

        $this->actingAs($user)->get($match[1])->assertOk();
    }

    #[DataProvider('roleProvider')]
    public function test_the_sidebar_dashboard_link_resolves_for_every_role(string $role): void
    {
        $user = $this->user($role);

        $dashboard = $user->dashboardUrl();
        $html = $this->actingAs($user)->get($dashboard)->assertOk()->getContent();

        $this->assertStringContainsString('href="'.$dashboard.'"', $html);
    }

    #[DataProvider('roleProvider')]
    public function test_the_dashboard_redirect_sends_each_role_to_its_own_dashboard(string $role): void
    {
        $user = $this->user($role);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect($user->dashboardUrl());
    }

    // ---------- Conditional controls on the catalog ----------

    public function test_a_consumer_is_not_offered_product_actions_they_cannot_perform(): void
    {
        $producer = $this->user('producer');
        $food = Food::factory()->create(['producer_id' => $producer->id]);

        $html = $this->actingAs($this->user('consumer'))->get(route('foods.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Add a product', $html);
        $this->assertStringNotContainsString(route('foods.edit', $food), $html);
        // The delete control's URL is a prefix of the show URL, so assert on
        // the confirmation dialog that only the delete control renders.
        $this->assertStringNotContainsString('Delete this product?', $html);
        $this->assertStringNotContainsString('_method', $html);

        // Reading the catalog is still allowed.
        $this->assertStringContainsString($food->name, $html);
    }

    public function test_a_consumer_is_not_offered_actions_on_the_product_page(): void
    {
        $producer = $this->user('producer');
        $food = Food::factory()->create(['producer_id' => $producer->id]);

        $html = $this->actingAs($this->user('consumer'))->get(route('foods.show', $food))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('foods.edit', $food), $html);
        $this->assertStringNotContainsString(route('foods.destroy', $food), $html);
    }

    public function test_an_admin_is_offered_the_moderation_actions_but_not_registration(): void
    {
        $producer = $this->user('producer');
        $food = Food::factory()->create(['producer_id' => $producer->id]);

        $html = $this->actingAs($this->user('admin'))->get(route('foods.index'))->assertOk()->getContent();

        // An admin moderates the catalogue, so Edit and Delete are offered on
        // another professional's product...
        $this->assertStringContainsString(route('foods.edit', $food), $html);
        $this->assertStringContainsString('Delete this product?', $html);

        // ...but registering a product is the professionals' first supply
        // chain step, so that control stays hidden even though the route is
        // reachable.
        $this->assertStringNotContainsString('Add a product', $html);
    }

    public function test_the_owner_is_offered_the_actions_they_can_perform(): void
    {
        $producer = $this->user('producer');
        $food = Food::factory()->create(['producer_id' => $producer->id]);

        $html = $this->actingAs($producer)->get(route('foods.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Add a product', $html);
        $this->assertStringContainsString(route('foods.edit', $food), $html);
        $this->assertStringContainsString('Delete this product?', $html);
    }

    public function test_a_producer_is_not_offered_actions_on_another_producers_product(): void
    {
        $theirs = Food::factory()->create(['producer_id' => $this->user('producer')->id]);

        $html = $this->actingAs($this->user('processor'))->get(route('foods.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('foods.edit', $theirs), $html);
        $this->assertStringNotContainsString('Delete this product?', $html);
    }

    public function test_a_consumer_is_not_offered_the_supply_chain_recording_form(): void
    {
        $producer = $this->user('producer');
        $food = Food::factory()->create(['producer_id' => $producer->id]);

        $html = $this->actingAs($this->user('consumer'))
            ->get(route('foods.transitions.index', $food))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(route('foods.transitions.store', $food), $html);
    }

    public function test_the_owner_is_offered_the_supply_chain_recording_form(): void
    {
        $producer = $this->user('producer');
        $food = Food::factory()->create(['producer_id' => $producer->id]);

        $html = $this->actingAs($producer)
            ->get(route('foods.transitions.index', $food))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('foods.transitions.store', $food), $html);
    }

    // ---------- Back office navigation ----------

    public function test_a_producer_is_not_offered_the_admin_only_region_actions(): void
    {
        $html = $this->actingAs($this->user('producer'))->get(route('back.agricultural-regions.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Add a region', $html);
        $this->assertStringNotContainsString(route('back.agricultural-regions.create'), $html);
    }

    public function test_an_admin_is_offered_the_admin_only_region_actions(): void
    {
        $html = $this->actingAs($this->user('admin'))->get(route('back.agricultural-regions.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Add a region', $html);
        $this->assertStringContainsString(route('back.agricultural-regions.create'), $html);
    }

    public function test_a_producer_is_not_offered_the_pending_request_queue(): void
    {
        $html = $this->actingAs($this->user('producer'))->get(route('back.farms.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('back.farms.requests'), $html);
    }

    public function test_the_back_office_never_offers_the_consumer_meal_area(): void
    {
        $html = $this->actingAs($this->user('producer'))->get(route('producer.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('meals.index'), $html);
    }

    #[DataProvider('roleProvider')]
    public function test_the_sidebar_renders_for_every_role(string $role): void
    {
        // The sidebar reads auth()->user() unconditionally, so this is the
        // check that the shared back-office layout is safe for every role.
        $this->actingAs($this->user($role))
            ->get(route('foods.index'))
            ->assertOk()
            ->assertSee('data-slot="sidebar"', false);
    }
}
