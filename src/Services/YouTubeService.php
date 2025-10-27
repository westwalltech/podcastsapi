<?php

namespace NewSong\PodcastLinkFinder\Services;

use GuzzleHttp\Client;

class YouTubeService
{
    protected Client $client;
    protected string $apiKey;
    protected string $channelId;
    protected string $baseUrl = 'https://www.googleapis.com/youtube/v3/';

    public function __construct()
    {
        $this->apiKey = config('podcast-link-finder.youtube.api_key');
        $this->channelId = config('podcast-link-finder.youtube.channel_id');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
        ]);
    }

    /**
     * Check if YouTube search is allowed today based on configured search days
     *
     * @return bool
     */
    public function isSearchAllowedToday(): bool
    {
        $allowedDays = config('podcast-link-finder.youtube.search_days', []);

        // If empty array, allow all days
        if (empty($allowedDays)) {
            return true;
        }

        $currentDay = now()->format('l'); // 'l' = full day name like 'Sunday'

        return in_array($currentDay, $allowedDays);
    }

    /**
     * Get a friendly message about when YouTube search is available
     *
     * @return string|null
     */
    public function getSearchRestrictionMessage(): ?string
    {
        if ($this->isSearchAllowedToday()) {
            return null;
        }

        $allowedDays = config('podcast-link-finder.youtube.search_days', []);

        if (empty($allowedDays)) {
            return null;
        }

        if (count($allowedDays) === 1) {
            return "YouTube search only available on {$allowedDays[0]}s to conserve API quota. Please enter URL manually or wait until {$allowedDays[0]}.";
        }

        $days = implode(', ', $allowedDays);
        return "YouTube search only available on: {$days}. Please enter URL manually.";
    }

    /**
     * Search for a video by title in the channel
     *
     * @param string $title
     * @param string|null $publishDate
     * @return string|null Video URL
     */
    public function findVideoByTitle(string $title, ?string $publishDate = null): ?string
    {
        try {
            $params = [
                'part' => 'snippet',
                'channelId' => $this->channelId,
                'q' => $title,
                'type' => 'video',
                'maxResults' => 10,
                'key' => $this->apiKey,
            ];

            // Add date filter if provided
            if ($publishDate) {
                $date = new \DateTime($publishDate);
                $startDate = (clone $date)->modify('-7 days')->format('Y-m-d\TH:i:s\Z');
                $endDate = (clone $date)->modify('+7 days')->format('Y-m-d\TH:i:s\Z');

                $params['publishedAfter'] = $startDate;
                $params['publishedBefore'] = $endDate;
            }

            $response = $this->client->get('search', [
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $videos = $data['items'] ?? [];

            // Find best match
            $bestMatch = $this->findBestMatch($videos, $title);

            if ($bestMatch) {
                $videoId = $bestMatch['id']['videoId'];
                return "https://www.youtube.com/watch?v={$videoId}";
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('YouTube API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find the best matching video
     *
     * @param array $videos
     * @param string $title
     * @return array|null
     */
    protected function findBestMatch(array $videos, string $title): ?array
    {
        $bestScore = 0;
        $bestMatch = null;

        foreach ($videos as $video) {
            $videoTitle = $video['snippet']['title'] ?? '';
            $score = $this->calculateSimilarity($title, $videoTitle);

            if ($score > $bestScore && $score > 0.6) {
                $bestScore = $score;
                $bestMatch = $video;
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate similarity between two strings
     *
     * @param string $str1
     * @param string $str2
     * @return float
     */
    protected function calculateSimilarity(string $str1, string $str2): float
    {
        similar_text(strtolower($str1), strtolower($str2), $percent);
        return $percent / 100;
    }

    /**
     * Search for all matching videos and return them with scores
     *
     * @param string $title
     * @param string|null $publishDate
     * @return array
     */
    public function searchAllMatches(string $title, ?string $publishDate = null): array
    {
        // Check if search is allowed today
        if (!$this->isSearchAllowedToday()) {
            \Log::info('YouTube search skipped: Not an allowed search day');
            return [];
        }

        try {
            $params = [
                'part' => 'snippet',
                'channelId' => $this->channelId,
                'q' => $title,
                'type' => 'video',
                'maxResults' => 10,
                'order' => 'date',
                'key' => $this->apiKey,
            ];

            // Add date filter if provided
            if ($publishDate) {
                $date = new \DateTime($publishDate);
                $startDate = (clone $date)->modify('-7 days')->format('Y-m-d\TH:i:s\Z');
                $endDate = (clone $date)->modify('+7 days')->format('Y-m-d\TH:i:s\Z');

                $params['publishedAfter'] = $startDate;
                $params['publishedBefore'] = $endDate;
            }

            $response = $this->client->get('search', [
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $videos = $data['items'] ?? [];

            // Calculate scores for all videos
            $results = [];
            foreach ($videos as $video) {
                $videoTitle = $video['snippet']['title'] ?? '';
                $score = $this->calculateSimilarity($title, $videoTitle);

                if ($score > 0.3) { // Lower threshold to show more options
                    $videoId = $video['id']['videoId'];
                    $results[] = [
                        'url' => "https://www.youtube.com/watch?v={$videoId}",
                        'title' => $videoTitle,
                        'description' => $video['snippet']['description'] ?? '',
                        'published_at' => $video['snippet']['publishedAt'] ?? '',
                        'thumbnail' => $video['snippet']['thumbnails']['default']['url'] ?? '',
                        'score' => round($score * 100, 1),
                    ];
                }
            }

            // Sort by score descending
            usort($results, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            return $results;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            \Log::error('YouTube API Error: ' . $errorMessage);

            // Try to get detailed error information from response
            if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody()->getContents();
                $errorData = json_decode($body, true);

                if ($errorData && isset($errorData['error'])) {
                    $apiError = $errorData['error'];
                    $reason = $apiError['errors'][0]['reason'] ?? 'unknown';
                    $message = $apiError['message'] ?? 'Unknown error';

                    \Log::error('YouTube API Error Details:', [
                        'code' => $apiError['code'] ?? 'N/A',
                        'reason' => $reason,
                        'message' => strip_tags($message),
                    ]);

                    // Log specific guidance based on error reason
                    if ($reason === 'quotaExceeded') {
                        \Log::error('YouTube API quota exceeded. Check your quota at: https://console.cloud.google.com/apis/api/youtube.googleapis.com/quotas');
                    } elseif ($reason === 'forbidden') {
                        \Log::error('YouTube API access forbidden. Check API key permissions and restrictions.');
                    }
                }
            }

            // Return empty array - errors are logged for debugging
            return [];
        }
    }

    /**
     * Check if YouTube API is accessible
     *
     * @return array [success => bool, message => string]
     */
    public function testConnection(): array
    {
        try {
            $response = $this->client->get('search', [
                'query' => [
                    'part' => 'snippet',
                    'channelId' => $this->channelId,
                    'q' => 'test',
                    'type' => 'video',
                    'maxResults' => 1,
                    'key' => $this->apiKey,
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                return [
                    'success' => true,
                    'message' => 'YouTube API connection successful'
                ];
            }

            return [
                'success' => false,
                'message' => "YouTube API returned status code: {$statusCode}"
            ];
        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Try to get detailed error information from response
            if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody()->getContents();
                $errorData = json_decode($body, true);

                if ($errorData && isset($errorData['error'])) {
                    $apiError = $errorData['error'];
                    $reason = $apiError['errors'][0]['reason'] ?? 'unknown';

                    // Return specific message based on error reason
                    if ($reason === 'quotaExceeded') {
                        return [
                            'success' => false,
                            'message' => 'YouTube API quota exceeded. Quotas reset at midnight Pacific Time. Check usage at: console.cloud.google.com'
                        ];
                    } elseif ($reason === 'forbidden' || $reason === 'accessNotConfigured') {
                        return [
                            'success' => false,
                            'message' => 'YouTube API not enabled or access forbidden. Enable the API in Google Cloud Console.'
                        ];
                    }
                }
            }

            // Fallback to generic error messages based on status code
            if (str_contains($message, '403')) {
                return [
                    'success' => false,
                    'message' => 'YouTube API: Permission denied. Check API quota or key restrictions.'
                ];
            } elseif (str_contains($message, '400')) {
                return [
                    'success' => false,
                    'message' => 'YouTube API: Bad request. Check channel ID configuration.'
                ];
            }

            return [
                'success' => false,
                'message' => 'YouTube API Error: ' . $message
            ];
        }
    }
}
