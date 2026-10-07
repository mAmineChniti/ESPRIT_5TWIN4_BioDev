<?php

use App\Http\Controllers\AgriculturalRegionController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ConsumerDashboardController;
use App\Http\Controllers\ConsumerSearchController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\FrontRegionController;
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
Route::get('/regions-agricoles', [FrontRegionController::class, 'index'])->name('front.regions.index');
Route::get('/regions-agricoles/{agriculturalRegion}', [FrontRegionController::class, 'show'])->name('front.regions.show');

Route::middleware(['auth'])->group(function () {
    // Reviewing and reporting are consumer actions. Deciding a report is an
    // admin action.
    Route::middleware(EnsureUserHasRole::class.':consumer')->group(function () {
        Route::post('/products/{food}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
        Route::delete('/products/{food}/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::post('/products/{food}/reports', [GreenwashingReportController::class, 'store'])->name('reports.store');
    });

    Route::patch('/reports/{report}', [GreenwashingReportController::class, 'update'])
        ->middleware(EnsureUserHasRole::class.':admin')
        ->name('reports.update');

    Route::get('/dashboard', function () {
        // Every dashboard is role-restricted, so the role picks the destination
        // rather than the request picking a fixed one.
        return redirect()->route(Auth::user()->dashboardRouteName());
    })->name('dashboard');

    // Each role dashboard is restricted to that role and shows its own data.
    Route::get('/admin', CatalogController::class)
        ->middleware(EnsureUserHasRole::class.':admin')
        ->name('admin.dashboard');

    Route::get('/admin/users', function () {
        $users = User::orderBy('role')->orderBy('name')->get();

        return view('back.users', compact('users'));
    })->middleware(EnsureUserHasRole::class.':admin')->name('admin.users');

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
        Route::get('/foods/{food}/edit', [FoodController::class, 'edit'])->name('foods.edit');
        Route::match(['put', 'patch'], '/foods/{food}', [FoodController::class, 'update'])->name('foods.update');
        Route::delete('/foods/{food}', [FoodController::class, 'destroy'])->name('foods.destroy');
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

    // ---------- Farms & agricultural regions ----------
    // Declared before the resources below because `farms/{farm}` and
    // `regions/{region}` would otherwise swallow the literal `farms/requests`
    // and `regions/create` paths, turning a 403 into a 404. Route::resource
    // has the same trap for its own `create` route, which it orders for us.
    Route::middleware(EnsureUserHasRole::class.':admin')->group(function () {
        Route::get('/farms/requests', [FarmController::class, 'requests'])->name('back.farms.requests');
        Route::patch('/farms/{farm}/approve', [FarmController::class, 'approve'])->name('back.farms.approve');
        Route::patch('/farms/{farm}/reject', [FarmController::class, 'reject'])->name('back.farms.reject');

        Route::resource('regions', AgriculturalRegionController::class)
            ->only(['create', 'store', 'edit', 'update', 'destroy'])
            ->names([
                'create' => 'back.regions.create',
                'store' => 'back.regions.store',
                'edit' => 'back.regions.edit',
                'update' => 'back.regions.update',
                'destroy' => 'back.regions.destroy',
            ]);
    });

    // Both admins and producers browse farms and regions, but only an admin
    // approves, rejects, or changes the shape of the region list.
    Route::middleware(EnsureUserHasRole::class.':admin,producer')->group(function () {
        Route::resource('regions', AgriculturalRegionController::class)
            ->only(['index', 'show'])
            ->names([
                'index' => 'back.regions.index',
                'show' => 'back.regions.show',
            ]);

        Route::resource('farms', FarmController::class)->names([
            'index' => 'back.farms.index',
            'create' => 'back.farms.create',
            'store' => 'back.farms.store',
            'show' => 'back.farms.show',
            'edit' => 'back.farms.edit',
            'update' => 'back.farms.update',
            'destroy' => 'back.farms.destroy',
        ]);
    });
});

require __DIR__.'/auth.php';
