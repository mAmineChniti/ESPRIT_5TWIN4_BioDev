<?php

namespace App\Models;

use Database\Factories\FoodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    /** @use HasFactory<FoodFactory> */
    use HasFactory;

    protected $table = 'foods';

    protected $fillable = [
        'name',
        'category',
        'calories',
        'protein',
        'carbs',
        'fat',
    ];
}
