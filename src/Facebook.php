<?php

declare(strict_types=1);

namespace SocialAPIs;

/**
 * Facebook public data: Pages, groups, posts, comments, search, the Meta Ads
 * Library, Marketplace and media.
 *
 *     $fb = new SocialAPIs\Facebook('YOUR_API_TOKEN'); // or set SOCIALAPIS_TOKEN
 *     $page = $fb->getPageInfo('nasa');
 *     echo $page['title'], ' ', $page['followers_count'];
 *
 * Every method returns the decoded JSON answer as an array. `$extra` passes
 * any other query parameter of the endpoint (filters, `end_cursor`, `limit`…);
 * the parameters of each endpoint are listed at https://socialapis.io/api-sources.
 */
final class Facebook extends Client
{
    // ---- Pages --------------------------------------------------------------

    /** Numeric Page ID from a Page URL or username. */
    public function getPageId(string $page, array $extra = []): array
    {
        return $this->request('/facebook/pages/id', self::params(['link' => self::facebookUrl($page)], $extra));
    }

    /** Public details of a Page (name, followers, category, contact, links). Unwraps the "0" envelope. */
    public function getPageInfo(string $page, array $extra = []): array
    {
        $body = $this->request('/facebook/pages/details', self::params(['link' => self::facebookUrl($page)], $extra));
        return isset($body['0']) && is_array($body['0']) ? $body['0'] : $body;
    }

    /** Recent posts of a Page. `limit` 3-9 (cost round(limit / 3) credits), `end_cursor`, `after_time`, `before_time`. */
    public function getPagePosts(string $page, array $extra = []): array
    {
        return $this->request('/facebook/pages/posts', self::params(['link' => self::facebookUrl($page)], $extra));
    }

    /** Reels of a Page. */
    public function getPageReels(string $page, array $extra = []): array
    {
        return $this->request('/facebook/pages/reels', self::params(['link' => self::facebookUrl($page)], $extra));
    }

    /** Long-form videos of a Page. */
    public function getPageVideos(string $page, array $extra = []): array
    {
        return $this->request('/facebook/pages/videos', self::params(['link' => self::facebookUrl($page)], $extra));
    }

    /** Photo albums of a Page, by numeric Page ID (see getPageId). */
    public function getPageAlbums(string $profileId, array $extra = []): array
    {
        return $this->request('/facebook/pages/albums', self::params(['profile_id' => $profileId], $extra));
    }

    // ---- Groups -------------------------------------------------------------

    /** Numeric group ID from a group URL, slug or ID. */
    public function getGroupId(string $group, array $extra = []): array
    {
        return $this->request('/facebook/groups/id', self::params(['link' => self::groupUrl($group)], $extra));
    }

    /** Details of a public group (members, privacy, description). */
    public function getGroupDetails(string $group, array $extra = []): array
    {
        return $this->request('/facebook/groups/details', self::params(['link' => self::groupUrl($group)], $extra));
    }

    /** Recent posts of a public group. Same options as getPagePosts. */
    public function getGroupPosts(string $group, array $extra = []): array
    {
        return $this->request('/facebook/groups/posts', self::params(['link' => self::groupUrl($group)], $extra));
    }

    /** Videos posted in a public group. */
    public function getGroupVideos(string $group, array $extra = []): array
    {
        return $this->request('/facebook/groups/videos', self::params(['link' => self::groupUrl($group)], $extra));
    }

    /** Photo albums of a public group, by numeric group ID (see getGroupId). */
    public function getGroupAlbums(string $groupId, array $extra = []): array
    {
        return $this->request('/facebook/groups/albums', self::params(['group_id' => $groupId], $extra));
    }

    /** Photos and videos in a Page or group album. */
    public function getAlbumItems(string $albumId, array $extra = []): array
    {
        return $this->request('/facebook/albums/items', self::params(['album_id' => $albumId], $extra));
    }

    // ---- Posts --------------------------------------------------------------

    /** Numeric post ID from a post URL. */
    public function getPostId(string $postUrl, array $extra = []): array
    {
        return $this->request('/facebook/posts/id', self::params(['link' => $postUrl], $extra));
    }

    /** Details of a post: text, media, reactions, author. */
    public function getPostDetails(string $postUrl, array $extra = []): array
    {
        return $this->request('/facebook/posts/details', self::params(['link' => $postUrl], $extra));
    }

    /** Extended post details: views, video URLs, music, author verification. */
    public function getPostDetailsExtended(string $postUrl, array $extra = []): array
    {
        return $this->request('/facebook/posts/details/extended', self::params(['link' => $postUrl], $extra));
    }

    /** Comments on a post or reel. Pass `include_reply_info` => true for getCommentReplies. */
    public function getPostComments(string $postUrl, array $extra = []): array
    {
        return $this->request('/facebook/posts/comments', self::params(['link' => $postUrl], $extra));
    }

    /** Replies to a comment (both values come from getPostComments with include_reply_info). */
    public function getCommentReplies(string $commentFeedbackId, string $expansionToken, array $extra = []): array
    {
        return $this->request('/facebook/posts/comments/replies', self::params([
            'comment_feedback_id' => $commentFeedbackId,
            'expansion_token' => $expansionToken,
        ], $extra));
    }

