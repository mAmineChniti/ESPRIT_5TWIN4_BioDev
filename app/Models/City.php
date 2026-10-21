<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class City extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_normalized',
        'country_code',
        'region',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Normalize accents, case, spacing, hyphens, and apostrophes for lookup.
     */
    public static function normalizeName(string $name): string
    {
        $normalizedName = Str::ascii(Str::lower(trim($name)));
        $normalizedName = preg_replace("/[-'’]+/u", ' ', $normalizedName) ?? $normalizedName;

        return preg_replace('/\s+/u', ' ', $normalizedName) ?? $normalizedName;
    }
}
