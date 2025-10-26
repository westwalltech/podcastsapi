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
                $startDate = $date->modify('-7 days')->format('Y-m-d\TH:i:s\Z');
                $endDate = $date->modify('+14 days')->format('Y-m-d\TH:i:s\Z');

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
                $startDate = $date->modify('-7 days')->format('Y-m-d\TH:i:s\Z');
                $endDate = $date->modify('+14 days')->format('Y-m-d\TH:i:s\Z');

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
            \Log::error('YouTube API Error: ' . $e->getMessage());
            return [];
        }
    }
}
