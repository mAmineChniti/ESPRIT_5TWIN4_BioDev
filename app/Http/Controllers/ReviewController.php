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
     *
     * Only a consumer reaches this action; the route carries the role gate.
     */
    public function store(Request $request, Food $food): RedirectResponse
    {
        $user = $request->user();

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

    /**
     * Remove a review the signed in consumer wrote.
     *
     * The review has to belong to both the consumer and the product named in
     * the URL. Checking only the author is not enough: a review id alone would
     * let a review be deleted through an unrelated product's address.
     */
    public function destroy(Request $request, Food $food, Review $review): RedirectResponse
    {
        // The review is real but not reachable at this address.
        abort_unless($review->food_id === $food->id, 404, 'Review not found.');

        abort_unless(
            $review->user_id === $request->user()->id,
            403,
            'You can only delete your own review.',
        );

        $review->delete();

        return back()->with('success', 'Your review was removed.');
    }
}
