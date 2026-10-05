<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Food;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $foods = Food::with('category')->get();

        return view('foods.index', compact('foods'));
    }

    public function create()
    {
        $categories = Category::all();

        return view('foods.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'origin' => 'nullable|string|max:255',
            'certifications' => 'nullable|string|max:255',
            'environmental_score' => 'nullable|in:A,B,C,D,E',
            'calories' => 'required|integer',
            'protein' => 'required|numeric',
            'carbs' => 'required|numeric',
            'fat' => 'required|numeric',
        ]);

        Food::create($validated);

        return redirect()->route('foods.index')->with('success', 'Produit ajouté avec succès!');
    }

    public function show(Food $food)
    {
        return view('foods.show', compact('food'));
    }

    public function edit(Food $food)
    {
        $categories = Category::all();

        return view('foods.edit', compact('food', 'categories'));
    }

    public function update(Request $request, Food $food)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'origin' => 'nullable|string|max:255',
            'certifications' => 'nullable|string|max:255',
            'environmental_score' => 'nullable|in:A,B,C,D,E',
            'calories' => 'required|integer',
            'protein' => 'required|numeric',
            'carbs' => 'required|numeric',
            'fat' => 'required|numeric',
        ]);

        $food->update($validated);

        return redirect()->route('foods.index')->with('success', 'Produit modifié avec succès!');
    }

    public function destroy(Food $food)
    {
        $food->delete();

        return redirect()->route('foods.index')->with('success', 'Produit supprimé!');
    }
}
