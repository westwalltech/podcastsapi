<?php

namespace NewSong\PodcastLinkFinder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NewSong\PodcastLinkFinder\Services\TransistorService;
use NewSong\PodcastLinkFinder\Services\SpotifyService;
use NewSong\PodcastLinkFinder\Services\YouTubeService;
use NewSong\PodcastLinkFinder\Services\ApplePodcastsService;

class PodcastSearchController
{
    protected TransistorService $transistor;
    protected SpotifyService $spotify;
    protected YouTubeService $youtube;
    protected ApplePodcastsService $apple;

    public function __construct(
        TransistorService $transistor,
        SpotifyService $spotify,
        YouTubeService $youtube,
        ApplePodcastsService $apple
    ) {
        $this->transistor = $transistor;
        $this->spotify = $spotify;
        $this->youtube = $youtube;
        $this->apple = $apple;
    }

    /**
     * Search for episodes in Transistor
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchEpisodes(Request $request): JsonResponse
    {
        $query = $request->input('query', '');

        if (empty($query)) {
            $episodes = $this->transistor->getRecentEpisodes(
                config('podcast-link-finder.search.max_results', 20)
            );
        } else {
            $episodes = $this->transistor->searchEpisodes($query);
        }

        return response()->json([
            'success' => true,
            'episodes' => $episodes->values()->all(),
        ]);
    }

    /**
     * Find links across all platforms for a selected episode
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function findLinks(Request $request): JsonResponse
    {
        $episodeId = $request->input('episode_id');

        if (!$episodeId) {
            return response()->json([
                'success' => false,
                'message' => 'Episode ID is required',
            ], 400);
        }

        // Get the episode details from Transistor
        $episode = $this->transistor->getEpisode($episodeId);

        if (!$episode) {
            return response()->json([
                'success' => false,
                'message' => 'Episode not found',
            ], 404);
        }

        $title = $episode['title'];
        $publishDate = $episode['published_at'];

        // Search each platform
        $links = [
            'spotify' => null,
            'apple_podcasts' => null,
            'youtube' => null,
        ];

        try {
            // Find Spotify link
            $links['spotify'] = $this->spotify->findEpisodeByTitle($title, $publishDate);
        } catch (\Exception $e) {
            \Log::warning("Failed to find Spotify link: {$e->getMessage()}");
        }

        try {
            // Find Apple Podcasts link
            $links['apple_podcasts'] = $this->apple->findEpisodeByTitle($title, $publishDate);
        } catch (\Exception $e) {
            \Log::warning("Failed to find Apple Podcasts link: {$e->getMessage()}");
        }

        try {
            // Find YouTube link
            $links['youtube'] = $this->youtube->findVideoByTitle($title, $publishDate);
        } catch (\Exception $e) {
            \Log::warning("Failed to find YouTube link: {$e->getMessage()}");
        }

        return response()->json([
            'success' => true,
            'episode' => $episode,
            'links' => $links,
        ]);
    }

    /**
     * Search for all matching content across platforms
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchPlatforms(Request $request): JsonResponse
    {
        $episodeId = $request->input('episode_id');

        if (!$episodeId) {
            return response()->json([
                'success' => false,
                'message' => 'Episode ID is required',
            ], 400);
        }

        // Get the episode details from Transistor
        $episode = $this->transistor->getEpisode($episodeId);

        if (!$episode) {
            return response()->json([
                'success' => false,
                'message' => 'Episode not found',
            ], 404);
        }

        $title = $episode['title'];
        $publishDate = $episode['published_at'];

        // Search each platform for all matches
        $results = [
            'spotify' => [],
            'apple_podcasts' => [],
            'youtube' => [],
        ];

        $warnings = [];

        try {
            $results['spotify'] = $this->spotify->searchAllMatches($title, $publishDate);
        } catch (\Exception $e) {
            \Log::warning("Failed to search Spotify: {$e->getMessage()}");
            $warnings['spotify'] = 'Failed to search Spotify. Check API credentials.';
        }

        try {
            $results['apple_podcasts'] = $this->apple->searchAllMatches($title, $publishDate);
        } catch (\Exception $e) {
            \Log::warning("Failed to search Apple Podcasts: {$e->getMessage()}");
            $warnings['apple_podcasts'] = 'Failed to search Apple Podcasts. Check API credentials.';
        }

        try {
            $results['youtube'] = $this->youtube->searchAllMatches($title, $publishDate);
            // If YouTube returns empty results, check if it's an API error
            if (empty($results['youtube'])) {
                $youtubeStatus = $this->youtube->testConnection();
                if (!$youtubeStatus['success']) {
                    $warnings['youtube'] = $youtubeStatus['message'];
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to search YouTube: {$e->getMessage()}");
            $warnings['youtube'] = 'Failed to search YouTube. ' . $e->getMessage();
        }

        return response()->json([
            'success' => true,
            'episode' => $episode,
            'results' => $results,
            'warnings' => $warnings,
        ]);
    }
}
