<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityDistance extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'city_a_id',
        'city_b_id',
        'distance_km',
    ];

    protected function casts(): array
    {
        return [
            'city_a_id' => 'integer',
            'city_b_id' => 'integer',
            'distance_km' => 'integer',
        ];
    }
}
