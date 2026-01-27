<?php

namespace NewSong\PodcastLinkFinder\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;

class YouTubeLivestreamService
{
    protected Client $client;
    protected string $apiKey;
    protected string $channelId;
    protected string $baseUrl = 'https://www.googleapis.com/youtube/v3/';

    public function __construct()
    {
        $this->apiKey = config('youtube-livestream.api_key') ?? config('podcast-link-finder.youtube.api_key');
        $this->channelId = $this->resolveChannelId();

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
        ]);
    }

    /**
     * Resolve channel ID from handle or direct ID
     */
    protected function resolveChannelId(): string
    {
        $channelHandle = config('youtube-livestream.channel_handle', '@newsongchurchokc');
        $channelId = config('youtube-livestream.channel_id') ?? config('podcast-link-finder.youtube.channel_id');

        // If we have a direct channel ID, use it
        if ($channelId && ! str_starts_with($channelId, '@')) {
            return $channelId;
        }

        // Otherwise use the handle to look up the channel ID
        // Note: This requires an additional API call, so prefer setting channel_id directly
        return $channelId ?? '';
    }

    /**
     * Get upcoming/scheduled livestreams from the channel
     */
    public function getUpcomingLivestreams(int $maxResults = 10): array
    {
        try {
            $params = [
                'part' => 'snippet',
                'channelId' => $this->channelId,
                'eventType' => 'upcoming',
                'type' => 'video',
                'order' => 'date',
                'maxResults' => $maxResults,
                'key' => $this->apiKey,
            ];

            $response = $this->client->get('search', [
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $videos = $data['items'] ?? [];

            // Get detailed info including scheduled start time
            $results = [];
            foreach ($videos as $video) {
                $videoId = $video['id']['videoId'] ?? null;
                if (! $videoId) {
                    continue;
                }

                // Get video details for scheduled start time
                $details = $this->getVideoDetails($videoId);

                $results[] = [
                    'video_id' => $videoId,
                    'url' => "https://www.youtube.com/watch?v={$videoId}",
                    'title' => $video['snippet']['title'] ?? '',
                    'description' => $video['snippet']['description'] ?? '',
                    'thumbnail' => $video['snippet']['thumbnails']['high']['url']
                        ?? $video['snippet']['thumbnails']['default']['url']
                        ?? '',
                    'scheduled_start' => $details['scheduled_start'] ?? null,
                    'live_status' => $details['live_status'] ?? 'upcoming',
                ];
            }

            // Sort by scheduled start time (earliest first)
            usort($results, function ($a, $b) {
                $aTime = $a['scheduled_start'] ? strtotime($a['scheduled_start']) : PHP_INT_MAX;
                $bTime = $b['scheduled_start'] ? strtotime($b['scheduled_start']) : PHP_INT_MAX;

                return $aTime <=> $bTime;
            });

            return $results;
        } catch (\Exception $e) {
            \Log::error('[YouTube Livestream] Failed to fetch upcoming livestreams: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get video details including scheduled start time
     */
    public function getVideoDetails(string $videoId): array
    {
        try {
            $response = $this->client->get('videos', [
                'query' => [
                    'part' => 'snippet,liveStreamingDetails,status',
                    'id' => $videoId,
                    'key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $video = $data['items'][0] ?? null;

            if (! $video) {
                return [];
            }

            $liveDetails = $video['liveStreamingDetails'] ?? [];

            return [
                'scheduled_start' => $liveDetails['scheduledStartTime'] ?? null,
                'actual_start' => $liveDetails['actualStartTime'] ?? null,
                'actual_end' => $liveDetails['actualEndTime'] ?? null,
                'live_status' => $video['snippet']['liveBroadcastContent'] ?? 'none',
                'privacy_status' => $video['status']['privacyStatus'] ?? 'public',
            ];
        } catch (\Exception $e) {
            \Log::warning('[YouTube Livestream] Failed to get video details for '.$videoId.': '.$e->getMessage());

            return [];
        }
    }

    /**
     * Find a livestream by date
     * Returns the earliest livestream scheduled for the given date
     *
     * @param  string  $date  Date in Y-m-d format
     */
    public function findLivestreamByDate(string $date): ?array
    {
        $livestreams = $this->getUpcomingLivestreams(20);
        $targetDate = Carbon::parse($date)->format('Y-m-d');
        $timezone = config('youtube-livestream.schedule.timezone', 'America/Chicago');

        $matchingStreams = [];

        foreach ($livestreams as $stream) {
            if (! $stream['scheduled_start']) {
                continue;
            }

            // Convert scheduled start to target timezone and compare dates
            $scheduledDate = Carbon::parse($stream['scheduled_start'])
                ->timezone($timezone)
                ->format('Y-m-d');

            if ($scheduledDate === $targetDate) {
                $matchingStreams[] = $stream;
            }
        }

        if (empty($matchingStreams)) {
            \Log::info("[YouTube Livestream] No livestream found for date: {$date}");

            return null;
        }

        // Return the earliest one (array is already sorted)
        $selected = $matchingStreams[0];
        \Log::info("[YouTube Livestream] Found livestream for {$date}: {$selected['title']}");

        return $selected;
    }

    /**
     * Check if a YouTube URL is still valid and represents an upcoming/live stream
     */
    public function isValidLivestreamUrl(string $url): bool
    {
        $videoId = $this->extractVideoId($url);

        if (! $videoId) {
            return false;
        }

        $details = $this->getVideoDetails($videoId);

        if (empty($details)) {
            return false;
        }

        // Check if video is public
        if (($details['privacy_status'] ?? 'public') !== 'public') {
            \Log::info("[YouTube Livestream] Video {$videoId} is not public");

            return false;
        }

        // Check if it's upcoming or live (not ended/none)
        $liveStatus = $details['live_status'] ?? 'none';

        // Valid statuses: upcoming, live
        // Invalid statuses: none (regular video or ended stream)
        if (in_array($liveStatus, ['upcoming', 'live'])) {
            return true;
        }

        // If it has actual_end time, the stream has ended
        if (! empty($details['actual_end'])) {
            \Log::info("[YouTube Livestream] Video {$videoId} stream has ended");

            return false;
        }

        // If it had a scheduled start but no live status, check if it's in the past
        if (! empty($details['scheduled_start'])) {
            $scheduledStart = Carbon::parse($details['scheduled_start']);
            $timezone = config('youtube-livestream.schedule.timezone', 'America/Chicago');

            // If scheduled for future, it's still valid
            if ($scheduledStart->isFuture()) {
                return true;
            }

            // If scheduled for today and within 4 hours of start time, consider it live
            if ($scheduledStart->isToday() && $scheduledStart->diffInHours(now()) <= 4) {
                return true;
            }
        }

        \Log::info("[YouTube Livestream] Video {$videoId} is not a valid upcoming/live stream (status: {$liveStatus})");

        return false;
    }

    /**
     * Extract video ID from various YouTube URL formats
     */
    public function extractVideoId(string $url): ?string
    {
        // Handle various YouTube URL formats
        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/live\/([a-zA-Z0-9_-]{11})/',
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Test the YouTube API connection
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
                    'type' => 'video',
                    'maxResults' => 1,
                    'key' => $this->apiKey,
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'message' => 'YouTube API connection successful',
                ];
            }

            return [
                'success' => false,
                'message' => 'YouTube API returned status: '.$response->getStatusCode(),
            ];
        } catch (\Exception $e) {
            \Log::error('[YouTube Livestream] API connection test failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'YouTube API connection failed. Please check the logs for details.',
            ];
        }
    }

    /**
     * Check if the service is properly configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->channelId);
    }
}
