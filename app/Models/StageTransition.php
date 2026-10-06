<?php

namespace App\Models;

use App\Enums\Stage;
use Database\Factories\StageTransitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StageTransition extends Model
{
    /** @use HasFactory<StageTransitionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'food_id',
        'actor_id',
        'from_stage',
        'to_stage',
        'notes',
        'occurred_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_stage' => Stage::class,
            'to_stage' => Stage::class,
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * The product that moved.
     *
     * @return BelongsTo<Food, $this>
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    /**
     * The user responsible for this step.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
