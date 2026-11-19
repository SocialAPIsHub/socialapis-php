# SocialAPIs PHP SDK: Facebook and Instagram public data

[![Packagist](https://img.shields.io/packagist/v/socialapishub/socialapis?label=Packagist&logo=packagist&logoColor=white)](https://packagist.org/packages/socialapishub/socialapis)
[![Tests](https://github.com/SocialAPIsHub/socialapis-php/actions/workflows/test.yml/badge.svg)](https://github.com/SocialAPIsHub/socialapis-php/actions/workflows/test.yml)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-777bb4.svg)](https://www.php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Public Facebook and Instagram data from PHP: Pages, posts, groups, comments, the **Meta Ads Library**, **Marketplace**, Instagram profiles, posts and reels. No OAuth, no Meta app review, no scraping code to maintain. Works in Laravel, Symfony, WordPress or plain PHP; the only requirement is the curl extension.

Powered by the [SocialAPIs](https://socialapis.io) REST API. **[Get a free API key](https://socialapis.io/auth/signup)**: 200 calls/month, no card.

```bash
composer require socialapishub/socialapis
```

```php
use SocialAPIs\Facebook;
use SocialAPIs\Instagram;

$fb = new Facebook('YOUR_API_TOKEN');           // or new Facebook() with SOCIALAPIS_TOKEN set

$page = $fb->getPageInfo('nasa');
echo $page['title'] . ': ' . $page['followers_count'] . " followers\n";

$posts = $fb->getPagePosts('nasa', ['limit' => 9]);

$ig = new Instagram('YOUR_API_TOKEN');
$profile = $ig->getProfileDetails('nasa');
```

Every method returns the decoded JSON answer as an array. The second argument passes any other parameter of the endpoint (filters, `limit`, `end_cursor`…); each endpoint's parameters and an example answer are on [socialapis.io/api-sources](https://socialapis.io/api-sources).

## Pagination

`Paginator` follows `end_cursor` for you. Each page is one API call; `pages` is a hard ceiling, and it stops early when there are no more results.

```php
use SocialAPIs\Paginator;

$posts = Paginator::items(
    fn (?string $cursor) => $fb->getPagePosts('nasa', ['limit' => 9, 'end_cursor' => $cursor]),
    pages: 3,
);
foreach ($posts as $post) {
    // one post at a time, across up to 3 calls
}
```

`Paginator::pages()` yields the raw answers instead; `Paginator::itemsOf($answer)` gives the records of one answer.

## Meta Ads Library

```php
// Active ads about a topic in the US, most impressions first
$answer = $fb->searchAds('running shoes', ['country' => 'US', 'activeStatus' => 'Active', 'sort_data' => 'impressions']);

// All ads of one advertiser (ad_page_id from getPageInfo() or searchAdsByKeywords())
$ads = Paginator::items(
    fn (?string $cursor) => $fb->searchAds(null, ['ad_page_id' => '123456789', 'country' => 'ALL', 'end_cursor' => $cursor]),
    pages: 2,
);
```

`data.ads` is a list of groups of near-identical ads; `Paginator` gives you the first ad of each group.

## Methods

| Area | `Facebook` methods |
|---|---|
| Pages | `getPageId`, `getPageInfo`, `getPagePosts`, `getPageReels`, `getPageVideos`, `getPageAlbums` |
| Groups | `getGroupId`, `getGroupDetails`, `getGroupPosts`, `getGroupVideos`, `getGroupAlbums`, `getAlbumItems` |
| Posts | `getPostId`, `getPostDetails`, `getPostDetailsExtended`, `getPostComments`, `getCommentReplies`, `getPostAttachments`, `getVideoPostDetails` |
| Search | `searchPages`, `searchPeople`, `searchLocations`, `searchPosts`, `searchVideos` |
| Ads Library | `getAdsCountries`, `searchAds`, `getAdsPageDetails`, `getAdArchiveDetails`, `searchAdsByKeywords` |
| Marketplace | `searchMarketplace`, `getListingDetails`, `getSellerDetails`, `getMarketplaceCategories`, `getCityCoordinates`, `searchVehicles`, `searchRentals` |
| Media | `downloadMedia` |

`Instagram`: `getUserId`, `getProfileDetails`, `getProfilePosts`, `getProfileReels`, `getProfileHighlights`, `getHighlightDetails`, `getPostId`, `getPostDetails`, `getReelsFeed`, `getReelsByAudio`, `search`, `getLocationPosts`, `getNearbyLocations`.

`Account` (free calls): `getUsage`, `getTopUps`, `getLimits`.

A newer endpoint that has no method yet works through `request()`: `$fb->request('/facebook/pages/posts', ['link' => 'https://www.facebook.com/nasa'])`.

## Errors

Every exception extends `SocialAPIs\Exception\SocialAPIsException`. Failed calls are not charged.

| Exception | When |
|---|---|
| `AuthenticationException` | 401: the key is missing, wrong or was replaced |
| `InsufficientCreditsException` | Not enough credits left: [top up or upgrade](https://socialapis.io/pricing) |
| `AccessDeniedException` | 403: account deactivated or plan expired (`errorCode()`) |
| `RateLimitException` | 429: too many calls (`retryAfterSeconds`) |
| `BadRequestException` | Other 4xx: a missing or invalid parameter |
| `ApiServerException` | 5xx: retry later |
| `ApiConnectionException` | Network error or timeout |

API errors carry `statusCode`, `requestId` and the decoded `body`.

## Options

```php
$fb = new Facebook(
    apiToken: 'YOUR_API_TOKEN',
    timeoutSeconds: 60,
    transport: $myTransport,   // any SocialAPIs\Http\Transport (your own HTTP client, a proxy, a test fake)
);
```

## Costs

Most calls cost 1 credit; Page and group posts cost round(limit ÷ 3) credits. Calls that return data are charged, including empty results; errors (4xx / 5xx) are not. Plans: [socialapis.io/pricing](https://socialapis.io/pricing).

## More

- [Endpoint catalog](https://socialapis.io/api-sources) · [API docs](https://docs.socialapis.io) · [Status](https://socialapis.io/status)
- Other SDKs: [Python](https://github.com/SocialAPIsHub/socialapis-python), [JavaScript / TypeScript](https://github.com/SocialAPIsHub/socialapis-js), [Go](https://github.com/SocialAPIsHub/socialapis-go)
- For AI agents: [SocialAPIs MCP server](https://github.com/SocialAPIsHub/mcp-server) · No code: [n8n node](https://github.com/SocialAPIsHub/n8n-nodes-socialapis)
- Support: [support@socialapis.io](mailto:support@socialapis.io) · [Telegram](https://t.me/socialapis)

Public data only. You are responsible for how you use it, including personal data rules (GDPR) where they apply.

## License

MIT
