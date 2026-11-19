<?php

declare(strict_types=1);

namespace SocialAPIs\Tests;

use PHPUnit\Framework\TestCase;
use SocialAPIs\Account;
use SocialAPIs\Exception\AccessDeniedException;
use SocialAPIs\Exception\ApiConnectionException;
use SocialAPIs\Exception\ApiServerException;
use SocialAPIs\Exception\AuthenticationException;
use SocialAPIs\Exception\BadRequestException;
use SocialAPIs\Exception\InsufficientCreditsException;
use SocialAPIs\Exception\RateLimitException;
use SocialAPIs\Facebook;
use SocialAPIs\Http\Response;
use SocialAPIs\Instagram;

final class ClientTest extends TestCase
{
    public function testSendsTheKeyHeaderAndBuildsTheQuery(): void
    {
        $t = new FakeTransport([FakeTransport::json(['message' => 'ok', '0' => ['title' => 'NASA', 'followers_count' => 26000000]])]);
        $fb = new Facebook('test-key', transport: $t);

        $page = $fb->getPageInfo('nasa', ['exact_followers' => true, 'ignored' => null]);

        self::assertSame(['title' => 'NASA', 'followers_count' => 26000000], $page);
        self::assertSame('/facebook/pages/details', $t->path(0));
        self::assertSame(['link' => 'https://www.facebook.com/nasa', 'exact_followers' => 'true'], $t->query(0));
        self::assertSame('test-key', $t->requests[0]['headers']['x-api-token']);
        self::assertStringStartsWith('https://api.socialapis.io/', $t->requests[0]['url']);
        self::assertStringNotContainsString('test-key', $t->requests[0]['url']);
    }

    public function testIdentifiersAreNormalised(): void
    {
        $t = new FakeTransport([
            FakeTransport::json(['data' => []]),
            FakeTransport::json(['data' => []]),
            FakeTransport::json(['data' => ['username' => 'nasa']]),
            FakeTransport::json(['data' => []]),
        ]);
        $fb = new Facebook('k', transport: $t);
        $ig = new Instagram('k', transport: $t);

        $fb->getGroupPosts('123456');
        $fb->getPagePosts('https://m.facebook.com/nasa', ['limit' => 9]);
        $profile = $ig->getProfileDetails('@nasa');
        $ig->getUserId('nasa');

        self::assertSame('https://www.facebook.com/groups/123456', $t->query(0)['link']);
        self::assertSame(['link' => 'https://m.facebook.com/nasa', 'limit' => '9'], $t->query(1));
        self::assertSame('nasa', $t->query(2)['username']);
        self::assertSame(['username' => 'nasa'], $profile);
        self::assertSame('https://www.instagram.com/nasa', $t->query(3)['link']);
    }

    public function testAdsSearchByAdvertiserLeavesOutTheQuery(): void
    {
        $t = new FakeTransport([FakeTransport::json(['data' => ['ads' => []]])]);
        (new Facebook('k', transport: $t))->searchAds(null, ['ad_page_id' => '123456789', 'country' => 'ALL']);
        self::assertSame(['ad_page_id' => '123456789', 'country' => 'ALL'], $t->query(0));
    }

    public function testAccountCalls(): void
    {
        $t = new FakeTransport([FakeTransport::json(['creditsRemaining' => 150])]);
        self::assertSame(['creditsRemaining' => 150], (new Account('k', transport: $t))->getUsage());
        self::assertSame('/usage', $t->path(0));
    }

    /**
     * @return iterable<string, array{Response, class-string}>
     */
    public static function errors(): iterable
    {
        yield '401' => [FakeTransport::json(['message' => 'Invalid API key'], 401), AuthenticationException::class];
        yield '402' => [FakeTransport::json(['message' => 'No credits'], 402), InsufficientCreditsException::class];
        yield '403 credits' => [
            FakeTransport::json(['error' => 'Insufficient credits', 'message' => 'You need 3 credits', 'creditsNeeded' => 3], 403),
            InsufficientCreditsException::class,
        ];
        yield '403 paused' => [
            FakeTransport::json(['success' => false, 'message' => 'Paused', 'code' => 'SUBSCRIPTION_PAUSED'], 403),
            AccessDeniedException::class,
        ];
        yield '404' => [FakeTransport::json(['message' => 'Not found'], 404), BadRequestException::class];
        yield '500' => [new Response(502, [], '<html>Bad gateway</html>'), ApiServerException::class];
    }

    /**
     * @param class-string<\Throwable> $class
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('errors')]
    public function testErrorsAreTyped(Response $response, string $class): void
    {
        $this->expectException($class);
        (new Facebook('k', transport: new FakeTransport([$response])))->getPageInfo('nasa');
    }

    public function testErrorDetails(): void
    {
        $t = new FakeTransport([
            FakeTransport::json(['message' => 'Slow down'], 429, ['retry-after' => '12', 'x-request-id' => 'req-1']),
            FakeTransport::json(['success' => false, 'message' => 'Expired', 'code' => 'SUBSCRIPTION_EXPIRED'], 403),
        ]);
        $fb = new Facebook('k', transport: $t);
        try {
            $fb->getPageInfo('nasa');
            self::fail('expected a rate limit');
        } catch (RateLimitException $e) {
            self::assertSame(12.0, $e->retryAfterSeconds);
            self::assertSame('req-1', $e->requestId);
            self::assertSame('Slow down', $e->getMessage());
        }
        try {
            $fb->getPageInfo('nasa');
            self::fail('expected access denied');
        } catch (AccessDeniedException $e) {
            self::assertSame('SUBSCRIPTION_EXPIRED', $e->errorCode());
            self::assertSame(403, $e->statusCode);
        }
    }

    public function testSuccessWithoutJsonIsAConnectionError(): void
    {
        $this->expectException(ApiConnectionException::class);
        (new Facebook('k', transport: new FakeTransport([new Response(200, [], 'not json')])))->getPageInfo('nasa');
    }

    public function testMissingKey(): void
    {
        $saved = getenv('SOCIALAPIS_TOKEN');
        putenv('SOCIALAPIS_TOKEN');
        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('SOCIALAPIS_TOKEN');
            new Facebook();
        } finally {
            if ($saved !== false) {
                putenv('SOCIALAPIS_TOKEN=' . $saved);
            }
        }
    }

    public function testKeyFromEnvironment(): void
    {
        $saved = getenv('SOCIALAPIS_TOKEN');
        putenv('SOCIALAPIS_TOKEN=env-key');
        try {
            $t = new FakeTransport([FakeTransport::json(['ok' => true])]);
            (new Account(transport: $t))->getUsage();
            self::assertSame('env-key', $t->requests[0]['headers']['x-api-token']);
        } finally {
            putenv($saved === false ? 'SOCIALAPIS_TOKEN' : 'SOCIALAPIS_TOKEN=' . $saved);
        }
    }
}
