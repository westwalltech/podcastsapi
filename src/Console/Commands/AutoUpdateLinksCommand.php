<?php

namespace NewSong\PodcastLinkFinder\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use NewSong\PodcastLinkFinder\Services\ApplePodcastsService;
use NewSong\PodcastLinkFinder\Services\SpotifyService;
use NewSong\PodcastLinkFinder\Services\TransistorService;
use NewSong\PodcastLinkFinder\Services\YouTubeService;
use Statamic\Facades\Entry;

class AutoUpdateLinksCommand extends Command
{
    protected $signature = 'podcast:auto-update {--force : Run even if auto-update is disabled}';
    protected $description = 'Automatically update podcast platform links for recent entries (scheduled task)';

    protected TransistorService $transistor;
    protected SpotifyService $spotify;
    protected YouTubeService $youtube;
    protected ApplePodcastsService $apple;

    protected int $processed = 0;
    protected int $updated = 0;
    protected int $skipped = 0;
    protected int $errors = 0;
    protected int $youtubeQuotaUsed = 0;
    protected array $updatedEntries = [];

    public function __construct(
        TransistorService $transistor,
        SpotifyService $spotify,
        YouTubeService $youtube,
        ApplePodcastsService $apple
    ) {
        parent::__construct();
        $this->transistor = $transistor;
        $this->spotify = $spotify;
        $this->youtube = $youtube;
        $this->apple = $apple;
    }

    public function handle()
    {
        // Check if auto-update is enabled
        if (! config('podcast-link-finder.auto_update.enabled') && ! $this->option('force')) {
            \Log::info('Podcast auto-update is disabled. Use --force to run anyway.');

            return Command::SUCCESS;
        }

        $collection = config('podcast-link-finder.auto_update.collection');
        $fieldHandle = config('podcast-link-finder.auto_update.field');
        $daysLookback = config('podcast-link-finder.auto_update.days_lookback', 7);

        \Log::info('Podcast auto-update started', [
            'collection' => $collection,
            'days_lookback' => $daysLookback,
        ]);

        // Fetch entries created/modified in last N days
        $cutoffDate = Carbon::now()->subDays($daysLookback);

        $allEntries = Entry::query()
            ->where('collection', $collection)
            ->get();

        // Filter by date in memory (Statamic's query builder doesn't handle date comparisons well)
        $entries = $allEntries->filter(function ($entry) use ($cutoffDate) {
            $updatedAt = $entry->lastModified();
            $createdAt = $entry->date(); // or created_at if available

            return ($updatedAt && $updatedAt >= $cutoffDate) ||
                   ($createdAt && $createdAt >= $cutoffDate);
        });

        $total = $entries->count();

        if ($total === 0) {
            \Log::info("No entries found from last {$daysLookback} days");

            return Command::SUCCESS;
        }

        \Log::info("Found {$total} entries from last {$daysLookback} days");

        // Fetch all Transistor episodes once
        $transistorEpisodes = $this->transistor->getRecentEpisodes(500);
        \Log::info('Loaded '.$transistorEpisodes->count().' episodes from Transistor');

        // Process each entry
        foreach ($entries as $entry) {
            $this->processed++;
            $entryTitle = $entry->title ?? $entry->slug;

            try {
                $result = $this->processEntry($entry, $transistorEpisodes, $fieldHandle);

                if ($result['updated']) {
                    $this->updated++;
                    $this->updatedEntries[] = [
                        'title' => $entryTitle,
                        'platforms' => $result['platforms_added'],
                    ];

                    \Log::info("Updated: {$entryTitle}", [
                        'added_platforms' => implode(', ', $result['platforms_added']),
                    ]);
                } else {
                    $this->skipped++;
                    \Log::info("Skipped: {$entryTitle} - ".$result['reason']);
                }

                if ($result['youtube_searched']) {
                    $this->youtubeQuotaUsed += 100;
                }
            } catch (\Exception $e) {
                $this->errors++;
                \Log::error("Error processing {$entryTitle}: ".$e->getMessage());
            }
        }

        // Log summary
        $this->logSummary();

        return Command::SUCCESS;
    }

