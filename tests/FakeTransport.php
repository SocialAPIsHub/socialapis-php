<?php

declare(strict_types=1);

namespace SocialAPIs\Tests;

use SocialAPIs\Http\Response;
use SocialAPIs\Http\Transport;

/** Answers from a list and records every request. */
final class FakeTransport implements Transport
{
    /** @var list<array{url: string, headers: array<string, string>}> */
    public array $requests = [];

    /**
     * @param list<Response> $responses
     */
    public function __construct(private array $responses)
    {
    }

    /**
     * @param array<string|int, mixed> $body
     * @param array<string, string> $headers
     */
    public static function json(array $body, int $status = 200, array $headers = []): Response
    {
        return new Response($status, $headers, json_encode($body, JSON_THROW_ON_ERROR));
    }

    public function get(string $url, array $headers, float $timeoutSeconds): Response
    {
        $this->requests[] = ['url' => $url, 'headers' => $headers];
        $response = array_shift($this->responses);
        if ($response === null) {
            throw new \LogicException("Unexpected request: {$url}");
        }
        return $response;
    }

    /** @return array<string, string> */
    public function query(int $index): array
    {
        parse_str((string) parse_url($this->requests[$index]['url'], PHP_URL_QUERY), $query);
        /** @var array<string, string> $query */
        return $query;
    }

    public function path(int $index): string
    {
        return (string) parse_url($this->requests[$index]['url'], PHP_URL_PATH);
    }
}
