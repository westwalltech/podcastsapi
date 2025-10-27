<?php

namespace NewSong\PodcastLinkFinder\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\Entry;
use NewSong\PodcastLinkFinder\Services\TransistorService;
use NewSong\PodcastLinkFinder\Services\SpotifyService;
use NewSong\PodcastLinkFinder\Services\YouTubeService;
use NewSong\PodcastLinkFinder\Services\ApplePodcastsService;

class BulkUpdateLinksCommand extends Command
{
    protected $signature = 'podcast:bulk-update
                            {collection : The collection handle to update}
                            {--field=podcast_links : The field handle containing podcast links}
                            {--dry-run : Show what would be updated without making changes}
                            {--only-empty : Only update entries without existing links}
                            {--force-youtube : Search YouTube even if not Sunday}
                            {--platforms=* : Only search specific platforms (spotify,apple,youtube)}
                            {--limit= : Limit number of entries to process}';

    protected $description = 'Bulk update podcast platform links for all entries in a collection';

    protected TransistorService $transistor;
    protected SpotifyService $spotify;
    protected YouTubeService $youtube;
    protected ApplePodcastsService $apple;

    protected int $updated = 0;
    protected int $skipped = 0;
    protected int $errors = 0;
    protected int $youtubeQuotaUsed = 0;

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
        $collection = $this->argument('collection');
        $fieldHandle = $this->option('field');
        $dryRun = $this->option('dry-run');
        $onlyEmpty = $this->option('only-empty');
        $forceYouTube = $this->option('force-youtube');
        $platforms = $this->option('platforms');
        $limit = $this->option('limit');

        // Validate collection exists
        if (!$this->validateCollection($collection)) {
            return Command::FAILURE;
        }

        // Determine which platforms to search
        $searchPlatforms = $this->determinePlatforms($platforms);

