<?php

declare(strict_types=1);

namespace SocialAPIs\Exception;

/**
 * An HTTP error answer from the API (4xx or 5xx). Errors are never charged.
 * The subclasses let you react to the kind of failure.
 */
class ApiException extends SocialAPIsException
{
    /**
     * @param array<string, mixed> $body
     */
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?string $requestId = null,
        public readonly array $body = [],
    ) {
        parent::__construct($message, $statusCode);
    }
}
