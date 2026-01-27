<?php

namespace NewSong\PodcastLinkFinder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Statamic\Facades\Entry;
use NewSong\PodcastLinkFinder\Services\YouTubeLivestreamService;
use Carbon\Carbon;

class YouTubeLivestreamController
{
    protected YouTubeLivestreamService $livestream;

    public function __construct(YouTubeLivestreamService $livestream)
    {
        $this->livestream = $livestream;
    }

    /**
     * Fetch YouTube livestream for a specific entry
     *
     * @param Request $request
     * @param string $entryId
     * @return JsonResponse
     */
    public function fetch(Request $request, string $entryId): JsonResponse
    {
        // Check if feature is enabled
        if (!config('youtube-livestream.enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'YouTube livestream fetch is disabled',
            ], 400);
        }

        // Check if service is configured
        if (!$this->livestream->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'YouTube API is not configured',
            ], 400);
        }

        // Get the entry
        $entry = Entry::find($entryId);

        if (!$entry) {
            return response()->json([
                'success' => false,
                'message' => 'Entry not found',
            ], 404);
        }

        // Get the air_date from the entry
        $dateField = config('youtube-livestream.date_field', 'air_date');
        $airDate = $entry->get($dateField);

        if (!$airDate) {
            return response()->json([
                'success' => false,
                'message' => "Entry does not have a {$dateField} field",
            ], 400);
        }

        // Parse the date
        $timezone = config('youtube-livestream.schedule.timezone', 'America/Chicago');

        try {
            $targetDate = Carbon::parse($airDate, $timezone)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format in ' . $dateField,
            ], 400);
        }

        // Check if existing URL should be preserved
        $urlField = config('youtube-livestream.url_field', 'youtube_url');
        $currentUrl = $entry->get($urlField);

        if (!empty($currentUrl) && config('youtube-livestream.overwrite.validate_existing', true)) {
            $isValid = $this->livestream->isValidLivestreamUrl($currentUrl);

            if ($isValid && config('youtube-livestream.overwrite.preserve_valid', true)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Existing URL is still valid',
                    'url' => $currentUrl,
                    'preserved' => true,
                    'fetched_at' => $entry->get(config('youtube-livestream.fetched_at_field', 'youtube_fetched_at')),
                ]);
            }
        }

        // Find livestream for the target date
        $livestreamData = $this->livestream->findLivestreamByDate($targetDate);

        if (!$livestreamData) {
            return response()->json([
                'success' => false,
                'message' => 'No upcoming livestream found for ' . Carbon::parse($targetDate)->format('F j, Y'),
            ], 404);
        }

        // Update the entry
        $fetchedAtField = config('youtube-livestream.fetched_at_field', 'youtube_fetched_at');
        $fetchedAt = Carbon::now()->toDateTimeString();

        try {
            $entry->set($urlField, $livestreamData['url']);
            $entry->set($fetchedAtField, $fetchedAt);
            $entry->save();

            \Log::info('[YouTube Livestream] Manual fetch successful', [
                'entry_id' => $entryId,
                'entry_title' => $entry->title ?? $entry->slug,
                'url' => $livestreamData['url'],
            ]);

            return response()->json([
                'success' => true,
                'url' => $livestreamData['url'],
                'title' => $livestreamData['title'],
                'scheduled_for' => $livestreamData['scheduled_start'],
                'fetched_at' => $fetchedAt,
            ]);
        } catch (\Exception $e) {
            \Log::error('[YouTube Livestream] Failed to save entry', [
                'entry_id' => $entryId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save entry. Please try again or check the logs for details.',
            ], 500);
        }
    }

    /**
     * Get upcoming livestreams (for preview/debugging)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upcoming(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if (!$this->livestream->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'YouTube API is not configured',
            ], 400);
        }

        $limit = $validated['limit'] ?? 10;
        $livestreams = $this->livestream->getUpcomingLivestreams($limit);

        return response()->json([
            'success' => true,
            'livestreams' => $livestreams,
            'count' => count($livestreams),
        ]);
    }

    /**
     * Validate an existing YouTube URL
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
        ]);

        $url = $validated['url'];
        $isValid = $this->livestream->isValidLivestreamUrl($url);
        $videoId = $this->livestream->extractVideoId($url);
        $details = $videoId ? $this->livestream->getVideoDetails($videoId) : [];

        return response()->json([
            'success' => true,
            'url' => $url,
            'is_valid' => $isValid,
            'video_id' => $videoId,
            'details' => $details,
        ]);
    }
}
