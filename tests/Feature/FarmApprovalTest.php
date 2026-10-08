<?php

namespace Tests\Feature;

use App\Enums\FarmStatus;
use App\Models\AgriculturalRegion;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A farm is only public once an admin has approved it.
 *
 * These cover the publication rule end to end: what the public pages render,
 * and who may reach the back-office actions.
 */
class FarmApprovalTest extends TestCase
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

    /**
     * @return array<string, array{string}>
     */
    public static function nonAdminProvider(): array
    {
        return [
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

    private function farm(FarmStatus $status, User $owner, ?AgriculturalRegion $region = null): Farm
    {
        return Farm::factory()->create([
            'agricultural_region_id' => ($region ?? AgriculturalRegion::factory()->create())->id,
            'status' => $status,
            'user_id' => $owner->id,
            'name' => 'ZTFarmMark-'.Str::random(12),
        ]);
    }

    // ---------- Publication ----------

    public function test_a_pending_farm_is_not_published_on_the_public_region_page(): void
    {
        $region = AgriculturalRegion::factory()->create();
        $producer = $this->user('producer');
        $farm = $this->farm(FarmStatus::Pending, $producer, $region);

        $html = $this->get(route('front.agricultural-regions.show', $region))->assertOk()->getContent();

        $this->assertStringNotContainsString($farm->name, $html);
        $this->assertStringNotContainsString($farm->address, $html);
    }

    public function test_a_rejected_farm_is_not_published_on_the_public_region_page(): void
    {
        $region = AgriculturalRegion::factory()->create();
        $farm = $this->farm(FarmStatus::Rejected, $this->user('producer'), $region);

        $this->get(route('front.agricultural-regions.show', $region))
            ->assertOk()
            ->assertDontSee($farm->name);
    }

    public function test_an_approved_farm_is_published_on_the_public_region_page(): void
    {
        $region = AgriculturalRegion::factory()->create();
        $farm = $this->farm(FarmStatus::Approved, $this->user('producer'), $region);

        $this->get(route('front.agricultural-regions.show', $region))
            ->assertOk()
            ->assertSee($farm->name);
    }

    public function test_the_public_farm_count_only_counts_approved_farms(): void
    {
        $region = AgriculturalRegion::factory()->create(['name' => 'Counted Region']);
        $producer = $this->user('producer');

        $this->farm(FarmStatus::Approved, $producer, $region);
        $this->farm(FarmStatus::Pending, $producer, $region);
        $this->farm(FarmStatus::Rejected, $producer, $region);

        $html = $this->get(route('front.agricultural-regions.index'))->assertOk()->getContent();

        // All three farms sit in one region and exactly one is publishable, so
        // the single badge on the page must read 1. Counting the badges too
        // keeps the number from being read off a neighbouring region, and
        // pins it to this region rather than to the page in general.
        $this->assertSame(1, substr_count($html, 'farm(s)'));
        $this->assertStringContainsString('1 farm(s)', $html);
        $this->assertStringNotContainsString('3 farm(s)', $html);
    }

    public function test_the_public_region_page_reports_an_empty_region_rather_than_leaking(): void
    {
        $region = AgriculturalRegion::factory()->create();
        $this->farm(FarmStatus::Pending, $this->user('producer'));

        $this->get(route('front.agricultural-regions.show', $region))
            ->assertOk()
            ->assertSee('No farms recorded in this region yet.');
    }

    // ---------- Back office reach ----------

    #[DataProvider('nonAdminProvider')]
    public function test_only_an_admin_may_see_the_pending_request_queue(string $role): void
    {
        $this->actingAs($this->user($role))
            ->get(route('back.farms.requests'))
            ->assertForbidden();
    }

    #[DataProvider('nonAdminProvider')]
    public function test_only_an_admin_may_approve_a_farm(string $role): void
    {
        $farm = $this->farm(FarmStatus::Pending, $this->user('producer'));

        $this->actingAs($this->user($role))
            ->patch(route('back.farms.approve', $farm))
            ->assertForbidden();

        $this->assertSame(FarmStatus::Pending, $farm->fresh()->status);
    }

    #[DataProvider('nonAdminProvider')]
    public function test_only_an_admin_may_reject_a_farm(string $role): void
    {
        $farm = $this->farm(FarmStatus::Pending, $this->user('producer'));

        $this->actingAs($this->user($role))
            ->patch(route('back.farms.reject', $farm), ['rejection_reason' => 'nope'])
            ->assertForbidden();

        $this->assertSame(FarmStatus::Pending, $farm->fresh()->status);
    }

    #[DataProvider('nonAdminProvider')]
    public function test_only_an_admin_may_change_the_region_list(string $role): void
    {
        $user = $this->user($role);
        $region = AgriculturalRegion::factory()->create();

        $this->actingAs($user)->get(route('back.agricultural-regions.create'))->assertForbidden();
        $this->actingAs($user)->post(route('back.agricultural-regions.store'), ['name' => 'X', 'code' => 'X1'])->assertForbidden();
        $this->actingAs($user)->get(route('back.agricultural-regions.edit', $region))->assertForbidden();
        $this->actingAs($user)->patch(route('back.agricultural-regions.update', $region), ['name' => 'X', 'code' => 'X1'])->assertForbidden();
        $this->actingAs($user)->delete(route('back.agricultural-regions.destroy', $region))->assertForbidden();

        $this->assertDatabaseHas('agricultural_regions', ['id' => $region->id]);
    }

    #[DataProvider('roleProvider')]
    public function test_only_admin_and_producer_may_list_regions(string $role): void
    {
        $response = $this->actingAs($this->user($role))->get(route('back.agricultural-regions.index'));

        in_array($role, ['admin', 'producer'], true)
            ? $response->assertOk()
            : $response->assertForbidden();
    }

    public function test_a_producer_sees_only_their_own_farms(): void
    {
        $mine = $this->user('producer');
        $theirs = $this->user('producer');

        $ownFarm = $this->farm(FarmStatus::Pending, $mine);
        $otherFarm = $this->farm(FarmStatus::Pending, $theirs);

        $html = $this->actingAs($mine)->get(route('back.farms.index'))->assertOk()->getContent();

        $this->assertStringContainsString($ownFarm->name, $html);
        $this->assertStringNotContainsString($otherFarm->name, $html);
    }

    public function test_an_admin_sees_every_farm(): void
    {
        $first = $this->farm(FarmStatus::Pending, $this->user('producer'));
        $second = $this->farm(FarmStatus::Pending, $this->user('producer'));

        $html = $this->actingAs($this->user('admin'))->get(route('back.farms.index'))->assertOk()->getContent();

        $this->assertStringContainsString($first->name, $html);
        $this->assertStringContainsString($second->name, $html);
    }

    public function test_a_producer_cannot_open_or_edit_another_producers_farm(): void
    {
        $theirs = $this->farm(FarmStatus::Approved, $this->user('producer'));

        $this->actingAs($this->user('producer'))->get(route('back.farms.show', $theirs))->assertForbidden();
        $this->actingAs($this->user('producer'))->get(route('back.farms.edit', $theirs))->assertForbidden();
        $this->actingAs($this->user('producer'))->delete(route('back.farms.destroy', $theirs))->assertForbidden();

        $this->assertDatabaseHas('farms', ['id' => $theirs->id]);
    }

    // ---------- Workflow ----------

    public function test_a_producer_submission_waits_for_an_admin(): void
    {
        $producer = $this->user('producer');
        $region = AgriculturalRegion::factory()->create();

        $this->actingAs($producer)->post(route('back.farms.store'), [
            'agricultural_region_id' => $region->id,
            'name' => 'Waiting Farm',
            'address' => '1 Road',
            'surface_hectares' => 12,
            'farming_type' => 'Biologique',
        ])->assertRedirect(route('back.farms.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('farms', ['name' => 'Waiting Farm', 'status' => FarmStatus::Pending->value]);
    }

    public function test_an_admin_submission_is_immediately_in_force(): void
    {
        $region = AgriculturalRegion::factory()->create();

        $this->actingAs($this->user('admin'))->post(route('back.farms.store'), [
            'agricultural_region_id' => $region->id,
            'name' => 'Admin Farm',
            'address' => '2 Road',
            'surface_hectares' => 12,
            'farming_type' => 'Biologique',
        ])->assertRedirect(route('back.farms.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('farms', ['name' => 'Admin Farm', 'status' => FarmStatus::Approved->value]);
    }

    public function test_approving_a_farm_publishes_it(): void
    {
        $region = AgriculturalRegion::factory()->create();
        $farm = $this->farm(FarmStatus::Pending, $this->user('producer'));

        $this->actingAs($this->user('admin'))
            ->patch(route('back.farms.approve', $farm))
            ->assertSessionHasNoErrors();

        $this->assertSame(FarmStatus::Approved, $farm->fresh()->status);
        $this->get(route('front.agricultural-regions.show', $region))->assertSee($farm->name);
    }

    public function test_rejecting_a_farm_records_the_reason_and_unpublishes_it(): void
    {
        $region = AgriculturalRegion::factory()->create();
        $farm = $this->farm(FarmStatus::Pending, $this->user('producer'), $region);

        $this->actingAs($this->user('admin'))->patch(route('back.farms.reject', $farm), [
            'rejection_reason' => 'Adresse incorrecte',
        ])->assertSessionHasNoErrors();

        $farm->refresh();
        $this->assertSame(FarmStatus::Rejected, $farm->status);
        $this->assertSame('Adresse incorrecte', $farm->rejection_reason);

        // Assert on the address rather than the name: the rejection flash
        // message quotes the farm name, so asserting on it would pass or fail
        // for the wrong reason.
        $this->get(route('front.agricultural-regions.show', $region))
            ->assertOk()
            ->assertDontSee($farm->address);
    }

    public function test_rejecting_a_farm_requires_a_reason(): void
    {
        $farm = $this->farm(FarmStatus::Pending, $this->user('producer'));

        $this->actingAs($this->user('admin'))
            ->patch(route('back.farms.reject', $farm))
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame(FarmStatus::Pending, $farm->fresh()->status);
    }

    public function test_rejecting_a_farm_bounds_the_reason(): void
    {
        $farm = $this->farm(FarmStatus::Pending, $this->user('producer'));

        $this->actingAs($this->user('admin'))
            ->patch(route('back.farms.reject', $farm), ['rejection_reason' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('rejection_reason');
    }

    public function test_approving_clears_a_previous_rejection_reason(): void
    {
        $farm = $this->farm(FarmStatus::Rejected, $this->user('producer'));
        $farm->update(['rejection_reason' => 'Ancienne erreur']);

        $this->actingAs($this->user('admin'))->patch(route('back.farms.approve', $farm));

        $this->assertNull($farm->fresh()->rejection_reason);
    }

    public function test_the_status_filter_only_accepts_a_known_status(): void
    {
        $farm = $this->farm(FarmStatus::Approved, $this->user('producer'));

        $this->actingAs($this->user('admin'))
            ->get(route('back.farms.index', ['status' => 'not-a-status']))
            ->assertSessionHasErrors('status');

        $this->actingAs($this->user('admin'))
            ->get(route('back.farms.index', ['status' => FarmStatus::Pending->value]))
            ->assertOk()
            ->assertDontSee($farm->name);
    }

    public function test_a_rejected_farm_cannot_be_edited(): void
    {
        $farm = $this->farm(FarmStatus::Rejected, $this->user('producer'));

        $this->assertFalse($farm->canBeEdited());
        $this->assertTrue($this->farm(FarmStatus::Pending, $this->user('producer'))->canBeEdited());
    }
}