    /** Photos and videos attached to a post. */
    public function getPostAttachments(string $postId, array $extra = []): array
    {
        return $this->request('/facebook/posts/attachments', self::params(['post_id' => $postId], $extra));
    }

    /** Title, reactions and play counts of a video post. */
    public function getVideoPostDetails(string $videoId, array $extra = []): array
    {
        return $this->request('/facebook/posts/video', self::params(['video_id' => $videoId], $extra));
    }

    // ---- Search -------------------------------------------------------------

    /** Pages matching a keyword (optionally near a location_uid from searchLocations). */
    public function searchPages(string $query, array $extra = []): array
    {
        return $this->request('/facebook/search/pages', self::params(['query' => $query], $extra));
    }

    /** Public profiles matching a name. */
    public function searchPeople(string $query, array $extra = []): array
    {
        return $this->request('/facebook/search/people', self::params(['query' => $query], $extra));
    }

    /** Locations matching a keyword, with the uids other searches take. */
    public function searchLocations(string $query, array $extra = []): array
    {
        return $this->request('/facebook/search/locations', self::params(['query' => $query], $extra));
    }

    /** Public posts matching a keyword. */
    public function searchPosts(string $query, array $extra = []): array
    {
        return $this->request('/facebook/search/posts', self::params(['query' => $query], $extra));
    }

    /** Public videos matching a keyword. */
    public function searchVideos(string $query, array $extra = []): array
    {
        return $this->request('/facebook/search/videos', self::params(['query' => $query], $extra));
    }

    // ---- Meta Ads Library ---------------------------------------------------

    /** Country codes the Ads Library accepts. */
    public function getAdsCountries(array $extra = []): array
    {
        return $this->request('/facebook/ads/countries', $extra);
    }

    /**
     * Ads by keyword (`query`) or advertiser (`ad_page_id` in $extra, with $query null).
     * Options: `country` (ISO code or ALL), `activeStatus` (Active / Inactive / ALL),
     * `sort_data` (impressions / recent), `after_time`, `before_time`, `end_cursor`.
     * `data.ads` is a list of groups of near-identical ads.
     */
    public function searchAds(?string $query, array $extra = []): array
    {
        return $this->request('/facebook/ads/search', self::params(['query' => $query], $extra));
    }

    /** Ads Library information about an advertiser's Page. */
    public function getAdsPageDetails(string $pageId, array $extra = []): array
    {
        return $this->request('/facebook/ads/page-details', self::params(['page_id' => $pageId], $extra));
    }

    /** Full details of one ad. */
    public function getAdArchiveDetails(string $adArchiveId, ?string $pageId = null, array $extra = []): array
    {
        return $this->request('/facebook/ads/archive-details', self::params([
            'ad_archive_id' => $adArchiveId,
            'page_id' => $pageId,
        ], $extra));
    }

    /** Advertisers and keywords matching a name (data.page_results holds the advertisers). */
    public function searchAdsByKeywords(string $query, array $extra = []): array
    {
        return $this->request('/facebook/ads/keywords', self::params(['query' => $query], $extra));
    }

    // ---- Marketplace --------------------------------------------------------

    /** Listings for sale. Filters: filter_location_latitude / _longitude, filter_price_max, sort_by… */
    public function searchMarketplace(string $query, array $extra = []): array
    {
        return $this->request('/facebook/marketplace/search', self::params(['query' => $query], $extra));
    }

    /** One listing: photos, price, seller, delivery. */
    public function getListingDetails(string $listingId, array $extra = []): array
    {
        return $this->request('/facebook/marketplace/listing', self::params(['listing_id' => $listingId], $extra));
    }

    /** A seller's profile, ratings and badges. */
    public function getSellerDetails(string $sellerId, array $extra = []): array
    {
        return $this->request('/facebook/marketplace/seller', self::params(['seller_id' => $sellerId], $extra));
    }

    /** Marketplace categories with their IDs. */
    public function getMarketplaceCategories(array $extra = []): array
    {
        return $this->request('/facebook/marketplace/categories', $extra);
    }

    /** GPS coordinates of a city, for the location filters. */
    public function getCityCoordinates(string $city, array $extra = []): array
    {
        return $this->request('/facebook/marketplace/city-coordinates', self::params(['city' => $city], $extra));
    }

    /** Vehicle listings near filter_location_latitude / filter_location_longitude. */
    public function searchVehicles(array $extra = []): array
    {
        return $this->request('/facebook/marketplace/vehicles', $extra);
    }

    /** Rental listings near filter_location_latitude / filter_location_longitude. */
    public function searchRentals(array $extra = []): array
    {
        return $this->request('/facebook/marketplace/rentals', $extra);
    }

    // ---- Media --------------------------------------------------------------

    /** Direct download URL of a Facebook video or photo. */
    public function downloadMedia(string $url, array $extra = []): array
    {
        return $this->request('/facebook/media/download', self::params(['url' => $url], $extra));
    }

    private static function groupUrl(string $group): string
    {
        return self::facebookUrl($group, 'https://www.facebook.com/groups/');
    }
}
