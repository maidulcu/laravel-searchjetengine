<?php

namespace SearchJet\Laravel\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

/**
 * Thrown when the SearchJet API answers with an error, or cannot be reached.
 */
class SearchJetException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?string $errorCode = null,
        public readonly array $body = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }

    /**
     * Build the right exception for a failed API response.
     *
     * The API uses two error shapes: search returns {"error": "<text>", "code": "QUOTA_EXCEEDED"},
     * index and analytics return {"error": "quota_exceeded", "message": "<text>"}.
     */
    public static function fromResponse(Response $response): self
    {
        $body = $response->json();
        $body = is_array($body) ? $body : [];
        $status = $response->status();

        $code = $body['code'] ?? (isset($body['message']) ? ($body['error'] ?? null) : null);
        $message = $body['message'] ?? $body['error'] ?? null;
        $message = is_string($message) && $message !== ''
            ? $message
            : "SearchJet API request failed with HTTP {$status}.";

        $class = match (true) {
            $status === 401 => AuthenticationException::class,
            $status === 429 => RateLimitException::class,
            default => self::class,
        };

        return new $class($message, $status, is_string($code) ? $code : null, $body);
    }
}
