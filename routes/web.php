<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ConsumerDashboardController;
use App\Http\Controllers\ConsumerSearchController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\GreenwashingReportController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StageTransitionController;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ProAccess;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('front.home', CatalogController::catalogData());
})->name('front.home');

// ---------- Public consumer space ----------
Route::get('/products', [ConsumerSearchController::class, 'index'])->name('products.index');
Route::get('/products/scan', [ConsumerSearchController::class, 'scan'])->name('products.scan');
Route::get('/products/{food}', [ConsumerSearchController::class, 'show'])->name('products.show');
Route::get('/greenwashing', function () {
    return view('front.greenwashing');
})->name('greenwashing');

Route::middleware(['auth'])->group(function () {
    // Reviewing and reporting are consumer actions.
    Route::post('/products/{food}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/products/{food}/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/products/{food}/reports', [GreenwashingReportController::class, 'store'])->name('reports.store');
    Route::patch('/reports/{report}', [GreenwashingReportController::class, 'update'])->name('reports.update');

    Route::get('/dashboard', function () {
        $role = Auth::user()?->role ?? 'consumer';

        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'producer' => redirect()->route('producer.dashboard'),
            'processor' => redirect()->route('processor.dashboard'),
            'distributor' => redirect()->route('distributor.dashboard'),
            default => redirect()->route('consumer.dashboard'),
        };
    })->name('dashboard');

    // Each role dashboard is restricted to that role and shows its own data.
    Route::get('/admin', CatalogController::class)
        ->middleware(EnsureUserHasRole::class.':admin')
        ->name('admin.dashboard');

    Route::middleware(EnsureUserHasRole::class.':admin')->group(function () {
        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
        Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::patch('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

        Route::get('/admin/reports', [GreenwashingReportController::class, 'index'])->name('admin.reports');
    });

    foreach ([
        'producer' => 'producer.dashboard',
        'processor' => 'processor.dashboard',
        'distributor' => 'distributor.dashboard',
    ] as $role => $name) {
        Route::get("/{$role}/dashboard", CatalogController::class)
            ->middleware(EnsureUserHasRole::class.':'.$role)
            ->name($name);
    }

    Route::get('/consumer/dashboard', ConsumerDashboardController::class)
        ->middleware(EnsureUserHasRole::class.':consumer')
        ->name('consumer.dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // "create" must be declared before "/foods/{food}" so it is not read as an id.
    Route::middleware(ProAccess::class)->group(function () {
        Route::get('/foods/create', [FoodController::class, 'create'])->name('foods.create');
        Route::post('/foods', [FoodController::class, 'store'])->name('foods.store');
    });

    // The catalog is readable by any signed in user.
    Route::get('/foods', [FoodController::class, 'index'])->name('foods.index');
    Route::get('/foods/{food}', [FoodController::class, 'show'])->name('foods.show');
    Route::get('/foods/{food}/trace', [StageTransitionController::class, 'index'])->name('foods.transitions.index');

    // Editing and moving products is limited to supply chain professionals.
    Route::middleware(ProAccess::class)->group(function () {
        Route::get('/foods/create', [FoodController::class, 'create'])->name('foods.create');
        Route::post('/foods', [FoodController::class, 'store'])->name('foods.store');
        Route::get('/foods/{food}/edit', [FoodController::class, 'edit'])->name('foods.edit');
        Route::match(['put', 'patch'], '/foods/{food}', [FoodController::class, 'update'])->name('foods.update');
        Route::delete('/foods/{food}', [FoodController::class, 'destroy'])->name('foods.destroy');
        Route::post('/foods/import', [FoodController::class, 'importCsv'])->name('foods.import');
        Route::post('/foods/{food}/transitions', [StageTransitionController::class, 'store'])->name('foods.transitions.store');
    });

    // Meal logging belongs to consumers.
    Route::middleware(EnsureUserHasRole::class.':consumer')->group(function () {
        Route::get('/meals', [MealController::class, 'index'])->name('meals.index');
        Route::get('/meals/create', [MealController::class, 'create'])->name('meals.create');
        Route::post('/meals', [MealController::class, 'store'])->name('meals.store');
        Route::get('/meals/{meal}', [MealController::class, 'show'])->name('meals.show');
        Route::delete('/meals/{meal}', [MealController::class, 'destroy'])->name('meals.destroy');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/logistics.php';
