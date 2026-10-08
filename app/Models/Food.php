<?php

namespace App\Models;

use App\Enums\EnvironmentalScore;
use App\Enums\ReportStatus;
use App\Enums\Stage;
use App\Enums\VerdictTone;
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
     * Ordered by insertion order rather than by occurred_at: two steps recorded in
     * the same second have no reliable time order, so id is what makes
     * "the latest step" unambiguous and agree with CatalogController::pendingByStage().
     *
     * @return HasMany<StageTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(StageTransition::class)->orderBy('id');
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
     *
     * Reads the loaded relation when there is one, so a page that has already
     * eager-loaded the chain does not issue a second query.
     */
    public function currentStage(): ?Stage
    {
        $transitions = $this->relationLoaded('transitions')
            ? $this->transitions
            : $this->transitions()->get();

        return $transitions->last()?->to_stage;
    }

    /**
     * The next stage the product may legitimately move to, or null once the
     * chain is complete.
     *
     * The chain only ever moves forward, so this is simply the stage after the
     * current one.
     */
    public function nextStage(): ?Stage
    {
        return Stage::next($this->currentStage());
    }

    /**
     * How many hand-offs have been recorded.
     */
    public function recordedStageCount(): int
    {
        return $this->relationLoaded('transitions')
            ? $this->transitions->count()
            : $this->transitions()->count();
    }

    /**
     * Whether every stage of the chain has been recorded. A chain that skipped
     * a step does not count, however many rows it has.
     */
    public function hasFullChain(): bool
    {
        $recorded = $this->relationLoaded('transitions')
            ? $this->transitions->pluck('to_stage')
            : $this->transitions()->pluck('to_stage');

        return Stage::isComplete($recorded->filter()->map->value->all());
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
     *
     * Prefers an aggregate already loaded by withAvg() so a listing sorted by
     * rating does not then run one query per product.
     */
    public function averageRating(): ?float
    {
        if (array_key_exists('reviews_avg_rating', $this->attributes)) {
            $precomputed = $this->reviews_avg_rating;

            return $precomputed === null ? null : round((float) $precomputed, 1);
        }

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

        $recorded = $this->recordedStageCount();
        $score += min($recorded, Stage::total()) * 12;

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
     * The level is decided by how much of the chain is actually recorded, not
     * by the score alone — a product with one of three stages can score above
     * the "well traced" threshold on certifications alone, and telling a
     * consumer its full chain is on file when it is not is precisely the
     * greenwashing this product exists to catch.
     *
     * @return array{level: string, tone: VerdictTone, message: string}
     */
    public function trustVerdict(): array
    {
        $upheld = $this->upheldReportCount();

        if ($upheld > 0) {
            return [
                'level' => 'At risk',
                'tone' => VerdictTone::High,
                'message' => $upheld === 1
                    ? 'One greenwashing report was upheld against this product.'
                    : "{$upheld} greenwashing reports were upheld against this product.",
            ];
        }

        if ($this->recordedStageCount() === 0) {
            return [
                'level' => 'Unverified',
                'tone' => VerdictTone::Medium,
                'message' => 'No supply chain has been recorded for this product yet.',
            ];
        }

        if (! $this->hasFullChain()) {
            $current = $this->currentStage()?->label() ?? 'an earlier stage';

            return [
                'level' => 'Partly traced',
                'tone' => VerdictTone::Medium,
                'message' => "Recorded up to {$current}. The later steps of the chain have not been recorded yet.",
            ];
        }

        if ($this->transparencyScore() >= 80) {
            return [
                'level' => 'Well traced',
                'tone' => VerdictTone::Low,
                'message' => $this->certifications->isNotEmpty()
                    ? 'The full chain is recorded and every certification is on file.'
                    : 'The full chain is recorded, but no certification backs it up.',
            ];
        }

        return [
            'level' => 'Partly traced',
            'tone' => VerdictTone::Medium,
            'message' => 'The full chain is recorded, but certifications or other evidence are missing.',
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
