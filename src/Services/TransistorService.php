<?php

namespace NewSong\PodcastLinkFinder\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;

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
     * @return Collection
     */
    public function getRecentEpisodes(int $limit = 20): Collection
    {
        try {
            $response = $this->client->get('episodes', [
                'query' => [
                    'pagination[per]' => $limit,
                    'status' => 'published',
                ],
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
            \Log::error('Transistor API Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Search episodes by title
     *
     * @param string $query
     * @return Collection
     */
    public function searchEpisodes(string $query): Collection
    {
        $episodes = $this->getRecentEpisodes(50);

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
            \Log::error('Transistor API Error: ' . $e->getMessage());
            return null;
        }
    }
}
