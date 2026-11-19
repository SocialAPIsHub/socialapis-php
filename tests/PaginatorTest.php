<?php

declare(strict_types=1);

namespace SocialAPIs\Tests;

use PHPUnit\Framework\TestCase;
use SocialAPIs\Facebook;
use SocialAPIs\Paginator;

final class PaginatorTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $posts
     *
     * @return array<string, mixed>
     */
    private static function answer(array $posts, ?string $cursor): array
    {
        return [
            'message' => 'ok',
            'meta' => ['statusCode' => 200, 'warnings' => [['a' => 1], ['b' => 2], ['c' => 3]]],
            'data' => ['posts' => $posts, 'page_info' => ['end_cursor' => $cursor, 'has_next_page' => $cursor !== null]],
        ];
    }

    public function testFollowsTheCursorUntilTheLastPage(): void
    {
        $t = new FakeTransport([
            FakeTransport::json(self::answer([['id' => '1'], ['id' => '2']], 'c1')),
            FakeTransport::json(self::answer([['id' => '3']], null)),
        ]);
        $fb = new Facebook('k', transport: $t);

        $posts = iterator_to_array(
            Paginator::items(fn (?string $cursor) => $fb->getPagePosts('nasa', ['end_cursor' => $cursor]), pages: 5),
            false,
        );

        self::assertSame(['1', '2', '3'], array_column($posts, 'id'));
        self::assertCount(2, $t->requests);
        self::assertArrayNotHasKey('end_cursor', $t->query(0));
        self::assertSame('c1', $t->query(1)['end_cursor']);
    }

    public function testPagesIsACeilingAndARepeatedCursorStops(): void
    {
        $calls = 0;
        $fetch = function (?string $cursor) use (&$calls): array {
            $calls++;
            return self::answer([['id' => (string) $calls]], 'same');
        };
        iterator_to_array(Paginator::items($fetch, pages: 1), false);
        self::assertSame(1, $calls);

        $calls = 0;
        iterator_to_array(Paginator::items($fetch, pages: 5), false);
        self::assertSame(2, $calls);
    }

    public function testItemsOfReadsAdsGroups(): void
    {
        $body = ['data' => ['ads' => [[['ad_archive_id' => '1'], ['ad_archive_id' => '2']], [['ad_archive_id' => '3']]]]];
        self::assertSame([['ad_archive_id' => '1'], ['ad_archive_id' => '3']], Paginator::itemsOf($body));
    }

    public function testShapes(): void
    {
        self::assertSame([], Paginator::itemsOf(['data' => ['posts' => []]]));
        self::assertSame([['x' => 1]], Paginator::itemsOf(['data' => [['x' => 1]]]));
        self::assertNull(Paginator::nextCursor(['meta' => ['end_cursor' => 'not-data']]));
        self::assertSame('top', Paginator::nextCursor(['data' => ['end_cursor' => 'top']]));
    }

    public function testInvalidPages(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        iterator_to_array(Paginator::pages(fn () => [], pages: 0));
    }
}
