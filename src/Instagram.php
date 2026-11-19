<?php

declare(strict_types=1);

namespace SocialAPIs;

/**
 * Instagram public data: profiles, posts, reels, highlights, search and locations.
 *
 *     $ig = new SocialAPIs\Instagram('YOUR_API_TOKEN'); // or set SOCIALAPIS_TOKEN
 *     $profile = $ig->getProfileDetails('nasa');
 *
 * Every method returns the decoded JSON answer as an array; `$extra` passes
 * any other query parameter (`end_cursor`…). Parameters per endpoint:
 * https://socialapis.io/api-sources.
 */
final class Instagram extends Client
{
    /** Numeric user ID from a username or profile URL. */
    public function getUserId(string $profile, array $extra = []): array
    {
        $profile = trim($profile);
        $link = str_starts_with($profile, 'http://') || str_starts_with($profile, 'https://')
            ? $profile
            : 'https://www.instagram.com/' . trim($profile, '/@');
        return $this->request('/instagram/user/id', self::params(['link' => $link], $extra));
    }

    /** Public profile details (followers, bio, counts). Unwraps the "data" envelope. */
    public function getProfileDetails(string $username, array $extra = []): array
    {
        $body = $this->request('/instagram/profile/details', self::params(['username' => ltrim($username, '@')], $extra));
        return isset($body['data']) && is_array($body['data']) ? $body['data'] : $body;
    }

    /** Recent posts of a profile. */
    public function getProfilePosts(string $username, array $extra = []): array
    {
        return $this->request('/instagram/profile/posts', self::params(['username' => ltrim($username, '@')], $extra));
    }

    /** Reels of a profile, by user ID (see getUserId). */
    public function getProfileReels(string $userId, array $extra = []): array
    {
        return $this->request('/instagram/profile/reels', self::params(['user_id' => $userId], $extra));
    }

    /** Story highlights of a profile, by user ID. */
    public function getProfileHighlights(string $userId, array $extra = []): array
    {
        return $this->request('/instagram/profile/highlights', self::params(['user_id' => $userId], $extra));
    }

    /** Stories inside one highlight. */
    public function getHighlightDetails(string $highlightId, array $extra = []): array
    {
        return $this->request('/instagram/highlight/details', self::params(['highlight_id' => $highlightId], $extra));
    }

    /** Shortcode / ID of a post from its URL. */
    public function getPostId(string $postUrl, array $extra = []): array
    {
        return $this->request('/instagram/post/id', self::params(['link' => $postUrl], $extra));
    }

    /** Details of a post by shortcode: media, likes, caption. */
    public function getPostDetails(string $shortcode, array $extra = []): array
    {
        return $this->request('/instagram/post/details', self::params(['shortcode' => $shortcode], $extra));
    }

    /** The trending reels feed. */
    public function getReelsFeed(array $extra = []): array
    {
        return $this->request('/instagram/reels/feed', $extra);
    }

    /** Reels using one audio track. */
    public function getReelsByAudio(string $audioId, array $extra = []): array
    {
        return $this->request('/instagram/reels/audio', self::params(['audio_id' => $audioId], $extra));
    }

    /** Users, hashtags and places matching a keyword. */
    public function search(string $keyword, array $extra = []): array
    {
        return $this->request('/instagram/search', self::params(['keyword' => $keyword], $extra));
    }

    /** Posts tagged at a location. */
    public function getLocationPosts(string $locationId, array $extra = []): array
    {
        return $this->request('/instagram/location/posts', self::params(['location_id' => $locationId], $extra));
    }

    /** Locations near another location. */
    public function getNearbyLocations(string $locationId, array $extra = []): array
    {
        return $this->request('/instagram/location/nearby', self::params(['location_id' => $locationId], $extra));
    }
}
