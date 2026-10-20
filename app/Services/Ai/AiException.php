<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Raised when the AI provider cannot be reached or refuses the request.
 *
 * The consumer-facing pages turn this into an honest "we could not reach the
 * detector" message rather than pretending an analysis succeeded.
 */
class AiException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('AI is not configured.');
    }

    public static function providerError(string $provider, int $status): self
    {
        return new self(
            "The AI provider ({$provider}) returned HTTP {$status}."
        );
    }

    public static function unknownProvider(string $provider): self
    {
        return new self(
            "The AI provider ({$provider}) has no configured endpoint."
        );
    }

    public static function malformedResponse(string $detail): self
    {
        return new self("The AI response could not be read: {$detail}");
    }

    public static function timeout(string $provider): self
    {
        return new self("The AI provider ({$provider}) did not respond in time.");
    }
}
