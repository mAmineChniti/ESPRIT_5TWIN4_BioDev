<?php

namespace App\Models;

use App\Enums\EnvironmentalScore;
use App\Enums\ReportStatus;
use App\Enums\Stage;
use Database\Factories\FoodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Food extends Model
{
    /** @use HasFactory<FoodFactory> */
    use HasFactory;

    protected $table = 'foods';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'category_id',
        'producer_id',
        'origin',
        'environmental_score',
        'scans_count',
        'calories',
        'protein',
        'carbs',
        'fat',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'environmental_score' => EnvironmentalScore::class,
            'calories' => 'integer',
            'protein' => 'decimal:2',
            'carbs' => 'decimal:2',
            'fat' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The user who registered this product.
     *
     * @return BelongsTo<User, $this>
     */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'producer_id');
    }

    /**
     * Certifications backing this product's claims.
     *
     * @return BelongsToMany<Certification, $this>
     */
    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(Certification::class)
            ->withPivot('obtained_on')
            ->withTimestamps();
    }

    /**
     * Every hand-off this product made along the supply chain.
     *
     * @return HasMany<StageTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(StageTransition::class)->orderBy('occurred_at');
    }

    /**
     * Meals that included this product.
     *
     * @return BelongsToMany<Meal, $this>
     */
    public function meals(): BelongsToMany
    {
        return $this->belongsToMany(Meal::class, 'meal_food', 'food_id', 'meal_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * The stage the product has most recently reached.
     */
    public function currentStage(): ?Stage
    {
        return $this->transitions->last()?->to_stage;
    }

    /**
     * Consumer reviews left on this product.
     *
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Greenwashing reports filed against this product.
     *
     * @return HasMany<GreenwashingReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(GreenwashingReport::class);
    }

    /**
     * Mean rating across all reviews, or null when nobody has reviewed it.
     */
    public function averageRating(): ?float
    {
        $average = $this->relationLoaded('reviews')
            ? $this->reviews->avg('rating')
            : $this->reviews()->avg('rating');

        return $average === null ? null : round((float) $average, 1);
    }

    /**
     * Reports that a reviewer upheld, which is what actually counts against
     * the product's trustworthiness.
     */
    public function upheldReportCount(): int
    {
        if (array_key_exists('upheld_reports_count', $this->attributes)) {
            return (int) $this->upheld_reports_count;
        }

        return $this->reports()->where('status', ReportStatus::Upheld->value)->count();
    }

    /**
     * A 0-100 transparency score combining how complete the supply chain is,
     * whether certifications are on file and still valid, and whether any
     * greenwashing reports were upheld.
     *
     * This is what the consumer page presents instead of a marketing claim.
     */
    public function transparencyScore(): int
    {
        $score = 40;

        $recorded = $this->transitions->count();
        $score += min($recorded, count(Stage::order())) * 12;

        if ($this->certifications->isNotEmpty()) {
            $score += 15;

            if ($this->certifications->contains(fn ($certification): bool => ! $certification->isExpired())) {
                $score += 5;
            }
        }

        if ($this->environmental_score !== null) {
            $score += 10;
        }

        $score -= min($this->upheldReportCount() * 15, 40);

        return max(0, min(100, $score));
    }

    /**
     * Plain language guidance shown to consumers alongside the score.
     *
     * @return array{level: string, tone: string, message: string}
     */
    public function trustVerdict(): array
    {
        $score = $this->transparencyScore();
        $upheld = $this->upheldReportCount();

        if ($upheld > 0) {
            return [
                'level' => 'At risk',
                'tone' => 'high',
                'message' => $upheld === 1
                    ? 'One greenwashing report was upheld against this product.'
                    : "{$upheld} greenwashing reports were upheld against this product.",
            ];
        }

        if ($this->transitions->isEmpty()) {
            return [
                'level' => 'Unverified',
                'tone' => 'medium',
                'message' => 'No supply chain has been recorded for this product yet.',
            ];
        }

        if ($score >= 80) {
            return [
                'level' => 'Well traced',
                'tone' => 'low',
                'message' => 'The full chain is recorded and every certification is on file.',
            ];
        }

        return [
            'level' => 'Partly traced',
            'tone' => 'medium',
            'message' => 'Some steps are recorded but the picture is incomplete.',
        ];
    }

    /**
     * Shipments that carried this product.
     *
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
