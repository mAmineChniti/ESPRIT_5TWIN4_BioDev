<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validShipment(Warehouse $warehouse, array $overrides = []): array
    {
        return [
            'reference' => 'SHP-000001',
            'warehouse_id' => $warehouse->id,
            'destination' => 'Marseille, France',
            'distance_km' => 1000,
            'weight_kg' => 10000,
            'transport_mode' => 'truck',
            'status' => 'preparing',
            'shipped_on' => '2026-10-05',
            ...$overrides,
        ];
    }

    public function test_distributor_can_create_a_shipment_with_a_computed_footprint(): void
    {
        $distributor = User::factory()->create(['role' => 'distributor']);
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($distributor)
            ->post(route('logistics.shipments.store'), $this->validShipment($warehouse))
            ->assertRedirect(route('logistics.shipments.index'));

        $shipment = Shipment::where('reference', 'SHP-000001')->firstOrFail();

        // 10 t x 1000 km x 0.100 = 1000 kg
        $this->assertEquals(1000.0, (float) $shipment->carbon_footprint_kg);
        $this->assertTrue($shipment->warehouse->is($warehouse));
    }

    public function test_shipment_validation_rejects_bad_input(): void
    {
        $distributor = User::factory()->create(['role' => 'distributor']);
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($distributor)
            ->post(route('logistics.shipments.store'), $this->validShipment($warehouse, [
                'distance_km' => -5,
                'transport_mode' => 'rocket',
            ]))
            ->assertSessionHasErrors(['distance_km', 'transport_mode']);
    }

    public function test_deleting_a_warehouse_deletes_its_shipments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shipment = Shipment::factory()->create();

        $this->actingAs($admin)
            ->delete(route('logistics.warehouses.destroy', $shipment->warehouse))
            ->assertRedirect(route('logistics.warehouses.index'));

        $this->assertModelMissing($shipment);
    }

    public function test_a_distributor_cannot_touch_another_distributors_shipment(): void
    {
        $owner = User::factory()->create(['role' => 'distributor']);
        $intruder = User::factory()->create(['role' => 'distributor']);
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($owner)
            ->post(route('logistics.shipments.store'), $this->validShipment($warehouse));

        $shipment = Shipment::where('reference', 'SHP-000001')->firstOrFail();

        // The route group admits every distributor, so the second layer is what
        // stops one distributor rewriting another's records.
        $this->actingAs($intruder)
            ->get(route('logistics.shipments.show', $shipment))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->get(route('logistics.shipments.edit', $shipment))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->patch(
                route('logistics.shipments.update', $shipment),
                $this->validShipment($warehouse, ['destination' => 'Hijacked, Spain'])
            )
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('logistics.shipments.destroy', $shipment))
            ->assertForbidden();

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'destination' => 'Marseille, France',
        ]);
    }

    public function test_an_admin_may_supervise_any_shipment(): void
    {
        $owner = User::factory()->create(['role' => 'distributor']);
        $admin = User::factory()->create(['role' => 'admin']);
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($owner)
            ->post(route('logistics.shipments.store'), $this->validShipment($warehouse));

        $shipment = Shipment::where('reference', 'SHP-000001')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('logistics.shipments.show', $shipment))
            ->assertOk();
    }

    public function test_a_distributor_can_still_manage_their_own_shipment(): void
    {
        $owner = User::factory()->create(['role' => 'distributor']);
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($owner)
            ->post(route('logistics.shipments.store'), $this->validShipment($warehouse));

        $shipment = Shipment::where('reference', 'SHP-000001')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('logistics.shipments.show', $shipment))
            ->assertOk();

        $this->actingAs($owner)
            ->delete(route('logistics.shipments.destroy', $shipment))
            ->assertRedirect(route('logistics.shipments.index'));

        $this->assertModelMissing($shipment);
    }

    public function test_consumers_cannot_open_the_logistics_area(): void
    {
        $consumer = User::factory()->create(['role' => 'consumer']);

        $this->actingAs($consumer)
            ->get(route('logistics.shipments.index'))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('logistics.warehouses.index'))->assertRedirect(route('login'));
    }
}
