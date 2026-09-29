<?php

namespace App\Models;

use Database\Factories\MealFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    /** @use HasFactory<MealFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'consumed_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'consumed_on' => 'date',
        ];
    }
}
