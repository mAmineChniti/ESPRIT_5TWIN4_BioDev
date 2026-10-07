<?php

namespace App\Http\Controllers;

use App\Enums\EnvironmentalScore;
use App\Enums\Stage;
use App\Http\Requests\FoodRequest;
use App\Models\Category;
use App\Models\Certification;
use App\Models\Food;
use App\Models\StageTransition;
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
            ->when($user->isProfessional(), fn ($query) => $query->where('producer_id', $user->id))
            ->latest()
            ->paginate(10);

        return view('foods.index', [
            'foods' => $foods,
            // Derived from the policy rather than a hand-rolled role check, so
            // the "Add a product" and CSV import controls appear for exactly
            // the people whose POST would be authorised.
            'canManage' => $user->can('create', Food::class),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Food::class);

        return view('foods.create', [
            'categories' => Category::orderBy('name')->get(),
            'certifications' => Certification::orderBy('name')->get(),
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

        return redirect()->route('foods.index')
            ->with('success', 'Product added successfully.');
    }

    public function show(Food $food): View
    {
        $this->authorize('view', $food);

        $food->load(['category', 'producer', 'transitions.actor', 'certifications']);

        return view('foods.show', compact('food'));
    }

    public function edit(Food $food): View
    {
        $this->authorize('update', $food);

        return view('foods.edit', [
            'food' => $food,
            'categories' => Category::orderBy('name')->get(),
            'certifications' => Certification::orderBy('name')->get(),
            'selectedCertifications' => $food->certifications->pluck('id')->all(),
        ]);
    }

    public function update(FoodRequest $request, Food $food): RedirectResponse
    {
        $this->authorize('update', $food);

        DB::transaction(function () use ($request, $food): void {
            $food->update($request->foodPayload());

            // Synced unconditionally: the form omits "certifications" entirely
            // when every box is unchecked, and guarding on has() would make
            // certifications impossible to remove.
            $food->certifications()->sync($this->certificationSyncPayload($request));
        });

        return redirect()->route('foods.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Food $food): RedirectResponse
    {
        $this->authorize('delete', $food);

        $food->delete();

        return redirect()->route('foods.index')
            ->with('success', 'Product deleted.');
    }

    /**
     * Import products from a CSV file.
     *
     * Expected columns: name, category, origin, environmental_score, calories, protein, carbs, fat
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

        $header = array_map(fn (string $col): string => strtolower(trim($col)), $header);

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
                DB::transaction(function () use ($request, $data, $name, $score): void {
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

    /**
     * Pivot payload for the certifications selected on a form.
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
