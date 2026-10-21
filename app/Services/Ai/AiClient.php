<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Thin client over Groq's hosted chat models.
 *
 * Uses Laravel's HTTP client rather than a vendor SDK for simplicity.
 */
class AiClient
{
    public function isConfigured(): bool
    {
        return filled($this->key());
    }

    public function model(): string
    {
        return (string) config('services.ai.model', 'llama-3.3-70b-versatile');
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
        ], $maxTokens));
    }

    /**
     * Ask the model for plain prose.
     *
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function text(array $messages, int $maxTokens = 400, ?int $timeout = null): string
    {
        return trim($this->call($messages, [], $maxTokens, $timeout));
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     */
    private function complete(array $messages, array $options, int $maxTokens): string
    {
        return $this->call($messages, $options, $maxTokens);
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     */
    private function call(
        array $messages,
        array $options,
        int $maxTokens,
        ?int $timeout = null,
    ): string {
        if (! $this->isConfigured()) {
            throw AiException::notConfigured();
        }

        $payload = $this->openAiPayload($messages, $options, $maxTokens);
        $url = $this->baseUrl().'/chat/completions';

        $request = Http::acceptJson()
            ->timeout($timeout ?? (int) config('services.ai.timeout', 25))
            ->withToken($this->key());

        $response = $this->send($request, $url, $payload);

        return (string) data_get($response->json(), 'choices.0.message.content', '');
    }

    /**
     * POST with one retry, because the free tier sheds load with a 503 or 429.
     *
     * @param  PendingRequest  $request
     * @param  array<string, mixed>  $payload
     */
    private function send($request, string $url, array $payload): Response
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                $response = $request->post($url, $payload);
            } catch (ConnectionException) {
                if ($attempt >= 2) {
                    throw AiException::timeout('groq');
                }

                usleep(400_000);

                continue;
            }

            // 429 is deliberately excluded: retrying a rate limit straight away
            // spends the quota faster. The free tier needs the caller to back
            // off, and the detector degrades honestly rather than hanging.
            $retryable = in_array($response->status(), [502, 503, 504], true);

            if ($retryable && $attempt < 2) {
                usleep(700_000);

                continue;
            }

            if ($response->failed()) {
                Log::warning('NutriTrace AI request failed.', [
                    'provider' => 'groq',
                    'model' => $this->model(),
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                throw AiException::providerError('groq', $response->status());
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

    /**
     * The Groq base URL.
     *
     * AI_BASE_URL wins when it is set (self-hosted gateways, proxies), but it
     * is optional: the default is the Groq public endpoint.
     */
    private function baseUrl(): string
    {
        $configured = config('services.ai.base_url');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }

        return 'https://api.groq.com/openai/v1';
    }

    private function key(): ?string
    {
        $key = config('services.ai.key');

        return is_string($key) && $key !== '' ? $key : null;
    }
}
