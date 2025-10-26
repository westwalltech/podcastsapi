<?php

namespace NewSong\PodcastLinkFinder\Services;

use GuzzleHttp\Client;

class ApplePodcastsService
{
    protected Client $client;
    protected string $showId;
    protected string $baseUrl = 'https://itunes.apple.com/';

    public function __construct()
    {
        $this->showId = config('podcast-link-finder.apple_podcasts.show_id');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
        ]);
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
        try {
            // Use iTunes Search API to find episodes
            $response = $this->client->get('search', [
                'query' => [
                    'term' => $title,
                    'media' => 'podcast',
                    'entity' => 'podcastEpisode',
                    'limit' => 50,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $episodes = array_filter($data['results'] ?? [], function ($item) {
                return ($item['collectionId'] ?? null) == $this->showId;
            });

            // Find best match
            $bestMatch = $this->findBestMatch($episodes, $title, $publishDate);

            if ($bestMatch) {
                $episodeId = $bestMatch['trackId'];
                return "https://podcasts.apple.com/us/podcast/new-song-church-okc/id{$this->showId}?i={$episodeId}";
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Apple Podcasts API Error: ' . $e->getMessage());
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
            $episodeTitle = $episode['trackName'] ?? '';
            $score = $this->calculateSimilarity($title, $episodeTitle);

            // Boost score if dates are close
            if ($publishDate && isset($episode['releaseDate'])) {
                $dateDiff = abs(strtotime($publishDate) - strtotime($episode['releaseDate']));
                $daysDiff = $dateDiff / 86400;

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
            $response = $this->client->get('search', [
                'query' => [
                    'term' => $title,
                    'media' => 'podcast',
                    'entity' => 'podcastEpisode',
                    'limit' => 50,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $allEpisodes = $data['results'] ?? [];

            // Filter by show ID and calculate scores
            $results = [];
            foreach ($allEpisodes as $episode) {
                if (isset($episode['collectionId']) && $episode['collectionId'] == $this->showId) {
                    $episodeTitle = $episode['trackName'] ?? '';
                    $score = $this->calculateSimilarity($title, $episodeTitle);

                    // Boost score if publish dates are close
                    if ($publishDate && !empty($episode['releaseDate'])) {
                        $targetDate = new \DateTime($publishDate);
                        $episodeDate = new \DateTime($episode['releaseDate']);
                        $daysDiff = abs($targetDate->diff($episodeDate)->days);

                        if ($daysDiff <= 7) {
                            $score += 0.2;
                        }
                    }

                    if ($score > 0.3) { // Lower threshold to show more options
                        $results[] = [
                            'url' => $episode['trackViewUrl'] ?? '',
                            'title' => $episodeTitle,
                            'description' => $episode['description'] ?? '',
                            'published_at' => $episode['releaseDate'] ?? '',
                            'thumbnail' => $episode['artworkUrl60'] ?? '',
                            'duration_ms' => ($episode['trackTimeMillis'] ?? 0),
                            'score' => round($score * 100, 1),
                        ];
                    }
                }
            }

            // Sort by score descending
            usort($results, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            return $results;
        } catch (\Exception $e) {
            \Log::error('Apple Podcasts API Error: ' . $e->getMessage());
            return [];
        }
    }
}
