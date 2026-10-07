<?php

namespace App\Http\Controllers;

use App\Enums\Stage;
use App\Http\Requests\FoodRequest;
use App\Models\Category;
use App\Models\Certification;
use App\Models\Food;
use App\Models\StageTransition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FoodController extends Controller
{
    /**
     * Display a listing of the resource. Consumers see the whole catalog;
     * producers see what they registered.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $foods = Food::query()
            ->with(['category', 'producer'])
            ->when(
                $user->isProfessional() && ! $user->isAdmin(),
                fn ($query) => $query->where('producer_id', $user->id)
            )
            ->latest()
            ->paginate(10);

        return view('foods.index', compact('foods'));
    }

    public function create(): View
    {
        return view('foods.create', [
            'categories' => Category::all(),
            'certifications' => Certification::orderBy('name')->get(),
        ]);
    }

    public function store(FoodRequest $request): RedirectResponse
    {
        $this->authorize('create', Food::class);

        $food = Food::create([
            ...$request->foodPayload(),
            'producer_id' => $request->user()->id,
        ]);

        $food->certifications()->sync(
            collect($request->certificationIds())
                ->mapWithKeys(fn (int $id): array => [$id => ['obtained_on' => now()->toDateString()]])
                ->all()
        );

        StageTransition::create([
            'food_id' => $food->id,
            'actor_id' => $request->user()->id,
            'from_stage' => null,
            'to_stage' => Stage::Produced->value,
            'notes' => 'Product registered',
            'occurred_at' => now(),
        ]);

        return redirect()->route('foods.index')
            ->with('success', 'Product added successfully.');
    }

    public function show(Food $food): View
    {
        $food->load(['category', 'producer', 'transitions.actor', 'certifications']);

        return view('foods.show', compact('food'));
    }

    public function edit(Food $food): View
    {
        $this->authorize('update', $food);

        return view('foods.edit', [
            'food' => $food,
            'categories' => Category::all(),
            'certifications' => Certification::orderBy('name')->get(),
            'selectedCertifications' => $food->certifications->pluck('id')->all(),
        ]);
    }

    public function update(FoodRequest $request, Food $food): RedirectResponse
    {
        $this->authorize('update', $food);

        $food->update($request->foodPayload());

        if ($request->has('certifications')) {
            $food->certifications()->sync(
                collect($request->certificationIds())
                    ->mapWithKeys(fn (int $id): array => [$id => ['obtained_on' => now()->toDateString()]])
                    ->all()
            );
        }

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
                continue;
            }

            $data = array_combine($header, $row);

            $category = Category::firstOrCreate(['name' => trim($data['category'] ?? 'Other')]);

            try {
                $food = Food::create([
                    'name' => trim($data['name']),
                    'category_id' => $category->id,
                    'origin' => trim($data['origin'] ?? ''),
                    'environmental_score' => strtoupper(trim($data['environmental_score'] ?? '')) ?: null,
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

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = ($data['name'] ?? 'unknown').': '.$e->getMessage();
            }
        }

        fclose($handle);

        $message = "{$imported} product(s) imported successfully.";

        if (count($errors) > 0) {
            $message .= ' '.count($errors).' row(s) skipped.';
        }

        return redirect()->route('foods.index')->with('success', $message);
    }
}
