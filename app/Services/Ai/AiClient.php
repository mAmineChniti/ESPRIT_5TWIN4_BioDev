<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Thin client over the hosted chat models.
 *
 * Uses Laravel's HTTP client rather than a vendor SDK so the provider is a
 * config value: Groq, OpenRouter, OpenAI and Together all speak the OpenAI
 * chat-completions shape, and Gemini is handled by one extra branch.
 */
class AiClient
{
    public function isConfigured(): bool
    {
        return filled($this->key());
    }

    public function provider(): string
    {
        return (string) config('services.ai.provider', 'groq');
    }

    public function model(): string
    {
        return $this->provider() === 'gemini'
            ? (string) config('services.ai.gemini_model')
            : (string) config('services.ai.model');
    }

    /**
     * Ask the model for JSON, and return the decoded array.
     *
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $schema  JSON Schema constraining the reply
     * @return array<string, mixed>
     */
    public function json(array $messages, array $schema, int $maxTokens = 900): array
    {
        return $this->decode($this->complete($messages, [
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'nutritrace_response',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ], $maxTokens, $schema));
    }

    /**
     * Ask the model for plain prose.
     *
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function text(array $messages, int $maxTokens = 400): string
    {
        return trim($this->call($messages, [], $maxTokens));
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>|null  $schema
     */
    private function complete(array $messages, array $options, int $maxTokens, ?array $schema): string
    {
        return $this->call($messages, $options, $maxTokens, $schema);
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>|null  $schema  Required shape, when the
     *                                             caller expects JSON back
     */
    private function call(array $messages, array $options, int $maxTokens, ?array $schema = null): string
    {
        if (! $this->isConfigured()) {
            throw AiException::notConfigured();
        }

        $payload = $this->provider() === 'gemini'
            ? $this->geminiPayload($messages, $maxTokens, $schema)
            : $this->openAiPayload($messages, $options, $maxTokens);

        $isGemini = $this->provider() === 'gemini';

        $url = $isGemini
            ? rtrim((string) config('services.ai.gemini_base_url'), '/').'/models/'.$this->model().':generateContent'
            : rtrim((string) config('services.ai.base_url'), '/').'/chat/completions';

        // Gemini rejects an OpenAI-style "Authorization: Bearer" outright; it
        // wants the key as x-goog-api-key. Sending both fails with a 401.
        $request = Http::acceptJson()->timeout((int) config('services.ai.timeout', 25));

        $request = $isGemini
            ? $request->withHeaders(['x-goog-api-key' => $this->key()])
            : $request->withToken($this->key());

        $response = $this->send($request, $url, $payload, $isGemini);

        return $isGemini
            ? $this->geminiText($response->json())
            : (string) data_get($response->json(), 'choices.0.message.content', '');
    }

    /**
     * POST with one retry, because the free tiers shed load with a 503 or 429.
     *
     * @param  PendingRequest  $request
     * @param  array<string, mixed>  $payload
     */
    private function send($request, string $url, array $payload, bool $isGemini): Response
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                $response = $request->post($url, $payload);
            } catch (ConnectionException) {
                if ($attempt >= 2) {
                    throw AiException::timeout($this->provider());
                }

                usleep(400_000);

                continue;
            }

            // 429 is deliberately excluded: retrying a rate limit straight away
            // spends the quota faster. The free tiers need the caller to back
            // off, and the detector degrades honestly rather than hanging.
            $retryable = in_array($response->status(), [502, 503, 504], true);

            if ($retryable && $attempt < 2) {
                usleep(700_000);

                continue;
            }

