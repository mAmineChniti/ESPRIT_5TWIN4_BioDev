<?php

namespace App\Services\Assistant;

use App\Models\Food;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiException;
use App\Services\Greenwashing\ProductRecord;
use Illuminate\Support\Facades\Log;

/**
 * Product assistant.
 *
 * Answers questions about one product strictly from the traceability record, so
 * it can say "not recorded" rather than guessing — which is the only acceptable
 * behaviour for a platform whose entire purpose is not making claims up.
 *
 * It is grounded rather than free-form: the model never sees outside knowledge
 * about the product, only what NutriTrace holds, and every reply has to name
 * the fields it used.
 */
class ProductAssistant
{
    public function __construct(private readonly AiClient $ai) {}

    /**
     * Never throws; an unreachable provider becomes a failure the caller renders.
     */
    public function ask(Food $food, string $question): Answer
    {
        $question = trim($question);

        if ($question === '') {
            return new Answer(body: 'Ask a question about this product.', sources: []);
        }

        try {
            $payload = $this->ai->json(
                $this->messages(ProductRecord::fromFood($food), $question),
                $this->schema(),
                maxTokens: 2000,
            );
        } catch (AiException $e) {
            Log::warning('Product assistant failed.', ['food_id' => $food->id, 'reason' => $e->getMessage()]);

            return Answer::failed($e->getMessage());
        }

        return Answer::fromModelResponse($payload);
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function messages(ProductRecord $record, string $question): array
    {
        return [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are the NutriTrace product assistant. You answer questions about ONE product using',
                    'only the record provided. You have no other knowledge of this product and must not',
                    'invent any.',
                    '',
                    'Rules:',
                    '- If the record does not contain the answer, say so plainly and set cannot_answer to true.',
                    '  "NutriTrace has no record of X" is a correct and useful answer.',
                    '- Never guess, estimate, or draw on general knowledge about the product, its maker or',
                    '  its health properties. Nutrition questions must be answered only from the recorded',
                    '  per-100g values, and you must say those are per 100 g.',
                    '- Be brief and concrete. Two or three sentences.',
                    '- You may interpret what the record shows, but you must not claim the product is good or',
                    '  bad for a person, and you must not give medical or dietary advice.',
                    '- List in "sources" the names of the record fields you actually used, so the consumer can',
                    '  check them. Use short field names like "certifications" or "supply_chain.steps".',
                ]),
            ],
            [
                'role' => 'user',
                'content' => 'Product record:'.PHP_EOL.json_encode(
                    $record->toArray(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ).PHP_EOL.PHP_EOL.'Question: '.$question,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['answer', 'sources', 'cannot_answer'],
            'properties' => [
                'answer' => [
                    'type' => 'string',
                    'description' => 'The reply to the consumer, two or three sentences, plain language.',
                ],
                'sources' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Record field names used, e.g. "certifications".',
                ],
                'cannot_answer' => [
                    'type' => 'boolean',
                    'description' => 'True when the record does not contain the answer.',
                ],
            ],
        ];
    }

    /**
     * Starter questions, phrased so the record can actually answer them.
     *
     * @return list<string>
     */
    public function suggestions(Food $food): array
    {
        $suggestions = ['Where was this grown?'];

        if ($food->certifications->isNotEmpty()) {
            $suggestions[] = 'What certifications does it have?';
        }

        $suggestions[] = 'Can I trace the whole supply chain?';
        $suggestions[] = 'What is the nutrition per 100 g?';

        return $suggestions;
    }
}
