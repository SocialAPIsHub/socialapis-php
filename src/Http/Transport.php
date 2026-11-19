<?php

declare(strict_types=1);

namespace SocialAPIs\Http;

use SocialAPIs\Exception\ApiConnectionException;

/**
 * Sends one GET request. Swap it to use your own HTTP client, a proxy or a
 * fake in tests.
 */
interface Transport
{
    /**
     * @param array<string, string> $headers
     *
     * @throws ApiConnectionException on a network failure or timeout
     */
    public function get(string $url, array $headers, float $timeoutSeconds): Response;
}