            if ($response->failed()) {
                Log::warning('NutriTrace AI request failed.', [
                    'provider' => $this->provider(),
                    'model' => $this->model(),
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                throw AiException::providerError($this->provider(), $response->status(), $response->body());
            }

            return $response;
        }
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function openAiPayload(array $messages, array $options, int $maxTokens): array
    {
        return [
            'model' => $this->model(),
            'messages' => $messages,
            'temperature' => 0.2,
            'max_tokens' => $maxTokens,
            ...$options,
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>
     */
    private function geminiPayload(array $messages, int $maxTokens, ?array $schema): array
    {
        // Gemini wants the two shapes separated, and expresses JSON mode as a
        // responseMimeType rather than a JSON Schema. The caller still passes a
        // schema so the two paths stay interchangeable and testable.
        $system = array_values(array_filter($messages, fn (array $m): bool => $m['role'] === 'system'));
        $turns = array_map(
            fn (array $m): array => [
                'role' => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $m['content']]],
            ],
            array_values(array_filter($messages, fn (array $m): bool => $m['role'] !== 'system'))
        );

        return [
            'systemInstruction' => ['parts' => array_map(
                fn (array $m): array => ['text' => $m['content']],
                $system
            )],
            'contents' => $turns,
            'generationConfig' => [
                'temperature' => 0.2,
                // Headroom: Gemini counts reasoning against the same budget, so
                // a limit tuned for the reply alone truncates the answer.
                'maxOutputTokens' => $maxTokens * 2,
                'responseMimeType' => 'application/json',
                // The thinking models spend most of the budget reasoning, which
                // buys nothing here and routinely cuts the reply short.
                'thinkingConfig' => ['thinkingBudget' => 0],
                // Without this Gemini invents its own field names, and every
                // field the caller reads back comes back empty.
                ...($schema ? ['responseSchema' => $this->geminiSchema($schema)] : []),
            ],
        ];
    }

    /**
     * Gemini accepts the JSON Schema subset but not every keyword the OpenAI
     * endpoint tolerates, so the unsupported parts are stripped on the way in.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function geminiSchema(array $schema): array
    {
        // Gemini's Schema subset rejects unknown keywords outright, so anything
        // the OpenAI endpoint tolerates but Gemini does not is dropped here.
        $clean = function (array $node) use (&$clean): array {
            $out = [];

            if (isset($node['type']) && is_string($node['type'])) {
                $out['type'] = $node['type'];
            }

            if (isset($node['description']) && is_string($node['description'])) {
                $out['description'] = $node['description'];
            }

            if (isset($node['enum']) && is_array($node['enum'])) {
                $out['enum'] = array_values(array_filter($node['enum'], 'is_string'));
            }

            if (isset($node['properties']) && is_array($node['properties'])) {
                $out['properties'] = [];

                foreach ($node['properties'] as $name => $child) {
                    if (is_array($child)) {
                        $out['properties'][$name] = $clean($child);
                    }
                }
            }

            if (isset($node['required']) && is_array($node['required'])) {
                $out['required'] = array_values(array_filter($node['required'], 'is_string'));
            }

            if (isset($node['items']) && is_array($node['items'])) {
                $out['items'] = $clean($node['items']);
            }

            return $out;
        };

        return $clean($schema);
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function geminiText(?array $body): string
    {
        // The reply can be split across parts, and reasoning parts are marked.
        $parts = data_get($body, 'candidates.0.content.parts', []);

        if (! is_array($parts)) {
            return '';
        }

        $text = '';

        foreach ($parts as $part) {
            if (! is_array($part) || ($part['thought'] ?? false) === true) {
                continue;
            }

            $text .= (string) ($part['text'] ?? '');
        }

        if ($text === '' && data_get($body, 'candidates.0.finishReason') === 'MAX_TOKENS') {
            throw AiException::malformedResponse(
                'the model used its whole token budget without finishing the answer'
            );
        }

        return $text;
    }

    /**
     * Models wrap JSON in prose or fences often enough to be worth handling.
     *
     * @return array<string, mixed>
     */
    private function decode(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === '') {
            throw AiException::malformedResponse('the model returned nothing');
        }

        if (Str::startsWith($raw, '```')) {
            $raw = (string) preg_replace('/^```[a-zA-Z]*\s*|\s*```$/m', '', $raw);
        }

        // Take the outermost object, ignoring anything the model wrapped around it.
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $raw = substr($raw, $start, $end - $start + 1);
        }

        try {
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw AiException::malformedResponse($e->getMessage());
        }

        if (! is_array($decoded)) {
            throw AiException::malformedResponse('expected an object');
        }

        return $decoded;
    }

    private function key(): ?string
    {
        $key = config('services.ai.key');

        return is_string($key) && $key !== '' ? $key : null;
    }
}
