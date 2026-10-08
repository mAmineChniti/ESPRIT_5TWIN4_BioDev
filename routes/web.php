<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AgriculturalRegionController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ConsumerDashboardController;
use App\Http\Controllers\ConsumerIntelligenceController;
use App\Http\Controllers\ConsumerSearchController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\FrontRegionController;
use App\Http\Controllers\GreenwashingReportController;
use App\Http\Controllers\JourneyController;
use App\Http\Controllers\JourneyStepController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicJourneyController;
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
Route::get('/agricultural-regions', [FrontRegionController::class, 'index'])->name('front.agricultural-regions.index');
Route::get('/agricultural-regions/{agriculturalRegion}', [FrontRegionController::class, 'show'])->name('front.agricultural-regions.show');

// ---------- AI greenwashing detection & product assistant (public reads) ----------
Route::get('/products/{food}/analysis', [ConsumerIntelligenceController::class, 'analyze'])
    ->name('products.analysis');
Route::get('/products/{food}/alternatives', [ConsumerIntelligenceController::class, 'alternatives'])
    ->name('products.alternatives');
Route::post('/products/{food}/ask', [ConsumerIntelligenceController::class, 'ask'])
    ->name('products.ask');
Route::get('/products/{food}/ask/suggestions', [ConsumerIntelligenceController::class, 'suggestions'])
    ->name('products.ask.suggestions');

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

    // The consumer's own space: AI detection, the assistant, recommendations
    // and one-click escalation of anything the detector flags.
    Route::get('/consumer/space', [ConsumerIntelligenceController::class, 'index'])->name('consumer.space');
    Route::get('/consumer/recommendations', [ConsumerIntelligenceController::class, 'recommendations'])
        ->name('consumer.recommendations');
    Route::post('/products/{food}/report-finding', [ConsumerIntelligenceController::class, 'reportFinding'])
        ->name('products.reportFinding');

    Route::get('/dashboard', function () {
        // Every dashboard is role-restricted, so the role picks the destination
        // rather than the request picking a fixed one.
        return redirect()->route(Auth::user()->dashboardRouteName());
    })->name('dashboard');

    // Each role dashboard is restricted to that role and shows its own data.
    Route::get('/admin', CatalogController::class)
        ->middleware(EnsureUserHasRole::class.':admin')
        ->name('admin.dashboard');

    Route::get('/admin/reports', [GreenwashingReportController::class, 'index'])
        ->middleware(EnsureUserHasRole::class.':admin')
        ->name('admin.reports');

    Route::resource('admin/users', AdminUserController::class)
        ->only(['index', 'edit', 'update', 'destroy'])
        ->names([
            'index' => 'admin.users',
            'edit' => 'admin.users.edit',
            'update' => 'admin.users.update',
            'destroy' => 'admin.users.destroy',
        ])
        ->middleware(EnsureUserHasRole::class.':admin');

    foreach ([
        'producer' => 'producer.dashboard',
        'processor' => 'processor.dashboard',
        'distributor' => 'distributor.dashboard',
    ] as $role => $name) {
        Route::get("/{$role}/dashboard", CatalogController::class)
            ->middleware(EnsureUserHasRole::class.':'.$role)
            ->name($name);

        if ($role === 'processor') {
            Route::get('processor/journeys/{journey}/qr', [JourneyController::class, 'qrCode'])
                ->name('processor.journeys.qr')
                ->middleware(EnsureUserHasRole::class.':processor');

            Route::resource('processor/journeys', JourneyController::class)
                ->only(['index', 'show'])
                ->parameters(['journeys' => 'journey'])
                ->names([
                    'index' => 'processor.journeys.index',
                    'show' => 'processor.journeys.show',
                ])
                ->middleware(EnsureUserHasRole::class.':processor');

            Route::resource('processor/journeys.steps', JourneyStepController::class)
                ->parameters(['journeys' => 'journey', 'steps' => 'step'])
                ->names([
                    'index' => 'processor.journeys.steps.index',
                    'create' => 'processor.journeys.steps.create',
                    'store' => 'processor.journeys.steps.store',
                    'show' => 'processor.journeys.steps.show',
                    'edit' => 'processor.journeys.steps.edit',
                    'update' => 'processor.journeys.steps.update',
                    'destroy' => 'processor.journeys.steps.destroy',
                ])
                ->middleware(EnsureUserHasRole::class.':processor');
        }
    }

    Route::get('/consumer/dashboard', ConsumerDashboardController::class)
        ->middleware(EnsureUserHasRole::class.':consumer')
        ->name('consumer.dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // "create" must be declared before "/foods/{food}" so it is not read as an
    // id. Everything that mutates a product lives in one ProAccess group placed
    // ahead of the read-only catalog routes for that reason.
    Route::middleware(ProAccess::class)->group(function () {
        Route::get('/foods/create', [FoodController::class, 'create'])->name('foods.create');
        Route::post('/foods', [FoodController::class, 'store'])->name('foods.store');
        Route::post('/foods/import', [FoodController::class, 'importCsv'])->name('foods.import');
        Route::get('/foods/{food}/edit', [FoodController::class, 'edit'])->name('foods.edit');
        Route::match(['put', 'patch'], '/foods/{food}', [FoodController::class, 'update'])->name('foods.update');
        Route::delete('/foods/{food}', [FoodController::class, 'destroy'])->name('foods.destroy');
        Route::post('/foods/{food}/transitions', [StageTransitionController::class, 'store'])->name('foods.transitions.store');
    });

    // The catalog is readable by any signed in user.
    Route::get('/foods', [FoodController::class, 'index'])->name('foods.index');
    Route::get('/foods/{food}', [FoodController::class, 'show'])->name('foods.show');
    Route::get('/foods/{food}/trace', [StageTransitionController::class, 'index'])->name('foods.transitions.index');

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

        Route::resource('admin/agricultural-regions', AgriculturalRegionController::class)
            ->only(['create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['agricultural-regions' => 'region'])
            ->names([
                'create' => 'back.agricultural-regions.create',
                'store' => 'back.agricultural-regions.store',
                'edit' => 'back.agricultural-regions.edit',
                'update' => 'back.agricultural-regions.update',
                'destroy' => 'back.agricultural-regions.destroy',
            ]);
    });

    // Both admins and producers browse farms and regions, but only an admin
    // approves, rejects, or changes the shape of the region list.
    Route::middleware(EnsureUserHasRole::class.':admin,producer')->group(function () {
        Route::resource('admin/agricultural-regions', AgriculturalRegionController::class)
            ->only(['index', 'show'])
            ->parameters(['agricultural-regions' => 'region'])
            ->names([
                'index' => 'back.agricultural-regions.index',
                'show' => 'back.agricultural-regions.show',
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

Route::get('/journeys/{code}', [PublicJourneyController::class, 'show'])
    ->name('journeys.public');

require __DIR__.'/auth.php';
require __DIR__.'/logistics.php';
