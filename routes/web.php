<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AgriculturalRegionController;
use App\Http\Controllers\AnalysisDisputeController;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|-------------------------------------------------------------------------------
| Route naming
|-------------------------------------------------------------------------------
|
| One namespace per module, no audience prefix and no role prefix on a shared
| resource. The audience is expressed by the middleware group, not the name, so
| `farms.index` is the same route for an admin and a producer and
| `agricultural-regions.index` is the public read.
|
| The exceptions are the two dashboards that own a role: `admin.dashboard` and
| `processor.dashboard` and friends are named after the role that may reach
| them, because the URL is role-specific too. A resource is never named after
| a role when more than one role may read it — that is why the back office
| regions live at /regions as `regions.*` rather than under /admin.
|
*/

// ---------- Public storefront ----------
Route::get('/', function () {
    return view('front.home', CatalogController::catalogData());
})->name('home');

Route::get('/products', [ConsumerSearchController::class, 'index'])->name('products.index');
Route::get('/products/scan', [ConsumerSearchController::class, 'scan'])->name('products.scan');
Route::get('/products/{food}', [ConsumerSearchController::class, 'show'])->name('products.show');
Route::get('/greenwashing', function () {
    return view('front.greenwashing.index');
})->name('greenwashing');
Route::get('/agricultural-regions', [FrontRegionController::class, 'index'])->name('agricultural-regions.index');
Route::get('/agricultural-regions/{agriculturalRegion}', [FrontRegionController::class, 'show'])->name('agricultural-regions.show');

// ---------- AI greenwashing detection & product assistant (public reads) ----------
// These are the JSON endpoints behind the product page, so they stay open.
Route::get('/products/{food}/analysis', [ConsumerIntelligenceController::class, 'analyze'])
    ->name('products.analysis');
Route::get('/products/{food}/alternatives', [ConsumerIntelligenceController::class, 'alternatives'])
    ->name('products.alternatives');
// The assistant calls a paid provider, and this route is deliberately open to
// guests because the product page shows it before sign-in. Throttling bounds
// the cost without closing the feature.
Route::post('/products/{food}/ask', [ConsumerIntelligenceController::class, 'ask'])
    ->middleware('throttle:20,1')
    ->name('products.ask');
Route::get('/products/{food}/ask/suggestions', [ConsumerIntelligenceController::class, 'suggestions'])
    ->name('products.ask.suggestions');

