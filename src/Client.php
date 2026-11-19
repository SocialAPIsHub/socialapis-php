<?php

declare(strict_types=1);

namespace SocialAPIs;

use SocialAPIs\Exception\AccessDeniedException;
use SocialAPIs\Exception\ApiConnectionException;
use SocialAPIs\Exception\ApiServerException;
use SocialAPIs\Exception\AuthenticationException;
use SocialAPIs\Exception\BadRequestException;
use SocialAPIs\Exception\InsufficientCreditsException;
use SocialAPIs\Exception\RateLimitException;
use SocialAPIs\Http\CurlTransport;
use SocialAPIs\Http\Transport;

/**
 * Shared HTTP layer of Facebook, Instagram and Account: the API key header,
 * query building and error mapping live here once.
 */
abstract class Client
{
    public const VERSION = '0.1.0';
    public const DEFAULT_BASE_URL = 'https://api.socialapis.io';

    private readonly string $apiToken;
    private readonly string $baseUrl;
    private readonly Transport $transport;

    /**
     * @param string|null $apiToken defaults to the SOCIALAPIS_TOKEN environment variable
     */
    public function __construct(
        ?string $apiToken = null,
        ?string $baseUrl = null,
        private readonly float $timeoutSeconds = 30.0,
        ?Transport $transport = null,
    ) {
        $token = $apiToken ?? (getenv('SOCIALAPIS_TOKEN') ?: null);
        if ($token === null || $token === '') {
            throw new \InvalidArgumentException(
                'An API key is required: pass it or set SOCIALAPIS_TOKEN. '
                . 'Get a free key at https://socialapis.io/auth/signup (200 calls/month, no card).'
            );
        }
        $this->apiToken = $token;
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');
        $this->transport = $transport ?? new CurlTransport();
    }

    /**
     * GET an endpoint and return the decoded JSON answer.
     *
     * Works for any endpoint, including ones newer than this SDK: see
     * https://socialapis.io/api-sources for the list and their parameters.
     *
     * @param array<string, scalar|null> $params null values are left out
     *
     * @return array<string|int, mixed>
     */
    public function request(string $path, array $params = []): array
    {
        if (!str_starts_with($path, '/')) {
            throw new \InvalidArgumentException("path must start with '/', got: {$path}");
        }
        $query = [];
        foreach ($params as $name => $value) {
            if ($value === null) {
                continue;
            }
            $query[$name] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }
        $url = $this->baseUrl . $path . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');

        $response = $this->transport->get($url, [
            'x-api-token' => $this->apiToken,
            'Accept' => 'application/json',
            'User-Agent' => 'socialapis-php/' . self::VERSION,
        ], $this->timeoutSeconds);

        $body = json_decode($response->body, true, 512, JSON_BIGINT_AS_STRING);
        $decoded = is_array($body) ? $body : [];

        if ($response->status >= 200 && $response->status < 300) {
            if (!is_array($body)) {
                throw new ApiConnectionException("The API answered {$response->status} without a JSON body");
            }
            return $body;
        }

        $message = self::messageOf($decoded) ?? ($response->body !== '' ? substr($response->body, 0, 300) : "HTTP {$response->status}");
        $requestId = $response->header('x-request-id');
        $status = $response->status;

        if ($status === 401) {
            throw new AuthenticationException($message, $status, $requestId, $decoded);
        }
        if ($status === 402 || ($status === 403 && self::isCreditError($decoded))) {
            throw new InsufficientCreditsException($message, $status, $requestId, $decoded);
        }
        if ($status === 403) {
            throw new AccessDeniedException($message, $status, $requestId, $decoded);
        }
        if ($status === 429) {
            $retry = $response->header('retry-after');
            throw new RateLimitException($message, $status, $requestId, $decoded, is_numeric($retry) ? (float) $retry : null);
        }
        if ($status >= 500) {
            throw new ApiServerException($message, $status, $requestId, $decoded);
        }
        throw new BadRequestException($message, $status, $requestId, $decoded);
    }

    /**
     * @param array<string|int, mixed> $body
     */
    private static function messageOf(array $body): ?string
    {
        foreach (['message', 'error', 'detail'] as $key) {
            $value = $body[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
            if (is_array($value) && isset($value['message']) && is_string($value['message']) && $value['message'] !== '') {
                return $value['message'];
            }
        }
        return null;
    }

    /**
     * @param array<string|int, mixed> $body
     */
    private static function isCreditError(array $body): bool
    {
        foreach (['error', 'message'] as $key) {
            if (isset($body[$key]) && is_string($body[$key]) && stripos($body[$key], 'credit') !== false) {
                return true;
            }
        }
        return isset($body['creditsNeeded']);
    }

    /**
     * Merge the main parameters with the caller's extra ones (extra wins).
     *
     * @param array<string, scalar|null> $main
     * @param array<string, scalar|null> $extra
     *
     * @return array<string, scalar|null>
     */
    protected static function params(array $main, array $extra): array
    {
        return array_merge($main, $extra);
    }

    protected static function facebookUrl(string $value, string $prefix = 'https://www.facebook.com/'): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('identifier is required');
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        return $prefix . ltrim($value, '/');
    }
}
