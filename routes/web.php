<?php

use App\Http\Controllers\ProfileController;
use App\Models\Food;
use App\Models\Meal;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$catalogData = function () {
    if (! Schema::hasTable('foods') || ! Schema::hasTable('meals')) {
        return [
            'foodCount' => 0,
            'mealCount' => 0,
            'avgCalories' => 0,
            'latestFoods' => collect(),
            'latestMeals' => collect(),
            'topCategories' => collect(),
        ];
    }

    return [
        'foodCount' => Food::count(),
        'mealCount' => Meal::count(),
        'avgCalories' => (int) round(Food::avg('calories') ?? 0),
        'latestFoods' => Food::latest()->take(8)->get(),
        'latestMeals' => Meal::latest()->take(5)->get(),
        'topCategories' => Food::join('categories', 'foods.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category, COUNT(*) as total')
            ->groupBy('categories.name', 'categories.id')
            ->orderByDesc('total')
            ->take(4)
            ->get(),
    ];
};

Route::get('/', function () use ($catalogData) {
    return view('front.home', $catalogData());
})->name('front.home');

Route::get('/admin', function () use ($catalogData) {
    if (!Auth::check() || Auth::user()->role !== 'admin') {
        abort(403);
    }
    return view('back.dashboard', $catalogData());
})->middleware(['auth'])->name('dashboard');

Route::get('/producer/dashboard', function () use ($catalogData) {
    return view('back.dashboard', $catalogData());
})->middleware(['auth'])->name('producer.dashboard');

Route::get('/processor/dashboard', function () use ($catalogData) {
    return view('back.dashboard', $catalogData());
})->middleware(['auth'])->name('processor.dashboard');

Route::get('/distributor/dashboard', function () use ($catalogData) {
    return view('back.dashboard', $catalogData());
})->middleware(['auth'])->name('distributor.dashboard');

Route::get('/consumer/dashboard', function () use ($catalogData) {
    return view('back.dashboard', $catalogData());
})->middleware(['auth'])->name('consumer.dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::resource('foods', App\Http\Controllers\FoodController::class)->middleware(\App\Http\Middleware\ProAccess::class);
});

require __DIR__.'/auth.php';