Route::middleware(['auth'])->group(function () {
    // Challenging our own analysis is open to every signed in role, not just
    // consumers: a producer disputing a verdict on their own product is the
    // clearest case, and the report is about NutriTrace, not the product.
    Route::post('/products/{food}/analysis-disputes', [AnalysisDisputeController::class, 'store'])
        ->name('products.analysis-disputes.store');

    // Reviewing and reporting are consumer actions.
    // Deciding a report is an admin action.
    Route::middleware(EnsureUserHasRole::class.':consumer')->group(function () {
        Route::post('/products/{food}/reviews', [ReviewController::class, 'store'])->name('products.reviews.store');
        Route::delete('/products/{food}/reviews/{review}', [ReviewController::class, 'destroy'])->name('products.reviews.destroy');
        Route::post('/products/{food}/reports', [GreenwashingReportController::class, 'store'])->name('products.reports.store');

        // The consumer's own space: AI detection, the assistant, recommendations
        // and one-click escalation of anything the detector flags.
        Route::get('/consumer/space', [ConsumerIntelligenceController::class, 'index'])->name('consumer.space');
        Route::get('/consumer/recommendations', [ConsumerIntelligenceController::class, 'recommendations'])
            ->name('consumer.recommendations');
        Route::post('/products/{food}/report-finding', [ConsumerIntelligenceController::class, 'reportFinding'])
            ->name('products.reports.escalate');

        Route::get('/meals', [MealController::class, 'index'])->name('meals.index');
        Route::get('/meals/create', [MealController::class, 'create'])->name('meals.create');
        Route::post('/meals', [MealController::class, 'store'])->name('meals.store');
        Route::get('/meals/{meal}', [MealController::class, 'show'])->name('meals.show');
        Route::delete('/meals/{meal}', [MealController::class, 'destroy'])->name('meals.destroy');

        Route::get('/consumer/dashboard', ConsumerDashboardController::class)->name('consumer.dashboard');
    });

    // The dispatch target every auth flow lands on: the role, not the request,
    // picks the dashboard.
    Route::get('/dashboard', function () {
        return redirect()->route(Auth::user()->dashboardRouteName());
    })->name('dashboard');

    // ---------- Administration ----------
    Route::middleware(EnsureUserHasRole::class.':admin')->group(function () {
        Route::get('/admin/dashboard', CatalogController::class)->name('admin.dashboard');

        Route::get('/admin/reports', [GreenwashingReportController::class, 'index'])->name('admin.reports.index');
        Route::patch('/admin/reports/{report}', [GreenwashingReportController::class, 'update'])->name('admin.reports.update');

        Route::get('/admin/analysis-disputes', [AnalysisDisputeController::class, 'index'])
            ->name('admin.analysis-disputes.index');
        Route::patch('/admin/analysis-disputes/{analysisDispute}', [AnalysisDisputeController::class, 'update'])
            ->name('admin.analysis-disputes.update');

        Route::resource('admin/users', AdminUserController::class)
            ->only(['index', 'edit', 'update', 'destroy'])
            ->names([
                'index' => 'admin.users.index',
                'edit' => 'admin.users.edit',
                'update' => 'admin.users.update',
                'destroy' => 'admin.users.destroy',
            ]);

        // Farms & agricultural regions: an admin supervises every farm in the
        // country, a producer only the ones they registered.
        Route::get('/farms/requests', [FarmController::class, 'requests'])->name('farms.requests');
        Route::patch('/farms/{farm}/approve', [FarmController::class, 'approve'])->name('farms.approve');
        Route::patch('/farms/{farm}/reject', [FarmController::class, 'reject'])->name('farms.reject');

        Route::resource('regions', AgriculturalRegionController::class)
            ->only(['create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['regions' => 'region'])
            ->names([
                'create' => 'regions.create',
                'store' => 'regions.store',
                'edit' => 'regions.edit',
                'update' => 'regions.update',
                'destroy' => 'regions.destroy',
            ]);
    });

    // Both admins and producers browse farms and regions, so these are named
    // for the module rather than the role.
    Route::middleware(EnsureUserHasRole::class.':admin,producer')->group(function () {
        Route::resource('regions', AgriculturalRegionController::class)
            ->only(['index', 'show'])
            ->parameters(['regions' => 'region'])
            ->names([
                'index' => 'regions.index',
                'show' => 'regions.show',
            ]);

        Route::resource('farms', FarmController::class)->names([
            'index' => 'farms.index',
            'create' => 'farms.create',
            'store' => 'farms.store',
            'show' => 'farms.show',
            'edit' => 'farms.edit',
            'update' => 'farms.update',
            'destroy' => 'farms.destroy',
        ]);
    });

    // ---------- Supply chain role dashboards ----------
    // Each role's dashboard is gated on that role, so the route file stays the
    // single source of truth for who may reach which controller.
    foreach (['producer', 'processor', 'distributor'] as $role) {
        Route::get("/{$role}/dashboard", CatalogController::class)
            ->middleware(EnsureUserHasRole::class.':'.$role)
            ->name("{$role}.dashboard");
    }

    // ---------- Traceability ----------
    // The traceability chain is the processor's own work: the journeys and the
    // steps that make each one up. Declared in one role-gated group so the two
    // halves cannot drift apart.
    Route::middleware(EnsureUserHasRole::class.':processor')->group(function (): void {
        Route::get('processor/journeys/{journey}/qr', [JourneyController::class, 'qrCode'])
            ->name('processor.journeys.qr');

        Route::resource('processor/journeys', JourneyController::class)
            ->only(['index', 'show'])
            ->parameters(['journeys' => 'journey'])
            ->names([
                'index' => 'processor.journeys.index',
                'show' => 'processor.journeys.show',
            ]);

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
            ]);
    });

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
});

Route::get('/journeys/{code}', [PublicJourneyController::class, 'show'])
    ->name('journeys.public');

require __DIR__.'/auth.php';
require __DIR__.'/logistics.php';
