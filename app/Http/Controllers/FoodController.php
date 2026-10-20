<?php

namespace App\Http\Controllers;

use App\Enums\EnvironmentalScore;
use App\Enums\Stage;
use App\Http\Requests\FoodRequest;
use App\Models\Category;
use App\Models\Certification;
use App\Models\Food;
use App\Models\StageTransition;
use App\Services\Ai\ProductAutoFillService;
use App\Services\Nutrition\NutriScoreCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FoodController extends Controller
{
    /**
     * Display a listing of the resource. Consumers see the whole catalog;
     * professionals see what they registered.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $this->authorize('viewAny', Food::class);
        $foods = Food::query()
            ->with(['category', 'producer'])
            ->when($user->isProfessional(), fn ($q) => $q->where('producer_id', $user->id))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->input('search')}%"))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('id', $request->input('category'))))
            ->when($request->filled('origin'), fn ($q) => $q->where('origin', 'like', "%{$request->input('origin')}%"))
            ->latest()
            ->paginate(10);

        return view('back.foods.index', [
            'foods' => $foods,
            'canManage' => $user->can('create', Food::class),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Food::class);

        return view('back.foods.create', [
            'categories' => Category::orderBy('name')->get(),
            'certifications' => Certification::orderBy('name')->get(),
            'nutriCalculator' => app(NutriScoreCalculator::class),
            'aiService' => app(ProductAutoFillService::class),
        ]);
    }

    public function store(FoodRequest $request): RedirectResponse
    {
        $this->authorize('create', Food::class);
        $food = DB::transaction(function () use ($request): Food {
            $food = Food::create([
                ...$request->foodPayload(),
                'producer_id' => $request->user()->id,
            ]);
            $food->certifications()->sync($this->certificationSyncPayload($request));
            StageTransition::create([
                'food_id' => $food->id,
                'actor_id' => $request->user()->id,
                'from_stage' => null,
                'to_stage' => Stage::Produced->value,
                'notes' => 'Product registered',
                'occurred_at' => now(),
            ]);

            return $food;
        });

        return redirect()->route('foods.index')->with('success', __('Product added successfully.'));
    }

    public function show(Food $food): View
    {
        $this->authorize('view', $food);
        $food->load(['category', 'producer', 'transitions.actor', 'certifications']);

        return view('back.foods.show', compact('food'));
    }

    public function edit(Food $food): View
    {
        $this->authorize('update', $food);

        return view('back.foods.edit', [
            'food' => $food,
            'categories' => Category::orderBy('name')->get(),
            'certifications' => Certification::orderBy('name')->get(),
            'selectedCertifications' => $food->certifications->pluck('id')->all(),
            'nutriCalculator' => app(NutriScoreCalculator::class),
            'aiService' => app(ProductAutoFillService::class),
        ]);
    }

    public function update(FoodRequest $request, Food $food): RedirectResponse
    {
        $this->authorize('update', $food);
        DB::transaction(function () use ($request, $food) {
            $food->update($request->foodPayload());
            $food->certifications()->sync($this->certificationSyncPayload($request));
        });

        return redirect()->route('foods.index')->with('success', __('Product updated successfully.'));
    }

    public function destroy(Food $food): RedirectResponse
    {
        $this->authorize('delete', $food);
        $food->delete();

        return redirect()->route('foods.index')->with('success', __('Product deleted.'));
    }

    /**
     * Import products from a CSV file.
     */
    public function importCsv(Request $request): RedirectResponse
    {
        $this->authorize('create', Food::class);
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);
        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'Unable to read the file.');
        }
        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return back()->with('error', 'The CSV file is empty.');
        }
        $header = array_map(fn ($c) => strtolower(trim($c)), $header);
        $imported = 0;
        $errors = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                $errors[] = 'Skipped a row with '.count($row).' columns, expected '.count($header).'.';

                continue;
            }
            $data = array_combine($header, $row);
            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                $errors[] = 'Skipped a row with no product name.';

                continue;
            }
            $score = strtoupper(trim((string) ($data['environmental_score'] ?? '')));
            if ($score !== '' && EnvironmentalScore::tryFrom($score) === null) {
                $errors[] = "{$name}: \"{$score}\" is not a valid eco grade (expected A-E).";

                continue;
            }
            try {
                DB::transaction(function () use ($request, $data, $name, $score) {
                    $category = Category::firstOrCreate(['name' => trim((string) ($data['category'] ?? '')) ?: 'Other']);
                    $food = Food::create([
                        'name' => $name,
                        'category_id' => $category->id,
                        'origin' => trim((string) ($data['origin'] ?? '')),
                        'environmental_score' => $score ?: null,
                        'calories' => (int) ($data['calories'] ?? 0),
                        'protein' => (float) ($data['protein'] ?? 0),
                        'carbs' => (float) ($data['carbs'] ?? 0),
                        'fat' => (float) ($data['fat'] ?? 0),
                        'producer_id' => $request->user()->id,
                    ]);
                    StageTransition::create([
                        'food_id' => $food->id,
                        'actor_id' => $request->user()->id,
                        'from_stage' => null,
                        'to_stage' => Stage::Produced->value,
                        'notes' => 'Imported via CSV',
                        'occurred_at' => now(),
                    ]);
                });

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "{$name}: {$e->getMessage()}";
            }
        }

        fclose($handle);

        $message = "{$imported} product(s) imported successfully.";

        if ($errors !== []) {
            $message .= ' '.count($errors).' row(s) skipped: '.collect($errors)->implode(' ');
        }

        return redirect()->route('foods.index')->with('success', $message);
    }

    /** AI suggestion */
    public function aiSuggest(Request $request): JsonResponse
    {
        $this->authorize('create', Food::class);
        $validated = $request->validate(['name' => 'required|string', 'origin' => 'nullable|string']);
        $service = app(ProductAutoFillService::class);
        $suggestion = $service->suggest($validated['name'], $validated['origin'] ?? null);

        return response()->json($suggestion);
    }

    public function liveNutriScore(Request $request): JsonResponse
    {
        return $this->nutriScore($request);
    }

    /** Nutri‑Score live calculation */
    public function nutriScore(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Food::class);
        $data = $request->validate([
            'calories' => 'required|numeric',
            'protein' => 'required|numeric',
            'carbs' => 'required|numeric',
            'fat' => 'required|numeric',
            'category' => 'nullable|string',
            'origin' => 'nullable|string',
            'environmental_score' => 'nullable|string',
        ]);

        $result = NutriScoreCalculator::calculateFromValues(
            calories: (int) $data['calories'],
            protein: (float) $data['protein'],
            carbs: (float) $data['carbs'],
            fat: (float) $data['fat'],
            categoryName: (string) ($data['category'] ?? 'Other'),
            ecoScore: (string) ($data['environmental_score'] ?? 'C'),
            origin: (string) ($data['origin'] ?? 'Unknown'),
        );

        return response()->json($result);
    }

    /** Comparison pages */
    public function compare(): View
    {
        $this->authorize('viewAny', Food::class);
        $foods = Food::with(['category', 'producer'])->orderBy('name')->get();

        return view('back.foods.compare', compact('foods'));
    }

    public function compareResult(Request $request): View
    {
        $this->authorize('viewAny', Food::class);

        // Filter out empty options before validating
        $rawIds = array_values(array_filter((array) $request->input('food_ids', []), fn ($id) => filled($id)));
        $request->merge(['food_ids' => $rawIds]);

        $validated = $request->validate([
            'food_ids' => ['required', 'array', 'min:2', 'max:3'],
            'food_ids.*' => ['required', 'integer', 'distinct', 'exists:foods,id'],
        ], [
            'food_ids.min' => __('Please select at least 2 distinct products to compare.'),
            'food_ids.*.distinct' => __('Please select different products to compare.'),
        ]);

        $products = Food::with(['category', 'producer'])->whereIn('id', $validated['food_ids'])->get();

        return view('back.foods.compare-result', compact('products'));
    }

    /**
     * Build the pivot payload for certifications sync.
     * The obtained_on date is set to today; the form only records which
     * certifications apply, not when each one was obtained.
     *
     * @return array<int, array{obtained_on: string}>
     */
    private function certificationSyncPayload(FoodRequest $request): array
    {
        $obtainedOn = now()->toDateString();

        return collect($request->certificationIds())
            ->unique()
            ->mapWithKeys(fn (int $id): array => [$id => ['obtained_on' => $obtainedOn]])
            ->all();
    }
}
