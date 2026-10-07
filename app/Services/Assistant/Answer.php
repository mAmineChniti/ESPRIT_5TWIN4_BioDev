<?php

namespace App\Services\Assistant;

/**
 * A single reply from the product assistant, with what it used to answer.
 *
 * `sources` is the part that matters: the model is required to say which parts
 * of the record it relied on, so a consumer can be shown the underlying data
 * instead of being asked to trust prose.
 */
final readonly class Answer
{
    /**
     * @param  list<string>  $sources
     */
    public function __construct(
        public string $body,
        public array $sources,
        public bool $isRefusal = false,
        public ?string $failure = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromModelResponse(array $payload): self
    {
        $body = trim((string) data_get($payload, 'answer', ''));
        $body = (string) preg_replace('/\s+/u', ' ', strip_tags($body));

        $sources = array_values(array_filter(array_map(
            static fn (mixed $s): string => is_string($s) ? trim($s) : '',
            data_get($payload, 'sources', []) ?: [],
        )));

        return new self(
            body: $body,
            sources: $sources,
            isRefusal: (bool) data_get($payload, 'cannot_answer', false),
        );
    }

    public static function failed(string $reason): self
    {
        return new self(body: '', sources: [], failure: $reason);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'answer' => $this->body,
            'sources' => $this->sources,
            'cannot_answer' => $this->isRefusal,
            'failure' => $this->failure,
        ];
    }
}
