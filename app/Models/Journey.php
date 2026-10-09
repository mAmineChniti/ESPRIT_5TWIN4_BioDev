<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journey extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'qr_code',
        'environmental_score',
        'total_distance_km',
        'ai_summary',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'environmental_score' => 'decimal:2',
            'total_distance_km' => 'decimal:1',
            'generated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Food::class, 'product_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(JourneyStep::class)->orderBy('step_order');
    }

    /**
     * The first free position in this journey's workflow.
     *
     * Step orders are unique per journey, so prefilling this number is what
     * keeps the create form from failing validation on submit.
     */
    public function nextStepOrder(): int
    {
        return (int) ($this->steps->max('step_order') ?? 0) + 1;
    }
}
