<?php

namespace NewSong\PodcastLinkFinder\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class SpotifyService
{
    protected Client $client;
    protected string $clientId;
    protected string $clientSecret;
    protected string $showId;
    protected string $baseUrl = 'https://api.spotify.com/v1/';

    public function __construct()
    {
        $this->clientId = config('podcast-link-finder.spotify.client_id');
        $this->clientSecret = config('podcast-link-finder.spotify.client_secret');
        $this->showId = config('podcast-link-finder.spotify.show_id');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
        ]);
    }

    /**
     * Get access token (cached for 1 hour)
     *
     * @return string|null
     */
    protected function getAccessToken(): ?string
    {
        return Cache::remember('spotify_access_token', 3600, function () {
            try {
                $client = new Client();
                $response = $client->post('https://accounts.spotify.com/api/token', [
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                return $data['access_token'] ?? null;
            } catch (\Exception $e) {
                \Log::error('Spotify Auth Error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Search for an episode by title
     *
     * @param string $title
     * @param string|null $publishDate
     * @return string|null Episode URL
     */
    public function findEpisodeByTitle(string $title, ?string $publishDate = null): ?string
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        try {
            // First, get episodes from the show
            $response = $this->client->get("shows/{$this->showId}/episodes", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'query' => [
                    'limit' => 50,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $episodes = $data['items'] ?? [];

            // Find best match
            $bestMatch = $this->findBestMatch($episodes, $title, $publishDate);

            if ($bestMatch) {
                return $bestMatch['external_urls']['spotify'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Spotify API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find the best matching episode
     *
     * @param array $episodes
     * @param string $title
     * @param string|null $publishDate
     * @return array|null
     */
    protected function findBestMatch(array $episodes, string $title, ?string $publishDate = null): ?array
    {
        $bestScore = 0;
        $bestMatch = null;

        foreach ($episodes as $episode) {
            $score = $this->calculateSimilarity($title, $episode['name']);

            // Boost score if dates are close
            if ($publishDate && isset($episode['release_date'])) {
                $dateDiff = abs(strtotime($publishDate) - strtotime($episode['release_date']));
                $daysDiff = $dateDiff / 86400; // Convert to days

                if ($daysDiff <= 7) {
                    $score += 0.2;
                }
            }

            if ($score > $bestScore && $score > 0.6) {
                $bestScore = $score;
                $bestMatch = $episode;
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
     * Search for all matching episodes and return them with scores
     *
     * @param string $title
     * @param string|null $publishDate
     * @return array
     */
    public function searchAllMatches(string $title, ?string $publishDate = null): array
    {
        try {
            $token = $this->getAccessToken();

            if (!$token) {
                return [];
            }

            $response = $this->client->get("shows/{$this->showId}/episodes", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'query' => [
                    'limit' => 50,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $episodes = $data['items'] ?? [];

            // Calculate scores for all episodes
            $results = [];
            foreach ($episodes as $episode) {
                $episodeTitle = $episode['name'] ?? '';
                $score = $this->calculateSimilarity($title, $episodeTitle);

                // Boost score if publish dates are close
                if ($publishDate && !empty($episode['release_date'])) {
                    $targetDate = new \DateTime($publishDate);
                    $episodeDate = new \DateTime($episode['release_date']);
                    $daysDiff = abs($targetDate->diff($episodeDate)->days);

                    if ($daysDiff <= 7) {
                        $score += 0.2;
                    }
                }

                if ($score > 0.3) { // Lower threshold to show more options
                    $results[] = [
                        'url' => $episode['external_urls']['spotify'] ?? '',
                        'title' => $episodeTitle,
                        'description' => $episode['description'] ?? '',
                        'published_at' => $episode['release_date'] ?? '',
                        'thumbnail' => $episode['images'][0]['url'] ?? '',
                        'duration_ms' => $episode['duration_ms'] ?? 0,
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
            \Log::error('Spotify API Error: ' . $e->getMessage());
            return [];
        }
    }
}
