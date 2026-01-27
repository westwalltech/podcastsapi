<?php

namespace NewSong\PodcastLinkFinder\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use NewSong\PodcastLinkFinder\Services\YouTubeLivestreamService;
use Statamic\Facades\Entry;

class FetchYouTubeLivestreamsCommand extends Command
{
    protected $signature = 'newsong:fetch-youtube-livestreams
        {--force : Run even if livestream fetch is disabled}
        {--dry-run : Preview changes without saving}
        {--date= : Specific date to fetch for (Y-m-d format)}';

    protected $description = 'Fetch YouTube livestream URLs for upcoming Sunday services';

    protected YouTubeLivestreamService $livestream;

    protected int $processed = 0;
    protected int $updated = 0;
    protected int $skipped = 0;
    protected int $errors = 0;
    protected array $results = [];

    public function __construct(YouTubeLivestreamService $livestream)
    {
        parent::__construct();
        $this->livestream = $livestream;
    }

    public function handle(): int
    {
        // Check if enabled
        if (! config('youtube-livestream.enabled', true) && ! $this->option('force')) {
            $this->info('YouTube livestream fetch is disabled. Use --force to run anyway.');
            \Log::info('[YouTube Livestream Fetch] Skipped - feature is disabled');

            return Command::SUCCESS;
        }

        // Check if service is configured
        if (! $this->livestream->isConfigured()) {
            $this->error('YouTube API is not configured. Please set YOUTUBE_API_KEY and YOUTUBE_CHANNEL_ID.');
            \Log::error('[YouTube Livestream Fetch] Failed - API not configured');

            return Command::FAILURE;
        }

        $isDryRun = $this->option('dry-run');
        $timezone = config('youtube-livestream.schedule.timezone', 'America/Chicago');

        // Determine target date
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'), $timezone)
            : $this->getNextSunday($timezone);

        $this->info("Fetching YouTube livestreams for: {$targetDate->format('l, F j, Y')}");
        \Log::info('[YouTube Livestream Fetch] Started', [
            'target_date' => $targetDate->format('Y-m-d'),
            'dry_run' => $isDryRun,
        ]);

        // Get entries matching the target date
        $entries = $this->getEntriesForDate($targetDate);

        if ($entries->isEmpty()) {
            $this->info('No entries found for '.$targetDate->format('Y-m-d'));
            \Log::info('[YouTube Livestream Fetch] No entries found for date');

            return Command::SUCCESS;
        }

        $this->info("Found {$entries->count()} entries to process");

        // Find livestream for the target date
        $livestream = $this->livestream->findLivestreamByDate($targetDate->format('Y-m-d'));

        if (! $livestream) {
            $this->warn('No upcoming livestream found for '.$targetDate->format('Y-m-d'));
            \Log::warning('[YouTube Livestream Fetch] No livestream found for date', [
                'date' => $targetDate->format('Y-m-d'),
            ]);

            return Command::SUCCESS;
        }

        $this->info("Found livestream: {$livestream['title']}");
        $this->info("URL: {$livestream['url']}");

        // Process each entry
        foreach ($entries as $entry) {
            $this->processEntry($entry, $livestream, $isDryRun);
        }

        // Output summary
        $this->outputSummary($isDryRun);

        return Command::SUCCESS;
    }

    /**
     * Get the next Sunday (or current day if today is Sunday)
     */
    protected function getNextSunday(string $timezone): Carbon
    {
        $now = Carbon::now($timezone);

        if ($now->isSunday()) {
            return $now->startOfDay();
        }

        return $now->next(Carbon::SUNDAY)->startOfDay();
    }