        $this->info("Bulk updating collection: {$collection}");
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
        }
        $this->newLine();

        // Fetch entries
        $entries = Entry::query()
            ->where('collection', $collection)
            ->get();

        if ($limit) {
            $entries = $entries->take((int) $limit);
        }

        if ($onlyEmpty) {
            $entries = $entries->filter(function ($entry) use ($fieldHandle) {
                $value = $entry->get($fieldHandle);
                return empty($value) ||
                       (empty($value['spotify_link']) &&
                        empty($value['apple_podcasts_link']) &&
                        empty($value['youtube_link']));
            });
        }

        $total = $entries->count();

        if ($total === 0) {
            $this->warn('No entries found to process');
            return Command::SUCCESS;
        }

        $this->info("Found {$total} entries to process");
        $this->newLine();

        // Fetch all Transistor episodes once
        $this->info('Fetching episodes from Transistor...');
        $transistorEpisodes = $this->transistor->getRecentEpisodes(500);
        $this->info('Loaded ' . $transistorEpisodes->count() . ' episodes from Transistor');
        $this->newLine();

        // Check YouTube availability
        $youtubeAvailable = $forceYouTube || $this->youtube->isSearchAllowedToday();
        if (!$youtubeAvailable && in_array('youtube', $searchPlatforms)) {
            $this->warn('⚠️  YouTube search restricted (not Sunday). Use --force-youtube to override.');
            $this->newLine();
        }

        // Progress bar
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        // Process each entry
        foreach ($entries as $entry) {
            $entryTitle = $entry->title ?? $entry->slug;
            $bar->setMessage($entryTitle);

            try {
                $result = $this->processEntry($entry, $transistorEpisodes, $fieldHandle, $searchPlatforms, $youtubeAvailable, $dryRun);

                if ($result['updated']) {
                    $this->updated++;
                } else {
                    $this->skipped++;
                }

                if ($result['youtube_searched']) {
                    $this->youtubeQuotaUsed += 100;
                }
            } catch (\Exception $e) {
                $this->errors++;
                \Log::error("Bulk update error for entry {$entryTitle}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->setMessage('Complete!');
        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->displaySummary($dryRun);

        return Command::SUCCESS;
    }

    protected function validateCollection(string $collection): bool
    {
        $collections = \Statamic\Facades\Collection::all()->map->handle()->toArray();

        if (!in_array($collection, $collections)) {
            $this->error("Collection '{$collection}' not found.");
            $this->newLine();
            $this->line('Available collections:');
            foreach ($collections as $col) {
                $this->line('  - ' . $col);
            }
            return false;
        }

        return true;
    }

    protected function determinePlatforms(array $platforms): array
    {
        if (empty($platforms)) {
            return ['spotify', 'apple', 'youtube'];
        }

        $valid = ['spotify', 'apple', 'youtube'];
        $filtered = array_intersect($platforms, $valid);

        if (empty($filtered)) {
            $this->warn('No valid platforms specified. Using all platforms.');
            return $valid;
        }

        return $filtered;
    }

    protected function processEntry($entry, $transistorEpisodes, string $fieldHandle, array $searchPlatforms, bool $youtubeAvailable, bool $dryRun): array
    {
        $entryTitle = $entry->title ?? $entry->slug;
        $updated = false;
        $youtubeSearched = false;

        // Find matching Transistor episode
        $matchedEpisode = $this->findMatchingEpisode($entryTitle, $transistorEpisodes);

        if (!$matchedEpisode) {
            return ['updated' => false, 'youtube_searched' => false];
        }

        // Get current links
        $currentLinks = $entry->get($fieldHandle) ?? [];
        $newLinks = $currentLinks;

        // Update episode info
        $newLinks['episode_id'] = $matchedEpisode['id'];
        $newLinks['episode_title'] = $matchedEpisode['title'];

        // Search each platform
        if (in_array('spotify', $searchPlatforms) && empty($currentLinks['spotify_link'])) {
            try {
                $results = $this->spotify->searchAllMatches($matchedEpisode['title'], $matchedEpisode['published_at']);
                if (!empty($results)) {
                    $newLinks['spotify_link'] = $results[0]['url'];
                    $updated = true;
                }
            } catch (\Exception $e) {
                \Log::warning("Spotify search failed for: {$entryTitle}");
            }
        }

        if (in_array('apple', $searchPlatforms) && empty($currentLinks['apple_podcasts_link'])) {
            try {
                $results = $this->apple->searchAllMatches($matchedEpisode['title'], $matchedEpisode['published_at']);
                if (!empty($results)) {
                    $newLinks['apple_podcasts_link'] = $results[0]['url'];
                    $updated = true;
                }
            } catch (\Exception $e) {
                \Log::warning("Apple Podcasts search failed for: {$entryTitle}");
            }
        }

        if (in_array('youtube', $searchPlatforms) && empty($currentLinks['youtube_link']) && $youtubeAvailable) {
            try {
                $results = $this->youtube->searchAllMatches($matchedEpisode['title'], $matchedEpisode['published_at']);
                $youtubeSearched = true;
                if (!empty($results)) {
                    $newLinks['youtube_link'] = $results[0]['url'];
                    $updated = true;
                }
            } catch (\Exception $e) {
                \Log::warning("YouTube search failed for: {$entryTitle}");
            }
        }

        // Save if updated and not dry run
        if ($updated && !$dryRun) {
            $entry->set($fieldHandle, $newLinks);
            $entry->save();
        }

        return ['updated' => $updated, 'youtube_searched' => $youtubeSearched];
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

    protected function displaySummary(bool $dryRun): void
    {
        $this->info('Summary:');
        $this->line('  ✓ ' . ($dryRun ? 'Would update: ' : 'Updated: ') . $this->updated . ' entries');
        $this->line('  ⏭ Skipped: ' . $this->skipped . ' entries (no match)');

        if ($this->errors > 0) {
            $this->line('  ✗ Errors: ' . $this->errors . ' entries');
        }

        $this->newLine();
        $this->line('  📊 YouTube quota used: ' . $this->youtubeQuotaUsed . ' units');

        if ($dryRun) {
            $this->newLine();
            $this->info('💡 Run without --dry-run to save changes');
        }
    }
}
