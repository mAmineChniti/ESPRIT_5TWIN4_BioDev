<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgriculturalRegion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'climate',
        'soil_type',
        'description',
    ];

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }
}
