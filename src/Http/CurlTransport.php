<?php

declare(strict_types=1);

namespace SocialAPIs\Http;

use SocialAPIs\Exception\ApiConnectionException;

/**
 * Default transport: PHP's curl extension, no other dependency.
 */
final class CurlTransport implements Transport
{
    public function get(string $url, array $headers, float $timeoutSeconds): Response
    {
        $handle = curl_init($url);
        if ($handle === false) {
            throw new ApiConnectionException('Could not start a curl request');
        }

        $responseHeaders = [];
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        curl_setopt_array($handle, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT_MS => (int) min(10_000, $timeoutSeconds * 1000),
            CURLOPT_TIMEOUT_MS => (int) ($timeoutSeconds * 1000),
            CURLOPT_ENCODING => '',
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($line);
            },
        ]);

        $body = curl_exec($handle);
        if ($body === false) {
            $error = curl_error($handle);
            $errno = curl_errno($handle);
            $kind = $errno === CURLE_OPERATION_TIMEDOUT ? 'timed out' : 'failed';
            throw new ApiConnectionException("Request {$kind}: {$error}");
        }
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

        return new Response($status, $responseHeaders, (string) $body);
    }
}
