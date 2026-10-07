<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use App\Enums\TransportMode;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference',
        'warehouse_id',
        'food_id',
        'user_id',
        'destination',
        'distance_km',
        'weight_kg',
        'transport_mode',
        'status',
        'shipped_on',
        'carbon_footprint_kg',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transport_mode' => TransportMode::class,
            'status' => ShipmentStatus::class,
            'shipped_on' => 'date',
            'distance_km' => 'decimal:1',
            'weight_kg' => 'decimal:2',
            'carbon_footprint_kg' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * The product carried by this shipment, when one is recorded.
     *
     * @return BelongsTo<Food, $this>
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    /**
     * The distributor who recorded the shipment.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
