<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapeParcours extends Model
{
    use HasFactory;

    protected $table = 'etapes_parcours';

    protected $fillable = [
        'parcours_id',
        'ordre',
        'type',
        'lieu',
        'date_etape',
        'description',
        'ferme_id',
        'expedition_id',
    ];

    protected function casts(): array
    {
        return [
            'ordre' => 'integer',
            'date_etape' => 'date',
        ];
    }

    public function parcours(): BelongsTo
    {
        return $this->belongsTo(Parcours::class);
    }

}
