<?php

namespace NewSong\PodcastLinkFinder\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TransistorService
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl = 'https://api.transistor.fm/v1/';

    public function __construct()
    {
        $this->apiKey = config('podcast-link-finder.transistor.api_key');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Get recent episodes from Transistor
     *
     * @param int $limit
     * @param string|null $status Filter by status: 'published', 'scheduled', 'draft', or null for all
     * @return Collection
     */
    public function getRecentEpisodes(int $limit = 20, ?string $status = 'published'): Collection
    {
        try {
            $query = [
                'pagination[per]' => $limit,
            ];

            if ($status !== null) {
                $query['status'] = $status;
            }

            $response = $this->client->get('episodes', [
                'query' => $query,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return collect($data['data'] ?? [])->map(function ($episode) {
                return [
                    'id' => $episode['id'],
                    'title' => $episode['attributes']['title'],
                    'description' => $episode['attributes']['description'] ?? '',
                    'published_at' => $episode['attributes']['published_at'],
                    'duration' => $episode['attributes']['duration'] ?? 0,
                    'audio_url' => $episode['attributes']['media_url'] ?? '',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Transistor API Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Search episodes by title
     *
     * @param string $query
     * @param string|null $status Filter by status: 'published', 'scheduled', 'draft', or null for all
     * @param int $limit Maximum number of episodes to search through
     * @return Collection
     */
    public function searchEpisodes(string $query, ?string $status = 'published', int $limit = 50): Collection
    {
        $episodes = $this->getRecentEpisodes($limit, $status);

        return $episodes->filter(function ($episode) use ($query) {
            return stripos($episode['title'], $query) !== false;
        });
    }

    /**
     * Get a specific episode by ID
     *
     * @param string $episodeId
     * @return array|null
     */
    public function getEpisode(string $episodeId): ?array
    {
        try {
            $response = $this->client->get("episodes/{$episodeId}");
            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['data'])) {
                $episode = $data['data'];
                return [
                    'id' => $episode['id'],
                    'title' => $episode['attributes']['title'],
                    'description' => $episode['attributes']['description'] ?? '',
                    'published_at' => $episode['attributes']['published_at'],
                    'duration' => $episode['attributes']['duration'] ?? 0,
                    'audio_url' => $episode['attributes']['media_url'] ?? '',
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Transistor API Error: ' . $e->getMessage());
            return null;
        }
    }
}