    protected function processEntry($entry, $transistorEpisodes, string $fieldHandle): array
    {
        $entryTitle = $entry->title ?? $entry->slug;
        $updated = false;
        $youtubeSearched = false;
        $platformsAdded = [];

        // Find matching Transistor episode
        $matchedEpisode = $this->findMatchingEpisode($entryTitle, $transistorEpisodes);

        if (! $matchedEpisode) {
            return [
                'updated' => false,
                'youtube_searched' => false,
                'platforms_added' => [],
                'reason' => 'No matching Transistor episode found',
            ];
        }

        // Get current links
        $currentLinks = $entry->get($fieldHandle) ?? [];
        $newLinks = $currentLinks;

        // Update episode info
        $newLinks['episode_id'] = $matchedEpisode['id'];
        $newLinks['episode_title'] = $matchedEpisode['title'];

        // Check which platforms are missing and search only those
        if (empty($currentLinks['spotify_link'])) {
            try {
                $results = $this->spotify->searchAllMatches($matchedEpisode['title'], $matchedEpisode['published_at']);
                if (! empty($results)) {
                    $newLinks['spotify_link'] = $results[0]['url'];
                    $platformsAdded[] = 'Spotify';
                    $updated = true;
                }
            } catch (\Exception $e) {
                \Log::warning("Spotify search failed for: {$entryTitle}");
            }
        }

        if (empty($currentLinks['apple_podcasts_link'])) {
            try {
                $results = $this->apple->searchAllMatches($matchedEpisode['title'], $matchedEpisode['published_at']);
                if (! empty($results)) {
                    $newLinks['apple_podcasts_link'] = $results[0]['url'];
                    $platformsAdded[] = 'Apple Podcasts';
                    $updated = true;
                }
            } catch (\Exception $e) {
                \Log::warning("Apple Podcasts search failed for: {$entryTitle}");
            }
        }

        if (empty($currentLinks['youtube_link'])) {
            try {
                $results = $this->youtube->searchAllMatches($matchedEpisode['title'], $matchedEpisode['published_at']);
                $youtubeSearched = true;
                if (! empty($results)) {
                    $newLinks['youtube_link'] = $results[0]['url'];
                    $platformsAdded[] = 'YouTube';
                    $updated = true;
                }
            } catch (\Exception $e) {
                \Log::warning("YouTube search failed for: {$entryTitle}");
            }
        }

        // Save if updated
        if ($updated) {
            $entry->set($fieldHandle, $newLinks);
            $entry->save();
        }

        return [
            'updated' => $updated,
            'youtube_searched' => $youtubeSearched,
            'platforms_added' => $platformsAdded,
            'reason' => $updated ? 'Updated' : 'All platforms already present',
        ];
    }

    protected function findMatchingEpisode(string $entryTitle, $transistorEpisodes): ?array
    {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($transistorEpisodes as $episode) {
            $score = $this->calculateSimilarity($entryTitle, $episode['title']);

            if ($score > $bestScore && $score > 0.6) {
                $bestScore = $score;
                $bestMatch = $episode;
            }
        }

        return $bestMatch;
    }

    protected function calculateSimilarity(string $str1, string $str2): float
    {
        similar_text(strtolower($str1), strtolower($str2), $percent);

        return $percent / 100;
    }

    protected function logSummary(): void
    {
        \Log::info('Podcast auto-update completed', [
            'processed' => $this->processed,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'youtube_quota_used' => $this->youtubeQuotaUsed,
        ]);

        if (! empty($this->updatedEntries)) {
            \Log::info('Updated entries:', $this->updatedEntries);
        }

        // Also output to console if run manually
        if ($this->output->isVerbose()) {
            $this->newLine();
            $this->info('Summary:');
            $this->line("  Processed: {$this->processed}");
            $this->line("  Updated: {$this->updated}");
            $this->line("  Skipped: {$this->skipped}");
            if ($this->errors > 0) {
                $this->line("  Errors: {$this->errors}");
            }
            $this->line("  YouTube quota used: {$this->youtubeQuotaUsed} units");
        }
    }
}
