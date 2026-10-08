<?php

namespace App\Models;

use App\Enums\FarmStatus;
use Database\Factories\FarmFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Farm extends Model
{
    /** @use HasFactory<FarmFactory> */
    use HasFactory;

    protected $fillable = [
        'agricultural_region_id',
        'user_id',
        'name',
        'producer_name',
        'address',
        'surface_hectares',
        'farming_type',
        'soil_type',
        'status',
        'rejection_reason',
        'phone',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FarmStatus::class,
            'surface_hectares' => 'decimal:2',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === FarmStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === FarmStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === FarmStatus::Rejected;
    }

    /**
     * Whether the details of this farm may still be corrected.
     *
     * A pending farm can be fixed while it waits, and an approved one while it
     * is in force. A rejected farm has been closed, so its record is kept as
     * submitted rather than edited.
     */
    public function canBeEdited(): bool
    {
        return ! $this->isRejected();
    }

    /**
     * Farms that have cleared administrative review.
     *
     * This is the only scope the public pages may use. Anything that is not
     * explicitly approved stays in the back office, which fails closed if a
     * row ever ends up with an unexpected status.
     *
     * @param  Builder<Farm>  $query
     * @return Builder<Farm>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', FarmStatus::Approved);
    }

    /**
     * @param  Builder<Farm>  $query
     * @return Builder<Farm>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', FarmStatus::Pending);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(AgriculturalRegion::class, 'agricultural_region_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
