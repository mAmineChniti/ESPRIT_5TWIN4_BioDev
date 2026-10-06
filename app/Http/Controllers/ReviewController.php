<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Leave or update an honest review of a product.
     */
    public function store(Request $request, Food $food): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->role === 'consumer', 403, 'Only consumers can review products.');

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        Review::updateOrCreate(
            ['food_id' => $food->id, 'user_id' => $user->id],
            ['rating' => $validated['rating'], 'body' => $validated['body'] ?? null],
        );

        return back()->with('success', 'Thank you — your review is published.');
    }

    public function destroy(Request $request, Food $food, Review $review): RedirectResponse
    {
        abort_unless(
            $review->user_id === $request->user()->id,
            403,
            'You can only delete your own review.',
        );

        $review->delete();

        return back()->with('success', 'Your review was removed.');
    }
}
