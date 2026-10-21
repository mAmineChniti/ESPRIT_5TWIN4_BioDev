<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Services\Ai\AiException;
use App\Services\JourneyChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PublicJourneyChatController extends Controller
{
    /**
     * Answer a public question about one traceability journey.
     */
    public function ask(Request $request, Journey $journey, JourneyChatService $chat): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:200'],
            'locale' => ['sometimes', 'string', Rule::in(['fr', 'en', 'ar'])],
        ]);

        try {
            return response()->json([
                'answer' => $chat->ask(
                    $journey,
                    $validated['question'],
                    $validated['locale'] ?? 'fr',
                ),
            ]);
        } catch (AiException $exception) {
            Log::warning('Public journey chat request failed.', [
                'journey_id' => $journey->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => __('The journey assistant is temporarily unavailable. Please try again.'),
            ], 503);
        }
    }
}
