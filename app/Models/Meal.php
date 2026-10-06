<?php

namespace App\Models;

use Database\Factories\MealFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Meal extends Model
{
    /** @use HasFactory<MealFactory> */
    use HasFactory;

    /**
     * The meal types the application supports.
     *
     * @var list<string>
     */
    public const TYPES = ['breakfast', 'lunch', 'dinner', 'snack', 'other'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'consumed_on',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consumed_on' => 'date',
        ];
    }

    /**
     * The user who logged this meal.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Products eaten in this meal.
     *
     * @return BelongsToMany<Food, $this>
     */
    public function foods(): BelongsToMany
    {
        return $this->belongsToMany(Food::class, 'meal_food', 'meal_id', 'food_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Total calories for the meal, summed from the products it contains.
     */
    public function totalCalories(): int
    {
        return (int) $this->foods->sum(
            fn (Food $food): float => $food->calories * ($this->quantityFor($food) / 100)
        );
    }

    /**
     * Quantity of a given product in this meal, in grams.
     */
    public function quantityFor(Food $food): float
    {
        return (float) ($this->foods->firstWhere('id', $food->id)?->pivot->quantity ?? 0);
    }
}