    /**
     * Get entries for a specific date
     */
    protected function getEntriesForDate(Carbon $targetDate)
    {
        $collection = config('youtube-livestream.collection', 'messages');
        $dateField = config('youtube-livestream.date_field', 'air_date');
        $timezone = config('youtube-livestream.schedule.timezone', 'America/Chicago');

        $entries = Entry::query()
            ->where('collection', $collection)
            ->get();

        // Filter by air_date matching target date
        return $entries->filter(function ($entry) use ($dateField, $targetDate, $timezone) {
            $airDate = $entry->get($dateField);

            if (! $airDate) {
                return false;
            }

            // Parse the air_date and compare
            try {
                $entryDate = Carbon::parse($airDate, $timezone)->startOfDay();

                return $entryDate->equalTo($targetDate->startOfDay());
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    /**
     * Process a single entry
     */
    protected function processEntry($entry, array $livestream, bool $isDryRun): void
    {
        $this->processed++;

        $entryTitle = $entry->title ?? $entry->slug ?? $entry->id();
        $urlField = config('youtube-livestream.url_field', 'youtube_url');
        $fetchedAtField = config('youtube-livestream.fetched_at_field', 'youtube_fetched_at');

        $currentUrl = $entry->get($urlField);
        $shouldUpdate = false;
        $reason = '';

        // Determine if we should update
        if (empty($currentUrl)) {
            $shouldUpdate = true;
            $reason = 'Field is empty';
        } elseif (config('youtube-livestream.overwrite.validate_existing', true)) {
            // Check if existing URL is still valid
            $isValid = $this->livestream->isValidLivestreamUrl($currentUrl);

            if (! $isValid && config('youtube-livestream.overwrite.replace_invalid', true)) {
                $shouldUpdate = true;
                $reason = 'Existing URL is no longer valid';
            } elseif ($isValid && config('youtube-livestream.overwrite.preserve_valid', true)) {
                $shouldUpdate = false;
                $reason = 'Existing URL is still valid';
            }
        }

        if (! $shouldUpdate) {
            $this->skipped++;
            $this->results[] = [
                'title' => $entryTitle,
                'status' => 'skipped',
                'reason' => $reason,
                'url' => $currentUrl,
            ];

            $this->line("  Skipped: {$entryTitle} - {$reason}");
            \Log::info('[YouTube Livestream Fetch] Skipped', [
                'entry' => $entryTitle,
                'reason' => $reason,
            ]);

            return;
        }

        // Update the entry
        if (! $isDryRun) {
            try {
                $entry->set($urlField, $livestream['url']);
                $entry->set($fetchedAtField, Carbon::now()->toDateTimeString());
                $entry->save();

                $this->updated++;
                $this->results[] = [
                    'title' => $entryTitle,
                    'status' => 'updated',
                    'url' => $livestream['url'],
                    'reason' => $reason,
                ];

                $this->info("  Updated: {$entryTitle}");
                \Log::info('[YouTube Livestream Fetch] Updated', [
                    'entry' => $entryTitle,
                    'url' => $livestream['url'],
                    'reason' => $reason,
                ]);
            } catch (\Exception $e) {
                $this->errors++;
                $this->results[] = [
                    'title' => $entryTitle,
                    'status' => 'error',
                    'reason' => $e->getMessage(),
                ];

                $this->error("  Error: {$entryTitle} - {$e->getMessage()}");
                \Log::error('[YouTube Livestream Fetch] Error', [
                    'entry' => $entryTitle,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            $this->updated++;
            $this->results[] = [
                'title' => $entryTitle,
                'status' => 'would_update',
                'url' => $livestream['url'],
                'reason' => $reason,
            ];

            $this->info("  Would update: {$entryTitle} ({$reason})");
        }
    }

    /**
     * Output summary
     */
    protected function outputSummary(bool $isDryRun): void
    {
        $this->newLine();

        if ($isDryRun) {
            $this->warn('DRY RUN - No changes were saved');
        }

        $this->info('Summary:');
        $this->line("  Processed: {$this->processed}");
        $this->line("  Updated: {$this->updated}");
        $this->line("  Skipped: {$this->skipped}");

        if ($this->errors > 0) {
            $this->error("  Errors: {$this->errors}");
        }

        \Log::info('[YouTube Livestream Fetch] Completed', [
            'processed' => $this->processed,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'dry_run' => $isDryRun,
        ]);
    }
}
