<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Farm extends Model
{
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

    public function isPending(): bool
    {
        return $this->status === 'en_attente';
    }

    public function isApproved(): bool
    {
        return $this->status === 'validee' || empty($this->status);
    }

    public function isRejected(): bool
    {
        return $this->status === 'refusee';
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'validee')->orWhereNull('status');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'en_attente');
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
