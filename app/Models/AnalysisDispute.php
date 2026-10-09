<?php

namespace App\Models;

use App\Enums\AnalysisDisputeReason;
use App\Enums\AnalysisDisputeStatus;
use App\Enums\FindingCategory;
use Database\Factories\AnalysisDisputeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Someone challenging the AI detector's verdict on a product.
 *
 * Not a greenwashing report: this records a claim about NutriTrace's analysis,
 * so it never touches the product's trust score.
 */
class AnalysisDispute extends Model
{
    /** @use HasFactory<AnalysisDisputeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'food_id',
        'user_id',
        'reason',
        'finding_category',
        'comment',
        'status',
        'resolution_note',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => AnalysisDisputeReason::class,
            'status' => AnalysisDisputeStatus::class,
            'finding_category' => FindingCategory::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Food, $this>
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === AnalysisDisputeStatus::Pending;
    }

    /**
     * @param  Builder<AnalysisDispute>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', AnalysisDisputeStatus::Pending->value);
    }

    /**
     * Whether the challenge targets one specific finding or the verdict as a whole.
     */
    public function targetsAFinding(): bool
    {
        return $this->finding_category !== null;
    }
}
