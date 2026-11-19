<?php

declare(strict_types=1);

namespace SocialAPIs\Exception;

/**
 * 429: too many calls. Retry after $retryAfterSeconds (when the API sends it).
 */
class RateLimitException extends ApiException
{
    /**
     * @param array<string, mixed> $body
     */
    public function __construct(
        string $message,
        int $statusCode = 429,
        ?string $requestId = null,
        array $body = [],
        public readonly ?float $retryAfterSeconds = null,
    ) {
        parent::__construct($message, $statusCode, $requestId, $body);
    }
}
