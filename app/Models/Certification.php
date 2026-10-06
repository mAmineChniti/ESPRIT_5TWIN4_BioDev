<?php

namespace App\Models;

use Database\Factories\CertificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Certification extends Model
{
    /** @use HasFactory<CertificationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'issuer',
        'certificate_number',
        'valid_until',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
        ];
    }

    /**
     * @return BelongsToMany<Food, $this>
     */
    public function foods(): BelongsToMany
    {
        return $this->belongsToMany(Food::class)
            ->withPivot('obtained_on')
            ->withTimestamps();
    }

    /**
     * A certification whose expiry date has passed is no longer trustworthy.
     */
    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }
}
