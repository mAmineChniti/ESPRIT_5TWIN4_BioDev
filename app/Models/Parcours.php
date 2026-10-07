<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parcours extends Model
{
    use HasFactory;

    protected $fillable = [
        'produit_id',
        'code_qr',
        'score_environnemental',
        'distance_totale_km',
        'resume_ia',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'score_environnemental' => 'decimal:2',
            'distance_totale_km' => 'decimal:1',
            'generated_at' => 'datetime',
        ];
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Food::class, 'produit_id');
    }

    public function etapes(): HasMany
    {
        return $this->hasMany(EtapeParcours::class)->orderBy('ordre');
    }
}
