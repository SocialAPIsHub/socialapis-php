<?php

declare(strict_types=1);

namespace SocialAPIs;

/**
 * Follow `end_cursor` across pages of any listing endpoint.
 *
 *     $posts = Paginator::items(
 *         fn (?string $cursor) => $fb->getPagePosts('nasa', ['end_cursor' => $cursor]),
 *         pages: 3,
 *     );
 *     foreach ($posts as $post) { ... }
 *
 * The cursor's place differs by endpoint (usually `page_info.end_cursor` next
 * to `has_next_page`), so it is looked for rather than read from a fixed path.
 * Every page is one billed call: `pages` is a hard ceiling, and paging stops
 * early when there is no cursor, `has_next_page` is false or a cursor repeats.
 */
final class Paginator
{
    /** Our own envelope around every answer: never data, never a cursor. */
    private const ENVELOPE_KEYS = ['meta', 'message'];

    /**
     * Each raw answer, one per page.
     *
     * @param callable(?string): array<string|int, mixed> $fetch
     *
     * @return \Generator<int, array<string|int, mixed>>
     */
    public static function pages(callable $fetch, int $pages = 1): \Generator
    {
        if ($pages < 1) {
            throw new \InvalidArgumentException('pages must be 1 or more');
        }
        $cursor = null;
        $seen = [];
        for ($i = 0; $i < $pages; $i++) {
            $body = $fetch($cursor);
            yield $body;
            $cursor = self::nextCursor($body);
            if ($cursor === null || isset($seen[$cursor])) {
                return;
            }
            $seen[$cursor] = true;
        }
    }

    /**
     * The records of every page (posts, comments, ads…), one by one.
     *
     * @param callable(?string): array<string|int, mixed> $fetch
     *
     * @return \Generator<int, array<string|int, mixed>>
     */
    public static function items(callable $fetch, int $pages = 1): \Generator
    {
        foreach (self::pages($fetch, $pages) as $body) {
            yield from self::itemsOf($body);
        }
    }

    /**
     * The cursor for the next page, or null when there is none.
     *
     * @param array<string|int, mixed> $body
     */
    public static function nextCursor(array $body): ?string
    {
        $found = self::findCursor($body, 0);
        return is_string($found) ? $found : null;
    }

    /**
     * The records of one answer: the longest list of objects outside our
     * envelope (`posts`, `items`, `videos`… depending on the endpoint).
     * Meta Ads search groups near-identical ads; each group gives its first ad.
     *
     * @param array<string|int, mixed> $body
     *
     * @return list<array<string|int, mixed>>
     */
    public static function itemsOf(array $body): array
    {
        $ads = self::adsOf($body);
        if ($ads !== null) {
            return $ads;
        }
        return self::bestList($body, 0) ?? [];
    }

    /**
     * @param array<string|int, mixed> $body
     *
     * @return list<array<string|int, mixed>>|null
     */
    private static function adsOf(array $body): ?array
    {
        $node = $body;
        for ($i = 0; $i < 4 && is_array($node); $i++) {
            if (isset($node['ads']) && is_array($node['ads']) && array_is_list($node['ads'])) {
                $out = [];
                foreach ($node['ads'] as $group) {
                    $ad = is_array($group) && array_is_list($group) ? ($group[0] ?? null) : $group;
                    if (is_array($ad) && !array_is_list($ad)) {
                        $out[] = $ad;
                    }
                }
                return $out;
            }
            $node = $node['data'] ?? null;
        }
        return null;
    }

    /**
     * @return list<array<string|int, mixed>>|null
     */
    private static function bestList(mixed $node, int $depth): ?array
    {
        if ($depth > 3 || !is_array($node)) {
            return null;
        }
        if (array_is_list($node)) {
            if ($node === []) {
                return null;
            }
            foreach ($node as $item) {
                if (!is_array($item) || ($item !== [] && array_is_list($item))) {
                    return null;
                }
            }
            return $node;
        }
        $best = null;
        foreach ($node as $key => $value) {
            if (in_array($key, self::ENVELOPE_KEYS, true)) {
                continue;
            }
            $found = self::bestList($value, $depth + 1);
            if ($found !== null && ($best === null || count($found) > count($best))) {
                $best = $found;
            }
        }
        return $best;
    }

    private static function findCursor(mixed $node, int $depth): string|false|null
    {
        if ($depth > 4 || !is_array($node) || array_is_list($node)) {
            return null;
        }
        $info = $node['page_info'] ?? null;
        if (is_array($info)) {
            if (($info['has_next_page'] ?? null) === false) {
                return false;
            }
            if (isset($info['end_cursor']) && is_string($info['end_cursor']) && $info['end_cursor'] !== '') {
                return $info['end_cursor'];
            }
        }
        if (isset($node['end_cursor']) && is_string($node['end_cursor']) && $node['end_cursor'] !== '') {
            return $node['end_cursor'];
        }
        foreach ($node as $key => $value) {
            if (in_array($key, self::ENVELOPE_KEYS, true)) {
                continue;
            }
            $found = self::findCursor($value, $depth + 1);
            if ($found !== null) {
                return $found;
            }
        }
        return null;
    }
}
