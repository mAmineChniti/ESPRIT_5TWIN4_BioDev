<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyStep extends Model
{
    use HasFactory;

    protected $table = 'journey_steps';

    protected $fillable = [
        'journey_id',
        'step_order',
        'type',
        'location',
        'step_date',
        'description',
        'farm_id',
        'shipment_id',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'step_date' => 'date',
        ];
    }

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }
}
